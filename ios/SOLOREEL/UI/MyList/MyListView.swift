import SwiftUI

/// My List page — History / Liked / Saved. Saved is the removable "My List"
/// proper: series the viewer bookmarked while watching via the action rail's
/// save button, mirroring the Android/web MyList screens.
struct MyListView: View {
    @State private var history: [ContinueWatchingItem] = []
    @State private var liked: [Series] = []
    @State private var saved: [Series] = []
    @State private var isLoading = true
    @State private var tab = 0

    private var guestIdOrNil: String? { TokenManager.shared.isLoggedIn ? nil : TokenManager.shared.guestId }

    private func load() async {
        isLoading = true
        if let data = try? await APIClient.shared.getMyList(guestId: guestIdOrNil) {
            history = data.history
            liked = data.liked
            saved = data.saved
        }
        isLoading = false
    }

    private func removeSaved(_ series: Series) {
        saved.removeAll { $0.id == series.id }
        Task { try? await APIClient.shared.removeSavedSeries(seriesId: series.id, guestId: guestIdOrNil) }
    }

    var body: some View {
        NavigationStack {
            VStack(spacing: 0) {
                Text("My List").font(.notoSans(size: 28, relativeTo: .title)).bold().foregroundColor(.white)
                    .frame(maxWidth: .infinity, alignment: .leading).padding(.horizontal).padding(.top, 16)

                Picker("", selection: $tab) {
                    Text("History").tag(0)
                    Text("Liked").tag(1)
                    Text("Saved").tag(2)
                }
                .pickerStyle(.segmented)
                .padding(.horizontal)
                .padding(.vertical, 12)

                if isLoading {
                    Spacer()
                    ProgressView().tint(.red)
                    Spacer()
                } else {
                    ScrollView {
                        switch tab {
                        case 0: historyGrid
                        case 1: likedGrid
                        default: savedGrid
                        }
                    }
                }
            }
            .background(Color.black)
            .preferredColorScheme(.dark)
        }
        .task { await load() }
    }

    private var historyGrid: some View {
        Group {
            if history.isEmpty {
                emptyState("You haven't watched anything yet.")
            } else {
                LazyVGrid(columns: [GridItem(.flexible()), GridItem(.flexible())], spacing: 16) {
                    ForEach(history) { item in
                        NavigationLink(destination: PlayerView(slug: item.episode_slug)) {
                            MyListCard(title: item.title, coverUrl: item.cover_image_url, badge: "EP.\(item.episode_number ?? 1)/\(item.episode_count ?? 1)")
                        }
                    }
                }.padding(16)
            }
        }
    }

    private var likedGrid: some View {
        Group {
            if liked.isEmpty {
                emptyState("You haven't liked any series yet.")
            } else {
                LazyVGrid(columns: [GridItem(.flexible()), GridItem(.flexible())], spacing: 16) {
                    ForEach(liked) { s in
                        NavigationLink(destination: SeriesDetailView(slug: s.slug)) {
                            MyListCard(title: s.title, coverUrl: s.cover_image_url, badge: "EP.\(s.episode_count ?? 0)")
                        }
                    }
                }.padding(16)
            }
        }
    }

    private var savedGrid: some View {
        Group {
            if saved.isEmpty {
                emptyState("Save series while watching to see them here.")
            } else {
                LazyVGrid(columns: [GridItem(.flexible()), GridItem(.flexible())], spacing: 16) {
                    ForEach(saved) { s in
                        ZStack(alignment: .topTrailing) {
                            NavigationLink(destination: SeriesDetailView(slug: s.slug)) {
                                MyListCard(title: s.title, coverUrl: s.cover_image_url, badge: "EP.\(s.episode_count ?? 0)")
                            }
                            Button { removeSaved(s) } label: {
                                Image(systemName: "xmark")
                                    .foregroundColor(.white)
                                    .padding(6)
                                    .background(Color.black.opacity(0.7))
                                    .clipShape(Circle())
                            }.padding(6)
                        }
                    }
                }.padding(16)
            }
        }
    }

    private func emptyState(_ message: String) -> some View {
        Text(message).foregroundColor(Color(white: 0.4)).multilineTextAlignment(.center).padding(32)
            .frame(maxWidth: .infinity)
    }
}

private struct MyListCard: View {
    let title: String
    let coverUrl: String?
    let badge: String

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            ZStack(alignment: .topLeading) {
                AsyncImage(url: URL(string: coverUrl ?? "")) { phase in
                    if let image = phase.image { image.resizable().aspectRatio(contentMode: .fill) }
                    else { Color.gray }
                }
                .frame(maxWidth: .infinity, minHeight: 220, maxHeight: 220)
                .cornerRadius(12)
                .clipped()

                Text(badge).font(.notoSans(size: 10, weight: .bold, relativeTo: .caption2)).foregroundColor(.white)
                    .padding(.horizontal, 6).padding(.vertical, 2)
                    .background(Color.black.opacity(0.6)).cornerRadius(4)
                    .padding(6)
            }
            Text(title).font(.notoSans(size: 13, weight: .semibold, relativeTo: .subheadline)).foregroundColor(.white).lineLimit(1)
        }
    }
}
