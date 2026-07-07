import SwiftUI
import UIKit

struct HomeView: View {
    @State private var banners: [Banner] = []
    @State private var series: [Series] = []
    @State private var shelves: [Shelf] = []
    @State private var shelfSeries: [String: [Series]] = [:]
    @State private var selectedShelf = 0
    @State private var isLoading = true
    @State private var currentBanner = 0

    private func loadShelfSeries(_ shelf: Shelf) {
        guard shelfSeries[shelf.slug] == nil else { return }
        Task {
            if let result = try? await APIClient.shared.getSeries(shelf: shelf.slug) {
                shelfSeries[shelf.slug] = result
            }
        }
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
                                                Text("SPONSORED").font(.caption2).bold().foregroundColor(.yellow)
                                            }
                                            Text(b.title ?? "").font(.title).bold()
                                            Text(b.subtitle ?? "").font(.subheadline).foregroundColor(.gray)
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
                    else if !shelves.isEmpty {
                        // Category tabs — tapping a tab or swiping the pager below
                        // moves between shelves, mirroring the banner carousel's
                        // left/right paging pattern.
                        ScrollView(.horizontal, showsIndicators: false) {
                            HStack(spacing: 8) {
                                ForEach(Array(shelves.enumerated()), id: \.offset) { index, shelf in
                                    let selected = selectedShelf == index
                                    HStack(spacing: 4) {
                                        if let emoji = shelf.emoji, !emoji.isEmpty { Text(emoji) }
                                        Text(shelf.name).font(.footnote).fontWeight(selected ? .bold : .regular)
                                    }
                                    .foregroundColor(.white)
                                    .padding(.horizontal, 14)
                                    .padding(.vertical, 8)
                                    .background(selected ? Color(red: 0.86, green: 0.15, blue: 0.15) : Color(white: 0.1))
                                    .cornerRadius(20)
                                    .onTapGesture {
                                        withAnimation { selectedShelf = index }
                                    }
                                }
                            }.padding(.horizontal, 16).padding(.top, 16)
                        }

                        TabView(selection: $selectedShelf) {
                            ForEach(Array(shelves.enumerated()), id: \.offset) { index, shelf in
                                ScrollView(.horizontal, showsIndicators: false) {
                                    HStack(spacing: 12) {
                                        ForEach(shelfSeries[shelf.slug] ?? []) { s in
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
                                .tag(index)
                            }
                        }
                        .frame(height: 240)
                        .tabViewStyle(.page(indexDisplayMode: .never))
                        .onChange(of: selectedShelf) { newValue in
                            if newValue >= 0 && newValue < shelves.count { loadShelfSeries(shelves[newValue]) }
                        }
                    } else {
                        Text("Trending Now").font(.title2).bold().padding(.horizontal, 16).padding(.top, 16)
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
                }
                .refreshable {
                    do {
                        banners = try await APIClient.shared.getBanners()
                        series = try await APIClient.shared.getSeries()
                        shelves = try await APIClient.shared.getShelves()
                        shelfSeries = [:]
                        if let first = shelves.first { loadShelfSeries(first) }
                    } catch {}
                    await NotificationCenterStore.shared.load(postSystemNotifications: false)
                }
                .background(Color.black)
            }
            .background(Color.black).preferredColorScheme(.dark)
        }
        .task {
            isLoading = true
            do {
                banners = try await APIClient.shared.getBanners()
                series = try await APIClient.shared.getSeries()
                shelves = try await APIClient.shared.getShelves()
                if let first = shelves.first { loadShelfSeries(first) }
                isLoading = false
            }
            catch { isLoading = false }
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
