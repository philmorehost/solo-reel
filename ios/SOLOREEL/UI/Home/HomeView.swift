import SwiftUI
import UIKit

struct HomeView: View {
    @State private var banners: [Banner] = []
    @State private var series: [Series] = []
    @State private var shelves: [Shelf] = []
    @State private var shelfSeries: [String: [Series]] = [:]
    @State private var isLoading = true
    @State private var currentBanner = 0

    private func loadHomeData() async {
        do {
            banners = try await APIClient.shared.getBanners()
            series = try await APIClient.shared.getSeries()
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
        } catch {}
    }

    var body: some View {
        NavigationStack {
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
                                        }
                                    }
                            }
                        }.frame(height: 350).tabViewStyle(.page)
                    }

                    if isLoading { ProgressView().padding() }
                    else {
                        // Latest releases row — distinct from the admin-managed
                        // "Trending Now" shelf rendered below.
                        if !series.isEmpty {
                            Text("Latest Release").font(.notoSans(size: 22, relativeTo: .title2)).bold().padding(.horizontal, 16).padding(.top, 24)
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
                                    NavigationLink(destination: SeriesDetailView(slug: s.slug)) {
                                        VStack(alignment: .leading, spacing: 6) {
                                            AsyncImage(url: URL(string: s.cover_image_url ?? "")) { phase in
                                                if let image = phase.image {
                                                    image.resizable()
                                                        .aspectRatio(contentMode: .fill)
                                                        .frame(height: 220)
                                                        .cornerRadius(12)
                                                } else {
                                                    Color.gray
                                                        .frame(height: 220)
                                                        .cornerRadius(12)
                                                }
                                            }
                                            .frame(maxWidth: .infinity)
                                            .clipped()
                                            
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
