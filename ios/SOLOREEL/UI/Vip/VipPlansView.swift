import SwiftUI

/// Standalone VIP membership screen — same PaymentURL/PaymentWebView checkout
/// flow (from CoinShopView.swift) used for coin purchases and the in-player
/// unlock-offers dialog (VipCoinOffersView in PlayerView.swift).
struct VipPlansView: View {
    @State private var plans: [VipPlan] = []
    @State private var isVip = false
    @State private var planName: String? = nil
    @State private var expiresAt: String? = nil
    @State private var isLoading = true
    @State private var paymentUrl: String? = nil
    @State private var pendingReference: String? = nil
    @State private var error: String? = nil
    @State private var isProcessing = false

    private func load() async {
        isLoading = true
        plans = (try? await APIClient.shared.getVipPlans()) ?? []
        if TokenManager.shared.isLoggedIn, let status = try? await APIClient.shared.getVipStatus() {
            isVip = status.is_vip ?? false
            planName = status.plan_name
            expiresAt = status.expires_at
        }
        isLoading = false
    }

    private func purchase(_ plan: VipPlan) {
        guard TokenManager.shared.isLoggedIn else {
            error = "Please sign in to subscribe to VIP."
            return
        }
        error = nil
        isProcessing = true
        Task {
            do {
                let result = try await APIClient.shared.purchaseVip(planId: plan.id)
                isProcessing = false
                if let url = result.authorization_url {
                    pendingReference = result.reference
                    paymentUrl = url
                } else {
                    error = "Could not initiate payment"
                }
            } catch {
                isProcessing = false
                self.error = "Could not initiate payment: \(error.localizedDescription)"
            }
        }
    }

    var body: some View {
        ScrollView {
            VStack(spacing: 16) {
                VStack(spacing: 8) {
                    Text("👑").font(.system(size: 48))
                    Text("SOLOREEL VIP").font(.notoSans(size: 22, weight: .bold, relativeTo: .title2)).foregroundColor(.white)
                    Text("Unlock every episode for free, no ads, no per-episode coins.")
                        .font(.notoSans(size: 13, relativeTo: .caption)).foregroundColor(Color(white: 0.6))
                        .multilineTextAlignment(.center)
                }
                .padding(.top, 20)
                .frame(maxWidth: .infinity)

                if isLoading {
                    ProgressView().tint(.yellow).padding(.top, 24)
                } else {
                    if isVip {
                        VStack(alignment: .leading, spacing: 4) {
                            Text("You're a VIP member 👑").font(.notoSans(size: 15, weight: .bold, relativeTo: .headline)).foregroundColor(.yellow)
                            Text("Plan: \(planName ?? "VIP")" + (expiresAt.map { " · Expires \(String($0.prefix(10)))" } ?? ""))
                                .font(.notoSans(size: 12, relativeTo: .caption)).foregroundColor(Color(white: 0.75))
                        }
                        .padding(16)
                        .frame(maxWidth: .infinity, alignment: .leading)
                        .background(Color.yellow.opacity(0.12))
                        .cornerRadius(14)
                    }

                    ForEach(plans) { plan in
                        Button { purchase(plan) } label: {
                            VStack(alignment: .leading, spacing: 4) {
                                Text(plan.name).font(.notoSans(size: 16, weight: .bold, relativeTo: .headline)).foregroundColor(.black)
                                Text("\(plan.currency) \(String(format: "%.2f", plan.price))").font(.notoSans(size: 24, weight: .heavy, relativeTo: .title2)).foregroundColor(.black)
                                Text("\(plan.duration_days) days").font(.notoSans(size: 12, relativeTo: .caption)).foregroundColor(.black.opacity(0.6))
                                if plan.perk_free_unlocks == true { Text("✔ Unlock all episodes free").font(.notoSans(size: 12, relativeTo: .caption)).foregroundColor(.black.opacity(0.8)) }
                                if plan.perk_ad_free == true { Text("✔ No ads required to unlock").font(.notoSans(size: 12, relativeTo: .caption)).foregroundColor(.black.opacity(0.8)) }
                            }
                            .padding(18)
                            .frame(maxWidth: .infinity, alignment: .leading)
                            .background(LinearGradient(colors: [Color(red: 0.99, green: 0.9, blue: 0.54), Color(red: 0.96, green: 0.62, blue: 0.04)], startPoint: .topLeading, endPoint: .bottomTrailing))
                            .cornerRadius(16)
                        }
                        .disabled(isProcessing)
                    }

                    if let error { Text(error).font(.notoSans(size: 13, relativeTo: .caption)).foregroundColor(.red) }
                    if pendingReference != nil {
                        Text("Waiting for you to complete payment in your browser...")
                            .font(.notoSans(size: 12, relativeTo: .caption)).foregroundColor(Color(white: 0.55))
                            .frame(maxWidth: .infinity, alignment: .center)
                    }
                }
            }
            .padding(.horizontal, 16)
            .padding(.bottom, 24)
        }
        .background(Color.black)
        .preferredColorScheme(.dark)
        .navigationTitle("VIP Membership")
        .navigationBarTitleDisplayMode(.inline)
        .task { await load() }
        .sheet(item: Binding(get: { paymentUrl.map { PaymentURL(url: $0) } }, set: { if $0 == nil { paymentUrl = nil } })) { item in
            PaymentWebView(url: item.url, onSuccess: { ref in
                paymentUrl = nil
                let reference = pendingReference ?? ref
                Task {
                    try? await APIClient.shared.verifyPayment(reference: reference)
                    await load()
                }
            }, onDismiss: { paymentUrl = nil })
        }
    }
}
