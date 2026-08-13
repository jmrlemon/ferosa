package com.example.ferosa_landscaping.ui.summary

import android.os.SystemClock
import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.ferosa_landscaping.data.api.models.MobileSummaryDto
import com.example.ferosa_landscaping.data.repository.AuthenticationException
import com.example.ferosa_landscaping.data.repository.SummaryRepository
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.Job
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

/**
 * Keeps the account snapshot behind Home and the navigation badges.
 *
 * Refreshes are requested from several places - app resume, tab changes, and
 * every page the WebView finishes - so they are coalesced: at most one request
 * in flight, and no more than one per [MIN_REFRESH_INTERVAL_MS] unless the
 * caller forces it.
 */
class SummaryViewModel(private val repository: SummaryRepository) : ViewModel() {

    companion object {
        private const val MIN_REFRESH_INTERVAL_MS = 2_000L
    }

    private val _summary = MutableStateFlow(AccountSummary())
    val summary: StateFlow<AccountSummary> = _summary.asStateFlow()

    /** Set when the server rejects the session, so the shell can sign out. */
    private val _sessionExpired = MutableStateFlow(false)
    val sessionExpired: StateFlow<Boolean> = _sessionExpired.asStateFlow()

    private var inFlight: Job? = null
    private var lastRefreshAt = 0L

    fun refresh(force: Boolean = false) {
        val now = SystemClock.elapsedRealtime()
        if (!force && now - lastRefreshAt < MIN_REFRESH_INTERVAL_MS) return
        if (inFlight?.isActive == true) return

        lastRefreshAt = now
        inFlight = viewModelScope.launch {
            try {
                _summary.value = repository.summary().toAccountSummary()
            } catch (e: CancellationException) {
                throw e
            } catch (_: AuthenticationException) {
                _sessionExpired.value = true
            } catch (_: Exception) {
                // A failed poll is not worth showing an error for; the badges
                // simply keep their previous values until the next attempt.
            }
        }
    }

    /** Clears everything on sign-out so the next account starts blank. */
    fun clear() {
        inFlight?.cancel()
        inFlight = null
        lastRefreshAt = 0L
        _summary.value = AccountSummary()
        _sessionExpired.value = false
    }

    class Factory(private val repository: SummaryRepository) : ViewModelProvider.Factory {
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            require(modelClass.isAssignableFrom(SummaryViewModel::class.java)) {
                "Unsupported ViewModel: ${modelClass.name}"
            }
            @Suppress("UNCHECKED_CAST")
            return SummaryViewModel(repository) as T
        }
    }
}

internal fun MobileSummaryDto.toAccountSummary(): AccountSummary = AccountSummary(
    cartCount = cartCount,
    unreadNotifications = unreadNotifications,
    unreadMessages = unreadMessages,
    activeOrder = activeOrder?.let {
        AccountSummary.ActiveOrder(
            id = it.id,
            orderNumber = it.orderNumber,
            status = it.status,
            statusLabel = it.statusLabel,
            totalAmount = it.totalAmount,
            placedAt = it.placedAt,
        )
    },
    nextAppointment = nextAppointment?.let {
        AccountSummary.NextAppointment(
            id = it.id,
            service = it.service,
            status = it.status,
            statusLabel = it.statusLabel,
            appointmentAt = it.appointmentAt,
        )
    },
    isLoaded = true,
)
