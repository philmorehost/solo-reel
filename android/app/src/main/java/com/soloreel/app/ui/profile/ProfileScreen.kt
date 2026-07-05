package com.soloreel.app.ui.profile

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.soloreel.app.data.api.SOLOREELApi
import com.soloreel.app.data.api.TokenManager
import com.soloreel.app.data.model.User
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class ProfileState(val user: User? = null, val coins: Double = 0.0, val isLoading: Boolean = false)

@HiltViewModel
class ProfileViewModel @Inject constructor(
    private val api: SOLOREELApi, private val tokenManager: TokenManager
) : ViewModel() {
    private val _state = MutableStateFlow(ProfileState())
    val state: StateFlow<ProfileState> = _state.asStateFlow()
    init { load() }
    fun load() {
        viewModelScope.launch {
            _state.value = _state.value.copy(isLoading = true, coins = tokenManager.userCoins)
            try { val r = api.getProfile(); _state.value = _state.value.copy(user = r.data, isLoading = false) }
            catch (e: Exception) { _state.value = _state.value.copy(isLoading = false) }
        }
    }
    fun logout() { tokenManager.clear() }
}

@Composable
fun ProfileScreen(onLogout: () -> Unit, vm: ProfileViewModel = hiltViewModel()) {
    val state by vm.state.collectAsState()

    Column(
        modifier = Modifier.fillMaxSize().background(Color(0xFF0A0A0A)).verticalScroll(rememberScrollState()).padding(16.dp),
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        Spacer(Modifier.height(24.dp))
        Box(
            modifier = Modifier.size(80.dp).clip(CircleShape).background(Color(0xFFDC2626)),
            contentAlignment = Alignment.Center
        ) {
            Text((state.user?.username?.firstOrNull()?.uppercase() ?: "U"), color = Color.White, fontSize = 32.sp, fontWeight = FontWeight.Bold)
        }
        Spacer(Modifier.height(12.dp))
        Text(state.user?.username ?: "User", color = Color.White, fontSize = 20.sp, fontWeight = FontWeight.Bold)
        Text(state.user?.email ?: "", color = Color.Gray, fontSize = 14.sp)
        Spacer(Modifier.height(8.dp))
        Text("${state.coins.toInt()} coins", color = Color(0xFFEAB308), fontSize = 16.sp, fontWeight = FontWeight.SemiBold)
        Spacer(Modifier.height(24.dp))

        ProfileMenuItem(Icons.Default.History, "Watch History", onClick = { })
        ProfileMenuItem(Icons.Default.Favorite, "Favorites", onClick = { })
        ProfileMenuItem(Icons.Default.Settings, "Settings", onClick = { })
        Spacer(Modifier.height(24.dp))
        Button(
            onClick = { vm.logout(); onLogout() },
            colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFDC2626)),
            modifier = Modifier.fillMaxWidth().height(48.dp)
        ) { Text("Logout", fontWeight = FontWeight.Bold) }
        Spacer(Modifier.height(32.dp))
    }
}

@Composable
fun ProfileMenuItem(icon: androidx.compose.ui.graphics.vector.ImageVector, label: String, onClick: () -> Unit) {
    Card(
        onClick = onClick,
        modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp),
        colors = CardDefaults.cardColors(containerColor = Color(0xFF1A1A1A))
    ) {
        Row(modifier = Modifier.padding(16.dp).fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
            Icon(icon, null, tint = Color(0xFFDC2626), modifier = Modifier.size(24.dp))
            Spacer(Modifier.width(12.dp))
            Text(label, color = Color.White, fontSize = 15.sp)
            Spacer(Modifier.weight(1f))
            Icon(Icons.Default.ChevronRight, null, tint = Color.Gray)
        }
    }
}
