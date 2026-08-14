package com.example.ferosa_landscaping

import androidx.compose.ui.test.assertIsSelected
import androidx.compose.ui.test.junit4.createComposeRule
import androidx.compose.ui.test.onNodeWithContentDescription
import androidx.compose.ui.test.onNodeWithText
import androidx.compose.ui.test.performClick
import com.example.ferosa_landscaping.ui.navigation.AppScreen
import com.example.ferosa_landscaping.ui.theme.Ferosa_landscapingTheme
import org.junit.Assert.assertEquals
import org.junit.Rule
import org.junit.Test

/**
 * The bottom bar is the only navigation a customer has, and it renders a
 * different set of destinations per role. A wrong tab set is not a cosmetic
 * problem - it is a customer looking at the staff workspace.
 */
class BottomNavigationTest {

    @get:Rule
    val compose = createComposeRule()

    private fun setNavigation(
        userRole: String,
        currentScreen: AppScreen = AppScreen.HOME,
        cartCount: Int = 0,
        unreadCount: Int = 0,
        onNavigate: (AppScreen) -> Unit = {},
    ) {
        compose.setContent {
            Ferosa_landscapingTheme {
                FerosaBottomNavigation(
                    currentScreen = currentScreen,
                    userRole = userRole,
                    cartCount = cartCount,
                    unreadCount = unreadCount,
                    onNavigate = onNavigate,
                )
            }
        }
    }

    @Test
    fun customer_sees_the_customer_destinations() {
        setNavigation(userRole = "user")

        listOf("Home", "Shop", "Estimator", "Book", "More").forEach { label ->
            compose.onNodeWithText(label).assertExists("missing customer tab: $label")
        }
        compose.onNodeWithText("Dashboard").assertDoesNotExist()
        compose.onNodeWithText("Inventory").assertDoesNotExist()
    }

    @Test
    fun staff_sees_the_workspace_destinations_and_never_the_shop() {
        setNavigation(userRole = "staff")

        listOf("Dashboard", "Portfolio", "Inventory", "Messages", "More").forEach { label ->
            compose.onNodeWithText(label).assertExists("missing staff tab: $label")
        }
        compose.onNodeWithText("Shop").assertDoesNotExist()
        compose.onNodeWithText("Book").assertDoesNotExist()
    }

    @Test
    fun admin_gets_the_same_workspace_as_staff() {
        setNavigation(userRole = "admin")

        compose.onNodeWithText("Dashboard").assertExists()
        compose.onNodeWithText("Shop").assertDoesNotExist()
    }

    @Test
    fun tapping_a_tab_reports_that_screen() {
        var navigatedTo: AppScreen? = null
        setNavigation(userRole = "user", onNavigate = { navigatedTo = it })

        compose.onNodeWithText("Book").performClick()

        assertEquals(AppScreen.BOOK, navigatedTo)
    }

    @Test
    fun the_open_tab_is_marked_selected() {
        setNavigation(userRole = "user", currentScreen = AppScreen.ESTIMATOR)

        compose.onNodeWithText("Estimator").assertIsSelected()
    }

    /**
     * MORE stands in for every screen that lives behind it, otherwise navigating
     * to Orders leaves no tab lit at all.
     */
    @Test
    fun more_stays_selected_for_the_screens_it_owns() {
        setNavigation(userRole = "user", currentScreen = AppScreen.ORDERS)

        compose.onNodeWithText("More").assertIsSelected()
    }

    // NavigationBarItem merges its descendants into one semantics node, and the
    // merged node keeps only the item's own text label - the badge count and the
    // icon's description are folded away. The badge assertions below therefore
    // have to read the unmerged tree; that is also exactly what TalkBack walks.

    @Test
    fun cart_count_shows_on_shop_and_is_announced_to_screen_readers() {
        setNavigation(userRole = "user", cartCount = 3)

        compose.onNodeWithText("3", useUnmergedTree = true).assertExists()
        compose.onNodeWithContentDescription(
            "Shop, 3 items in cart",
            useUnmergedTree = true,
        ).assertExists()
    }

    @Test
    fun a_runaway_unread_count_is_capped_in_the_badge() {
        setNavigation(userRole = "user", unreadCount = 1240)

        compose.onNodeWithText("99+", useUnmergedTree = true).assertExists()
        // The real number still reaches TalkBack; only the badge art is capped.
        compose.onNodeWithContentDescription(
            "More, 1240 unread",
            useUnmergedTree = true,
        ).assertExists()
    }

    @Test
    fun no_badge_is_drawn_when_there_is_nothing_to_report() {
        setNavigation(userRole = "user", cartCount = 0, unreadCount = 0)

        compose.onNodeWithText("0", useUnmergedTree = true).assertDoesNotExist()
        // Plain "Shop", with no count appended.
        compose.onNodeWithContentDescription("Shop", useUnmergedTree = true).assertExists()
    }
}
