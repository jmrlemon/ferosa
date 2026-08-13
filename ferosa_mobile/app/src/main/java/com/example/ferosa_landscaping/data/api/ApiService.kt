package com.example.ferosa_landscaping.data.api

import com.example.ferosa_landscaping.data.api.models.*
import okhttp3.ResponseBody
import retrofit2.Response
import retrofit2.http.*

interface ApiService {
    @GET("api/ar/products")
    suspend fun getArProducts(): Response<List<ArProductDto>>

    @GET("api/ar/products/{id}/model")
    @Streaming
    suspend fun downloadModel(@Path("id") productId: Int): Response<ResponseBody>

    @POST("api/ar/cart/add")
    suspend fun addToCart(@Body request: AddToCartRequest): Response<CartResponse>

    @GET("api/ar/products/{id}/model-info")
    suspend fun getModelInfo(@Path("id") productId: Int): Response<ModelInfoDto>

    // ── Native shell ────────────────────────────────────────────────────────
    // Mirrors the `mobile` route group in routes/api.php; ApiContractTest keeps
    // the two in step.

    @GET("api/mobile/summary")
    suspend fun getMobileSummary(): Response<MobileSummaryDto>

    @GET("api/mobile/estimator-rates")
    suspend fun getEstimatorRates(): Response<EstimatorRateCardDto>
}
