package com.soloreel.app.ui.series

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
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
    val isLoading: Boolean = false, val error: String? = null
)

@HiltViewModel
class SeriesDetailViewModel @Inject constructor(private val api: SOLOREELApi) : ViewModel() {
    private val _state = MutableStateFlow(SeriesDetailState())
    val state: StateFlow<SeriesDetailState> = _state.asStateFlow()
    fun load(slug: String) {
        viewModelScope.launch {
            _state.value = _state.value.copy(isLoading = true)
            try {
                val s = api.getSeriesDetail(slug)
                val e = if (s.data?.id != null) api.getEpisodes(s.data.id) else null
                _state.value = SeriesDetailState(series = s.data, episodes = e?.data ?: emptyList())
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
            LazyColumn(modifier = Modifier.fillMaxSize(), contentPadding = PaddingValues(bottom = 16.dp)) {
                item {
                    Column(modifier = Modifier.padding(16.dp)) {
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
                    }
                }

                item { Text("Episodes", color = Color.White, fontSize = 16.sp, fontWeight = FontWeight.Bold, modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)) }
                if (state.episodes.isEmpty()) {
                    item { Text("No episodes yet", color = Color.Gray, modifier = Modifier.padding(16.dp)) }
                }
                items(state.episodes) { episode ->
                    EpisodeRow(episode) {
                        navController.navigate(Screen.EpisodePlayer.createRoute(episode.slug))
                    }
                }
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun EpisodeRow(episode: Episode, onClick: () -> Unit) {
    Card(
        onClick = onClick,
        modifier = Modifier.fillMaxWidth().padding(horizontal = 16.dp, vertical = 4.dp),
        colors = CardDefaults.cardColors(containerColor = Color(0xFF1A1A1A))
    ) {
        Row(modifier = Modifier.padding(12.dp), verticalAlignment = Alignment.CenterVertically) {
            episode.thumbnail_url?.let {
                androidx.compose.foundation.Image(
                    painter = rememberAsyncImagePainter(it),
                    contentDescription = null,
                    modifier = Modifier.size(width = 80.dp, height = 56.dp).clip(RoundedCornerShape(8.dp)),
                    contentScale = ContentScale.Crop
                )
            }
            Spacer(Modifier.width(12.dp))
            Column(modifier = Modifier.weight(1f)) {
                Text("Ep ${episode.episode_number ?: ""}", color = Color(0xFFDC2626), fontSize = 12.sp)
                Text(episode.title, color = Color.White, fontSize = 14.sp, fontWeight = FontWeight.Medium, maxLines = 1)
            }
            if (episode.is_free == false) { Icon(Icons.Default.Lock, "Locked", tint = Color.Yellow, modifier = Modifier.size(20.dp)) }
        }
    }
}
