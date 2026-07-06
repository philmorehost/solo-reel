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
import androidx.compose.material.icons.filled.Lock
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.navigation.NavHostController
import androidx.media3.common.MediaItem
import androidx.media3.common.util.UnstableApi
import androidx.media3.exoplayer.ExoPlayer
import androidx.media3.ui.AspectRatioFrameLayout
import androidx.media3.ui.PlayerView
import com.soloreel.app.data.api.SOLOREELApi
import com.soloreel.app.data.api.apiMessage
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
    
    private var currentSlug: String? = null

    fun load(slug: String) {
        currentSlug = slug
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

    fun unlockWithCoins(episodeId: Int, onSuccess: () -> Unit, onInsufficientCoins: () -> Unit) {
        viewModelScope.launch {
            _state.value = _state.value.copy(isLoading = true)
            try {
                val r = api.unlockWithCoins(episodeId)
                if (r.status == true) {
                    currentSlug?.let { load(it) }
                    onSuccess()
                } else {
                    _state.value = _state.value.copy(error = r.error ?: r.message ?: "Failed to unlock", isLoading = false)
                }
            } catch (e: Exception) {
                val msg = e.apiMessage(e.message ?: "Unknown error")
                if (msg.contains("Insufficient coins", ignoreCase = true)) {
                    _state.value = _state.value.copy(isLoading = false)
                    onInsufficientCoins()
                } else {
                    _state.value = _state.value.copy(error = msg, isLoading = false)
                }
            }
        }
    }

    fun unlockWithAd(episodeId: Int, onSuccess: () -> Unit) {
        viewModelScope.launch {
            _state.value = _state.value.copy(isLoading = true)
            try {
                val r = api.unlockWithAd(episodeId)
                if (r.status == true) {
                    currentSlug?.let { load(it) }
                    onSuccess()
                } else {
                    _state.value = _state.value.copy(error = r.error ?: r.message ?: "Failed to unlock with ad", isLoading = false)
                }
            } catch (e: Exception) {
                _state.value = _state.value.copy(error = e.message, isLoading = false)
            }
        }
    }
}

@UnstableApi
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PlayerScreen(slug: String, navController: NavHostController, vm: PlayerViewModel = hiltViewModel()) {
    val state by vm.state.collectAsState()
    LaunchedEffect(slug) { vm.load(slug) }

    Column(modifier = Modifier.fillMaxSize().background(Color.Black)) {
        TopAppBar(
            title = { Text(state.episode?.title ?: "Player", color = Color.White) },
            navigationIcon = { IconButton(onClick = { navController.popBackStack() }) { Icon(Icons.Default.ArrowBack, null, tint = Color.White) } },
            colors = TopAppBarDefaults.topAppBarColors(containerColor = Color.Black)
        )

        if (state.isLoading) {
            Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) { CircularProgressIndicator(color = Color(0xFFDC2626)) }
        } else if (state.error != null) {
            Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) { Text(state.error!!, color = Color.Red) }
        } else if (state.episode != null) {
            if (state.episode?.is_unlocked == true && state.episode?.video_hls_url != null) {
                val context = androidx.compose.ui.platform.LocalContext.current
                var exoPlayer by remember(slug) { mutableStateOf<ExoPlayer?>(null) }
    
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
            } else {
                // Unlock UI
                Box(modifier = Modifier.fillMaxWidth().aspectRatio(9f / 16f).background(Color(0xFF111111)), contentAlignment = Alignment.Center) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally, modifier = Modifier.padding(16.dp)) {
                        val unlockMethod = state.episode?.unlock_method ?: "coins"
                        Icon(Icons.Default.Lock, "Locked", tint = Color(0xFFEAB308), modifier = Modifier.size(48.dp))
                        Spacer(Modifier.height(16.dp))
                        Text("Unlock Episode", color = Color.White, fontSize = 20.sp, fontWeight = FontWeight.Bold)
                        Spacer(Modifier.height(8.dp))
                        
                        val costText = if (unlockMethod == "ads") "This episode can be unlocked by watching a short ad." else "This episode requires ${state.episode?.coin_cost?.toInt() ?: 0} coins to unlock."
                        Text(costText, color = Color.Gray, fontSize = 14.sp)
                        Spacer(Modifier.height(24.dp))
                        
                        if (unlockMethod == "coins" || unlockMethod == "both") {
                            Button(
                                onClick = { 
                                    vm.unlockWithCoins(
                                        episodeId = state.episode!!.id, 
                                        onSuccess = {},
                                        onInsufficientCoins = { navController.navigate(com.soloreel.app.ui.navigation.Screen.Coins.route) }
                                    ) 
                                },
                                modifier = Modifier.fillMaxWidth(0.8f).height(48.dp),
                                colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFDC2626))
                            ) {
                                Text("Unlock for ${state.episode?.coin_cost?.toInt() ?: 0} Coins", fontWeight = FontWeight.Bold)
                            }
                            Spacer(Modifier.height(12.dp))
                        }
                        
                        if (unlockMethod == "ads" || unlockMethod == "both") {
                            OutlinedButton(
                                onClick = { vm.unlockWithAd(state.episode!!.id) {} },
                                modifier = Modifier.fillMaxWidth(0.8f).height(48.dp),
                                colors = ButtonDefaults.outlinedButtonColors(contentColor = Color.White),
                                border = androidx.compose.foundation.BorderStroke(1.dp, Color.White)
                            ) {
                                Text("Watch Ad to Unlock", fontWeight = FontWeight.Bold)
                            }
                            Spacer(Modifier.height(12.dp))
                        }
                        
                        TextButton(onClick = { navController.navigate(com.soloreel.app.ui.navigation.Screen.Coins.route) }) {
                            Text("Buy More Coins", color = Color(0xFFEAB308), fontWeight = FontWeight.Bold)
                        }
                    }
                }
            }

            Column(modifier = Modifier.padding(16.dp)) {
                Text(state.episode!!.title, color = Color.White, style = MaterialTheme.typography.titleLarge)
                Text(state.episode!!.series_title ?: "", color = Color.Gray, style = MaterialTheme.typography.bodyMedium)
                state.episode!!.description?.let { Text(it, color = Color.LightGray, modifier = Modifier.padding(top = 8.dp)) }
            }
        }
    }
}
