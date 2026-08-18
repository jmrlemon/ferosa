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

/**
 * Screen-space bounds used as a forgiving tap target for a rendered AR model.
 *
 * A model's world origin is commonly its grounded base, not the visual centre. Using only that
 * origin for selection makes tall products difficult to tap, so the fallback hit test projects the
 * model bounds and checks this rectangle instead.
 */
data class ScreenHitBounds(
    val left: Float,
    val top: Float,
    val right: Float,
    val bottom: Float,
)

/** Returns the smallest finite screen-space rectangle containing the projected model points. */
fun screenHitBoundsFromPoints(points: List<Pair<Float, Float>>): ScreenHitBounds? {
    val finitePoints = points.filter { (x, y) -> x.isFinite() && y.isFinite() }
    if (finitePoints.isEmpty()) return null

    return ScreenHitBounds(
        left = finitePoints.minOf { it.first },
        top = finitePoints.minOf { it.second },
        right = finitePoints.maxOf { it.first },
        bottom = finitePoints.maxOf { it.second },
    )
}

/** Checks whether a screen point lies within bounds, allowing a uniform touch-target padding. */
fun containsScreenHitBounds(
    bounds: ScreenHitBounds,
    x: Float,
    y: Float,
    paddingPx: Float,
): Boolean {
    require(paddingPx.isFinite() && paddingPx >= 0f) { "paddingPx must be finite and non-negative" }
    return x.isFinite() && y.isFinite() &&
        x >= bounds.left - paddingPx &&
        x <= bounds.right + paddingPx &&
        y >= bounds.top - paddingPx &&
        y <= bounds.bottom + paddingPx
}

/**
 * Accepts only a horizontal-plane hit that is inside both ARCore's polygon and its extents.
 *
 * ARCore can return a geometrically nearby hit outside the tracked polygon when the plane is
 * still being refined. That fallback is useful for generic raycasts, but it can make an AR object
 * appear to hover beside the physical surface. Placement uses the stricter rule.
 */
fun isPlacementPlanePoseValid(
    isPoseInPolygon: Boolean,
    isPoseInExtents: Boolean,
): Boolean = isPoseInPolygon && isPoseInExtents

/**
 * Toggles the catalog preview without changing already-committed models in the scene.
 */
fun toggleSelectedProduct(
    current: ArProduct?,
    tapped: ArProduct,
): ArProduct? = if (current?.id == tapped.id) null else tapped

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

/**
 * Computes the next placed-model yaw, preferring the value tracked by the placement screen over
 * a quaternion-to-Euler readback from the renderer.
 */
fun nextPlacedModelYaw(rememberedYawDegrees: Float?, nodeYawDegrees: Float): Float =
    turn180Degrees(rememberedYawDegrees ?: nodeYawDegrees)
