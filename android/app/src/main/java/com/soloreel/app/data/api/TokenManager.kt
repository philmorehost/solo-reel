package com.soloreel.app.data.api

import android.content.Context
import android.content.SharedPreferences
import dagger.hilt.android.qualifiers.ApplicationContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class TokenManager @Inject constructor(@ApplicationContext context: Context) {
    private val prefs: SharedPreferences = context.getSharedPreferences("soloreel_auth", Context.MODE_PRIVATE)

    var accessToken: String?
        get() = prefs.getString("access_token", null)
        set(value) { prefs.edit().putString("access_token", value).apply() }

    var refreshToken: String?
        get() = prefs.getString("refresh_token", null)
        set(value) { prefs.edit().putString("refresh_token", value).apply() }

    var userEmail: String?
        get() = prefs.getString("user_email", null)
        set(value) { prefs.edit().putString("user_email", value).apply() }

    var userName: String?
        get() = prefs.getString("user_name", null)
        set(value) { prefs.edit().putString("user_name", value).apply() }

    var userCoins: Double
        get() = prefs.getFloat("user_coins", 0f).toDouble()
        set(value) { prefs.edit().putFloat("user_coins", value.toFloat()).apply() }

    var savedPassword: String?
        get() = prefs.getString("saved_password", null)
        set(value) { prefs.edit().putString("saved_password", value).apply() }

    val isLoggedIn: Boolean get() = accessToken != null

    fun clear() {
        prefs.edit().clear().apply()
    }
}
