package com.example.ferosa_landscaping

import androidx.test.platform.app.InstrumentationRegistry
import com.example.ferosa_landscaping.data.api.models.ArProductDto
import com.example.ferosa_landscaping.data.cache.ModelCacheManager
import kotlinx.coroutines.runBlocking
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNotNull
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test
import java.io.ByteArrayInputStream
import java.io.File
import java.io.IOException
import java.io.InputStream

/**
 * The AR model cache is what stands between a customer and a download on every
 * single placement, and it is the only reason AR works at all once the network
 * drops. It is also the piece with the most room to go quietly wrong - LRU
 * eviction, a 100 MB per-model ceiling, and a temporary file that is promoted
 * only after a download completes.
 *
 * These run instrumented rather than on the JVM because the manager writes to a
 * real `context.filesDir`; a real filesystem is the point, not an obstacle.
 */
class ModelCacheManagerTest {

    private val context = InstrumentationRegistry.getInstrumentation().targetContext
    private lateinit var cache: ModelCacheManager

    private val cacheDir: File
        get() = File(context.filesDir, "ar_model_cache")

    @Before
    fun setUp() = runBlocking {
        cache = ModelCacheManager(context)
        cache.clearCache()
    }

    @After
    fun tearDown() = runBlocking {
        cache.clearCache()
    }

    private fun stream(bytes: ByteArray): InputStream = ByteArrayInputStream(bytes)

    private fun glb(size: Int, fill: Byte = 1): ByteArray = ByteArray(size) { fill }

    // ─── Round trip ─────────────────────────────────────────────────────────

    @Test
    fun a_downloaded_model_can_be_read_back() = runBlocking {
        val bytes = glb(2048)

        val written = cache.cacheModel(productId = 7, input = stream(bytes), expectedSize = 2048)

        assertTrue(written.exists())
        assertArrayEqualsMessage(bytes, written.readBytes())
        assertEquals(written.absolutePath, cache.getCachedModel(7)?.absolutePath)
        assertTrue(cache.isFresh(7))
        assertEquals(setOf(7), cache.getCachedProductIds())
        assertEquals(2048L, cache.getTotalCacheSize())
    }

    @Test
    fun a_product_with_no_model_reports_nothing_cached() = runBlocking {
        assertNull(cache.getCachedModel(404))
        assertNull(cache.getCachedModelStale(404))
        assertFalse(cache.isFresh(404))
        assertEquals(emptySet<Int>(), cache.getCachedProductIds())
    }

    @Test
    fun re_downloading_a_product_replaces_it_rather_than_accumulating() = runBlocking {
        cache.cacheModel(productId = 3, input = stream(glb(1024, fill = 1)))
        cache.cacheModel(productId = 3, input = stream(glb(4096, fill = 2)))

        val file = requireNotNull(cache.getCachedModel(3))
        assertEquals(4096, file.readBytes().size)
        assertEquals(2.toByte(), file.readBytes().first())
        // One entry, one file - a replaced model must not leave its predecessor
        // behind eating the cache budget.
        assertEquals(setOf(3), cache.getCachedProductIds())
        assertEquals(4096L, cache.getTotalCacheSize())
    }

    // ─── Rejections ─────────────────────────────────────────────────────────

    @Test
    fun a_model_declared_larger_than_the_ceiling_is_refused_before_downloading() = runBlocking {
        val tooBig = ModelCacheManager.MAX_SINGLE_MODEL_BYTES + 1

        val thrown = runCatching {
            cache.cacheModel(productId = 9, input = stream(glb(16)), expectedSize = tooBig)
        }.exceptionOrNull()

        assertTrue("expected rejection, got $thrown", thrown is IllegalArgumentException)
        assertNull("nothing may be cached for a refused model", cache.getCachedModel(9))
        assertNoTemporaryFilesLeftBehind()
    }

    @Test
    fun an_empty_download_is_refused() = runBlocking {
        val thrown = runCatching {
            cache.cacheModel(productId = 11, input = stream(ByteArray(0)))
        }.exceptionOrNull()

        assertTrue("expected rejection, got $thrown", thrown is IllegalArgumentException)
        assertNull(cache.getCachedModel(11))
        assertNoTemporaryFilesLeftBehind()
    }

    /**
     * The declared size is a claim by the server. A body that keeps streaming past
     * the ceiling has to be stopped mid-flight, or a hostile or broken response
     * fills the phone's storage.
     */
    @Test
    fun a_download_that_exceeds_the_ceiling_mid_stream_is_aborted() = runBlocking {
        val overLimit = (ModelCacheManager.MAX_SINGLE_MODEL_BYTES + 1024L).toInt()

        val thrown = runCatching {
            cache.cacheModel(
                productId = 13,
                input = endlessStream(),
                expectedSize = null, // server claimed nothing
            )
        }.exceptionOrNull()

        assertTrue("expected rejection, got $thrown", thrown is IllegalArgumentException)
        assertNull(cache.getCachedModel(13))
        assertNoTemporaryFilesLeftBehind()
        assertTrue("cache must not retain the aborted body", cache.getTotalCacheSize() < overLimit)
    }

    // ─── Failure must not destroy what already worked ───────────────────────

    /**
     * This is the one that matters during a demo: a customer re-opens a product
     * whose model is already cached, the network dies halfway through the
     * refresh, and the working copy must still be there afterwards.
     */
    @Test
    fun a_failed_re_download_leaves_the_previous_model_usable() = runBlocking {
        val good = glb(3072, fill = 7)
        cache.cacheModel(productId = 21, input = stream(good))

        val thrown = runCatching {
            cache.cacheModel(productId = 21, input = failingStream(afterBytes = 512))
        }.exceptionOrNull()

        assertNotNull("the failed download should have thrown", thrown)

        val survivor = cache.getCachedModel(21)
        assertNotNull("the previously cached model was lost by a failed refresh", survivor)
        assertArrayEqualsMessage(good, requireNotNull(survivor).readBytes())
        assertNoTemporaryFilesLeftBehind()
    }

    @Test
    fun a_failed_download_for_a_new_product_leaves_no_trace() = runBlocking {
        val thrown = runCatching {
            cache.cacheModel(productId = 22, input = failingStream(afterBytes = 256))
        }.exceptionOrNull()

        assertNotNull(thrown)
        assertNull(cache.getCachedModel(22))
        assertFalse(22 in cache.getCachedProductIds())
        assertNoTemporaryFilesLeftBehind()
    }

    // ─── LRU eviction ───────────────────────────────────────────────────────

    /**
     * Eviction is driven by lastAccessedAt, not by insertion order, so reading a
     * model has to protect it from being the next one dropped.
     */
    @Test
    fun the_least_recently_used_model_is_evicted_first() = runBlocking {
        // Two halves fill the budget exactly, so the third arrival is the first
        // one that cannot be satisfied without evicting something. Insertion
        // order and access order are made to disagree: product 1 goes in first
        // but is touched last, so an implementation that evicted by age would
        // drop 1 and an LRU one drops 2.
        val half = (ModelCacheManager.MAX_CACHE_SIZE_BYTES / 2).toInt()

        cache.cacheModel(productId = 1, input = stream(glb(half)))
        Thread.sleep(5)
        cache.cacheModel(productId = 2, input = stream(glb(half)))
        Thread.sleep(5)

        assertNotNull(cache.getCachedModel(1))
        Thread.sleep(5)

        cache.cacheModel(productId = 3, input = stream(glb(1024)))

        val remaining = cache.getCachedProductIds()
        assertFalse("product 2 was the least recently used and should be gone", 2 in remaining)
        assertTrue("the touched model must survive eviction", 1 in remaining)
        assertTrue(3 in remaining)
        assertTrue(
            "cache grew past its budget: ${cache.getTotalCacheSize()}",
            cache.getTotalCacheSize() <= ModelCacheManager.MAX_CACHE_SIZE_BYTES,
        )
    }

    /**
     * The companion to the test above: when everything still fits, nothing may be
     * thrown away. Evicting eagerly would mean re-downloading models that were
     * costing nothing to keep.
     */
    @Test
    fun nothing_is_evicted_while_the_budget_still_has_room() = runBlocking {
        val quarter = (ModelCacheManager.MAX_CACHE_SIZE_BYTES / 4).toInt()

        (1..4).forEach { id ->
            cache.cacheModel(productId = id, input = stream(glb(quarter)))
        }

        assertEquals(setOf(1, 2, 3, 4), cache.getCachedProductIds())
        assertEquals(ModelCacheManager.MAX_CACHE_SIZE_BYTES, cache.getTotalCacheSize())
    }

    @Test
    fun evicting_a_model_deletes_its_file_not_just_its_record() = runBlocking {
        val chunk = (ModelCacheManager.MAX_CACHE_SIZE_BYTES / 2).toInt()
        cache.cacheModel(productId = 31, input = stream(glb(chunk)))
        val evictedPath = requireNotNull(cache.getCachedModel(31)).absolutePath

        // Two more halves cannot coexist with the first.
        cache.cacheModel(productId = 32, input = stream(glb(chunk)))
        cache.cacheModel(productId = 33, input = stream(glb(chunk)))

        assertFalse(31 in cache.getCachedProductIds())
        assertFalse("evicted file left on disk: $evictedPath", File(evictedPath).exists())
    }

    // ─── Metadata durability ────────────────────────────────────────────────

    /**
     * A half-written metadata file must not brick AR permanently. Starting from
     * an empty cache costs one download; refusing to parse costs the feature.
     */
    @Test
    fun corrupt_metadata_is_recovered_from_rather_than_thrown() = runBlocking {
        cache.cacheModel(productId = 41, input = stream(glb(1024)))

        File(cacheDir, "cache_metadata.json").writeText("{ this is not json")

        assertNull(cache.getCachedModel(41))
        assertEquals(emptySet<Int>(), cache.getCachedProductIds())
        // And the cache is still usable afterwards.
        val recovered = cache.cacheModel(productId = 41, input = stream(glb(1024)))
        assertTrue(recovered.exists())
        assertEquals(setOf(41), cache.getCachedProductIds())
    }

    @Test
    fun a_model_file_deleted_behind_the_managers_back_is_forgotten() = runBlocking {
        cache.cacheModel(productId = 51, input = stream(glb(1024)))
        requireNotNull(cache.getCachedModel(51)).delete()

        assertNull(cache.getCachedModel(51))
        assertNull(cache.getCachedModelStale(51))
        assertFalse(51 in cache.getCachedProductIds())
    }

    // ─── Offline catalog ────────────────────────────────────────────────────

    @Test
    fun the_catalog_survives_so_cached_models_stay_discoverable_offline() = runBlocking {
        val products = listOf(
            ArProductDto(
                id = 61,
                name = "Bougainvillea",
                description = "Climbing shrub",
                price = 450.0,
                thumbnailUrl = null,
                modelUrl = "/storage/ar-models/bougainvillea.glb",
                heightCm = 120f,
                category = "Plants",
                inStock = true,
            ),
        )

        cache.cacheCatalog(products)
        val restored = cache.getCachedCatalog()

        assertEquals(1, restored.size)
        assertEquals("Bougainvillea", restored.first().name)
        // The @SerializedName fields are the ones a ProGuard slip would break.
        assertEquals("/storage/ar-models/bougainvillea.glb", restored.first().modelUrl)
        assertEquals(120f, restored.first().heightCm, 0.001f)
        assertTrue(restored.first().inStock)
    }

    @Test
    fun a_corrupt_catalog_degrades_to_empty_instead_of_throwing() = runBlocking {
        cache.cacheCatalog(emptyList())
        File(cacheDir, "catalog_metadata.json").writeText("[[[")

        assertEquals(emptyList<ArProductDto>(), cache.getCachedCatalog())
    }

    @Test
    fun clearing_the_cache_removes_models_and_catalog_together() = runBlocking {
        cache.cacheModel(productId = 71, input = stream(glb(1024)))
        cache.cacheCatalog(
            listOf(
                ArProductDto(
                    id = 71, name = "Fern", description = null, price = 200.0,
                    thumbnailUrl = null, modelUrl = "/m.glb", heightCm = 40f,
                    category = null, inStock = true,
                )
            )
        )

        cache.clearCache()

        assertNull(cache.getCachedModel(71))
        assertEquals(emptySet<Int>(), cache.getCachedProductIds())
        assertEquals(emptyList<ArProductDto>(), cache.getCachedCatalog())
        assertEquals(0L, cache.getTotalCacheSize())
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    /** Fails partway through, standing in for a connection dropped mid-download. */
    private fun failingStream(afterBytes: Int): InputStream = object : InputStream() {
        private var served = 0
        override fun read(): Int = throw IOException("read(): not used by the cache")
        override fun read(b: ByteArray, off: Int, len: Int): Int {
            if (served >= afterBytes) throw IOException("connection dropped")
            val count = minOf(len, afterBytes - served)
            java.util.Arrays.fill(b, off, off + count, 1.toByte())
            served += count
            return count
        }
    }

    /** Never ends, standing in for a response with no honest length. */
    private fun endlessStream(): InputStream = object : InputStream() {
        override fun read(): Int = 1
        override fun read(b: ByteArray, off: Int, len: Int): Int {
            java.util.Arrays.fill(b, off, off + len, 1.toByte())
            return len
        }
    }

    private fun assertNoTemporaryFilesLeftBehind() {
        val leftovers = cacheDir.listFiles()?.filter { it.name.endsWith(".download") }.orEmpty()
        assertTrue("temporary download files left behind: $leftovers", leftovers.isEmpty())
    }

    private fun assertArrayEqualsMessage(expected: ByteArray, actual: ByteArray) {
        assertEquals("cached model size differs", expected.size, actual.size)
        assertTrue("cached model bytes differ", expected.contentEquals(actual))
    }
}
