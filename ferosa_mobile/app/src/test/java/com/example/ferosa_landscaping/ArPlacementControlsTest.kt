package com.example.ferosa_landscaping

import androidx.compose.ui.unit.dp
import com.example.ferosa_landscaping.ui.ar.ArProduct
import com.example.ferosa_landscaping.ui.ar.PlacementControlState
import com.example.ferosa_landscaping.ui.ar.PlacementTargetStability
import com.example.ferosa_landscaping.ui.ar.canConfirmPlacement
import com.example.ferosa_landscaping.ui.ar.components.productInfoPanelMaxHeight
import com.example.ferosa_landscaping.ui.ar.components.productInfoPanelAvailableHeight
import com.example.ferosa_landscaping.ui.ar.components.productInfoPanelBottomContentPadding
import com.example.ferosa_landscaping.ui.ar.crosshairCoordinates
import com.example.ferosa_landscaping.ui.ar.isPlacementTargetStable
import com.example.ferosa_landscaping.ui.ar.isPlacementPlanePoseValid
import com.example.ferosa_landscaping.ui.ar.isPlacementDepthPoseValid
import com.example.ferosa_landscaping.ui.ar.toggleSelectedProduct
import com.example.ferosa_landscaping.ui.ar.turn180Degrees
import com.example.ferosa_landscaping.ui.ar.nextPlacedModelYaw
import com.example.ferosa_landscaping.ui.ar.updatePlacementTargetStability
import com.example.ferosa_landscaping.ui.ar.containsScreenHitBounds
import com.example.ferosa_landscaping.ui.ar.screenHitBoundsFromPoints
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class ArPlacementControlsTest {

    @Test
    fun place_requires_a_selected_product_and_ready_preview() {
        assertFalse(
            canConfirmPlacement(
                PlacementControlState(
                    hasSelectedProduct = false,
                    isPreviewReady = true,
                    hasFreshTarget = true,
                    isBusy = false,
                    isMoving = false,
                    placedCount = 0,
                    maxPlacedModels = 5,
                )
            )
        )
        assertFalse(
            canConfirmPlacement(
                PlacementControlState(
                    hasSelectedProduct = true,
                    isPreviewReady = false,
                    hasFreshTarget = true,
                    isBusy = false,
                    isMoving = false,
                    placedCount = 0,
                    maxPlacedModels = 5,
                )
            )
        )
    }

    @Test
    fun place_requires_a_fresh_target_and_idle_controls() {
        val missingTarget = readyState().copy(hasFreshTarget = false)
        val busy = readyState().copy(isBusy = true)
        val moving = readyState().copy(isMoving = true)

        assertFalse(canConfirmPlacement(missingTarget))
        assertFalse(canConfirmPlacement(busy))
        assertFalse(canConfirmPlacement(moving))
    }

    @Test
    fun fifth_model_can_be_placed_but_sixth_model_cannot() {
        assertTrue(canConfirmPlacement(readyState().copy(placedCount = 4)))
        assertFalse(canConfirmPlacement(readyState().copy(placedCount = 5)))
        assertFalse(canConfirmPlacement(readyState().copy(placedCount = -1)))
    }

    @Test
    fun half_turn_rotates_yaw_without_accumulating_out_of_range_values() {
        assertEquals(180f, turn180Degrees(0f), 0.0001f)
        assertEquals(0f, turn180Degrees(180f), 0.0001f)
        assertEquals(179f, turn180Degrees(359f), 0.0001f)
        assertEquals(0f, turn180Degrees(-180f), 0.0001f)
    }

    @Test
    fun placed_model_turn_uses_remembered_yaw_before_reading_node_euler_angles() {
        assertEquals(180f, nextPlacedModelYaw(rememberedYawDegrees = null, nodeYawDegrees = 0f), 0.0001f)
        assertEquals(0f, nextPlacedModelYaw(rememberedYawDegrees = 180f, nodeYawDegrees = 0f), 0.0001f)
    }

    @Test
    fun crosshair_coordinates_are_the_view_center_including_fractional_pixels() {
        assertEquals(634f to 1378f, crosshairCoordinates(width = 1268, height = 2756))
        assertEquals(2.5f to 3.5f, crosshairCoordinates(width = 5, height = 7))
    }

    @Test
    fun crosshair_coordinates_reject_non_positive_view_dimensions() {
        assertFalse(runCatching { crosshairCoordinates(width = 0, height = 7) }.isSuccess)
        assertFalse(runCatching { crosshairCoordinates(width = 5, height = 0) }.isSuccess)
    }

    @Test
    fun target_requires_two_valid_samples_before_it_is_confirmed() {
        var state = PlacementTargetStability()

        state = updatePlacementTargetStability(state, hasValidHit = true, nowMillis = 0L)
        assertFalse(isPlacementTargetStable(state, nowMillis = 0L))

        state = updatePlacementTargetStability(state, hasValidHit = true, nowMillis = 150L)
        assertTrue(isPlacementTargetStable(state, nowMillis = 150L))
    }

    @Test
    fun target_survives_one_probe_miss_but_expires_after_the_grace_period() {
        var state = PlacementTargetStability()
        state = updatePlacementTargetStability(state, hasValidHit = true, nowMillis = 0L)
        state = updatePlacementTargetStability(state, hasValidHit = true, nowMillis = 150L)

        state = updatePlacementTargetStability(state, hasValidHit = false, nowMillis = 300L)
        assertTrue(isPlacementTargetStable(state, nowMillis = 300L))

        state = updatePlacementTargetStability(state, hasValidHit = false, nowMillis = 501L)
        assertFalse(isPlacementTargetStable(state, nowMillis = 501L))
        assertEquals(0, state.consecutiveValidHits)
    }

    @Test
    fun miss_before_confirmation_does_not_create_a_stable_target() {
        var state = PlacementTargetStability()
        state = updatePlacementTargetStability(state, hasValidHit = true, nowMillis = 0L)
        state = updatePlacementTargetStability(state, hasValidHit = false, nowMillis = 150L)

        assertFalse(isPlacementTargetStable(state, nowMillis = 150L))
        assertEquals(0, state.consecutiveValidHits)
    }

    @Test
    fun placement_surface_requires_a_pose_inside_the_plane_polygon_and_extents() {
        assertTrue(isPlacementPlanePoseValid(isPoseInPolygon = true, isPoseInExtents = true))
        assertFalse(isPlacementPlanePoseValid(isPoseInPolygon = false, isPoseInExtents = true))
        assertFalse(isPlacementPlanePoseValid(isPoseInPolygon = true, isPoseInExtents = false))
    }

    @Test
    fun depth_fallback_accepts_only_nearly_upward_surface_normals() {
        assertTrue(isPlacementDepthPoseValid(surfaceNormalY = 1f))
        assertTrue(isPlacementDepthPoseValid(surfaceNormalY = 0.95f))
        assertFalse(isPlacementDepthPoseValid(surfaceNormalY = 0.93f))
        assertFalse(isPlacementDepthPoseValid(surfaceNormalY = 0f))
        assertFalse(isPlacementDepthPoseValid(surfaceNormalY = Float.NaN))
    }

    @Test
    fun tapping_the_selected_product_toggles_the_preview_off() {
        val product = sampleProduct(7)

        assertEquals(null, toggleSelectedProduct(current = product, tapped = product))
        assertEquals(product, toggleSelectedProduct(current = null, tapped = product))
    }

    @Test
    fun tapping_a_different_product_switches_the_preview_selection() {
        val first = sampleProduct(7)
        val second = sampleProduct(8)

        assertEquals(second, toggleSelectedProduct(current = first, tapped = second))
    }

    @Test
    fun screen_hit_bounds_cover_taps_on_the_visible_model_away_from_its_origin() {
        val bounds = screenHitBoundsFromPoints(
            listOf(
                480f to 240f,
                700f to 240f,
                480f to 1120f,
                700f to 1120f,
            )
        )

        assertTrue(bounds != null)
        assertTrue(containsScreenHitBounds(bounds!!, x = 600f, y = 300f, paddingPx = 0f))
        assertFalse(containsScreenHitBounds(bounds, x = 760f, y = 300f, paddingPx = 0f))
    }

    @Test
    fun screen_hit_bounds_ignore_non_finite_projection_points() {
        val bounds = screenHitBoundsFromPoints(
            listOf(
                Float.NaN to 200f,
                100f to Float.POSITIVE_INFINITY,
                120f to 220f,
                220f to 420f,
            )
        )

        assertTrue(bounds != null)
        assertTrue(containsScreenHitBounds(bounds!!, x = 170f, y = 320f, paddingPx = 0f))
    }

    @Test
    fun product_info_panel_is_capped_below_the_dialog_viewport() {
        assertEquals(720f, productInfoPanelMaxHeight(800.dp).value, 0.0001f)
    }

    @Test
    fun product_info_panel_uses_a_safe_fallback_for_an_unbounded_viewport() {
        assertEquals(640f, productInfoPanelMaxHeight((-1f).dp).value, 0.0001f)
    }

    @Test
    fun product_info_panel_uses_the_window_height_when_dialog_constraints_are_unbounded() {
        assertEquals(
            800f,
            productInfoPanelAvailableHeight(measuredHeight = (-1f).dp, windowHeight = 800.dp).value,
            0.0001f,
        )
        assertEquals(
            800f,
            productInfoPanelAvailableHeight(
                measuredHeight = Float.POSITIVE_INFINITY.dp,
                windowHeight = 800.dp,
            ).value,
            0.0001f,
        )
    }

    @Test
    fun product_info_panel_prefers_a_finite_dialog_height_over_the_window_height() {
        assertEquals(
            600f,
            productInfoPanelAvailableHeight(measuredHeight = 600.dp, windowHeight = 800.dp).value,
            0.0001f,
        )
    }

    @Test
    fun product_info_panel_keeps_a_safe_bottom_gap_when_navigation_insets_are_missing() {
        assertEquals(32f, productInfoPanelBottomContentPadding(0.dp).value, 0.0001f)
    }

    @Test
    fun product_info_panel_adds_a_small_gap_after_a_reported_navigation_inset() {
        assertEquals(36f, productInfoPanelBottomContentPadding(24.dp).value, 0.0001f)
    }

    private fun readyState() = PlacementControlState(
        hasSelectedProduct = true,
        isPreviewReady = true,
        hasFreshTarget = true,
        isBusy = false,
        isMoving = false,
        placedCount = 0,
        maxPlacedModels = 5,
    )

    private fun sampleProduct(id: Int) = ArProduct(
        id = id,
        name = "Plant $id",
        price = 10.0,
        thumbnailUrl = "",
        modelUrl = "https://example.test/model-$id.glb",
        heightCm = 50f,
        category = "Plants",
        description = "",
        inStock = true,
    )
}
