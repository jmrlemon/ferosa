package com.example.ferosa_landscaping

import com.example.ferosa_landscaping.ui.navigation.AppScreen
import com.example.ferosa_landscaping.ui.navigation.isNativeFor
import com.example.ferosa_landscaping.ui.navigation.webUrl
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNotNull
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Test

/**
 * The shell decides per screen whether to draw Compose or point the shared
 * WebView at a Laravel page. Getting that wrong is not cosmetic: a screen that
 * claims to be native shows an empty overlay, and one that claims to be web
 * loads a page the role may not be allowed to see.
 */
class AppScreenRoutingTest {

    @Test
    fun `every screen either has a web page or is drawn natively`() {
        AppScreen.entries.forEach { screen ->
            val hasUrl = screen.webUrl() != null
            val nativeForSomeone = screen.isNativeFor("user") || screen.isNativeFor("admin")
            assertTrue(
                "$screen has neither a web URL nor a native renderer",
                hasUrl || nativeForSomeone,
            )
        }
    }

    @Test
    fun `web URLs all sit under the configured server`() {
        AppScreen.entries.mapNotNull { it.webUrl() }.forEach { url ->
            assertTrue("$url is not under $SERVER_URL", url.startsWith("$SERVER_URL/"))
        }
    }

    @Test
    fun `customers get native Home and Estimator, staff get the web pages`() {
        assertTrue(AppScreen.HOME.isNativeFor("user"))
        assertTrue(AppScreen.ESTIMATOR.isNativeFor("user"))

        listOf("admin", "staff").forEach { role ->
            assertFalse("$role should get web Home", AppScreen.HOME.isNativeFor(role))
            assertFalse("$role should get web Estimator", AppScreen.ESTIMATOR.isNativeFor(role))
        }
    }

    /**
     * ESTIMATOR is the one screen that is native for customers and web for
     * staff, so it is the only one whose null URL is role-dependent. Staff fall
     * through to the WebView, which needs a URL to load.
     */
    @Test
    fun `the estimator has no URL of its own and Home does`() {
        assertNull(AppScreen.ESTIMATOR.webUrl())
        assertNotNull(AppScreen.HOME.webUrl())
    }

    @Test
    fun `MORE is native for every role`() {
        listOf("user", "admin", "staff", "").forEach { role ->
            assertTrue("MORE must be native for $role", AppScreen.MORE.isNativeFor(role))
        }
        assertNull(AppScreen.MORE.webUrl())
    }

    @Test
    fun `an unrecognised role is treated as a customer, never as staff`() {
        assertTrue(AppScreen.HOME.isNativeFor("nonsense"))
        assertTrue(AppScreen.HOME.isNativeFor(""))
    }

    @Test
    fun `admin screens point at admin routes`() {
        val adminScreens = AppScreen.entries.filter { it.name.startsWith("ADMIN_") }
        assertEquals(8, adminScreens.size)
        adminScreens.forEach { screen ->
            val url = requireNotNull(screen.webUrl()) { "$screen must have a web URL" }
            assertTrue("$screen -> $url is not an admin route", url.startsWith("$SERVER_URL/admin"))
        }
    }

    @Test
    fun `no two screens share a destination`() {
        val urls = AppScreen.entries.mapNotNull { it.webUrl() }
        assertEquals("two screens resolve to the same page", urls.size, urls.toSet().size)
    }
}
