import SwiftUI
import UserNotifications

// Shared store: fetches in-app notifications, tracks the unread badge, and
// surfaces new items as local phone notifications when the app is opened.
@MainActor
final class NotificationCenterStore: ObservableObject {
    static let shared = NotificationCenterStore()

    @Published var items: [AppNotification] = []
    @Published var unreadCount = 0
    @Published var isLoading = false

    private var lastSeenId: Int {
        get { UserDefaults.standard.integer(forKey: "last_seen_notification_id") }
        set { UserDefaults.standard.set(newValue, forKey: "last_seen_notification_id") }
    }

    func load(postSystemNotifications: Bool = false) async {
        isLoading = true
        do {
            let tm = TokenManager.shared
            let fetched = try await APIClient.shared.getNotifications(guestId: tm.isGuest ? tm.guestId : nil)
            items = fetched
            unreadCount = fetched.filter { !$0.is_read }.count
            if postSystemNotifications {
                postLocalNotificationsForNew(fetched)
            }
        } catch { }
        isLoading = false
    }

    func markRead(_ id: Int) async {
        let tm = TokenManager.shared
        try? await APIClient.shared.markNotificationRead(id: id, guestId: tm.isGuest ? tm.guestId : nil)
        items = items.map { n in
            guard n.id == id else { return n }
            return AppNotification(id: n.id, title: n.title, body: n.body, type: n.type, series_id: n.series_id, is_read: true, created_at: n.created_at)
        }
        unreadCount = items.filter { !$0.is_read }.count
    }

    private func postLocalNotificationsForNew(_ fetched: [AppNotification]) {
        let fresh = fetched.filter { !$0.is_read && $0.id > lastSeenId }
        guard !fresh.isEmpty else { return }
        lastSeenId = fetched.map(\.id).max() ?? lastSeenId

        let center = UNUserNotificationCenter.current()
        center.requestAuthorization(options: [.alert, .sound, .badge]) { granted, _ in
            guard granted else { return }
            for n in fresh.prefix(3) {
                let content = UNMutableNotificationContent()
                content.title = n.title
                content.body = n.body ?? ""
                content.sound = .default
                let request = UNNotificationRequest(identifier: "soloreel-\(n.id)", content: content, trigger: nil)
                center.add(request)
            }
        }
    }
}

/// Bell icon with unread badge — shown on the Home screen.
struct NotificationBell: View {
    @ObservedObject var store = NotificationCenterStore.shared

    var body: some View {
        NavigationLink(destination: NotificationsView()) {
            ZStack(alignment: .topTrailing) {
                Image(systemName: "bell.fill")
                    .foregroundColor(.white)
                    .padding(10)
                    .background(Color.black.opacity(0.4))
                    .clipShape(Circle())
                if store.unreadCount > 0 {
                    Text(store.unreadCount > 9 ? "9+" : "\(store.unreadCount)")
                        .font(.system(size: 10, weight: .bold))
                        .foregroundColor(.white)
                        .padding(.horizontal, 5).padding(.vertical, 1)
                        .background(Color.red)
                        .clipShape(Capsule())
                }
            }
        }
    }
}

struct NotificationsView: View {
    @ObservedObject var store = NotificationCenterStore.shared

    var body: some View {
        Group {
            if store.isLoading && store.items.isEmpty {
                ProgressView().tint(.red).frame(maxWidth: .infinity, maxHeight: .infinity)
            } else if store.items.isEmpty {
                VStack(spacing: 12) {
                    Text("🔔").font(.system(size: 52))
                    Text("No notifications yet").foregroundColor(Color(white: 0.35))
                }.frame(maxWidth: .infinity, maxHeight: .infinity)
            } else {
                ScrollView {
                    LazyVStack(spacing: 10) {
                        ForEach(store.items) { n in
                            Button {
                                if !n.is_read { Task { await store.markRead(n.id) } }
                            } label: {
                                HStack(alignment: .top, spacing: 12) {
                                    Text(n.type == "series_available" ? "🎬" : "🔔").font(.system(size: 22))
                                    VStack(alignment: .leading, spacing: 4) {
                                        Text(n.title).font(.subheadline).fontWeight(.semibold).foregroundColor(.white).multilineTextAlignment(.leading)
                                        if let body = n.body {
                                            Text(body).font(.caption).foregroundColor(Color(white: 0.6)).multilineTextAlignment(.leading)
                                        }
                                        if let date = n.created_at {
                                            Text(date).font(.caption2).foregroundColor(Color(white: 0.35))
                                        }
                                    }
                                    Spacer()
                                    if !n.is_read {
                                        Circle().fill(Color.red).frame(width: 10, height: 10)
                                    }
                                }
                                .padding(16)
                                .background(n.is_read ? Color(white: 0.07) : Color(red: 0.11, green: 0.08, blue: 0.08))
                                .cornerRadius(14)
                            }
                        }
                    }.padding(16)
                }
                .refreshable { await store.load() }
            }
        }
        .background(Color(red: 0.04, green: 0.04, blue: 0.04))
        .preferredColorScheme(.dark)
        .navigationTitle("Notifications").navigationBarTitleDisplayMode(.inline)
        .task { await store.load() }
    }
}
