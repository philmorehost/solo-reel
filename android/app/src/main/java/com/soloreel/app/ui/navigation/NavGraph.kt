package com.soloreel.app.ui.navigation

import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Bookmark
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.ShoppingCart
import androidx.compose.material.icons.filled.Whatshot
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
import com.soloreel.app.ui.foryou.ForYouScreen
import com.soloreel.app.ui.home.HomeScreen
import com.soloreel.app.ui.mylist.MyListScreen
import com.soloreel.app.ui.notifications.NotificationsScreen
import com.soloreel.app.ui.player.PlayerScreen
import com.soloreel.app.ui.profile.ProfileScreen
import com.soloreel.app.ui.series.SeriesDetailScreen
import com.soloreel.app.ui.vip.VipPlansScreen

data class BottomNavItem(val label: String, val icon: ImageVector, val route: String)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun NavGraph(isLoggedIn: Boolean) {
    val navController = rememberNavController()
    val navBackStackEntry by navController.currentBackStackEntryAsState()
    val currentRoute = navBackStackEntry?.destination?.route

    val items = listOf(
        BottomNavItem("Home", Icons.Default.Home, Screen.Home.route),
        BottomNavItem("For You", Icons.Default.Whatshot, Screen.ForYou.route),
        BottomNavItem("My List", Icons.Default.Bookmark, Screen.MyList.route),
        BottomNavItem("Coins", Icons.Default.ShoppingCart, Screen.Coins.route),
        BottomNavItem("Profile", Icons.Default.Person, Screen.Profile.route),
    )

    Scaffold(
        topBar = {
            if (currentRoute in items.map { it.route }) {
                com.soloreel.app.ui.components.GlobalTopBar(navController = navController)
            }
        },
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
                                selectedIconColor = androidx.compose.ui.graphics.Color(0xFFFF2A2A), // Brighter Red
                                selectedTextColor = androidx.compose.ui.graphics.Color(0xFFFF2A2A),
                                unselectedIconColor = androidx.compose.ui.graphics.Color(0xFFB0B0B0), // Brighter silver/gray
                                unselectedTextColor = androidx.compose.ui.graphics.Color(0xFFB0B0B0),
                                indicatorColor = androidx.compose.ui.graphics.Color(0xFFFF2A2A).copy(alpha = 0.15f),
                            )
                        )
                    }
                }
            }
        }
    ) { padding ->
        NavHost(
            navController = navController,
            startDestination = Screen.Home.route,
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
                    onRegisterSuccess = { requiresVerification, userId, email ->
                        if (requiresVerification) {
                            navController.navigate(Screen.VerifyOtp.createRoute(userId, email))
                        } else {
                            navController.navigate(Screen.Home.route) {
                                popUpTo(Screen.Register.route) { inclusive = true }
                            }
                        }
                    },
                    onNavigateToLogin = { navController.popBackStack() }
                )
            }
            composable(
                Screen.VerifyOtp.route,
                arguments = listOf(
                    navArgument("userId") { type = NavType.IntType },
                    navArgument("email") { type = NavType.StringType }
                )
            ) { backStackEntry ->
                val userId = backStackEntry.arguments?.getInt("userId") ?: 0
                val email = backStackEntry.arguments?.getString("email") ?: ""
                com.soloreel.app.ui.auth.VerifyOtpScreen(
                    userId = userId,
                    email = email,
                    onVerifySuccess = {
                        navController.navigate(Screen.Home.route) {
                            popUpTo(Screen.Auth.route) { inclusive = true }
                        }
                    },
                    onNavigateBack = { navController.popBackStack() }
                )
            }
            composable(Screen.Home.route) { HomeScreen(navController = navController) }
            composable(Screen.Notifications.route) { NotificationsScreen(navController = navController) }
            composable(Screen.ForYou.route) { ForYouScreen(navController = navController) }
            composable(Screen.MyList.route) { MyListScreen(navController = navController) }
            composable(Screen.Coins.route) { CoinShopScreen() }
            composable(Screen.VipPlans.route) { VipPlansScreen(navController = navController) }
            composable(Screen.Profile.route) { backStackEntry ->
                val profileVm: com.soloreel.app.ui.profile.ProfileViewModel = androidx.hilt.navigation.compose.hiltViewModel()
                val profileUpdated by backStackEntry.savedStateHandle.getStateFlow("profile_updated", false).collectAsState()
                LaunchedEffect(profileUpdated) {
                    if (profileUpdated) {
                        profileVm.load()
                        backStackEntry.savedStateHandle["profile_updated"] = false
                    }
                }
                ProfileScreen(
                    vm = profileVm,
                    onLogout = {
                        navController.navigate(Screen.Auth.route) {
                            popUpTo(0) { inclusive = true }
                        }
                    },
                    onNavigateToLogin = {
                        navController.navigate(Screen.Auth.route)
                    },
                    onNavigateToHistory = { navController.navigate(Screen.History.route) },
                    // "My Favorites" now opens My List (Liked/Saved/History) — the
                    // old standalone Favorites screen is superseded by it.
                    onNavigateToFavorites = { navController.navigate(Screen.MyList.route) },
                    onNavigateToEditProfile = { navController.navigate(Screen.EditProfile.route) },
                    onNavigateToCoinShop = { navController.navigate(Screen.Coins.route) },
                    onNavigateToAdvertise = { navController.navigate(Screen.Advertise.route) },
                    onNavigateToMyAds = { navController.navigate(Screen.MyAds.route) },
                    onNavigateToVip = { navController.navigate(Screen.VipPlans.route) }
                )
            }
            composable(Screen.History.route) { com.soloreel.app.ui.profile.HistoryScreen(navController) }
            composable(Screen.Favorites.route) { com.soloreel.app.ui.profile.FavoritesScreen(navController) }
            composable(Screen.EditProfile.route) { com.soloreel.app.ui.profile.EditProfileScreen(navController) }
            composable(Screen.Advertise.route) { com.soloreel.app.ui.advertise.AdvertiseScreen(navController) }
            composable(Screen.MyAds.route) { com.soloreel.app.ui.advertise.MyAdsScreen(navController) }
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
                PlayerScreen(slug = slug, navController = navController)
            }
        }
    }
}
