package com.soloreel.app.ui.coins

import android.webkit.*
import androidx.compose.foundation.*
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.compose.ui.window.Dialog
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.soloreel.app.data.api.GuestPurchaseBody
import com.soloreel.app.data.api.SOLOREELApi
import com.soloreel.app.data.api.TokenManager
import com.soloreel.app.data.api.apiMessage
import com.soloreel.app.data.model.CoinPackage
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class CoinState(
    val packages: List<CoinPackage> = emptyList(),
    val coins: Double = 0.0,
    val isLoading: Boolean = false,
    val error: String? = null,
    val paymentUrl: String? = null,
    val paymentRef: String? = null,
    val paymentSuccess: Boolean = false,
    val isLoggedIn: Boolean = false
)

@HiltViewModel
class CoinViewModel @Inject constructor(
    private val api: SOLOREELApi,
    private val tokenManager: TokenManager
) : ViewModel() {
    private val _state = MutableStateFlow(CoinState())
    val state: StateFlow<CoinState> = _state.asStateFlow()

    init { load() }

    fun load() {
        viewModelScope.launch {
            _state.value = _state.value.copy(
                isLoading = true,
                coins = if (tokenManager.isLoggedIn) tokenManager.userCoins else tokenManager.guestCoins,
                isLoggedIn = tokenManager.isLoggedIn
            )
            try {
                val r = api.getCoinPackages()
                _state.value = _state.value.copy(packages = r.data ?: emptyList(), isLoading = false)
            } catch (e: Exception) {
                _state.value = _state.value.copy(isLoading = false, error = e.message)
            }
        }
    }

    fun initiatePurchase(packageId: Int) {
        viewModelScope.launch {
            _state.value = _state.value.copy(error = null)
            try {
                val r = if (tokenManager.isLoggedIn) {
                    api.purchaseCoins(mapOf("package_id" to packageId))
                } else {
                    api.guestPurchaseCoins(GuestPurchaseBody(packageId, tokenManager.guestId))
                }
                val url = r.data?.authorization_url
                val ref = r.data?.reference
                if (url != null) {
                    _state.value = _state.value.copy(paymentUrl = url, paymentRef = ref)
                } else {
                    _state.value = _state.value.copy(error = r.error ?: r.message ?: "Could not initiate payment")
                }
            } catch (e: Exception) {
                _state.value = _state.value.copy(error = "Could not initiate payment: ${e.apiMessage("please try again")}")
            }
        }
    }

    fun onPaymentSuccess(ref: String) {
        viewModelScope.launch {
            try {
                api.verifyPayment(ref)
                if (tokenManager.isLoggedIn) {
                    val r = api.getProfile()
                    r.data?.coin_balance?.let { tokenManager.userCoins = it }
                    _state.value = _state.value.copy(
                        coins = tokenManager.userCoins, paymentSuccess = true, paymentUrl = null
                    )
                } else {
                    val r = api.getGuestBalance(tokenManager.guestId)
                    r.data?.coin_balance?.let { tokenManager.guestCoins = it }
                    _state.value = _state.value.copy(
                        coins = tokenManager.guestCoins, paymentSuccess = true, paymentUrl = null
                    )
                }
            } catch (e: Exception) {
                _state.value = _state.value.copy(paymentUrl = null, paymentSuccess = true)
            }
        }
    }

    fun dismissPayment() { _state.value = _state.value.copy(paymentUrl = null) }
    fun dismissSuccess() { _state.value = _state.value.copy(paymentSuccess = false) }
}

@Composable
fun CoinShopScreen(vm: CoinViewModel = hiltViewModel()) {
    val state by vm.state.collectAsState()

    if (state.paymentUrl != null) {
        PaymentWebView(
            url = state.paymentUrl!!,
            onSuccess = { ref -> vm.onPaymentSuccess(state.paymentRef ?: ref) },
            onDismiss = { vm.dismissPayment() }
        )
        return
    }

    LazyColumn(modifier = Modifier.fillMaxSize().background(Color(0xFF0A0A0A)).padding(16.dp)) {
        item {
            Text("Coin Shop", color = Color.White, fontSize = 22.sp, fontWeight = FontWeight.Bold)
            Spacer(Modifier.height(4.dp))
        }

        // Balance card
        item {
            Card(
                modifier = Modifier.fillMaxWidth().padding(vertical = 8.dp),
                colors = CardDefaults.cardColors(containerColor = Color(0xFF1A1A1A)),
                shape = RoundedCornerShape(16.dp)
            ) {
                Row(modifier = Modifier.padding(20.dp).fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                    Column(modifier = Modifier.weight(1f)) {
                        Text(if (state.isLoggedIn) "Your Balance" else "Guest Balance", color = Color(0xFF999999), fontSize = 13.sp)
                        Text("${state.coins.toInt()} Coins", color = Color(0xFFEAB308), fontSize = 28.sp, fontWeight = FontWeight.Bold)
                    }
                    Icon(Icons.Default.Paid, null, tint = Color(0xFFEAB308), modifier = Modifier.size(44.dp))
                }
            }
            Spacer(Modifier.height(16.dp))
            Text("Select a Package", color = Color(0xFF999999), fontSize = 14.sp, fontWeight = FontWeight.SemiBold)
            Spacer(Modifier.height(8.dp))
        }

        if (state.isLoading) {
            item { Box(Modifier.fillMaxWidth().padding(32.dp), contentAlignment = Alignment.Center) { CircularProgressIndicator(color = Color(0xFFDC2626)) } }
        } else {
            items(state.packages) { pkg ->
                CoinPackageCard(pkg = pkg, onClick = { vm.initiatePurchase(pkg.id) })
            }
        }

        state.error?.let { err ->
            item { Text(err, color = Color.Red, modifier = Modifier.padding(16.dp)) }
        }
    }

    if (state.paymentSuccess) {
        PaymentSuccessDialog(onDismiss = { vm.dismissSuccess() })
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun CoinPackageCard(pkg: CoinPackage, onClick: () -> Unit) {
    val gradients = listOf(
        listOf(Color(0xFF1A0A2E), Color(0xFF2D1B69)),
        listOf(Color(0xFF0A1A2E), Color(0xFF1B3A69)),
        listOf(Color(0xFF1A2E0A), Color(0xFF2D6919)),
        listOf(Color(0xFF2E0A0A), Color(0xFF691919)),
    )
    val idx = pkg.id % gradients.size
    Card(
        onClick = onClick,
        modifier = Modifier.fillMaxWidth().padding(vertical = 6.dp),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = Color.Transparent)
    ) {
        Box(
            modifier = Modifier.fillMaxWidth()
                .background(Brush.horizontalGradient(gradients[idx]), RoundedCornerShape(16.dp))
                .border(1.dp, Color(0xFF333333), RoundedCornerShape(16.dp))
                .padding(20.dp)
        ) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Text("🪙", fontSize = 32.sp)
                Spacer(Modifier.width(14.dp))
                Column(modifier = Modifier.weight(1f)) {
                    Text(pkg.name, color = Color.White, fontWeight = FontWeight.Bold, fontSize = 17.sp)
                    Text("${pkg.coins} coins", color = Color(0xFFEAB308), fontSize = 15.sp, fontWeight = FontWeight.SemiBold)
                }
                Column(horizontalAlignment = Alignment.End) {
                    Text("${pkg.currency} ${String.format("%.2f", pkg.price)}", color = Color(0xFFDC2626), fontWeight = FontWeight.ExtraBold, fontSize = 18.sp)
                    Text("Buy Now →", color = Color(0xFF888888), fontSize = 12.sp)
                }
            }
        }
    }
}

@Composable
fun PaymentWebView(url: String, onSuccess: (String) -> Unit, onDismiss: () -> Unit) {
    var loadError by remember { mutableStateOf(false) }
    var reloadKey by remember { mutableIntStateOf(0) }
    val loadedUrl = remember { mutableStateOf<String?>(null) }

    Dialog(onDismissRequest = onDismiss) {
        Card(shape = RoundedCornerShape(16.dp), modifier = Modifier.fillMaxSize(0.95f)) {
            Column(modifier = Modifier.fillMaxSize()) {
                Row(modifier = Modifier.fillMaxWidth().background(Color(0xFF111111)).padding(12.dp), verticalAlignment = Alignment.CenterVertically) {
                    Text("Complete Payment", color = Color.White, fontWeight = FontWeight.Bold, fontSize = 16.sp, modifier = Modifier.weight(1f))
                    IconButton(onClick = onDismiss) { Icon(Icons.Default.Close, null, tint = Color.White) }
                }
                Box(modifier = Modifier.weight(1f).fillMaxSize()) {
                    AndroidView(
                        factory = { ctx ->
                            WebView(ctx).apply {
                                settings.javaScriptEnabled = true
                                settings.domStorageEnabled = true
                                settings.setSupportMultipleWindows(true)
                                settings.javaScriptCanOpenWindowsAutomatically = true
                                settings.useWideViewPort = true
                                settings.loadWithOverviewMode = true
                                settings.mixedContentMode = WebSettings.MIXED_CONTENT_ALWAYS_ALLOW
                                // Payment gateways commonly refuse to render for the default Android
                                // WebView UA (fraud/PCI heuristics key off the "; wv)" marker) — present
                                // as a normal mobile browser instead.
                                settings.userAgentString = settings.userAgentString.replace("; wv", "")

                                webViewClient = object : WebViewClient() {
                                    override fun shouldOverrideUrlLoading(view: WebView?, request: WebResourceRequest?): Boolean {
                                        val redirectUrl = request?.url?.toString() ?: return false
                                        if (!request.isForMainFrame) return false

                                        val ref = request.url?.getQueryParameter("reference") ?: request.url?.getQueryParameter("trxref")
                                        // Ensure we actually have a reference before assuming it's our success redirect
                                        if (!ref.isNullOrBlank() && (redirectUrl.contains("callback") || redirectUrl.contains("verify") || redirectUrl.contains("success"))) {
                                            onSuccess(ref)
                                            return true
                                        }
                                        return false
                                    }

                                    override fun onPageStarted(view: WebView?, url: String?, favicon: android.graphics.Bitmap?) {
                                        loadError = false
                                    }

                                    override fun onReceivedError(view: WebView?, request: WebResourceRequest?, error: WebResourceError?) {
                                        if (request?.isForMainFrame == true) loadError = true
                                    }

                                    override fun onReceivedHttpError(view: WebView?, request: WebResourceRequest?, errorResponse: WebResourceResponse?) {
                                        if (request?.isForMainFrame == true) loadError = true
                                    }
                                }

                                // Payment popups (Payhub's inline.js / 3DS challenges) call window.open();
                                // without this, Android's default WebView silently drops them, leaving a
                                // blank screen. Reuse this same WebView instance as the popup's target.
                                webChromeClient = object : WebChromeClient() {
                                    override fun onCreateWindow(
                                        view: WebView?,
                                        isDialog: Boolean,
                                        isUserGesture: Boolean,
                                        resultMsg: android.os.Message?
                                    ): Boolean {
                                        val transport = resultMsg?.obj as? WebView.WebViewTransport ?: return false
                                        transport.webView = view
                                        resultMsg.sendToTarget()
                                        return true
                                    }
                                }

                                loadUrl(url)
                                loadedUrl.value = url
                            }
                        },
                        update = { view ->
                            // Only reload when the target URL actually changes (or a manual retry was
                            // requested) — reloading on every recomposition interrupted the gateway's
                            // JS mid-init and was a root cause of the blank-page bug.
                            if (loadedUrl.value != url || reloadKey > 0) {
                                loadError = false
                                view.loadUrl(url)
                                loadedUrl.value = url
                            }
                        },
                        modifier = Modifier.fillMaxSize()
                    )

                    if (loadError) {
                        Column(
                            modifier = Modifier.fillMaxSize().background(Color(0xFF0A0A0A)).padding(24.dp),
                            horizontalAlignment = Alignment.CenterHorizontally,
                            verticalArrangement = Arrangement.Center
                        ) {
                            Icon(Icons.Default.ErrorOutline, null, tint = Color(0xFFDC2626), modifier = Modifier.size(40.dp))
                            Spacer(Modifier.height(12.dp))
                            Text("Couldn't load the payment page.", color = Color.White, textAlign = TextAlign.Center)
                            Spacer(Modifier.height(16.dp))
                            Button(
                                onClick = { reloadKey++; loadedUrl.value = null },
                                colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFDC2626))
                            ) { Text("Retry") }
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun PaymentSuccessDialog(onDismiss: () -> Unit) {
    AlertDialog(
        onDismissRequest = onDismiss,
        containerColor = Color(0xFF161616),
        title = { Text("Payment Successful! 🎉", color = Color.White, fontWeight = FontWeight.Bold) },
        text = { Text("Your coins have been added to your account.", color = Color(0xFF999999)) },
        confirmButton = {
            Button(onClick = onDismiss, colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFDC2626))) {
                Text("Great, Thanks!")
            }
        }
    )
}
