package com.soloreel.app.ui.home

import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
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
import coil.compose.rememberAsyncImagePainter
import com.soloreel.app.data.model.Banner
import com.soloreel.app.data.model.Series
import com.soloreel.app.ui.navigation.Screen

@Composable
fun HomeScreen(navController: NavHostController, viewModel: HomeViewModel = hiltViewModel()) {
    val state by viewModel.state.collectAsState()
    LaunchedEffect(Unit) { viewModel.load() }

    LazyColumn(
        modifier = Modifier.fillMaxSize().background(Color(0xFF0A0A0A)),
        contentPadding = PaddingValues(bottom = 8.dp)
    ) {
        // Hero Banner Carousel
        item {
            if (state.banners.isNotEmpty()) {
                var currentBanner by remember { mutableStateOf(0) }
                LaunchedEffect(Unit) {
                    while (true) { kotlinx.coroutines.delay(4000); currentBanner = (currentBanner + 1) % state.banners.size }
                }
                val banner = state.banners[currentBanner]
                Box(
                    modifier = Modifier.fillMaxWidth().height(350.dp),
                    contentAlignment = Alignment.BottomStart
                ) {
                    Image(
                        painter = rememberAsyncImagePainter(banner.image_url),
                        contentDescription = null,
                        modifier = Modifier.fillMaxSize(),
                        contentScale = ContentScale.Crop
                    )
                    Box(
                        modifier = Modifier.fillMaxSize()
                            .background(Brush.verticalGradient(listOf(Color.Transparent, Color(0xFF0A0A0A))))
                    )
                    Column(modifier = Modifier.padding(16.dp)) {
                        banner.title?.let { Text(it, color = Color.White, fontSize = 24.sp, fontWeight = FontWeight.Bold) }
                        banner.subtitle?.let { Text(it, color = Color.LightGray, fontSize = 14.sp) }
                    }
                }
            }
        }

        // Series Shelves
        if (state.series.isNotEmpty()) {
            item { Text("Trending Now", color = Color.White, fontSize = 18.sp, fontWeight = FontWeight.Bold, modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)) }
            item {
                LazyRow(contentPadding = PaddingValues(horizontal = 12.dp)) {
                    items(state.series) { series ->
                        SeriesCard(series) {
                            navController.navigate(Screen.SeriesDetail.createRoute(series.slug))
                        }
                    }
                }
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

@Composable
fun SeriesCard(series: Series, onClick: () -> Unit) {
    Column(
        modifier = Modifier.width(140.dp).padding(horizontal = 4.dp).clickable(onClick = onClick)
    ) {
        Box(
            modifier = Modifier.fillMaxWidth().height(200.dp).clip(RoundedCornerShape(12.dp)).background(Color(0xFF1A1A1A))
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
        Spacer(Modifier.height(4.dp))
        Text(series.title, color = Color.White, fontSize = 13.sp, maxLines = 2, fontWeight = FontWeight.Medium)
    }
}
