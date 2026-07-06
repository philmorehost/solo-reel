import SwiftUI
import AVKit
import AVFoundation

/// Muted, looping, no-controls video for banner/ad placements — not a full player.
struct MutedLoopingVideoView: View {
    let url: String
    @State private var player: AVQueuePlayer?
    @State private var looper: AVPlayerLooper?

    var body: some View {
        Group {
            if let player = player {
                VideoPlayerRepresentable(player: player)
            } else {
                Color.black
            }
        }
        .onAppear {
            guard player == nil, let videoUrl = URL(string: url) else { return }
            let item = AVPlayerItem(url: videoUrl)
            let queuePlayer = AVQueuePlayer()
            queuePlayer.isMuted = true
            looper = AVPlayerLooper(player: queuePlayer, templateItem: item)
            queuePlayer.play()
            player = queuePlayer
        }
        .onDisappear {
            player?.pause()
        }
    }
}

/// Plain AVPlayerLayer wrapper — VideoPlayer/AVPlayerViewController would show
/// controls; this is a silent decorative video with no user-facing chrome.
private struct VideoPlayerRepresentable: UIViewRepresentable {
    let player: AVQueuePlayer

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
