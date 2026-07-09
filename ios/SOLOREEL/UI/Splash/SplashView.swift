import SwiftUI
import AVFoundation

/// Video splash — plays once on launch, then hands off to the main TabView.
/// Muted (consistent with this app's other decorative/background video
/// convention — see MutedLoopingVideoView.swift), with a fallback timeout in
/// case playback fails to start or the video runs unexpectedly long.
///
/// NOTE: splash_video.mp4 lives at ios/SOLOREEL/Resources/splash_video.mp4 in
/// this source tree. Since this repo doesn't contain the .xcodeproj, add the
/// file to the app target's "Copy Bundle Resources" build phase in Xcode
/// (drag it into the project navigator with "Copy items if needed" checked).
struct SplashView: View {
    var onFinished: () -> Void
    @State private var player: AVPlayer? = nil
    @State private var finished = false

    private func finish() {
        guard !finished else { return }
        finished = true
        onFinished()
    }

    var body: some View {
        ZStack {
            Color.black.ignoresSafeArea()
            if let player {
                SplashVideoLayerView(player: player)
                    .ignoresSafeArea()
            }
        }
        .onAppear {
            guard player == nil, let url = Bundle.main.url(forResource: "splash_video", withExtension: "mp4") else {
                finish()
                return
            }
            let item = AVPlayerItem(url: url)
            let avPlayer = AVPlayer(playerItem: item)
            avPlayer.isMuted = true
            NotificationCenter.default.addObserver(
                forName: .AVPlayerItemDidPlayToEndTime, object: item, queue: .main
            ) { _ in finish() }
            player = avPlayer
            avPlayer.play()

            // Fallback: don't strand the user on the splash screen forever if
            // playback never fires the end notification.
            DispatchQueue.main.asyncAfter(deadline: .now() + 8) { finish() }
        }
        .onDisappear {
            player?.pause()
        }
    }
}

/// Plain AVPlayerLayer wrapper — no VideoPlayer/AVPlayerViewController chrome,
/// same approach as MutedLoopingVideoView's VideoPlayerRepresentable.
private struct SplashVideoLayerView: UIViewRepresentable {
    let player: AVPlayer

    func makeUIView(context: Context) -> PlayerContainerView {
        let view = PlayerContainerView()
        view.playerLayer.player = player
        view.playerLayer.videoGravity = .resizeAspectFill
        return view
    }

    func updateUIView(_ uiView: PlayerContainerView, context: Context) {
        uiView.playerLayer.player = player
    }

    final class PlayerContainerView: UIView {
        override static var layerClass: AnyClass { AVPlayerLayer.self }
        var playerLayer: AVPlayerLayer { layer as! AVPlayerLayer }
    }
}
