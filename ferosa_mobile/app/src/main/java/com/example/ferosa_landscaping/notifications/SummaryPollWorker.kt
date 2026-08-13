package com.example.ferosa_landscaping.notifications

import android.content.Context
import androidx.work.Constraints
import androidx.work.CoroutineWorker
import androidx.work.ExistingPeriodicWorkPolicy
import androidx.work.NetworkType
import androidx.work.PeriodicWorkRequestBuilder
import androidx.work.WorkManager
import androidx.work.WorkerParameters
import com.example.ferosa_landscaping.data.api.ApiClient
import com.example.ferosa_landscaping.data.repository.AuthenticationException
import com.example.ferosa_landscaping.data.repository.SummaryRepository
import com.example.ferosa_landscaping.ui.summary.AccountSummary
import com.example.ferosa_landscaping.ui.summary.toAccountSummary
import java.util.concurrent.TimeUnit

/**
 * Polls /api/mobile/summary in the background and raises a local notification
 * when something the customer cares about has actually changed.
 *
 * Fifteen minutes is WorkManager's floor for periodic work, so that is the
 * worst-case latency between a staff member changing an order's status and the
 * customer's phone saying so.
 */
class SummaryPollWorker(
    appContext: Context,
    params: WorkerParameters,
) : CoroutineWorker(appContext, params) {

    override suspend fun doWork(): Result {
        val repository = SummaryRepository(ApiClient.service)

        val summary = try {
            repository.summary().toAccountSummary()
        } catch (_: AuthenticationException) {
            // The session is gone. Retrying cannot fix that and would keep
            // waking the device, so stop until the next sign-in re-enqueues us.
            cancel(applicationContext)
            return Result.success()
        } catch (_: Exception) {
            return Result.retry()
        }

        val store = SummaryStore(applicationContext)
        val previous = store.read()

        // The first run after sign-in establishes the baseline. Announcing it
        // would tell the customer about an order they placed minutes ago.
        if (previous.hasBaseline) {
            announceChanges(previous, summary)
        }

        store.write(summary)
        return Result.success()
    }

    private fun announceChanges(previous: SummaryStore.Snapshot, summary: AccountSummary) {
        summary.activeOrder?.let { order ->
            val isDifferentOrder = order.id != previous.orderId
            val isNewStatus = order.status != previous.orderStatus
            if (isDifferentOrder || isNewStatus) {
                FerosaNotifications.orderUpdate(
                    context = applicationContext,
                    orderNumber = order.orderNumber,
                    statusLabel = order.statusLabel,
                )
            }
        }

        summary.nextAppointment?.let { appointment ->
            val isDifferentAppointment = appointment.id != previous.appointmentId
            val isNewStatus = appointment.status != previous.appointmentStatus
            if (isDifferentAppointment || isNewStatus) {
                FerosaNotifications.appointmentUpdate(
                    context = applicationContext,
                    service = appointment.service,
                    statusLabel = appointment.statusLabel,
                )
            }
        }

        // Only a rise counts: reading the thread in the app drops the count and
        // must not be reported as new activity.
        if (summary.unreadMessages > previous.unreadMessages) {
            FerosaNotifications.newMessages(applicationContext, summary.unreadMessages)
        }
    }

    companion object {
        private const val WORK_NAME = "ferosa_summary_poll"

        /** Starts polling. Safe to call on every sign-in. */
        fun enqueue(context: Context) {
            val request = PeriodicWorkRequestBuilder<SummaryPollWorker>(
                15, TimeUnit.MINUTES,
            ).setConstraints(
                Constraints.Builder()
                    .setRequiredNetworkType(NetworkType.CONNECTED)
                    .build()
            ).build()

            WorkManager.getInstance(context).enqueueUniquePeriodicWork(
                WORK_NAME,
                // KEEP so an already-running schedule is not reset on every
                // launch, which would push the next run out indefinitely.
                ExistingPeriodicWorkPolicy.KEEP,
                request,
            )
        }

        fun cancel(context: Context) {
            WorkManager.getInstance(context).cancelUniqueWork(WORK_NAME)
        }
    }
}
