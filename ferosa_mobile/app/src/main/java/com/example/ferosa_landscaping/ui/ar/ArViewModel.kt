package com.example.ferosa_landscaping.ui.ar

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.ferosa_landscaping.data.api.ApiService
import com.example.ferosa_landscaping.data.api.models.ArProductDto
import com.example.ferosa_landscaping.data.cache.ModelCacheManager
import com.example.ferosa_landscaping.data.repository.AuthenticationException
import com.example.ferosa_landscaping.data.repository.CartOperationException
import com.example.ferosa_landscaping.data.repository.CartRepository
import com.example.ferosa_landscaping.data.repository.ModelRepository
import com.example.ferosa_landscaping.util.ConnectivityMonitor
import io.github.sceneview.ar.node.AnchorNode
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.Job
import kotlinx.coroutines.flow.*
import kotlinx.coroutines.launch
import java.util.UUID

/**
 * ViewModel managing the AR plant placement experience.
 *
 * Coordinates product loading, model placement (max 5), repositioning,
 * product info display, and add-to-cart operations.
 */
class ArViewModel(
    private val apiService: ApiService,
    internal val modelRepository: ModelRepository,
    private val cartRepository: CartRepository,
    private val cacheManager: ModelCacheManager,
    private val connectivityMonitor: ConnectivityMonitor
) : ViewModel() {

    companion object {
        /** Maximum number of models that can be placed simultaneously. */
        const val MAX_PLACED_MODELS = 5
    }

    // ─── Internal mutable state ─────────────────────────────────────────────────

    private val _products = MutableStateFlow<List<ArProduct>>(emptyList())
    private val _selectedProduct = MutableStateFlow<ArProduct?>(null)
    private val _placedModels = MutableStateFlow<List<PlacedModel>>(emptyList())
    private val _isLoading = MutableStateFlow(false)
    private val _error = MutableStateFlow<ArError?>(null)
    private val _isOffline = MutableStateFlow(false)
    private val _repositioningModel = MutableStateFlow<PlacedModel?>(null)
    private val _selectedInfoModel = MutableStateFlow<PlacedModel?>(null)
    private val _cartActionState = MutableStateFlow<CartActionState>(CartActionState.Idle)

    private var productLoadJob: Job? = null
    private var productLoadGeneration = 0L

    // ─── Public StateFlows ──────────────────────────────────────────────────────

    /** List of available AR products. */
    val products: StateFlow<List<ArProduct>> = _products.asStateFlow()

    /** Currently selected product for placement. */
    val selectedProduct: StateFlow<ArProduct?> = _selectedProduct.asStateFlow()

    /** List of models currently placed in the AR scene. */
    val placedModels: StateFlow<List<PlacedModel>> = _placedModels.asStateFlow()

    /** Whether a loading operation is in progress. */
    val isLoading: StateFlow<Boolean> = _isLoading.asStateFlow()

    /** Current error state, null if no error. */
    val error: StateFlow<ArError?> = _error.asStateFlow()

    /** Whether the device is currently offline. */
    val isOffline: StateFlow<Boolean> = _isOffline.asStateFlow()

    /** The model currently being repositioned, null if not repositioning. */
    val repositioningModel: StateFlow<PlacedModel?> = _repositioningModel.asStateFlow()

    /** The placed model whose info panel is shown, null if none. */
    val selectedInfoModel: StateFlow<PlacedModel?> = _selectedInfoModel.asStateFlow()

    /** Current add-to-cart progress and result for the AR product sheet. */
    val cartActionState: StateFlow<CartActionState> = _cartActionState.asStateFlow()

    /**
     * Whether a new model can be placed.
     * False when the placement limit (5) is reached.
     */
    val canPlace: StateFlow<Boolean> = _placedModels
        .map { it.size < MAX_PLACED_MODELS }
        .stateIn(viewModelScope, SharingStarted.Eagerly, true)

    init {
        // Observe connectivity changes
        viewModelScope.launch {
            connectivityMonitor.connectivityFlow.collect { connected ->
                val wasOffline = _isOffline.value
                _isOffline.value = !connected

                when {
                    !connected -> startProductLoad(
                        onlineRequested = false,
                        showLoading = false,
                    )
                    wasOffline -> loadProducts()
                }
            }
        }
    }

    // ─── Actions ────────────────────────────────────────────────────────────────

    /**
     * Loads the list of AR-enabled products from the backend.
     * When offline, shows only products with cached models.
     */
    fun loadProducts() {
        val online = connectivityMonitor.isConnected
        startProductLoad(
            onlineRequested = online,
            showLoading = online,
        )
    }

    /**
     * Starts a single authoritative catalog load. Connectivity changes cancel the
     * previous request and advance [productLoadGeneration], preventing a delayed
     * online response from restoring products that are unavailable offline.
     */
    private fun startProductLoad(
        onlineRequested: Boolean,
        showLoading: Boolean,
    ) {
        productLoadJob?.cancel()
        val generation = ++productLoadGeneration
        if (!showLoading) {
            _isLoading.value = false
        }
        if (!onlineRequested) {
            _isOffline.value = true
        }

        productLoadJob = viewModelScope.launch {
            _isLoading.value = showLoading
            _error.value = null

            try {
                val nextProducts = if (onlineRequested) {
                    fetchProductsOnline()
                } else {
                    fetchProductsOffline()
                }

                if (generation != productLoadGeneration) return@launch

                // A network callback can arrive just after an HTTP response. Do
                // one final synchronous check before publishing the online list.
                if (onlineRequested && !connectivityMonitor.isConnected) {
                    _isLoading.value = false
                    _isOffline.value = true
                    val offlineProducts = fetchProductsOffline()
                    if (generation == productLoadGeneration) {
                        applyProductCatalog(offlineProducts)
                    }
                } else {
                    _isOffline.value = !onlineRequested
                    applyProductCatalog(nextProducts)
                }
            } catch (e: CancellationException) {
                throw e
            } catch (e: AuthenticationException) {
                if (generation == productLoadGeneration) {
                    _error.value = ArError.AuthError(e.message ?: "Authentication failed")
                }
            } catch (e: Exception) {
                if (generation == productLoadGeneration) {
                    _error.value = ArError.NetworkError(
                        e.message ?: "Failed to load products"
                    )
                }
            } finally {
                if (generation == productLoadGeneration) {
                    _isLoading.value = false
                }
            }
        }
    }

    /**
     * Selects a product for placement in the AR scene.
     */
    fun selectProduct(product: ArProduct) {
        _selectedProduct.value = product
        _error.value = null
    }

    /**
     * Places a model in the AR scene at the given anchor position.
     * Enforces the placement limit of [MAX_PLACED_MODELS].
     *
     * @param anchorNode The ARCore anchor node for the placement position
     * @param product The product being placed
     */
    fun placeModel(anchorNode: AnchorNode, product: ArProduct): Boolean {
        val currentModels = _placedModels.value

        if (currentModels.size >= MAX_PLACED_MODELS) {
            _error.value = ArError.PlacementLimitError()
            return false
        }

        // Find the ModelNode child of the anchorNode
        val modelNode = anchorNode.childNodes.filterIsInstance<io.github.sceneview.node.ModelNode>()
            .firstOrNull()

        if (modelNode == null) {
            _error.value = ArError.ModelLoadError("Model node not found on anchor")
            return false
        }

        val placedModel = PlacedModel(
            id = UUID.randomUUID().toString(),
            product = product,
            anchorNode = anchorNode,
            modelNode = modelNode
        )

        _placedModels.value = currentModels + placedModel
        return true
    }

    /**
     * Drops every reference to nodes owned by the current SceneView.
     *
     * The caller must remove nodes and detach anchors before invoking this during a
     * normal reset. Keeping renderer-bound nodes in a retained ViewModel would make
     * them unsafe to reuse after an Activity or SceneView recreation.
     */
    fun clearSceneState() {
        _placedModels.value = emptyList()
        _repositioningModel.value = null
        _selectedInfoModel.value = null
    }

    /**
     * Deletes a placed model from the AR scene, freeing a placement slot.
     *
     * @param placed The model to remove
     */
    fun deleteModel(placed: PlacedModel) {
        _placedModels.value = _placedModels.value.filter { it.id != placed.id }

        // Dismiss info panel if showing this model
        if (_selectedInfoModel.value?.id == placed.id) {
            _selectedInfoModel.value = null
        }

        // Cancel repositioning if this model is being repositioned
        if (_repositioningModel.value?.id == placed.id) {
            _repositioningModel.value = null
        }
    }

    /**
     * Enters repositioning mode for the given placed model.
     * Stores the original anchor for snap-back on cancel.
     *
     * @param placed The model to start repositioning
     */
    fun startRepositioning(placed: PlacedModel) {
        // Store original anchor for snap-back
        val modelWithOriginal = placed.copy(originalAnchor = placed.anchorNode)

        // Update the placed models list with the stored original
        _placedModels.value = _placedModels.value.map {
            if (it.id == placed.id) modelWithOriginal else it
        }

        _repositioningModel.value = modelWithOriginal

        // Dismiss info panel while repositioning
        _selectedInfoModel.value = null
    }

    /**
     * Completes repositioning by attaching the model to a new anchor.
     * Detaches the old anchor.
     *
     * @param newAnchor The new anchor node at the final position
     */
    fun finishRepositioning(newAnchor: AnchorNode) {
        val repositioning = _repositioningModel.value ?: return

        // Update the placed model with the new anchor and clear originalAnchor
        val updatedModel = repositioning.copy(
            anchorNode = newAnchor,
            modelNode = newAnchor.childNodes.filterIsInstance<io.github.sceneview.node.ModelNode>()
                .firstOrNull() ?: repositioning.modelNode,
            originalAnchor = null
        )

        _placedModels.value = _placedModels.value.map {
            if (it.id == repositioning.id) updatedModel else it
        }

        _repositioningModel.value = null
    }

    /**
     * Updates the repositioning model's anchor during real-time drag.
     * This keeps the ViewModel's placed models list in sync as the model moves.
     *
     * @param updatedModel The model with updated anchor position
     */
    fun updateRepositioningAnchor(updatedModel: PlacedModel) {
        _repositioningModel.value = updatedModel
        _placedModels.value = _placedModels.value.map {
            if (it.id == updatedModel.id) updatedModel else it
        }
    }

    /**
     * Cancels repositioning and snaps the model back to its original position.
     */
    fun cancelRepositioning() {
        val repositioning = _repositioningModel.value ?: return

        // Restore to original anchor
        val originalAnchor = repositioning.originalAnchor
        if (originalAnchor != null) {
            val restoredModel = repositioning.copy(
                anchorNode = originalAnchor,
                originalAnchor = null
            )

            _placedModels.value = _placedModels.value.map {
                if (it.id == repositioning.id) restoredModel else it
            }
        }

        _repositioningModel.value = null
    }

    /**
     * Adds a product to the user's cart via the backend API.
     *
     * @param product The product to add to cart
     */
    fun addToCart(product: ArProduct) {
        if (_cartActionState.value is CartActionState.Adding) return

        _cartActionState.value = CartActionState.Adding(product.id)
        viewModelScope.launch {
            try {
                val result = cartRepository.addToCart(productId = product.id, quantity = 1)
                if (!result.success) {
                    _cartActionState.value = CartActionState.Failed(product.id, result.message)
                } else {
                    _cartActionState.value = CartActionState.Added(product.id)
                }
            } catch (e: AuthenticationException) {
                _cartActionState.value = CartActionState.Failed(
                    product.id,
                    e.message ?: "Please sign in again to add this item."
                )
            } catch (e: CartOperationException) {
                _cartActionState.value = CartActionState.Failed(
                    product.id,
                    e.message ?: "Could not add this item to your cart."
                )
            } catch (e: Exception) {
                _cartActionState.value = CartActionState.Failed(
                    product.id,
                    e.message ?: "Could not add this item to your cart."
                )
            }
        }
    }

    /** Returns the product sheet to its neutral state after showing a result. */
    fun clearCartAction() {
        if (_cartActionState.value !is CartActionState.Adding) {
            _cartActionState.value = CartActionState.Idle
        }
    }

    /**
     * Shows the product info panel for a placed model.
     *
     * @param placed The placed model to show info for
     */
    fun showProductInfo(placed: PlacedModel) {
        clearCartAction()
        _selectedInfoModel.value = placed
    }

    /**
     * Dismisses the product info panel.
     */
    fun dismissProductInfo() {
        _selectedInfoModel.value = null
    }

    /**
     * Clears the current error state.
     */
    fun clearError() {
        _error.value = null
    }

    /**
     * Sets a model load error that will be displayed as a banner.
     *
     * @param message The error message to display
     */
    fun setModelLoadError(message: String) {
        _error.value = ArError.ModelLoadError(message)
    }

    // ─── Private helpers ────────────────────────────────────────────────────────

    /**
     * Loads products from the API when online.
     */
    private suspend fun fetchProductsOnline(): List<ArProduct> {
        val response = apiService.getArProducts()

        if (response.isSuccessful) {
            val dtos = response.body() ?: emptyList()
            try {
                cacheManager.cacheCatalog(dtos)
            } catch (_: Exception) {
                // Catalog persistence is best-effort; an online session should
                // remain usable even if local storage is temporarily unavailable.
            }
            return dtos.map { it.toArProduct() }
        } else {
            val code = response.code()
            if (code == 401) {
                throw AuthenticationException("Authentication failed")
            }
            throw Exception("Failed to load products (HTTP $code)")
        }
    }

    /**
     * Loads only cached products when offline.
     * Shows only products whose models are available in the local cache.
     */
    private suspend fun fetchProductsOffline(): List<ArProduct> {
        val cachedIds = cacheManager.getCachedProductIds()
        return cacheManager.getCachedCatalog()
            .asSequence()
            .filter { it.id in cachedIds }
            .map { it.toArProduct() }
            .toList()
    }

    /** Publishes a catalog and keeps selection tied to an available item. */
    private fun applyProductCatalog(products: List<ArProduct>) {
        _products.value = products
        _selectedProduct.value = _selectedProduct.value?.let { selected ->
            products.firstOrNull { it.id == selected.id }
        }
    }
}

/**
 * Extension function to convert API DTO to domain model.
 */
fun ArProductDto.toArProduct(): ArProduct = ArProduct(
    id = id,
    name = name,
    price = price,
    thumbnailUrl = thumbnailUrl ?: "",
    modelUrl = modelUrl,
    heightCm = heightCm,
    category = category ?: "",
    description = description ?: "",
    inStock = inStock,
)
