package com.soloreel.app.data.model

import com.google.gson.annotations.SerializedName

data class Banner(
    val id: Int,
    val title: String?,
    val subtitle: String?,
    val image_url: String?,
    val link_url: String?,
    val is_ad: Boolean? = false,
    val media_type: String? = "image",
    val duration_seconds: Int? = 5
)

data class InterstitialAd(
    val id: Int,
    val title: String?,
    val media_url: String?,
    val media_type: String?,
    val target_url: String?
)

data class AdPricing(
    val duration_seconds: Int,
    val platform_placement: String,
    val price: Double,
    val currency: String?
)

data class MyAd(
    val id: Int,
    val title: String?,
    val media_url: String?,
    val media_type: String?,
    val target_url: String?,
    val duration_seconds: Int,
    val platform_placement: String,
    val payment_status: String,
    val is_active: Boolean,
    val is_expired: Boolean,
    val expires_at: String?
)

data class Series(
    val id: Int,
    val title: String,
    val slug: String,
    @SerializedName("cover_image_url", alternate = ["cover_image"])
    val cover_image_url: String?,
    val synopsis: String?,
    val genre: String?,
    val status: String?,
    val episode_count: Int?
)

data class Episode(
    val id: Int,
    val title: String,
    val slug: String,
    val series_id: Int?,
    val series_title: String?,
    val video_hls_url: String?,
    val thumbnail_url: String?,
    val description: String?,
    val is_free: Boolean?,
    val is_unlocked: Boolean?,
    val coin_cost: Double?,
    val unlock_method: String?,
    val episode_number: Int?,
    val video_duration_seconds: Int?
)

data class Shelf(val id: Int, val name: String, val slug: String, val emoji: String?)

data class CoinPackage(val id: Int, val name: String, val coins: Int, val price: Double, val currency: String, val color_code: String?)

data class User(
    val id: Int,
    val username: String,
    val email: String,
    @SerializedName("display_name") val displayName: String?,
    val coin_balance: Double?,
    val role: String?,
    val bonus_coins: Double?,
    val bonus_expires_at: String?
)

data class PaymentInit(val authorization_url: String?, val reference: String?)

data class WatchHistoryItem(
    val id: Int,
    val series_title: String?,
    val episode_title: String?,
    val thumbnail_url: String?,
    val slug: String?,
    val watched_at: String?,
    val progress_seconds: Int?
)

data class Favorite(val id: Int, val series: Series?, val title: String?, val slug: String?, val cover_image_url: String?)

data class GuestWallet(val guest_id: String, val coin_balance: Double)

data class WeeklyBonusStatus(
    val bonus_coins: Double,
    val bonus_expires_at: String?,
    val weekly_amount: Double
)

data class SeriesRequest(
    val title: String,
    val description: String?,
    val email: String?,
    val guest_id: String?
)

data class AppNotification(
    val id: Int,
    val title: String,
    val body: String?,
    val type: String?,
    val series_id: Int?,
    val is_read: Boolean,
    val created_at: String?
)
