package com.soloreel.app.ui.vip

import androidx.browser.customtabs.CustomTabsIntent
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
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
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.navigation.NavHostController
import com.soloreel.app.data.api.PaymentResultBus
import com.soloreel.app.data.api.SOLOREELApi
import com.soloreel.app.data.api.TokenManager
import com.soloreel.app.data.api.apiMessage
import com.soloreel.app.data.model.VipPlan
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class VipPlansState(
    val plans: List<VipPlan> = emptyList(),
    val isVip: Boolean = false,
    val planName: String? = null,
    val expiresAt: String? = null,
    val isLoading: Boolean = false,
    val error: String? = null
)

@HiltViewModel
class VipPlansViewModel @Inject constructor(
    private val api: SOLOREELApi,
    private val tokenManager: TokenManager
) : ViewModel() {
    private val _state = MutableStateFlow(VipPlansState())
    val state: StateFlow<VipPlansState> = _state.asStateFlow()

    fun load() {
        viewModelScope.launch {
            _state.value = _state.value.copy(isLoading = true)
            try {
                val plans = api.getVipPlans().data ?: emptyList()
                var isVip = false
                var planName: String? = null
                var expiresAt: String? = null
                if (tokenManager.isLoggedIn) {
                    try {
                        val status = api.getVipStatus().data
                        isVip = status?.get("is_vip")?.asBoolean ?: false
                        planName = status?.get("plan_name")?.takeIf { !it.isJsonNull }?.asString
                        expiresAt = status?.get("expires_at")?.takeIf { !it.isJsonNull }?.asString
                    } catch (_: Exception) { }
                }
                _state.value = _state.value.copy(plans = plans, isVip = isVip, planName = planName, expiresAt = expiresAt, isLoading = false)
            } catch (e: Exception) {
                _state.value = _state.value.copy(isLoading = false, error = e.apiMessage("Could not load VIP plans"))
            }
        }
    }

    fun purchase(planId: Int, onResult: (authUrl: String?, reference: String?, error: String?) -> Unit) {
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

    fun verifyAndReload(reference: String) {
        viewModelScope.launch {
            try { api.verifyPayment(reference) } catch (_: Exception) { }
            load()
        }
    }
}

/** Standalone VIP membership screen — same Chrome Custom Tab + PaymentResultBus
 * checkout flow used for coin purchases and the in-player unlock-offers dialog. */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun VipPlansScreen(navController: NavHostController, vm: VipPlansViewModel = hiltViewModel()) {
    val state by vm.state.collectAsState()
    val context = LocalContext.current
    var pendingReference by remember { mutableStateOf<String?>(null) }
    var purchaseError by remember { mutableStateOf<String?>(null) }

    LaunchedEffect(Unit) { vm.load() }
    LaunchedEffect(Unit) {
        PaymentResultBus.events.collect { result ->
            val ref = pendingReference ?: return@collect
            if (result.status == "success") {
                vm.verifyAndReload(result.reference ?: ref)
            }
            pendingReference = null
        }
    }

    Column(modifier = Modifier.fillMaxSize().background(Color(0xFF0A0A0A))) {
        TopAppBar(
            title = { Text("VIP Membership", color = Color.White, fontWeight = FontWeight.Bold) },
            navigationIcon = { IconButton(onClick = { navController.popBackStack() }) { Icon(Icons.Default.ArrowBack, null, tint = Color.White) } },
            colors = TopAppBarDefaults.topAppBarColors(containerColor = Color.Black)
        )

        if (state.isLoading && state.plans.isEmpty()) {
            Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                CircularProgressIndicator(color = Color(0xFFEAB308))
            }
            return@Column
        }

        LazyColumn(modifier = Modifier.fillMaxSize().padding(horizontal = 16.dp)) {
            item {
                Column(horizontalAlignment = Alignment.CenterHorizontally, modifier = Modifier.fillMaxWidth().padding(vertical = 20.dp)) {
                    Text("👑", fontSize = 48.sp)
                    Spacer(Modifier.height(8.dp))
                    Text("SOLOREEL VIP", color = Color.White, fontSize = 22.sp, fontWeight = FontWeight.Bold)
                    Spacer(Modifier.height(6.dp))
                    Text(
                        "Unlock every episode for free, no ads, no per-episode coins.",
                        color = Color(0xFF999999), fontSize = 13.sp, textAlign = androidx.compose.ui.text.style.TextAlign.Center
                    )
                }
            }

            if (state.isVip) {
                item {
                    Box(
                        modifier = Modifier.fillMaxWidth().padding(bottom = 16.dp)
                            .background(Brush.linearGradient(listOf(Color(0x33F59E0B), Color(0x1AF59E0B))), RoundedCornerShape(14.dp))
                            .padding(16.dp)
                    ) {
                        Column {
                            Text("You're a VIP member 👑", color = Color(0xFFEAB308), fontWeight = FontWeight.Bold, fontSize = 15.sp)
                            Text(
                                "Plan: ${state.planName ?: "VIP"}" + (state.expiresAt?.let { " · Expires ${it.take(10)}" } ?: ""),
                                color = Color(0xFFCCCCCC), fontSize = 12.sp
                            )
                        }
                    }
                }
            }

            items(state.plans) { plan ->
                Box(
                    modifier = Modifier.fillMaxWidth().padding(bottom = 12.dp)
                        .background(Brush.linearGradient(listOf(Color(0xFFFDE68A), Color(0xFFF59E0B))), RoundedCornerShape(16.dp))
                        .clickable {
                            purchaseError = null
                            vm.purchase(plan.id) { authUrl, reference, err ->
                                if (authUrl != null && reference != null) {
                                    pendingReference = reference
                                    CustomTabsIntent.Builder().build().launchUrl(context, android.net.Uri.parse(authUrl))
                                } else {
                                    purchaseError = err ?: "Could not start checkout"
                                }
                            }
                        }
                        .padding(18.dp)
                ) {
                    Column {
                        Text(plan.name, color = Color.Black, fontWeight = FontWeight.Bold, fontSize = 16.sp)
                        Text("${plan.currency} ${String.format("%.2f", plan.price)}", color = Color.Black, fontWeight = FontWeight.ExtraBold, fontSize = 24.sp)
                        Text("${plan.duration_days} days", color = Color(0x99000000), fontSize = 12.sp)
                        Spacer(Modifier.height(8.dp))
                        if (plan.perk_free_unlocks == true) Text("✔ Unlock all episodes free", color = Color(0xCC000000), fontSize = 12.sp)
                        if (plan.perk_ad_free == true) Text("✔ No ads required to unlock", color = Color(0xCC000000), fontSize = 12.sp)
                    }
                }
            }

            (purchaseError ?: state.error)?.let { err ->
                item { Text(err, color = Color(0xFFDC2626), fontSize = 13.sp, modifier = Modifier.padding(vertical = 8.dp)) }
            }

            if (pendingReference != null) {
                item {
                    Text(
                        "Waiting for you to complete payment in your browser...",
                        color = Color(0xFF888888), fontSize = 12.sp,
                        modifier = Modifier.fillMaxWidth().padding(vertical = 8.dp),
                        textAlign = androidx.compose.ui.text.style.TextAlign.Center
                    )
                }
            }

            item { Spacer(Modifier.height(24.dp)) }
        }
    }
}
