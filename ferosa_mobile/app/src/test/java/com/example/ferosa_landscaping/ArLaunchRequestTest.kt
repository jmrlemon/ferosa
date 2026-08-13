package com.example.ferosa_landscaping

import com.example.ferosa_landscaping.ui.shell.ArLaunchRequest
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test

/**
 * Guards the deep link the estimator hands to ArActivity.
 *
 * Every AR entry point used to send a hardcoded `ferosa://ar?designId=demo`.
 * ArActivity reads `type`, `size`, `cost` and `productId` from this URI, so the
 * AR header always showed its defaults no matter what the customer configured.
 */
class ArLaunchRequestTest {

    @Test
    fun `an empty request still produces a link ArActivity accepts`() {
        val link = ArLaunchRequest().toDeepLink()

        assertTrue(link.startsWith("ferosa://ar?"))
        // No design context means ArActivity's own defaults are correct.
        assertTrue(link.none { it == '&' })
    }

    @Test
    fun `estimator context is carried into the AR deep link`() {
        val link = ArLaunchRequest(
            projectType = "hardscaping",
            propertySize = 250,
            estimatedCost = 87_500,
        ).toDeepLink()

        assertTrue("missing project type in $link", link.contains("type=hardscaping"))
        assertTrue("missing size in $link", link.contains("size=250"))
        assertTrue("missing cost in $link", link.contains("cost=87500"))
    }

    @Test
    fun `a product preview carries only the product`() {
        val link = ArLaunchRequest(productId = 42).toDeepLink()

        assertTrue(link.contains("productId=42"))
        assertTrue("size should be absent in $link", !link.contains("size="))
        assertTrue("cost should be absent in $link", !link.contains("cost="))
    }

    @Test
    fun `the scheme and host are the ones the manifest registers`() {
        val link = ArLaunchRequest(projectType = "design").toDeepLink()

        assertEquals("ferosa://ar", link.substringBefore('?'))
    }
}
