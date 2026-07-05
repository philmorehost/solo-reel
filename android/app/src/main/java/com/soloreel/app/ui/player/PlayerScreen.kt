package com.soloreel.app.ui.player

import android.net.Uri
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.unit.dp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.media3.common.MediaItem
import androidx.media3.common.util.UnstableApi
import androidx.media3.exoplayer.ExoPlayer
import androidx.media3.ui.AspectRatioFrameLayout
import androidx.media3.ui.PlayerView
import com.soloreel.app.data.api.SOLOREELApi
import com.soloreel.app.data.model.Episode
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class PlayerState(val episode: Episode? = null, val isLoading: Boolean = true, val error: String? = null)

@HiltViewModel
class PlayerViewModel @Inject constructor(private val api: SOLOREELApi) : ViewModel() {
    private val _state = MutableStateFlow(PlayerState())
    val state: StateFlow<PlayerState> = _state.asStateFlow()

    fun load(slug: String) {
        viewModelScope.launch {
            _state.value = PlayerState(isLoading = true)
            try {
                val r = api.getEpisode(slug)
                _state.value = PlayerState(episode = r.data, isLoading = false)
            } catch (e: Exception) {
                _state.value = PlayerState(error = e.message, isLoading = false)
            }
        }
    }
}

@UnstableApi
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PlayerScreen(slug: String, vm: PlayerViewModel = hiltViewModel()) {
    val state by vm.state.collectAsState()
    LaunchedEffect(slug) { vm.load(slug) }

    Column(modifier = Modifier.fillMaxSize().background(Color.Black)) {
        TopAppBar(
            title = { Text(state.episode?.title ?: "Player", color = Color.White) },
            navigationIcon = { IconButton(onClick = { /*navController.popBackStack()*/ }) { Icon(Icons.Default.ArrowBack, null, tint = Color.White) } },
            colors = TopAppBarDefaults.topAppBarColors(containerColor = Color.Black)
        )

        if (state.isLoading) {
            Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) { CircularProgressIndicator(color = Color(0xFFDC2626)) }
        } else if (state.error != null) {
            Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) { Text(state.error!!, color = Color.Red) }
        } else if (state.episode?.video_hls_url != null) {
            val context = androidx.compose.ui.platform.LocalContext.current
            var exoPlayer by remember { mutableStateOf<ExoPlayer?>(null) }

            AndroidView(
                modifier = Modifier.fillMaxWidth().aspectRatio(9f / 16f),
                factory = { ctx ->
                    ExoPlayer.Builder(ctx).build().apply {
                        exoPlayer = this
                        setMediaItem(MediaItem.fromUri(Uri.parse(state.episode!!.video_hls_url)))
                        prepare()
                        playWhenReady = true
                    }
                    PlayerView(ctx).apply {
                        player = exoPlayer
                        useController = true
                        resizeMode = AspectRatioFrameLayout.RESIZE_MODE_FIT
                    }
                },
                update = { view ->
                    view.player = exoPlayer
                }
            )

            DisposableEffect(Unit) {
                onDispose { exoPlayer?.release() }
            }

            Column(modifier = Modifier.padding(16.dp)) {
                Text(state.episode!!.title, color = Color.White, style = MaterialTheme.typography.titleLarge)
                Text(state.episode!!.series_title ?: "", color = Color.Gray, style = MaterialTheme.typography.bodyMedium)
                state.episode!!.description?.let { Text(it, color = Color.LightGray, modifier = Modifier.padding(top = 8.dp)) }
            }
        }
    }
}
