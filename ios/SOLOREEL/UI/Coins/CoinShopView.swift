import SwiftUI
import WebKit

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
                            Text(tokenManager.isLoggedIn ? "Your Balance" : "Guest Balance").font(.caption).foregroundColor(Color(white: 0.6))
                            Text("\(Int(currentCoins)) Coins").font(.largeTitle).bold().foregroundColor(.yellow)
                        }
                        Spacer()
                        Image(systemName: "dollarsign.circle.fill").font(.system(size: 44)).foregroundColor(.yellow)
                    }.padding(20).background(Color(white: 0.1)).cornerRadius(16).padding(.horizontal)

                    Text("Select a Package").font(.subheadline).foregroundColor(Color(white: 0.6)).frame(maxWidth: .infinity, alignment: .leading).padding(.horizontal)

                    if isLoading {
                        ProgressView().tint(.red)
                    } else {
                        ForEach(packages) { pkg in
                            Button { purchasePackage(pkg) } label: {
                                HStack {
                                    Text("🪙").font(.system(size: 32))
                                    VStack(alignment: .leading, spacing: 4) {
                                        Text(pkg.name).fontWeight(.bold).foregroundColor(.white).font(.headline)
                                        Text("\(pkg.coins) coins").foregroundColor(.yellow).font(.subheadline).fontWeight(.semibold)
                                    }
                                    Spacer()
                                    VStack(alignment: .trailing, spacing: 2) {
                                        Text("\(pkg.currency) \(String(format: "%.2f", pkg.price))").foregroundColor(Color(red: 0.86, green: 0.15, blue: 0.15)).fontWeight(.heavy).font(.headline)
                                        Text("Buy Now →").foregroundColor(Color(white: 0.5)).font(.caption)
                                    }
                                }.padding(20).background(Color(white: 0.08)).overlay(RoundedRectangle(cornerRadius: 16).stroke(Color(white: 0.15), lineWidth: 1)).cornerRadius(16).padding(.horizontal)
                            }
                        }
                    }
                }.padding(.vertical, 16)
            }
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

// MARK: - In-App Payment WebView
struct PaymentWebView: View {
    let url: String
    let onSuccess: (String) -> Void
    let onDismiss: () -> Void

    var body: some View {
        NavigationStack {
            WebViewRepresentable(url: url, onSuccess: onSuccess, onDismiss: onDismiss)
                .ignoresSafeArea()
                .navigationTitle("Complete Payment").navigationBarTitleDisplayMode(.inline)
                .toolbar { ToolbarItem(placement: .cancellationAction) { Button("Cancel", action: onDismiss) } }
        }
    }
}

struct WebViewRepresentable: UIViewRepresentable {
    let url: String
    let onSuccess: (String) -> Void
    let onDismiss: () -> Void

    func makeUIView(context: Context) -> WKWebView {
        let webView = WKWebView()
        webView.navigationDelegate = context.coordinator
        if let u = URL(string: url) { webView.load(URLRequest(url: u)) }
        return webView
    }
    func updateUIView(_ uiView: WKWebView, context: Context) {}
    func makeCoordinator() -> Coordinator { Coordinator(onSuccess: onSuccess, onDismiss: onDismiss) }

    class Coordinator: NSObject, WKNavigationDelegate {
        let onSuccess: (String) -> Void; let onDismiss: () -> Void
        init(onSuccess: @escaping (String) -> Void, onDismiss: @escaping () -> Void) {
            self.onSuccess = onSuccess; self.onDismiss = onDismiss
        }
        func webView(_ webView: WKWebView, decidePolicyFor action: WKNavigationAction, decisionHandler: @escaping (WKNavigationActionPolicy) -> Void) {
            // Only react to top-level redirects — the Payhub iframe navigates internally too
            if action.targetFrame?.isMainFrame == true, let url = action.request.url?.absoluteString {
                if url.contains("callback") || url.contains("verify") || url.contains("success") {
                    let components = URLComponents(string: url)
                    let ref = components?.queryItems?.first(where: { $0.name == "reference" || $0.name == "trxref" })?.value ?? ""
                    if !ref.isEmpty { onSuccess(ref); decisionHandler(.cancel); return }
                    else { onDismiss(); decisionHandler(.cancel); return }
                }
            }
            decisionHandler(.allow)
        }
    }
}
