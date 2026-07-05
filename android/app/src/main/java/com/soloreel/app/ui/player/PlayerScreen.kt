package com.soloreel.app.ui.player

import androidx.compose.foundation.layout.*
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color

@Composable
fun PlayerScreen(episodeSlug: String) {
    // Stub for ExoPlayer Media3 integration
    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
        Text("ExoPlayer Loading: $episodeSlug", color = Color.White)
    }
}
