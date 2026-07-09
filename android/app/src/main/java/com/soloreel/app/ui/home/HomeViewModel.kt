package com.soloreel.app.ui.home

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.soloreel.app.ads.RewardedAdManager
import com.soloreel.app.data.api.SOLOREELApi
import com.soloreel.app.data.model.Banner
import com.soloreel.app.data.model.ContinueWatchingItem
import com.soloreel.app.data.model.Series
import com.soloreel.app.data.model.Shelf
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject
import com.soloreel.app.data.api.TokenManager

data class HomeState(
    val banners: List<Banner> = emptyList(),
    val series: List<Series> = emptyList(),
    val shelves: List<Shelf> = emptyList(),
    val shelfSeries: Map<String, List<Series>> = emptyMap(),
    val continueWatching: List<ContinueWatchingItem> = emptyList(),
    val resumeSlugs: Map<Int, String> = emptyMap(),
    val isLoading: Boolean = false, val error: String? = null
)

@HiltViewModel
class HomeViewModel @Inject constructor(
    private val api: SOLOREELApi,
    private val tokenManager: TokenManager
) : ViewModel() {
    private val _state = MutableStateFlow(HomeState())
    val state: StateFlow<HomeState> = _state.asStateFlow()

    private val guestIdOrNull: String? get() = if (tokenManager.isLoggedIn) null else tokenManager.guestId

    fun load() {
        viewModelScope.launch {
            _state.value = _state.value.copy(isLoading = true)
            try {
                val bannersRes = api.getBanners()
                val seriesRes = api.getSeries()
                val shelves = try { api.getShelves().data ?: emptyList() } catch (e: Exception) { emptyList() }
                val continueWatching = try { api.getContinueWatching(guestIdOrNull).data ?: emptyList() } catch (e: Exception) { emptyList() }
                _state.value = HomeState(
                    banners = bannersRes.data ?: emptyList(),
                    series = seriesRes.data ?: emptyList(),
                    shelves = shelves,
                    continueWatching = continueWatching
                )
                shelves.forEach { loadShelfSeries(it.slug) }
                fetchResumeSlugs((seriesRes.data ?: emptyList()).map { it.id })

                if (tokenManager.isLoggedIn && !tokenManager.installBonusClaimed) {
                    try {
                        val res = api.claimInstallBonus()
                        // Regardless of whether we actually got coins or it was already claimed on another device, mark locally
                        if (res.status == true) {
                            tokenManager.installBonusClaimed = true
                        }
                    } catch (e: Exception) {
                        // ignore error, will try again next time
                    }
                }

                try {
                    val adsConfig = api.getAdsConfig().data
                    adsConfig?.get("admob_android_rewarded_unit_id")?.let { RewardedAdManager.adUnitId = it }
                } catch (e: Exception) {
                    // Keep the built-in test ad unit ID on failure.
                }
            } catch (e: Exception) {
                _state.value = _state.value.copy(isLoading = false, error = e.message)
            }
        }
    }

    fun loadShelfSeries(slug: String) {
        if (_state.value.shelfSeries.containsKey(slug)) return
        viewModelScope.launch {
            try {
                val res = api.getSeries(shelf = slug)
                val seriesList = res.data ?: emptyList()
                _state.value = _state.value.copy(
                    shelfSeries = _state.value.shelfSeries + (slug to seriesList)
                )
                fetchResumeSlugs(seriesList.map { it.id })
            } catch (e: Exception) {
                // Leave the tab empty on failure; user can switch tabs to retry.
            }
        }
    }

    /** Banner clicks only carry a slug, not a series id, so this resolves the
     * resume episode for a single series (acceptable per-tap cost, unlike
     * the batch lookup used for card lists). Falls back to null on failure
     * so the caller can fall back to the series-detail screen. */
    suspend fun resolveBannerTarget(slug: String): String? {
        return try {
            val series = api.getSeriesDetail(slug).data ?: return null
            api.getResumeEpisode(series.id, guestIdOrNull).data?.slug
        } catch (e: Exception) {
            null
        }
    }

    /** Skip-to-player: resolve each card's resume episode (or episode 1) in
     * one batch call, so tapping a card jumps straight into the reel feed. */
    private fun fetchResumeSlugs(ids: List<Int>) {
        val missing = ids.filter { !_state.value.resumeSlugs.containsKey(it) }
        if (missing.isEmpty()) return
        viewModelScope.launch {
            try {
                val res = api.getResumeBatch(missing.joinToString(","), guestIdOrNull)
                val fetched = (res.data ?: emptyMap()).mapKeys { it.key.toIntOrNull() ?: -1 }.filterKeys { it >= 0 }
                _state.value = _state.value.copy(resumeSlugs = _state.value.resumeSlugs + fetched)
            } catch (e: Exception) {
                // Cards fall back to their series slug (SeriesDetail) on failure.
            }
        }
    }
}
