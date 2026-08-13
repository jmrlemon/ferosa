package com.example.ferosa_landscaping.ui.shell

/**
 * What the AR screen should be told about the design being previewed.
 *
 * Every AR entry point used to send the same hardcoded `ferosa://ar?designId=demo`,
 * so ArActivity - which reads `type`, `size` and `cost` - always fell back to
 * its defaults and the header read "Design · 100 sq m" no matter what the
 * customer had configured in the estimator. The web estimator has always built
 * the full link (see estimator.blade.php); this is the native equivalent.
 *
 * All fields are optional: opening AR from Home or More carries no design, and
 * ArActivity's own defaults are the right answer there.
 */
data class ArLaunchRequest(
    val projectType: String? = null,
    val propertySize: Int? = null,
    val estimatedCost: Long? = null,
    val productId: Int? = null,
) {
    /** Builds the deep link ArActivity parses in `launchArScreen()`. */
    fun toDeepLink(): String = buildString {
        append("ferosa://ar?designId=native")
        projectType?.let { append("&type=").append(it) }
        propertySize?.let { append("&size=").append(it) }
        estimatedCost?.let { append("&cost=").append(it) }
        productId?.let { append("&productId=").append(it) }
    }
}
