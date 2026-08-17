package com.example.ferosa_landscaping

import com.example.ferosa_landscaping.ui.ar.AR_EMPTY_CATALOG_TITLE
import com.example.ferosa_landscaping.ui.ar.ArProduct
import com.example.ferosa_landscaping.ui.ar.calculateGroundedModelTransform
import com.example.ferosa_landscaping.ui.ar.formatArSessionConfigLog
import com.example.ferosa_landscaping.ui.ar.isPreviewRequestCurrent
import com.example.ferosa_landscaping.ui.ar.resolveCachedModelLoaderInput
import com.example.ferosa_landscaping.ui.ar.shouldShowArEmptyState
import com.example.ferosa_landscaping.ui.ar.validateGlbFile
import org.junit.Assert.assertArrayEquals
import org.junit.Assert.assertEquals
import org.junit.Assert.assertThrows
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test
import java.nio.ByteBuffer
import java.nio.ByteOrder
import java.nio.file.Files

class ArModelPlacementTest {

    @Test
    fun empty_catalog_title_explains_that_ar_products_are_not_ready_yet() {
        assertEquals("No AR products available yet", AR_EMPTY_CATALOG_TITLE)
    }

    @Test
    fun empty_catalog_state_only_shows_after_loading_finishes_without_products() {
        assertTrue(shouldShowArEmptyState(emptyList(), isLoading = false))
        assertFalse(shouldShowArEmptyState(emptyList(), isLoading = true))
        assertFalse(shouldShowArEmptyState(listOf(sampleArProduct()), isLoading = false))
    }

    @Test
    fun cache_loader_input_is_a_byte_buffer_not_a_bare_path() {
        val file = Files.createTempFile("ferosa-model-buffer", ".glb").toFile()
        val expected = byteArrayOf(0x67, 0x6c, 0x54, 0x46, 0x02, 0x00)
        try {
            file.writeBytes(expected)

            val buffer = resolveCachedModelLoaderInput(file)
            val actual = ByteArray(buffer.remaining()).also(buffer::get)

            assertArrayEquals(expected, actual)
        } finally {
            file.delete()
        }
    }

    @Test
    fun preview_request_is_current_only_for_the_active_generation_and_product() {
        assertTrue(isPreviewRequestCurrent(3L, 3L, 11, 11))
        assertFalse(isPreviewRequestCurrent(2L, 3L, 11, 11))
        assertFalse(isPreviewRequestCurrent(3L, 3L, 11, 12))
        assertFalse(isPreviewRequestCurrent(3L, 3L, 11, null))
    }

    @Test
    fun session_config_log_includes_the_live_requested_modes() {
        assertEquals(
            "Session config applied planeFindingMode=HORIZONTAL " +
                "lightEstimationMode=ENVIRONMENTAL_HDR depthMode=AUTOMATIC depthOcclusion=true",
            formatArSessionConfigLog(
                planeFindingMode = "HORIZONTAL",
                lightEstimationMode = "ENVIRONMENTAL_HDR",
                depthMode = "AUTOMATIC",
                depthOcclusionEnabled = true,
            ),
        )
    }

    @Test
    fun transform_uses_y_height_and_places_bottom_center_on_anchor() {
        val transform = calculateGroundedModelTransform(
            centerX = 10f,
            centerY = 2f,
            centerZ = -5f,
            halfExtentX = 2f,
            halfExtentY = 1f,
            halfExtentZ = 3f,
            desiredHeightMeters = 1.5f,
        )

        assertEquals(0.75f, transform.uniformScale, 0.0001f)
        assertEquals(-7.5f, transform.positionX, 0.0001f)
        assertEquals(-0.75f, transform.positionY, 0.0001f)
        assertEquals(3.75f, transform.positionZ, 0.0001f)

        val scaledBottom = (2f - 1f) * transform.uniformScale + transform.positionY
        val scaledTop = (2f + 1f) * transform.uniformScale + transform.positionY
        assertEquals(0f, scaledBottom, 0.0001f)
        assertEquals(1.5f, scaledTop, 0.0001f)
    }

    @Test
    fun transform_rejects_zero_height_model() {
        assertThrows(IllegalArgumentException::class.java) {
            calculateGroundedModelTransform(
                centerX = 0f,
                centerY = 0f,
                centerZ = 0f,
                halfExtentX = 1f,
                halfExtentY = 0f,
                halfExtentZ = 1f,
                desiredHeightMeters = 1f,
            )
        }
    }

    @Test
    fun glb_validation_accepts_matching_version_two_header() {
        val file = Files.createTempFile("ferosa-model", ".glb").toFile()
        try {
            val bytes = ByteBuffer.allocate(12)
                .order(ByteOrder.LITTLE_ENDIAN)
                .put(byteArrayOf('g'.code.toByte(), 'l'.code.toByte(), 'T'.code.toByte(), 'F'.code.toByte()))
                .putInt(2)
                .putInt(12)
                .array()
            file.writeBytes(bytes)

            validateGlbFile(file)
        } finally {
            file.delete()
        }
    }

    @Test
    fun glb_validation_rejects_truncated_download() {
        val file = Files.createTempFile("ferosa-model-truncated", ".glb").toFile()
        try {
            val bytes = ByteBuffer.allocate(12)
                .order(ByteOrder.LITTLE_ENDIAN)
                .put(byteArrayOf('g'.code.toByte(), 'l'.code.toByte(), 'T'.code.toByte(), 'F'.code.toByte()))
                .putInt(2)
                .putInt(28)
                .array()
            file.writeBytes(bytes)

            assertThrows(IllegalArgumentException::class.java) {
                validateGlbFile(file)
            }
        } finally {
            file.delete()
        }
    }

    private fun sampleArProduct() = ArProduct(
        id = 1,
        name = "Test plant",
        price = 10.0,
        thumbnailUrl = "",
        modelUrl = "https://example.test/model.glb",
        heightCm = 50f,
        category = "Plants",
        description = "",
        inStock = true,
    )
}
