package com.example.ferosa_landscaping.ui.format

import java.text.NumberFormat
import java.text.SimpleDateFormat
import java.util.Locale

/**
 * Locale("en","PH") is deprecated; Locale.forLanguageTag is the supported way to
 * build one. Held as a constant so the peso formatter does not rebuild it.
 */
val PH_LOCALE: Locale = Locale.forLanguageTag("en-PH")

/** Whole-peso amount, e.g. ₱12,500. */
fun formatPeso(value: Double): String {
    val number = NumberFormat.getIntegerInstance(PH_LOCALE).format(Math.round(value))
    return "₱$number"
}

/**
 * Renders an ISO-8601 timestamp from the API as, for example,
 * "Fri, 14 Aug · 9:00 AM".
 *
 * java.time is API 26+ and this module targets minSdk 24 without core library
 * desugaring, so this goes through SimpleDateFormat. The `XXX` pattern needs
 * API 24, which is exactly the floor.
 *
 * Returns null rather than throwing when the server sends something unexpected,
 * so a bad timestamp degrades to hiding one line instead of crashing Home.
 */
fun formatIsoDateTime(iso: String): String? = runCatching {
    val parser = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ssXXX", PH_LOCALE)
    val parsed = parser.parse(iso) ?: return null
    SimpleDateFormat("EEE, d MMM · h:mm a", PH_LOCALE).format(parsed)
}.getOrNull()
