package com.example.ferosa_landscaping.notifications

import android.Manifest
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.os.Build
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import androidx.core.content.ContextCompat
import com.example.ferosa_landscaping.MainActivity
import com.example.ferosa_landscaping.R
import com.example.ferosa_landscaping.ui.navigation.AppScreen

/**
 * Local notifications for order, appointment and message changes.
 *
 * These are raised by [SummaryPollWorker] from data the app polls itself. There
 * is no push service involved, so nothing here needs a Firebase project or a
 * google-services.json - the trade-off is latency, bounded by WorkManager's
 * fifteen-minute floor for periodic work.
 */
object FerosaNotifications {

    const val EXTRA_TARGET_SCREEN = "com.example.ferosa_landscaping.TARGET_SCREEN"

    private const val CHANNEL_ORDERS = "ferosa_orders"
    private const val CHANNEL_APPOINTMENTS = "ferosa_appointments"
    private const val CHANNEL_MESSAGES = "ferosa_messages"

    // Fixed per category so an update replaces the previous notification for
    // that topic instead of stacking a new one every poll.
    private const val ID_ORDER = 1001
    private const val ID_APPOINTMENT = 1002
    private const val ID_MESSAGES = 1003

    fun ensureChannels(context: Context) {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) return

        val manager = context.getSystemService(NotificationManager::class.java) ?: return
        listOf(
            Triple(CHANNEL_ORDERS, "Order updates", "Status changes on orders you have placed."),
            Triple(CHANNEL_APPOINTMENTS, "Appointments", "Confirmations and changes to booked visits."),
            Triple(CHANNEL_MESSAGES, "Messages", "Replies from the Ferosa team."),
        ).forEach { (id, name, description) ->
            manager.createNotificationChannel(
                NotificationChannel(id, name, NotificationManager.IMPORTANCE_DEFAULT).apply {
                    this.description = description
                }
            )
        }
    }

    /**
     * From API 33 a notification is silently dropped unless POST_NOTIFICATIONS
     * has been granted. Below that it is granted at install time.
     */
    fun canPost(context: Context): Boolean {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.TIRAMISU) return true
        return ContextCompat.checkSelfPermission(
            context,
            Manifest.permission.POST_NOTIFICATIONS,
        ) == PackageManager.PERMISSION_GRANTED
    }

    fun orderUpdate(context: Context, orderNumber: String, statusLabel: String) {
        post(
            context = context,
            id = ID_ORDER,
            channelId = CHANNEL_ORDERS,
            title = "Order $orderNumber",
            body = statusLabel,
            target = AppScreen.ORDERS,
        )
    }

    fun appointmentUpdate(context: Context, service: String, statusLabel: String) {
        post(
            context = context,
            id = ID_APPOINTMENT,
            channelId = CHANNEL_APPOINTMENTS,
            title = "$service appointment",
            body = statusLabel,
            target = AppScreen.APPOINTMENTS,
        )
    }

    fun newMessages(context: Context, count: Int) {
        post(
            context = context,
            id = ID_MESSAGES,
            channelId = CHANNEL_MESSAGES,
            title = "Ferosa replied",
            body = if (count == 1) "You have 1 unread message" else "You have $count unread messages",
            target = AppScreen.MESSAGES,
        )
    }

    private fun post(
        context: Context,
        id: Int,
        channelId: String,
        title: String,
        body: String,
        target: AppScreen,
    ) {
        // Repeated inline rather than delegated to canPost() so lint can see the
        // guard; it does not follow the check through a helper.
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU &&
            ContextCompat.checkSelfPermission(
                context,
                Manifest.permission.POST_NOTIFICATIONS,
            ) != PackageManager.PERMISSION_GRANTED
        ) {
            return
        }
        ensureChannels(context)

        val intent = Intent(context, MainActivity::class.java).apply {
            flags = Intent.FLAG_ACTIVITY_SINGLE_TOP or Intent.FLAG_ACTIVITY_CLEAR_TOP
            putExtra(EXTRA_TARGET_SCREEN, target.name)
        }
        val pendingIntent = PendingIntent.getActivity(
            context,
            id,
            intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE,
        )

        val notification = NotificationCompat.Builder(context, channelId)
            .setSmallIcon(R.drawable.ic_notification)
            .setContentTitle(title)
            .setContentText(body)
            .setStyle(NotificationCompat.BigTextStyle().bigText(body))
            .setPriority(NotificationCompat.PRIORITY_DEFAULT)
            .setAutoCancel(true)
            .setContentIntent(pendingIntent)
            .build()

        // Guarded above, but the platform can still refuse - a revoked
        // permission between the check and here, or a disabled channel - and
        // that must not take the background worker down with it.
        try {
            NotificationManagerCompat.from(context).notify(id, notification)
        } catch (_: SecurityException) {
            // Nothing useful to do; the user simply does not get this one.
        }
    }
}
