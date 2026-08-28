package com.example.ferosa_landscaping.ui.components

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Badge
import androidx.compose.material3.BadgedBox
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.example.ferosa_landscaping.ui.theme.Brand50
import com.example.ferosa_landscaping.ui.theme.Brand600
import com.example.ferosa_landscaping.ui.theme.Surface100
import com.example.ferosa_landscaping.ui.theme.Surface400
import com.example.ferosa_landscaping.ui.theme.Surface600
import com.example.ferosa_landscaping.ui.theme.Surface900

/**
 * Tile used by both the Home quick-action grid and the More screen.
 *
 * [badgeCount] draws an unread/count marker on the icon when greater than zero;
 * the count is folded into the click label so TalkBack announces "Messages, 3
 * unread" rather than leaving the badge invisible to screen readers.
 */
@Composable
fun QuickActionCard(
    icon: ImageVector,
    title: String,
    subtitle: String,
    modifier: Modifier = Modifier,
    badgeCount: Int = 0,
    onClick: () -> Unit,
) {
    val accessibleLabel = if (badgeCount > 0) "$title, $badgeCount unread" else title

    Card(
        modifier = modifier
            .border(1.dp, Surface100, RoundedCornerShape(14.dp))
            .clickable(onClick = onClick, role = Role.Button, onClickLabel = accessibleLabel),
        shape = RoundedCornerShape(14.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            BadgedBox(
                badge = {
                    if (badgeCount > 0) {
                        Badge(containerColor = Brand600, contentColor = Color.White) {
                            Text(formatBadgeCount(badgeCount))
                        }
                    }
                }
            ) {
                Box(
                    modifier = Modifier
                        .size(36.dp)
                        .clip(RoundedCornerShape(10.dp))
                        .background(Brand50),
                    contentAlignment = Alignment.Center
                ) {
                    Icon(
                        imageVector = icon,
                        contentDescription = null,
                        tint = Brand600,
                        modifier = Modifier.size(20.dp)
                    )
                }
            }
            Spacer(Modifier.height(10.dp))
            Text(
                title,
                style = MaterialTheme.typography.titleSmall,
                color = Surface900,
                fontWeight = FontWeight.SemiBold,
            )
            Spacer(Modifier.height(2.dp))
            Text(subtitle, style = MaterialTheme.typography.bodySmall, color = Surface400)
        }
    }
}

/**
 * Circular header action. Keeps the 48dp minimum touch target while the visible
 * circle stays 44dp, and exposes itself to TalkBack as a button.
 *
 * The badge is a sibling of the touch target rather than a [BadgedBox] around
 * it. A badge sits at the top-right *outside* its anchor, and the anchor here is
 * a circle clipped to the 48dp target — so the badge was being cut in half by
 * that clip, leaving the unread count on Home unreadable. The outer Box has no
 * clip of its own, so the badge can overhang it.
 */
@Composable
fun HeaderIconButton(
    icon: ImageVector,
    contentDescription: String,
    onClick: () -> Unit,
    badgeCount: Int = 0,
) {
    val accessibleLabel = if (badgeCount > 0) {
        "$contentDescription, $badgeCount unread"
    } else {
        contentDescription
    }

    Box(contentAlignment = Alignment.Center) {
        Box(
            modifier = Modifier
                .size(48.dp)
                .clip(CircleShape)
                .clickable(onClick = onClick, role = Role.Button, onClickLabel = accessibleLabel),
            contentAlignment = Alignment.Center,
        ) {
            Box(
                modifier = Modifier
                    .size(44.dp)
                    .clip(CircleShape)
                    .background(Surface100),
                contentAlignment = Alignment.Center,
            ) {
                Icon(
                    icon,
                    contentDescription = accessibleLabel,
                    tint = Surface600,
                    modifier = Modifier.size(20.dp),
                )
            }
        }

        if (badgeCount > 0) {
            Badge(
                containerColor = Brand600,
                contentColor = Color.White,
                modifier = Modifier.align(Alignment.TopEnd),
            ) {
                Text(formatBadgeCount(badgeCount))
            }
        }
    }
}

/** Keeps a runaway unread count from stretching the badge across the tile. */
internal fun formatBadgeCount(count: Int): String = if (count > 99) "99+" else count.toString()
