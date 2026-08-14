package com.example.ferosa_landscaping

import com.example.ferosa_landscaping.ui.web.webCallbackMatchesCurrentDocument
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class WebViewNavigationTest {

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
