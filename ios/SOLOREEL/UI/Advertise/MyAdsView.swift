import SwiftUI

struct MyAdsView: View {
    @State private var ads: [MyAd] = []
    @State private var isLoading = true
    @State private var paymentUrl: String?
    @State private var paymentRef: String?

    var body: some View {
        Group {
            if isLoading {
                ProgressView().tint(.red)
            } else if ads.isEmpty {
                Text("You haven't created any ads yet.").foregroundColor(.gray)
            } else {
                ScrollView {
                    VStack(spacing: 12) {
                        ForEach(ads) { ad in
                            MyAdRow(ad: ad, onRenew: { renew(ad.id) })
                        }
                    }.padding()
                }
            }
        }
        .background(Color(red: 0.04, green: 0.04, blue: 0.04))
        .preferredColorScheme(.dark)
        .navigationTitle("My Ads")
        .toolbar {
            ToolbarItem(placement: .navigationBarTrailing) {
                NavigationLink(destination: AdvertiseView()) { Text("New Ad").bold() }
            }
        }
        .task { await load() }
        .sheet(item: Binding(get: { paymentUrl.map { PaymentURL(url: $0) } }, set: { if $0 == nil { paymentUrl = nil } })) { item in
            PaymentWebView(url: item.url, onSuccess: { ref in
                paymentUrl = nil
                Task { _ = try? await APIClient.shared.verifyPayment(reference: paymentRef ?? ref); await load() }
            }, onDismiss: { paymentUrl = nil })
        }
    }

    func load() async {
        isLoading = true
        ads = (try? await APIClient.shared.getMyAds()) ?? []
        isLoading = false
    }

    func renew(_ id: Int) {
        Task {
            if let result = try? await APIClient.shared.renewAd(id: id), let url = result.authorization_url {
                paymentUrl = url
                paymentRef = result.reference
            }
        }
    }
}

struct MyAdRow: View {
    let ad: MyAd
    let onRenew: () -> Void

    var statusLabel: String {
        if ad.payment_status == "pending" { return "Awaiting Payment" }
        if ad.is_expired { return "Expired" }
        return ad.is_active ? "Active" : "Inactive"
    }
    var statusColor: Color {
        if ad.payment_status == "pending" { return .yellow }
        if ad.is_expired { return .gray }
        return ad.is_active ? .green : .gray
    }

    var body: some View {
        HStack {
            AsyncImage(url: URL(string: ad.media_url ?? "")) { phase in
                if let image = phase.image { image.resizable().aspectRatio(contentMode: .fill) }
                else { Color.black }
            }.frame(width: 90, height: 54).clipShape(RoundedRectangle(cornerRadius: 8))

            VStack(alignment: .leading, spacing: 2) {
                Text(ad.title ?? "").foregroundColor(.white).bold().lineLimit(1)
                Text("\(ad.duration_seconds)s • \(ad.platform_placement.capitalized)").font(.caption).foregroundColor(.gray)
                if let exp = ad.expires_at { Text("Expires \(exp)").font(.caption2).foregroundColor(Color(white: 0.4)) }
                Text(statusLabel).font(.caption).bold().foregroundColor(statusColor)
            }
            Spacer()
            if ad.payment_status != "pending" {
                Button("Renew", action: onRenew)
                    .font(.caption).bold()
                    .padding(.horizontal, 12).padding(.vertical, 8)
                    .background(Color.indigo).foregroundColor(.white).cornerRadius(8)
            }
        }
        .padding(12)
        .background(Color(white: 0.1))
        .cornerRadius(12)
    }
}
