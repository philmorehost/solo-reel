import SwiftUI
import PhotosUI
import UIKit

struct AdvertiseView: View {
    @State private var prices: [AdPricing] = []
    @State private var title = ""
    @State private var targetUrl = ""
    @State private var duration = 5
    @State private var placement = "both"
    @State private var pickerItem: PhotosPickerItem?
    @State private var imageData: Data?
    @State private var isSubmitting = false
    @State private var errorMessage: String?
    @State private var paymentUrl: String?
    @State private var paymentRef: String?
    @State private var showSuccess = false

    private var price: Double? {
        prices.first(where: { $0.duration_seconds == duration && $0.platform_placement == placement })?.price
    }

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 16) {
                Text("Run your own banner ad on the home screen. Pick a duration, where it shows, and pay once for a campaign.")
                    .font(.subheadline).foregroundColor(Color(white: 0.6))

                TextField("Ad Title", text: $title)
                    .textFieldStyle(.roundedBorder)
                TextField("Target Link (optional)", text: $targetUrl)
                    .textFieldStyle(.roundedBorder)
                    .keyboardType(.URL).autocapitalization(.none)

                Text("Banner Image").font(.headline).foregroundColor(.white)
                // Self-serve ads are image-only for now (matches Android); video
                // banners remain an admin-only capability via the web admin panel.
                PhotosPicker(selection: $pickerItem, matching: .images) {
                    ZStack {
                        RoundedRectangle(cornerRadius: 12).fill(Color(white: 0.1)).frame(height: 160)
                        if let data = imageData, let uiImage = UIImage(data: data) {
                            Image(uiImage: uiImage).resizable().aspectRatio(contentMode: .fill)
                                .frame(height: 160).clipShape(RoundedRectangle(cornerRadius: 12))
                        } else {
                            VStack {
                                Image(systemName: "photo").font(.system(size: 32)).foregroundColor(Color(white: 0.4))
                                Text("Tap to choose an image (1200x600 recommended)").font(.caption).foregroundColor(Color(white: 0.4))
                            }
                        }
                    }
                }
                .onChange(of: pickerItem) { newItem in
                    Task { imageData = try? await newItem?.loadTransferable(type: Data.self) }
                }

                Text("On-Screen Duration").font(.headline).foregroundColor(.white)
                HStack {
                    ForEach([5, 10, 15], id: \.self) { d in
                        Button("\(d)s") { duration = d }
                            .padding(.horizontal, 16).padding(.vertical, 8)
                            .background(duration == d ? Color.red : Color(white: 0.15))
                            .foregroundColor(.white).cornerRadius(8)
                    }
                }

                Text("Where should it show?").font(.headline).foregroundColor(.white)
                HStack {
                    ForEach([("website", "Website"), ("app", "App"), ("both", "Both")], id: \.0) { value, label in
                        Button(label) { placement = value }
                            .padding(.horizontal, 16).padding(.vertical, 8)
                            .background(placement == value ? Color.red : Color(white: 0.15))
                            .foregroundColor(.white).cornerRadius(8)
                    }
                }

                if let p = price {
                    Text("Price: ₦\(String(format: "%.2f", p))").font(.title3).bold().foregroundColor(.yellow)
                } else {
                    Text("Price unavailable").foregroundColor(.gray)
                }

                if let err = errorMessage {
                    Text(err).foregroundColor(.red).font(.caption)
                }

                Button {
                    submit()
                } label: {
                    if isSubmitting { ProgressView().tint(.white) }
                    else { Text("Continue to Payment").fontWeight(.bold) }
                }
                .frame(maxWidth: .infinity).frame(height: 50)
                .background(Color.red).foregroundColor(.white).cornerRadius(12)
                .disabled(isSubmitting)
            }
            .padding()
        }
        .background(Color(red: 0.04, green: 0.04, blue: 0.04))
        .preferredColorScheme(.dark)
        .navigationTitle("Advertise With Us")
        .task {
            prices = (try? await APIClient.shared.getAdsPricing()) ?? []
        }
        .sheet(item: Binding(get: { paymentUrl.map { PaymentURL(url: $0) } }, set: { if $0 == nil { paymentUrl = nil } })) { item in
            PaymentWebView(url: item.url, onSuccess: { ref in
                paymentUrl = nil
                Task { await handlePaymentSuccess(ref: ref) }
            }, onDismiss: { paymentUrl = nil })
        }
        .alert("Payment Successful! 🎉", isPresented: $showSuccess) {
            Button("Great, Thanks!", role: .cancel) {}
        } message: { Text("Your ad is now live.") }
    }

    func submit() {
        guard !title.isEmpty, let data = imageData else {
            errorMessage = "Please add a title and an image."
            return
        }
        isSubmitting = true
        errorMessage = nil
        Task {
            do {
                let result = try await APIClient.shared.subscribeAd(
                    title: title, targetUrl: targetUrl, durationSeconds: duration, platformPlacement: placement,
                    mediaData: data, mediaFilename: "ad_upload.jpg", mediaMimeType: "image/jpeg"
                )
                await MainActor.run {
                    isSubmitting = false
                    if let url = result.authorization_url {
                        paymentUrl = url
                        paymentRef = result.reference
                    } else {
                        errorMessage = "Could not initiate payment. Please try again."
                    }
                }
            } catch {
                await MainActor.run {
                    isSubmitting = false
                    errorMessage = error.localizedDescription
                }
            }
        }
    }

    func handlePaymentSuccess(ref: String) async {
        _ = try? await APIClient.shared.verifyPayment(reference: ref)
        showSuccess = true
    }
}
