package com.soloreel.app.ui.home

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.soloreel.app.ads.RewardedAdManager
import com.soloreel.app.data.api.SOLOREELApi
import com.soloreel.app.data.model.Banner
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
    val isLoading: Boolean = false, val error: String? = null
)

@HiltViewModel
class HomeViewModel @Inject constructor(
    private val api: SOLOREELApi,
    private val tokenManager: TokenManager
) : ViewModel() {
    private val _state = MutableStateFlow(HomeState())
    val state: StateFlow<HomeState> = _state.asStateFlow()

    fun load() {
        viewModelScope.launch {
            _state.value = _state.value.copy(isLoading = true)
            try {
                val bannersRes = api.getBanners()
                val seriesRes = api.getSeries()
                val shelves = try { api.getShelves().data ?: emptyList() } catch (e: Exception) { emptyList() }
                _state.value = HomeState(
                    banners = bannersRes.data ?: emptyList(),
                    series = seriesRes.data ?: emptyList(),
                    shelves = shelves
                )
                shelves.firstOrNull()?.let { loadShelfSeries(it.slug) }

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
                _state.value = _state.value.copy(
                    shelfSeries = _state.value.shelfSeries + (slug to (res.data ?: emptyList()))
                )
            } catch (e: Exception) {
                // Leave the tab empty on failure; user can switch tabs to retry.
            }
        }
    }
}
