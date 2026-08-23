package com.example.ferosa_landscaping

import com.example.ferosa_landscaping.ui.shell.WebNavigationCoverController
import com.example.ferosa_landscaping.ui.navigation.AppScreen
import com.example.ferosa_landscaping.ui.web.isWebTargetReady
import com.example.ferosa_landscaping.ui.web.webCallbackMatchesCurrentDocument
import com.example.ferosa_landscaping.ui.web.shouldReleaseWebNavigationCover
import com.example.ferosa_landscaping.ui.web.shouldShowWebNavigationCover
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class WebViewNavigationTest {

    @Test
    fun `navigation cover can be shown before screen state changes`() {
        var shown = false
        val controller = WebNavigationCoverController()
        controller.attach { shown = true }

        controller.showImmediately(AppScreen.SHOP)

        assertTrue(shown)
    }

    @Test
    fun `a stale Book callback cannot release a cover requested for Shop`() {
        val controller = WebNavigationCoverController()
        controller.showImmediately(AppScreen.SHOP)

        assertFalse(controller.releaseFor(AppScreen.BOOK))
        assertTrue(controller.isRequested())
        assertTrue(controller.releaseFor(AppScreen.SHOP))
        assertFalse(controller.isRequested())
    }

    @Test
    fun `switching from a rendered page covers the old frame immediately`() {
        assertTrue(
            shouldShowWebNavigationCover(
                isNativeScreen = false,
                hasTargetUrl = true,
                targetIsReady = false,
                currentScreenReady = false,
                isLoading = false,
            )
        )
    }

    @Test
    fun `a reload is covered even when the destination URL is unchanged`() {
        assertTrue(
            shouldShowWebNavigationCover(
                isNativeScreen = false,
                hasTargetUrl = true,
                targetIsReady = true,
                currentScreenReady = true,
                isLoading = true,
            )
        )
    }

    @Test
    fun `a committed destination removes the cover`() {
        assertFalse(
            shouldShowWebNavigationCover(
                isNativeScreen = false,
                hasTargetUrl = true,
                targetIsReady = true,
                currentScreenReady = true,
                isLoading = false,
            )
        )
    }

    @Test
    fun `a remembered destination is not ready when the WebView still shows another page`() {
        assertFalse(
            isWebTargetReady(
                readyUrlMatchesTarget = true,
                currentDocumentMatchesTarget = false,
                hasInFlightNavigation = true,
            )
        )
    }

    @Test
    fun `a destination is ready only after its current document commits`() {
        assertTrue(
            isWebTargetReady(
                readyUrlMatchesTarget = true,
                currentDocumentMatchesTarget = true,
                hasInFlightNavigation = false,
            )
        )
    }

    @Test
    fun `a committed page releases only for the active tab`() {
        assertTrue(
            shouldReleaseWebNavigationCover(
                callbackMatchesCurrentDocument = true,
                isNativeScreen = false,
                currentScreenReady = false,
                callbackMatchesTarget = true,
            )
        )
        assertTrue(
            shouldReleaseWebNavigationCover(
                callbackMatchesCurrentDocument = true,
                isNativeScreen = false,
                currentScreenReady = true,
                callbackMatchesTarget = false,
            )
        )
        assertFalse(
            shouldReleaseWebNavigationCover(
                callbackMatchesCurrentDocument = true,
                isNativeScreen = false,
                currentScreenReady = false,
                callbackMatchesTarget = false,
            )
        )
        assertFalse(
            shouldReleaseWebNavigationCover(
                callbackMatchesCurrentDocument = false,
                isNativeScreen = false,
                currentScreenReady = true,
                callbackMatchesTarget = true,
            )
        )
    }

    @Test
    fun `page finished cannot reveal the old frame before visible commit`() {
        assertFalse(
            shouldReleaseWebNavigationCover(
                callbackMatchesCurrentDocument = true,
                isNativeScreen = false,
                currentScreenReady = false,
                callbackMatchesTarget = true,
                callbackIsVisibleCommit = false,
            )
        )
    }

    @Test
    fun `an internal page stays visible after its tab has already committed`() {
        assertFalse(
            shouldShowWebNavigationCover(
                isNativeScreen = false,
                hasTargetUrl = true,
                targetIsReady = false,
                currentScreenReady = true,
                isLoading = false,
            )
        )
    }

    @Test
    fun `native screens do not cover their Compose content`() {
        assertFalse(
            shouldShowWebNavigationCover(
                isNativeScreen = true,
                hasTargetUrl = true,
                targetIsReady = false,
                currentScreenReady = false,
                isLoading = true,
            )
        )
    }

    @Test
    fun `a callback for the current document is accepted`() {
        assertTrue(
            webCallbackMatchesCurrentDocument(
                callbackUrl = "$SERVER_URL/shop",
                currentUrl = "$SERVER_URL/shop/",
            )
        )
    }

    @Test
    fun `a late callback from the previous tab is rejected`() {
        assertFalse(
            webCallbackMatchesCurrentDocument(
                callbackUrl = "$SERVER_URL/schedule",
                currentUrl = "$SERVER_URL/shop",
            )
        )
    }

    @Test
    fun `admin tabs on one route remain distinct`() {
        assertFalse(
            webCallbackMatchesCurrentDocument(
                callbackUrl = "$SERVER_URL/admin?tab=products",
                currentUrl = "$SERVER_URL/admin?tab=messages",
            )
        )
    }

    @Test
    fun `fragment-only changes still belong to the current document`() {
        assertTrue(
            webCallbackMatchesCurrentDocument(
                callbackUrl = "$SERVER_URL/shop#filters",
                currentUrl = "$SERVER_URL/shop",
            )
        )
    }

    @Test
    fun `a callback cannot be current without a WebView URL`() {
        assertFalse(
            webCallbackMatchesCurrentDocument(
                callbackUrl = "$SERVER_URL/shop",
                currentUrl = null,
            )
        )
    }
}
