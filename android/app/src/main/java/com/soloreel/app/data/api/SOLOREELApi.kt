package com.soloreel.app.data.api

import com.soloreel.app.data.model.Banner
import com.soloreel.app.data.model.Series
import com.soloreel.app.data.model.Episode
import com.soloreel.app.data.model.User
import com.soloreel.app.data.model.Shelf
import com.soloreel.app.data.model.CoinPackage
import com.soloreel.app.data.model.PaymentInit
import com.soloreel.app.data.model.WatchHistoryItem
import retrofit2.Response
import retrofit2.http.*
import com.google.gson.JsonElement

data class ApiResponse<T>(val status: Boolean?, val data: T?, val message: String?)
data class LoginBody(val email: String, val password: String)
data class RegisterBody(val username: String, val email: String, val password: String, val display_name: String)
data class GoogleLoginBody(val email: String, val displayName: String)
data class AuthResult(val user: User?, val token: String?)

interface SOLOREELApi {
    @GET("api/v1/banners")
    suspend fun getBanners(@Query("active") active: String = "true"): ApiResponse<List<Banner>>

    @GET("api/v1/series")
    suspend fun getSeries(@Query("shelf") shelf: String? = null, @Query("size") size: Int = 20): ApiResponse<List<Series>>

    @GET("api/v1/series/{slug}/by-slug")
    suspend fun getSeriesDetail(@Path("slug") slug: String): ApiResponse<Series>

    @GET("api/v1/episodes/{slug}/by-slug")
    suspend fun getEpisode(@Path("slug") slug: String): ApiResponse<Episode>

    @GET("api/v1/series/{id}/episodes")
    suspend fun getEpisodes(@Path("id") seriesId: Int): ApiResponse<List<Episode>>

    @GET("api/v1/search")
    suspend fun search(@Query("q") query: String, @Query("size") size: Int = 20): ApiResponse<List<Series>>

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

    @GET("api/v1/coin-packages")
    suspend fun getCoinPackages2(): ApiResponse<List<CoinPackage>>

    @POST("api/v1/coins/purchase")
    suspend fun purchaseCoins(@Body body: Map<String, Int>): ApiResponse<PaymentInit>

    @GET("api/v1/payment/verify")
    suspend fun verifyPayment(@Query("reference") reference: String): ApiResponse<JsonElement>
}
