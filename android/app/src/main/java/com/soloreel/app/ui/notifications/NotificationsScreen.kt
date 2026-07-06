package com.soloreel.app.ui.notifications

import android.Manifest
import android.app.NotificationChannel
import android.app.NotificationManager
import android.content.Context
import android.content.pm.PackageManager
import android.os.Build
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import androidx.core.content.ContextCompat
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.navigation.NavHostController
import com.soloreel.app.R
import com.soloreel.app.data.api.SOLOREELApi
import com.soloreel.app.data.api.TokenManager
import com.soloreel.app.data.model.AppNotification
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class NotificationsState(
    val items: List<AppNotification> = emptyList(),
    val unreadCount: Int = 0,
    val isLoading: Boolean = false
)

@HiltViewModel
class NotificationsViewModel @Inject constructor(
    private val api: SOLOREELApi,
    private val tokenManager: TokenManager
) : ViewModel() {
    private val _state = MutableStateFlow(NotificationsState())
    val state: StateFlow<NotificationsState> = _state.asStateFlow()

    fun load(context: Context? = null) {
        viewModelScope.launch {
            _state.value = _state.value.copy(isLoading = true)
            try {
                val r = api.getNotifications(guestId = if (tokenManager.isGuest) tokenManager.guestId else null)
                val items = r.data ?: emptyList()
                _state.value = NotificationsState(
                    items = items,
                    unreadCount = items.count { !it.is_read },
                    isLoading = false
                )
                context?.let { postSystemNotificationsForNew(it, items) }
            } catch (e: Exception) {
                _state.value = _state.value.copy(isLoading = false)
            }
        }
    }

    fun markRead(id: Int) {
        viewModelScope.launch {
            try {
                api.markNotificationRead(
                    id,
                    if (tokenManager.isGuest) mapOf("guest_id" to tokenManager.guestId) else emptyMap()
                )
            } catch (_: Exception) { }
            val items = _state.value.items.map { if (it.id == id) it.copy(is_read = true) else it }
            _state.value = _state.value.copy(items = items, unreadCount = items.count { !it.is_read })
        }
    }

    /** Shows a phone notification for anything the user hasn't been alerted about yet. */
    private fun postSystemNotificationsForNew(context: Context, items: List<AppNotification>) {
        val lastSeen = tokenManager.lastSeenNotificationId
        val fresh = items.filter { !it.is_read && it.id > lastSeen }
        if (fresh.isEmpty()) return
        tokenManager.lastSeenNotificationId = items.maxOf { it.id }

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU &&
            ContextCompat.checkSelfPermission(context, Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED
        ) return

        val manager = NotificationManagerCompat.from(context)
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            manager.createNotificationChannel(
                NotificationChannel("soloreel_updates", "SOLOREEL Updates", NotificationManager.IMPORTANCE_DEFAULT)
            )
        }
        fresh.take(3).forEach { n ->
            val notif = NotificationCompat.Builder(context, "soloreel_updates")
                .setSmallIcon(R.mipmap.ic_launcher)
                .setContentTitle(n.title)
                .setContentText(n.body ?: "")
                .setStyle(NotificationCompat.BigTextStyle().bigText(n.body ?: ""))
                .setAutoCancel(true)
                .build()
            try { manager.notify(n.id, notif) } catch (_: SecurityException) { }
        }
    }
}

/** Bell icon with unread badge — used on the Home screen. */
@Composable
fun NotificationBell(unreadCount: Int, onClick: () -> Unit) {
    Box {
        IconButton(
            onClick = onClick,
            modifier = Modifier.background(Color(0x66000000), CircleShape)
        ) {
            Icon(Icons.Default.Notifications, contentDescription = "Notifications", tint = Color.White)
        }
        if (unreadCount > 0) {
            Box(
                modifier = Modifier
                    .align(Alignment.TopEnd)
                    .background(Color(0xFFDC2626), CircleShape)
                    .padding(horizontal = 5.dp, vertical = 1.dp)
            ) {
                Text(
                    if (unreadCount > 9) "9+" else "$unreadCount",
                    color = Color.White, fontSize = 10.sp, fontWeight = FontWeight.Bold
                )
            }
        }
    }
}

@Composable
fun NotificationsScreen(navController: NavHostController, vm: NotificationsViewModel = hiltViewModel()) {
    val state by vm.state.collectAsState()
    LaunchedEffect(Unit) { vm.load() }

    Column(modifier = Modifier.fillMaxSize().background(Color(0xFF0A0A0A))) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(horizontal = 8.dp, vertical = 12.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            IconButton(onClick = { navController.popBackStack() }) {
                Icon(Icons.Default.ArrowBack, contentDescription = "Back", tint = Color.White)
            }
            Text("Notifications", color = Color.White, fontSize = 20.sp, fontWeight = FontWeight.Bold)
        }

        when {
            state.isLoading && state.items.isEmpty() -> Box(Modifier.fillMaxWidth().padding(40.dp), contentAlignment = Alignment.Center) {
                CircularProgressIndicator(color = Color(0xFFDC2626))
            }
            state.items.isEmpty() -> Column(
                modifier = Modifier.fillMaxWidth().padding(top = 80.dp),
                horizontalAlignment = Alignment.CenterHorizontally
            ) {
                Text("🔔", fontSize = 52.sp)
                Spacer(Modifier.height(12.dp))
                Text("No notifications yet", color = Color(0xFF555555), fontSize = 15.sp)
            }
            else -> LazyColumn(contentPadding = PaddingValues(16.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
                items(state.items) { n ->
                    Card(
                        modifier = Modifier.fillMaxWidth().clickable { if (!n.is_read) vm.markRead(n.id) },
                        shape = RoundedCornerShape(14.dp),
                        colors = CardDefaults.cardColors(
                            containerColor = if (n.is_read) Color(0xFF121212) else Color(0xFF1D1414)
                        )
                    ) {
                        Row(modifier = Modifier.padding(16.dp), verticalAlignment = Alignment.Top) {
                            Text(if (n.type == "series_available") "🎬" else "🔔", fontSize = 22.sp)
                            Spacer(Modifier.width(12.dp))
                            Column(Modifier.weight(1f)) {
                                Text(n.title, color = Color.White, fontSize = 15.sp, fontWeight = FontWeight.SemiBold)
                                n.body?.let {
                                    Spacer(Modifier.height(4.dp))
                                    Text(it, color = Color(0xFF999999), fontSize = 13.sp)
                                }
                                n.created_at?.let {
                                    Spacer(Modifier.height(6.dp))
                                    Text(it, color = Color(0xFF555555), fontSize = 11.sp)
                                }
                            }
                            if (!n.is_read) {
                                Box(Modifier.size(10.dp).background(Color(0xFFDC2626), CircleShape))
                            }
                        }
                    }
                }
            }
        }
    }
}
