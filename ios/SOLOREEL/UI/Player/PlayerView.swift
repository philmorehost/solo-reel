import SwiftUI
import AVKit
import AVFoundation
import UIKit

/// Which bottom sheet is currently presented over the reel feed — bridges the
/// UIKit action-rail buttons (in ReelCell) up to the SwiftUI `.sheet()` that
/// PlayerView owns, since ReelCell has no SwiftUI presentation context of its own.
enum PlayerSheet: Identifiable {
    case info(Episode)
    case comments(Episode)
    case offers(Episode)
    var id: String {
        switch self {
        case .info(let e): return "info-\(e.id)"
        case .comments(let e): return "comments-\(e.id)"
        case .offers(let e): return "offers-\(e.id)"
        }
    }
}

@MainActor
final class ReelFeedViewModel: ObservableObject {
    @Published var episodes: [Episode] = []
    @Published var startIndex: Int = 0
    @Published var isLoading = true
    @Published var errorMessage: String?
    @Published var interstitialAd: InterstitialAd?
    @Published var activeSheet: PlayerSheet?
    /// Set by the info sheet's episode-row tap; consumed (and cleared) by
    /// ReelPagerView.updateUIViewController to scroll the feed without navigating away.
    @Published var jumpToEpisodeId: Int?
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

    /// Fire-and-forget: powers "resume last-watched episode" and the Continue Watching shelf.
    func recordProgress(episode: Episode) {
        Task {
            try? await APIClient.shared.recordProgress(episodeId: episode.id, guestId: guestIdOrNil)
        }
    }

    func toggleLike(episode: Episode) async -> (liked: Bool, count: Int)? {
        guard let r = try? await APIClient.shared.toggleLike(episodeId: episode.id, guestId: guestIdOrNil) else { return nil }
        return (r.liked == true, r.count)
    }

    func toggleSave(episode: Episode) async -> (saved: Bool, count: Int)? {
        guard let r = try? await APIClient.shared.toggleSave(episodeId: episode.id, guestId: guestIdOrNil) else { return nil }
        return (r.saved == true, r.count)
    }

    func recordShare(episode: Episode) {
        Task { try? await APIClient.shared.recordShare(episodeId: episode.id, guestId: guestIdOrNil, platform: "ios") }
    }

    private var seriesInfoCache: [String: Series] = [:]

    func loadSeriesInfo(seriesSlug: String) async -> Series? {
        if let cached = seriesInfoCache[seriesSlug] { return cached }
        guard let series = try? await APIClient.shared.getSeriesDetail(slug: seriesSlug) else { return nil }
        seriesInfoCache[seriesSlug] = series
        return series
    }

    func loadComments(episode: Episode) async -> CommentsPage? {
        try? await APIClient.shared.getComments(episodeId: episode.id)
    }

    func postComment(episode: Episode, body: String) async -> EpisodeComment? {
        try? await APIClient.shared.postComment(episodeId: episode.id, body: body, guestId: guestIdOrNil)
    }

    // --- VIP subscription: an alternative to buying coins, not a
    // replacement. Reuses PaymentAuthSession, the same Safari-based checkout
    // already proven for coin purchases. ---

    @Published var vipPlans: [VipPlan] = []

    func loadVipPlans() {
        guard vipPlans.isEmpty else { return }
        Task {
            if let plans = try? await APIClient.shared.getVipPlans() { vipPlans = plans }
        }
    }

    func purchaseVip(planId: Int) async -> (authUrl: String?, reference: String?, error: String?) {
        guard TokenManager.shared.isLoggedIn else {
            return (nil, nil, "Please sign in to subscribe to VIP.")
        }
        do {
            let result = try await APIClient.shared.purchaseVip(planId: planId)
            if let url = result.authorization_url { return (url, result.reference, nil) }
            return (nil, nil, "Could not initiate payment. Please try again.")
        } catch {
            return (nil, nil, error.localizedDescription)
        }
    }

    /// Verifies a completed VIP payment, then retries the normal coin-unlock
    /// call for this episode — it now succeeds for free via the server's VIP
    /// gating in Api\TransactionController::unlock(), no separate "VIP
    /// unlock" client path needed.
    func verifyVipAndRetryUnlock(reference: String, episode: Episode) async -> String? {
        _ = try? await APIClient.shared.verifyPayment(reference: reference)
        return await unlockWithCoins(episode: episode)
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
        .sheet(item: $vm.activeSheet) { sheet in
            switch sheet {
            case .info(let episode):
                SeriesInfoSheetView(vm: vm, episode: episode)
            case .comments(let episode):
                CommentsSheetView(vm: vm, episode: episode)
            case .offers(let episode):
                VipCoinOffersView(vm: vm, episode: episode)
            }
        }
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
        if let targetId = vm.jumpToEpisodeId {
            vc.scrollToEpisode(id: targetId)
            DispatchQueue.main.async { vm.jumpToEpisodeId = nil }
        }
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
                vm.recordProgress(episode: episodes[indexPath.item])
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

    /// Jump to an episode picked from the info sheet, without leaving the swipe feed.
    func scrollToEpisode(id: Int) {
        guard let index = episodes.firstIndex(where: { $0.id == id }) else { return }
        collectionView.scrollToItem(at: IndexPath(item: index, section: 0), at: .top, animated: true)
        DispatchQueue.main.asyncAfter(deadline: .now() + 0.3) { [weak self] in self?.updatePlaybackForCurrentPosition() }
    }
}

protocol ReelCellDelegate: AnyObject {
    func reelCell(_ cell: ReelCell, didFinishEpisode episode: Episode)
    func reelCellDidToggleMute(_ cell: ReelCell)
    func reelCell(_ cell: ReelCell, unlockWithCoins episode: Episode)
    func reelCell(_ cell: ReelCell, unlockWithAd episode: Episode)
    func reelCell(_ cell: ReelCell, toggleLike episode: Episode, completion: @escaping (Bool, Int) -> Void)
    func reelCell(_ cell: ReelCell, toggleSave episode: Episode, completion: @escaping (Bool, Int) -> Void)
    func reelCell(_ cell: ReelCell, share episode: Episode, completion: @escaping () -> Void)
    func reelCell(_ cell: ReelCell, showCommentsFor episode: Episode)
    func reelCell(_ cell: ReelCell, showInfoFor episode: Episode)
    func reelCell(_ cell: ReelCell, showOffersFor episode: Episode)
}

extension ReelCollectionViewController: ReelCellDelegate {
    func reelCell(_ cell: ReelCell, didFinishEpisode episode: Episode) {
        vm.recordProgress(episode: episode)
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

    func reelCell(_ cell: ReelCell, toggleLike episode: Episode, completion: @escaping (Bool, Int) -> Void) {
        Task { @MainActor in
            if let result = await vm.toggleLike(episode: episode) { completion(result.liked, result.count) }
        }
    }

    func reelCell(_ cell: ReelCell, toggleSave episode: Episode, completion: @escaping (Bool, Int) -> Void) {
        Task { @MainActor in
            if let result = await vm.toggleSave(episode: episode) { completion(result.saved, result.count) }
        }
    }

    /// Downloads the episode's video and hands it to the OS share sheet so
    /// Instagram/TikTok/Facebook appear as share targets — the same
    /// native-share-with-download pattern as web/Android, since no platform
    /// can share a remote video URL as a "file" via its native share intent.
    func reelCell(_ cell: ReelCell, share episode: Episode, completion: @escaping () -> Void) {
        vm.recordShare(episode: episode)
        guard let urlStr = episode.video_hls_url, let url = URL(string: urlStr), !urlStr.contains(".m3u8") else {
            if let urlStr = episode.video_hls_url, let url = URL(string: urlStr) { UIApplication.shared.open(url) }
            completion()
            return
        }
        Task {
            defer { Task { @MainActor in completion() } }
            do {
                let (tempURL, _) = try await URLSession.shared.download(from: url)
                let destURL = FileManager.default.temporaryDirectory.appendingPathComponent("soloreel-episode-\(episode.id).mp4")
                try? FileManager.default.removeItem(at: destURL)
                try FileManager.default.moveItem(at: tempURL, to: destURL)
                await MainActor.run {
                    let activityVC = UIActivityViewController(activityItems: [destURL], applicationActivities: nil)
                    self.present(activityVC, animated: true)
                }
            } catch {
                // Network failure — the share sheet simply doesn't open; the
                // share-attempt was already recorded above.
            }
        }
    }

    func reelCell(_ cell: ReelCell, showCommentsFor episode: Episode) {
        vm.activeSheet = .comments(episode)
    }

    func reelCell(_ cell: ReelCell, showInfoFor episode: Episode) {
        vm.activeSheet = .info(episode)
    }

    func reelCell(_ cell: ReelCell, showOffersFor episode: Episode) {
        vm.activeSheet = .offers(episode)
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

    // Instagram-style action rail: like, comment, save, share (share hidden
    // past episode 2 — can_share is computed server-side), plus series info.
    private let likeButton = UIButton(type: .system)
    private let likeCountLabel = UILabel()
    private let commentButton = UIButton(type: .system)
    private let commentCountLabel = UILabel()
    private let saveButton = UIButton(type: .system)
    private let saveCountLabel = UILabel()
    private let shareButton = UIButton(type: .system)
    private let shareCountLabel = UILabel()
    private let infoButton = UIButton(type: .system)
    private var isLiked = false
    private var isSaved = false
    private var isSharing = false
    private var shareItemStack: UIStackView?

    private let lockedOverlay = UIView()
    private let lockTitleLabel = UILabel()
    private let lockBodyLabel = UILabel()
    private let unlockCoinsButton = UIButton(type: .system)
    private let unlockAdButton = UIButton(type: .system)
    private let unlockErrorLabel = UILabel()
    private let vipOffersButton = UIButton(type: .system)

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

        let actionRailStack = setUpActionRail()

        setUpLockedOverlay()

        NSLayoutConstraint.activate([
            textStack.leadingAnchor.constraint(equalTo: contentView.leadingAnchor, constant: 16),
            textStack.trailingAnchor.constraint(lessThanOrEqualTo: actionRailStack.leadingAnchor, constant: -12),
            textStack.bottomAnchor.constraint(equalTo: contentView.safeAreaLayoutGuide.bottomAnchor, constant: -24),

            muteButton.topAnchor.constraint(equalTo: contentView.safeAreaLayoutGuide.topAnchor, constant: 12),
            muteButton.trailingAnchor.constraint(equalTo: contentView.trailingAnchor, constant: -16),
            muteButton.widthAnchor.constraint(equalToConstant: 36),
            muteButton.heightAnchor.constraint(equalToConstant: 36),

            actionRailStack.trailingAnchor.constraint(equalTo: contentView.trailingAnchor, constant: -12),
            actionRailStack.bottomAnchor.constraint(equalTo: contentView.safeAreaLayoutGuide.bottomAnchor, constant: -32),

            heartImageView.centerXAnchor.constraint(equalTo: contentView.centerXAnchor),
            heartImageView.centerYAnchor.constraint(equalTo: contentView.centerYAnchor),
            heartImageView.widthAnchor.constraint(equalToConstant: 96),
            heartImageView.heightAnchor.constraint(equalToConstant: 96),
        ])
    }

    private func actionRailItem(button: UIButton, countLabel: UILabel) -> UIStackView {
        button.tintColor = .white
        button.translatesAutoresizingMaskIntoConstraints = false
        countLabel.textColor = .white
        countLabel.font = .boldSystemFont(ofSize: 12)
        countLabel.textAlignment = .center

        let stack = UIStackView(arrangedSubviews: [button, countLabel])
        stack.axis = .vertical
        stack.spacing = 2
        stack.alignment = .center
        NSLayoutConstraint.activate([
            button.widthAnchor.constraint(equalToConstant: 32),
            button.heightAnchor.constraint(equalToConstant: 32),
        ])
        return stack
    }

    private func setUpActionRail() -> UIStackView {
        likeButton.addTarget(self, action: #selector(handleLikeTap), for: .touchUpInside)
        commentButton.setImage(UIImage(systemName: "bubble.right"), for: .normal)
        commentButton.addTarget(self, action: #selector(handleCommentTap), for: .touchUpInside)
        saveButton.addTarget(self, action: #selector(handleSaveTap), for: .touchUpInside)
        shareButton.setImage(UIImage(systemName: "paperplane"), for: .normal)
        shareButton.addTarget(self, action: #selector(handleShareTap), for: .touchUpInside)
        infoButton.setImage(UIImage(systemName: "info.circle"), for: .normal)
        infoButton.tintColor = .white
        infoButton.translatesAutoresizingMaskIntoConstraints = false
        infoButton.addTarget(self, action: #selector(handleInfoTap), for: .touchUpInside)
        NSLayoutConstraint.activate([
            infoButton.widthAnchor.constraint(equalToConstant: 32),
            infoButton.heightAnchor.constraint(equalToConstant: 32),
        ])

        let shareStack = actionRailItem(button: shareButton, countLabel: shareCountLabel)
        shareItemStack = shareStack

        let stack = UIStackView(arrangedSubviews: [
            actionRailItem(button: likeButton, countLabel: likeCountLabel),
            actionRailItem(button: commentButton, countLabel: commentCountLabel),
            actionRailItem(button: saveButton, countLabel: saveCountLabel),
            shareStack,
            infoButton,
        ])
        stack.axis = .vertical
        stack.spacing = 20
        stack.alignment = .center
        stack.translatesAutoresizingMaskIntoConstraints = false
        contentView.addSubview(stack)
        return stack
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

        // VIP is an alternative to buying coins, not a replacement — this
        // opens the same VIP-plans-plus-coins sheet whether the viewer just
        // wants to browse it, or landed here after an "insufficient coins" unlock failure.
        vipOffersButton.setTitle("View VIP & Coin Offers", for: .normal)
        vipOffersButton.setTitleColor(.systemYellow, for: .normal)
        vipOffersButton.titleLabel?.font = .boldSystemFont(ofSize: 14)
        vipOffersButton.addTarget(self, action: #selector(handleShowOffers), for: .touchUpInside)

        let stack = UIStackView(arrangedSubviews: [lockTitleLabel, lockBodyLabel, unlockCoinsButton, unlockAdButton, vipOffersButton, unlockErrorLabel])
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

        isLiked = false
        isSaved = false
        isSharing = false
        updateLikeIcon()
        updateSaveIcon()
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

        isLiked = episode.is_liked_by_viewer == true
        isSaved = episode.is_saved_by_viewer == true
        updateLikeIcon()
        updateSaveIcon()
        likeCountLabel.text = ReelCell.formatCount(episode.like_count ?? 0)
        commentCountLabel.text = ReelCell.formatCount(episode.comment_count ?? 0)
        saveCountLabel.text = ReelCell.formatCount(episode.save_count ?? 0)
        shareCountLabel.text = ReelCell.formatCount(episode.share_count ?? 0)
        shareItemStack?.isHidden = episode.can_share != true

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
            if error.localizedCaseInsensitiveContains("insufficient"), let episode {
                delegate?.reelCell(self, showOffersFor: episode)
            }
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

    @objc private func handleShowOffers() {
        guard let episode else { return }
        delegate?.reelCell(self, showOffersFor: episode)
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
        // Double-tap-to-like is idempotent (Instagram convention) — always
        // shows the heart burst, but only actually likes once, never unlikes.
        if !isLiked, let episode { doLike(episode) }
    }

    private func updateLikeIcon() {
        likeButton.setImage(UIImage(systemName: isLiked ? "heart.fill" : "heart"), for: .normal)
        likeButton.tintColor = isLiked ? .systemRed : .white
    }

    private func updateSaveIcon() {
        saveButton.setImage(UIImage(systemName: isSaved ? "bookmark.fill" : "bookmark"), for: .normal)
        saveButton.tintColor = isSaved ? .systemYellow : .white
    }

    private func doLike(_ episode: Episode) {
        let wasLiked = isLiked
        isLiked = !wasLiked
        updateLikeIcon()
        delegate?.reelCell(self, toggleLike: episode) { [weak self] liked, count in
            guard let self else { return }
            self.isLiked = liked
            self.updateLikeIcon()
            self.likeCountLabel.text = ReelCell.formatCount(count)
        }
    }

    @objc private func handleLikeTap() {
        guard let episode else { return }
        doLike(episode)
    }

    @objc private func handleSaveTap() {
        guard let episode else { return }
        let wasSaved = isSaved
        isSaved = !wasSaved
        updateSaveIcon()
        delegate?.reelCell(self, toggleSave: episode) { [weak self] saved, count in
            guard let self else { return }
            self.isSaved = saved
            self.updateSaveIcon()
            self.saveCountLabel.text = ReelCell.formatCount(count)
        }
    }

    @objc private func handleShareTap() {
        guard let episode, !isSharing else { return }
        isSharing = true
        let currentCount = Int(shareCountLabel.text ?? "") ?? 0
        shareCountLabel.text = ReelCell.formatCount(currentCount + 1)
        delegate?.reelCell(self, share: episode) { [weak self] in
            self?.isSharing = false
        }
    }

    @objc private func handleCommentTap() {
        guard let episode else { return }
        delegate?.reelCell(self, showCommentsFor: episode)
    }

    @objc private func handleInfoTap() {
        guard let episode else { return }
        delegate?.reelCell(self, showInfoFor: episode)
    }

    private static func formatCount(_ n: Int) -> String {
        if n < 1000 { return "\(n)" }
        if n < 1_000_000 {
            let value = Double(n) / 1000.0
            return String(format: value.truncatingRemainder(dividingBy: 1) == 0 ? "%.0fK" : "%.1fK", value)
        }
        let value = Double(n) / 1_000_000.0
        return String(format: value.truncatingRemainder(dividingBy: 1) == 0 ? "%.0fM" : "%.1fM", value)
    }
}

/// Series synopsis + full episode list, lazy-loaded only when opened (most
/// swipes never open it). Tapping an episode row scrolls the existing feed
/// to it via vm.jumpToEpisodeId, rather than navigating away — the vertical
/// swipe mechanic must stay intact.
struct SeriesInfoSheetView: View {
    @ObservedObject var vm: ReelFeedViewModel
    let episode: Episode
    @Environment(\.dismiss) private var dismiss
    @State private var series: Series?
    @State private var loaded = false

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(alignment: .leading, spacing: 12) {
                    if !loaded {
                        ProgressView().tint(.red).frame(maxWidth: .infinity).padding(.top, 40)
                    } else {
                        if let synopsis = series?.synopsis, !synopsis.isEmpty {
                            Text(synopsis).font(.notoSans(size: 14, relativeTo: .subheadline)).foregroundColor(Color(white: 0.8))
                        }
                        Text("Episodes (\(vm.episodes.count))").font(.notoSans(size: 13, weight: .semibold, relativeTo: .subheadline)).foregroundColor(Color(white: 0.6))
                        ForEach(vm.episodes) { ep in
                            Button {
                                vm.jumpToEpisodeId = ep.id
                                vm.activeSheet = nil
                            } label: {
                                HStack(spacing: 10) {
                                    AsyncImage(url: URL(string: ep.thumbnail_url ?? "")) { phase in
                                        if let image = phase.image { image.resizable().aspectRatio(contentMode: .fill) }
                                        else { Color(white: 0.15) }
                                    }.frame(width: 64, height: 40).cornerRadius(4).clipped()
                                    Text("EP \(ep.episode_number ?? 0) · \(ep.title)").font(.notoSans(size: 13, relativeTo: .footnote)).foregroundColor(.white).lineLimit(1)
                                    Spacer()
                                    if ep.is_unlocked != true {
                                        Image(systemName: "lock.fill").foregroundColor(Color(white: 0.5)).font(.system(size: 12))
                                    }
                                }
                            }
                        }
                    }
                }
                .padding()
            }
            .background(Color(red: 0.08, green: 0.08, blue: 0.08))
            .preferredColorScheme(.dark)
            .navigationTitle("Series Info")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button("Done") { dismiss() }
                }
            }
        }
        .task {
            if let slug = episode.series_slug {
                series = await vm.loadSeriesInfo(seriesSlug: slug)
            }
            loaded = true
        }
    }
}

/// Flat comment list (no nested replies, no comment-likes — matches the
/// web/Android scope) with a text field + send button pinned to the bottom.
struct CommentsSheetView: View {
    @ObservedObject var vm: ReelFeedViewModel
    let episode: Episode
    @Environment(\.dismiss) private var dismiss
    @State private var comments: [EpisodeComment] = []
    @State private var total = 0
    @State private var loaded = false
    @State private var input = ""
    @State private var posting = false

    var body: some View {
        NavigationStack {
            VStack(spacing: 0) {
                if !loaded {
                    ProgressView().tint(.red).frame(maxWidth: .infinity).padding(.top, 40)
                    Spacer()
                } else if comments.isEmpty {
                    Spacer()
                    Text("No comments yet. Be the first!").foregroundColor(Color(white: 0.5))
                    Spacer()
                } else {
                    ScrollView {
                        VStack(alignment: .leading, spacing: 14) {
                            ForEach(comments) { c in
                                VStack(alignment: .leading, spacing: 2) {
                                    HStack(alignment: .top, spacing: 6) {
                                        Text(c.author ?? "Guest").font(.notoSans(size: 13, weight: .semibold, relativeTo: .footnote)).foregroundColor(.white)
                                        Text(c.body).font(.notoSans(size: 13, relativeTo: .footnote)).foregroundColor(Color(white: 0.85))
                                    }
                                }
                            }
                        }.padding()
                    }
                }

                HStack(spacing: 8) {
                    TextField("Add a comment...", text: $input)
                        .foregroundColor(.white)
                        .padding(10)
                        .background(Color(white: 0.1))
                        .cornerRadius(20)
                    Button {
                        postComment()
                    } label: {
                        Image(systemName: "paperplane.fill").foregroundColor(input.isEmpty || posting ? Color(white: 0.4) : .red)
                    }.disabled(input.isEmpty || posting)
                }.padding()
            }
            .background(Color(red: 0.08, green: 0.08, blue: 0.08))
            .preferredColorScheme(.dark)
            .navigationTitle("Comments (\(total))")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button("Done") { dismiss() }
                }
            }
        }
        .task {
            if let page = await vm.loadComments(episode: episode) {
                comments = page.items
                total = page.total
            }
            loaded = true
        }
    }

    private func postComment() {
        let body = input.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !body.isEmpty else { return }
        posting = true
        Task {
            if let comment = await vm.postComment(episode: episode, body: body) {
                comments.insert(comment, at: 0)
                total += 1
                input = ""
            }
            posting = false
        }
    }
}

/// VIP subscription — an alternative to buying coins, not a replacement.
/// Shown when a coin unlock fails for insufficient balance, or when the
/// viewer taps "View VIP & Coin Offers" from the lock screen. VIP plan
/// cards reuse PaymentAuthSession, the same Safari-based checkout already
/// proven for coin purchases; on success this retries the normal coin-unlock
/// call for the episode, which now succeeds for free via the server's VIP gating.
struct VipCoinOffersView: View {
    @ObservedObject var vm: ReelFeedViewModel
    let episode: Episode
    @Environment(\.dismiss) private var dismiss
    @State private var paymentUrl: String? = nil
    @State private var pendingReference: String? = nil
    @State private var error: String? = nil
    @State private var isProcessing = false
    @State private var showCoinShop = false

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(alignment: .leading, spacing: 12) {
                    Text("VIP Unlock all series for free").font(.notoSans(size: 18, weight: .bold, relativeTo: .headline)).foregroundColor(.white)
                    Text("Auto renew. Cancel anytime.").font(.notoSans(size: 12, relativeTo: .caption)).foregroundColor(Color(white: 0.55))

                    if vm.vipPlans.isEmpty {
                        ProgressView().tint(.red).frame(maxWidth: .infinity).padding(.top, 24)
                    } else {
                        ForEach(vm.vipPlans) { plan in
                            Button {
                                purchase(plan: plan)
                            } label: {
                                VStack(alignment: .leading, spacing: 4) {
                                    Text(plan.name).font(.notoSans(size: 15, weight: .bold, relativeTo: .headline)).foregroundColor(.black)
                                    Text("\(plan.currency) \(String(format: "%.2f", plan.price))").font(.notoSans(size: 22, weight: .heavy, relativeTo: .title2)).foregroundColor(.black)
                                    Text("Auto-renew. Cancel anytime.").font(.notoSans(size: 11, relativeTo: .caption2)).foregroundColor(Color.black.opacity(0.6))
                                }
                                .padding(16)
                                .frame(maxWidth: .infinity, alignment: .leading)
                                .background(LinearGradient(colors: [Color(red: 0.99, green: 0.9, blue: 0.54), Color(red: 0.96, green: 0.62, blue: 0.04)], startPoint: .topLeading, endPoint: .bottomTrailing))
                                .cornerRadius(14)
                            }
                            .disabled(isProcessing)
                        }
                    }

                    if let error { Text(error).font(.notoSans(size: 12, relativeTo: .caption)).foregroundColor(.red) }
                    if pendingReference != nil {
                        Text("Waiting for you to complete payment in your browser...").font(.notoSans(size: 12, relativeTo: .caption)).foregroundColor(Color(white: 0.55)).frame(maxWidth: .infinity, alignment: .center)
                    }

                    Button {
                        showCoinShop = true
                    } label: {
                        Text("Top Up Coins Instead").font(.notoSans(size: 15, weight: .bold, relativeTo: .headline)).foregroundColor(.white)
                            .frame(maxWidth: .infinity).padding(14)
                            .background(Color(red: 0.86, green: 0.15, blue: 0.15)).cornerRadius(12)
                    }
                    .padding(.top, 8)
                }
                .padding()
            }
            .background(Color(red: 0.08, green: 0.08, blue: 0.08))
            .preferredColorScheme(.dark)
            .navigationTitle("Unlock Episode \(episode.episode_number ?? 0)")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button("Done") { dismiss() }
                }
            }
        }
        .task { vm.loadVipPlans() }
        .sheet(item: Binding(get: { paymentUrl.map { PaymentURL(url: $0) } }, set: { if $0 == nil { paymentUrl = nil } })) { item in
            PaymentWebView(url: item.url, onSuccess: { ref in
                paymentUrl = nil
                let reference = pendingReference ?? ref
                Task {
                    isProcessing = true
                    let unlockError = await vm.verifyVipAndRetryUnlock(reference: reference, episode: episode)
                    isProcessing = false
                    if unlockError == nil { dismiss() } else { error = unlockError }
                }
            }, onDismiss: { paymentUrl = nil })
        }
        .sheet(isPresented: $showCoinShop) {
            CoinShopView()
        }
    }

    private func purchase(plan: VipPlan) {
        error = nil
        isProcessing = true
        Task {
            let result = await vm.purchaseVip(planId: plan.id)
            isProcessing = false
            if let url = result.authUrl {
                pendingReference = result.reference
                paymentUrl = url
            } else {
                error = result.error ?? "Could not initiate payment"
            }
        }
    }
}
}
