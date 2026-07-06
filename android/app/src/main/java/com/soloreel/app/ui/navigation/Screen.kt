package com.soloreel.app.ui.navigation

sealed class Screen(val route: String) {
    object Home : Screen("home")
    object Search : Screen("search")
    object Coins : Screen("coins")
    object Profile : Screen("profile")
    object SeriesDetail : Screen("series/{slug}") {
        fun createRoute(slug: String) = "series/$slug"
    }
    object EpisodePlayer : Screen("episode/{slug}") {
        fun createRoute(slug: String) = "episode/$slug"
    }
    object Auth : Screen("auth")
    object Register : Screen("register")
    object Notifications : Screen("notifications")
}
