package com.soloreel.app.ui.profile

import androidx.compose.animation.*
import androidx.compose.foundation.*
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
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
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.soloreel.app.data.api.SOLOREELApi
import com.soloreel.app.data.api.TokenManager
import com.soloreel.app.data.model.User
import com.soloreel.app.data.model.WeeklyBonusStatus
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class ProfileState(
    val user: User? = null,
    val coins: Double = 0.0,
    val guestCoins: Double = 0.0,
    val bonusStatus: WeeklyBonusStatus? = null,
    val isLoading: Boolean = false,
    val isLoggedIn: Boolean = false,
    val guestId: String = ""
)

@HiltViewModel
class ProfileViewModel @Inject constructor(
    private val api: SOLOREELApi, private val tokenManager: TokenManager
) : ViewModel() {
    private val _state = MutableStateFlow(ProfileState())
    val state: StateFlow<ProfileState> = _state.asStateFlow()
    init { load() }

    fun load() {
        viewModelScope.launch {
            val loggedIn = tokenManager.isLoggedIn
            _state.value = ProfileState(
                isLoggedIn = loggedIn,
                coins = tokenManager.userCoins,
                guestCoins = tokenManager.guestCoins,
                guestId = tokenManager.guestId,
                isLoading = loggedIn
            )
            if (!loggedIn) return@launch
            try {
                val r = api.getProfile()
                val bonus = try { api.getBonusStatus().data } catch (e: Exception) { null }
                _state.value = _state.value.copy(
                    user = r.data,
                    bonusStatus = bonus,
                    isLoading = false
                )
            } catch (e: Exception) {
                _state.value = _state.value.copy(isLoading = false)
            }
        }
    }

    fun logout() { tokenManager.clear() }
}

@Composable
fun ProfileScreen(
    onLogout: () -> Unit,
    onNavigateToLogin: () -> Unit = {},
    vm: ProfileViewModel = hiltViewModel()
) {
    val state by vm.state.collectAsState()

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(Color(0xFF0A0A0A))
            .verticalScroll(rememberScrollState())
    ) {
        if (!state.isLoggedIn) {
            GuestProfileScreen(
                guestId = state.guestId,
                guestCoins = state.guestCoins,
                onNavigateToLogin = onNavigateToLogin
            )
        } else {
            RegisteredProfileScreen(
                state = state,
                onLogout = { vm.logout(); onLogout() }
            )
        }
    }
}

@Composable
fun GuestProfileScreen(guestId: String, guestCoins: Double, onNavigateToLogin: () -> Unit) {
    Column(
        modifier = Modifier.fillMaxWidth().padding(20.dp),
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        Spacer(Modifier.height(32.dp))

        // Guest avatar
        Box(
            modifier = Modifier.size(100.dp).clip(CircleShape)
                .background(Brush.radialGradient(listOf(Color(0xFF3A3A3A), Color(0xFF1A1A1A)))),
            contentAlignment = Alignment.Center
        ) {
            Icon(Icons.Default.Person, null, tint = Color(0xFF666666), modifier = Modifier.size(56.dp))
        }

        Spacer(Modifier.height(16.dp))
        Text("Guest User", color = Color.White, fontSize = 22.sp, fontWeight = FontWeight.Bold)
        Text("ID: ${guestId.take(8)}...", color = Color(0xFF666666), fontSize = 12.sp)
        Spacer(Modifier.height(12.dp))

        // Guest coin balance
        Card(
            modifier = Modifier.fillMaxWidth(),
            colors = CardDefaults.cardColors(containerColor = Color(0xFF1A1A1A)),
            shape = RoundedCornerShape(16.dp)
        ) {
            Row(
                modifier = Modifier.padding(20.dp).fillMaxWidth(),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Column {
                    Text("Your Coin Balance", color = Color(0xFF999999), fontSize = 13.sp)
                    Text("${guestCoins.toInt()} Coins", color = Color(0xFFEAB308), fontSize = 24.sp, fontWeight = FontWeight.Bold)
                }
                Icon(Icons.Default.Paid, null, tint = Color(0xFFEAB308), modifier = Modifier.size(40.dp))
            }
        }

        Spacer(Modifier.height(24.dp))

        // Weekly Bonus Ad Banner
        Card(
            modifier = Modifier.fillMaxWidth(),
            colors = CardDefaults.cardColors(containerColor = Color(0xFF0D0D0D)),
            shape = RoundedCornerShape(20.dp),
            border = BorderStroke(1.5.dp, Brush.horizontalGradient(listOf(Color(0xFFDC2626), Color(0xFFEF4444), Color(0xFFB91C1C))))
        ) {
            Column(modifier = Modifier.padding(20.dp), horizontalAlignment = Alignment.CenterHorizontally) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text("🎁", fontSize = 28.sp)
                    Spacer(Modifier.width(8.dp))
                    Text("FREE Weekly Bonus!", color = Color(0xFFDC2626), fontSize = 18.sp, fontWeight = FontWeight.Black)
                }
                Spacer(Modifier.height(10.dp))
                Text(
                    "Register and get FREE coins every week!\nUse them to unlock premium episodes.\nGuests miss out — don't let that be you!",
                    color = Color(0xFFCCCCCC), fontSize = 14.sp, textAlign = TextAlign.Center, lineHeight = 22.sp
                )
                Spacer(Modifier.height(8.dp))
                Text("✓ 50 bonus coins every Monday", color = Color(0xFF4ADE80), fontSize = 13.sp)
                Text("✓ Save your watch progress", color = Color(0xFF4ADE80), fontSize = 13.sp)
                Text("✓ Create a favorites list", color = Color(0xFF4ADE80), fontSize = 13.sp)
                Text("✗ Guests lose session when app closes", color = Color(0xFFEF4444), fontSize = 13.sp)
                Spacer(Modifier.height(16.dp))
                Button(
                    onClick = onNavigateToLogin,
                    modifier = Modifier.fillMaxWidth().height(52.dp),
                    shape = RoundedCornerShape(12.dp),
                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFDC2626))
                ) {
                    Text("Register / Login Now", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                }
            }
        }

        Spacer(Modifier.height(16.dp))
        Text(
            "You can still buy coins and watch episodes as a guest.\nYour coin balance is saved on this device.",
            color = Color(0xFF555555), fontSize = 12.sp, textAlign = TextAlign.Center
        )
        Spacer(Modifier.height(32.dp))
    }
}

@Composable
fun RegisteredProfileScreen(state: ProfileState, onLogout: () -> Unit) {
    Column(modifier = Modifier.fillMaxWidth().padding(20.dp), horizontalAlignment = Alignment.CenterHorizontally) {
        Spacer(Modifier.height(32.dp))

        // Avatar
        Box(
            modifier = Modifier.size(100.dp).clip(CircleShape)
                .background(Brush.radialGradient(listOf(Color(0xFFDC2626), Color(0xFF7F1D1D)))),
            contentAlignment = Alignment.Center
        ) {
            Text(
                (state.user?.username?.firstOrNull()?.uppercase() ?: "U"),
                color = Color.White, fontSize = 40.sp, fontWeight = FontWeight.Bold
            )
        }

        Spacer(Modifier.height(12.dp))
        Text(state.user?.displayName ?: state.user?.username ?: "User", color = Color.White, fontSize = 22.sp, fontWeight = FontWeight.Bold)
        Text(state.user?.email ?: "", color = Color(0xFF666666), fontSize = 14.sp)
        Spacer(Modifier.height(20.dp))

        // Coin Balance Card
        Card(
            modifier = Modifier.fillMaxWidth(),
            colors = CardDefaults.cardColors(containerColor = Color(0xFF1A1A1A)),
            shape = RoundedCornerShape(16.dp)
        ) {
            Row(modifier = Modifier.padding(20.dp).fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                Column(modifier = Modifier.weight(1f)) {
                    Text("Coin Balance", color = Color(0xFF999999), fontSize = 13.sp)
                    Text("${(state.user?.coin_balance ?: state.coins).toInt()} Coins", color = Color(0xFFEAB308), fontSize = 24.sp, fontWeight = FontWeight.Bold)
                }
                Icon(Icons.Default.Paid, null, tint = Color(0xFFEAB308), modifier = Modifier.size(40.dp))
            }
        }

        // Weekly Bonus Card
        state.bonusStatus?.let { bonus ->
            Spacer(Modifier.height(12.dp))
            Card(
                modifier = Modifier.fillMaxWidth(),
                colors = CardDefaults.cardColors(containerColor = Color(0xFF0F1A0F)),
                shape = RoundedCornerShape(16.dp),
                border = BorderStroke(1.dp, Color(0xFF4ADE80))
            ) {
                Row(modifier = Modifier.padding(16.dp).fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                    Text("🎁", fontSize = 28.sp)
                    Spacer(Modifier.width(12.dp))
                    Column(modifier = Modifier.weight(1f)) {
                        Text("Weekly Bonus", color = Color(0xFF4ADE80), fontSize = 14.sp, fontWeight = FontWeight.Bold)
                        Text("${bonus.bonus_coins.toInt()} coins available", color = Color.White, fontSize = 16.sp, fontWeight = FontWeight.SemiBold)
                        if (bonus.bonus_expires_at != null) {
                            Text("Expires: ${bonus.bonus_expires_at.take(10)}", color = Color(0xFF888888), fontSize = 12.sp)
                        }
                    }
                }
            }
        }

        Spacer(Modifier.height(24.dp))

        // Menu items
        ProfileMenuItem(Icons.Default.History, "Watch History") {}
        ProfileMenuItem(Icons.Default.Favorite, "My Favorites") {}
        ProfileMenuItem(Icons.Default.Edit, "Edit Profile") {}
        ProfileMenuItem(Icons.Default.ShoppingCart, "Buy More Coins") {}
        ProfileMenuItem(Icons.Default.Settings, "Settings") {}

        Spacer(Modifier.height(24.dp))
        OutlinedButton(
            onClick = onLogout,
            colors = ButtonDefaults.outlinedButtonColors(contentColor = Color(0xFFDC2626)),
            border = BorderStroke(1.dp, Color(0xFFDC2626)),
            modifier = Modifier.fillMaxWidth().height(48.dp),
            shape = RoundedCornerShape(12.dp)
        ) { Text("Logout", fontWeight = FontWeight.Bold) }
        Spacer(Modifier.height(32.dp))
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ProfileMenuItem(icon: ImageVector, label: String, onClick: () -> Unit) {
    Card(
        onClick = onClick,
        modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp),
        colors = CardDefaults.cardColors(containerColor = Color(0xFF161616)),
        shape = RoundedCornerShape(12.dp)
    ) {
        Row(modifier = Modifier.padding(16.dp).fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
            Box(modifier = Modifier.size(40.dp).clip(RoundedCornerShape(10.dp)).background(Color(0xFF1F1F1F)), contentAlignment = Alignment.Center) {
                Icon(icon, null, tint = Color(0xFFDC2626), modifier = Modifier.size(22.dp))
            }
            Spacer(Modifier.width(14.dp))
            Text(label, color = Color.White, fontSize = 15.sp, modifier = Modifier.weight(1f))
            Icon(Icons.Default.ChevronRight, null, tint = Color(0xFF444444))
        }
    }
}
