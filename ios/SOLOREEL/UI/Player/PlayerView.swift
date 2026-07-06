import SwiftUI
import AVKit
import AVFoundation

struct PlayerView: View {
    let slug: String
    @State private var episode: Episode?
    @State private var isLoading = true
    @State private var errorMessage: String?
    @State private var player: AVPlayer?
    @State private var showNextEpisodeOverlay = false
    @State private var endObserver: NSObjectProtocol?
    @ObservedObject private var tokenManager = TokenManager.shared
    @Environment(\.dismiss) private var dismiss

    private var guestIdOrNil: String? { tokenManager.isLoggedIn ? nil : tokenManager.guestId }

    var body: some View {
        ZStack {
            Color.black.ignoresSafeArea()

            if isLoading {
                ProgressView().tint(.red)
            } else if let ep = episode, ep.is_unlocked == true, let urlStr = ep.video_hls_url, let url = URL(string: urlStr) {
                VideoPlayer(player: player)
                    .ignoresSafeArea()
                    .frame(maxWidth: .infinity, maxHeight: .infinity)
                    .onAppear { setUpPlayer(url: url) }

                if showNextEpisodeOverlay {
                    VStack(spacing: 16) {
                        Text("Episode finished").foregroundColor(.white).font(.headline)
                        Button("Next Episode ▶") {
                            showNextEpisodeOverlay = false
                            Task { await loadNextEpisode() }
                        }.buttonStyle(.borderedProminent).tint(.red)
                        Button("Done") { dismiss() }.foregroundColor(.white)
                    }
                    .padding(24)
                    .background(Color.black.opacity(0.85))
                }
            } else if let ep = episode {
                unlockView(for: ep)
            } else if let err = errorMessage {
                Text(err).foregroundColor(.red).padding()
            }

            VStack {
                HStack {
                    Button { dismiss() } label: { Image(systemName: "xmark").foregroundColor(.white).padding() }
                    Spacer()
                }
                Spacer()
            }
        }
        .task { await load() }
        .onDisappear {
            player?.pause()
            if let observer = endObserver { NotificationCenter.default.removeObserver(observer) }
        }
    }

    @ViewBuilder
    private func unlockView(for ep: Episode) -> some View {
        let unlockMethod = ep.unlock_method ?? "coins"
        VStack(spacing: 16) {
            Image(systemName: "lock.fill").font(.system(size: 40)).foregroundColor(.yellow)
            Text("Unlock Episode").font(.title3).bold().foregroundColor(.white)
            Text(unlockMethod == "ads"
                 ? "This episode can be unlocked by watching a short ad."
                 : "This episode requires \(Int(ep.coin_cost ?? 0)) coins to unlock.")
                .foregroundColor(.gray).multilineTextAlignment(.center).padding(.horizontal, 32)

            if unlockMethod == "coins" || unlockMethod == "both" {
                Button("Unlock for \(Int(ep.coin_cost ?? 0)) Coins") {
                    Task { await unlockWithCoins() }
                }
                .buttonStyle(.borderedProminent).tint(.red)
            }
            if unlockMethod == "ads" || unlockMethod == "both" {
                Button("Watch Ad to Unlock") {
                    RewardedAdManager.shared.showAd(
                        onRewarded: { Task { await unlockWithAd() } },
                        onFailed: { message in errorMessage = message }
                    )
                }
                .buttonStyle(.bordered).tint(.white)
            }
            if let err = errorMessage {
                Text(err).foregroundColor(.red).font(.caption)
            }
        }
        .padding(24)
    }

    func load() async {
        isLoading = true
        errorMessage = nil
        do {
            episode = try await APIClient.shared.getEpisode(slug: slug, guestId: guestIdOrNil)
        } catch {
            errorMessage = error.localizedDescription
        }
        isLoading = false
    }

    func setUpPlayer(url: URL) {
        guard player == nil else { return }
        let item = AVPlayerItem(url: url)
        let newPlayer = AVPlayer(playerItem: item)
        player = newPlayer
        newPlayer.play()
        endObserver = NotificationCenter.default.addObserver(
            forName: .AVPlayerItemDidPlayToEndTime, object: item, queue: .main
        ) { _ in
            showNextEpisodeOverlay = true
        }
    }

    func loadNextEpisode() async {
        guard let ep = episode, let seriesId = ep.series_id else { return }
        do {
            let episodes = try await APIClient.shared.getEpisodes(seriesId: seriesId, guestId: guestIdOrNil)
            if let next = episodes.first(where: { ($0.episode_number ?? -1) == (ep.episode_number ?? 0) + 1 }) {
                if let observer = endObserver { NotificationCenter.default.removeObserver(observer) }
                player?.pause()
                player = nil
                isLoading = true
                episode = try await APIClient.shared.getEpisode(slug: next.slug, guestId: guestIdOrNil)
                isLoading = false
            } else {
                dismiss()
            }
        } catch {
            dismiss()
        }
    }

    func unlockWithCoins() async {
        guard let ep = episode else { return }
        errorMessage = nil
        do {
            try await APIClient.shared.unlockWithCoins(episodeId: ep.id, guestId: guestIdOrNil)
            episode = try await APIClient.shared.getEpisode(slug: slug, guestId: guestIdOrNil)
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    func unlockWithAd() async {
        guard let ep = episode else { return }
        errorMessage = nil
        do {
            try await APIClient.shared.unlockWithAd(episodeId: ep.id, guestId: guestIdOrNil)
            episode = try await APIClient.shared.getEpisode(slug: slug, guestId: guestIdOrNil)
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}
