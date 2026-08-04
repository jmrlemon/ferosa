package com.example.ferosa_landscaping

import com.example.ferosa_landscaping.ui.ar.calculateGroundedModelTransform
import com.example.ferosa_landscaping.ui.ar.validateGlbFile
import org.junit.Assert.assertEquals
import org.junit.Assert.assertThrows
import org.junit.Test
import java.nio.ByteBuffer
import java.nio.ByteOrder
import java.nio.file.Files

class ArModelPlacementTest {

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
}
