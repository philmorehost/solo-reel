package com.soloreel.app.ui.foryou

import androidx.compose.foundation.ExperimentalFoundationApi
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.pager.VerticalPager
import androidx.compose.foundation.pager.rememberPagerState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.PlayArrow
import androidx.compose.material.icons.filled.VolumeOff
import androidx.compose.material.icons.filled.VolumeUp
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.media3.common.MediaItem
import androidx.media3.common.Player
import androidx.media3.common.util.UnstableApi
import androidx.media3.exoplayer.ExoPlayer
import androidx.media3.ui.AspectRatioFrameLayout
import androidx.media3.ui.PlayerView
import androidx.navigation.NavHostController
import com.soloreel.app.data.api.SOLOREELApi
import com.soloreel.app.data.api.TokenManager
import com.soloreel.app.data.model.ForYouItem
import com.soloreel.app.ui.navigation.Screen
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class ForYouState(
    val trailers: List<ForYouItem> = emptyList(),
    val isLoading: Boolean = false
)

@HiltViewModel
class ForYouViewModel @Inject constructor(
    private val api: SOLOREELApi,
    private val tokenManager: TokenManager
) : ViewModel() {
    private val _state = MutableStateFlow(ForYouState())
    val state: StateFlow<ForYouState> = _state.asStateFlow()

    private val guestIdOrNull: String? get() = if (tokenManager.isLoggedIn) null else tokenManager.guestId

    fun load() {
        viewModelScope.launch {
            _state.value = _state.value.copy(isLoading = true)
            try {
                val trailers = api.getForYou(guestIdOrNull).data ?: emptyList()
                _state.value = _state.value.copy(trailers = trailers, isLoading = false)
            } catch (e: Exception) {
                _state.value = _state.value.copy(isLoading = false)
            }
        }
    }
}

/** Vertical feed of admin-uploaded trailers, auto-playing one after another —
 * the mobile counterpart to /for-you on web. "Watch Now" resumes the series
 * via the resume_slug the backend already resolved. */
@OptIn(ExperimentalFoundationApi::class)
@Composable
fun ForYouScreen(navController: NavHostController, vm: ForYouViewModel = hiltViewModel()) {
    val state by vm.state.collectAsState()
    LaunchedEffect(Unit) { vm.load() }

    Box(modifier = Modifier.fillMaxSize().background(Color.Black)) {
        if (state.isLoading && state.trailers.isEmpty()) {
            Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                CircularProgressIndicator(color = Color(0xFFDC2626))
            }
        } else if (state.trailers.isEmpty()) {
            Column(Modifier.fillMaxSize(), horizontalAlignment = Alignment.CenterHorizontally, verticalArrangement = Arrangement.Center) {
                Text("🎬", fontSize = 52.sp)
                Spacer(Modifier.height(12.dp))
                Text("No trailers yet", color = Color.White, fontSize = 18.sp, fontWeight = FontWeight.Bold)
                Text("Check back soon for new trailers.", color = Color(0xFF888888), fontSize = 13.sp)
            }
        } else {
            var muted by remember { mutableStateOf(false) }
            val pagerState = rememberPagerState(initialPage = 0) { state.trailers.size }

            VerticalPager(state = pagerState, modifier = Modifier.fillMaxSize()) { page ->
                val trailer = state.trailers[page]
                val shouldMount = kotlin.math.abs(pagerState.currentPage - page) <= 1
                TrailerPage(
                    trailer = trailer,
                    shouldMount = shouldMount,
                    isActive = pagerState.currentPage == page,
                    muted = muted,
                    onToggleMute = { muted = !muted },
                    onWatchNow = {
                        if (trailer.resume_slug != null) {
                            navController.navigate(Screen.EpisodePlayer.createRoute(trailer.resume_slug))
                        } else {
                            navController.navigate(Screen.SeriesDetail.createRoute(trailer.series_slug))
                        }
                    }
                )
            }
        }
    }
}

@UnstableApi
@Composable
private fun TrailerPage(
    trailer: ForYouItem,
    shouldMount: Boolean,
    isActive: Boolean,
    muted: Boolean,
    onToggleMute: () -> Unit,
    onWatchNow: () -> Unit
) {
    Box(modifier = Modifier.fillMaxSize().background(Color.Black)) {
        if (shouldMount) {
            val context = androidx.compose.ui.platform.LocalContext.current
            var exoPlayer by remember(trailer.episode_id) { mutableStateOf<ExoPlayer?>(null) }

            AndroidView(
                modifier = Modifier.fillMaxSize(),
                factory = { ctx ->
                    ExoPlayer.Builder(ctx).build().apply {
                        exoPlayer = this
                        setMediaItem(MediaItem.fromUri(android.net.Uri.parse(trailer.trailer_url)))
                        repeatMode = Player.REPEAT_MODE_ONE
                        prepare()
                    }
                    PlayerView(ctx).apply {
                        player = exoPlayer
                        useController = false
                        resizeMode = AspectRatioFrameLayout.RESIZE_MODE_ZOOM
                    }
                },
                update = { view ->
                    view.player = exoPlayer
                    exoPlayer?.volume = if (muted) 0f else 1f
                    exoPlayer?.playWhenReady = isActive
                }
            )

            DisposableEffect(trailer.episode_id) {
                onDispose { exoPlayer?.release() }
            }
        }

        Box(
            modifier = Modifier.fillMaxSize()
                .background(Brush.verticalGradient(listOf(Color.Transparent, Color(0x66000000), Color(0xCC000000))))
        )

        IconButton(
            onClick = onToggleMute,
            modifier = Modifier.align(Alignment.TopEnd).padding(16.dp).background(Color(0x66000000), CircleShape)
        ) {
            Icon(if (muted) Icons.Default.VolumeOff else Icons.Default.VolumeUp, contentDescription = "Mute toggle", tint = Color.White)
        }

        Column(modifier = Modifier.align(Alignment.BottomStart).padding(20.dp).padding(bottom = 24.dp)) {
            Text(trailer.series_title, color = Color.White, fontSize = 20.sp, fontWeight = FontWeight.ExtraBold)
            Spacer(Modifier.height(12.dp))
            Button(
                onClick = onWatchNow,
                colors = ButtonDefaults.buttonColors(containerColor = Color.White, contentColor = Color.Black),
                shape = androidx.compose.foundation.shape.RoundedCornerShape(24.dp)
            ) {
                Icon(Icons.Default.PlayArrow, contentDescription = null, modifier = Modifier.size(20.dp))
                Spacer(Modifier.width(6.dp))
                Text("Watch Now", fontWeight = FontWeight.Bold)
            }
        }
    }
}
