package com.example.ferosa_landscaping.data.repository

import com.example.ferosa_landscaping.data.api.ApiService
import com.example.ferosa_landscaping.data.api.models.AddToCartRequest
import kotlinx.coroutines.delay
import java.io.IOException

/**
 * Wraps the add-to-cart API call with error handling and retry logic.
 *
 * Retry strategy:
 * - Max 3 attempts with exponential backoff (1s, 2s, 4s)
 * - Network errors and 5xx responses are retryable
 * - 4xx errors (except 401) are NOT retried (client error)
 * - 401 triggers an authentication error
 */
class CartRepository(
    private val apiService: ApiService
) {

    companion object {
        private const val MAX_RETRY_ATTEMPTS = 3
        private const val INITIAL_BACKOFF_MS = 1000L
    }

    /**
     * Result of an add-to-cart operation.
     *
     * @property success Whether the item was added successfully
     * @property message A message from the server (success confirmation or error detail)
     * @property cartCount The updated cart item count, if available
     */
    data class CartResult(
        val success: Boolean,
        val message: String,
        val cartCount: Int? = null
    )

    /**
     * Adds a product to the user's cart.
     *
     * @param productId The product to add
     * @param quantity The quantity to add (defaults to 1)
     * @return [CartResult] indicating success or failure with a message
     * @throws CartOperationException if the operation fails after all retries
     * @throws AuthenticationException if authentication fails (401 response)
     */
    suspend fun addToCart(productId: Int, quantity: Int = 1): CartResult {
        val request = AddToCartRequest(productId = productId, quantity = quantity)
        var lastException: Exception? = null

        for (attempt in 0 until MAX_RETRY_ATTEMPTS) {
            try {
                val response = apiService.addToCart(request)

                if (response.isSuccessful) {
                    val body = response.body()
                    return CartResult(
                        success = body?.success ?: true,
                        message = body?.message ?: "Item added to cart",
                        cartCount = body?.cartCount
                    )
                }

                val code = response.code()

                // 401 — authentication issue, don't retry
                if (code == 401) {
                    throw AuthenticationException("Authentication failed when adding to cart")
                }

                // Non-retryable client errors (4xx)
                if (code in 400..499) {
                    val errorMessage = parseErrorMessage(response.errorBody()?.string())
                    return CartResult(
                        success = false,
                        message = errorMessage
                    )
                }

                // 5xx — retryable server error
                lastException = CartOperationException("Server error $code when adding to cart")
            } catch (e: IOException) {
                // Network error — retryable
                lastException = e
            } catch (e: AuthenticationException) {
                throw e
            }

            // Exponential backoff: 1s, 2s, 4s
            if (attempt < MAX_RETRY_ATTEMPTS - 1) {
                val backoffMs = INITIAL_BACKOFF_MS * (1L shl attempt)
                delay(backoffMs)
            }
        }

        throw CartOperationException(
            "Failed to add item to cart after $MAX_RETRY_ATTEMPTS attempts",
            lastException
        )
    }

    /**
     * Parses the error message from the server response body.
     * Falls back to a generic message if parsing fails.
     */
    private fun parseErrorMessage(errorBody: String?): String {
        if (errorBody.isNullOrBlank()) {
            return "Failed to add item to cart"
        }
        // Try to extract "error" or "message" field from JSON
        return try {
            val regex = """"(?:error|message)"\s*:\s*"([^"]+)"""".toRegex()
            regex.find(errorBody)?.groupValues?.get(1) ?: "Failed to add item to cart"
        } catch (e: Exception) {
            "Failed to add item to cart"
        }
    }
}

/**
 * Exception thrown when a cart operation fails after exhausting retries.
 */
class CartOperationException(
    message: String,
    cause: Throwable? = null
) : Exception(message, cause)
