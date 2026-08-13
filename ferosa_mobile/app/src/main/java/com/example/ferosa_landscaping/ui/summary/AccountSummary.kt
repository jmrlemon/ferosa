package com.example.ferosa_landscaping.ui.summary

/**
 * The customer's account at a glance, as served by GET /api/mobile/summary.
 *
 * Drives the native Home screen, the navigation badges, and the background poll
 * that raises local notifications.
 */
data class AccountSummary(
    val cartCount: Int = 0,
    val unreadNotifications: Int = 0,
    val unreadMessages: Int = 0,
    val activeOrder: ActiveOrder? = null,
    val nextAppointment: NextAppointment? = null,
    /** False until the first successful load, so Home can show a skeleton. */
    val isLoaded: Boolean = false,
) {
    data class ActiveOrder(
        val id: Int,
        val orderNumber: String,
        val status: String,
        val statusLabel: String,
        val totalAmount: Double,
        val placedAt: String,
    )

    data class NextAppointment(
        val id: Int,
        val service: String,
        val status: String,
        val statusLabel: String,
        val appointmentAt: String,
    )

    /** Everything the badges need, collapsed to one comparable value. */
    val hasAnyUnread: Boolean
        get() = unreadMessages > 0 || unreadNotifications > 0
}
