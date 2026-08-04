package com.example.ferosa_landscaping.ui.ar

import java.io.File
import java.nio.ByteBuffer
import java.nio.ByteOrder

private const val GLB_HEADER_SIZE_BYTES = 12
private const val GLB_VERSION_2 = 2
private const val MIN_MODEL_HEIGHT_UNITS = 0.000001f

/**
 * A renderer-independent transform that scales an asset to its configured real-world height and
 * moves the asset's bottom-center onto an AR anchor.
 */
data class GroundedModelTransform(
    val uniformScale: Float,
    val positionX: Float,
    val positionY: Float,
    val positionZ: Float,
)

/**
 * Calculates a stable real-world transform from the model's glTF bounding box.
 *
 * SceneView 2.x `scaleToUnits` fits the model's largest axis into a cube. That makes a wide plant
 * shorter than its configured height. Scaling from the Y extent keeps the catalog's height_cm value
 * authoritative, while the position offsets compensate for arbitrary exported pivots.
 */
fun calculateGroundedModelTransform(
    centerX: Float,
    centerY: Float,
    centerZ: Float,
    halfExtentX: Float,
    halfExtentY: Float,
    halfExtentZ: Float,
    desiredHeightMeters: Float,
): GroundedModelTransform {
    val values = floatArrayOf(
        centerX,
        centerY,
        centerZ,
        halfExtentX,
        halfExtentY,
        halfExtentZ,
        desiredHeightMeters,
    )
    require(values.all(Float::isFinite)) {
        "The 3D model contains invalid dimensions."
    }
    require(desiredHeightMeters > 0f) {
        "The product's real-world height must be greater than zero."
    }
    require(halfExtentX >= 0f && halfExtentY >= 0f && halfExtentZ >= 0f) {
        "The 3D model contains invalid bounds."
    }

    val sourceHeight = halfExtentY * 2f
    require(sourceHeight > MIN_MODEL_HEIGHT_UNITS) {
        "The 3D model has no measurable height. Export it with Y as the up axis."
    }

    val scale = desiredHeightMeters / sourceHeight
    require(scale.isFinite() && scale > 0f) {
        "The 3D model could not be scaled to its real-world height."
    }

    val sourceBottomY = centerY - halfExtentY
    return GroundedModelTransform(
        uniformScale = scale,
        positionX = -centerX * scale,
        positionY = -sourceBottomY * scale,
        positionZ = -centerZ * scale,
    )
}

/**
 * Rejects incomplete or mislabeled cache files before they reach Filament's native GLB loader.
 */
fun validateGlbFile(file: File) {
    require(file.isFile && file.length() >= GLB_HEADER_SIZE_BYTES) {
        "The downloaded 3D model is empty or incomplete."
    }

    val header = ByteArray(GLB_HEADER_SIZE_BYTES)
    file.inputStream().buffered().use { input ->
        var offset = 0
        while (offset < header.size) {
            val count = input.read(header, offset, header.size - offset)
            if (count < 0) break
            offset += count
        }
        require(offset == header.size) {
            "The downloaded 3D model has an incomplete header."
        }
    }

    require(
        header[0] == 'g'.code.toByte() &&
            header[1] == 'l'.code.toByte() &&
            header[2] == 'T'.code.toByte() &&
            header[3] == 'F'.code.toByte()
    ) {
        "The downloaded file is not a valid GLB model."
    }

    val buffer = ByteBuffer.wrap(header).order(ByteOrder.LITTLE_ENDIAN)
    val version = buffer.getInt(4)
    val declaredLength = Integer.toUnsignedLong(buffer.getInt(8))

    require(version == GLB_VERSION_2) {
        "The 3D model must use the GLB 2.0 format."
    }
    require(declaredLength == file.length()) {
        "The downloaded GLB file is incomplete."
    }
}
