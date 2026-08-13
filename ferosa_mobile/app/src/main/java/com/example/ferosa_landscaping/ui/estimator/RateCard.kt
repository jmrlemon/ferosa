package com.example.ferosa_landscaping.ui.estimator

import androidx.compose.ui.Alignment
import com.example.ferosa_landscaping.data.api.models.EstimatorRateCardDto

/**
 * The estimator's pricing, as the screen wants it.
 *
 * The authoritative copy lives in the server's config/estimator.php and arrives
 * over GET /api/mobile/estimator-rates. [BUNDLED_RATE_CARD] below is the
 * offline fallback and matches that config at the time of writing - it is a
 * safety net, not a second source of truth, so a price change on the server
 * reaches installed apps without a release.
 */
data class RateCard(
    val projectTypes: List<ProjectType>,
    val tiers: List<Tier>,
    val addons: List<Addon>,
    val quickSizes: List<Int>,
    val rangeLow: Double,
    val rangeHigh: Double,
    val defaultProjectType: String,
    val defaultTier: String,
    val defaultSize: Int,
) {
    data class ProjectType(
        val key: String,
        val label: String,
        val description: String,
        val rate: Int,
    )

    data class Tier(
        val key: String,
        val label: String,
        val multiplier: Double,
        val description: String,
        val packageTitle: String,
        val caption: String,
        val examples: List<String>,
        val visualIndex: Int,
    ) {
        /**
         * Where to anchor the three-panel sprite at
         * public/images/tier-package-visuals.png for this tier.
         */
        val imageAlignment: Alignment
            get() = when (visualIndex) {
                0 -> Alignment.CenterStart
                1 -> Alignment.Center
                else -> Alignment.CenterEnd
            }

        /** Horizontal pivot for the same sprite, as a 0..1 fraction. */
        val imagePivotFraction: Float
            get() = when (visualIndex) {
                0 -> 0f
                1 -> 0.5f
                else -> 1f
            }
    }

    data class Addon(
        val key: String,
        val label: String,
        val description: String,
        val amount: Int,
    )

    fun projectType(key: String): ProjectType =
        projectTypes.firstOrNull { it.key == key } ?: projectTypes.first()

    fun tier(key: String): Tier = tiers.firstOrNull { it.key == key } ?: tiers.first()
}

/**
 * Offline fallback. Kept in step with config/estimator.php; the server copy wins
 * whenever the device can reach it.
 */
val BUNDLED_RATE_CARD = RateCard(
    projectTypes = listOf(
        RateCard.ProjectType("design", "Garden Design", "Full landscape design, plant selection & installation.", 50),
        RateCard.ProjectType("maintenance", "Maintenance", "Regular lawn care, pruning, weeding & cleanup.", 10),
        RateCard.ProjectType("hardscaping", "Hardscaping", "Patios, walkways, retaining walls & stonework.", 120),
    ),
    tiers = listOf(
        RateCard.Tier(
            key = "standard",
            label = "Standard",
            multiplier = 1.0,
            description = "Budget-friendly materials with solid craftsmanship.",
            packageTitle = "Starter Garden",
            caption = "A practical garden using common plants, lawn, and simple edging.",
            examples = listOf(
                "Common shrubs and groundcover",
                "Basic soil preparation",
                "Simple edging and layout",
            ),
            visualIndex = 0,
        ),
        RateCard.Tier(
            key = "premium",
            label = "Premium",
            multiplier = 1.6,
            description = "Higher-grade plants and materials with more visual detail.",
            packageTitle = "Enhanced Garden",
            caption = "A polished garden with mature planting, a refined path, stone edging, and lighting.",
            examples = listOf(
                "Mature plants and layered planting",
                "Decorative stone and edging",
                "Selected garden lighting",
            ),
            visualIndex = 1,
        ),
        RateCard.Tier(
            key = "luxury",
            label = "Luxury",
            multiplier = 2.4,
            description = "Top-tier finishes, specimen plants, and bespoke design.",
            packageTitle = "Signature Landscape",
            caption = "A bespoke landscape with specimen plants, custom stonework, lighting, and a water feature.",
            examples = listOf(
                "Rare or specimen plants",
                "Custom hardscape and irrigation",
                "Water feature or signature focal point",
            ),
            visualIndex = 2,
        ),
    ),
    addons = listOf(
        RateCard.Addon("irrigation", "Irrigation System", "Automated sprinkler & drip lines.", 40_000),
        RateCard.Addon("lighting", "Outdoor Lighting", "Path lights, spotlights & accent LEDs.", 25_000),
        RateCard.Addon("water", "Water Feature", "Custom pond, fountain or water wall.", 60_000),
        RateCard.Addon("pergola", "Pergola / Gazebo", "Shaded structure for outdoor living.", 80_000),
        RateCard.Addon("fence", "Decorative Fencing", "Bamboo, wood or metal boundary fencing.", 20_000),
        RateCard.Addon("soil", "Soil Preparation & Mulch", "Deep aeration, enriched topsoil & mulch.", 15_000),
    ),
    quickSizes = listOf(50, 100, 250, 500, 1000, 2000, 5000),
    rangeLow = 0.8,
    rangeHigh = 1.25,
    defaultProjectType = "design",
    defaultTier = "standard",
    defaultSize = 100,
)

/**
 * Converts the API payload, falling back to [BUNDLED_RATE_CARD] for any section
 * the server did not send rather than rendering an estimator with no options.
 */
fun EstimatorRateCardDto.toRateCard(): RateCard {
    val projectTypes = projectTypes.map { (key, dto) ->
        RateCard.ProjectType(key, dto.label, dto.description, dto.rate)
    }
    val mappedTiers = tiers.map { (key, dto) ->
        RateCard.Tier(
            key = key,
            label = dto.label,
            multiplier = dto.multiplier,
            description = dto.description,
            packageTitle = dto.packageTitle,
            caption = dto.caption,
            examples = dto.examples,
            visualIndex = dto.visualIndex,
        )
    }
    val mappedAddons = addons.map { (key, dto) ->
        RateCard.Addon(key, dto.label, dto.description, dto.amount)
    }

    return RateCard(
        projectTypes = projectTypes.ifEmpty { BUNDLED_RATE_CARD.projectTypes },
        tiers = mappedTiers.ifEmpty { BUNDLED_RATE_CARD.tiers },
        addons = mappedAddons.ifEmpty { BUNDLED_RATE_CARD.addons },
        quickSizes = quickSizes.ifEmpty { BUNDLED_RATE_CARD.quickSizes },
        rangeLow = range?.low ?: BUNDLED_RATE_CARD.rangeLow,
        rangeHigh = range?.high ?: BUNDLED_RATE_CARD.rangeHigh,
        defaultProjectType = defaults?.projectType ?: BUNDLED_RATE_CARD.defaultProjectType,
        defaultTier = defaults?.tier ?: BUNDLED_RATE_CARD.defaultTier,
        defaultSize = defaults?.size ?: BUNDLED_RATE_CARD.defaultSize,
    )
}
