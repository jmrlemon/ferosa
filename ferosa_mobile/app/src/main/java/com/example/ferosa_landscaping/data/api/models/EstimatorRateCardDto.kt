package com.example.ferosa_landscaping.data.api.models

import com.google.gson.annotations.SerializedName

/**
 * Response of GET /api/mobile/estimator-rates — the server's copy of
 * config/estimator.php.
 *
 * Gson deserialises JSON objects into LinkedHashMap, so the display order of
 * project types, tiers and add-ons follows the order they are written in the
 * config file rather than being arbitrary.
 */
data class EstimatorRateCardDto(
    @SerializedName("project_types") val projectTypes: Map<String, ProjectTypeDto> = emptyMap(),
    @SerializedName("tiers") val tiers: Map<String, TierDto> = emptyMap(),
    @SerializedName("addons") val addons: Map<String, AddonDto> = emptyMap(),
    @SerializedName("quick_sizes") val quickSizes: List<Int> = emptyList(),
    @SerializedName("range") val range: RangeDto? = null,
    @SerializedName("defaults") val defaults: DefaultsDto? = null,
)

data class ProjectTypeDto(
    @SerializedName("label") val label: String = "",
    @SerializedName("description") val description: String = "",
    @SerializedName("rate") val rate: Int = 0,
)

data class TierDto(
    @SerializedName("label") val label: String = "",
    @SerializedName("multiplier") val multiplier: Double = 1.0,
    @SerializedName("description") val description: String = "",
    @SerializedName("package_title") val packageTitle: String = "",
    @SerializedName("caption") val caption: String = "",
    @SerializedName("examples") val examples: List<String> = emptyList(),
    @SerializedName("visual_index") val visualIndex: Int = 0,
)

data class AddonDto(
    @SerializedName("label") val label: String = "",
    @SerializedName("description") val description: String = "",
    @SerializedName("amount") val amount: Int = 0,
)

data class RangeDto(
    @SerializedName("low") val low: Double = 0.8,
    @SerializedName("high") val high: Double = 1.25,
)

data class DefaultsDto(
    @SerializedName("project_type") val projectType: String = "design",
    @SerializedName("tier") val tier: String = "standard",
    @SerializedName("size") val size: Int = 100,
)
