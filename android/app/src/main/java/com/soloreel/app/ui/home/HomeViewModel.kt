package com.soloreel.app.ui.home

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.soloreel.app.data.api.SOLOREELApi
import com.soloreel.app.data.model.Banner
import com.soloreel.app.data.model.Series
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class HomeState(val banners: List<Banner> = emptyList(), val series: List<Series> = emptyList(),
    val isLoading: Boolean = false, val error: String? = null)

@HiltViewModel
class HomeViewModel @Inject constructor(private val api: SOLOREELApi) : ViewModel() {
    private val _state = MutableStateFlow(HomeState())
    val state: StateFlow<HomeState> = _state.asStateFlow()

    fun load() {
        viewModelScope.launch {
            _state.value = _state.value.copy(isLoading = true)
            try {
                val bannersRes = api.getBanners()
                val seriesRes = api.getSeries()
                _state.value = HomeState(
                    banners = bannersRes.data ?: emptyList(),
                    series = seriesRes.data ?: emptyList()
                )
            } catch (e: Exception) {
                _state.value = _state.value.copy(isLoading = false, error = e.message)
            }
        }
    }
}
