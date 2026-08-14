package com.example.ferosa_landscaping

import androidx.compose.ui.test.assertCountEquals
import androidx.compose.ui.test.junit4.createComposeRule
import androidx.compose.ui.test.onAllNodesWithText
import androidx.compose.ui.test.onNodeWithContentDescription
import androidx.compose.ui.test.onNodeWithText
import androidx.compose.ui.test.performClick
import androidx.compose.ui.test.performScrollTo
import com.example.ferosa_landscaping.ui.home.HomeScreen
import com.example.ferosa_landscaping.ui.summary.AccountSummary
import com.example.ferosa_landscaping.ui.theme.Ferosa_landscapingTheme
import org.junit.Assert.assertTrue
import org.junit.Rule
import org.junit.Test

/**
 * Home answers two questions for a returning customer - "where is my order" and
 * "when is someone coming" - from [AccountSummary]. These cover the three states
 * that section can be in, because picking the wrong one shows a customer with a
 * live order the empty-state copy instead.
 */
class HomeScreenTest {

    @get:Rule
    val compose = createComposeRule()

    private val order = AccountSummary.ActiveOrder(
        id = 1,
        orderNumber = "ORD-00042",
        status = "out_for_delivery",
        statusLabel = "Out for delivery",
        totalAmount = 12500.0,
        placedAt = "2026-08-10T09:00:00+08:00",
    )

    private val appointment = AccountSummary.NextAppointment(
        id = 7,
        service = "Garden maintenance",
        status = "confirmed",
        statusLabel = "Confirmed",
        appointmentAt = "2026-08-14T09:00:00+08:00",
    )

    private fun setHome(
        summary: AccountSummary,
        onOpenAr: () -> Unit = {},
        onOpenShop: () -> Unit = {},
        onOpenOrders: () -> Unit = {},
        onOpenBook: () -> Unit = {},
        onOpenAppointments: () -> Unit = {},
    ) {
        compose.setContent {
            Ferosa_landscapingTheme {
                HomeScreen(
                    summary = summary,
                    onOpenAr = onOpenAr,
                    onOpenShop = onOpenShop,
                    onOpenOrders = onOpenOrders,
                    onOpenBook = onOpenBook,
                    onOpenAppointments = onOpenAppointments,
                )
            }
        }
    }

    @Test
    fun before_the_summary_arrives_neither_activity_nor_empty_state_is_claimed() {
        setHome(AccountSummary(isLoaded = false, activeOrder = order))

        // The skeleton holds the space; asserting the copy is absent is what
        // stops a half-loaded Home from telling the customer they have nothing.
        compose.onNodeWithText("Your activity").assertDoesNotExist()
        compose.onNodeWithText("Nothing in progress").assertDoesNotExist()
    }

    @Test
    fun a_loaded_but_empty_account_gets_the_empty_state() {
        setHome(AccountSummary(isLoaded = true))

        compose.onNodeWithText("Nothing in progress").assertExists()
        compose.onNodeWithText("Your activity").assertDoesNotExist()
    }

    @Test
    fun an_active_order_is_shown_with_its_number_status_and_total() {
        setHome(AccountSummary(isLoaded = true, activeOrder = order))

        compose.onNodeWithText("Your activity").assertExists()
        compose.onNodeWithText("ORDER ORD-00042").assertExists()
        compose.onNodeWithText("Out for delivery").assertExists()
        // formatPeso rounds to whole pesos and groups thousands.
        compose.onNodeWithText("₱12,500").assertExists()
        compose.onNodeWithText("Nothing in progress").assertDoesNotExist()
    }

    @Test
    fun tapping_the_active_order_opens_orders() {
        var opened = false
        setHome(AccountSummary(isLoaded = true, activeOrder = order), onOpenOrders = { opened = true })

        compose.onNodeWithText("Out for delivery").performScrollTo().performClick()

        assertTrue("expected the order card to open Orders", opened)
    }

    @Test
    fun the_next_appointment_is_shown_with_its_service() {
        setHome(AccountSummary(isLoaded = true, nextAppointment = appointment))

        compose.onNodeWithText("NEXT VISIT").assertExists()
        compose.onNodeWithText("Garden maintenance").assertExists()
    }

    @Test
    fun an_order_and_a_visit_can_be_shown_together() {
        setHome(AccountSummary(isLoaded = true, activeOrder = order, nextAppointment = appointment))

        compose.onNodeWithText("ORDER ORD-00042").assertExists()
        compose.onNodeWithText("NEXT VISIT").assertExists()
    }

    @Test
    fun the_empty_state_buttons_reach_shop_and_booking() {
        var shop = false
        var book = false
        setHome(
            AccountSummary(isLoaded = true),
            onOpenShop = { shop = true },
            onOpenBook = { book = true },
        )

        compose.onNodeWithText("Browse shop").performScrollTo().performClick()
        compose.onNodeWithText("Book a service").performScrollTo().performClick()

        assertTrue("expected Browse shop to open Shop", shop)
        assertTrue("expected Book a service to open booking", book)
    }

    @Test
    fun both_ar_entry_points_launch_ar() {
        var launches = 0
        setHome(AccountSummary(isLoaded = true), onOpenAr = { launches++ })

        compose.onNodeWithText("Launch AR camera").performScrollTo().performClick()
        compose.onNodeWithText("AR Mode").performScrollTo().performClick()

        assertTrue("expected both AR entry points to fire, got $launches", launches == 2)
    }

    @Test
    fun unread_messages_are_surfaced_in_both_places_that_show_them() {
        setHome(AccountSummary(isLoaded = true, unreadMessages = 5))

        // Home carries the count twice on purpose - the header icon button and
        // the Messages quick action - so an exact count is what catches one of
        // them silently dropping the badge.
        compose.onAllNodesWithText("5").assertCountEquals(2)
        compose.onNodeWithContentDescription("Messages, 5 unread").assertExists()
        compose.onNodeWithText("Chat with support").performScrollTo().assertExists()
    }
}
