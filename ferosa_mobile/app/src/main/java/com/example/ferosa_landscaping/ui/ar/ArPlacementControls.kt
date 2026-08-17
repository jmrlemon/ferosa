package com.example.ferosa_landscaping.ui.ar

/**
 * Renderer-independent state used to decide whether the current preview can be committed.
 *
 * Keeping this rule separate from Compose and SceneView makes placement eligibility deterministic
 * and prevents individual callbacks from drifting apart as controls are added.
 */
data class PlacementControlState(
    val hasSelectedProduct: Boolean,
    val isPreviewReady: Boolean,
    val hasFreshTarget: Boolean,
    val isBusy: Boolean,
    val isMoving: Boolean,
    val placedCount: Int,
    val maxPlacedModels: Int,
)

fun canConfirmPlacement(state: PlacementControlState): Boolean =
    state.hasSelectedProduct &&
        state.isPreviewReady &&
        state.hasFreshTarget &&
        !state.isBusy &&
        !state.isMoving &&
        state.placedCount >= 0 &&
        state.maxPlacedModels > 0 &&
        state.placedCount < state.maxPlacedModels

/** Returns the exact centre point used by the rendered AR crosshair for a valid view size. */
fun crosshairCoordinates(width: Int, height: Int): Pair<Float, Float> {
    require(width > 0 && height > 0) { "AR view dimensions must be positive" }
    return width / 2f to height / 2f
}

/** Returns the next upright half-turn while keeping yaw in the canonical [0, 360) range. */
fun turn180Degrees(currentYawDegrees: Float): Float {
    require(currentYawDegrees.isFinite()) { "Yaw must be finite" }

    val normalizedYaw = (currentYawDegrees % 360f + 360f) % 360f
    return (normalizedYaw + 180f) % 360f
}
