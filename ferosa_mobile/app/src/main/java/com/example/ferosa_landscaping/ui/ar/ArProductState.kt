package com.example.ferosa_landscaping.ui.ar

import io.github.sceneview.ar.node.AnchorNode
import io.github.sceneview.node.ModelNode

/**
 * Represents an AR-enabled product from the backend catalog.
 * Mapped from [com.example.ferosa_landscaping.data.api.models.ArProductDto].
 */
data class ArProduct(
    val id: Int,
    val name: String,
    val price: Double,
    val thumbnailUrl: String,
    val modelUrl: String,
    val heightCm: Float,
    val category: String,
    val description: String,
    val inStock: Boolean,
)

/**
 * Represents a 3D model placed in the AR scene.
 *
 * @property id Unique UUID for tracking this placement
 * @property product The product this model represents
 * @property anchorNode The ARCore anchor node fixing this model in world space
 * @property modelNode The rendered 3D model node
 * @property originalAnchor Stored during repositioning for snap-back if cancelled
 */
data class PlacedModel(
    val id: String,
    val product: ArProduct,
    val anchorNode: AnchorNode,
    val modelNode: ModelNode,
    val originalAnchor: AnchorNode? = null
)

/**
 * Sealed class representing the overall AR screen state.
 */
sealed class ArScreenState {
    /** Initial loading state while fetching products. */
    object Loading : ArScreenState()

    /** No AR-enabled products available. */
    object NoProducts : ArScreenState()

    /** AR session is ready with products loaded. */
    data class Ready(
        val products: List<ArProduct>,
        val selectedProduct: ArProduct?,
        val placedModels: List<PlacedModel>,
        val isModelLoading: Boolean,
        val isOffline: Boolean,
    ) : ArScreenState()

    /** An error occurred. */
    data class Error(val message: String, val canRetry: Boolean) : ArScreenState()
}

/**
 * Represents errors that can occur during AR operations.
 */
sealed class ArError(val message: String) {
    class NetworkError(message: String) : ArError(message)
    class AuthError(message: String) : ArError(message)
    class ModelLoadError(message: String) : ArError(message)
    class CartError(message: String) : ArError(message)
    class PlacementLimitError : ArError("Maximum 5 items placed")
}

/**
 * Explicit state for the AR add-to-cart action.
 *
 * Keeping this separate from the screen-level error state prevents the UI from
 * reporting success before the network request has actually completed.
 */
sealed interface CartActionState {
    data object Idle : CartActionState
    data class Adding(val productId: Int) : CartActionState
    data class Added(val productId: Int) : CartActionState
    data class Failed(val productId: Int, val message: String) : CartActionState
}
