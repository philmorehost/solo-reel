package com.soloreel.app.ui.navigation

import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.ShoppingCart
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.navigation.NavHostController
import androidx.navigation.NavType
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import androidx.navigation.navArgument
import com.soloreel.app.ui.auth.AuthScreen
import com.soloreel.app.ui.auth.RegisterScreen
import com.soloreel.app.ui.coins.CoinShopScreen
import com.soloreel.app.ui.home.HomeScreen
import com.soloreel.app.ui.player.PlayerScreen
import com.soloreel.app.ui.profile.ProfileScreen
import com.soloreel.app.ui.search.SearchScreen
import com.soloreel.app.ui.series.SeriesDetailScreen

data class BottomNavItem(val label: String, val icon: ImageVector, val route: String)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun NavGraph(isLoggedIn: Boolean) {
    val navController = rememberNavController()
    val navBackStackEntry by navController.currentBackStackEntryAsState()
    val currentRoute = navBackStackEntry?.destination?.route

    val items = listOf(
        BottomNavItem("Home", Icons.Default.Home, Screen.Home.route),
        BottomNavItem("Search", Icons.Default.Search, Screen.Search.route),
        BottomNavItem("Coins", Icons.Default.ShoppingCart, Screen.Coins.route),
        BottomNavItem("Profile", Icons.Default.Person, Screen.Profile.route),
    )

    Scaffold(
        bottomBar = {
            if (currentRoute in items.map { it.route }) {
                NavigationBar(containerColor = androidx.compose.ui.graphics.Color(0xFF111111)) {
                    items.forEach { item ->
                        NavigationBarItem(
                            icon = { Icon(item.icon, contentDescription = item.label) },
                            label = { Text(item.label, style = MaterialTheme.typography.labelSmall) },
                            selected = currentRoute == item.route,
                            onClick = {
                                if (currentRoute != item.route) {
                                    navController.navigate(item.route) {
                                        popUpTo(Screen.Home.route) { saveState = true }
                                        launchSingleTop = true
                                        restoreState = true
                                    }
                                }
                            },
                            colors = NavigationBarItemDefaults.colors(
                                selectedIconColor = androidx.compose.ui.graphics.Color(0xFFDC2626),
                                selectedTextColor = androidx.compose.ui.graphics.Color(0xFFDC2626),
                                unselectedIconColor = androidx.compose.ui.graphics.Color(0xFF6B7280),
                                unselectedTextColor = androidx.compose.ui.graphics.Color(0xFF6B7280),
                                indicatorColor = androidx.compose.ui.graphics.Color(0xFFDC2626).copy(alpha = 0.1f),
                            )
                        )
                    }
                }
            }
        }
    ) { padding ->
        NavHost(
            navController = navController,
            startDestination = if (isLoggedIn) Screen.Home.route else Screen.Auth.route,
            modifier = Modifier.padding(padding)
        ) {
            composable(Screen.Auth.route) {
                AuthScreen(
                    onLoginSuccess = {
                        navController.navigate(Screen.Home.route) {
                            popUpTo(Screen.Auth.route) { inclusive = true }
                        }
                    },
                    onNavigateToRegister = { navController.navigate(Screen.Register.route) }
                )
            }
            composable(Screen.Register.route) {
                RegisterScreen(
                    onRegisterSuccess = {
                        navController.navigate(Screen.Home.route) {
                            popUpTo(Screen.Register.route) { inclusive = true }
                        }
                    },
                    onNavigateToLogin = { navController.popBackStack() }
                )
            }
            composable(Screen.Home.route) { HomeScreen(navController = navController) }
            composable(Screen.Search.route) { SearchScreen(navController = navController) }
            composable(Screen.Coins.route) { CoinShopScreen() }
            composable(Screen.Profile.route) {
                ProfileScreen(
                    onLogout = {
                        navController.navigate(Screen.Auth.route) {
                            popUpTo(0) { inclusive = true }
                        }
                    }
                )
            }
            composable(
                Screen.SeriesDetail.route,
                arguments = listOf(navArgument("slug") { type = NavType.StringType })
            ) { backStackEntry ->
                val slug = backStackEntry.arguments?.getString("slug") ?: ""
                SeriesDetailScreen(slug = slug, navController = navController)
            }
            composable(
                Screen.EpisodePlayer.route,
                arguments = listOf(navArgument("slug") { type = NavType.StringType })
            ) { backStackEntry ->
                val slug = backStackEntry.arguments?.getString("slug") ?: ""
                PlayerScreen(slug = slug)
            }
        }
    }
}
