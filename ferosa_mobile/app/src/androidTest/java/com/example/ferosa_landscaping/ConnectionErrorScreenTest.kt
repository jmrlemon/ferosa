package com.example.ferosa_landscaping

import androidx.compose.ui.test.junit4.createComposeRule
import androidx.compose.ui.test.onNodeWithText
import androidx.compose.ui.test.performClick
import com.example.ferosa_landscaping.ui.theme.Ferosa_landscapingTheme
import com.example.ferosa_landscaping.ui.web.ConnectionErrorScreen
import org.junit.Assert.assertEquals
import org.junit.Rule
import org.junit.Test

/**
 * This screen replaces the WebView's default error page whenever the main frame
 * fails or the load times out. It is the only thing standing between a dropped
 * connection and a blank white activity, so its retry has to work.
 */
class ConnectionErrorScreenTest {

    @get:Rule
    val compose = createComposeRule()

    @Test
    fun it_explains_the_failure_and_offers_a_retry() {
        compose.setContent {
            Ferosa_landscapingTheme { ConnectionErrorScreen(onRetry = {}) }
        }

        compose.onNodeWithText("No connection").assertExists()
        compose.onNodeWithText("Try again").assertExists()
    }

    @Test
    fun retry_can_be_pressed_more_than_once() {
        var retries = 0
        compose.setContent {
            Ferosa_landscapingTheme { ConnectionErrorScreen(onRetry = { retries++ }) }
        }

        // A customer on a bad connection will tap this repeatedly; the screen
        // stays up until a load succeeds, so it must not latch after one press.
        compose.onNodeWithText("Try again").performClick()
        compose.onNodeWithText("Try again").performClick()

        assertEquals(2, retries)
    }
}
