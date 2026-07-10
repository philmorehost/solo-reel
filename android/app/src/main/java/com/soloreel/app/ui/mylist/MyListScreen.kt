package com.soloreel.app.ui.mylist

import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Close
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.navigation.NavHostController
import coil.compose.rememberAsyncImagePainter
import com.soloreel.app.data.api.SOLOREELApi
import com.soloreel.app.data.api.TokenManager
import com.soloreel.app.data.model.ContinueWatchingItem
import com.soloreel.app.data.model.Series
import com.soloreel.app.ui.navigation.Screen
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class MyListState(
    val history: List<ContinueWatchingItem> = emptyList(),
    val liked: List<Series> = emptyList(),
    val saved: List<Series> = emptyList(),
    val isLoading: Boolean = false
)

@HiltViewModel
class MyListViewModel @Inject constructor(
    private val api: SOLOREELApi,
    private val tokenManager: TokenManager
) : ViewModel() {
    private val _state = MutableStateFlow(MyListState())
    val state: StateFlow<MyListState> = _state.asStateFlow()

    private val guestIdOrNull: String? get() = if (tokenManager.isLoggedIn) null else tokenManager.guestId

    fun load() {
        viewModelScope.launch {
            _state.value = _state.value.copy(isLoading = true)
            try {
                val data = api.getMyList(guestIdOrNull).data
                _state.value = _state.value.copy(
                    history = data?.history ?: emptyList(),
                    liked = data?.liked ?: emptyList(),
                    saved = data?.saved ?: emptyList(),
                    isLoading = false
                )
            } catch (e: Exception) {
                _state.value = _state.value.copy(isLoading = false)
            }
        }
    }

    fun removeSaved(seriesId: Int) {
        _state.value = _state.value.copy(saved = _state.value.saved.filter { it.id != seriesId })
        viewModelScope.launch {
            try { api.removeSavedSeries(seriesId, guestIdOrNull) } catch (_: Exception) { }
        }
    }
}

@Composable
fun MyListScreen(navController: NavHostController, vm: MyListViewModel = hiltViewModel()) {
    val state by vm.state.collectAsState()
    var tab by remember { mutableStateOf(0) }
    val tabs = listOf("History", "Liked", "Saved")

    LaunchedEffect(Unit) { vm.load() }

    Column(modifier = Modifier.fillMaxSize().background(Color(0xFF0A0A0A))) {
        Text("My List", color = Color.White, fontSize = 22.sp, fontWeight = FontWeight.Bold, modifier = Modifier.padding(16.dp))

        TabRow(
            selectedTabIndex = tab,
            containerColor = Color(0xFF0A0A0A),
            contentColor = Color(0xFFDC2626)
        ) {
            tabs.forEachIndexed { index, label ->
                Tab(selected = tab == index, onClick = { tab = index }, text = { Text(label) })
            }
        }

        if (state.isLoading) {
            Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                CircularProgressIndicator(color = Color(0xFFDC2626))
            }
        } else {
            when (tab) {
                0 -> HistoryGrid(state.history) { item ->
                    navController.navigate(Screen.EpisodePlayer.createRoute(item.episode_slug))
                }
                1 -> LikedGrid(state.liked) { series ->
                    navController.navigate(Screen.SeriesDetail.createRoute(series.slug))
                }
                2 -> SavedGrid(state.saved, onClick = { series ->
                    navController.navigate(Screen.SeriesDetail.createRoute(series.slug))
                }, onRemove = { series -> vm.removeSaved(series.id) })
            }
        }
    }
}

@Composable
private fun HistoryGrid(items: List<ContinueWatchingItem>, onClick: (ContinueWatchingItem) -> Unit) {
    if (items.isEmpty()) { EmptyState("You haven't watched anything yet."); return }
    LazyColumn(modifier = Modifier.fillMaxSize().padding(horizontal = 8.dp)) {
        items(items.chunked(3)) { row ->
            Row(modifier = Modifier.fillMaxWidth().padding(horizontal = 4.dp), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                row.forEach { item ->
                    Box(modifier = Modifier.weight(1f)) {
                        MyListCard(
                            title = item.title,
                            coverUrl = item.cover_image_url,
                            badge = "EP.${item.episode_number ?: 1}/${item.episode_count ?: 1}",
                            onClick = { onClick(item) }
                        )
                    }
                }
                repeat(3 - row.size) { Spacer(Modifier.weight(1f)) }
            }
            Spacer(Modifier.height(12.dp))
        }
    }
}

@Composable
private fun LikedGrid(items: List<Series>, onClick: (Series) -> Unit) {
    if (items.isEmpty()) { EmptyState("You haven't liked any series yet."); return }
    LazyColumn(modifier = Modifier.fillMaxSize().padding(horizontal = 8.dp)) {
        items(items.chunked(3)) { row ->
            Row(modifier = Modifier.fillMaxWidth().padding(horizontal = 4.dp), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                row.forEach { series ->
                    Box(modifier = Modifier.weight(1f)) {
                        MyListCard(
                            title = series.title,
                            coverUrl = series.cover_image_url,
                            badge = "EP.${series.episode_count ?: 0}",
                            onClick = { onClick(series) }
                        )
                    }
                }
                repeat(3 - row.size) { Spacer(Modifier.weight(1f)) }
            }
            Spacer(Modifier.height(12.dp))
        }
    }
}

@Composable
private fun SavedGrid(items: List<Series>, onClick: (Series) -> Unit, onRemove: (Series) -> Unit) {
    if (items.isEmpty()) { EmptyState("Save series while watching to see them here."); return }
    LazyColumn(modifier = Modifier.fillMaxSize().padding(horizontal = 8.dp)) {
        items(items.chunked(3)) { row ->
            Row(modifier = Modifier.fillMaxWidth().padding(horizontal = 4.dp), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                row.forEach { series ->
                    Box(modifier = Modifier.weight(1f)) {
                        MyListCard(
                            title = series.title,
                            coverUrl = series.cover_image_url,
                            badge = "EP.${series.episode_count ?: 0}",
                            onClick = { onClick(series) },
                            onRemove = { onRemove(series) }
                        )
                    }
                }
                repeat(3 - row.size) { Spacer(Modifier.weight(1f)) }
            }
            Spacer(Modifier.height(12.dp))
        }
    }
}

@Composable
private fun MyListCard(title: String, coverUrl: String?, badge: String, onClick: () -> Unit, onRemove: (() -> Unit)? = null) {
    Column(modifier = Modifier.fillMaxWidth().clickable(onClick = onClick)) {
        Box(modifier = Modifier.fillMaxWidth().height(170.dp).clip(RoundedCornerShape(12.dp)).background(Color(0xFF1A1A1A))) {
            if (coverUrl != null) {
                Image(painter = rememberAsyncImagePainter(coverUrl), contentDescription = null, modifier = Modifier.fillMaxSize(), contentScale = ContentScale.Crop)
            }
            Box(modifier = Modifier.padding(6.dp).align(Alignment.TopStart).background(Color(0x99000000), RoundedCornerShape(4.dp)).padding(horizontal = 6.dp, vertical = 2.dp)) {
                Text(badge, color = Color.White, fontSize = 10.sp, fontWeight = FontWeight.Bold)
            }
            if (onRemove != null) {
                IconButton(
                    onClick = onRemove,
                    modifier = Modifier.align(Alignment.TopEnd).padding(4.dp).background(Color(0xB3000000), androidx.compose.foundation.shape.CircleShape).size(28.dp)
                ) {
                    Icon(Icons.Default.Close, contentDescription = "Remove from My List", tint = Color.White, modifier = Modifier.size(16.dp))
                }
            }
        }
        Spacer(Modifier.height(6.dp))
        Text(title, color = Color.White, fontSize = 13.sp, maxLines = 1, fontWeight = FontWeight.SemiBold)
    }
}

@Composable
private fun EmptyState(message: String) {
    Box(Modifier.fillMaxSize().padding(32.dp), contentAlignment = Alignment.Center) {
        Text(message, color = Color(0xFF666666), textAlign = androidx.compose.ui.text.style.TextAlign.Center)
    }
}
