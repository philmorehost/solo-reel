import Foundation
import UIKit
import GoogleMobileAds

/// Loads and shows a Google AdMob rewarded ad to unlock a locked episode without
/// spending coins. Uses Google's public TEST ad unit ID by default — replace
/// adUnitID with your real AdMob rewarded ad unit ID before release (see
/// ADMOB_SETUP.md at the project root).
final class RewardedAdManager: NSObject {
    static let shared = RewardedAdManager()

    private let adUnitID = "ca-app-pub-3940256099942544/1712485313" // Google TEST rewarded ad unit
    private var rewardedAd: GADRewardedAd?
    private var isLoading = false
    private var onRewarded: (() -> Void)?
    private var onFailed: ((String) -> Void)?

    private override init() {
        super.init()
        preload()
    }

    func preload() {
        guard rewardedAd == nil, !isLoading else { return }
        isLoading = true
        GADRewardedAd.load(withAdUnitID: adUnitID, request: GADRequest()) { [weak self] ad, error in
            self?.isLoading = false
            guard error == nil else {
                self?.rewardedAd = nil
                return
            }
            self?.rewardedAd = ad
            self?.rewardedAd?.fullScreenContentDelegate = self
        }
    }

    /// Shows the rewarded ad if one is ready. `onRewarded` fires only after the
    /// user watches the ad to completion and earns the reward — callers must not
    /// unlock content before this fires. `onFailed` fires if no ad is ready or
    /// presentation fails, with a reason message for the UI.
    func showAd(onRewarded: @escaping () -> Void, onFailed: @escaping (String) -> Void) {
        guard let ad = rewardedAd, let root = Self.topViewController() else {
            onFailed("Ad not ready yet — please try again in a moment.")
            preload()
            return
        }
        self.onRewarded = onRewarded
        self.onFailed = onFailed
        ad.present(fromRootViewController: root) { [weak self] in
            // This only fires once the user actually earns the reward (watches to
            // completion); dismissing early simply never calls it — no free unlock.
            self?.onRewarded?()
        }
    }

    private static func topViewController() -> UIViewController? {
        guard let scene = UIApplication.shared.connectedScenes.first(where: { $0.activationState == .foregroundActive }) as? UIWindowScene,
              let root = scene.windows.first(where: { $0.isKeyWindow })?.rootViewController else {
            return nil
        }
        var top = root
        while let presented = top.presentedViewController { top = presented }
        return top
    }
}

extension RewardedAdManager: GADFullScreenContentDelegate {
    func ad(_ ad: GADFullScreenPresentingAd, didFailToPresentFullScreenContentWithError error: Error) {
        onFailed?(error.localizedDescription)
        rewardedAd = nil
        preload()
    }

    func adDidDismissFullScreenContent(_ ad: GADFullScreenPresentingAd) {
        rewardedAd = nil
        preload()
    }
}
