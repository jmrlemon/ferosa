package com.example.ferosa_landscaping

import com.example.ferosa_landscaping.data.api.models.AddonDto
import com.example.ferosa_landscaping.data.api.models.DefaultsDto
import com.example.ferosa_landscaping.data.api.models.EstimatorRateCardDto
import com.example.ferosa_landscaping.data.api.models.ProjectTypeDto
import com.example.ferosa_landscaping.data.api.models.RangeDto
import com.example.ferosa_landscaping.ui.estimator.BUNDLED_RATE_CARD
import com.example.ferosa_landscaping.ui.estimator.toRateCard
import org.junit.Assert.assertEquals
import org.junit.Test

/**
 * The estimator's pricing comes from the server so the app and the web page
 * cannot quote a customer differently. These cover the two halves of that: the
 * server's copy wins, and an unreachable server still leaves a usable screen.
 */
class RateCardTest {

    @Test
    fun `the servers rates replace the bundled ones`() {
        val card = EstimatorRateCardDto(
            projectTypes = mapOf(
                "design" to ProjectTypeDto(label = "Garden Design", description = "d", rate = 65),
            ),
            addons = mapOf(
                "irrigation" to AddonDto(label = "Irrigation", description = "d", amount = 44_000),
            ),
            quickSizes = listOf(10, 20),
            range = RangeDto(low = 0.5, high = 2.0),
            defaults = DefaultsDto(projectType = "design", tier = "premium", size = 300),
        ).toRateCard()

        assertEquals(65, card.projectType("design").rate)
        assertEquals(44_000, card.addons.first().amount)
        assertEquals(listOf(10, 20), card.quickSizes)
        assertEquals(0.5, card.rangeLow, 0.0)
        assertEquals(2.0, card.rangeHigh, 0.0)
        assertEquals("premium", card.defaultTier)
        assertEquals(300, card.defaultSize)
    }

    @Test
    fun `sections the server omits fall back to the bundled card`() {
        // An empty payload must not produce an estimator with no options at all.
        val card = EstimatorRateCardDto().toRateCard()

        assertEquals(BUNDLED_RATE_CARD.projectTypes, card.projectTypes)
        assertEquals(BUNDLED_RATE_CARD.tiers, card.tiers)
        assertEquals(BUNDLED_RATE_CARD.addons, card.addons)
        assertEquals(BUNDLED_RATE_CARD.quickSizes, card.quickSizes)
        assertEquals(BUNDLED_RATE_CARD.rangeLow, card.rangeLow, 0.0)
    }

    @Test
    fun `an unknown key falls back to the first option instead of throwing`() {
        // A saved selection can outlive a rate card change on the server.
        assertEquals(
            BUNDLED_RATE_CARD.projectTypes.first(),
            BUNDLED_RATE_CARD.projectType("no-such-project"),
        )
        assertEquals(BUNDLED_RATE_CARD.tiers.first(), BUNDLED_RATE_CARD.tier("no-such-tier"))
    }

    @Test
    fun `the bundled card still offers every quick size the web page does`() {
        // The drift this whole change removed: the web had 5000, the app did not.
        assertEquals(listOf(50, 100, 250, 500, 1000, 2000, 5000), BUNDLED_RATE_CARD.quickSizes)
    }
}
