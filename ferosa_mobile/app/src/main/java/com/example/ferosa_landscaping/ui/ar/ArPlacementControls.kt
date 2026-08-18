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

/**
 * Temporal state used to keep an intermittent ARCore raycast from making the preview blink.
 *
 * ARCore can briefly return no hit while a detected plane is being updated. A target is only
 * considered confirmed after consecutive valid samples, and a confirmed target is retained for a
 * short grace period when one probe misses. The caller still re-tests the screen coordinate before
 * creating an anchor, so this state is presentation smoothing rather than placement authority.
 */
data class PlacementTargetStability(
    val consecutiveValidHits: Int = 0,
    val lastValidAtMillis: Long = Long.MIN_VALUE,
)

const val PLACEMENT_TARGET_CONFIRMATION_HITS = 2
const val PLACEMENT_TARGET_MISS_GRACE_MILLIS = 350L

fun updatePlacementTargetStability(
    state: PlacementTargetStability,
    hasValidHit: Boolean,
    nowMillis: Long,
    requiredValidHits: Int = PLACEMENT_TARGET_CONFIRMATION_HITS,
    missGraceMillis: Long = PLACEMENT_TARGET_MISS_GRACE_MILLIS,
): PlacementTargetStability {
    require(requiredValidHits > 0) { "requiredValidHits must be positive" }
    require(missGraceMillis >= 0L) { "missGraceMillis must not be negative" }

    if (hasValidHit) {
        return state.copy(
            consecutiveValidHits =
                (state.consecutiveValidHits + 1).coerceAtMost(requiredValidHits),
            lastValidAtMillis = nowMillis,
        )
    }

    val hasConfirmedTarget = state.consecutiveValidHits >= requiredValidHits
    val isWithinGrace = state.lastValidAtMillis != Long.MIN_VALUE &&
        nowMillis >= state.lastValidAtMillis &&
        nowMillis - state.lastValidAtMillis <= missGraceMillis
    return if (hasConfirmedTarget && isWithinGrace) {
        state
    } else {
        PlacementTargetStability()
    }
}

fun isPlacementTargetStable(
    state: PlacementTargetStability,
    nowMillis: Long,
    requiredValidHits: Int = PLACEMENT_TARGET_CONFIRMATION_HITS,
    missGraceMillis: Long = PLACEMENT_TARGET_MISS_GRACE_MILLIS,
): Boolean {
    require(requiredValidHits > 0) { "requiredValidHits must be positive" }
    require(missGraceMillis >= 0L) { "missGraceMillis must not be negative" }

    return state.consecutiveValidHits >= requiredValidHits &&
        state.lastValidAtMillis != Long.MIN_VALUE &&
        nowMillis >= state.lastValidAtMillis &&
        nowMillis - state.lastValidAtMillis <= missGraceMillis
}

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
