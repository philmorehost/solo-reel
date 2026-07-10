package com.soloreel.app.data.api

import com.soloreel.app.data.model.*
import retrofit2.http.*
import com.google.gson.JsonElement
import okhttp3.MultipartBody
import okhttp3.RequestBody

data class ApiResponse<T>(val status: Boolean?, val data: T?, val message: String?, val error: String?)

/** Extracts the server's `error`/`message` field from an HTTP error body instead of "HTTP 401 ...". */
fun Throwable.apiMessage(fallback: String): String {
    if (this is retrofit2.HttpException) {
        try {
            val body = response()?.errorBody()?.string()
            if (!body.isNullOrBlank()) {
                val json = com.google.gson.Gson().fromJson(body, com.google.gson.JsonObject::class.java)
                val errorEl = json?.get("error")
                val messageEl = json?.get("message")
                val msg = when {
                    errorEl != null && errorEl.isJsonPrimitive -> errorEl.asString
                    messageEl != null && messageEl.isJsonPrimitive -> messageEl.asString
                    else -> null
                }
                if (!msg.isNullOrBlank()) return msg
            }
        } catch (_: Exception) { }
    }
    return message ?: fallback
}
data class LoginBody(val email: String, val password: String)
data class RegisterBody(val username: String, val email: String, val password: String, val display_name: String, val guest_id: String? = null)
data class GoogleLoginBody(val email: String, val displayName: String, val guest_id: String? = null)
data class AuthResult(val user: User?, val token: String?, val requires_verification: Boolean? = null, val user_id: Int? = null)
data class GuestInitBody(val guest_id: String)
data class GuestPurchaseBody(val package_id: Int, val guest_id: String, val email: String? = null)
data class VerifyOtpBody(val user_id: Int, val otp: String, val guest_id: String? = null)
data class ResendOtpBody(val email: String)

interface SOLOREELApi {
    @GET("api/v1/banners")
    suspend fun getBanners(@Query("active") active: String = "true"): ApiResponse<List<Banner>>

    @GET("api/v1/series")
    suspend fun getSeries(@Query("shelf") shelf: String? = null, @Query("size") size: Int = 20): ApiResponse<List<Series>>

    @GET("api/v1/series/{slug}/by-slug")
    suspend fun getSeriesDetail(@Path("slug") slug: String): ApiResponse<Series>

    @GET("api/v1/episodes/{slug}/by-slug")
    suspend fun getEpisode(@Path("slug") slug: String, @Query("guest_id") guestId: String? = null): ApiResponse<Episode>

    @GET("api/v1/series/{id}/episodes")
    suspend fun getEpisodes(@Path("id") seriesId: Int, @Query("guest_id") guestId: String? = null): ApiResponse<List<Episode>>

    @GET("api/v1/search")
    suspend fun search(@Query("q") query: String, @Query("size") size: Int = 20, @Query("category") category: String? = null): ApiResponse<List<Series>>

    // Content hub tabs — HOT / NEW / RANKING / CATEGORIES / TV SERIES / MOVIES.
    // TV Series and Movies reuse search()'s existing "category" filter above.
    @GET("api/v1/series/hot")
    suspend fun getHotSeries(@Query("size") size: Int = 24): ApiResponse<List<Series>>

    @GET("api/v1/series/new")
    suspend fun getNewSeries(): ApiResponse<com.soloreel.app.data.model.NewReleases>

    @GET("api/v1/series/categories")
    suspend fun getCategories(): ApiResponse<List<com.soloreel.app.data.model.CategoryGroup>>

    @GET("api/v1/ranking")
    suspend fun getRanking(@Query("limit") limit: Int = 30): ApiResponse<List<Series>>

    // "For You" — random admin-uploaded trailer feed, and "My List" — history/liked/saved.
    @GET("api/v1/for-you")
    suspend fun getForYou(@Query("guest_id") guestId: String? = null, @Query("limit") limit: Int = 20): ApiResponse<List<com.soloreel.app.data.model.ForYouItem>>

    @GET("api/v1/me/list")
    suspend fun getMyList(@Query("guest_id") guestId: String? = null): ApiResponse<com.soloreel.app.data.model.MyListData>

    @DELETE("api/v1/me/list/saved/{seriesId}")
    suspend fun removeSavedSeries(@Path("seriesId") seriesId: Int, @Query("guest_id") guestId: String? = null): ApiResponse<JsonElement>

    @GET("api/v1/coin-packages")
    suspend fun getCoinPackages(): ApiResponse<List<CoinPackage>>

    @GET("api/v1/shelves")
    suspend fun getShelves(@Query("active") active: String = "true"): ApiResponse<List<Shelf>>

    @POST("api/v1/auth/login")
    suspend fun login(@Body body: LoginBody): ApiResponse<AuthResult>

    @POST("api/v1/auth/google")
    suspend fun googleLogin(@Body body: GoogleLoginBody): ApiResponse<AuthResult>

    @POST("api/v1/auth/register")
    suspend fun register(@Body body: RegisterBody): ApiResponse<AuthResult>

    @POST("api/v1/auth/verify-otp")
    suspend fun verifyOtp(@Body body: VerifyOtpBody): ApiResponse<AuthResult>

    @POST("api/v1/auth/resend-otp")
    suspend fun resendOtp(@Body body: ResendOtpBody): ApiResponse<JsonElement>

    @GET("api/v1/user/profile")
    suspend fun getProfile(): ApiResponse<User>

    @PUT("api/v1/user/profile")
    suspend fun updateProfile(@Body body: Map<String, String>): ApiResponse<User>

    @GET("api/v1/user/favorites")
    suspend fun getFavorites(): ApiResponse<List<Series>>

    @POST("api/v1/user/favorites/{seriesId}")
    suspend fun addFavorite(@Path("seriesId") seriesId: Int): ApiResponse<JsonElement>

    @DELETE("api/v1/user/favorites/{seriesId}")
    suspend fun removeFavorite(@Path("seriesId") seriesId: Int): ApiResponse<JsonElement>

    @GET("api/v1/user/watch-history")
    suspend fun getWatchHistory(): ApiResponse<List<WatchHistoryItem>>

    @GET("api/v1/user/transactions")
    suspend fun getTransactions(): ApiResponse<List<com.soloreel.app.data.model.Transaction>>

    @GET("api/v1/user/continue-watching")
    suspend fun getContinueWatching(@Query("guest_id") guestId: String? = null): ApiResponse<List<ContinueWatchingItem>>

    @GET("api/v1/series/{id}/resume")
    suspend fun getResumeEpisode(@Path("id") seriesId: Int, @Query("guest_id") guestId: String? = null): ApiResponse<ResumeEpisode>

    @GET("api/v1/series/resume-batch")
    suspend fun getResumeBatch(@Query("ids") ids: String, @Query("guest_id") guestId: String? = null): ApiResponse<Map<String, String>>

    @POST("api/v1/episodes/{id}/progress")
    suspend fun recordProgress(@Path("id") episodeId: Int, @Body body: Map<String, String> = emptyMap()): ApiResponse<JsonElement>

    @POST("api/v1/episodes/{id}/like")
    suspend fun toggleLike(@Path("id") episodeId: Int, @Body body: Map<String, String> = emptyMap()): ApiResponse<LikeSaveResult>

    @POST("api/v1/episodes/{id}/save")
    suspend fun toggleSave(@Path("id") episodeId: Int, @Body body: Map<String, String> = emptyMap()): ApiResponse<LikeSaveResult>

    @GET("api/v1/episodes/{id}/comments")
    suspend fun getComments(@Path("id") episodeId: Int, @Query("offset") offset: Int = 0, @Query("limit") limit: Int = 20): ApiResponse<CommentsPage>

    @POST("api/v1/episodes/{id}/comments")
    suspend fun postComment(@Path("id") episodeId: Int, @Body body: Map<String, String>): ApiResponse<Comment>

    @POST("api/v1/episodes/{id}/share")
    suspend fun recordShare(@Path("id") episodeId: Int, @Body body: Map<String, String> = emptyMap()): ApiResponse<JsonElement>

    @GET("api/v1/user/bonus-status")
    suspend fun getBonusStatus(): ApiResponse<WeeklyBonusStatus>

    @POST("api/v1/coins/purchase")
    suspend fun purchaseCoins(@Body body: Map<String, Int>): ApiResponse<PaymentInit>

    @POST("api/v1/coins/guest-purchase")
    suspend fun guestPurchaseCoins(@Body body: GuestPurchaseBody): ApiResponse<PaymentInit>

    @GET("api/v1/payment/verify")
    suspend fun verifyPayment(@Query("reference") reference: String): ApiResponse<JsonElement>

    // VIP subscription — an alternative to buying coins, not a replacement;
    // registered users only (see 021_vip_subscriptions.sql for why).
    @GET("api/v1/vip/plans")
    suspend fun getVipPlans(): ApiResponse<List<com.soloreel.app.data.model.VipPlan>>

    @POST("api/v1/vip/purchase")
    suspend fun purchaseVip(@Body body: Map<String, Int>): ApiResponse<PaymentInit>

    @GET("api/v1/user/vip-status")
    suspend fun getVipStatus(): ApiResponse<Map<String, JsonElement>>

    // Guest endpoints
    @POST("api/v1/guest/init")
    suspend fun initGuest(@Body body: GuestInitBody): ApiResponse<GuestWallet>

    @GET("api/v1/guest/balance")
    suspend fun getGuestBalance(@Query("guest_id") guestId: String): ApiResponse<GuestWallet>

    // Series requests
    @POST("api/v1/series-requests")
    suspend fun createSeriesRequest(@Body body: SeriesRequest): ApiResponse<JsonElement>

    // In-app notifications
    @GET("api/v1/notifications")
    suspend fun getNotifications(@Query("guest_id") guestId: String? = null): ApiResponse<List<AppNotification>>

    @POST("api/v1/notifications/{id}/read")
    suspend fun markNotificationRead(@Path("id") id: Int, @Body body: Map<String, String> = emptyMap()): ApiResponse<JsonElement>

    @GET("api/v1/auth/google-config")
    suspend fun getGoogleConfig(): ApiResponse<Map<String, String>>

    @GET("api/v1/ads-config")
    suspend fun getAdsConfig(): ApiResponse<Map<String, String>>

    @GET("api/v1/ads/interstitial")
    suspend fun getInterstitialAd(): ApiResponse<InterstitialAd?>

    @POST("api/v1/user/claim-install-bonus")
    suspend fun claimInstallBonus(): ApiResponse<Map<String, Any>>

    @POST("api/v1/episodes/unlock-with-ad/{id}")
    suspend fun unlockWithAd(@Path("id") episodeId: Int, @Body body: Map<String, String> = emptyMap()): ApiResponse<JsonElement>

    @POST("api/v1/episodes/unlock/{id}")
    suspend fun unlockWithCoins(@Path("id") episodeId: Int, @Body body: Map<String, String> = emptyMap()): ApiResponse<JsonElement>

    // Self-serve ad marketplace
    @GET("api/v1/ads/pricing")
    suspend fun getAdsPricing(): ApiResponse<List<AdPricing>>

    @GET("api/v1/ads/my-ads")
    suspend fun getMyAds(): ApiResponse<List<MyAd>>

    @Multipart
    @POST("api/v1/ads/subscribe")
    suspend fun subscribeAd(
        @Part("title") title: RequestBody,
        @Part("target_url") targetUrl: RequestBody,
        @Part("duration_seconds") durationSeconds: RequestBody,
        @Part("platform_placement") platformPlacement: RequestBody,
        @Part mediaFile: MultipartBody.Part
    ): ApiResponse<PaymentInit>

    @POST("api/v1/ads/renew/{id}")
    suspend fun renewAd(@Path("id") id: Int): ApiResponse<PaymentInit>
}
