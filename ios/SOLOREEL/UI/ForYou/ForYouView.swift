import SwiftUI
import AVKit

/// Vertical feed of admin-uploaded trailers, auto-playing one after another —
/// the mobile counterpart to /for-you on web. "Watch Now" resumes the series
/// via the resume_slug the backend already resolved. Uses a plain scrolling
/// feed with visibility-based play/pause rather than PlayerView's UIKit
/// paging controller — that one is tightly coupled to Episode + the unlock
/// overlay/likes/comments delegate, which trailers don't need.
struct ForYouView: View {
    @State private var trailers: [ForYouItem] = []
    @State private var isLoading = true
    @State private var activeId: Int? = nil
    @State private var muted = false

    private var guestIdOrNil: String? { TokenManager.shared.isLoggedIn ? nil : TokenManager.shared.guestId }

    var body: some View {
        NavigationStack {
            GeometryReader { geo in
                if isLoading {
                    ProgressView().tint(.red).frame(maxWidth: .infinity, maxHeight: .infinity).background(Color.black)
                } else if trailers.isEmpty {
                    VStack(spacing: 12) {
                        Text("🎬").font(.system(size: 52))
                        Text("No trailers yet").font(.notoSans(size: 18, weight: .bold, relativeTo: .headline)).foregroundColor(.white)
                        Text("Check back soon for new trailers.").font(.notoSans(size: 13, relativeTo: .caption)).foregroundColor(Color(white: 0.55))
                    }.frame(maxWidth: .infinity, maxHeight: .infinity).background(Color.black)
                } else {
                    ScrollView(.vertical, showsIndicators: false) {
                        LazyVStack(spacing: 0) {
                            ForEach(trailers) { trailer in
                                TrailerCard(trailer: trailer, isActive: activeId == trailer.id, muted: muted, onToggleMute: { muted.toggle() })
                                    .frame(width: geo.size.width, height: geo.size.height)
                                    .onAppear { activeId = trailer.id }
                            }
                        }
                    }
                    .scrollDisabled(false)
                    .background(Color.black)
                }
            }
            .background(Color.black)
            .navigationBarHidden(true)
            .preferredColorScheme(.dark)
        }
        .task {
            trailers = (try? await APIClient.shared.getForYou(guestId: guestIdOrNil)) ?? []
            isLoading = false
        }
    }
}

private struct TrailerCard: View {
    let trailer: ForYouItem
    let isActive: Bool
    let muted: Bool
    let onToggleMute: () -> Void
    @State private var player: AVPlayer? = nil

    var body: some View {
        ZStack(alignment: .bottomLeading) {
            Color.black
            if let player {
                VideoPlayer(player: player)
                    .disabled(true)
                    .allowsHitTesting(false)
            }

            LinearGradient(colors: [.clear, .black.opacity(0.4), .black.opacity(0.8)], startPoint: .top, endPoint: .bottom)

            VStack(alignment: .leading, spacing: 12) {
                Text(trailer.series_title).font(.notoSans(size: 20, weight: .heavy, relativeTo: .title2)).foregroundColor(.white)
                NavigationLink(destination: destinationView) {
                    HStack {
                        Image(systemName: "play.fill")
                        Text("Watch Now").font(.notoSans(size: 15, weight: .bold, relativeTo: .headline))
                    }
                    .foregroundColor(.black)
                    .padding(.horizontal, 20).padding(.vertical, 12)
                    .background(Color.white)
                    .cornerRadius(24)
                }
            }
            .padding(20)
            .padding(.bottom, 24)

            Button(action: onToggleMute) {
                Image(systemName: muted ? "speaker.slash.fill" : "speaker.wave.2.fill")
                    .foregroundColor(.white)
                    .padding(10)
                    .background(Color.black.opacity(0.4))
                    .clipShape(Circle())
            }
            .frame(maxWidth: .infinity, alignment: .trailing)
            .padding(16)
        }
        .onAppear { setUpPlayerIfNeeded() }
        .onChange(of: isActive) { active in
            if active { player?.play() } else { player?.pause() }
        }
        .onChange(of: muted) { m in player?.isMuted = m }
        .onDisappear { player?.pause() }
    }

    @ViewBuilder
    private var destinationView: some View {
        if let slug = trailer.resume_slug {
            PlayerView(slug: slug)
        } else {
            SeriesDetailView(slug: trailer.series_slug)
        }
    }

    private func setUpPlayerIfNeeded() {
        guard player == nil, let url = URL(string: trailer.trailer_url) else { return }
        let item = AVPlayerItem(url: url)
        let p = AVPlayer(playerItem: item)
        p.isMuted = muted
        p.actionAtItemEnd = .none
        NotificationCenter.default.addObserver(forName: .AVPlayerItemDidPlayToEndTime, object: item, queue: .main) { _ in
            p.seek(to: .zero)
            p.play()
        }
        player = p
        if isActive { p.play() }
    }
}
