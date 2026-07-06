package com.soloreel.app.ads

import android.app.Activity
import android.content.Context
import com.google.android.gms.ads.AdError
import com.google.android.gms.ads.AdRequest
import com.google.android.gms.ads.FullScreenContentCallback
import com.google.android.gms.ads.LoadAdError
import com.google.android.gms.ads.rewarded.RewardedAd
import com.google.android.gms.ads.rewarded.RewardedAdLoadCallback

/**
 * Loads and shows a Google AdMob rewarded ad to unlock a locked episode without
 * spending coins. Uses Google's public TEST ad unit ID by default — replace
 * AD_UNIT_ID with your real AdMob rewarded ad unit ID before release
 * (see ADMOB_SETUP.md at the project root).
 */
object RewardedAdManager {
    private const val DEFAULT_AD_UNIT_ID = "ca-app-pub-3940256099942544/5224354917" // Google TEST rewarded ad unit

    /** Overridden once at startup from /api/v1/ads-config (see HomeViewModel.load()). */
    var adUnitId: String = DEFAULT_AD_UNIT_ID

    private var rewardedAd: RewardedAd? = null
    private var isLoading = false

    fun preload(context: Context) {
        if (rewardedAd != null || isLoading) return
        isLoading = true
        RewardedAd.load(context, adUnitId, AdRequest.Builder().build(), object : RewardedAdLoadCallback() {
            override fun onAdLoaded(ad: RewardedAd) {
                rewardedAd = ad
                isLoading = false
            }

            override fun onAdFailedToLoad(error: LoadAdError) {
                rewardedAd = null
                isLoading = false
            }
        })
    }

    /**
     * Shows the rewarded ad if one is ready. [onRewarded] fires only after the
     * user watches the ad to completion and earns the reward — callers must not
     * unlock content before this fires. [onFailed] fires if no ad is ready or
     * the ad fails to display, with a reason message for the UI.
     */
    fun showAd(activity: Activity, onRewarded: () -> Unit, onFailed: (String) -> Unit) {
        val ad = rewardedAd
        if (ad == null) {
            onFailed("Ad not ready yet — please try again in a moment.")
            preload(activity)
            return
        }

        ad.fullScreenContentCallback = object : FullScreenContentCallback() {
            override fun onAdDismissedFullScreenContent() {
                rewardedAd = null
                preload(activity)
            }

            override fun onAdFailedToShowFullScreenContent(error: AdError) {
                rewardedAd = null
                preload(activity)
                onFailed(error.message)
            }
        }

        // This callback only fires once the user actually earns the reward (watches
        // to completion); dismissing early simply never calls it — no free unlock.
        ad.show(activity) { onRewarded() }
    }
}
