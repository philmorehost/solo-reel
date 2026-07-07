import SwiftUI

struct SeriesDetailView: View {
    let slug: String
    @State private var series: Series?; @State private var episodes: [Episode] = []; @State private var isLoading = true

    var body: some View {
        ScrollView {
            if isLoading { ProgressView().padding() }
            else if let s = series {
                    VStack(alignment: .leading) {
                    AsyncImage(url: URL(string: s.cover_image_url ?? "")) { phase in
                        if let image = phase.image {
                            image.resizable()
                                .aspectRatio(contentMode: .fill)
                                .frame(height: 220)
                                .cornerRadius(16)
                        } else {
                            Color.gray
                                .frame(height: 220)
                                .cornerRadius(16)
                        }
                    }.padding(.horizontal)
                    Text(s.title).font(.notoSans(size: 28, relativeTo: .title)).bold().padding(.horizontal)
                    Text(s.synopsis ?? "").foregroundColor(.gray).font(.notoSans(size: 15, relativeTo: .subheadline)).padding(.horizontal)

                    Text("Episodes").font(.notoSans(size: 20, relativeTo: .title3)).bold().padding(.horizontal).padding(.top)
                    ForEach(episodes) { ep in
                        NavigationLink(destination: PlayerView(slug: ep.slug)) {
                            HStack {
                                AsyncImage(url: URL(string: ep.thumbnail_url ?? "")) { phase in
                                    if let image = phase.image {
                                        image.resizable()
                                            .aspectRatio(contentMode: .fill)
                                            .frame(width: 80, height: 56)
                                            .cornerRadius(8)
                                    } else {
                                        Color.gray
                                            .frame(width: 80, height: 56)
                                            .cornerRadius(8)
                                    }
                                }
                                VStack(alignment: .leading) {
                                    Text("Ep \(ep.episode_number ?? 0)").foregroundColor(.red).font(.notoSans(size: 12, relativeTo: .caption))
                                    Text(ep.title).foregroundColor(.white).fontWeight(.medium).lineLimit(1)
                                }
                                Spacer()
                                if ep.is_free == false { Image(systemName: "lock.fill").foregroundColor(.yellow) }
                            }.padding().background(Color(white: 0.1)).cornerRadius(12).padding(.horizontal)
                        }
                    }
                }
            }
        }
        .refreshable {
            do {
                series = try await APIClient.shared.getSeriesDetail(slug: slug)
                if let id = series?.id { episodes = try await APIClient.shared.getEpisodes(seriesId: id) }
            } catch {}
        }
        .background(Color.black).preferredColorScheme(.dark)
        .task {
            do {
                series = try await APIClient.shared.getSeriesDetail(slug: slug)
                if let id = series?.id { episodes = try await APIClient.shared.getEpisodes(seriesId: id) }
                isLoading = false
            } catch { isLoading = false }
        }
    }
}
