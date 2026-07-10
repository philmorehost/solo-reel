package com.soloreel.app.data.model

import com.google.gson.annotations.SerializedName

data class VipPlan(
    val id: Int,
    val name: String,
    val price: Double,
    val currency: String,
    val duration_days: Int,
    val perk_free_unlocks: Boolean? = false,
    val perk_ad_free: Boolean? = false
)

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
    val episode_count: Int?,
    val episodes: List<Episode>? = null,
    val is_hot: Boolean? = false,
    val is_new: Boolean? = false,
    // Only populated by the RANKING tab (/api/v1/ranking) — total likes across the series' episodes.
    val like_count: Int? = null
)

/** NEW tab: /api/v1/series/new — coming-soon vs. recently released. */
data class NewReleases(val coming_soon: List<Series>, val all_new: List<Series>)

/** CATEGORIES tab: /api/v1/series/categories — one shelf per genre. */
data class CategoryGroup(val genre: String, val series: List<Series>)

/** My List page: /api/v1/me/list -> {history, liked, saved}. */
data class MyListData(
    val history: List<ContinueWatchingItem>,
    val liked: List<Series>,
    val saved: List<Series>
)

/** For You feed item: /api/v1/for-you — an admin-uploaded trailer, "Watch Now" resumes the series. */
data class ForYouItem(
    val episode_id: Int,
    val trailer_url: String,
    val series_id: Int,
    val series_title: String,
    val series_slug: String,
    val cover_image_url: String?,
    val resume_slug: String?
)

data class Episode(
    val id: Int,
    val title: String,
    val slug: String,
    val series_id: Int?,
    val series_title: String?,
    val series_slug: String? = null,
    val video_hls_url: String?,
    val thumbnail_url: String?,
    val description: String?,
    val is_free: Boolean?,
    val is_unlocked: Boolean?,
    val coin_cost: Double?,
    val unlock_method: String?,
    val episode_number: Int?,
    val video_duration_seconds: Int?,
    val like_count: Int? = 0,
    val comment_count: Int? = 0,
    val save_count: Int? = 0,
    val share_count: Int? = 0,
    val is_liked_by_viewer: Boolean? = false,
    val is_saved_by_viewer: Boolean? = false,
    val can_share: Boolean? = false
)

data class LikeSaveResult(val liked: Boolean? = null, val saved: Boolean? = null, val count: Int = 0)

data class Comment(val id: Int, val author: String?, val body: String, val created_at: String?)

data class CommentsPage(val items: List<Comment>, val total: Int)

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

/** GET /api/v1/user/transactions — coin ledger entries + VIP subscription purchases, merged. */
data class Transaction(
    val description: String,
    val amount: Double,
    val currency: String?,
    val kind: String, // "coins" | "vip"
    val created_at: String
)

data class ContinueWatchingItem(
    val id: Int,
    val title: String,
    val slug: String,
    val cover_image_url: String?,
    val episode_count: Int?,
    val episode_slug: String,
    val episode_number: Int?
)

data class ResumeEpisode(val slug: String, val episode_number: Int?, val is_first_watch: Boolean?)

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
