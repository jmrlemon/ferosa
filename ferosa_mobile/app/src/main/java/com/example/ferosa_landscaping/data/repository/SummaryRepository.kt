package com.example.ferosa_landscaping.data.repository

import com.example.ferosa_landscaping.data.api.ApiService
import com.example.ferosa_landscaping.data.api.models.EstimatorRateCardDto
import com.example.ferosa_landscaping.data.api.models.MobileSummaryDto

/**
 * Reads the two native-shell endpoints.
 *
 * Both are cheap reads with no retry: the summary is polled often enough that a
 * failed attempt is corrected by the next one, and the rate card has a bundled
 * fallback in the estimator screen.
 */
class SummaryRepository(private val apiService: ApiService) {

    /**
     * @throws AuthenticationException when the Laravel session has expired, so
     *   the shell can return the user to the login screen rather than showing
     *   badges that will never update again.
     */
    suspend fun summary(): MobileSummaryDto {
        val response = apiService.getMobileSummary()

        if (response.isSuccessful) {
            return response.body() ?: MobileSummaryDto()
        }
        if (response.code() == 401 || response.code() == 419) {
            throw AuthenticationException("Session expired")
        }
        throw IllegalStateException("Failed to load summary (HTTP ${response.code()})")
    }

    suspend fun estimatorRates(): EstimatorRateCardDto? {
        val response = runCatching { apiService.getEstimatorRates() }.getOrNull() ?: return null
        return if (response.isSuccessful) response.body() else null
    }
}
