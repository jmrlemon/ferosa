package com.example.ferosa_landscaping

import com.example.ferosa_landscaping.ui.ar.PlacementControlState
import com.example.ferosa_landscaping.ui.ar.canConfirmPlacement
import com.example.ferosa_landscaping.ui.ar.crosshairCoordinates
import com.example.ferosa_landscaping.ui.ar.turn180Degrees
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
    fun crosshair_coordinates_are_the_view_center_including_fractional_pixels() {
        assertEquals(634f to 1378f, crosshairCoordinates(width = 1268, height = 2756))
        assertEquals(2.5f to 3.5f, crosshairCoordinates(width = 5, height = 7))
    }

    @Test
    fun crosshair_coordinates_reject_non_positive_view_dimensions() {
        assertFalse(runCatching { crosshairCoordinates(width = 0, height = 7) }.isSuccess)
        assertFalse(runCatching { crosshairCoordinates(width = 5, height = 0) }.isSuccess)
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
}
