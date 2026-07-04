package com.soloreel.app.data.api

import retrofit2.http.GET
import retrofit2.http.Path
import retrofit2.http.Query
import retrofit2.http.POST
import retrofit2.http.Body

data class ApiResponse<T>(val data: T?, val error: String?, val message: String?, val token: String?)

data class Series(val id: Int, val title: String, val slug: String, val cover_image: String?, val hero_image: String?, val synopsis: String)
data class Episode(val id: Int, val title: String, val slug: String, val video_url: String, val thumbnail_url: String, val is_free: Boolean, val coin_cost: Double)
data class Banner(val id: Int, val title: String, val image_url: String)
data class CoinPackage(val id: Int, val name: String, val coins: Double, val price: Double, val currency: String, val color_code: String)
data class User(val id: Int, val username: String, val email: String, val coin_balance: Double)
data class LoginRequest(val email: String, val password: String)

interface SOLOREELApi {
    @GET("api/v1/banners")
    suspend fun getBanners(@Query("active") active: Boolean = true): ApiResponse<List<Banner>>

    @GET("api/v1/series")
    suspend fun getSeries(@Query("size") size: Int = 12): ApiResponse<List<Series>>

    @GET("api/v1/series/{slug}")
    suspend fun getSeriesDetail(@Path("slug") slug: String): ApiResponse<Series>

    @POST("api/v1/auth/login")
    suspend fun login(@Body request: LoginRequest): ApiResponse<User>

    @GET("api/v1/coin-packages")
    suspend fun getCoinPackages(): ApiResponse<List<CoinPackage>>

    @GET("api/v1/user/profile")
    suspend fun getProfile(): ApiResponse<User>
}
