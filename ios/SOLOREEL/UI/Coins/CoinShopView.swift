import SwiftUI

struct CoinShopView: View {
    @ObservedObject var tokenManager = TokenManager.shared
    @State private var packages: [CoinPackage] = []
    @State private var isLoading = true
    @State private var paymentUrl: String? = nil
    @State private var paymentRef: String? = nil
    @State private var showSuccess = false
    @State private var purchaseError: String? = nil

    var currentCoins: Double { tokenManager.isLoggedIn ? tokenManager.coins : tokenManager.guestCoins }

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(spacing: 16) {
                    // Balance card
                    HStack {
                        VStack(alignment: .leading, spacing: 4) {
                            Text(tokenManager.isLoggedIn ? "Your Balance" : "Guest Balance").font(.notoSans(size: 12, relativeTo: .caption)).foregroundColor(Color(white: 0.6))
                            Text("\(Int(currentCoins)) Coins").font(.notoSans(size: 34, relativeTo: .largeTitle)).bold().foregroundColor(.yellow)
                        }
                        Spacer()
                        Image(systemName: "dollarsign.circle.fill").font(.notoSans(size: 44)).foregroundColor(.yellow)
                    }.padding(20).background(Color(white: 0.1)).cornerRadius(16).padding(.horizontal)

                    Text("Select a Package").font(.notoSans(size: 15, relativeTo: .subheadline)).foregroundColor(Color(white: 0.6)).frame(maxWidth: .infinity, alignment: .leading).padding(.horizontal)

                    if isLoading {
                        ProgressView().tint(.red)
                    } else {
                        ForEach(packages) { pkg in
                            Button { purchasePackage(pkg) } label: {
                                HStack {
                                    Text("🪙").font(.notoSans(size: 32))
                                    VStack(alignment: .leading, spacing: 4) {
                                        Text(pkg.name).fontWeight(.bold).foregroundColor(.white).font(.notoSans(size: 17, weight: .semibold, relativeTo: .headline))
                                        Text("\(pkg.coins) coins").foregroundColor(.yellow).font(.notoSans(size: 15, relativeTo: .subheadline)).fontWeight(.semibold)
                                    }
                                    Spacer()
                                    VStack(alignment: .trailing, spacing: 2) {
                                        Text("\(pkg.currency) \(String(format: "%.2f", pkg.price))").foregroundColor(Color(red: 0.86, green: 0.15, blue: 0.15)).fontWeight(.heavy).font(.notoSans(size: 17, weight: .semibold, relativeTo: .headline))
                                        Text("Buy Now →").foregroundColor(Color(white: 0.5)).font(.notoSans(size: 12, relativeTo: .caption))
                                    }
                                }.padding(20).background(Color(white: 0.08)).overlay(RoundedRectangle(cornerRadius: 16).stroke(Color(white: 0.15), lineWidth: 1)).cornerRadius(16).padding(.horizontal)
                            }
                        }
                    }
                }.padding(.vertical, 16)
            }
            .refreshable { await loadPackages() }
            .background(Color(red: 0.04, green: 0.04, blue: 0.04))
            .preferredColorScheme(.dark)
            .navigationTitle("Coin Shop").navigationBarTitleDisplayMode(.inline)
        }
        .task { await loadPackages() }
        .sheet(item: Binding(get: { paymentUrl.map { PaymentURL(url: $0) } }, set: { if $0 == nil { paymentUrl = nil } })) { item in
            PaymentWebView(url: item.url, onSuccess: { ref in
                paymentUrl = nil
                Task { await handlePaymentSuccess(ref: ref) }
            }, onDismiss: { paymentUrl = nil })
        }
        .alert("Payment Successful! 🎉", isPresented: $showSuccess) {
            Button("Great, Thanks!", role: .cancel) {}
        } message: { Text("Your coins have been added to your account.") }
        .alert("Payment Error", isPresented: Binding(get: { purchaseError != nil }, set: { if !$0 { purchaseError = nil } })) {
            Button("OK", role: .cancel) { purchaseError = nil }
        } message: { Text(purchaseError ?? "") }
    }

    func loadPackages() async {
        isLoading = true
        packages = (try? await APIClient.shared.getCoinPackages()) ?? []
        isLoading = false
    }

    func purchasePackage(_ pkg: CoinPackage) {
        Task {
            do {
                // Guests use the guest-purchase endpoint (no login needed);
                // registered users go through the authenticated endpoint.
                let result: PaymentInit
                if tokenManager.isLoggedIn {
                    result = try await APIClient.shared.purchaseCoins(packageId: pkg.id)
                } else {
                    result = try await APIClient.shared.guestPurchaseCoins(packageId: pkg.id, guestId: tokenManager.guestId)
                }
                await MainActor.run {
                    if let url = result.authorization_url {
                        paymentUrl = url
                        paymentRef = result.reference
                    } else {
                        purchaseError = "Could not initiate payment. Please try again."
                    }
                }
            } catch {
                await MainActor.run { purchaseError = error.localizedDescription }
            }
        }
    }

    func handlePaymentSuccess(ref: String) async {
        // Confirm with the gateway and credit the coins server-side.
        if let result = try? await APIClient.shared.verifyPayment(reference: ref) {
            if tokenManager.isLoggedIn {
                tokenManager.coins = result.coin_balance ?? tokenManager.coins
            } else {
                tokenManager.guestCoins = result.coin_balance ?? tokenManager.guestCoins
            }
        } else if tokenManager.isLoggedIn, let user = try? await APIClient.shared.getProfile() {
            tokenManager.coins = user.coin_balance ?? tokenManager.coins
        }
        showSuccess = true
    }
}

struct PaymentURL: Identifiable { let id = UUID(); let url: String }

/// Payment checkout opens via ASWebAuthenticationSession (real Safari engine,
/// same mechanism already proven for Google Sign-In) instead of an embedded
/// WKWebView — payment gateways are notoriously unreliable inside app
/// WebViews (cookie/storage restrictions, popup-handling quirks, fraud
/// heuristics that specifically target them), which is exactly why this
/// checkout "worked perfectly" on the website but not in-app: the website was
/// always being tested in a real browser. This makes the app use one too.
struct PaymentWebView: View {
    let url: String
    let onSuccess: (String) -> Void
    let onDismiss: () -> Void

    var body: some View {
        VStack(spacing: 16) {
            ProgressView().tint(.red)
            Text("Waiting for you to complete payment in your browser...")
                .foregroundColor(.white)
                .multilineTextAlignment(.center)
                .padding(.horizontal, 32)
            Button("Cancel") {
                PaymentAuthSession.shared.cancel()
                onDismiss()
            }
            .foregroundColor(Color(white: 0.6))
        }
        .frame(maxWidth: .infinity, maxHeight: .infinity)
        .background(Color(red: 0.04, green: 0.04, blue: 0.04).ignoresSafeArea())
        .onAppear {
            PaymentAuthSession.shared.start(url: url, onSuccess: onSuccess, onDismiss: onDismiss)
        }
    }
}
