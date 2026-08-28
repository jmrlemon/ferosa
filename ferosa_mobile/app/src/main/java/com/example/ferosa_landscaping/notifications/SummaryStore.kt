package com.example.ferosa_landscaping.notifications

import android.content.Context
import androidx.datastore.core.DataStore
import androidx.datastore.preferences.core.Preferences
import androidx.datastore.preferences.core.booleanPreferencesKey
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.intPreferencesKey
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import com.example.ferosa_landscaping.ui.summary.AccountSummary
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.launch

private val Context.summaryDataStore: DataStore<Preferences> by preferencesDataStore(
    name = "ferosa_summary_state"
)

/**
 * The last account snapshot the background poll acted on.
 *
 * Without this the poll would re-announce the same "out for delivery" every
 * fifteen minutes for as long as the order stayed in that state.
 */
class SummaryStore(private val context: Context) {

    data class Snapshot(
        val hasBaseline: Boolean = false,
        val orderId: Int = 0,
        val orderStatus: String = "",
        val appointmentId: Int = 0,
        val appointmentStatus: String = "",
        val unreadMessages: Int = 0,
    )

    private object Keys {
        val HAS_BASELINE = booleanPreferencesKey("has_baseline")
        val ORDER_ID = intPreferencesKey("order_id")
        val ORDER_STATUS = stringPreferencesKey("order_status")
        val APPOINTMENT_ID = intPreferencesKey("appointment_id")
        val APPOINTMENT_STATUS = stringPreferencesKey("appointment_status")
        val UNREAD_MESSAGES = intPreferencesKey("unread_messages")
    }

    suspend fun read(): Snapshot {
        val preferences = context.summaryDataStore.data.first()
        return Snapshot(
            hasBaseline = preferences[Keys.HAS_BASELINE] ?: false,
            orderId = preferences[Keys.ORDER_ID] ?: 0,
            orderStatus = preferences[Keys.ORDER_STATUS].orEmpty(),
            appointmentId = preferences[Keys.APPOINTMENT_ID] ?: 0,
            appointmentStatus = preferences[Keys.APPOINTMENT_STATUS].orEmpty(),
            unreadMessages = preferences[Keys.UNREAD_MESSAGES] ?: 0,
        )
    }

    suspend fun write(summary: AccountSummary) {
        context.summaryDataStore.edit { preferences ->
            preferences[Keys.HAS_BASELINE] = true
            preferences[Keys.ORDER_ID] = summary.activeOrder?.id ?: 0
            preferences[Keys.ORDER_STATUS] = summary.activeOrder?.status.orEmpty()
            preferences[Keys.APPOINTMENT_ID] = summary.nextAppointment?.id ?: 0
            preferences[Keys.APPOINTMENT_STATUS] = summary.nextAppointment?.status.orEmpty()
            preferences[Keys.UNREAD_MESSAGES] = summary.unreadMessages
        }
    }

    /** Called on sign-out so the next account does not inherit a baseline. */
    suspend fun clear() {
        context.summaryDataStore.edit { it.clear() }
    }

    companion object {
        /**
         * Clears the baseline from a caller that cannot suspend — sign-out runs
         * from a Compose event handler.
         *
         * Deliberately not tied to the composition or the ViewModel scope: both
         * are being torn down as part of signing out, and a cancelled write
         * would leave the previous account's order, appointment and unread
         * counts on disk. The next account's first poll then compares against
         * them, and either announces a stranger's order status or stays silent
         * about its own.
         */
        fun clearAsync(context: Context) {
            val appContext = context.applicationContext
            CoroutineScope(SupervisorJob() + Dispatchers.IO).launch {
                runCatching { SummaryStore(appContext).clear() }
            }
        }
    }
}
