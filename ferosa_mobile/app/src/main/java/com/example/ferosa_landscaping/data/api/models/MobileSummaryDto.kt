package com.example.ferosa_landscaping.data.api.models

import com.google.gson.annotations.SerializedName

/** Response of GET /api/mobile/summary. */
data class MobileSummaryDto(
    @SerializedName("cart_count") val cartCount: Int = 0,
    @SerializedName("unread_notifications") val unreadNotifications: Int = 0,
    @SerializedName("unread_messages") val unreadMessages: Int = 0,
    @SerializedName("active_order") val activeOrder: ActiveOrderDto? = null,
    @SerializedName("next_appointment") val nextAppointment: NextAppointmentDto? = null,
)

data class ActiveOrderDto(
    @SerializedName("id") val id: Int = 0,
    @SerializedName("order_number") val orderNumber: String = "",
    @SerializedName("status") val status: String = "",
    @SerializedName("status_label") val statusLabel: String = "",
    @SerializedName("total_amount") val totalAmount: Double = 0.0,
    @SerializedName("placed_at") val placedAt: String = "",
)

data class NextAppointmentDto(
    @SerializedName("id") val id: Int = 0,
    @SerializedName("service") val service: String = "",
    @SerializedName("status") val status: String = "",
    @SerializedName("status_label") val statusLabel: String = "",
    @SerializedName("appointment_at") val appointmentAt: String = "",
)
