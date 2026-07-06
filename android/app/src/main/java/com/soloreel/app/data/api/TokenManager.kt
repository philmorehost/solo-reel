package com.soloreel.app.data.api

import android.content.Context
import android.content.SharedPreferences
import dagger.hilt.android.qualifiers.ApplicationContext
import java.util.UUID
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

    // Guest identity — auto-generated, persists across app sessions
    val guestId: String
        get() {
            val stored = prefs.getString("guest_id", null)
            if (stored != null) return stored
            val newId = UUID.randomUUID().toString()
            prefs.edit().putString("guest_id", newId).apply()
            return newId
        }

    var guestCoins: Double
        get() = prefs.getFloat("guest_coins", 0f).toDouble()
        set(value) { prefs.edit().putFloat("guest_coins", value.toFloat()).apply() }

    // Highest notification id already surfaced as a phone notification
    var lastSeenNotificationId: Int
        get() = prefs.getInt("last_seen_notification_id", 0)
        set(value) { prefs.edit().putInt("last_seen_notification_id", value).apply() }

    val isLoggedIn: Boolean get() = accessToken != null

    val isGuest: Boolean get() = !isLoggedIn

    fun clear() {
        // Keep guestId and guestCoins on logout so guest state is preserved
        val gid = guestId
        val gc = guestCoins
        prefs.edit().clear().apply()
        prefs.edit().putString("guest_id", gid).putFloat("guest_coins", gc.toFloat()).apply()
    }
}
