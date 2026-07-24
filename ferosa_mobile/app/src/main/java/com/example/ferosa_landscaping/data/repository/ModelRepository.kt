package com.example.ferosa_landscaping.data.repository

import com.example.ferosa_landscaping.data.api.ApiService
import com.example.ferosa_landscaping.data.cache.ModelCacheManager
import com.example.ferosa_landscaping.util.ConnectivityMonitor
import kotlinx.coroutines.delay
import java.io.File
import java.io.IOException
import java.text.SimpleDateFormat
import java.util.Locale

/**
 * Coordinates fetching 3D plant model files from the backend API and caching them locally.
 *
 * Logic:
 * - When online, compare cached files with server metadata before reuse
 * - If metadata is temporarily unavailable, keep using a still-fresh cache
 * - If cache is stale and offline, return stale cached model with an outdated flag
 * - Retry logic with exponential backoff (1s, 2s, 4s) for network failures, max 3 attempts
 */
class ModelRepository(
    private val apiService: ApiService,
    private val cacheManager: ModelCacheManager,
    private val connectivityMonitor: ConnectivityMonitor
) {

    companion object {
        private const val MAX_RETRY_ATTEMPTS = 3
        private const val INITIAL_BACKOFF_MS = 1000L
    }

    /**
     * Result of a model load operation.
     *
     * @property file The local file path to the model
     * @property isOutdated True if the model was loaded from a stale cache while offline
     */
    data class ModelResult(
        val file: File,
        val isOutdated: Boolean = false
    )

    /**
     * Gets the local file for a product's 3D model, downloading from API if necessary.
     *
     * @param productId The product ID to fetch the model for
     * @return [ModelResult] containing the file path and freshness flag
     * @throws ModelDownloadException if the model cannot be obtained
     */
    suspend fun getModel(productId: Int): ModelResult {
        val cacheIsFresh = cacheManager.isFresh(productId)
        val cachedFile = cacheManager.getCachedModelStale(productId)

        // Offline: use whatever is available and tell the UI when it may be stale.
        if (!connectivityMonitor.isConnected) {
            if (cachedFile != null) {
                return ModelResult(file = cachedFile, isOutdated = !cacheIsFresh)
            }
            throw ModelDownloadException("No network available and no cached model found for product $productId")
        }

        // Even a recently downloaded model may have been replaced by staff. Compare
        // server metadata before reuse so customers do not see an old AR asset for days.
        if (cachedFile != null) {
            try {
                val response = apiService.getModelInfo(productId)
                if (response.isSuccessful) {
                    val info = response.body()
                    if (info != null && cachedModelMatchesServer(cachedFile, info.fileSize, info.updatedAt)) {
                        return ModelResult(file = cachedFile, isOutdated = false)
                    }
                } else if (response.code() == 401) {
                    throw AuthenticationException("Authentication failed when checking model $productId")
                } else if (response.code() >= 500 && cacheIsFresh) {
                    return ModelResult(file = cachedFile, isOutdated = false)
                }
            } catch (exception: IOException) {
                // A transient metadata request should not block a still-fresh cache.
                if (cacheIsFresh) {
                    return ModelResult(file = cachedFile, isOutdated = false)
                }
            }
        }

        // Missing or changed cache: download with retry logic.
        return downloadWithRetry(productId)
    }

    private fun cachedModelMatchesServer(
        cachedFile: File,
        serverFileSize: Long,
        serverUpdatedAt: String,
    ): Boolean {
        if (serverFileSize >= 0L && cachedFile.length() != serverFileSize) return false

        val serverUpdatedMillis = listOf(
            "yyyy-MM-dd'T'HH:mm:ss.SSSXXX",
            "yyyy-MM-dd'T'HH:mm:ssXXX",
        ).firstNotNullOfOrNull { pattern ->
            runCatching {
                SimpleDateFormat(pattern, Locale.US).apply { isLenient = false }
                    .parse(serverUpdatedAt)
                    ?.time
            }.getOrNull()
        } ?: return false

        return cachedFile.lastModified() >= serverUpdatedMillis
    }

    /**
     * Downloads the model from the API with exponential backoff retry logic.
     * Max 3 attempts with delays of 1s, 2s, 4s.
     */
    private suspend fun downloadWithRetry(productId: Int): ModelResult {
        var lastException: Exception? = null

        for (attempt in 0 until MAX_RETRY_ATTEMPTS) {
            try {
                val response = apiService.downloadModel(productId)

                if (response.isSuccessful) {
                    val body = response.body()
                        ?: throw ModelDownloadException("Empty response body for product $productId")

                    val contentLength = body.contentLength().takeIf { it >= 0L }
                    val cachedFile = body.use {
                        cacheManager.cacheModel(
                            productId = productId,
                            input = it.byteStream(),
                            expectedSize = contentLength,
                        )
                    }
                    return ModelResult(file = cachedFile, isOutdated = false)
                }

                // Non-retryable client errors (4xx except 401)
                val code = response.code()
                if (code in 400..499 && code != 401) {
                    throw ModelDownloadException(
                        "Server returned error $code for product $productId model download"
                    )
                }

                // 401 — authentication issue, don't retry
                if (code == 401) {
                    throw AuthenticationException("Authentication failed when downloading model for product $productId")
                }

                // 5xx — retryable
                lastException = ModelDownloadException("Server error $code for product $productId")
            } catch (e: IOException) {
                // Network errors are retryable
                lastException = e
            } catch (e: ModelDownloadException) {
                throw e
            } catch (e: AuthenticationException) {
                throw e
            }

            // Exponential backoff: 1s, 2s, 4s
            if (attempt < MAX_RETRY_ATTEMPTS - 1) {
                val backoffMs = INITIAL_BACKOFF_MS * (1L shl attempt)
                delay(backoffMs)
            }
        }

        // All retries exhausted — try stale cache as fallback
        val staleFile = cacheManager.getCachedModelStale(productId)
        if (staleFile != null) {
            return ModelResult(file = staleFile, isOutdated = true)
        }

        throw ModelDownloadException(
            "Failed to download model for product $productId after $MAX_RETRY_ATTEMPTS attempts",
            lastException
        )
    }
}

/**
 * Exception thrown when a model download operation fails.
 */
class ModelDownloadException(
    message: String,
    cause: Throwable? = null
) : Exception(message, cause)

/**
 * Exception thrown when authentication fails during a model operation.
 */
class AuthenticationException(
    message: String,
    cause: Throwable? = null
) : Exception(message, cause)
