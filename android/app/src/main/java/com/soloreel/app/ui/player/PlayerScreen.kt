package com.soloreel.app.ui.player

import android.net.Uri
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.ExperimentalFoundationApi
import androidx.compose.foundation.gestures.detectTapGestures
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.pager.VerticalPager
import androidx.compose.foundation.pager.rememberPagerState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Bookmark
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Favorite
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.VolumeOff
import androidx.compose.material.icons.filled.VolumeUp
import androidx.compose.material.icons.outlined.BookmarkBorder
import androidx.compose.material.icons.outlined.ChatBubbleOutline
import androidx.compose.material.icons.outlined.FavoriteBorder
import androidx.compose.material.icons.outlined.Info
import androidx.compose.material.icons.outlined.Send
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.input.pointer.pointerInput
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.compose.ui.window.Dialog
import androidx.core.content.FileProvider
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
import androidx.browser.customtabs.CustomTabsIntent
import com.soloreel.app.ads.InterstitialAdGate
import com.soloreel.app.ads.RewardedAdManager
import com.soloreel.app.data.api.PaymentResultBus
import com.soloreel.app.data.api.SOLOREELApi
import com.soloreel.app.data.api.TokenManager
import com.soloreel.app.data.api.apiMessage
import com.soloreel.app.data.model.Comment
import com.soloreel.app.data.model.Episode
import com.soloreel.app.data.model.InterstitialAd
import com.soloreel.app.data.model.Series
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.distinctUntilChanged
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import kotlin.math.abs
import java.io.File
import java.net.URL
import javax.inject.Inject

data class PlayerState(
    val episodes: List<Episode> = emptyList(),
    val startIndex: Int = 0,
    val isLoading: Boolean = true,
    val error: String? = null,
    val interstitialAd: InterstitialAd? = null,
    val vipPlans: List<com.soloreel.app.data.model.VipPlan> = emptyList()
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

    // --- VIP subscription: an alternative to buying coins, not a replacement.
    // Shown alongside coin/ad unlock options when a viewer can't afford an
    // episode. Reuses the exact Custom-Tab + PaymentResultBus checkout flow
    // CoinShopScreen already established for coin purchases. ---

    fun loadVipPlans() {
        if (_state.value.vipPlans.isNotEmpty()) return
        viewModelScope.launch {
            try {
                val plans = api.getVipPlans().data ?: emptyList()
                _state.value = _state.value.copy(vipPlans = plans)
            } catch (_: Exception) { /* offers dialog just shows an empty VIP section */ }
        }
    }

    fun purchaseVip(planId: Int, onResult: (authUrl: String?, reference: String?, error: String?) -> Unit) {
        if (!tokenManager.isLoggedIn) {
            onResult(null, null, "Please sign in to subscribe to VIP.")
            return
        }
        viewModelScope.launch {
            try {
                val r = api.purchaseVip(mapOf("plan_id" to planId))
                onResult(r.data?.authorization_url, r.data?.reference, if (r.data?.authorization_url == null) (r.error ?: r.message ?: "Could not initiate payment") else null)
            } catch (e: Exception) {
                onResult(null, null, "Could not initiate payment: ${e.apiMessage("please try again")}")
            }
        }
    }

    /** Verifies a completed VIP payment, then retries the normal coin-unlock
     * call for [episodeId] — it now succeeds for free via the server's VIP
     * gating in Api\TransactionController::unlock(), no separate "VIP unlock"
     * client path needed. */
    fun verifyVipAndRetryUnlock(reference: String, episodeId: Int, onSuccess: () -> Unit) {
        viewModelScope.launch {
            try { api.verifyPayment(reference) } catch (_: Exception) { }
            unlockWithCoins(episodeId, onSuccess) { /* still insufficient/failed — leave overlay as-is */ }
        }
    }

    /** Fire-and-forget: powers "resume last-watched episode" and the Continue Watching shelf. */
    fun recordProgress(episodeId: Int) {
        viewModelScope.launch {
            try { api.recordProgress(episodeId, unlockBody()) } catch (_: Exception) { }
        }
    }

    fun toggleLike(episodeId: Int, onResult: (liked: Boolean, count: Int) -> Unit) {
        viewModelScope.launch {
            try {
                val r = api.toggleLike(episodeId, unlockBody()).data
                if (r != null) onResult(r.liked == true, r.count)
            } catch (_: Exception) { }
        }
    }

    fun toggleSave(episodeId: Int, onResult: (saved: Boolean, count: Int) -> Unit) {
        viewModelScope.launch {
            try {
                val r = api.toggleSave(episodeId, unlockBody()).data
                if (r != null) onResult(r.saved == true, r.count)
            } catch (_: Exception) { }
        }
    }

    fun recordShare(episodeId: Int) {
        viewModelScope.launch {
            val body = unlockBody() + mapOf("platform" to "android")
            try { api.recordShare(episodeId, body) } catch (_: Exception) { }
        }
    }

    fun loadComments(episodeId: Int, onResult: (List<Comment>, Int) -> Unit) {
        viewModelScope.launch {
            try {
                val page = api.getComments(episodeId, 0, 50).data
                if (page != null) onResult(page.items, page.total)
            } catch (_: Exception) { onResult(emptyList(), 0) }
        }
    }

    fun postComment(episodeId: Int, body: String, onResult: (Comment?) -> Unit) {
        viewModelScope.launch {
            try {
                val payload = unlockBody() + mapOf("body" to body)
                onResult(api.postComment(episodeId, payload).data)
            } catch (_: Exception) { onResult(null) }
        }
    }

    private var seriesInfoCache: Series? = null

    fun loadSeriesInfo(seriesSlug: String, onResult: (Series?) -> Unit) {
        val cached = seriesInfoCache
        if (cached != null) { onResult(cached); return }
        viewModelScope.launch {
            try {
                val series = api.getSeriesDetail(seriesSlug).data
                seriesInfoCache = series
                onResult(series)
            } catch (_: Exception) { onResult(null) }
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
            onActive = { vm.recordProgress(episode.id) },
            onEnded = {
                vm.recordProgress(episode.id)
                if (page < episodes.lastIndex) {
                    scope.launch { pagerState.animateScrollToPage(page + 1) }
                }
            },
            onUnlockWithCoins = { onSuccess, onInsufficient -> vm.unlockWithCoins(episode.id, onSuccess, onInsufficient) },
            onUnlockWithAd = { onSuccess -> vm.unlockWithAd(episode.id, onSuccess) },
            onNavigateToCoins = { navController.navigate(com.soloreel.app.ui.navigation.Screen.Coins.route) },
            onToggleLike = { onResult -> vm.toggleLike(episode.id, onResult) },
            onToggleSave = { onResult -> vm.toggleSave(episode.id, onResult) },
            onShare = { vm.recordShare(episode.id) },
            onLoadComments = { onResult -> vm.loadComments(episode.id, onResult) },
            onPostComment = { body, onResult -> vm.postComment(episode.id, body, onResult) },
            onLoadSeriesInfo = { onResult -> episode.series_slug?.let { vm.loadSeriesInfo(it, onResult) } ?: onResult(null) },
            allEpisodes = episodes,
            onJumpToIndex = { idx -> scope.launch { pagerState.animateScrollToPage(idx) } },
            vipPlans = state.vipPlans,
            onLoadVipPlans = { vm.loadVipPlans() },
            onPurchaseVip = { planId, onResult -> vm.purchaseVip(planId, onResult) },
            onVerifyVipAndRetry = { reference, onSuccess -> vm.verifyVipAndRetryUnlock(reference, episode.id, onSuccess) }
        )
    }
}

@UnstableApi
@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun ReelPage(
    episode: Episode,
    isActive: Boolean,
    shouldMount: Boolean,
    muted: Boolean,
    onMuteToggle: () -> Unit,
    onActive: () -> Unit,
    onEnded: () -> Unit,
    onUnlockWithCoins: (onSuccess: () -> Unit, onInsufficient: () -> Unit) -> Unit,
    onUnlockWithAd: (onSuccess: () -> Unit) -> Unit,
    onNavigateToCoins: () -> Unit,
    onToggleLike: (onResult: (Boolean, Int) -> Unit) -> Unit,
    onToggleSave: (onResult: (Boolean, Int) -> Unit) -> Unit,
    onShare: () -> Unit,
    onLoadComments: (onResult: (List<Comment>, Int) -> Unit) -> Unit,
    onPostComment: (body: String, onResult: (Comment?) -> Unit) -> Unit,
    onLoadSeriesInfo: (onResult: (Series?) -> Unit) -> Unit,
    allEpisodes: List<Episode>,
    onJumpToIndex: (Int) -> Unit,
    vipPlans: List<com.soloreel.app.data.model.VipPlan>,
    onLoadVipPlans: () -> Unit,
    onPurchaseVip: (planId: Int, onResult: (String?, String?, String?) -> Unit) -> Unit,
    onVerifyVipAndRetry: (reference: String, onSuccess: () -> Unit) -> Unit
) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    var isUnlocked by remember(episode.id) { mutableStateOf(episode.is_unlocked == true || episode.is_free == true) }
    var exoPlayer by remember(episode.id) { mutableStateOf<ExoPlayer?>(null) }
    var showHeart by remember { mutableStateOf(false) }

    var isLiked by remember(episode.id) { mutableStateOf(episode.is_liked_by_viewer == true) }
    var likeCount by remember(episode.id) { mutableIntStateOf(episode.like_count ?: 0) }
    var isSaved by remember(episode.id) { mutableStateOf(episode.is_saved_by_viewer == true) }
    var saveCount by remember(episode.id) { mutableIntStateOf(episode.save_count ?: 0) }
    var commentCount by remember(episode.id) { mutableIntStateOf(episode.comment_count ?: 0) }
    var shareCount by remember(episode.id) { mutableIntStateOf(episode.share_count ?: 0) }
    var isSharing by remember(episode.id) { mutableStateOf(false) }
    var showInfoSheet by remember { mutableStateOf(false) }
    var showCommentsSheet by remember { mutableStateOf(false) }

    fun doLike() {
        val wasLiked = isLiked
        isLiked = !wasLiked
        likeCount += if (isLiked) 1 else -1
        onToggleLike { liked, count -> isLiked = liked; likeCount = count }
    }

    fun doSave() {
        val wasSaved = isSaved
        isSaved = !wasSaved
        saveCount += if (isSaved) 1 else -1
        onToggleSave { saved, count -> isSaved = saved; saveCount = count }
    }

    fun doShare() {
        onShare()
        shareCount += 1
        val videoUrl = episode.video_hls_url
        if (videoUrl == null || videoUrl.contains(".m3u8")) {
            try {
                context.startActivity(android.content.Intent(android.content.Intent.ACTION_VIEW, Uri.parse(videoUrl)))
            } catch (_: Exception) { }
            return
        }
        isSharing = true
        scope.launch {
            try {
                val dir = File(context.cacheDir, "shared_episodes").apply { mkdirs() }
                val file = File(dir, "soloreel-episode-${episode.id}.mp4")
                withContext(Dispatchers.IO) {
                    URL(videoUrl).openStream().use { input ->
                        file.outputStream().use { output -> input.copyTo(output) }
                    }
                }
                val uri = FileProvider.getUriForFile(context, "com.soloreel.app.fileprovider", file)
                val sendIntent = android.content.Intent(android.content.Intent.ACTION_SEND).apply {
                    type = "video/*"
                    putExtra(android.content.Intent.EXTRA_STREAM, uri)
                    addFlags(android.content.Intent.FLAG_GRANT_READ_URI_PERMISSION)
                }
                context.startActivity(android.content.Intent.createChooser(sendIntent, "Share episode"))
            } catch (_: Exception) {
                android.widget.Toast.makeText(context, "Unable to share this episode right now.", android.widget.Toast.LENGTH_SHORT).show()
            } finally {
                isSharing = false
            }
        }
    }

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
                    if (!isActive) exoPlayer?.seekTo(0) else onActive()
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
            var showOffersDialog by remember { mutableStateOf(false) }

            UnlockOverlay(
                episode = episode,
                onUnlockWithCoins = { onUnlockWithCoins({ isUnlocked = true }, { showOffersDialog = true }) },
                onUnlockWithAd = {
                    val activity = context as? android.app.Activity ?: return@UnlockOverlay
                    RewardedAdManager.showAd(
                        activity = activity,
                        onRewarded = { onUnlockWithAd { isUnlocked = true } },
                        onFailed = { reason -> android.widget.Toast.makeText(context, reason, android.widget.Toast.LENGTH_SHORT).show() }
                    )
                },
                onNavigateToCoins = { showOffersDialog = true }
            )

            if (showOffersDialog) {
                VipCoinOffersDialog(
                    vipPlans = vipPlans,
                    onLoadVipPlans = onLoadVipPlans,
                    onPurchaseVip = onPurchaseVip,
                    onVerifyVipAndRetry = onVerifyVipAndRetry,
                    onUnlockSuccess = { isUnlocked = true; showOffersDialog = false },
                    onNavigateToCoins = { showOffersDialog = false; onNavigateToCoins() },
                    onDismiss = { showOffersDialog = false }
                )
            }
        }

        if (isUnlocked) {
            IconButton(onClick = onMuteToggle, modifier = Modifier.align(Alignment.TopEnd).padding(top = 56.dp, end = 12.dp).background(Color(0x66000000), androidx.compose.foundation.shape.CircleShape)) {
                Icon(if (muted) Icons.Default.VolumeOff else Icons.Default.VolumeUp, "Mute toggle", tint = Color.White)
            }
        }

        // Instagram-style action rail: like, comment, save, share (share
        // hidden past episode 2 — can_share is computed server-side).
        Column(
            modifier = Modifier.align(Alignment.CenterEnd).padding(end = 12.dp, bottom = 80.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.spacedBy(20.dp)
        ) {
            ActionRailButton(
                icon = if (isLiked) Icons.Default.Favorite else Icons.Outlined.FavoriteBorder,
                tint = if (isLiked) Color(0xFFDC2626) else Color.White,
                count = likeCount,
                contentDescription = "Like",
                onClick = { doLike() }
            )
            ActionRailButton(
                icon = Icons.Outlined.ChatBubbleOutline,
                tint = Color.White,
                count = commentCount,
                contentDescription = "Comments",
                onClick = { showCommentsSheet = true }
            )
            ActionRailButton(
                icon = if (isSaved) Icons.Default.Bookmark else Icons.Outlined.BookmarkBorder,
                tint = if (isSaved) Color(0xFFEAB308) else Color.White,
                count = saveCount,
                contentDescription = "Save",
                onClick = { doSave() }
            )
            if (episode.can_share == true) {
                ActionRailButton(
                    icon = Icons.Outlined.Send,
                    tint = Color.White,
                    count = shareCount,
                    contentDescription = "Share",
                    enabled = !isSharing,
                    onClick = { doShare() }
                )
            }
            IconButton(onClick = { showInfoSheet = true }) {
                Icon(Icons.Outlined.Info, "Series info", tint = Color.White, modifier = Modifier.size(28.dp))
            }
        }

        Column(modifier = Modifier.align(Alignment.BottomStart).padding(16.dp).padding(end = 72.dp)) {
            Text("EP ${episode.episode_number} · ${episode.title}", color = Color.White, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
            episode.series_title?.let { Text(it, color = Color.LightGray, style = MaterialTheme.typography.bodySmall) }
        }
    }

    if (showInfoSheet) {
        SeriesInfoSheet(
            onDismiss = { showInfoSheet = false },
            onLoadSeriesInfo = onLoadSeriesInfo,
            allEpisodes = allEpisodes,
            onSelectEpisode = { idx -> showInfoSheet = false; onJumpToIndex(idx) }
        )
    }

    if (showCommentsSheet) {
        CommentsSheet(
            onDismiss = { showCommentsSheet = false },
            onLoadComments = onLoadComments,
            onPostComment = { body, onDone ->
                onPostComment(body) { comment ->
                    if (comment != null) commentCount += 1
                    onDone(comment)
                }
            }
        )
    }
}

@Composable
private fun ActionRailButton(
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    tint: Color,
    count: Int,
    contentDescription: String,
    enabled: Boolean = true,
    onClick: () -> Unit
) {
    Column(horizontalAlignment = Alignment.CenterHorizontally) {
        IconButton(onClick = onClick, enabled = enabled, modifier = Modifier.size(40.dp)) {
            Icon(icon, contentDescription, tint = tint, modifier = Modifier.size(30.dp))
        }
        if (count > 0) {
            Text(formatCount(count), color = Color.White, fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
        }
    }
}

private fun formatCount(n: Int): String {
    if (n < 1000) return n.toString()
    if (n < 1000000) return "%.1f".format(n / 1000.0).trimEnd('0').trimEnd('.') + "K"
    return "%.1f".format(n / 1000000.0).trimEnd('0').trimEnd('.') + "M"
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun SeriesInfoSheet(
    onDismiss: () -> Unit,
    onLoadSeriesInfo: (onResult: (Series?) -> Unit) -> Unit,
    allEpisodes: List<Episode>,
    onSelectEpisode: (Int) -> Unit
) {
    var series by remember { mutableStateOf<Series?>(null) }
    var loaded by remember { mutableStateOf(false) }

    LaunchedEffect(Unit) {
        onLoadSeriesInfo { result -> series = result; loaded = true }
    }

    ModalBottomSheet(onDismissRequest = onDismiss, containerColor = Color(0xFF141416)) {
        Column(modifier = Modifier.fillMaxWidth().heightIn(max = 500.dp).padding(horizontal = 16.dp)) {
            Text("Series Info", color = Color.White, fontSize = 18.sp, fontWeight = FontWeight.Bold)
            Spacer(Modifier.height(8.dp))
            if (!loaded) {
                Box(Modifier.fillMaxWidth().padding(32.dp), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator(color = Color(0xFFDC2626))
                }
            } else {
                series?.synopsis?.let {
                    Text(it, color = Color(0xFFCCCCCC), fontSize = 14.sp, modifier = Modifier.padding(bottom = 12.dp))
                }
                Text("Episodes (${allEpisodes.size})", color = Color(0xFF999999), fontSize = 13.sp, fontWeight = FontWeight.Bold, modifier = Modifier.padding(bottom = 8.dp))
                LazyColumn(modifier = Modifier.weight(1f, fill = false)) {
                    items(allEpisodes.size) { idx ->
                        val ep = allEpisodes[idx]
                        Row(
                            modifier = Modifier.fillMaxWidth().clickable { onSelectEpisode(idx) }.padding(vertical = 8.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Image(
                                painter = coil.compose.rememberAsyncImagePainter(ep.thumbnail_url),
                                contentDescription = null,
                                modifier = Modifier.size(width = 64.dp, height = 40.dp).clip(androidx.compose.foundation.shape.RoundedCornerShape(4.dp)),
                                contentScale = ContentScale.Crop
                            )
                            Spacer(Modifier.width(10.dp))
                            Text("EP ${ep.episode_number} · ${ep.title}", color = Color.White, fontSize = 13.sp, modifier = Modifier.weight(1f), maxLines = 1)
                            if (ep.is_unlocked != true) {
                                Icon(Icons.Default.Lock, "Locked", tint = Color(0xFF888888), modifier = Modifier.size(16.dp))
                            }
                        }
                    }
                }
            }
            Spacer(Modifier.height(16.dp))
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun CommentsSheet(
    onDismiss: () -> Unit,
    onLoadComments: (onResult: (List<Comment>, Int) -> Unit) -> Unit,
    onPostComment: (body: String, onResult: (Comment?) -> Unit) -> Unit
) {
    var comments by remember { mutableStateOf<List<Comment>>(emptyList()) }
    var total by remember { mutableIntStateOf(0) }
    var loaded by remember { mutableStateOf(false) }
    var input by remember { mutableStateOf("") }
    var posting by remember { mutableStateOf(false) }

    LaunchedEffect(Unit) {
        onLoadComments { items, count -> comments = items; total = count; loaded = true }
    }

    ModalBottomSheet(onDismissRequest = onDismiss, containerColor = Color(0xFF141416)) {
        Column(modifier = Modifier.fillMaxWidth().heightIn(max = 550.dp).padding(horizontal = 16.dp)) {
            Text("Comments ($total)", color = Color.White, fontSize = 18.sp, fontWeight = FontWeight.Bold)
            Spacer(Modifier.height(8.dp))
            if (!loaded) {
                Box(Modifier.fillMaxWidth().padding(32.dp), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator(color = Color(0xFFDC2626))
                }
            } else if (comments.isEmpty()) {
                Box(Modifier.fillMaxWidth().padding(32.dp), contentAlignment = Alignment.Center) {
                    Text("No comments yet. Be the first!", color = Color(0xFF888888))
                }
            } else {
                LazyColumn(modifier = Modifier.weight(1f, fill = false)) {
                    items(comments.size) { idx ->
                        val c = comments[idx]
                        Column(modifier = Modifier.fillMaxWidth().padding(vertical = 8.dp)) {
                            Row {
                                Text(c.author ?: "Guest", color = Color.White, fontWeight = FontWeight.Bold, fontSize = 13.sp)
                                Spacer(Modifier.width(6.dp))
                                Text(c.body, color = Color(0xFFDDDDDD), fontSize = 13.sp)
                            }
                        }
                    }
                }
            }
            Spacer(Modifier.height(8.dp))
            Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.padding(bottom = 16.dp)) {
                OutlinedTextField(
                    value = input,
                    onValueChange = { input = it },
                    placeholder = { Text("Add a comment...", color = Color(0xFF666666)) },
                    modifier = Modifier.weight(1f),
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(imeAction = ImeAction.Send),
                    colors = OutlinedTextFieldDefaults.colors(
                        focusedTextColor = Color.White, unfocusedTextColor = Color.White,
                        focusedBorderColor = Color(0xFFDC2626), unfocusedBorderColor = Color(0xFF333333)
                    )
                )
                Spacer(Modifier.width(8.dp))
                IconButton(
                    enabled = input.isNotBlank() && !posting,
                    onClick = {
                        val body = input.trim()
                        posting = true
                        onPostComment(body) { comment ->
                            posting = false
                            if (comment != null) {
                                comments = listOf(comment) + comments
                                total += 1
                                input = ""
                            }
                        }
                    }
                ) {
                    Icon(Icons.Outlined.Send, "Post comment", tint = Color(0xFFDC2626))
                }
            }
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

/**
 * VIP subscription — an alternative to buying coins, not a replacement.
 * Shown when a coin unlock fails for insufficient balance, or when the
 * viewer taps "Buy More Coins" from the lock screen. VIP plan cards launch
 * the same Chrome Custom Tab + PaymentResultBus checkout CoinShopScreen uses
 * for coins; on success this retries the normal coin-unlock call for the
 * episode, which now succeeds for free via the server's VIP gating.
 */
@Composable
private fun VipCoinOffersDialog(
    vipPlans: List<com.soloreel.app.data.model.VipPlan>,
    onLoadVipPlans: () -> Unit,
    onPurchaseVip: (planId: Int, onResult: (String?, String?, String?) -> Unit) -> Unit,
    onVerifyVipAndRetry: (reference: String, onSuccess: () -> Unit) -> Unit,
    onUnlockSuccess: () -> Unit,
    onNavigateToCoins: () -> Unit,
    onDismiss: () -> Unit
) {
    val context = LocalContext.current
    var pendingReference by remember { mutableStateOf<String?>(null) }
    var error by remember { mutableStateOf<String?>(null) }

    LaunchedEffect(Unit) { onLoadVipPlans() }

    LaunchedEffect(Unit) {
        PaymentResultBus.events.collect { result ->
            val ref = pendingReference ?: return@collect
            if (result.status == "success") {
                onVerifyVipAndRetry(result.reference ?: ref, onUnlockSuccess)
            }
            pendingReference = null
        }
    }

    Dialog(onDismissRequest = onDismiss) {
        Card(shape = RoundedCornerShape(20.dp), colors = CardDefaults.cardColors(containerColor = Color(0xFF161616))) {
            Column(modifier = Modifier.padding(20.dp).heightIn(max = 480.dp)) {
                Text("VIP Unlock all series for free", color = Color.White, fontSize = 18.sp, fontWeight = FontWeight.Bold)
                Text("Auto renew. Cancel anytime.", color = Color(0xFF888888), fontSize = 12.sp)
                Spacer(Modifier.height(12.dp))

                if (vipPlans.isEmpty()) {
                    Box(Modifier.fillMaxWidth().padding(24.dp), contentAlignment = Alignment.Center) {
                        CircularProgressIndicator(color = Color(0xFFDC2626), modifier = Modifier.size(28.dp))
                    }
                } else {
                    LazyColumn(modifier = Modifier.weight(1f, fill = false)) {
                        items(vipPlans) { plan ->
                            Box(
                                modifier = Modifier.fillMaxWidth().padding(vertical = 6.dp)
                                    .background(Brush.linearGradient(listOf(Color(0xFFFDE68A), Color(0xFFF59E0B))), RoundedCornerShape(14.dp))
                                    .clickable {
                                        error = null
                                        onPurchaseVip(plan.id) { authUrl, reference, err ->
                                            if (authUrl != null && reference != null) {
                                                pendingReference = reference
                                                CustomTabsIntent.Builder().build().launchUrl(context, android.net.Uri.parse(authUrl))
                                            } else {
                                                error = err ?: "Could not start checkout"
                                            }
                                        }
                                    }
                                    .padding(16.dp)
                            ) {
                                Column {
                                    Text(plan.name, color = Color.Black, fontWeight = FontWeight.Bold, fontSize = 15.sp)
                                    Text("${plan.currency} ${String.format("%.2f", plan.price)}", color = Color.Black, fontWeight = FontWeight.ExtraBold, fontSize = 22.sp)
                                    Text("Auto-renew. Cancel anytime.", color = Color(0x99000000), fontSize = 11.sp)
                                }
                            }
                        }
                    }
                }

                error?.let { Text(it, color = Color(0xFFDC2626), fontSize = 12.sp, modifier = Modifier.padding(top = 8.dp)) }

                Spacer(Modifier.height(12.dp))
                Button(
                    onClick = onNavigateToCoins,
                    modifier = Modifier.fillMaxWidth().height(44.dp),
                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFDC2626))
                ) { Text("Top Up Coins Instead", fontWeight = FontWeight.Bold) }

                if (pendingReference != null) {
                    Spacer(Modifier.height(10.dp))
                    Text("Waiting for you to complete payment in your browser...", color = Color(0xFF888888), fontSize = 12.sp, textAlign = TextAlign.Center, modifier = Modifier.fillMaxWidth())
                }

                TextButton(onClick = onDismiss, modifier = Modifier.align(Alignment.CenterHorizontally)) {
                    Text("Cancel", color = Color(0xFF888888))
                }
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
