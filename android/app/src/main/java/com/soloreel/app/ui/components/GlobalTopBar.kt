package com.soloreel.app.ui.components

import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.navigation.NavController
import com.soloreel.app.ui.navigation.Screen
import com.soloreel.app.ui.notifications.NotificationBell
import com.soloreel.app.ui.notifications.NotificationsViewModel

@Composable
fun GlobalTopBar(
    navController: NavController,
    notificationsViewModel: NotificationsViewModel = hiltViewModel()
) {
    val notifState by notificationsViewModel.state.collectAsState()
    val context = LocalContext.current
    LaunchedEffect(Unit) {
        notificationsViewModel.load(context)
    }

    Row(
        modifier = Modifier
            .fillMaxWidth()
            .background(Color(0xFF0A0A0A))
            .padding(horizontal = 16.dp, vertical = 16.dp)
            .statusBarsPadding(),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment = Alignment.CenterVertically
    ) {
        Image(
            painter = androidx.compose.ui.res.painterResource(id = com.soloreel.app.R.drawable.logo_splash),
            contentDescription = "Logo",
            modifier = Modifier.height(48.dp), // Increased logo size
            contentScale = ContentScale.Fit
        )
        NotificationBell(unreadCount = notifState.unreadCount) {
            navController.navigate(Screen.Notifications.route)
        }
    }
}
