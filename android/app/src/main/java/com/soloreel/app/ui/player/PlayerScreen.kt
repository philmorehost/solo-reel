package com.soloreel.app.ui.player

import android.net.Uri
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.ExperimentalFoundationApi
import androidx.compose.foundation.gestures.detectTapGestures
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.pager.VerticalPager
import androidx.compose.foundation.pager.rememberPagerState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Favorite
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.VolumeOff
import androidx.compose.material.icons.filled.VolumeUp
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.input.pointer.pointerInput
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.navigation.NavHostController
import androidx.media3.common.MediaItem
import androidx.media3.common.Player
import androidx.media3.common.util.UnstableApi
import androidx.media3.exoplayer.ExoPlayer
import androidx.media3.ui.AspectRatioFrameLayout
import androidx.media3.ui.PlayerView
import com.soloreel.app.ads.InterstitialAdGate
import com.soloreel.app.ads.RewardedAdManager
import com.soloreel.app.data.api.SOLOREELApi
import com.soloreel.app.data.api.TokenManager
import com.soloreel.app.data.api.apiMessage
import com.soloreel.app.data.model.Episode
import com.soloreel.app.data.model.InterstitialAd
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.distinctUntilChanged
import kotlinx.coroutines.launch
import kotlin.math.abs
import javax.inject.Inject

data class PlayerState(
    val episodes: List<Episode> = emptyList(),
    val startIndex: Int = 0,
    val isLoading: Boolean = true,
    val error: String? = null,
    val interstitialAd: InterstitialAd? = null
)

@HiltViewModel
class PlayerViewModel @Inject constructor(
    private val api: SOLOREELApi,
    private val tokenManager: TokenManager
) : ViewModel() {
    private val _state = MutableStateFlow(PlayerState())
    val state: StateFlow<PlayerState> = _state.asStateFlow()

    private val guestIdOrNull: String? get() = if (tokenManager.isLoggedIn) null else tokenManager.guestId

    /** Shows an admin-uploaded interstitial ad every few episodes (see InterstitialAdGate). */
    fun maybeLoadInterstitial() {
        if (!InterstitialAdGate.shouldShowForNewEpisode()) return
        viewModelScope.launch {
            try {
                val ad = api.getInterstitialAd().data
                if (ad != null) _state.value = _state.value.copy(interstitialAd = ad)
            } catch (_: Exception) { /* skip silently — ads are non-critical */ }
        }
    }

    fun dismissInterstitial() { _state.value = _state.value.copy(interstitialAd = null) }

    /** Loads the full episode list for [slug]'s series so the vertical feed can page
     * through siblings without a network round-trip per swipe. */
    fun load(slug: String) {
        viewModelScope.launch {
            _state.value = PlayerState(isLoading = true)
            try {
                val current = api.getEpisode(slug, guestIdOrNull).data
                val seriesId = current?.series_id
                val list = if (seriesId != null) {
                    api.getEpisodes(seriesId, guestIdOrNull).data ?: listOfNotNull(current)
                } else {
                    listOfNotNull(current)
                }
                val startIndex = list.indexOfFirst { it.slug == slug }.coerceAtLeast(0)
                _state.value = PlayerState(episodes = list, startIndex = startIndex, isLoading = false)
            } catch (e: Exception) {
                _state.value = PlayerState(error = e.message, isLoading = false)
            }
        }
    }

    private fun unlockBody(): Map<String, String> =
        guestIdOrNull?.let { mapOf("guest_id" to it) } ?: emptyMap()

    private fun markUnlocked(episodeId: Int) {
        val updated = _state.value.episodes.map {
            if (it.id == episodeId) it.copy(is_unlocked = true) else it
        }
        _state.value = _state.value.copy(episodes = updated)
    }

    fun unlockWithCoins(episodeId: Int, onSuccess: () -> Unit, onInsufficientCoins: () -> Unit) {
        viewModelScope.launch {
            try {
                val r = api.unlockWithCoins(episodeId, unlockBody())
                if (r.status == true) {
                    markUnlocked(episodeId)
                    onSuccess()
                }
            } catch (e: Exception) {
                val msg = e.apiMessage(e.message ?: "Unknown error")
                if (msg.contains("Insufficient coins", ignoreCase = true)) onInsufficientCoins()
            }
        }
    }

    fun unlockWithAd(episodeId: Int, onSuccess: () -> Unit) {
        viewModelScope.launch {
            try {
                val r = api.unlockWithAd(episodeId, unlockBody())
                if (r.status == true) {
                    markUnlocked(episodeId)
                    onSuccess()
                }
            } catch (_: Exception) { /* toast already shown by caller on ad failure */ }
        }
    }
}

/**
 * NOTE on ViewPager2: this app is 100% Jetpack Compose (single-activity, no
 * Fragments), so a raw View-based ViewPager2 would need a FragmentStateAdapter
 * bridge for no real benefit. Compose Foundation's VerticalPager is the direct,
 * idiomatic equivalent (same snap-to-page/fling-per-page mechanics) and is what
 * this file uses to fulfil the "vertical ViewPager2" requirement.
 */
@UnstableApi
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PlayerScreen(slug: String, navController: NavHostController, vm: PlayerViewModel = hiltViewModel()) {
    val state by vm.state.collectAsState()
    LaunchedEffect(slug) { vm.load(slug) }

    Box(modifier = Modifier.fillMaxSize().background(Color.Black)) {
        if (state.isLoading) {
            Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) { CircularProgressIndicator(color = Color(0xFFDC2626)) }
        } else if (state.error != null) {
            Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) { Text(state.error!!, color = Color.Red) }
        } else if (state.episodes.isNotEmpty()) {
            ReelFeed(state = state, vm = vm, navController = navController)
        }

        TopAppBar(
            title = {},
            navigationIcon = { IconButton(onClick = { navController.popBackStack() }) { Icon(Icons.Default.ArrowBack, null, tint = Color.White) } },
            colors = TopAppBarDefaults.topAppBarColors(containerColor = Color.Transparent)
        )

        state.interstitialAd?.let { ad -> InterstitialOverlay(ad = ad, onDismiss = { vm.dismissInterstitial() }) }
    }
}

@UnstableApi
@OptIn(ExperimentalFoundationApi::class)
@Composable
private fun ReelFeed(state: PlayerState, vm: PlayerViewModel, navController: NavHostController) {
    val episodes = state.episodes
    val pagerState = rememberPagerState(initialPage = state.startIndex) { episodes.size }
    val scope = rememberCoroutineScope()
    // Global mute state: unmuting one video keeps the feed unmuted as the user swipes.
    var muted by rememberSaveable { mutableStateOf(true) }

    LaunchedEffect(pagerState) {
        snapshotFlow { pagerState.currentPage }
            .distinctUntilChanged()
            .collect { vm.maybeLoadInterstitial() }
    }

    VerticalPager(
        state = pagerState,
        modifier = Modifier.fillMaxSize(),
        beyondBoundsPageCount = 1 // keep the immediate neighbor composed, not the whole feed
    ) { page ->
        val episode = episodes[page]
        val isActive = pagerState.currentPage == page && !pagerState.isScrollInProgress
        // Only mount an ExoPlayer for the current page and its immediate neighbors.
        val shouldMount = abs(pagerState.currentPage - page) <= 1

        ReelPage(
            episode = episode,
            isActive = isActive,
            shouldMount = shouldMount,
            muted = muted,
            onMuteToggle = { muted = !muted },
            onEnded = {
                if (page < episodes.lastIndex) {
                    scope.launch { pagerState.animateScrollToPage(page + 1) }
                }
            },
            onUnlockWithCoins = { onSuccess, onInsufficient -> vm.unlockWithCoins(episode.id, onSuccess, onInsufficient) },
            onUnlockWithAd = { onSuccess -> vm.unlockWithAd(episode.id, onSuccess) },
            onNavigateToCoins = { navController.navigate(com.soloreel.app.ui.navigation.Screen.Coins.route) }
        )
    }
}

@UnstableApi
@Composable
private fun ReelPage(
    episode: Episode,
    isActive: Boolean,
    shouldMount: Boolean,
    muted: Boolean,
    onMuteToggle: () -> Unit,
    onEnded: () -> Unit,
    onUnlockWithCoins: (onSuccess: () -> Unit, onInsufficient: () -> Unit) -> Unit,
    onUnlockWithAd: (onSuccess: () -> Unit) -> Unit,
    onNavigateToCoins: () -> Unit
) {
    val context = LocalContext.current
    var isUnlocked by remember(episode.id) { mutableStateOf(episode.is_unlocked == true || episode.is_free == true) }
    var exoPlayer by remember(episode.id) { mutableStateOf<ExoPlayer?>(null) }
    var showHeart by remember { mutableStateOf(false) }

    Box(modifier = Modifier.fillMaxSize()) {
        if (isUnlocked && episode.video_hls_url != null) {
            if (shouldMount) {
                AndroidView(
                    modifier = Modifier.fillMaxSize(),
                    factory = { ctx ->
                        ExoPlayer.Builder(ctx).build().apply {
                            exoPlayer = this
                            setMediaItem(MediaItem.fromUri(Uri.parse(episode.video_hls_url)))
                            volume = if (muted) 0f else 1f
                            prepare()
                            addListener(object : Player.Listener {
                                override fun onPlaybackStateChanged(playbackState: Int) {
                                    if (playbackState == Player.STATE_ENDED) onEnded()
                                }
                            })
                        }
                        PlayerView(ctx).apply {
                            player = exoPlayer
                            useController = false
                            resizeMode = AspectRatioFrameLayout.RESIZE_MODE_ZOOM
                        }
                    },
                    update = { view -> view.player = exoPlayer }
                )

                LaunchedEffect(isActive) {
                    exoPlayer?.playWhenReady = isActive
                    if (!isActive) exoPlayer?.seekTo(0)
                }
                LaunchedEffect(muted) { exoPlayer?.volume = if (muted) 0f else 1f }

                DisposableEffect(episode.id, shouldMount) {
                    onDispose { exoPlayer?.release() }
                }

                // Tap-to-pause / double-tap-to-like layered on top of the player.
                // detectTapGestures only claims stationary taps, so a vertical drag
                // is left untouched and still reaches the Pager's own scroll handling.
                Box(
                    modifier = Modifier
                        .fillMaxSize()
                        .pointerInput(episode.id) {
                            detectTapGestures(
                                onTap = {
                                    val p = exoPlayer ?: return@detectTapGestures
                                    p.playWhenReady = !p.playWhenReady
                                },
                                onDoubleTap = {
                                    showHeart = true
                                }
                            )
                        }
                )

                if (showHeart) {
                    LaunchedEffect(Unit) { delay(700); showHeart = false }
                    Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        Icon(Icons.Default.Favorite, null, tint = Color(0xFFDC2626), modifier = Modifier.size(96.dp))
                    }
                }
            }
        } else {
            UnlockOverlay(
                episode = episode,
                onUnlockWithCoins = { onUnlockWithCoins({ isUnlocked = true }, onNavigateToCoins) },
                onUnlockWithAd = {
                    val activity = context as? android.app.Activity ?: return@UnlockOverlay
                    RewardedAdManager.showAd(
                        activity = activity,
                        onRewarded = { onUnlockWithAd { isUnlocked = true } },
                        onFailed = { reason -> android.widget.Toast.makeText(context, reason, android.widget.Toast.LENGTH_SHORT).show() }
                    )
                },
                onNavigateToCoins = onNavigateToCoins
            )
        }

        if (isUnlocked) {
            IconButton(onClick = onMuteToggle, modifier = Modifier.align(Alignment.TopEnd).padding(top = 56.dp, end = 12.dp).background(Color(0x66000000), androidx.compose.foundation.shape.CircleShape)) {
                Icon(if (muted) Icons.Default.VolumeOff else Icons.Default.VolumeUp, "Mute toggle", tint = Color.White)
            }
        }

        Column(modifier = Modifier.align(Alignment.BottomStart).padding(16.dp)) {
            Text("EP ${episode.episode_number} · ${episode.title}", color = Color.White, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
            episode.series_title?.let { Text(it, color = Color.LightGray, style = MaterialTheme.typography.bodySmall) }
        }
    }
}

@Composable
private fun UnlockOverlay(
    episode: Episode,
    onUnlockWithCoins: () -> Unit,
    onUnlockWithAd: () -> Unit,
    onNavigateToCoins: () -> Unit
) {
    Box(modifier = Modifier.fillMaxSize().background(Color(0xFF111111)), contentAlignment = Alignment.Center) {
        Column(horizontalAlignment = Alignment.CenterHorizontally, modifier = Modifier.padding(16.dp)) {
            val unlockMethod = episode.unlock_method ?: "coins"
            Icon(Icons.Default.Lock, "Locked", tint = Color(0xFFEAB308), modifier = Modifier.size(48.dp))
            Spacer(Modifier.height(16.dp))
            Text("Unlock Episode ${episode.episode_number}", color = Color.White, fontSize = 20.sp, fontWeight = FontWeight.Bold)
            Spacer(Modifier.height(8.dp))

            val costText = if (unlockMethod == "ads") "This episode can be unlocked by watching a short ad." else "This episode requires ${episode.coin_cost?.toInt() ?: 0} coins to unlock."
            Text(costText, color = Color.Gray, fontSize = 14.sp)
            Spacer(Modifier.height(24.dp))

            if (unlockMethod == "coins" || unlockMethod == "both") {
                Button(
                    onClick = onUnlockWithCoins,
                    modifier = Modifier.fillMaxWidth(0.8f).height(48.dp),
                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFDC2626))
                ) { Text("Unlock for ${episode.coin_cost?.toInt() ?: 0} Coins", fontWeight = FontWeight.Bold) }
                Spacer(Modifier.height(12.dp))
            }

            if (unlockMethod == "ads" || unlockMethod == "both") {
                OutlinedButton(
                    onClick = onUnlockWithAd,
                    modifier = Modifier.fillMaxWidth(0.8f).height(48.dp),
                    colors = ButtonDefaults.outlinedButtonColors(contentColor = Color.White),
                    border = androidx.compose.foundation.BorderStroke(1.dp, Color.White)
                ) { Text("Watch Ad to Unlock", fontWeight = FontWeight.Bold) }
                Spacer(Modifier.height(12.dp))
            }

            TextButton(onClick = onNavigateToCoins) {
                Text("Buy More Coins", color = Color(0xFFEAB308), fontWeight = FontWeight.Bold)
            }
        }
    }
}

@Composable
private fun InterstitialOverlay(ad: InterstitialAd, onDismiss: () -> Unit) {
    val context = LocalContext.current
    Box(modifier = Modifier.fillMaxSize().background(Color.Black)) {
        if (ad.media_type == "video" && !ad.media_url.isNullOrBlank()) {
            com.soloreel.app.ui.home.MutedLoopingVideo(url = ad.media_url, modifier = Modifier.fillMaxSize())
        } else if (!ad.media_url.isNullOrBlank()) {
            Image(
                painter = coil.compose.rememberAsyncImagePainter(ad.media_url),
                contentDescription = null,
                modifier = Modifier.fillMaxSize(),
                contentScale = ContentScale.Crop
            )
        }
        Column(modifier = Modifier.fillMaxSize().padding(16.dp)) {
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text("Sponsored", color = Color.White, fontWeight = FontWeight.Bold, modifier = Modifier.background(Color(0x99000000), androidx.compose.foundation.shape.RoundedCornerShape(4.dp)).padding(horizontal = 8.dp, vertical = 4.dp))
                IconButton(onClick = onDismiss, modifier = Modifier.background(Color(0x99000000), androidx.compose.foundation.shape.CircleShape)) {
                    Icon(Icons.Default.Close, "Dismiss", tint = Color.White)
                }
            }
            Spacer(Modifier.weight(1f))
            ad.target_url?.let { url ->
                Button(
                    onClick = {
                        try { context.startActivity(android.content.Intent(android.content.Intent.ACTION_VIEW, android.net.Uri.parse(url))) } catch (_: Exception) {}
                    },
                    modifier = Modifier.fillMaxWidth(),
                    colors = ButtonDefaults.buttonColors(containerColor = Color.White, contentColor = Color.Black)
                ) { Text(ad.title ?: "Learn More", fontWeight = FontWeight.Bold) }
            }
        }
    }
}
