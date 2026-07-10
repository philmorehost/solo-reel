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

    // Header live search — same debounced /search endpoint SearchView uses.
    @State private var searchQuery = ""
    @State private var searchResults: [Series] = []
    @State private var isSearching = false
    @State private var searchTask: Task<Void, Never>? = nil

    // Content Hub Tabs: HOT / NEW / RANKING / CATEGORIES / TV SERIES / MOVIES
    @State private var activeTab = "hot"
    @State private var tabSeries: [Series] = []
    @State private var tabNewReleases: NewReleases? = nil
    @State private var tabCategories: [CategoryGroup] = []
    @State private var tabLoading = false
    @State private var tabCache: Set<String> = []

    private var guestIdOrNil: String? { TokenManager.shared.isLoggedIn ? nil : TokenManager.shared.guestId }

    private func onSearchQueryChange(_ q: String) {
        searchTask?.cancel()
        if q.trimmingCharacters(in: .whitespaces).isEmpty {
            searchResults = []
            isSearching = false
            return
        }
        searchTask = Task {
            try? await Task.sleep(nanoseconds: 400_000_000)
            if Task.isCancelled { return }
            isSearching = true
            let r = (try? await APIClient.shared.search(q: q, size: 8)) ?? []
            if Task.isCancelled { return }
            searchResults = r
            isSearching = false
        }
    }

    private func selectTab(_ tab: String) {
        activeTab = tab
        Task { await loadTab(tab) }
    }

    private func loadTab(_ tab: String) async {
        if tabCache.contains(tab) { return }
        tabLoading = true
        switch tab {
        case "hot":
            let list = (try? await APIClient.shared.getHotSeries()) ?? []
            tabSeries = list
            await fetchResumeSlugs(ids: list.map { $0.id })
        case "new":
            let releases = try? await APIClient.shared.getNewSeries()
            tabNewReleases = releases
            if let r = releases { await fetchResumeSlugs(ids: (r.coming_soon + r.all_new).map { $0.id }) }
        case "ranking":
            let list = (try? await APIClient.shared.getRanking()) ?? []
            tabSeries = list
            await fetchResumeSlugs(ids: list.map { $0.id })
        case "categories":
            let groups = (try? await APIClient.shared.getCategories()) ?? []
            tabCategories = groups
            await fetchResumeSlugs(ids: groups.flatMap { $0.series }.map { $0.id })
        case "tv_series":
            let list = (try? await APIClient.shared.search(q: "", size: 60, category: "tv_series")) ?? []
            tabSeries = list
            await fetchResumeSlugs(ids: list.map { $0.id })
        case "movies":
            let list = (try? await APIClient.shared.search(q: "", size: 60, category: "movies")) ?? []
            tabSeries = list
            await fetchResumeSlugs(ids: list.map { $0.id })
        default: break
        }
        tabCache.insert(tab)
        tabLoading = false
    }

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
                    NavigationLink(destination: VipPlansView()) {
                        HStack(spacing: 4) {
                            Text("👑").font(.notoSans(size: 15))
                            Text("VIP").font(.notoSans(size: 13, weight: .bold, relativeTo: .caption)).foregroundColor(Color(red: 0.96, green: 0.62, blue: 0.04))
                        }
                        .padding(.horizontal, 10).padding(.vertical, 6)
                        .background(Color(red: 0.96, green: 0.62, blue: 0.04).opacity(0.15))
                        .clipShape(Capsule())
                    }
                    Spacer().frame(width: 8)
                    NotificationBell()
                }
                .padding(.horizontal, 16)
                .padding(.top, 8)
                .padding(.bottom, 8)
                .background(Color.black)

                // Live search — same debounced /search endpoint SearchView uses,
                // just inline near the top with a compact dropdown.
                VStack(spacing: 0) {
                    HStack {
                        Image(systemName: "magnifyingglass").foregroundColor(Color(white: 0.4))
                        TextField("Search titles...", text: $searchQuery).foregroundColor(.white)
                            .onChange(of: searchQuery) { _ in onSearchQueryChange(searchQuery) }
                        if !searchQuery.isEmpty {
                            Button { searchQuery = ""; onSearchQueryChange("") } label: {
                                Image(systemName: "xmark.circle.fill").foregroundColor(Color(white: 0.4))
                            }
                        }
                    }.padding(12).background(Color(white: 0.08)).cornerRadius(14)

                    if !searchQuery.isEmpty {
                        VStack(spacing: 0) {
                            if isSearching {
                                ProgressView().tint(.red).padding(16)
                            } else if searchResults.isEmpty {
                                Text("No titles found.").foregroundColor(Color(white: 0.4)).padding(16)
                            } else {
                                ForEach(searchResults) { s in
                                    NavigationLink(destination: SeriesDetailView(slug: s.slug)) {
                                        HStack {
                                            seriesCoverImage(s, width: 36, height: 50)
                                            VStack(alignment: .leading) {
                                                Text(s.title).font(.notoSans(size: 13, weight: .medium, relativeTo: .subheadline)).foregroundColor(.white).lineLimit(1)
                                                Text("\(s.genre ?? "Drama") · \(s.episode_count ?? 0) episodes").font(.notoSans(size: 11, relativeTo: .caption2)).foregroundColor(Color(white: 0.5))
                                            }
                                            Spacer()
                                        }.padding(10)
                                    }
                                }
                            }
                        }.background(Color(white: 0.06)).cornerRadius(14).padding(.top, 6)
                    }
                }
                .padding(.horizontal, 16)
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
                        // Content Hub Tabs: HOT / NEW / RANKING / CATEGORIES / TV SERIES / MOVIES
                        VStack(alignment: .leading, spacing: 0) {
                            ScrollView(.horizontal, showsIndicators: false) {
                                HStack(spacing: 8) {
                                    ForEach(homeHubTabs, id: \.0) { key, label in
                                        Button { selectTab(key) } label: {
                                            Text(label)
                                                .font(.notoSans(size: 13, weight: .bold, relativeTo: .caption))
                                                .foregroundColor(activeTab == key ? .white : Color(white: 0.65))
                                                .padding(.horizontal, 16).padding(.vertical, 8)
                                                .background(activeTab == key ? Color.red : Color(white: 0.1))
                                                .cornerRadius(20)
                                        }
                                    }
                                }.padding(.horizontal, 16)
                            }
                            .padding(.top, 20)

                            ContentHubTabContent(
                                activeTab: activeTab,
                                tabSeries: tabSeries,
                                tabNewReleases: tabNewReleases,
                                tabCategories: tabCategories,
                                isLoading: tabLoading,
                                seriesCardLink: { s, content in AnyView(seriesCardLink(s, content: content)) }
                            )
                        }

                        // Continue Watching — per-viewer, computed from watch history, never admin-curated.
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
            await loadTab("hot")
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

let homeHubTabs: [(String, String)] = [
    ("hot", "🔥 HOT"),
    ("new", "✨ NEW"),
    ("ranking", "🏆 RANKING"),
    ("categories", "CATEGORIES"),
    ("tv_series", "TV SERIES"),
    ("movies", "MOVIES")
]

/// Content Hub Tabs body — HOT/TV Series/Movies share a grid layout with
/// HOT/NEW badges, NEW splits into coming-soon/all-new sections, RANKING is a
/// numbered leaderboard with a like counter, CATEGORIES is one shelf per genre.
struct ContentHubTabContent: View {
    let activeTab: String
    let tabSeries: [Series]
    let tabNewReleases: NewReleases?
    let tabCategories: [CategoryGroup]
    let isLoading: Bool
    let seriesCardLink: (Series, @escaping () -> AnyView) -> AnyView

    var body: some View {
        Group {
            if isLoading {
                ProgressView().tint(.red).frame(maxWidth: .infinity).padding(32)
            } else {
                switch activeTab {
                case "new":
                    VStack(alignment: .leading, spacing: 0) {
                        if let releases = tabNewReleases, !releases.coming_soon.isEmpty {
                            Text("🔜 Coming Soon").font(.notoSans(size: 18, weight: .bold, relativeTo: .title3)).foregroundColor(.white).padding(.horizontal, 16).padding(.top, 16)
                            hubRow(releases.coming_soon)
                        }
                        if let releases = tabNewReleases, !releases.all_new.isEmpty {
                            Text("✨ New Releases").font(.notoSans(size: 18, weight: .bold, relativeTo: .title3)).foregroundColor(.white).padding(.horizontal, 16).padding(.top, 16)
                            hubRow(releases.all_new)
                        }
                        if (tabNewReleases?.coming_soon.isEmpty ?? true) && (tabNewReleases?.all_new.isEmpty ?? true) {
                            Text("No new titles yet.").foregroundColor(Color(white: 0.4)).padding(32)
                        }
                    }
                case "ranking":
                    if tabSeries.isEmpty {
                        Text("No rankings yet — be the first to like a series!").foregroundColor(Color(white: 0.4)).padding(32)
                    } else {
                        VStack(spacing: 8) {
                            ForEach(Array(tabSeries.enumerated()), id: \.element.id) { index, s in
                                rankingRow(s, index + 1)
                            }
                        }.padding(.horizontal, 16).padding(.top, 12)
                    }
                case "categories":
                    if tabCategories.isEmpty {
                        Text("No categories yet.").foregroundColor(Color(white: 0.4)).padding(32)
                    } else {
                        VStack(alignment: .leading, spacing: 0) {
                            ForEach(tabCategories, id: \.genre) { group in
                                Text(group.genre).font(.notoSans(size: 18, weight: .bold, relativeTo: .title3)).foregroundColor(.white).padding(.horizontal, 16).padding(.top, 16)
                                hubRow(group.series)
                            }
                        }
                    }
                default:
                    // hot / tv_series / movies — same grid treatment.
                    if tabSeries.isEmpty {
                        Text("Nothing here yet.").foregroundColor(Color(white: 0.4)).padding(32)
                    } else {
                        LazyVGrid(columns: [GridItem(.flexible()), GridItem(.flexible())], spacing: 16) {
                            ForEach(tabSeries) { s in
                                seriesCardLink(s, { AnyView(hubGridCard(s)) })
                            }
                        }.padding(.horizontal, 16).padding(.top, 16)
                    }
                }
            }
        }
    }

    @ViewBuilder
    private func hubRow(_ list: [Series]) -> some View {
        ScrollView(.horizontal, showsIndicators: false) {
            HStack(spacing: 12) {
                ForEach(list) { s in
                    seriesCardLink(s, {
                        AnyView(
                            VStack(alignment: .leading) {
                                AsyncImage(url: URL(string: s.cover_image_url ?? "")) { phase in
                                    if let image = phase.image { image.resizable().aspectRatio(contentMode: .fill).frame(width: 140, height: 200).cornerRadius(12) }
                                    else { Color.gray.frame(width: 140, height: 200).cornerRadius(12) }
                                }
                                Text(s.title).font(.notoSans(size: 12, relativeTo: .caption)).foregroundColor(.white).lineLimit(2).frame(width: 140, alignment: .leading)
                            }
                        )
                    })
                }
            }.padding(.horizontal, 16)
        }
    }

    @ViewBuilder
    private func hubGridCard(_ s: Series) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            ZStack(alignment: .topLeading) {
                AsyncImage(url: URL(string: s.cover_image_url ?? "")) { phase in
                    if let image = phase.image { image.resizable().aspectRatio(contentMode: .fill) }
                    else { Color.gray }
                }
                .frame(maxWidth: .infinity, minHeight: 220, maxHeight: 220)
                .cornerRadius(12)
                .clipped()

                HStack(spacing: 4) {
                    if s.is_hot == true {
                        Text("🔥 HOT").font(.notoSans(size: 9, weight: .bold, relativeTo: .caption2)).foregroundColor(.white).padding(.horizontal, 6).padding(.vertical, 2).background(Color.red).cornerRadius(4)
                    }
                    if s.is_new == true {
                        Text("NEW").font(.notoSans(size: 9, weight: .bold, relativeTo: .caption2)).foregroundColor(.white).padding(.horizontal, 6).padding(.vertical, 2).background(Color.green).cornerRadius(4)
                    }
                }.padding(6)
            }
            Text(s.title).font(.notoSans(size: 12, weight: .semibold, relativeTo: .caption)).foregroundColor(.white).lineLimit(1)
            if let genre = s.genre { Text(genre).font(.notoSans(size: 11, relativeTo: .caption2)).foregroundColor(.gray) }
        }
    }

    @ViewBuilder
    private func rankingRow(_ s: Series, _ rank: Int) -> some View {
        seriesCardLink(s, {
            AnyView(
                HStack(spacing: 12) {
                    Text("\(rank)").font(.notoSans(size: 20, weight: .heavy, relativeTo: .title3)).foregroundColor(rank <= 3 ? .yellow : Color(white: 0.5)).frame(width: 32)
                    AsyncImage(url: URL(string: s.cover_image_url ?? "")) { phase in
                        if let image = phase.image { image.resizable().aspectRatio(contentMode: .fill) } else { Color.gray }
                    }.frame(width: 52, height: 74).cornerRadius(8).clipped()
                    VStack(alignment: .leading) {
                        Text(s.title).font(.notoSans(size: 14, weight: .semibold, relativeTo: .subheadline)).foregroundColor(.white).lineLimit(1)
                        Text("EP.\(s.episode_count ?? 0)").font(.notoSans(size: 12, relativeTo: .caption)).foregroundColor(Color(white: 0.55))
                    }
                    Spacer()
                    Text("❤ \(s.like_count ?? 0)").font(.notoSans(size: 13, weight: .bold, relativeTo: .subheadline)).foregroundColor(.red)
                }
                .padding(10)
                .background(Color(white: 0.08))
                .cornerRadius(12)
            )
        })
    }
}
