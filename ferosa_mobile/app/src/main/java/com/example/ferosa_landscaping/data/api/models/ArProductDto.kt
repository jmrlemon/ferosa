package com.example.ferosa_landscaping.data.api.models

import com.google.gson.annotations.SerializedName

data class ArProductDto(
    val id: Int,
    val name: String,
    val description: String?,
    val price: Double,
    @SerializedName("thumbnail_url") val thumbnailUrl: String?,
    @SerializedName("model_url") val modelUrl: String,
    @SerializedName("height_cm") val heightCm: Float,
    val category: String?,
    @SerializedName("in_stock") val inStock: Boolean,
)

data class AddToCartRequest(
    @SerializedName("product_id") val productId: Int,
    val quantity: Int = 1
)

data class CartResponse(
    val success: Boolean,
    val message: String?,
    @SerializedName("cart_count") val cartCount: Int?
)

data class ModelInfoDto(
    @SerializedName("updated_at") val updatedAt: String,
    @SerializedName("file_size") val fileSize: Long
)
