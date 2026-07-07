package com.soloreel.app

import android.content.Intent
import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.soloreel.app.ui.splash.SplashScreen
import com.soloreel.app.ui.navigation.NavGraph
import com.soloreel.app.ui.theme.SoloreelTypography
import com.soloreel.app.data.api.PaymentResultBus
import com.soloreel.app.data.api.TokenManager
import javax.inject.Inject
import dagger.hilt.android.AndroidEntryPoint

import android.view.WindowManager
import androidx.fragment.app.FragmentActivity

@AndroidEntryPoint
class MainActivity : FragmentActivity() {
    @Inject
    lateinit var tokenManager: TokenManager

    private val notificationPermissionLauncher =
        registerForActivityResult(androidx.activity.result.contract.ActivityResultContracts.RequestPermission()) { }

    override fun onNewIntent(intent: Intent) {
        super.onNewIntent(intent)
        // The Chrome Custom Tab payment flow returns here via
        // soloreel://payment-complete while this Activity is already running
        // (singleTask launch mode), so the result arrives as a new intent
        // rather than a fresh onCreate.
        PaymentResultBus.handleIntent(intent)
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        PaymentResultBus.handleIntent(intent)

        // Prevent screenshots and screen recording
        window.setFlags(
            WindowManager.LayoutParams.FLAG_SECURE,
            WindowManager.LayoutParams.FLAG_SECURE
        )
        if (android.os.Build.VERSION.SDK_INT >= android.os.Build.VERSION_CODES.TIRAMISU &&
            androidx.core.content.ContextCompat.checkSelfPermission(this, android.Manifest.permission.POST_NOTIFICATIONS)
                != android.content.pm.PackageManager.PERMISSION_GRANTED
        ) {
            notificationPermissionLauncher.launch(android.Manifest.permission.POST_NOTIFICATIONS)
        }
        setContent {
            MaterialTheme(colorScheme = darkColorScheme(), typography = SoloreelTypography) {
                Surface(
                    modifier = Modifier.fillMaxSize(),
                    color = MaterialTheme.colorScheme.background
                ) {
                    var showSplash by remember { mutableStateOf(true) }
                    if (showSplash) {
                        SplashScreen(onFinished = { showSplash = false })
                    } else {
                        NavGraph(isLoggedIn = tokenManager.isLoggedIn)
                    }
                }
            }
        }
    }
}
