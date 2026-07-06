import Foundation

/// Tracks episode navigations app-wide so the player can show an admin-uploaded
/// interstitial ad every few episodes instead of on every single one.
enum InterstitialAdGate {
    private static let everyNEpisodes = 3
    private static var count = 0

    /// Call once per new episode load. Returns true when an interstitial should be shown.
    static func shouldShowForNewEpisode() -> Bool {
        count += 1
        return count % everyNEpisodes == 0
    }
}
