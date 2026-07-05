package com.soloreel.app.ui.coins

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.soloreel.app.data.api.AuthResult
import com.soloreel.app.data.api.SOLOREELApi
import com.soloreel.app.data.api.TokenManager
import com.soloreel.app.data.model.CoinPackage
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class CoinState(val packages: List<CoinPackage> = emptyList(), val coins: Double = 0.0, val isLoading: Boolean = false, val error: String? = null)

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
            _state.value = _state.value.copy(isLoading = true, coins = tokenManager.userCoins)
            try { val r = api.getCoinPackages(); _state.value = _state.value.copy(packages = r.data ?: emptyList(), isLoading = false) }
            catch (e: Exception) { _state.value = _state.value.copy(isLoading = false, error = e.message) }
        }
    }
}

@Composable
fun CoinShopScreen(vm: CoinViewModel = hiltViewModel()) {
    val state by vm.state.collectAsState()

    LazyColumn(modifier = Modifier.fillMaxSize().background(Color(0xFF0A0A0A)).padding(16.dp)) {
        item {
            Text("Coin Shop", color = Color.White, fontSize = 22.sp, fontWeight = FontWeight.Bold)
            Spacer(Modifier.height(4.dp))
            Text("Balance: ${state.coins.toInt()} coins", color = Color(0xFFEAB308), fontSize = 16.sp, fontWeight = FontWeight.SemiBold)
            Spacer(Modifier.height(16.dp))
        }

        if (state.isLoading) {
            item { Box(Modifier.fillMaxWidth().padding(32.dp), contentAlignment = Alignment.Center) { CircularProgressIndicator(color = Color(0xFFDC2626)) } }
        } else {
            items(state.packages) { pkg ->
                Card(
                    modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp),
                    colors = CardDefaults.cardColors(containerColor = Color(0xFF1A1A1A)),
                    shape = RoundedCornerShape(12.dp)
                ) {
                    Row(
                        modifier = Modifier.padding(16.dp).fillMaxWidth(),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Column {
                            Text(pkg.name, color = Color.White, fontWeight = FontWeight.Bold, fontSize = 16.sp)
                            Text("${pkg.coins} coins", color = Color(0xFFEAB308), fontSize = 14.sp)
                        }
                        Text("${pkg.currency} ${String.format("%.2f", pkg.price)}", color = Color(0xFFDC2626), fontWeight = FontWeight.Bold, fontSize = 16.sp)
                    }
                }
            }
        }
        state.error?.let { item { Text(it, color = Color.Red, modifier = Modifier.padding(16.dp)) } }
    }
}
