package com.example.ferosa_landscaping.ui.navigation

import com.example.ferosa_landscaping.SERVER_URL

/**
 * Every destination the native shell can show.
 *
 * Most map to a Laravel page rendered in the shared WebView; the few that are
 * native Compose screens return `null` from [webUrl].
 */
enum class AppScreen {
    HOME,
    SHOP,
    PROJECTS,
    ORDERS,
    APPOINTMENTS,
    BOOK,
    ACCOUNT,
    ESTIMATOR,
    FEEDBACK,
    MESSAGES,
    NOTIFICATIONS,
    ADMIN_DASHBOARD,
    ADMIN_PROJECTS,
    ADMIN_INVENTORY,
    ADMIN_APPOINTMENTS,
    ADMIN_ORDERS,
    ADMIN_MESSAGES,
    ADMIN_BUSINESS_PROFILE,
    ADMIN_ACCOUNT,
    MORE,
}

/**
 * The Laravel page behind a screen, or `null` when the screen is native.
 *
 * Customer HOME and ESTIMATOR keep a URL of `null` for customers only - staff
 * see the web pages - so the role-aware caller in the shell decides, not this.
 */
fun AppScreen.webUrl(): String? = when (this) {
    AppScreen.HOME -> "$SERVER_URL/home"
    // The cart has no destination of its own on purpose. /checkout is the cart
    // *and* the delivery/payment form, which does not belong in a navigation
    // slot, and /shop already floats a cart button once there is something in
    // it. The cart count rides along as a badge on this tab instead.
    AppScreen.SHOP -> "$SERVER_URL/shop"
    AppScreen.PROJECTS -> "$SERVER_URL/projects"
    AppScreen.ORDERS -> "$SERVER_URL/orders"
    AppScreen.APPOINTMENTS -> "$SERVER_URL/appointments"
    AppScreen.BOOK -> "$SERVER_URL/schedule"
    AppScreen.ACCOUNT -> "$SERVER_URL/account"
    AppScreen.ESTIMATOR -> null
    AppScreen.FEEDBACK -> "$SERVER_URL/feedback"
    AppScreen.MESSAGES -> "$SERVER_URL/messages"
    AppScreen.NOTIFICATIONS -> "$SERVER_URL/notifications"
    AppScreen.ADMIN_DASHBOARD -> "$SERVER_URL/admin"
    AppScreen.ADMIN_PROJECTS -> "$SERVER_URL/admin/projects"
    AppScreen.ADMIN_INVENTORY -> "$SERVER_URL/admin?tab=products"
    AppScreen.ADMIN_APPOINTMENTS -> "$SERVER_URL/admin/service-scheduling"
    AppScreen.ADMIN_ORDERS -> "$SERVER_URL/admin/ordering-delivery"
    AppScreen.ADMIN_MESSAGES -> "$SERVER_URL/admin?tab=messages"
    AppScreen.ADMIN_BUSINESS_PROFILE -> "$SERVER_URL/admin/business-profile"
    AppScreen.ADMIN_ACCOUNT -> "$SERVER_URL/admin/account"
    AppScreen.MORE -> null
}

/**
 * Left-to-right position used to pick a transition direction between native
 * screens, so moving Home -> More slides the opposite way to More -> Home.
 */
fun AppScreen.nativeNavigationOrder(): Int = when (this) {
    AppScreen.HOME -> 0
    AppScreen.ESTIMATOR -> 1
    AppScreen.MORE -> 2
    else -> 1
}

/** True when this screen is drawn by Compose rather than the WebView. */
fun AppScreen.isNativeFor(userRole: String): Boolean {
    if (this == AppScreen.MORE) return true
    val isCustomer = userRole != "admin" && userRole != "staff"
    return isCustomer && (this == AppScreen.HOME || this == AppScreen.ESTIMATOR)
}
