package com.soloreel.app.ads

/**
 * Tracks episode navigations app-wide so the player can show an admin-uploaded
 * interstitial ad every few episodes instead of on every single one.
 */
object InterstitialAdGate {
    private const val EVERY_N_EPISODES = 3
    private var count = 0

    /** Call once per new episode load. Returns true when an interstitial should be shown. */
    fun shouldShowForNewEpisode(): Boolean {
        count++
        return count % EVERY_N_EPISODES == 0
    }
}
