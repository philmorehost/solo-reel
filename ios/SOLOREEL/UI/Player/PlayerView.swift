import SwiftUI
import AVKit

struct PlayerView: View {
    let slug: String
    @State private var episode: Episode?; @State private var isLoading = true
    @Environment(\.dismiss) private var dismiss

    var body: some View {
        ZStack {
            Color.black.ignoresSafeArea()
            if isLoading { ProgressView().tint(.red) }
            else if let ep = episode, let urlStr = ep.video_hls_url, let url = URL(string: urlStr) {
                VideoPlayer(player: AVPlayer(url: url))
                    .ignoresSafeArea()
                    .frame(maxWidth: .infinity, maxHeight: .infinity)
                VStack {
                    HStack {
                        Button { dismiss() } label: { Image(systemName: "xmark").foregroundColor(.white).padding() }
                        Spacer()
                    }
                    Spacer()
                }
            }
        }
        .task {
            do { episode = try await APIClient.shared.getEpisode(slug: slug); isLoading = false }
            catch { isLoading = false }
        }
    }
}
