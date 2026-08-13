package com.example.ferosa_landscaping.ui.more

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Chat
import androidx.compose.material.icons.automirrored.filled.EventNote
import androidx.compose.material.icons.filled.AccountCircle
import androidx.compose.material.icons.filled.Collections
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material.icons.filled.Receipt
import androidx.compose.material.icons.filled.Star
import androidx.compose.material.icons.filled.ViewInAr
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.ferosa_landscaping.ui.components.QuickActionCard
import com.example.ferosa_landscaping.ui.navigation.AppScreen
import com.example.ferosa_landscaping.ui.summary.AccountSummary
import com.example.ferosa_landscaping.ui.theme.Brand50
import com.example.ferosa_landscaping.ui.theme.Brand700
import com.example.ferosa_landscaping.ui.theme.Surface50
import com.example.ferosa_landscaping.ui.theme.Surface500
import com.example.ferosa_landscaping.ui.theme.Surface900

private data class MoreAction(
    val icon: ImageVector,
    val title: String,
    val subtitle: String,
    val screen: AppScreen? = null,
    val opensAr: Boolean = false,
    val badgeCount: Int = 0,
)

/**
 * Overflow destinations.
 *
 * Orders and Appointments used to live here while the Estimator - a tool most
 * customers use once - held a permanent navigation slot. Home now surfaces the
 * active order and next visit directly, and the Estimator moved in here.
 */
@Composable
fun MoreScreen(
    modifier: Modifier = Modifier,
    userRole: String,
    summary: AccountSummary = AccountSummary(),
    onNavigate: (AppScreen) -> Unit,
    onOpenAr: () -> Unit,
) {
    val isStaffWorkspace = userRole == "admin" || userRole == "staff"
    val actions = if (isStaffWorkspace) {
        buildList {
            add(MoreAction(Icons.AutoMirrored.Filled.EventNote, "Appointments", "Pending confirmations and visits", AppScreen.ADMIN_APPOINTMENTS))
            add(MoreAction(Icons.Default.Receipt, "Orders & Delivery", "Processing, fulfilment, and proof", AppScreen.ADMIN_ORDERS))
            if (userRole == "admin") {
                add(MoreAction(Icons.Default.AccountCircle, "Business Profile", "Trust details and policies", AppScreen.ADMIN_BUSINESS_PROFILE))
            }
            add(
                MoreAction(
                    Icons.Default.Notifications,
                    "Notifications",
                    "New work and status alerts",
                    AppScreen.NOTIFICATIONS,
                    badgeCount = summary.unreadNotifications,
                )
            )
            add(MoreAction(Icons.Default.Home, "Customer View", "Review the customer experience", AppScreen.HOME))
            add(MoreAction(Icons.Default.AccountCircle, "Admin Account", "Work profile and secure sign-out", AppScreen.ADMIN_ACCOUNT))
            add(MoreAction(Icons.Default.ViewInAr, "AR Visualizer", "Test placement models", opensAr = true))
        }
    } else {
        // Estimator is absent on purpose - it has its own tab, the same reason
        // Shop and Book are not repeated here.
        listOf(
            MoreAction(Icons.AutoMirrored.Filled.EventNote, "Appointments", "Scheduled services and history", AppScreen.APPOINTMENTS),
            MoreAction(Icons.Default.Receipt, "Orders", "Purchases and delivery tracking", AppScreen.ORDERS),
            MoreAction(Icons.Default.Collections, "Projects", "Explore completed landscapes", AppScreen.PROJECTS),
            MoreAction(
                Icons.AutoMirrored.Filled.Chat,
                "Messages",
                "Talk with the Ferosa team",
                AppScreen.MESSAGES,
                badgeCount = summary.unreadMessages,
            ),
            MoreAction(
                Icons.Default.Notifications,
                "Notifications",
                "Order and booking updates",
                AppScreen.NOTIFICATIONS,
                badgeCount = summary.unreadNotifications,
            ),
            MoreAction(Icons.Default.Star, "Feedback", "Share your experience", AppScreen.FEEDBACK),
            MoreAction(Icons.Default.AccountCircle, "Account", "Profile, preferences, and sign-out", AppScreen.ACCOUNT),
            MoreAction(Icons.Default.ViewInAr, "AR Visualizer", "Preview elements in your space", opensAr = true),
        )
    }

    Column(
        modifier = modifier
            .background(Surface50)
            .verticalScroll(rememberScrollState())
            .padding(horizontal = 18.dp, vertical = 18.dp),
        verticalArrangement = Arrangement.spacedBy(14.dp),
    ) {
        Column(modifier = Modifier.padding(vertical = 4.dp)) {
            Text(
                if (isStaffWorkspace) "Operations tools" else "More from Ferosa",
                style = MaterialTheme.typography.headlineSmall,
                color = Surface900,
                fontWeight = FontWeight.Bold,
            )
            Spacer(Modifier.height(5.dp))
            Text(
                if (isStaffWorkspace) {
                    "Open the same live admin workflows available on the web dashboard."
                } else {
                    "Planning tools, your history, messages, and account settings."
                },
                style = MaterialTheme.typography.bodyMedium,
                color = Surface500,
                lineHeight = 20.sp,
            )
        }

        actions.chunked(2).forEach { rowActions ->
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(12.dp),
            ) {
                rowActions.forEach { action ->
                    QuickActionCard(
                        icon = action.icon,
                        title = action.title,
                        subtitle = action.subtitle,
                        modifier = Modifier.weight(1f),
                        badgeCount = action.badgeCount,
                        onClick = {
                            if (action.opensAr) onOpenAr()
                            else action.screen?.let(onNavigate)
                        },
                    )
                }
                if (rowActions.size == 1) {
                    Spacer(Modifier.weight(1f))
                }
            }
        }

        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(14.dp),
            colors = CardDefaults.cardColors(containerColor = Brand50),
        ) {
            Text(
                "Your products, prices, stock, projects, and notifications stay synchronized with Ferosa.",
                modifier = Modifier.padding(16.dp),
                style = MaterialTheme.typography.bodySmall,
                color = Brand700,
                lineHeight = 19.sp,
            )
        }
    }
}
