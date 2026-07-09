import SwiftUI
import UIKit

/** Destination for banner taps, which need an async lookup before we know
 * whether to land in the player or fall back to the series-detail screen
 * (unlike the card grids below, which pre-resolve resumeSlugs before render). */
enum HomeNavTarget: Hashable {
    case player(String)
    case seriesDetail(String)
}

struct HomeView: View {
    @State private var banners: [Banner] = []
    @State private var series: [Series] = []
    @State private var shelves: [Shelf] = []
    @State private var shelfSeries: [String: [Series]] = [:]
    @State private var continueWatching: [ContinueWatchingItem] = []
    @State private var resumeSlugs: [Int: String] = [:]
    @State private var isLoading = true
    @State private var currentBanner = 0
    @State private var navPath = NavigationPath()

    private var guestIdOrNil: String? { TokenManager.shared.isLoggedIn ? nil : TokenManager.shared.guestId }

    private func loadHomeData() async {
        do {
            banners = try await APIClient.shared.getBanners()
            series = try await APIClient.shared.getSeries()
            continueWatching = (try? await APIClient.shared.getContinueWatching(guestId: guestIdOrNil)) ?? []
            let fetchedShelves = try await APIClient.shared.getShelves()

            // Ensure trending-now is always sorted to be first in list
            let sorted = fetchedShelves.sorted { s1, s2 in
                if s1.slug == "trending-now" { return true }
                if s2.slug == "trending-now" { return false }
                return s1.id < s2.id
            }
            shelves = sorted

            // Pre-load series for each shelf
            for shelf in sorted {
                if let result = try? await APIClient.shared.getSeries(shelf: shelf.slug) {
                    shelfSeries[shelf.slug] = result
                }
            }

            await fetchResumeSlugs(ids: allVisibleSeriesIds())
        } catch {}
    }

    private func allVisibleSeriesIds() -> [Int] {
        var ids = series.map { $0.id }
        for list in shelfSeries.values { ids.append(contentsOf: list.map { $0.id }) }
        return Array(Set(ids))
    }

    /** Skip-to-player: resolve each card's resume episode (or episode 1) in one batch call. */
    private func fetchResumeSlugs(ids: [Int]) async {
        let missing = ids.filter { resumeSlugs[$0] == nil }
        guard !missing.isEmpty else { return }
        if let fetched = try? await APIClient.shared.getResumeBatch(ids: missing, guestId: guestIdOrNil) {
            for (key, slug) in fetched {
                if let seriesId = Int(key) { resumeSlugs[seriesId] = slug }
            }
        }
    }

    /** Banner clicks only carry a slug, not a series id, so this resolves the
     * resume episode for a single series (acceptable per-tap cost, unlike the
     * batch lookup used for the card grids). Falls back to series-detail. */
    private func resolveBannerTarget(slug: String) async -> HomeNavTarget {
        guard let s = try? await APIClient.shared.getSeriesDetail(slug: slug),
              let resume = try? await APIClient.shared.getResumeEpisode(seriesId: s.id, guestId: guestIdOrNil) else {
            return .seriesDetail(slug)
        }
        return .player(resume.slug)
    }

    @ViewBuilder
    private func seriesCoverImage(_ s: Series, width: CGFloat?, height: CGFloat) -> some View {
        AsyncImage(url: URL(string: s.cover_image_url ?? "")) { phase in
            if let image = phase.image {
                image.resizable().aspectRatio(contentMode: .fill)
            } else {
                Color.gray
            }
        }
        .frame(width: width, height: height)
        .cornerRadius(12)
        .clipped()
    }

    /** Skip-to-player: jumps straight into the reel feed at the viewer's resume
     * episode, falling back to the series-detail screen if the batch lookup
     * hasn't resolved an entry for this series yet. */
    @ViewBuilder
    private func seriesCardLink<Content: View>(_ s: Series, @ViewBuilder content: () -> Content) -> some View {
        if let resumeSlug = resumeSlugs[s.id] {
            NavigationLink(destination: PlayerView(slug: resumeSlug)) { content() }
        } else {
            NavigationLink(destination: SeriesDetailView(slug: s.slug)) { content() }
        }
    }

    var body: some View {
        NavigationStack(path: $navPath) {
            VStack(spacing: 0) {
                HStack {
                    Image("logo_topbar")
                        .resizable()
                        .aspectRatio(contentMode: .fit)
                        .frame(height: 38)
                    Spacer()
                    NotificationBell()
                }
                .padding(.horizontal, 16)
                .padding(.top, 8)
                .padding(.bottom, 8)
                .background(Color.black)

                ScrollView(.vertical) {
                VStack(alignment: .leading, spacing: 0) {
                    // Banners
                    if !banners.isEmpty {
                        TabView(selection: $currentBanner) {
                            ForEach(Array(banners.enumerated()), id: \.offset) { i, b in
                                ZStack {
                                    if b.media_type == "video", let urlStr = b.image_url {
                                        MutedLoopingVideoView(url: urlStr)
                                    } else {
                                        AsyncImage(url: URL(string: b.image_url ?? "")) { phase in
                                            if let image = phase.image { image.resizable().aspectRatio(contentMode: .fill) }
                                            else { Color.gray }
                                        }
                                    }
                                }
                                    .frame(height: 350).clipped()
                                    .overlay(LinearGradient(gradient: Gradient(colors: [.clear, .black]), startPoint: .top, endPoint: .bottom))
                                    .overlay(alignment: .bottomLeading) {
                                        VStack(alignment: .leading) {
                                            if b.is_ad == true {
                                                Text("SPONSORED").font(.notoSans(size: 11, relativeTo: .caption2)).bold().foregroundColor(.yellow)
                                            }
                                            Text(b.title ?? "").font(.notoSans(size: 28, relativeTo: .title)).bold()
                                            Text(b.subtitle ?? "").font(.notoSans(size: 15, relativeTo: .subheadline)).foregroundColor(.gray)
                                        }.padding()
                                    }
                                    .tag(i)
                                    .onTapGesture {
                                        if b.is_ad == true, let urlStr = b.link_url, let url = URL(string: urlStr) {
                                            UIApplication.shared.open(url)
                                        } else if let urlStr = b.link_url {
                                            let slug = urlStr.split(separator: "/").last.map(String.init) ?? urlStr
                                            Task {
                                                let target = await resolveBannerTarget(slug: slug)
                                                navPath.append(target)
                                            }
                                        }
                                    }
                            }
                        }.frame(height: 350).tabViewStyle(.page)
                    }

                    if isLoading { ProgressView().padding() }
                    else {
                        // Continue Watching — per-viewer, computed from watch
                        // history, never admin-curated. Always above Latest Release.
                        if !continueWatching.isEmpty {
                            Text("Continue Watching").font(.notoSans(size: 22, relativeTo: .title2)).bold().padding(.horizontal, 16).padding(.top, 24)
                            ScrollView(.horizontal, showsIndicators: false) {
                                HStack(spacing: 12) {
                                    ForEach(continueWatching) { item in
                                        NavigationLink(destination: PlayerView(slug: item.episode_slug)) {
                                            VStack(alignment: .leading) {
                                                AsyncImage(url: URL(string: item.cover_image_url ?? "")) { phase in
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
                                                Text(item.title).font(.notoSans(size: 12, relativeTo: .caption)).foregroundColor(.white).lineLimit(2).frame(width: 140, alignment: .leading)
                                            }
                                        }
                                    }
                                }.padding(.horizontal, 16)
                            }
                        }

                        // Latest releases row — distinct from the admin-managed
                        // "Trending Now" shelf rendered below.
                        if !series.isEmpty {
                            Text("Latest Release").font(.notoSans(size: 22, relativeTo: .title2)).bold().padding(.horizontal, 16).padding(.top, 24)
                            ScrollView(.horizontal, showsIndicators: false) {
                                HStack(spacing: 12) {
                                    ForEach(series) { s in
                                        seriesCardLink(s) {
                                            VStack(alignment: .leading) {
                                                seriesCoverImage(s, width: 140, height: 200)
                                                Text(s.title).font(.notoSans(size: 12, relativeTo: .caption)).foregroundColor(.white).lineLimit(2).frame(width: 140, alignment: .leading)
                                            }
                                        }
                                    }
                                }.padding(.horizontal, 16)
                            }
                        }

                        // Display each shelf with its movies listed horizontally
                        ForEach(shelves) { shelf in
                            let list = shelfSeries[shelf.slug] ?? []
                            if !list.isEmpty {
                                Text(shelf.name).font(.notoSans(size: 22, relativeTo: .title2)).bold().padding(.horizontal, 16).padding(.top, 24)
                                ScrollView(.horizontal, showsIndicators: false) {
                                    HStack(spacing: 12) {
                                        ForEach(list) { s in
                                            seriesCardLink(s) {
                                                VStack(alignment: .leading) {
                                                    seriesCoverImage(s, width: 140, height: 200)
                                                    Text(s.title).font(.notoSans(size: 12, relativeTo: .caption)).foregroundColor(.white).lineLimit(2).frame(width: 140, alignment: .leading)
                                                }
                                            }
                                        }
                                    }.padding(.horizontal, 16)
                                }
                            }
                        }

                        // Bottom "All Series" section
                        if !series.isEmpty {
                            Text("All Series").font(.notoSans(size: 22, relativeTo: .title2)).bold().padding(.horizontal, 16).padding(.top, 28)
                            
                            LazyVGrid(columns: [GridItem(.flexible()), GridItem(.flexible())], spacing: 16) {
                                ForEach(series) { s in
                                    seriesCardLink(s) {
                                        VStack(alignment: .leading, spacing: 6) {
                                            seriesCoverImage(s, width: nil, height: 220)
                                                .frame(maxWidth: .infinity)

                                            Text(s.title).font(.notoSans(size: 12, relativeTo: .caption)).bold().foregroundColor(.white).lineLimit(1)
                                            if let genre = s.genre {
                                                Text(genre).font(.notoSans(size: 11, relativeTo: .caption2)).foregroundColor(.gray)
                                            }
                                        }
                                    }
                                }
                            }
                            .padding(.horizontal, 16)
                            .padding(.bottom, 30)
                        }
                    }
                }
                }
                .refreshable {
                    await loadHomeData()
                    await NotificationCenterStore.shared.load(postSystemNotifications: false)
                }
                .background(Color.black)
            }
            .background(Color.black).preferredColorScheme(.dark)
            .navigationDestination(for: HomeNavTarget.self) { target in
                switch target {
                case .player(let slug): PlayerView(slug: slug)
                case .seriesDetail(let slug): SeriesDetailView(slug: slug)
                }
            }
        }
        .task {
            isLoading = true
            await loadHomeData()
            isLoading = false
            await NotificationCenterStore.shared.load(postSystemNotifications: true)
            if let adsConfig = try? await APIClient.shared.getAdsConfig(), let unitId = adsConfig["admob_ios_rewarded_unit_id"] {
                RewardedAdManager.shared.configure(adUnitID: unitId)
            }
        }
        .task {
            // Each slide (ad or regular banner) can set its own on-screen
            // duration (ads: 5/10/15s per what the advertiser picked; regular
            // banners default to 5s). Cancelled automatically when the view
            // disappears since this is a SwiftUI `.task`.
            while !Task.isCancelled {
                guard !banners.isEmpty else {
                    try? await Task.sleep(nanoseconds: 500_000_000)
                    continue
                }
                let seconds = banners[currentBanner].duration_seconds ?? 5
                try? await Task.sleep(nanoseconds: UInt64(max(1, seconds)) * 1_000_000_000)
                withAnimation { currentBanner = (currentBanner + 1) % banners.count }
            }
        }
    }
}
