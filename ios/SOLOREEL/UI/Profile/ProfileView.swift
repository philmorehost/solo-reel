import SwiftUI

struct ProfileView: View {
    @ObservedObject var tokenManager = TokenManager.shared
    @State private var bonusStatus: WeeklyBonusStatus? = nil

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(spacing: 0) {
                    if tokenManager.isGuest {
                        GuestProfileSection(onLogin: {})
                    } else {
                        RegisteredProfileSection(bonusStatus: bonusStatus, onLogout: { tokenManager.logout() })
                    }
                }
            }
            .background(Color(red: 0.04, green: 0.04, blue: 0.04))
            .preferredColorScheme(.dark)
        }
        .task {
            if tokenManager.isLoggedIn {
                bonusStatus = try? await APIClient.shared.getBonusStatus()
            }
        }
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
                Image(systemName: "person.fill").font(.system(size: 44)).foregroundColor(Color(white: 0.4))
            }
            Text("Guest User").font(.title2).bold().foregroundColor(.white)
            Text("Tap below to unlock all features").foregroundColor(Color(white: 0.5)).font(.subheadline)

            // Guest coin balance
            HStack {
                VStack(alignment: .leading) {
                    Text("Guest Balance").font(.caption).foregroundColor(Color(white: 0.6))
                    Text("\(Int(TokenManager.shared.guestCoins)) Coins").font(.title2).bold().foregroundColor(.yellow)
                }
                Spacer()
                Image(systemName: "dollarsign.circle.fill").font(.system(size: 40)).foregroundColor(.yellow)
            }.padding(20).background(Color(white: 0.1)).cornerRadius(16).padding(.horizontal)

            // Weekly bonus promo card
            VStack(alignment: .leading, spacing: 12) {
                HStack {
                    Text("🎁").font(.system(size: 28))
                    Text("FREE Weekly Bonus!").font(.title3).bold().foregroundColor(Color(red: 0.86, green: 0.15, blue: 0.15))
                }
                Text("Register and get FREE coins every week!\nUse them to unlock premium episodes.\nDon't miss out!").foregroundColor(Color(white: 0.8)).font(.subheadline).lineSpacing(4)
                VStack(alignment: .leading, spacing: 4) {
                    Label("50 bonus coins every Monday", systemImage: "checkmark.circle.fill").foregroundColor(Color(red: 0.29, green: 0.87, blue: 0.5)).font(.subheadline)
                    Label("Save your watch progress", systemImage: "checkmark.circle.fill").foregroundColor(Color(red: 0.29, green: 0.87, blue: 0.5)).font(.subheadline)
                    Label("Create a favorites list", systemImage: "checkmark.circle.fill").foregroundColor(Color(red: 0.29, green: 0.87, blue: 0.5)).font(.subheadline)
                    Label("Guests lose session on close", systemImage: "xmark.circle.fill").foregroundColor(Color(red: 0.94, green: 0.27, blue: 0.27)).font(.subheadline)
                }
                Button(action: onLogin) {
                    Text("Register / Login Now").fontWeight(.bold).frame(maxWidth: .infinity).frame(height: 50).background(Color(red: 0.86, green: 0.15, blue: 0.15)).foregroundColor(.white).cornerRadius(12)
                }
            }.padding(20).background(Color(white: 0.07)).overlay(RoundedRectangle(cornerRadius: 20).stroke(Color(red: 0.86, green: 0.15, blue: 0.15), lineWidth: 1.5)).cornerRadius(20).padding(.horizontal)

            Text("You can still buy coins and watch episodes as a guest.\nYour coin balance is saved on this device.").foregroundColor(Color(white: 0.35)).font(.caption).multilineTextAlignment(.center).padding(.horizontal, 32)
            Spacer().frame(height: 40)
        }
    }
}

// MARK: - Registered Profile
struct RegisteredProfileSection: View {
    let bonusStatus: WeeklyBonusStatus?
    let onLogout: () -> Void
    var body: some View {
        VStack(spacing: 16) {
            Spacer().frame(height: 40)
            // Avatar
            ZStack {
                Circle().fill(LinearGradient(colors: [Color(red: 0.86, green: 0.15, blue: 0.15), Color(red: 0.5, green: 0.07, blue: 0.07)], startPoint: .top, endPoint: .bottom)).frame(width: 100, height: 100)
                Text(String(TokenManager.shared.username?.first?.uppercased() ?? "U")).font(.system(size: 40)).bold().foregroundColor(.white)
            }
            Text(TokenManager.shared.username ?? "User").font(.title2).bold().foregroundColor(.white)
            Text(TokenManager.shared.email ?? "").foregroundColor(Color(white: 0.4)).font(.subheadline)

            // Coin balance
            HStack {
                VStack(alignment: .leading) {
                    Text("Coin Balance").font(.caption).foregroundColor(Color(white: 0.6))
                    Text("\(Int(TokenManager.shared.coins)) Coins").font(.title2).bold().foregroundColor(.yellow)
                }
                Spacer()
                Image(systemName: "dollarsign.circle.fill").font(.system(size: 40)).foregroundColor(.yellow)
            }.padding(20).background(Color(white: 0.1)).cornerRadius(16).padding(.horizontal)

            // Weekly bonus card
            if let bonus = bonusStatus, bonus.bonus_coins > 0 {
                HStack {
                    Text("🎁").font(.system(size: 28))
                    VStack(alignment: .leading, spacing: 2) {
                        Text("Weekly Bonus").font(.subheadline).bold().foregroundColor(Color(red: 0.29, green: 0.87, blue: 0.5))
                        Text("\(Int(bonus.bonus_coins)) coins available").font(.headline).foregroundColor(.white)
                        if let exp = bonus.bonus_expires_at { Text("Expires: \(exp.prefix(10))").font(.caption).foregroundColor(Color(white: 0.5)) }
                    }
                    Spacer()
                }.padding(16).background(Color(red: 0.06, green: 0.1, blue: 0.06)).overlay(RoundedRectangle(cornerRadius: 14).stroke(Color(red: 0.29, green: 0.87, blue: 0.5), lineWidth: 1)).cornerRadius(14).padding(.horizontal)
            }

            VStack(spacing: 8) {
                NavigationLink(destination: WatchHistoryView()) {
                    ProfileRow(icon: "clock", text: "Watch History")
                }
                NavigationLink(destination: FavoritesView()) {
                    ProfileRow(icon: "heart", text: "My Favorites")
                }
                NavigationLink(destination: EditProfileView()) {
                    ProfileRow(icon: "pencil", text: "Edit Profile")
                }
                ProfileRow(icon: "cart", text: "Buy More Coins")
                NavigationLink(destination: AdvertiseView()) {
                    ProfileRow(icon: "megaphone", text: "Advertise With Us")
                }
                NavigationLink(destination: MyAdsView()) {
                    ProfileRow(icon: "rectangle.stack", text: "My Ads")
                }
                NavigationLink(destination: SettingsView()) {
                    ProfileRow(icon: "gearshape", text: "Settings")
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
                Image(systemName: icon).foregroundColor(Color(red: 0.86, green: 0.15, blue: 0.15)).font(.system(size: 16))
            }
            Text(text).foregroundColor(.white).font(.callout)
            Spacer()
            Image(systemName: "chevron.right").foregroundColor(Color(white: 0.27)).font(.caption)
        }.padding(.horizontal, 16).padding(.vertical, 12).background(Color(white: 0.09)).cornerRadius(12).padding(.horizontal)
    }
}

// MARK: - Placeholder Views
struct WatchHistoryView: View {
    var body: some View {
        VStack {
            Text("Watch History").font(.title).bold()
            Text("Coming soon!").foregroundColor(.gray)
        }
        .frame(maxWidth: .infinity, maxHeight: .infinity)
        .background(Color(red: 0.04, green: 0.04, blue: 0.04).ignoresSafeArea())
        .foregroundColor(.white)
    }
}

struct FavoritesView: View {
    var body: some View {
        VStack {
            Text("My Favorites").font(.title).bold()
            Text("Coming soon!").foregroundColor(.gray)
        }
        .frame(maxWidth: .infinity, maxHeight: .infinity)
        .background(Color(red: 0.04, green: 0.04, blue: 0.04).ignoresSafeArea())
        .foregroundColor(.white)
    }
}

struct SettingsView: View {
    var body: some View {
        VStack {
            Text("Settings").font(.title).bold()
            Text("Coming soon!").foregroundColor(.gray)
        }
        .frame(maxWidth: .infinity, maxHeight: .infinity)
        .background(Color(red: 0.04, green: 0.04, blue: 0.04).ignoresSafeArea())
        .foregroundColor(.white)
    }
}
