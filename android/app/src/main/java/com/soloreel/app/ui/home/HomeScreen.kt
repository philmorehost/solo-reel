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
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.PlayArrow
import androidx.compose.material.icons.filled.Search
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
import com.soloreel.app.data.model.CategoryGroup
import com.soloreel.app.data.model.ContinueWatchingItem
import com.soloreel.app.data.model.NewReleases
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
        viewModel.loadTab("hot")
        notificationsViewModel.load(context)
    }

    Box(modifier = Modifier.fillMaxSize()) {
    LazyColumn(
        modifier = Modifier.fillMaxSize().background(Color(0xFF0A0A0A)),
        contentPadding = PaddingValues(bottom = 8.dp)
    ) {
        // Live search — reuses the same debounced /api/v1/search endpoint as SearchScreen,
        // just inline near the top with a compact dropdown instead of a full page.
        item {
            HomeSearchBar(
                query = state.searchQuery,
                results = state.searchResults,
                isSearching = state.isSearching,
                onQueryChange = viewModel::onSearchQueryChange,
                onResultClick = { series -> navigateToSeries(navController, series, state.resumeSlugs) }
            )
        }

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

        // Content Hub Tabs: HOT / NEW / RANKING / CATEGORIES / TV SERIES / MOVIES
        item {
            ContentHubTabs(
                activeTab = state.activeTab,
                onTabSelected = viewModel::selectTab
            )
        }
        item {
            ContentHubTabContent(
                activeTab = state.activeTab,
                tabSeries = state.tabSeries,
                tabNewReleases = state.tabNewReleases,
                tabCategories = state.tabCategories,
                isLoading = state.tabLoading,
                resumeSlugs = state.resumeSlugs,
                onSeriesClick = { series -> navigateToSeries(navController, series, state.resumeSlugs) }
            )
            Spacer(Modifier.height(16.dp))
        }

        // Continue Watching — per-viewer, computed at request time from watch history, never admin-curated.
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

/** Inline live-search near the top of Home — same debounced /api/v1/search endpoint
 * SearchScreen uses, just a compact input + dropdown instead of a full page. */
@Composable
fun HomeSearchBar(
    query: String,
    results: List<Series>,
    isSearching: Boolean,
    onQueryChange: (String) -> Unit,
    onResultClick: (Series) -> Unit
) {
    Column(modifier = Modifier.fillMaxWidth().padding(horizontal = 16.dp, vertical = 8.dp)) {
        OutlinedTextField(
            value = query,
            onValueChange = onQueryChange,
            placeholder = { Text("Search titles...", color = Color(0xFF555555)) },
            leadingIcon = { Icon(Icons.Default.Search, null, tint = Color(0xFF555555)) },
            trailingIcon = {
                if (query.isNotEmpty()) {
                    IconButton(onClick = { onQueryChange("") }) {
                        Icon(Icons.Default.Close, null, tint = Color(0xFF555555))
                    }
                }
            },
            modifier = Modifier.fillMaxWidth(),
            singleLine = true,
            shape = RoundedCornerShape(24.dp),
            colors = OutlinedTextFieldDefaults.colors(
                focusedBorderColor = Color(0xFFDC2626), unfocusedBorderColor = Color(0xFF222222),
                focusedContainerColor = Color(0xFF111111), unfocusedContainerColor = Color(0xFF111111),
                focusedTextColor = Color.White, unfocusedTextColor = Color.White, cursorColor = Color(0xFFDC2626)
            )
        )
        if (query.isNotBlank()) {
            Card(
                modifier = Modifier.fillMaxWidth().padding(top = 4.dp),
                colors = CardDefaults.cardColors(containerColor = Color(0xFF141414)),
                shape = RoundedCornerShape(14.dp)
            ) {
                Column(modifier = Modifier.padding(vertical = 4.dp)) {
                    if (isSearching) {
                        Box(Modifier.fillMaxWidth().padding(16.dp), contentAlignment = Alignment.Center) {
                            CircularProgressIndicator(color = Color(0xFFDC2626), modifier = Modifier.size(20.dp))
                        }
                    } else if (results.isEmpty()) {
                        Text("No titles found.", color = Color(0xFF666666), modifier = Modifier.padding(16.dp))
                    } else {
                        results.forEach { series ->
                            Row(
                                modifier = Modifier.fillMaxWidth().clickable { onResultClick(series) }.padding(10.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Box(modifier = Modifier.width(36.dp).height(50.dp).clip(RoundedCornerShape(6.dp)).background(Color(0xFF222222))) {
                                    if (series.cover_image_url != null) {
                                        Image(painter = rememberAsyncImagePainter(series.cover_image_url), contentDescription = null, modifier = Modifier.fillMaxSize(), contentScale = ContentScale.Crop)
                                    }
                                }
                                Spacer(Modifier.width(10.dp))
                                Column {
                                    Text(series.title, color = Color.White, fontSize = 13.sp, fontWeight = FontWeight.Medium, maxLines = 1)
                                    Text((series.genre ?: "Drama") + " · ${series.episode_count ?: 0} episodes", color = Color(0xFF888888), fontSize = 11.sp)
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

private val homeHubTabs = listOf(
    "hot" to "🔥 HOT",
    "new" to "✨ NEW",
    "ranking" to "🏆 RANKING",
    "categories" to "CATEGORIES",
    "tv_series" to "TV SERIES",
    "movies" to "MOVIES"
)

@Composable
fun ContentHubTabs(activeTab: String, onTabSelected: (String) -> Unit) {
    Row(
        modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()).padding(horizontal = 12.dp, vertical = 8.dp),
        horizontalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        homeHubTabs.forEach { (key, label) ->
            val selected = key == activeTab
            Box(
                modifier = Modifier
                    .clip(RoundedCornerShape(20.dp))
                    .background(if (selected) Color(0xFFDC2626) else Color(0xFF1A1A1A))
                    .clickable { onTabSelected(key) }
                    .padding(horizontal = 16.dp, vertical = 8.dp)
            ) {
                Text(label, color = if (selected) Color.White else Color(0xFFB0B0B0), fontSize = 13.sp, fontWeight = FontWeight.Bold)
            }
        }
    }
}

@Composable
fun ContentHubTabContent(
    activeTab: String,
    tabSeries: List<Series>,
    tabNewReleases: NewReleases?,
    tabCategories: List<CategoryGroup>,
    isLoading: Boolean,
    resumeSlugs: Map<Int, String>,
    onSeriesClick: (Series) -> Unit
) {
    if (isLoading) {
        Box(Modifier.fillMaxWidth().padding(32.dp), contentAlignment = Alignment.Center) {
            CircularProgressIndicator(color = Color(0xFFDC2626))
        }
        return
    }

    when (activeTab) {
        "new" -> {
            Column {
                if (!tabNewReleases?.coming_soon.isNullOrEmpty()) {
                    Text("🔜 Coming Soon", color = Color.White, fontSize = 16.sp, fontWeight = FontWeight.Bold, modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp))
                    HubSeriesRow(tabNewReleases!!.coming_soon, onSeriesClick)
                }
                if (!tabNewReleases?.all_new.isNullOrEmpty()) {
                    Text("✨ New Releases", color = Color.White, fontSize = 16.sp, fontWeight = FontWeight.Bold, modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp))
                    HubSeriesRow(tabNewReleases!!.all_new, onSeriesClick)
                }
                if (tabNewReleases?.coming_soon.isNullOrEmpty() && tabNewReleases?.all_new.isNullOrEmpty()) {
                    Text("No new titles yet.", color = Color(0xFF666666), modifier = Modifier.padding(32.dp))
                }
            }
        }
        "ranking" -> {
            if (tabSeries.isEmpty()) {
                Text("No rankings yet — be the first to like a series!", color = Color(0xFF666666), modifier = Modifier.padding(32.dp))
            } else {
                Column(modifier = Modifier.padding(horizontal = 16.dp)) {
                    tabSeries.forEachIndexed { index, series ->
                        RankingRow(series, index + 1) { onSeriesClick(series) }
                        Spacer(Modifier.height(8.dp))
                    }
                }
            }
        }
        "categories" -> {
            if (tabCategories.isEmpty()) {
                Text("No categories yet.", color = Color(0xFF666666), modifier = Modifier.padding(32.dp))
            } else {
                Column {
                    tabCategories.forEach { group ->
                        Text(group.genre, color = Color.White, fontSize = 16.sp, fontWeight = FontWeight.Bold, modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp))
                        HubSeriesRow(group.series, onSeriesClick)
                    }
                }
            }
        }
        else -> {
            // hot / tv_series / movies — same grid treatment, chunked(3) rows
            // to avoid nesting a same-axis scrollable inside the outer LazyColumn.
            if (tabSeries.isEmpty()) {
                Text("Nothing here yet.", color = Color(0xFF666666), modifier = Modifier.padding(32.dp))
            } else {
                Column(modifier = Modifier.padding(horizontal = 12.dp)) {
                    tabSeries.chunked(3).forEach { rowSeries ->
                        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            rowSeries.forEach { series ->
                                Box(modifier = Modifier.weight(1f)) {
                                    HubGridCard(series) { onSeriesClick(series) }
                                }
                            }
                            repeat(3 - rowSeries.size) { Spacer(Modifier.weight(1f)) }
                        }
                        Spacer(Modifier.height(12.dp))
                    }
                }
            }
        }
    }
}

@Composable
private fun HubSeriesRow(series: List<Series>, onSeriesClick: (Series) -> Unit) {
    LazyRow(contentPadding = PaddingValues(horizontal = 12.dp)) {
        items(series) { s -> SeriesCard(s) { onSeriesClick(s) } }
    }
    Spacer(Modifier.height(8.dp))
}

/** Same footprint as GridSeriesCard, with HOT/NEW badge overlays. */
@Composable
private fun HubGridCard(series: Series, onClick: () -> Unit) {
    Column(modifier = Modifier.fillMaxWidth().clickable(onClick = onClick)) {
        Box(modifier = Modifier.fillMaxWidth().height(170.dp).clip(RoundedCornerShape(12.dp)).background(Color(0xFF1A1A1A))) {
            if (series.cover_image_url != null) {
                Image(painter = rememberAsyncImagePainter(series.cover_image_url), contentDescription = null, modifier = Modifier.fillMaxSize(), contentScale = ContentScale.Crop)
            }
            Row(modifier = Modifier.padding(6.dp).align(Alignment.TopStart), horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                if (series.is_hot == true) {
                    Box(modifier = Modifier.background(Color(0xFFDC2626), RoundedCornerShape(4.dp)).padding(horizontal = 6.dp, vertical = 2.dp)) {
                        Text("🔥 HOT", color = Color.White, fontSize = 9.sp, fontWeight = FontWeight.Bold)
                    }
                }
                if (series.is_new == true) {
                    Box(modifier = Modifier.background(Color(0xFF059669), RoundedCornerShape(4.dp)).padding(horizontal = 6.dp, vertical = 2.dp)) {
                        Text("NEW", color = Color.White, fontSize = 9.sp, fontWeight = FontWeight.Bold)
                    }
                }
            }
        }
        Spacer(Modifier.height(6.dp))
        Text(series.title, color = Color.White, fontSize = 13.sp, maxLines = 1, fontWeight = FontWeight.SemiBold)
        series.genre?.let { Text(it, color = Color(0xFF888888), fontSize = 11.sp, maxLines = 1) }
    }
}

/** RANKING tab row: rank number, cover thumb, title, like counter. */
@Composable
private fun RankingRow(series: Series, rank: Int, onClick: () -> Unit) {
    Row(
        modifier = Modifier.fillMaxWidth().clip(RoundedCornerShape(12.dp)).background(Color(0xFF141414)).clickable(onClick = onClick).padding(10.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Text(
            "$rank", color = if (rank <= 3) Color(0xFFEAB308) else Color(0xFF888888),
            fontSize = 20.sp, fontWeight = FontWeight.ExtraBold, modifier = Modifier.width(32.dp), textAlign = androidx.compose.ui.text.style.TextAlign.Center
        )
        Box(modifier = Modifier.width(52.dp).height(74.dp).clip(RoundedCornerShape(8.dp)).background(Color(0xFF222222))) {
            if (series.cover_image_url != null) {
                Image(painter = rememberAsyncImagePainter(series.cover_image_url), contentDescription = null, modifier = Modifier.fillMaxSize(), contentScale = ContentScale.Crop)
            }
        }
        Spacer(Modifier.width(12.dp))
        Column(modifier = Modifier.weight(1f)) {
            Text(series.title, color = Color.White, fontSize = 14.sp, fontWeight = FontWeight.SemiBold, maxLines = 1)
            Text("EP.${series.episode_count ?: 0}", color = Color(0xFF888888), fontSize = 12.sp)
        }
        Text("❤ ${series.like_count ?: 0}", color = Color(0xFFDC2626), fontSize = 13.sp, fontWeight = FontWeight.Bold)
    }
}
