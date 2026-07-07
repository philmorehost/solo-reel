import SwiftUI
import AVKit
import AVFoundation
import UIKit

@MainActor
final class ReelFeedViewModel: ObservableObject {
    @Published var episodes: [Episode] = []
    @Published var startIndex: Int = 0
    @Published var isLoading = true
    @Published var errorMessage: String?
    @Published var interstitialAd: InterstitialAd?
    // Global mute state: unmuting one video keeps the feed unmuted as the user swipes,
    // and survives relaunches via UserDefaults.
    @Published var muted: Bool = UserDefaults.standard.object(forKey: "reel_muted") == nil
        ? true
        : UserDefaults.standard.bool(forKey: "reel_muted")

    private var guestIdOrNil: String? { TokenManager.shared.isLoggedIn ? nil : TokenManager.shared.guestId }

    func toggleMute() {
        muted.toggle()
        UserDefaults.standard.set(muted, forKey: "reel_muted")
    }

    func load(startSlug: String) async {
        isLoading = true
        errorMessage = nil
        do {
            let current = try await APIClient.shared.getEpisode(slug: startSlug, guestId: guestIdOrNil)
            var list = [current]
            if let seriesId = current.series_id {
                list = try await APIClient.shared.getEpisodes(seriesId: seriesId, guestId: guestIdOrNil)
            }
            startIndex = max(list.firstIndex(where: { $0.slug == startSlug }) ?? 0, 0)
            episodes = list
        } catch {
            errorMessage = error.localizedDescription
        }
        isLoading = false

        if InterstitialAdGate.shouldShowForNewEpisode() {
            interstitialAd = try? await APIClient.shared.getInterstitialAd()
        }
    }

    func dismissInterstitial() { interstitialAd = nil }

    /// Re-fetches the single episode after an unlock and swaps it into the feed in place,
    /// so the rest of the vertical feed (and its scroll position) is undisturbed.
    private func refreshEpisode(slug: String) async {
        guard let updated = try? await APIClient.shared.getEpisode(slug: slug, guestId: guestIdOrNil) else { return }
        if let idx = episodes.firstIndex(where: { $0.id == updated.id }) {
            episodes[idx] = updated
        }
    }

    func unlockWithCoins(episode: Episode) async -> String? {
        do {
            try await APIClient.shared.unlockWithCoins(episodeId: episode.id, guestId: guestIdOrNil)
            await refreshEpisode(slug: episode.slug)
            return nil
        } catch {
            return error.localizedDescription
        }
    }

    func unlockWithAd(episode: Episode) async -> String? {
        do {
            try await APIClient.shared.unlockWithAd(episodeId: episode.id, guestId: guestIdOrNil)
            await refreshEpisode(slug: episode.slug)
            return nil
        } catch {
            return error.localizedDescription
        }
    }
}

struct PlayerView: View {
    let slug: String
    @StateObject private var vm = ReelFeedViewModel()
    @Environment(\.dismiss) private var dismiss

    var body: some View {
        ZStack {
            Color.black.ignoresSafeArea()

            if vm.isLoading {
                ProgressView().tint(.red)
            } else if let err = vm.errorMessage, vm.episodes.isEmpty {
                Text(err).foregroundColor(.red).padding()
            } else if !vm.episodes.isEmpty {
                ReelPagerView(vm: vm, startSlug: slug)
                    .ignoresSafeArea()
            }

            VStack {
                HStack {
                    Button { dismiss() } label: { Image(systemName: "xmark").foregroundColor(.white).padding() }
                    Spacer()
                }
                Spacer()
            }

            if let ad = vm.interstitialAd {
                interstitialView(for: ad)
            }
        }
        .task { await vm.load(startSlug: slug) }
    }

    @ViewBuilder
    private func interstitialView(for ad: InterstitialAd) -> some View {
        ZStack {
            Color.black.ignoresSafeArea()
            if ad.media_type == "video", let urlStr = ad.media_url {
                MutedLoopingVideoView(url: urlStr).ignoresSafeArea()
            } else if let urlStr = ad.media_url, let url = URL(string: urlStr) {
                AsyncImage(url: url) { phase in
                    if let image = phase.image { image.resizable().aspectRatio(contentMode: .fill) }
                    else { Color.black }
                }.ignoresSafeArea()
            }
            VStack {
                HStack {
                    Text("Sponsored").font(.notoSans(size: 12, relativeTo: .caption)).bold().foregroundColor(.white)
                        .padding(.horizontal, 8).padding(.vertical, 4)
                        .background(Color.black.opacity(0.6)).cornerRadius(4)
                    Spacer()
                    Button { vm.dismissInterstitial() } label: {
                        Image(systemName: "xmark").foregroundColor(.white)
                            .padding(8).background(Color.black.opacity(0.6)).clipShape(Circle())
                    }
                }.padding()
                Spacer()
                if let urlStr = ad.target_url, let url = URL(string: urlStr) {
                    Button(ad.title ?? "Learn More") { UIApplication.shared.open(url) }
                        .buttonStyle(.borderedProminent).tint(.white).foregroundColor(.black)
                        .padding()
                }
            }
        }
    }
}

/// Bridges a vertically-paging UICollectionView into SwiftUI. This is the direct
/// UIKit equivalent of the requirement — a real UICollectionView with
/// isPagingEnabled + vertical scroll direction, rather than SwiftUI's own
/// TabView(.page) (which doesn't give per-cell control over player mount/unmount).
struct ReelPagerView: UIViewControllerRepresentable {
    @ObservedObject var vm: ReelFeedViewModel
    let startSlug: String

    func makeUIViewController(context: Context) -> ReelCollectionViewController {
        ReelCollectionViewController(vm: vm, startSlug: startSlug)
    }

    func updateUIViewController(_ vc: ReelCollectionViewController, context: Context) {
        vc.apply(episodes: vm.episodes, muted: vm.muted)
    }
}

final class ReelCollectionViewController: UIViewController, UICollectionViewDataSource, UICollectionViewDelegate {
    private let vm: ReelFeedViewModel
    private let startSlug: String
    private var episodes: [Episode] = []
    private var didScrollToStart = false
    private var collectionView: UICollectionView!

    init(vm: ReelFeedViewModel, startSlug: String) {
        self.vm = vm
        self.startSlug = startSlug
        super.init(nibName: nil, bundle: nil)
    }
    required init?(coder: NSCoder) { fatalError("init(coder:) has not been implemented") }

    override func viewDidLoad() {
        super.viewDidLoad()
        let layout = UICollectionViewFlowLayout()
        layout.scrollDirection = .vertical
        layout.minimumLineSpacing = 0
        layout.minimumInteritemSpacing = 0
        layout.itemSize = view.bounds.size

        collectionView = UICollectionView(frame: view.bounds, collectionViewLayout: layout)
        collectionView.autoresizingMask = [.flexibleWidth, .flexibleHeight]
        collectionView.isPagingEnabled = true
        collectionView.showsVerticalScrollIndicator = false
        collectionView.backgroundColor = .black
        collectionView.contentInsetAdjustmentBehavior = .never
        // The feed itself owns vertical paging; don't let a system pull-to-refresh
        // (if one is ever added to an ancestor) fight the same gesture.
        collectionView.bounces = true
        collectionView.dataSource = self
        collectionView.delegate = self
        collectionView.register(ReelCell.self, forCellWithReuseIdentifier: ReelCell.reuseId)
        view.addSubview(collectionView)
    }

    override func viewDidLayoutSubviews() {
        super.viewDidLayoutSubviews()
        if let layout = collectionView.collectionViewLayout as? UICollectionViewFlowLayout {
            layout.itemSize = view.bounds.size
        }
    }

    /// Called from SwiftUI whenever the feed's episode list or mute state changes.
    func apply(episodes: [Episode], muted: Bool) {
        let firstLoad = self.episodes.isEmpty && !episodes.isEmpty
        self.episodes = episodes
        collectionView.reloadData()

        if firstLoad, !didScrollToStart {
            didScrollToStart = true
            let index = episodes.firstIndex(where: { $0.slug == startSlug }) ?? 0
            DispatchQueue.main.async { [weak self] in
                guard let self else { return }
                self.collectionView.scrollToItem(at: IndexPath(item: index, section: 0), at: .top, animated: false)
                self.updatePlaybackForCurrentPosition()
            }
        }

        for case let cell as ReelCell in collectionView.visibleCells {
            cell.setMuted(muted)
        }
    }

    func collectionView(_ collectionView: UICollectionView, numberOfItemsInSection section: Int) -> Int {
        episodes.count
    }

    func collectionView(_ collectionView: UICollectionView, cellForItemAt indexPath: IndexPath) -> UICollectionViewCell {
        let cell = collectionView.dequeueReusableCell(withReuseIdentifier: ReelCell.reuseId, for: indexPath) as! ReelCell
        let episode = episodes[indexPath.item]
        cell.configure(episode: episode, muted: vm.muted, delegate: self)
        return cell
    }

    func scrollViewDidEndDecelerating(_ scrollView: UIScrollView) { updatePlaybackForCurrentPosition() }
    func scrollViewDidEndScrollingAnimation(_ scrollView: UIScrollView) { updatePlaybackForCurrentPosition() }

    private var currentIndex: Int {
        guard view.bounds.height > 0 else { return 0 }
        return Int((collectionView.contentOffset.y / view.bounds.height).rounded())
    }

    /// Autoplay the page that just snapped into place; pause + rewind every other
    /// mounted cell. UICollectionView's own reuse pool already keeps memory bounded
    /// to roughly the visible cell plus one screen of overscroll in each direction,
    /// matching the "current + neighbor" preload budget.
    private func updatePlaybackForCurrentPosition() {
        let active = currentIndex
        for case let cell as ReelCell in collectionView.visibleCells {
            guard let indexPath = collectionView.indexPath(for: cell) else { continue }
            if indexPath.item == active {
                cell.play()
            } else {
                cell.pauseAndReset()
            }
        }
    }

    fileprivate func advance(from index: Int) {
        let next = index + 1
        guard next < episodes.count else { return }
        collectionView.scrollToItem(at: IndexPath(item: next, section: 0), at: .top, animated: true)
    }
}

protocol ReelCellDelegate: AnyObject {
    func reelCell(_ cell: ReelCell, didFinishEpisode episode: Episode)
    func reelCellDidToggleMute(_ cell: ReelCell)
    func reelCell(_ cell: ReelCell, unlockWithCoins episode: Episode)
    func reelCell(_ cell: ReelCell, unlockWithAd episode: Episode)
}

extension ReelCollectionViewController: ReelCellDelegate {
    func reelCell(_ cell: ReelCell, didFinishEpisode episode: Episode) {
        guard let indexPath = collectionView.indexPath(for: cell) else { return }
        advance(from: indexPath.item)
    }

    func reelCellDidToggleMute(_ cell: ReelCell) { vm.toggleMute() }

    func reelCell(_ cell: ReelCell, unlockWithCoins episode: Episode) {
        Task { @MainActor in
            let error = await vm.unlockWithCoins(episode: episode)
            cell.showUnlockResult(error: error, updatedEpisode: error == nil ? vm.episodes.first(where: { $0.id == episode.id }) : nil)
        }
    }

    func reelCell(_ cell: ReelCell, unlockWithAd episode: Episode) {
        RewardedAdManager.shared.showAd(
            onRewarded: { [weak self] in
                Task { @MainActor in
                    let error = await self?.vm.unlockWithAd(episode: episode)
                    cell.showUnlockResult(error: error, updatedEpisode: error == nil ? self?.vm.episodes.first(where: { $0.id == episode.id }) : nil)
                }
            },
            onFailed: { message in cell.showUnlockResult(error: message, updatedEpisode: nil) }
        )
    }
}

/// A single full-screen video card. Chrome (title, mute button, unlock CTA) is
/// plain UIKit rather than an embedded SwiftUI hierarchy, since a UICollectionViewCell
/// mixing both content-management systems is harder to reason about without a
/// compiler on hand to verify it — this keeps the risk surface small.
final class ReelCell: UICollectionViewCell {
    static let reuseId = "ReelCell"

    private weak var delegate: ReelCellDelegate?
    private var episode: Episode?

    private var player: AVPlayer?
    private var playerLayer: AVPlayerLayer?
    private var endObserver: NSObjectProtocol?

    private let titleLabel = UILabel()
    private let subtitleLabel = UILabel()
    private let muteButton = UIButton(type: .system)
    private let heartImageView = UIImageView(image: UIImage(systemName: "heart.fill"))
    private let lockedOverlay = UIView()
    private let lockTitleLabel = UILabel()
    private let lockBodyLabel = UILabel()
    private let unlockCoinsButton = UIButton(type: .system)
    private let unlockAdButton = UIButton(type: .system)
    private let unlockErrorLabel = UILabel()

    private lazy var singleTap: UITapGestureRecognizer = {
        let g = UITapGestureRecognizer(target: self, action: #selector(handleSingleTap))
        g.numberOfTapsRequired = 1
        return g
    }()
    private lazy var doubleTap: UITapGestureRecognizer = {
        let g = UITapGestureRecognizer(target: self, action: #selector(handleDoubleTap))
        g.numberOfTapsRequired = 2
        return g
    }()

    override init(frame: CGRect) {
        super.init(frame: frame)
        setUpViews()
    }
    required init?(coder: NSCoder) { fatalError("init(coder:) has not been implemented") }

    private func setUpViews() {
        backgroundColor = .black
        contentView.backgroundColor = .black

        singleTap.require(toFail: doubleTap)
        contentView.addGestureRecognizer(singleTap)
        contentView.addGestureRecognizer(doubleTap)

        titleLabel.textColor = .white
        titleLabel.font = .boldSystemFont(ofSize: 17)
        titleLabel.numberOfLines = 2
        subtitleLabel.textColor = UIColor.white.withAlphaComponent(0.8)
        subtitleLabel.font = .systemFont(ofSize: 13)

        let textStack = UIStackView(arrangedSubviews: [titleLabel, subtitleLabel])
        textStack.axis = .vertical
        textStack.spacing = 2
        textStack.translatesAutoresizingMaskIntoConstraints = false
        contentView.addSubview(textStack)

        muteButton.tintColor = .white
        muteButton.backgroundColor = UIColor.black.withAlphaComponent(0.4)
        muteButton.layer.cornerRadius = 18
        muteButton.translatesAutoresizingMaskIntoConstraints = false
        muteButton.addTarget(self, action: #selector(handleMuteTap), for: .touchUpInside)
        contentView.addSubview(muteButton)

        heartImageView.tintColor = .systemRed
        heartImageView.alpha = 0
        heartImageView.contentMode = .scaleAspectFit
        heartImageView.translatesAutoresizingMaskIntoConstraints = false
        contentView.addSubview(heartImageView)

        setUpLockedOverlay()

        NSLayoutConstraint.activate([
            textStack.leadingAnchor.constraint(equalTo: contentView.leadingAnchor, constant: 16),
            textStack.trailingAnchor.constraint(lessThanOrEqualTo: contentView.trailingAnchor, constant: -16),
            textStack.bottomAnchor.constraint(equalTo: contentView.safeAreaLayoutGuide.bottomAnchor, constant: -24),

            muteButton.topAnchor.constraint(equalTo: contentView.safeAreaLayoutGuide.topAnchor, constant: 12),
            muteButton.trailingAnchor.constraint(equalTo: contentView.trailingAnchor, constant: -16),
            muteButton.widthAnchor.constraint(equalToConstant: 36),
            muteButton.heightAnchor.constraint(equalToConstant: 36),

            heartImageView.centerXAnchor.constraint(equalTo: contentView.centerXAnchor),
            heartImageView.centerYAnchor.constraint(equalTo: contentView.centerYAnchor),
            heartImageView.widthAnchor.constraint(equalToConstant: 96),
            heartImageView.heightAnchor.constraint(equalToConstant: 96),
        ])
    }

    private func setUpLockedOverlay() {
        lockedOverlay.backgroundColor = UIColor.black.withAlphaComponent(0.75)
        lockedOverlay.translatesAutoresizingMaskIntoConstraints = false
        lockedOverlay.isHidden = true
        contentView.addSubview(lockedOverlay)

        lockTitleLabel.textColor = .white
        lockTitleLabel.font = .boldSystemFont(ofSize: 20)
        lockTitleLabel.textAlignment = .center

        lockBodyLabel.textColor = .lightGray
        lockBodyLabel.font = .systemFont(ofSize: 14)
        lockBodyLabel.textAlignment = .center
        lockBodyLabel.numberOfLines = 0

        unlockCoinsButton.setTitleColor(.white, for: .normal)
        unlockCoinsButton.backgroundColor = UIColor.systemRed
        unlockCoinsButton.layer.cornerRadius = 22
        unlockCoinsButton.titleLabel?.font = .boldSystemFont(ofSize: 16)
        unlockCoinsButton.addTarget(self, action: #selector(handleUnlockWithCoins), for: .touchUpInside)

        unlockAdButton.setTitle("Watch Ad to Unlock", for: .normal)
        unlockAdButton.setTitleColor(.white, for: .normal)
        unlockAdButton.layer.borderColor = UIColor.white.cgColor
        unlockAdButton.layer.borderWidth = 1
        unlockAdButton.layer.cornerRadius = 22
        unlockAdButton.titleLabel?.font = .boldSystemFont(ofSize: 16)
        unlockAdButton.addTarget(self, action: #selector(handleUnlockWithAd), for: .touchUpInside)

        unlockErrorLabel.textColor = .systemRed
        unlockErrorLabel.font = .systemFont(ofSize: 13)
        unlockErrorLabel.textAlignment = .center
        unlockErrorLabel.numberOfLines = 0

        let stack = UIStackView(arrangedSubviews: [lockTitleLabel, lockBodyLabel, unlockCoinsButton, unlockAdButton, unlockErrorLabel])
        stack.axis = .vertical
        stack.spacing = 12
        stack.alignment = .fill
        stack.translatesAutoresizingMaskIntoConstraints = false
        lockedOverlay.addSubview(stack)

        NSLayoutConstraint.activate([
            lockedOverlay.leadingAnchor.constraint(equalTo: contentView.leadingAnchor),
            lockedOverlay.trailingAnchor.constraint(equalTo: contentView.trailingAnchor),
            lockedOverlay.topAnchor.constraint(equalTo: contentView.topAnchor),
            lockedOverlay.bottomAnchor.constraint(equalTo: contentView.bottomAnchor),

            stack.centerYAnchor.constraint(equalTo: lockedOverlay.centerYAnchor),
            stack.leadingAnchor.constraint(equalTo: lockedOverlay.leadingAnchor, constant: 32),
            stack.trailingAnchor.constraint(equalTo: lockedOverlay.trailingAnchor, constant: -32),
            unlockCoinsButton.heightAnchor.constraint(equalToConstant: 44),
            unlockAdButton.heightAnchor.constraint(equalToConstant: 44),
        ])
    }

    override func prepareForReuse() {
        super.prepareForReuse()
        pauseAndReset()
        player = nil
        playerLayer?.removeFromSuperlayer()
        playerLayer = nil
        if let observer = endObserver { NotificationCenter.default.removeObserver(observer) }
        endObserver = nil
        lockedOverlay.isHidden = true
        unlockErrorLabel.text = nil
        unlockCoinsButton.isHidden = false
        unlockAdButton.isHidden = false
    }

    override func layoutSubviews() {
        super.layoutSubviews()
        playerLayer?.frame = contentView.bounds
    }

    func configure(episode: Episode, muted: Bool, delegate: ReelCellDelegate) {
        self.episode = episode
        self.delegate = delegate
        titleLabel.text = "EP \(episode.episode_number ?? 0) · \(episode.title)"
        subtitleLabel.text = episode.series_title
        updateMuteIcon(muted: muted)

        let unlocked = episode.is_unlocked == true || episode.is_free == true
        lockedOverlay.isHidden = unlocked

        if unlocked, let urlStr = episode.video_hls_url, let url = URL(string: urlStr) {
            let item = AVPlayerItem(url: url)
            let p = AVPlayer(playerItem: item)
            p.isMuted = muted
            player = p
            let layer = AVPlayerLayer(player: p)
            layer.frame = contentView.bounds
            layer.videoGravity = .resizeAspectFill
            contentView.layer.insertSublayer(layer, at: 0)
            playerLayer = layer
            endObserver = NotificationCenter.default.addObserver(forName: .AVPlayerItemDidPlayToEndTime, object: item, queue: .main) { [weak self] _ in
                guard let self, let episode = self.episode else { return }
                self.delegate?.reelCell(self, didFinishEpisode: episode)
            }
        } else {
            let unlockMethod = episode.unlock_method ?? "coins"
            lockTitleLabel.text = "Episode \(episode.episode_number ?? 0) Locked"
            lockBodyLabel.text = unlockMethod == "ads"
                ? "This episode can be unlocked by watching a short ad."
                : "This episode requires \(Int(episode.coin_cost ?? 0)) coins to unlock."
            unlockCoinsButton.setTitle("Unlock for \(Int(episode.coin_cost ?? 0)) Coins", for: .normal)
            unlockCoinsButton.isHidden = !(unlockMethod == "coins" || unlockMethod == "both")
            unlockAdButton.isHidden = !(unlockMethod == "ads" || unlockMethod == "both")
        }
    }

    func setMuted(_ muted: Bool) {
        player?.isMuted = muted
        updateMuteIcon(muted: muted)
    }

    func play() { player?.play() }
    func pauseAndReset() {
        player?.pause()
        player?.seek(to: .zero)
    }

    func showUnlockResult(error: String?, updatedEpisode: Episode?) {
        if let error {
            unlockErrorLabel.text = error
            return
        }
        guard let updatedEpisode, let delegate else { return }
        configure(episode: updatedEpisode, muted: player?.isMuted ?? true, delegate: delegate)
        play()
    }

    private func updateMuteIcon(muted: Bool) {
        muteButton.setImage(UIImage(systemName: muted ? "speaker.slash.fill" : "speaker.wave.2.fill"), for: .normal)
    }

    @objc private func handleMuteTap() { delegate?.reelCellDidToggleMute(self) }

    @objc private func handleUnlockWithCoins() {
        guard let episode else { return }
        unlockErrorLabel.text = nil
        delegate?.reelCell(self, unlockWithCoins: episode)
    }

    @objc private func handleUnlockWithAd() {
        guard let episode else { return }
        unlockErrorLabel.text = nil
        delegate?.reelCell(self, unlockWithAd: episode)
    }

    @objc private func handleSingleTap() {
        guard let p = player else { return }
        if p.rate == 0 { p.play() } else { p.pause() }
    }

    @objc private func handleDoubleTap() {
        heartImageView.alpha = 1
        heartImageView.transform = CGAffineTransform(scaleX: 0.6, y: 0.6)
        UIView.animate(withDuration: 0.25, animations: {
            self.heartImageView.transform = CGAffineTransform(scaleX: 1.2, y: 1.2)
        }, completion: { _ in
            UIView.animate(withDuration: 0.35, delay: 0.15, options: [], animations: {
                self.heartImageView.alpha = 0
            })
        })
    }
}
