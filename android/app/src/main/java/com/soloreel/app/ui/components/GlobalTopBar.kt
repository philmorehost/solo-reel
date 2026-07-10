package com.soloreel.app.ui.components

import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Text
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
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
            // logo_topbar is a tight crop of the wordmark (logo_splash is a square canvas
            // that's mostly black padding — at any given height, most of that box was
            // invisible filler, making the visible logo look small/blurry).
            painter = androidx.compose.ui.res.painterResource(id = com.soloreel.app.R.drawable.logo_topbar),
            contentDescription = "Logo",
            modifier = Modifier.height(40.dp),
            contentScale = ContentScale.Fit
        )
        Row(verticalAlignment = Alignment.CenterVertically) {
            Row(
                verticalAlignment = Alignment.CenterVertically,
                modifier = Modifier
                    .clip(RoundedCornerShape(20.dp))
                    .background(Color(0xFFEAB308).copy(alpha = 0.15f))
                    .clickable { navController.navigate(Screen.VipPlans.route) }
                    .padding(horizontal = 10.dp, vertical = 6.dp)
            ) {
                Text("👑", fontSize = 16.sp)
                Spacer(Modifier.width(4.dp))
                Text("VIP", color = Color(0xFFEAB308), fontWeight = FontWeight.Bold, fontSize = 13.sp)
            }
            Spacer(Modifier.width(8.dp))
            NotificationBell(unreadCount = notifState.unreadCount) {
                navController.navigate(Screen.Notifications.route)
            }
        }
    }
}
