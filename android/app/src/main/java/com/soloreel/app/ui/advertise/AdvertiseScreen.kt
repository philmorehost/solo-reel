package com.soloreel.app.ui.advertise

import android.net.Uri
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Image
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.navigation.NavHostController
import coil.compose.rememberAsyncImagePainter
import com.soloreel.app.data.api.SOLOREELApi
import com.soloreel.app.data.api.apiMessage
import com.soloreel.app.data.model.AdPricing
import com.soloreel.app.ui.coins.PaymentSuccessDialog
import com.soloreel.app.ui.coins.PaymentWebView
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.toRequestBody
import javax.inject.Inject

data class AdvertiseState(
    val prices: List<AdPricing> = emptyList(),
    val isLoading: Boolean = false,
    val error: String? = null,
    val paymentUrl: String? = null,
    val paymentRef: String? = null,
    val paymentSuccess: Boolean = false
)

@HiltViewModel
class AdvertiseViewModel @Inject constructor(private val api: SOLOREELApi) : ViewModel() {
    private val _state = MutableStateFlow(AdvertiseState())
    val state: StateFlow<AdvertiseState> = _state.asStateFlow()

    init {
        viewModelScope.launch {
            try {
                val r = api.getAdsPricing()
                _state.value = _state.value.copy(prices = r.data ?: emptyList())
            } catch (_: Exception) { }
        }
    }

    fun submit(context: android.content.Context, title: String, targetUrl: String, duration: Int, placement: String, imageUri: Uri) {
        viewModelScope.launch {
            _state.value = _state.value.copy(isLoading = true, error = null)
            try {
                val resolver = context.contentResolver
                val bytes = resolver.openInputStream(imageUri)?.use { it.readBytes() }
                    ?: throw Exception("Could not read the selected file")
                val mimeType = resolver.getType(imageUri) ?: "image/jpeg"
                val fileName = "ad_upload." + (mimeType.substringAfter("/").ifBlank { "jpg" })

                val mediaPart = MultipartBody.Part.createFormData(
                    "media_file", fileName, bytes.toRequestBody(mimeType.toMediaTypeOrNull())
                )
                val r = api.subscribeAd(
                    title = title.toRequestBody("text/plain".toMediaTypeOrNull()),
                    targetUrl = targetUrl.toRequestBody("text/plain".toMediaTypeOrNull()),
                    durationSeconds = duration.toString().toRequestBody("text/plain".toMediaTypeOrNull()),
                    platformPlacement = placement.toRequestBody("text/plain".toMediaTypeOrNull()),
                    mediaFile = mediaPart
                )
                val url = r.data?.authorization_url
                if (url != null) {
                    _state.value = _state.value.copy(isLoading = false, paymentUrl = url, paymentRef = r.data.reference)
                } else {
                    _state.value = _state.value.copy(isLoading = false, error = r.error ?: r.message ?: "Could not initiate payment")
                }
            } catch (e: Exception) {
                _state.value = _state.value.copy(isLoading = false, error = "Could not submit ad: ${e.apiMessage("please try again")}")
            }
        }
    }

    fun onPaymentSuccess(ref: String) {
        viewModelScope.launch {
            try {
                api.verifyPayment(ref)
                _state.value = _state.value.copy(paymentUrl = null, paymentSuccess = true)
            } catch (e: Exception) {
                _state.value = _state.value.copy(paymentUrl = null, paymentSuccess = true)
            }
        }
    }

    fun dismissPayment() { _state.value = _state.value.copy(paymentUrl = null) }
    fun dismissSuccess() { _state.value = _state.value.copy(paymentSuccess = false) }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdvertiseScreen(navController: NavHostController, vm: AdvertiseViewModel = hiltViewModel()) {
    val state by vm.state.collectAsState()
    val context = LocalContext.current

    var title by remember { mutableStateOf("") }
    var targetUrl by remember { mutableStateOf("") }
    var duration by remember { mutableStateOf(5) }
    var placement by remember { mutableStateOf("both") }
    var imageUri by remember { mutableStateOf<Uri?>(null) }

    val imagePicker = rememberLauncherForActivityResult(ActivityResultContracts.GetContent()) { uri -> imageUri = uri }

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
            title = { Text("Advertise With Us", color = Color.White) },
            navigationIcon = { IconButton(onClick = { navController.popBackStack() }) { Icon(Icons.Default.ArrowBack, null, tint = Color.White) } },
            colors = TopAppBarDefaults.topAppBarColors(containerColor = Color.Black)
        )

        LazyColumn(modifier = Modifier.fillMaxSize().padding(16.dp)) {
            item {
                Text("Run your own banner ad on the home screen. Pick a duration, where it shows, and pay once for a campaign.", color = Color(0xFF999999), fontSize = 13.sp)
                Spacer(Modifier.height(16.dp))

                OutlinedTextField(
                    value = title, onValueChange = { title = it },
                    label = { Text("Ad Title") }, modifier = Modifier.fillMaxWidth(),
                    colors = OutlinedTextFieldDefaults.colors(focusedTextColor = Color.White, unfocusedTextColor = Color.White)
                )
                Spacer(Modifier.height(12.dp))
                OutlinedTextField(
                    value = targetUrl, onValueChange = { targetUrl = it },
                    label = { Text("Target Link (optional)") }, modifier = Modifier.fillMaxWidth(),
                    colors = OutlinedTextFieldDefaults.colors(focusedTextColor = Color.White, unfocusedTextColor = Color.White)
                )
                Spacer(Modifier.height(16.dp))

                Text("Banner Image", color = Color.White, fontWeight = FontWeight.Bold)
                Spacer(Modifier.height(8.dp))
                Box(
                    modifier = Modifier.fillMaxWidth().height(160.dp)
                        .background(Color(0xFF1A1A1A), RoundedCornerShape(12.dp))
                        .border(1.dp, Color(0xFF333333), RoundedCornerShape(12.dp))
                        .clickable { imagePicker.launch("image/*") },
                    contentAlignment = Alignment.Center
                ) {
                    if (imageUri != null) {
                        Image(painter = rememberAsyncImagePainter(imageUri), contentDescription = null, modifier = Modifier.fillMaxSize())
                    } else {
                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                            Icon(Icons.Default.Image, null, tint = Color(0xFF666666), modifier = Modifier.size(36.dp))
                            Spacer(Modifier.height(8.dp))
                            Text("Tap to choose an image (1200x600 recommended)", color = Color(0xFF666666), fontSize = 12.sp)
                        }
                    }
                }
                Spacer(Modifier.height(16.dp))

                Text("On-Screen Duration", color = Color.White, fontWeight = FontWeight.Bold)
                Row(modifier = Modifier.fillMaxWidth().padding(top = 8.dp), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    listOf(5, 10, 15).forEach { d ->
                        FilterChip(
                            selected = duration == d, onClick = { duration = d },
                            label = { Text("${d}s") }
                        )
                    }
                }
                Spacer(Modifier.height(16.dp))

                Text("Where should it show?", color = Color.White, fontWeight = FontWeight.Bold)
                Row(modifier = Modifier.fillMaxWidth().padding(top = 8.dp), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    listOf("website" to "Website", "app" to "App", "both" to "Both").forEach { (value, label) ->
                        FilterChip(
                            selected = placement == value, onClick = { placement = value },
                            label = { Text(label) }
                        )
                    }
                }
                Spacer(Modifier.height(16.dp))

                val price = state.prices.firstOrNull { it.duration_seconds == duration && it.platform_placement == placement }?.price
                Text(
                    price?.let { "Price: ₦${String.format("%,.2f", it)}" } ?: "Price unavailable",
                    color = Color(0xFFEAB308), fontSize = 18.sp, fontWeight = FontWeight.Bold
                )
                Spacer(Modifier.height(16.dp))

                state.error?.let { Text(it, color = Color.Red, modifier = Modifier.padding(bottom = 12.dp)) }

                Button(
                    onClick = {
                        val uri = imageUri
                        if (title.isBlank() || uri == null) {
                            return@Button
                        }
                        vm.submit(context, title, targetUrl, duration, placement, uri)
                    },
                    enabled = !state.isLoading,
                    modifier = Modifier.fillMaxWidth().height(50.dp),
                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFDC2626))
                ) {
                    if (state.isLoading) CircularProgressIndicator(modifier = Modifier.size(20.dp), color = Color.White)
                    else Text("Continue to Payment", fontWeight = FontWeight.Bold)
                }
            }
        }
    }

    if (state.paymentSuccess) {
        PaymentSuccessDialog(onDismiss = { vm.dismissSuccess(); navController.popBackStack() })
    }
}
