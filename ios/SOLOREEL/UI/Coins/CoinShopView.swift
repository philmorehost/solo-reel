import SwiftUI

struct CoinShopView: View {
    @State private var packages: [CoinPackage] = []

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(spacing: 12) {
                    Text("Coin Shop").font(.title).bold()
                    Text("\(TokenManager.shared.coins) coins").foregroundColor(.yellow).font(.title3)

                    ForEach(packages) { pkg in
                        HStack {
                            VStack(alignment: .leading) {
                                Text(pkg.name).fontWeight(.bold).foregroundColor(.white)
                                Text("\(pkg.coins) coins").foregroundColor(.yellow).font(.caption)
                            }
                            Spacer()
                            Text("\(pkg.currency) \(String(format: "%.2f", pkg.price))").foregroundColor(.red).fontWeight(.bold)
                        }.padding().background(Color(white: 0.1)).cornerRadius(12).padding(.horizontal)
                    }
                }.padding(.top)
            }.background(Color.black).preferredColorScheme(.dark)
        }
        .task { do { packages = try await APIClient.shared.getCoinPackages() } catch {} }
    }
}
