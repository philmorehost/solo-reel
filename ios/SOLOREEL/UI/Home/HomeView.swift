import SwiftUI

struct HomeView: View {
    @State private var banners: [Banner] = []
    @State private var series: [Series] = []
    @State private var isLoading = true
    @State private var currentBanner = 0

    let timer = Timer.publish(every: 4, on: .main, in: .common).autoconnect()

    var body: some View {
        NavigationStack {
            ScrollView(.vertical) {
                VStack(alignment: .leading, spacing: 0) {
                    // Banners
                    if !banners.isEmpty {
                        TabView(selection: $currentBanner) {
                            ForEach(Array(banners.enumerated()), id: \.offset) { i, b in
                                AsyncImage(url: URL(string: b.image_url ?? "")) { phase in
                                    if let image = phase.image { image.resizable().aspectRatio(contentMode: .fill) }
                                    else { Color.gray }
                                }
                                    .frame(height: 350).clipped()
                                    .overlay(LinearGradient(gradient: Gradient(colors: [.clear, .black]), startPoint: .top, endPoint: .bottom))
                                    .overlay(alignment: .bottomLeading) {
                                        VStack(alignment: .leading) { Text(b.title ?? "").font(.title).bold(); Text(b.subtitle ?? "").font(.subheadline).foregroundColor(.gray) }.padding()
                                    }
                                    .tag(i)
                            }
                        }.frame(height: 350).tabViewStyle(.page)
                    }

                    Text("Trending Now").font(.title2).bold().padding(.horizontal, 16).padding(.top, 16)

                    if isLoading { ProgressView().padding() }
                    else {
                        ScrollView(.horizontal, showsIndicators: false) {
                            HStack(spacing: 12) {
                                ForEach(series) { s in
                                NavigationLink(destination: SeriesDetailView(slug: s.slug)) {
                                    VStack(alignment: .leading) {
                                        AsyncImage(url: URL(string: s.cover_image_url ?? "")) { phase in
                                            if let image = phase.image {
                                                image.resizable()
                                                    .aspectRatio(contentMode: .fill)
                                                    .frame(width: 140, height: 200)
                                                    .cornerRadius(12)
                                            } else {
                                                Color.gray
                                                    .frame(width: 140, height: 200)
                                                    .cornerRadius(12)
                                            }
                                        }
                                        Text(s.title).font(.caption).foregroundColor(.white).lineLimit(2).frame(width: 140, alignment: .leading)
                                    }
                                }
                                }
                            }.padding(.horizontal, 12)
                        }
                    }
                }
            }.background(Color.black).preferredColorScheme(.dark)
            .overlay(alignment: .topTrailing) {
                NotificationBell().padding(.trailing, 16).padding(.top, 8)
            }
        }
        .task {
            isLoading = true
            do { banners = try await APIClient.shared.getBanners(); series = try await APIClient.shared.getSeries(); isLoading = false }
            catch { isLoading = false }
            await NotificationCenterStore.shared.load(postSystemNotifications: true)
        }
    }
}
