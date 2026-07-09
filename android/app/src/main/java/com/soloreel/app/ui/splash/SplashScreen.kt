package com.soloreel.app.ui.splash

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.viewinterop.AndroidView
import androidx.media3.common.MediaItem
import androidx.media3.common.Player
import androidx.media3.common.util.UnstableApi
import androidx.media3.exoplayer.ExoPlayer
import androidx.media3.ui.AspectRatioFrameLayout
import androidx.media3.ui.PlayerView
import kotlinx.coroutines.delay

/** Video splash — plays once on launch, then hands off to NavGraph.
 * Muted (consistent with this app's other decorative/background video
 * convention — see MutedLoopingVideoView in HomeScreen.kt), with a fallback
 * timeout in case playback fails to start or the video runs unexpectedly long. */
@UnstableApi
@Composable
fun SplashScreen(onFinished: () -> Unit) {
    val context = LocalContext.current
    var finished by remember { mutableStateOf(false) }

    fun finish() {
        if (!finished) {
            finished = true
            onFinished()
        }
    }

    val exoPlayer = remember {
        ExoPlayer.Builder(context).build().apply {
            val uri = android.net.Uri.parse("android.resource://${context.packageName}/${com.soloreel.app.R.raw.splash_video}")
            setMediaItem(MediaItem.fromUri(uri))
            volume = 0f
            prepare()
            playWhenReady = true
        }
    }

    DisposableEffect(Unit) {
        val listener = object : Player.Listener {
            override fun onPlaybackStateChanged(playbackState: Int) {
                if (playbackState == Player.STATE_ENDED) finish()
            }
            override fun onPlayerError(error: androidx.media3.common.PlaybackException) {
                finish()
            }
        }
        exoPlayer.addListener(listener)
        onDispose {
            exoPlayer.removeListener(listener)
            exoPlayer.release()
        }
    }

    // Fallback: if playback never fires STATE_ENDED (e.g. a malformed file),
    // don't strand the user on the splash screen forever.
    LaunchedEffect(Unit) {
        delay(8000)
        finish()
    }

    AndroidView(
        modifier = Modifier.fillMaxSize().background(Color.Black),
        factory = { ctx ->
            PlayerView(ctx).apply {
                player = exoPlayer
                useController = false
                resizeMode = AspectRatioFrameLayout.RESIZE_MODE_ZOOM
            }
        }
    )
}
