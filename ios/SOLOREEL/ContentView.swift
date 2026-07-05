import SwiftUI

struct ContentView: View {
    @State private var showSplash = true
    @StateObject private var tokenManager = TokenManager.shared

    var body: some View {
        if showSplash {
            SplashView(onFinished: { showSplash = false })
        } else if !tokenManager.isLoggedIn {
            AuthView()
        } else {
            TabView {
                HomeView().tabItem { Label("Home", systemImage: "house.fill") }
                SearchView().tabItem { Label("Search", systemImage: "magnifyingglass") }
                CoinShopView().tabItem { Label("Coins", systemImage: "bitcoinsign.circle.fill") }
                ProfileView().tabItem { Label("Profile", systemImage: "person.circle.fill") }
            }
            .tint(.red)
            .preferredColorScheme(.dark)
        }
    }
}
