package com.soloreel.app.ui.home

import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.itemsIndexed
import androidx.compose.foundation.pager.HorizontalPager
import androidx.compose.foundation.pager.rememberPagerState
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.PlayArrow
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.navigation.NavHostController
import androidx.compose.ui.platform.LocalContext
import coil.compose.rememberAsyncImagePainter
import com.soloreel.app.data.model.Banner
import com.soloreel.app.data.model.ContinueWatchingItem
import com.soloreel.app.data.model.Series
import com.soloreel.app.ui.navigation.Screen
import com.soloreel.app.ui.notifications.NotificationBell
import com.soloreel.app.ui.notifications.NotificationsViewModel
import kotlinx.coroutines.launch
import androidx.compose.foundation.ExperimentalFoundationApi

@OptIn(ExperimentalFoundationApi::class)
@Composable
fun HomeScreen(
    navController: NavHostController,
    viewModel: HomeViewModel = hiltViewModel(),
    notificationsViewModel: NotificationsViewModel = hiltViewModel()
) {
    val state by viewModel.state.collectAsState()
    val notifState by notificationsViewModel.state.collectAsState()
    val context = LocalContext.current
    val coroutineScope = rememberCoroutineScope()
    LaunchedEffect(Unit) {
        viewModel.load()
        notificationsViewModel.load(context)
    }

    Box(modifier = Modifier.fillMaxSize()) {
    LazyColumn(
        modifier = Modifier.fillMaxSize().background(Color(0xFF0A0A0A)),
        contentPadding = PaddingValues(bottom = 8.dp)
    ) {
        // Hero Banner Carousel
        item {
            if (state.banners.isNotEmpty()) {
                var currentBanner by remember { mutableStateOf(0) }
                LaunchedEffect(state.banners) {
                    while (true) {
                        val waitMs = ((state.banners[currentBanner].duration_seconds ?: 5).coerceIn(1, 60)) * 1000L
                        kotlinx.coroutines.delay(waitMs)
                        currentBanner = (currentBanner + 1) % state.banners.size
                    }
                }
                val banner = state.banners[currentBanner]
                val isAd = banner.is_ad == true
                val onBannerClick = {
                    if (isAd) {
                        banner.link_url?.let { url ->
                            try { context.startActivity(android.content.Intent(android.content.Intent.ACTION_VIEW, android.net.Uri.parse(url))) } catch (_: Exception) {}
                        }
                    } else {
                        banner.link_url?.substringAfterLast("/")?.let { slug ->
                            coroutineScope.launch {
                                val resumeSlug = viewModel.resolveBannerTarget(slug)
                                if (resumeSlug != null) {
                                    navController.navigate(Screen.EpisodePlayer.createRoute(resumeSlug))
                                } else {
                                    navController.navigate(Screen.SeriesDetail.createRoute(slug))
                                }
                            }
                        }
                    }
                }
                Box(
                    modifier = Modifier.fillMaxWidth().height(500.dp).clickable { onBannerClick() },
                    contentAlignment = Alignment.BottomStart
                ) {
                    if (banner.media_type == "video" && !banner.image_url.isNullOrBlank()) {
                        MutedLoopingVideo(url = banner.image_url, modifier = Modifier.fillMaxSize())
                    } else {
                        Image(
                            painter = rememberAsyncImagePainter(banner.image_url),
                            contentDescription = null,
                            modifier = Modifier.fillMaxSize(),
                            contentScale = ContentScale.Crop
                        )
                    }
                    Box(
                        modifier = Modifier.fillMaxSize()
                            .background(Brush.verticalGradient(
                                colors = listOf(Color.Transparent, Color(0x66000000), Color(0xFF0A0A0A)),
                                startY = 300f
                            ))
                    )
                    Column(modifier = Modifier.padding(24.dp).padding(bottom = 16.dp)) {
                        if (isAd) {
                            Text("SPONSORED", color = Color(0xFFEAB308), fontSize = 12.sp, fontWeight = FontWeight.Bold, style = androidx.compose.ui.text.TextStyle(letterSpacing = 2.sp))
                            Spacer(modifier = Modifier.height(4.dp))
                        }
                        banner.title?.let { Text(it, color = Color.White, fontSize = 36.sp, fontWeight = FontWeight.Black, style = androidx.compose.ui.text.TextStyle(letterSpacing = 1.sp)) }
                        Spacer(modifier = Modifier.height(8.dp))
                        banner.subtitle?.let { Text(it, color = Color(0xEEFFFFFF), fontSize = 16.sp, maxLines = 2) }
                        Spacer(modifier = Modifier.height(20.dp))
                        Button(
                            onClick = { onBannerClick() },
                            modifier = Modifier.width(160.dp).height(48.dp),
                            shape = RoundedCornerShape(8.dp),
                            colors = ButtonDefaults.buttonColors(containerColor = Color.White, contentColor = Color.Black)
                        ) {
                            if (!isAd) {
                                Icon(androidx.compose.material.icons.Icons.Default.PlayArrow, contentDescription = "Play", modifier = Modifier.size(24.dp))
                                Spacer(modifier = Modifier.width(8.dp))
                            }
                            Text(if (isAd) "Learn More" else "Play", fontWeight = FontWeight.Bold, fontSize = 18.sp)
                        }
                    }
                }
            }
        }

        // Continue Watching — per-viewer, computed at request time from watch
        // history, never admin-curated. Always rendered above Latest Release.
        if (state.continueWatching.isNotEmpty()) {
            item { Text("Continue Watching", color = Color.White, fontSize = 18.sp, fontWeight = FontWeight.Bold, modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)) }
            item {
                LazyRow(contentPadding = PaddingValues(horizontal = 12.dp)) {
                    items(state.continueWatching) { item ->
                        ContinueWatchingCard(item) {
                            navController.navigate(Screen.EpisodePlayer.createRoute(item.episode_slug))
                        }
                    }
                }
                Spacer(Modifier.height(16.dp))
            }
        }

        // Latest releases row — distinct from the "Trending Now" shelf below,
        // which comes from the admin-managed shelves and carries the same name.
        if (state.series.isNotEmpty()) {
            item { Text("Latest Release", color = Color.White, fontSize = 18.sp, fontWeight = FontWeight.Bold, modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)) }
            item {
                LazyRow(contentPadding = PaddingValues(horizontal = 12.dp)) {
                    items(state.series) { series ->
                        SeriesCard(series) {
                            navigateToSeries(navController, series, state.resumeSlugs)
                        }
                    }
                }
                Spacer(Modifier.height(16.dp))
            }
        }

        // Show a horizontal row for each shelf
        items(state.shelves) { shelf ->
            val seriesForShelf = state.shelfSeries[shelf.slug] ?: emptyList()
            if (seriesForShelf.isNotEmpty()) {
                Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)) {
                    if (!shelf.emoji.isNullOrBlank()) {
                        Text(shelf.emoji, fontSize = 18.sp)
                        Spacer(Modifier.width(8.dp))
                    }
                    Text(shelf.name, color = Color.White, fontSize = 18.sp, fontWeight = FontWeight.Bold)
                }
                LazyRow(contentPadding = PaddingValues(horizontal = 12.dp)) {
                    items(seriesForShelf) { series ->
                        SeriesCard(series) {
                            navigateToSeries(navController, series, state.resumeSlugs)
                        }
                    }
                }
                Spacer(Modifier.height(16.dp))
            }
        }

        // All Series grid — every series again in a browsable vertical grid.
        if (state.series.isNotEmpty()) {
            item {
                Text("All Series", color = Color.White, fontSize = 18.sp, fontWeight = FontWeight.Bold, modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp))
            }
            items(state.series.chunked(3)) { rowSeries ->
                Row(
                    modifier = Modifier.fillMaxWidth().padding(horizontal = 12.dp),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    rowSeries.forEach { series ->
                        Box(modifier = Modifier.weight(1f)) {
                            GridSeriesCard(series) {
                                navigateToSeries(navController, series, state.resumeSlugs)
                            }
                        }
                    }
                    repeat(3 - rowSeries.size) { Spacer(Modifier.weight(1f)) }
                }
                Spacer(Modifier.height(12.dp))
            }
        }

        if (state.isLoading) {
            item { Box(Modifier.fillMaxWidth().padding(32.dp), contentAlignment = Alignment.Center) { CircularProgressIndicator(color = Color(0xFFDC2626)) } }
        }
        state.error?.let { err ->
            item { Box(Modifier.fillMaxWidth().padding(32.dp), contentAlignment = Alignment.Center) { Text(err, color = Color.Red) } }
        }
    }


    }
}

/** Skip-to-player: jump straight into the reel feed at the viewer's resume
 * episode, falling back to the series-detail screen if the batch lookup
 * hasn't resolved an entry for this series yet (still loading, or failed). */
fun navigateToSeries(navController: NavHostController, series: Series, resumeSlugs: Map<Int, String>) {
    val resumeSlug = resumeSlugs[series.id]
    if (resumeSlug != null) {
        navController.navigate(Screen.EpisodePlayer.createRoute(resumeSlug))
    } else {
        navController.navigate(Screen.SeriesDetail.createRoute(series.slug))
    }
}

@Composable
fun SeriesCard(series: Series, onClick: () -> Unit) {
    Column(
        modifier = Modifier.width(140.dp).padding(horizontal = 4.dp).clickable(onClick = onClick)
    ) {
        Box(
            modifier = Modifier.fillMaxWidth().height(220.dp).clip(RoundedCornerShape(12.dp)).background(Color(0xFF1A1A1A))
        ) {
            if (series.cover_image_url != null) {
                Image(
                    painter = rememberAsyncImagePainter(series.cover_image_url),
                    contentDescription = null,
                    modifier = Modifier.fillMaxSize(),
                    contentScale = ContentScale.Crop
                )
                Box(
                    modifier = Modifier.fillMaxSize()
                        .background(Brush.verticalGradient(listOf(Color.Transparent, Color.Transparent, Color(0xAA000000))))
                )
                Box(
                    modifier = Modifier.padding(8.dp).align(Alignment.TopStart).background(Color(0x99000000), RoundedCornerShape(4.dp)).padding(horizontal = 6.dp, vertical = 2.dp)
                ) {
                    Text("EPISODE", color = Color.White, fontSize = 10.sp, fontWeight = FontWeight.Bold)
                }
            }
        }
        Spacer(Modifier.height(8.dp))
        Text(series.title, color = Color.White, fontSize = 14.sp, maxLines = 2, fontWeight = FontWeight.SemiBold)
    }
}

/** Same footprint as SeriesCard, but links straight into the resume episode instead of the series detail page. */
@Composable
fun ContinueWatchingCard(item: ContinueWatchingItem, onClick: () -> Unit) {
    Column(
        modifier = Modifier.width(140.dp).padding(horizontal = 4.dp).clickable(onClick = onClick)
    ) {
        Box(
            modifier = Modifier.fillMaxWidth().height(220.dp).clip(RoundedCornerShape(12.dp)).background(Color(0xFF1A1A1A))
        ) {
            if (item.cover_image_url != null) {
                Image(
                    painter = rememberAsyncImagePainter(item.cover_image_url),
                    contentDescription = null,
                    modifier = Modifier.fillMaxSize(),
                    contentScale = ContentScale.Crop
                )
                Box(
                    modifier = Modifier.fillMaxSize()
                        .background(Brush.verticalGradient(listOf(Color.Transparent, Color.Transparent, Color(0xAA000000))))
                )
                Box(
                    modifier = Modifier.padding(8.dp).align(Alignment.TopStart).background(Color(0x99000000), RoundedCornerShape(4.dp)).padding(horizontal = 6.dp, vertical = 2.dp)
                ) {
                    Text("EP.${item.episode_number ?: 1} / EP.${item.episode_count ?: 1}", color = Color.White, fontSize = 10.sp, fontWeight = FontWeight.Bold)
                }
            }
        }
        Spacer(Modifier.height(8.dp))
        Text(item.title, color = Color.White, fontSize = 14.sp, maxLines = 2, fontWeight = FontWeight.SemiBold)
    }
}

/** Full-width series card for the All Series grid (flexible width, unlike SeriesCard's fixed 140dp). */
@Composable
fun GridSeriesCard(series: Series, onClick: () -> Unit) {
    Column(modifier = Modifier.fillMaxWidth().clickable(onClick = onClick)) {
        Box(
            modifier = Modifier.fillMaxWidth().height(170.dp).clip(RoundedCornerShape(12.dp)).background(Color(0xFF1A1A1A))
        ) {
            if (series.cover_image_url != null) {
                Image(
                    painter = rememberAsyncImagePainter(series.cover_image_url),
                    contentDescription = null,
                    modifier = Modifier.fillMaxSize(),
                    contentScale = ContentScale.Crop
                )
            }
        }
        Spacer(Modifier.height(6.dp))
        Text(series.title, color = Color.White, fontSize = 13.sp, maxLines = 1, fontWeight = FontWeight.SemiBold)
        series.genre?.let { Text(it, color = Color(0xFF888888), fontSize = 11.sp, maxLines = 1) }
    }
}

/** Muted, looping, no-controls video for banner/ad placements — not a full player. */
@androidx.media3.common.util.UnstableApi
@Composable
fun MutedLoopingVideo(url: String, modifier: Modifier = Modifier) {
    val context = LocalContext.current
    var exoPlayer by remember(url) { mutableStateOf<androidx.media3.exoplayer.ExoPlayer?>(null) }

    androidx.compose.ui.viewinterop.AndroidView(
        modifier = modifier,
        factory = { ctx ->
            androidx.media3.exoplayer.ExoPlayer.Builder(ctx).build().apply {
                exoPlayer = this
                setMediaItem(androidx.media3.common.MediaItem.fromUri(android.net.Uri.parse(url)))
                volume = 0f
                repeatMode = androidx.media3.common.Player.REPEAT_MODE_ONE
                prepare()
                playWhenReady = true
            }
            androidx.media3.ui.PlayerView(ctx).apply {
                player = exoPlayer
                useController = false
                resizeMode = androidx.media3.ui.AspectRatioFrameLayout.RESIZE_MODE_ZOOM
            }
        },
        update = { view -> view.player = exoPlayer }
    )

    DisposableEffect(url) {
        onDispose { exoPlayer?.release() }
    }
}
