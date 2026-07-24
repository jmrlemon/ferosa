package com.example.ferosa_landscaping.data.cache

import android.content.Context
import com.example.ferosa_landscaping.data.api.models.ArProductDto
import com.google.gson.Gson
import com.google.gson.reflect.TypeToken
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock
import kotlinx.coroutines.withContext
import java.io.File
import java.io.InputStream
import java.util.concurrent.TimeUnit

/**
 * Manages local caching of 3D plant model files (GLB/glTF) with LRU eviction.
 *
 * Cache metadata is stored as a JSON file tracking productId, fileName, downloadedAt,
 * fileSize, and lastAccessedAt for each cached model entry.
 */
class ModelCacheManager(private val context: Context) {

    companion object {
        const val MAX_CACHE_SIZE_BYTES = 200L * 1024 * 1024 // 200 MB
        const val MAX_SINGLE_MODEL_BYTES = 100L * 1024 * 1024 // 100 MB
        const val CACHE_VALIDITY_DAYS = 7L
        private const val CACHE_DIR_NAME = "ar_model_cache"
        private const val METADATA_FILE_NAME = "cache_metadata.json"
        private const val CATALOG_FILE_NAME = "catalog_metadata.json"
    }

    data class CacheEntry(
        val productId: Int,
        val fileName: String,
        val downloadedAt: Long,
        val fileSize: Long,
        var lastAccessedAt: Long
    )

    private val gson = Gson()
    private val mutex = Mutex()

    private val cacheDir: File
        get() = File(context.filesDir, CACHE_DIR_NAME).also { it.mkdirs() }

    private val metadataFile: File
        get() = File(cacheDir, METADATA_FILE_NAME)

    private val catalogFile: File
        get() = File(cacheDir, CATALOG_FILE_NAME)

    /** Persists the latest AR catalog so cached models remain discoverable offline. */
    suspend fun cacheCatalog(products: List<ArProductDto>): Unit = mutex.withLock {
        withContext(Dispatchers.IO) {
            catalogFile.writeText(gson.toJson(products))
        }
    }

    /** Returns the last successfully loaded AR catalog, or an empty list. */
    suspend fun getCachedCatalog(): List<ArProductDto> = mutex.withLock {
        withContext(Dispatchers.IO) {
            if (!catalogFile.exists()) return@withContext emptyList()

            try {
                val type = object : TypeToken<List<ArProductDto>>() {}.type
                gson.fromJson<List<ArProductDto>>(catalogFile.readText(), type) ?: emptyList()
            } catch (_: Exception) {
                emptyList()
            }
        }
    }

    /**
     * Returns the cached model [File] if it exists and is fresh (< 7 days old).
     * Updates lastAccessedAt on access. Returns null if not cached or stale.
     */
    suspend fun getCachedModel(productId: Int): File? = mutex.withLock {
        withContext(Dispatchers.IO) {
            val entries = loadMetadata()
            val entry = entries[productId] ?: return@withContext null

            val file = File(cacheDir, entry.fileName)
            if (!file.exists()) {
                // File missing — remove stale metadata
                entries.remove(productId)
                saveMetadata(entries)
                return@withContext null
            }

            if (!isFreshInternal(entry)) {
                return@withContext null
            }

            // Update last accessed time
            entry.lastAccessedAt = System.currentTimeMillis()
            saveMetadata(entries)
            file
        }
    }

    /**
     * Caches a model file for the given [productId]. Evicts LRU entries if needed
     * to stay within the 200 MB cache limit. Returns the written [File].
     */
    suspend fun cacheModel(productId: Int, data: ByteArray): File = mutex.withLock {
        withContext(Dispatchers.IO) {
            val entries = loadMetadata()
            val requiredSpace = data.size.toLong()

            // Remove existing entry for this product if present
            val existingEntry = entries[productId]
            if (existingEntry != null) {
                File(cacheDir, existingEntry.fileName).delete()
                entries.remove(productId)
            }

            // Evict LRU entries until we have enough space
            evictLRUInternal(entries, requiredSpace)

            // Write the model file
            val fileName = "model_${productId}.glb"
            val modelFile = File(cacheDir, fileName)
            modelFile.writeBytes(data)

            // Record metadata
            val now = System.currentTimeMillis()
            entries[productId] = CacheEntry(
                productId = productId,
                fileName = fileName,
                downloadedAt = now,
                fileSize = data.size.toLong(),
                lastAccessedAt = now
            )
            saveMetadata(entries)

            modelFile
        }
    }

    /**
     * Streams a model into the cache without holding the entire GLB in memory.
     * The temporary file is promoted only after the stream completes.
     */
    suspend fun cacheModel(
        productId: Int,
        input: InputStream,
        expectedSize: Long? = null,
    ): File = mutex.withLock {
        withContext(Dispatchers.IO) {
            if (expectedSize != null && expectedSize > MAX_SINGLE_MODEL_BYTES) {
                throw IllegalArgumentException("AR model is larger than 100 MB")
            }

            val temporaryFile = File(cacheDir, "model_${productId}.download")
            var bytesWritten = 0L
            var replacingCacheEntry = false

            try {
                input.use { source ->
                    temporaryFile.outputStream().buffered().use { target ->
                        val buffer = ByteArray(DEFAULT_BUFFER_SIZE)
                        while (true) {
                            val count = source.read(buffer)
                            if (count < 0) break
                            bytesWritten += count
                            if (bytesWritten > MAX_SINGLE_MODEL_BYTES) {
                                throw IllegalArgumentException("AR model is larger than 100 MB")
                            }
                            target.write(buffer, 0, count)
                        }
                    }
                }

                if (bytesWritten == 0L) {
                    throw IllegalArgumentException("Downloaded AR model is empty")
                }

                val entries = loadMetadata()
                val existingEntry = entries.remove(productId)
                evictLRUInternal(entries, bytesWritten)

                val fileName = "model_${productId}.glb"
                val modelFile = File(cacheDir, fileName)
                replacingCacheEntry = true
                if (existingEntry != null) {
                    File(cacheDir, existingEntry.fileName).delete()
                }
                modelFile.delete()

                if (!temporaryFile.renameTo(modelFile)) {
                    temporaryFile.copyTo(modelFile, overwrite = true)
                    temporaryFile.delete()
                }

                val now = System.currentTimeMillis()
                entries[productId] = CacheEntry(
                    productId = productId,
                    fileName = fileName,
                    downloadedAt = now,
                    fileSize = bytesWritten,
                    lastAccessedAt = now,
                )
                saveMetadata(entries)
                modelFile
            } catch (exception: Exception) {
                temporaryFile.delete()
                if (replacingCacheEntry) {
                    File(cacheDir, "model_${productId}.glb").delete()
                    val entries = loadMetadata()
                    entries.remove(productId)
                    saveMetadata(entries)
                }
                throw exception
            }
        }
    }

    /**
     * Returns true if the cached model for [productId] is less than 7 days old.
     * Returns false if not cached or if the cache entry is stale.
     */
    suspend fun isFresh(productId: Int): Boolean = mutex.withLock {
        withContext(Dispatchers.IO) {
            val entries = loadMetadata()
            val entry = entries[productId] ?: return@withContext false
            isFreshInternal(entry)
        }
    }

    /**
     * Removes least recently accessed cache entries until [requiredSpace] bytes
     * can be written without exceeding the 200 MB limit.
     */
    suspend fun evictLRU(requiredSpace: Long): Unit = mutex.withLock {
        withContext(Dispatchers.IO) {
            val entries = loadMetadata()
            evictLRUInternal(entries, requiredSpace)
            saveMetadata(entries)
        }
    }

    /**
     * Returns the total size in bytes of all cached model files.
     */
    suspend fun getTotalCacheSize(): Long = mutex.withLock {
        withContext(Dispatchers.IO) {
            val entries = loadMetadata()
            entries.values.sumOf { it.fileSize }
        }
    }

    /**
     * Returns the cached model [File] regardless of freshness (even if stale).
     * Updates lastAccessedAt on access. Returns null if not cached at all.
     * Used for offline fallback when the device cannot reach the network.
     */
    suspend fun getCachedModelStale(productId: Int): File? = mutex.withLock {
        withContext(Dispatchers.IO) {
            val entries = loadMetadata()
            val entry = entries[productId] ?: return@withContext null

            val file = File(cacheDir, entry.fileName)
            if (!file.exists()) {
                // File missing — remove stale metadata
                entries.remove(productId)
                saveMetadata(entries)
                return@withContext null
            }

            // Update last accessed time
            entry.lastAccessedAt = System.currentTimeMillis()
            saveMetadata(entries)
            file
        }
    }

    /**
     * Returns the set of product IDs that currently have cached models.
     */
    suspend fun getCachedProductIds(): Set<Int> = mutex.withLock {
        withContext(Dispatchers.IO) {
            val entries = loadMetadata()
            entries.keys.toSet()
        }
    }

    /**
     * Clears all cached model files and metadata.
     */
    suspend fun clearCache(): Unit = mutex.withLock {
        withContext(Dispatchers.IO) {
            val entries = loadMetadata()
            for (entry in entries.values) {
                File(cacheDir, entry.fileName).delete()
            }
            entries.clear()
            saveMetadata(entries)
            catalogFile.delete()
        }
    }

    // --- Internal helpers ---

    private fun isFreshInternal(entry: CacheEntry): Boolean {
        val ageMillis = System.currentTimeMillis() - entry.downloadedAt
        val maxAgeMillis = TimeUnit.DAYS.toMillis(CACHE_VALIDITY_DAYS)
        return ageMillis < maxAgeMillis
    }

    private fun evictLRUInternal(
        entries: MutableMap<Int, CacheEntry>,
        requiredSpace: Long
    ) {
        val currentSize = entries.values.sumOf { it.fileSize }
        var availableSpace = MAX_CACHE_SIZE_BYTES - currentSize

        if (availableSpace >= requiredSpace) return

        // Sort by lastAccessedAt ascending (least recently used first)
        val sortedEntries = entries.values.sortedBy { it.lastAccessedAt }

        for (entry in sortedEntries) {
            if (availableSpace >= requiredSpace) break

            // Delete the file
            File(cacheDir, entry.fileName).delete()
            availableSpace += entry.fileSize
            entries.remove(entry.productId)
        }
    }

    private fun loadMetadata(): MutableMap<Int, CacheEntry> {
        if (!metadataFile.exists()) {
            return mutableMapOf()
        }
        return try {
            val json = metadataFile.readText()
            val type = object : TypeToken<MutableMap<Int, CacheEntry>>() {}.type
            gson.fromJson(json, type) ?: mutableMapOf()
        } catch (e: Exception) {
            // If metadata is corrupted, start fresh
            mutableMapOf()
        }
    }

    private fun saveMetadata(entries: MutableMap<Int, CacheEntry>) {
        val json = gson.toJson(entries)
        metadataFile.writeText(json)
    }
}
