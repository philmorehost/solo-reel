package com.soloreel.app.ui.series

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.GridItemSpan
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.navigation.NavHostController
import coil.compose.rememberAsyncImagePainter
import com.soloreel.app.data.api.SOLOREELApi
import com.soloreel.app.data.api.ApiResponse
import com.soloreel.app.data.api.TokenManager
import com.soloreel.app.data.model.Episode
import com.soloreel.app.data.model.Series
import com.soloreel.app.ui.navigation.Screen
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class SeriesDetailState(
    val series: Series? = null, val episodes: List<Episode> = emptyList(),
    val resumeSlug: String? = null, val hasHistory: Boolean = false,
    val isLoading: Boolean = false, val error: String? = null
)

@HiltViewModel
class SeriesDetailViewModel @Inject constructor(
    private val api: SOLOREELApi,
    private val tokenManager: TokenManager
) : ViewModel() {
    private val _state = MutableStateFlow(SeriesDetailState())
    val state: StateFlow<SeriesDetailState> = _state.asStateFlow()

    private val guestIdOrNull: String? get() = if (tokenManager.isLoggedIn) null else tokenManager.guestId

    fun load(slug: String) {
        viewModelScope.launch {
            _state.value = _state.value.copy(isLoading = true)
            try {
                val seriesResp = api.getSeriesDetail(slug)
                val seriesData = seriesResp.data
                val episodesList = if (seriesData?.id != null) {
                    api.getEpisodes(seriesData.id).data ?: emptyList()
                } else emptyList()

                var resumeSlug: String? = episodesList.firstOrNull()?.slug
                var hasHistory = false
                if (seriesData?.id != null) {
                    try {
                        val resume = api.getResumeEpisode(seriesData.id, guestIdOrNull).data
                        if (resume != null) {
                            resumeSlug = resume.slug
                            hasHistory = resume.is_first_watch == false
                        }
                    } catch (_: Exception) { /* fall back to episode 1 */ }
                }

                _state.value = SeriesDetailState(series = seriesData, episodes = episodesList, resumeSlug = resumeSlug, hasHistory = hasHistory)
            } catch (e: Exception) { _state.value = SeriesDetailState(error = e.message) }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SeriesDetailScreen(slug: String, navController: NavHostController, vm: SeriesDetailViewModel = hiltViewModel()) {
    val state by vm.state.collectAsState()
    LaunchedEffect(slug) { vm.load(slug) }

    Column(modifier = Modifier.fillMaxSize().background(Color(0xFF0A0A0A))) {
        TopAppBar(
            title = { Text(state.series?.title ?: "Loading...", color = Color.White, fontWeight = FontWeight.Bold) },
            navigationIcon = { IconButton(onClick = { navController.popBackStack() }) { Icon(Icons.Default.ArrowBack, null, tint = Color.White) } },
            colors = TopAppBarDefaults.topAppBarColors(containerColor = Color(0xFF0A0A0A))
        )

        if (state.isLoading) { Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) { CircularProgressIndicator(color = Color(0xFFDC2626)) } }
        else if (state.error != null) { Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) { Text(state.error!!, color = Color.Red) } }
        else if (state.series != null) {
                LazyVerticalGrid(
                    columns = GridCells.Adaptive(minSize = 100.dp),
                    modifier = Modifier.fillMaxSize(),
                    contentPadding = PaddingValues(16.dp),
                    horizontalArrangement = Arrangement.spacedBy(12.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    item(span = { GridItemSpan(maxLineSpan) }) {
                        Column {
                            state.series!!.cover_image_url?.let { url ->
                                androidx.compose.foundation.Image(
                                    painter = rememberAsyncImagePainter(url),
                                    contentDescription = null,
                                    modifier = Modifier.fillMaxWidth().height(220.dp).clip(RoundedCornerShape(16.dp)),
                                    contentScale = ContentScale.Crop
                                )
                            }
                            Spacer(Modifier.height(12.dp))
                            Text(state.series!!.title, color = Color.White, fontSize = 22.sp, fontWeight = FontWeight.Bold)
                            state.series!!.synopsis?.let { Text(it, color = Color.LightGray, fontSize = 14.sp, modifier = Modifier.padding(top = 8.dp)) }
                            state.resumeSlug?.let { resumeSlug ->
                                Button(
                                    onClick = { navController.navigate(Screen.EpisodePlayer.createRoute(resumeSlug)) },
                                    modifier = Modifier.fillMaxWidth().padding(top = 16.dp).height(48.dp),
                                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFDC2626))
                                ) {
                                    Text(if (state.hasHistory) "Resume" else "Play Episode 1", fontWeight = FontWeight.Bold)
                                }
                            }
                        }
                    }

                    item(span = { GridItemSpan(maxLineSpan) }) { 
                        Text("Episodes", color = Color.White, fontSize = 16.sp, fontWeight = FontWeight.Bold, modifier = Modifier.padding(vertical = 8.dp)) 
                    }
                    if (state.episodes.isEmpty()) {
                        item(span = { GridItemSpan(maxLineSpan) }) { Text("No episodes yet", color = Color.Gray, modifier = Modifier.padding(16.dp)) }
                    }
                    items(state.episodes) { episode ->
                        EpisodeGridItem(episode) {
                            navController.navigate(Screen.EpisodePlayer.createRoute(episode.slug))
                        }
                    }
                }
            }
        }
    }
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun EpisodeGridItem(episode: Episode, onClick: () -> Unit) {
    Card(
        onClick = onClick,
        modifier = Modifier.fillMaxWidth().aspectRatio(2f/3f),
        colors = CardDefaults.cardColors(containerColor = Color(0xFF1A1A1A)),
        shape = RoundedCornerShape(8.dp)
    ) {
        Box(modifier = Modifier.fillMaxSize()) {
            episode.thumbnail_url?.let {
                androidx.compose.foundation.Image(
                    painter = rememberAsyncImagePainter(it),
                    contentDescription = null,
                    modifier = Modifier.fillMaxSize(),
                    contentScale = ContentScale.Crop
                )
            }
            // Overlay for readability
            Box(modifier = Modifier.fillMaxSize().background(Color.Black.copy(alpha = 0.3f)))
            
            // Episode number badge
            Box(
                modifier = Modifier.align(Alignment.TopStart).padding(6.dp)
                    .background(Color.Black.copy(alpha = 0.7f), RoundedCornerShape(4.dp)).padding(horizontal = 6.dp, vertical = 2.dp)
            ) {
                Text("EP ${episode.episode_number ?: ""}", color = Color.White, fontSize = 10.sp, fontWeight = FontWeight.Bold)
            }
            
            // Lock/Free icon
            Box(modifier = Modifier.align(Alignment.TopEnd).padding(6.dp)) {
                if (episode.is_free == false) {
                    Icon(Icons.Default.Lock, "Locked", tint = Color(0xFFEAB308), modifier = Modifier.size(16.dp))
                } else {
                    Box(modifier = Modifier.background(Color(0xFF22C55E), RoundedCornerShape(4.dp)).padding(horizontal = 4.dp, vertical = 2.dp)) {
                        Text("FREE", color = Color.White, fontSize = 9.sp, fontWeight = FontWeight.Bold)
                    }
                }
            }
            
            // Title at bottom
            Box(
                modifier = Modifier.align(Alignment.BottomCenter).fillMaxWidth()
                    .background(Color.Black.copy(alpha = 0.7f)).padding(horizontal = 6.dp, vertical = 4.dp)
            ) {
                Text(episode.title, color = Color.White, fontSize = 11.sp, maxLines = 1, textAlign = TextAlign.Center, modifier = Modifier.fillMaxWidth())
            }
        }
    }
}
