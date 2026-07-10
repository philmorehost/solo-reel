import SwiftUI

struct ProfileView: View {
    @ObservedObject var tokenManager = TokenManager.shared
    @State private var bonusStatus: WeeklyBonusStatus? = nil
    @State private var vipStatus: VipStatus? = nil
    @State private var transactions: [Transaction] = []

    private func loadExtras() async {
        guard tokenManager.isLoggedIn else { return }
        bonusStatus = try? await APIClient.shared.getBonusStatus()
        vipStatus = try? await APIClient.shared.getVipStatus()
        transactions = (try? await APIClient.shared.getTransactions()) ?? []
    }

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(spacing: 0) {
                    if tokenManager.isGuest {
                        GuestProfileSection(onLogin: {})
                    } else {
                        RegisteredProfileSection(bonusStatus: bonusStatus, vipStatus: vipStatus, transactions: transactions, onLogout: { tokenManager.logout() })
                    }
                }
            }
            .refreshable { await loadExtras() }
            .background(Color(red: 0.04, green: 0.04, blue: 0.04))
            .preferredColorScheme(.dark)
        }
        .task { await loadExtras() }
    }
}

// MARK: - Guest Profile
struct GuestProfileSection: View {
    let onLogin: () -> Void
    var body: some View {
        VStack(spacing: 20) {
            Spacer().frame(height: 40)
            // Guest avatar
            ZStack {
                Circle().fill(LinearGradient(colors: [Color(white: 0.22), Color(white: 0.1)], startPoint: .top, endPoint: .bottom)).frame(width: 100, height: 100)
                Image(systemName: "person.fill").font(.notoSans(size: 44)).foregroundColor(Color(white: 0.4))
            }
            Text("Guest User").font(.notoSans(size: 22, relativeTo: .title2)).bold().foregroundColor(.white)
            Text("Tap below to unlock all features").foregroundColor(Color(white: 0.5)).font(.notoSans(size: 15, relativeTo: .subheadline))

            // Guest coin balance
            HStack {
                VStack(alignment: .leading) {
                    Text("Guest Balance").font(.notoSans(size: 12, relativeTo: .caption)).foregroundColor(Color(white: 0.6))
                    Text("\(Int(TokenManager.shared.guestCoins)) Coins").font(.notoSans(size: 22, relativeTo: .title2)).bold().foregroundColor(.yellow)
                }
                Spacer()
                Image(systemName: "dollarsign.circle.fill").font(.notoSans(size: 40)).foregroundColor(.yellow)
            }.padding(20).background(Color(white: 0.1)).cornerRadius(16).padding(.horizontal)

            // Weekly bonus promo card
            VStack(alignment: .leading, spacing: 12) {
                HStack {
                    Text("🎁").font(.notoSans(size: 28))
                    Text("FREE Weekly Bonus!").font(.notoSans(size: 20, relativeTo: .title3)).bold().foregroundColor(Color(red: 0.86, green: 0.15, blue: 0.15))
                }
                Text("Register and get FREE coins every week!\nUse them to unlock premium episodes.\nDon't miss out!").foregroundColor(Color(white: 0.8)).font(.notoSans(size: 15, relativeTo: .subheadline)).lineSpacing(4)
                VStack(alignment: .leading, spacing: 4) {
                    Label("50 bonus coins every Monday", systemImage: "checkmark.circle.fill").foregroundColor(Color(red: 0.29, green: 0.87, blue: 0.5)).font(.notoSans(size: 15, relativeTo: .subheadline))
                    Label("Save your watch progress", systemImage: "checkmark.circle.fill").foregroundColor(Color(red: 0.29, green: 0.87, blue: 0.5)).font(.notoSans(size: 15, relativeTo: .subheadline))
                    Label("Create a favorites list", systemImage: "checkmark.circle.fill").foregroundColor(Color(red: 0.29, green: 0.87, blue: 0.5)).font(.notoSans(size: 15, relativeTo: .subheadline))
                    Label("Guests lose session on close", systemImage: "xmark.circle.fill").foregroundColor(Color(red: 0.94, green: 0.27, blue: 0.27)).font(.notoSans(size: 15, relativeTo: .subheadline))
                }
                Button(action: onLogin) {
                    Text("Register / Login Now").fontWeight(.bold).frame(maxWidth: .infinity).frame(height: 50).background(Color(red: 0.86, green: 0.15, blue: 0.15)).foregroundColor(.white).cornerRadius(12)
                }
            }.padding(20).background(Color(white: 0.07)).overlay(RoundedRectangle(cornerRadius: 20).stroke(Color(red: 0.86, green: 0.15, blue: 0.15), lineWidth: 1.5)).cornerRadius(20).padding(.horizontal)

            Text("You can still buy coins and watch episodes as a guest.\nYour coin balance is saved on this device.").foregroundColor(Color(white: 0.35)).font(.notoSans(size: 12, relativeTo: .caption)).multilineTextAlignment(.center).padding(.horizontal, 32)
            Spacer().frame(height: 40)
        }
    }
}

// MARK: - Registered Profile
struct RegisteredProfileSection: View {
    let bonusStatus: WeeklyBonusStatus?
    let vipStatus: VipStatus?
    let transactions: [Transaction]
    let onLogout: () -> Void
    var body: some View {
        VStack(spacing: 16) {
            Spacer().frame(height: 40)
            // Avatar
            ZStack {
                Circle().fill(LinearGradient(colors: [Color(red: 0.86, green: 0.15, blue: 0.15), Color(red: 0.5, green: 0.07, blue: 0.07)], startPoint: .top, endPoint: .bottom)).frame(width: 100, height: 100)
                Text(String(TokenManager.shared.username?.first?.uppercased() ?? "U")).font(.notoSans(size: 40)).bold().foregroundColor(.white)
            }
            Text(TokenManager.shared.username ?? "User").font(.notoSans(size: 22, relativeTo: .title2)).bold().foregroundColor(.white)
            Text(TokenManager.shared.email ?? "").foregroundColor(Color(white: 0.4)).font(.notoSans(size: 15, relativeTo: .subheadline))

            if vipStatus?.is_vip == true {
                HStack(spacing: 6) {
                    Text("👑").font(.notoSans(size: 13))
                    Text("VIP · \(vipStatus?.plan_name ?? "Member")").font(.notoSans(size: 13, weight: .bold, relativeTo: .caption))
                }
                .foregroundColor(Color(red: 0.96, green: 0.62, blue: 0.04))
                .padding(.horizontal, 14).padding(.vertical, 6)
                .background(LinearGradient(colors: [Color(red: 0.96, green: 0.62, blue: 0.04).opacity(0.2), Color(red: 0.92, green: 0.7, blue: 0.03).opacity(0.2)], startPoint: .leading, endPoint: .trailing))
                .cornerRadius(20)
            }

            // Coin balance
            HStack {
                VStack(alignment: .leading) {
                    Text("Coin Balance").font(.notoSans(size: 12, relativeTo: .caption)).foregroundColor(Color(white: 0.6))
                    Text("\(Int(TokenManager.shared.coins)) Coins").font(.notoSans(size: 22, relativeTo: .title2)).bold().foregroundColor(.yellow)
                }
                Spacer()
                Image(systemName: "dollarsign.circle.fill").font(.notoSans(size: 40)).foregroundColor(.yellow)
            }.padding(20).background(Color(white: 0.1)).cornerRadius(16).padding(.horizontal)

            // Weekly bonus card
            if let bonus = bonusStatus, bonus.bonus_coins > 0 {
                HStack {
                    Text("🎁").font(.notoSans(size: 28))
                    VStack(alignment: .leading, spacing: 2) {
                        Text("Weekly Bonus").font(.notoSans(size: 15, relativeTo: .subheadline)).bold().foregroundColor(Color(red: 0.29, green: 0.87, blue: 0.5))
                        Text("\(Int(bonus.bonus_coins)) coins available").font(.notoSans(size: 17, weight: .semibold, relativeTo: .headline)).foregroundColor(.white)
                        if let exp = bonus.bonus_expires_at { Text("Expires: \(exp.prefix(10))").font(.notoSans(size: 12, relativeTo: .caption)).foregroundColor(Color(white: 0.5)) }
                    }
                    Spacer()
                }.padding(16).background(Color(red: 0.06, green: 0.1, blue: 0.06)).overlay(RoundedRectangle(cornerRadius: 14).stroke(Color(red: 0.29, green: 0.87, blue: 0.5), lineWidth: 1)).cornerRadius(14).padding(.horizontal)
            }

            // Recent Transactions — coin purchases + VIP subscription payments, merged.
            if !transactions.isEmpty {
                VStack(alignment: .leading, spacing: 8) {
                    Text("Recent Transactions").font(.notoSans(size: 16, weight: .bold, relativeTo: .headline)).foregroundColor(.white)
                    VStack(spacing: 0) {
                        ForEach(Array(transactions.enumerated()), id: \.element.id) { index, txn in
                            HStack {
                                VStack(alignment: .leading, spacing: 2) {
                                    Text(txn.description).font(.notoSans(size: 13, weight: .semibold, relativeTo: .subheadline)).foregroundColor(Color(white: 0.85)).lineLimit(1)
                                    Text(String(txn.created_at.prefix(10))).font(.notoSans(size: 11, relativeTo: .caption2)).foregroundColor(Color(white: 0.4))
                                }
                                Spacer()
                                if txn.kind == "vip" {
                                    Text("\(txn.currency ?? "") \(String(format: "%.2f", txn.amount))").font(.notoSans(size: 13, weight: .bold, relativeTo: .subheadline)).foregroundColor(Color(red: 0.96, green: 0.62, blue: 0.04))
                                } else {
                                    Text("\(txn.amount > 0 ? "+" : "")\(Int(txn.amount)) Coins")
                                        .font(.notoSans(size: 13, weight: .bold, relativeTo: .subheadline))
                                        .foregroundColor(txn.amount > 0 ? Color(red: 0.29, green: 0.87, blue: 0.5) : Color(red: 0.94, green: 0.27, blue: 0.27))
                                }
                            }
                            .padding(14)
                            if index < transactions.count - 1 { Divider().background(Color(white: 0.15)) }
                        }
                    }
                    .background(Color(white: 0.08))
                    .cornerRadius(14)
                }.padding(.horizontal)
            }

            VStack(spacing: 8) {
                NavigationLink(destination: WatchHistoryView()) {
                    ProfileRow(icon: "clock", text: "Watch History")
                }
                NavigationLink(destination: MyListView()) {
                    ProfileRow(icon: "heart", text: "My Favorites")
                }
                NavigationLink(destination: EditProfileView()) {
                    ProfileRow(icon: "pencil", text: "Edit Profile")
                }
                ProfileRow(icon: "cart", text: "Buy More Coins")
                NavigationLink(destination: VipPlansView()) {
                    ProfileRow(icon: "crown", text: "VIP Membership")
                }
                NavigationLink(destination: AdvertiseView()) {
                    ProfileRow(icon: "megaphone", text: "Advertise With Us")
                }
                NavigationLink(destination: MyAdsView()) {
                    ProfileRow(icon: "rectangle.stack", text: "My Ads")
                }
            }.padding(.top, 8)

            Spacer().frame(height: 16)
            Button(action: onLogout) {
                Text("Logout").fontWeight(.bold).frame(maxWidth: .infinity).frame(height: 48).overlay(RoundedRectangle(cornerRadius: 12).stroke(Color(red: 0.86, green: 0.15, blue: 0.15), lineWidth: 1.5)).foregroundColor(Color(red: 0.86, green: 0.15, blue: 0.15))
            }.padding(.horizontal)
            Spacer().frame(height: 40)
        }
    }
}

struct ProfileRow: View {
    let icon: String; let text: String
    var body: some View {
        HStack {
            ZStack {
                RoundedRectangle(cornerRadius: 8).fill(Color(white: 0.12)).frame(width: 36, height: 36)
                Image(systemName: icon).foregroundColor(Color(red: 0.86, green: 0.15, blue: 0.15)).font(.notoSans(size: 16))
            }
            Text(text).foregroundColor(.white).font(.notoSans(size: 16, relativeTo: .callout))
            Spacer()
            Image(systemName: "chevron.right").foregroundColor(Color(white: 0.27)).font(.notoSans(size: 12, relativeTo: .caption))
        }.padding(.horizontal, 16).padding(.vertical, 12).background(Color(white: 0.09)).cornerRadius(12).padding(.horizontal)
    }
}

// MARK: - Watch History
struct WatchHistoryView: View {
    @State private var items: [WatchHistoryItem] = []
    @State private var isLoading = true

    var body: some View {
        Group {
            if isLoading {
                ProgressView().tint(.red)
            } else if items.isEmpty {
                Text("You haven't watched anything yet.").foregroundColor(Color(white: 0.4))
            } else {
                ScrollView {
                    LazyVStack(spacing: 12) {
                        ForEach(items) { item in
                            NavigationLink(destination: destinationFor(item)) {
                                HStack(spacing: 12) {
                                    AsyncImage(url: URL(string: item.thumbnail_url ?? "")) { phase in
                                        if let image = phase.image { image.resizable().aspectRatio(contentMode: .fill) }
                                        else { Color(white: 0.15) }
                                    }
                                    .frame(width: 90, height: 60)
                                    .cornerRadius(8)
                                    .clipped()

                                    VStack(alignment: .leading, spacing: 2) {
                                        Text(item.series_title ?? "Unknown series").font(.notoSans(size: 14, weight: .semibold, relativeTo: .subheadline)).foregroundColor(.white).lineLimit(1)
                                        Text(item.episode_title ?? "").font(.notoSans(size: 12, relativeTo: .caption)).foregroundColor(Color(white: 0.55)).lineLimit(1)
                                        if let watched = item.watched_at {
                                            Text(String(watched.prefix(10))).font(.notoSans(size: 11, relativeTo: .caption2)).foregroundColor(Color(white: 0.35))
                                        }
                                    }
                                    Spacer()
                                }
                                .padding(12)
                                .background(Color(white: 0.09))
                                .cornerRadius(12)
                            }
                        }
                    }.padding(16)
                }
            }
        }
        .frame(maxWidth: .infinity, maxHeight: .infinity)
        .background(Color(red: 0.04, green: 0.04, blue: 0.04).ignoresSafeArea())
        .navigationTitle("Watch History")
        .navigationBarTitleDisplayMode(.inline)
        .task {
            items = (try? await APIClient.shared.getWatchHistory()) ?? []
            isLoading = false
        }
    }

    @ViewBuilder
    private func destinationFor(_ item: WatchHistoryItem) -> some View {
        if let slug = item.slug {
            PlayerView(slug: slug)
        } else {
            EmptyView()
        }
    }
}
