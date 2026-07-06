package com.soloreel.app.ui.advertise

import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.navigation.NavHostController
import coil.compose.rememberAsyncImagePainter
import com.soloreel.app.data.api.SOLOREELApi
import com.soloreel.app.data.model.MyAd
import com.soloreel.app.ui.coins.PaymentWebView
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class MyAdsState(
    val ads: List<MyAd> = emptyList(),
    val isLoading: Boolean = true,
    val paymentUrl: String? = null,
    val paymentRef: String? = null
)

@HiltViewModel
class MyAdsViewModel @Inject constructor(private val api: SOLOREELApi) : ViewModel() {
    private val _state = MutableStateFlow(MyAdsState())
    val state: StateFlow<MyAdsState> = _state.asStateFlow()

    fun load() {
        viewModelScope.launch {
            _state.value = _state.value.copy(isLoading = true)
            try {
                val r = api.getMyAds()
                _state.value = _state.value.copy(ads = r.data ?: emptyList(), isLoading = false)
            } catch (e: Exception) {
                _state.value = _state.value.copy(isLoading = false)
            }
        }
    }

    fun renew(adId: Int) {
        viewModelScope.launch {
            try {
                val r = api.renewAd(adId)
                r.data?.authorization_url?.let { url ->
                    _state.value = _state.value.copy(paymentUrl = url, paymentRef = r.data.reference)
                }
            } catch (_: Exception) { }
        }
    }

    fun onPaymentSuccess(ref: String) {
        viewModelScope.launch {
            try { api.verifyPayment(ref) } catch (_: Exception) { }
            _state.value = _state.value.copy(paymentUrl = null)
            load()
        }
    }

    fun dismissPayment() { _state.value = _state.value.copy(paymentUrl = null) }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun MyAdsScreen(navController: NavHostController, vm: MyAdsViewModel = hiltViewModel()) {
    val state by vm.state.collectAsState()
    LaunchedEffect(Unit) { vm.load() }

    if (state.paymentUrl != null) {
        PaymentWebView(
            url = state.paymentUrl!!,
            onSuccess = { ref -> vm.onPaymentSuccess(state.paymentRef ?: ref) },
            onDismiss = { vm.dismissPayment() }
        )
        return
    }

    Column(modifier = Modifier.fillMaxSize().background(Color(0xFF0A0A0A))) {
        TopAppBar(
            title = { Text("My Ads", color = Color.White) },
            navigationIcon = { IconButton(onClick = { navController.popBackStack() }) { Icon(Icons.Default.ArrowBack, null, tint = Color.White) } },
            actions = {
                TextButton(onClick = { navController.navigate(com.soloreel.app.ui.navigation.Screen.Advertise.route) }) {
                    Text("New Ad", color = Color(0xFFDC2626), fontWeight = FontWeight.Bold)
                }
            },
            colors = TopAppBarDefaults.topAppBarColors(containerColor = Color.Black)
        )

        if (state.isLoading) {
            Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) { CircularProgressIndicator(color = Color(0xFFDC2626)) }
        } else if (state.ads.isEmpty()) {
            Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                Text("You haven't created any ads yet.", color = Color(0xFF999999))
            }
        } else {
            LazyColumn(modifier = Modifier.fillMaxSize().padding(16.dp)) {
                items(state.ads) { ad -> MyAdCard(ad, onRenew = { vm.renew(ad.id) }) }
            }
        }
    }
}

@Composable
fun MyAdCard(ad: MyAd, onRenew: () -> Unit) {
    val statusLabel = when {
        ad.payment_status == "pending" -> "Awaiting Payment"
        ad.is_expired -> "Expired"
        ad.is_active -> "Active"
        else -> "Inactive"
    }
    val statusColor = when {
        ad.payment_status == "pending" -> Color(0xFFEAB308)
        ad.is_expired -> Color(0xFF666666)
        ad.is_active -> Color(0xFF22C55E)
        else -> Color(0xFF666666)
    }

    Card(
        modifier = Modifier.fillMaxWidth().padding(vertical = 6.dp),
        colors = CardDefaults.cardColors(containerColor = Color(0xFF1A1A1A)),
        shape = RoundedCornerShape(12.dp)
    ) {
        Row(modifier = Modifier.padding(12.dp).fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
            Box(modifier = Modifier.size(width = 90.dp, height = 54.dp).clip(RoundedCornerShape(8.dp)).background(Color(0xFF0A0A0A))) {
                ad.media_url?.let {
                    Image(painter = rememberAsyncImagePainter(it), contentDescription = null, modifier = Modifier.fillMaxSize(), contentScale = ContentScale.Crop)
                }
            }
            Spacer(Modifier.width(12.dp))
            Column(modifier = Modifier.weight(1f)) {
                Text(ad.title ?: "", color = Color.White, fontWeight = FontWeight.Bold, maxLines = 1)
                Text("${ad.duration_seconds}s • ${ad.platform_placement.replaceFirstChar { it.uppercase() }}", color = Color(0xFF999999), fontSize = 12.sp)
                ad.expires_at?.let { Text("Expires $it", color = Color(0xFF666666), fontSize = 11.sp) }
                Text(statusLabel, color = statusColor, fontSize = 12.sp, fontWeight = FontWeight.Bold)
            }
            if (ad.payment_status != "pending") {
                Button(onClick = onRenew, colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF4F46E5))) {
                    Text("Renew", fontSize = 12.sp)
                }
            }
        }
    }
}
