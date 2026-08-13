package com.example.ferosa_landscaping.ui.home

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Chat
import androidx.compose.material.icons.automirrored.filled.KeyboardArrowRight
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.Calculate
import androidx.compose.material.icons.filled.LocalShipping
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material.icons.filled.Receipt
import androidx.compose.material.icons.filled.ShoppingCart
import androidx.compose.material.icons.filled.ViewInAr
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.ferosa_landscaping.ui.components.HeaderIconButton
import com.example.ferosa_landscaping.ui.components.QuickActionCard
import com.example.ferosa_landscaping.ui.format.formatIsoDateTime
import com.example.ferosa_landscaping.ui.format.formatPeso
import com.example.ferosa_landscaping.ui.summary.AccountSummary
import com.example.ferosa_landscaping.ui.theme.Brand50
import com.example.ferosa_landscaping.ui.theme.Brand600
import com.example.ferosa_landscaping.ui.theme.Brand700
import com.example.ferosa_landscaping.ui.theme.Surface100
import com.example.ferosa_landscaping.ui.theme.Surface300
import com.example.ferosa_landscaping.ui.theme.Surface400
import com.example.ferosa_landscaping.ui.theme.Surface500
import com.example.ferosa_landscaping.ui.theme.Surface50
import com.example.ferosa_landscaping.ui.theme.Surface900
import java.util.Calendar

/**
 * The customer's landing screen.
 *
 * This used to be three static cards explaining what the app can do. A
 * returning customer already knows; what they open the app for is "where is my
 * order" and "when is someone coming". Those now sit at the top, fed by
 * [AccountSummary], and the feature copy is gone.
 */
@Composable
fun HomeScreen(
    modifier: Modifier = Modifier,
    summary: AccountSummary = AccountSummary(),
    onOpenAr: () -> Unit = {},
    onOpenShop: () -> Unit = {},
    onOpenOrders: () -> Unit = {},
    onOpenBook: () -> Unit = {},
    onOpenEstimator: () -> Unit = {},
    onOpenMessages: () -> Unit = {},
    onOpenNotifications: () -> Unit = {},
    onOpenAppointments: () -> Unit = {},
) {
    Column(
        modifier = modifier
            .fillMaxSize()
            .background(Surface50)
            .verticalScroll(rememberScrollState())
            .padding(horizontal = 20.dp, vertical = 16.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp)
    ) {
        HomeHeader(
            unreadMessages = summary.unreadMessages,
            unreadNotifications = summary.unreadNotifications,
            onOpenMessages = onOpenMessages,
            onOpenNotifications = onOpenNotifications,
        )

        Greeting()

        // ── What is happening on this account right now ─────────────────────
        when {
            !summary.isLoaded -> ActivitySkeleton()

            summary.activeOrder != null || summary.nextAppointment != null -> {
                Text(
                    "Your activity",
                    style = MaterialTheme.typography.titleMedium,
                    color = Surface900,
                    fontWeight = FontWeight.SemiBold,
                )
                summary.activeOrder?.let { order ->
                    ActiveOrderCard(order = order, onClick = onOpenOrders)
                }
                summary.nextAppointment?.let { appointment ->
                    NextAppointmentCard(appointment = appointment, onClick = onOpenAppointments)
                }
            }

            else -> NothingInFlightCard(onOpenShop = onOpenShop, onOpenBook = onOpenBook)
        }

        ArHeroCard(onOpenAr = onOpenAr)

        Text(
            "Start here",
            style = MaterialTheme.typography.titleMedium,
            color = Surface900,
            modifier = Modifier.padding(top = 4.dp)
        )

        Row(horizontalArrangement = Arrangement.spacedBy(12.dp), modifier = Modifier.fillMaxWidth()) {
            QuickActionCard(
                icon = Icons.Default.ShoppingCart,
                title = "Shop",
                subtitle = "Browse products",
                modifier = Modifier.weight(1f),
                onClick = onOpenShop
            )
            QuickActionCard(
                icon = Icons.Default.Receipt,
                title = "Orders",
                subtitle = "Track & history",
                modifier = Modifier.weight(1f),
                onClick = onOpenOrders
            )
        }

        Row(horizontalArrangement = Arrangement.spacedBy(12.dp), modifier = Modifier.fillMaxWidth()) {
            QuickActionCard(
                icon = Icons.Default.CalendarMonth,
                title = "Book Service",
                subtitle = "Schedule a visit",
                modifier = Modifier.weight(1f),
                onClick = onOpenBook
            )
            QuickActionCard(
                icon = Icons.Default.Calculate,
                title = "Estimator",
                subtitle = "Get a cost estimate",
                modifier = Modifier.weight(1f),
                onClick = onOpenEstimator
            )
        }

        Row(horizontalArrangement = Arrangement.spacedBy(12.dp), modifier = Modifier.fillMaxWidth()) {
            QuickActionCard(
                icon = Icons.Default.ViewInAr,
                title = "AR Mode",
                subtitle = "Camera view",
                modifier = Modifier.weight(1f),
                onClick = onOpenAr
            )
            QuickActionCard(
                icon = Icons.AutoMirrored.Filled.Chat,
                title = "Messages",
                subtitle = "Chat with support",
                modifier = Modifier.weight(1f),
                badgeCount = summary.unreadMessages,
                onClick = onOpenMessages
            )
        }

        Text(
            "Ferosa for Android",
            style = MaterialTheme.typography.labelSmall,
            color = Surface300,
            modifier = Modifier
                .fillMaxWidth()
                .padding(vertical = 12.dp),
            textAlign = TextAlign.Center
        )
    }
}

@Composable
private fun HomeHeader(
    unreadMessages: Int,
    unreadNotifications: Int,
    onOpenMessages: () -> Unit,
    onOpenNotifications: () -> Unit,
) {
    Row(
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.SpaceBetween,
        modifier = Modifier
            .fillMaxWidth()
            .padding(top = 8.dp)
    ) {
        Row(
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            Box(
                modifier = Modifier
                    .size(36.dp)
                    .clip(RoundedCornerShape(10.dp))
                    .background(Brand600),
                contentAlignment = Alignment.Center
            ) {
                Text("F", color = Color.White, fontWeight = FontWeight.Bold, fontSize = 18.sp)
            }
            Column {
                Text(
                    "Ferosa",
                    style = MaterialTheme.typography.titleMedium,
                    color = Surface900,
                    fontWeight = FontWeight.Bold,
                )
                Text(
                    "Garden companion",
                    style = MaterialTheme.typography.bodySmall,
                    color = Surface400
                )
            }
        }
        // 44dp circles inside 48dp targets: Android's minimum touch size
        // is 48dp, and these were 38dp — small enough to miss regularly.
        Row(horizontalArrangement = Arrangement.spacedBy(4.dp)) {
            HeaderIconButton(
                icon = Icons.AutoMirrored.Filled.Chat,
                contentDescription = "Messages",
                onClick = onOpenMessages,
                badgeCount = unreadMessages,
            )
            HeaderIconButton(
                icon = Icons.Default.Notifications,
                contentDescription = "Notifications",
                onClick = onOpenNotifications,
                badgeCount = unreadNotifications,
            )
        }
    }
}

@Composable
private fun Greeting() {
    Column(modifier = Modifier.padding(top = 4.dp, bottom = 2.dp)) {
        // Matches the greeting the web home page shows, rather than always
        // claiming it is morning.
        val greeting = remember {
            when (Calendar.getInstance().get(Calendar.HOUR_OF_DAY)) {
                in 0..11 -> "GOOD MORNING"
                in 12..17 -> "GOOD AFTERNOON"
                else -> "GOOD EVENING"
            }
        }
        Text(
            greeting,
            style = MaterialTheme.typography.labelSmall,
            color = Brand600,
            fontWeight = FontWeight.Bold,
            letterSpacing = 1.2.sp,
        )
        Spacer(Modifier.height(5.dp))
        Text(
            "Your outdoor space,\nmade simple.",
            style = MaterialTheme.typography.headlineMedium,
            color = Surface900,
            fontWeight = FontWeight.Bold,
            lineHeight = 34.sp,
        )
    }
}

@Composable
private fun ActiveOrderCard(order: AccountSummary.ActiveOrder, onClick: () -> Unit) {
    ActivityCard(
        icon = Icons.Default.LocalShipping,
        eyebrow = "ORDER ${order.orderNumber}",
        title = order.statusLabel,
        detail = formatPeso(order.totalAmount),
        onClick = onClick,
        clickLabel = "Order ${order.orderNumber}, ${order.statusLabel}",
    )
}

@Composable
private fun NextAppointmentCard(
    appointment: AccountSummary.NextAppointment,
    onClick: () -> Unit,
) {
    val whenText = formatIsoDateTime(appointment.appointmentAt)
    ActivityCard(
        icon = Icons.Default.CalendarMonth,
        eyebrow = "NEXT VISIT",
        title = appointment.service,
        detail = whenText ?: appointment.statusLabel,
        onClick = onClick,
        clickLabel = "Next visit, ${appointment.service}${whenText?.let { ", $it" } ?: ""}",
    )
}

@Composable
private fun ActivityCard(
    icon: ImageVector,
    eyebrow: String,
    title: String,
    detail: String,
    onClick: () -> Unit,
    clickLabel: String,
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .border(1.dp, Surface100, RoundedCornerShape(14.dp))
            .clickable(onClick = onClick, role = Role.Button, onClickLabel = clickLabel),
        shape = RoundedCornerShape(14.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
    ) {
        Row(
            modifier = Modifier.padding(16.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(14.dp),
        ) {
            Box(
                modifier = Modifier
                    .size(42.dp)
                    .clip(RoundedCornerShape(12.dp))
                    .background(Brand50),
                contentAlignment = Alignment.Center,
            ) {
                Icon(icon, contentDescription = null, tint = Brand700, modifier = Modifier.size(21.dp))
            }
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    eyebrow,
                    style = MaterialTheme.typography.labelSmall,
                    color = Surface400,
                    fontWeight = FontWeight.Bold,
                    letterSpacing = 0.8.sp,
                )
                Spacer(Modifier.height(3.dp))
                Text(
                    title,
                    style = MaterialTheme.typography.titleSmall,
                    color = Surface900,
                    fontWeight = FontWeight.SemiBold,
                )
                Spacer(Modifier.height(2.dp))
                Text(detail, style = MaterialTheme.typography.bodySmall, color = Surface500)
            }
            Icon(
                Icons.AutoMirrored.Filled.KeyboardArrowRight,
                contentDescription = null,
                tint = Surface300,
                modifier = Modifier.size(22.dp),
            )
        }
    }
}

/** Shown to an account with no order in flight and no upcoming visit. */
@Composable
private fun NothingInFlightCard(onOpenShop: () -> Unit, onOpenBook: () -> Unit) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(14.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
    ) {
        Column(modifier = Modifier.padding(18.dp)) {
            Text(
                "Nothing in progress",
                style = MaterialTheme.typography.titleSmall,
                color = Surface900,
                fontWeight = FontWeight.SemiBold,
            )
            Spacer(Modifier.height(4.dp))
            Text(
                "When you place an order or book a visit, you'll be able to track it here.",
                style = MaterialTheme.typography.bodySmall,
                color = Surface500,
                lineHeight = 18.sp,
            )
            Spacer(Modifier.height(14.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                Button(
                    onClick = onOpenShop,
                    shape = RoundedCornerShape(11.dp),
                    colors = ButtonDefaults.buttonColors(containerColor = Brand600),
                    contentPadding = PaddingValues(horizontal = 18.dp, vertical = 11.dp),
                ) {
                    Text("Browse shop", fontWeight = FontWeight.SemiBold, fontSize = 13.sp)
                }
                Button(
                    onClick = onOpenBook,
                    shape = RoundedCornerShape(11.dp),
                    colors = ButtonDefaults.buttonColors(
                        containerColor = Brand50,
                        contentColor = Brand700,
                    ),
                    contentPadding = PaddingValues(horizontal = 18.dp, vertical = 11.dp),
                ) {
                    Text("Book a service", fontWeight = FontWeight.SemiBold, fontSize = 13.sp)
                }
            }
        }
    }
}

/**
 * Placeholder held until the first summary arrives.
 *
 * Reserving the space stops the whole page jumping down once the request
 * resolves, which is what happens if the cards simply appear.
 */
@Composable
private fun ActivitySkeleton() {
    Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
        repeat(2) {
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .height(78.dp)
                    .clip(RoundedCornerShape(14.dp))
                    .background(Surface100)
            )
        }
    }
}

@Composable
private fun ArHeroCard(onOpenAr: () -> Unit) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(18.dp),
        colors = CardDefaults.cardColors(containerColor = Surface900),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(
            modifier = Modifier
                .background(
                    Brush.linearGradient(listOf(Surface900, Color(0xFF0F3D24), Brand700))
                )
                .padding(22.dp)
        ) {
            Surface(
                color = Color.White.copy(alpha = 0.12f),
                shape = RoundedCornerShape(999.dp)
            ) {
                Text(
                    "CAMERA AR PREVIEW",
                    color = Color.White,
                    fontSize = 11.sp,
                    fontWeight = FontWeight.SemiBold,
                    modifier = Modifier.padding(horizontal = 10.dp, vertical = 5.dp)
                )
            }
            Spacer(Modifier.height(14.dp))
            Text(
                "See it before you plant it",
                style = MaterialTheme.typography.headlineMedium,
                color = Color.White
            )
            Spacer(Modifier.height(8.dp))
            Text(
                "Place real 3D products in your space and explore the result from every angle.",
                style = MaterialTheme.typography.bodyMedium,
                color = Color(0xDDF4F4F5),
                lineHeight = 20.sp
            )
            Spacer(Modifier.height(20.dp))
            Button(
                onClick = onOpenAr,
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(12.dp),
                colors = ButtonDefaults.buttonColors(
                    containerColor = Color.White,
                    contentColor = Brand700
                ),
                contentPadding = PaddingValues(vertical = 14.dp)
            ) {
                Icon(
                    imageVector = Icons.Default.ViewInAr,
                    contentDescription = null,
                    modifier = Modifier.size(18.dp)
                )
                Spacer(Modifier.width(8.dp))
                Text("Launch AR camera", fontWeight = FontWeight.Bold)
            }
        }
    }
}
