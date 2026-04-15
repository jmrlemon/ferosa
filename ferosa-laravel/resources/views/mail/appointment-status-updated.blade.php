<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body { font-family: system-ui, -apple-system, sans-serif; background: #f9fafb; color: #111827; margin: 0; padding: 24px; }
    .card { background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; max-width: 520px; margin: 0 auto; padding: 32px; }
    .badge { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; }
    .badge-confirmed { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
    .badge-completed { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
    .badge-cancelled { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
    .badge-scheduled { background: #fefce8; color: #ca8a04; border: 1px solid #fef08a; }
    .footer { margin-top: 24px; font-size: 12px; color: #9ca3af; }
  </style>
</head>
<body>
  <div class="card">
    <p style="font-size:20px;font-weight:700;margin:0 0 4px">Appointment Update</p>
    <p style="color:#6b7280;font-size:14px;margin:0 0 20px">Hi {{ $appointment->user->name ?? 'there' }}, your appointment status has been updated.</p>

    <table style="width:100%;border-collapse:collapse;font-size:13px;margin-bottom:20px">
      <tr>
        <td style="color:#6b7280;padding:6px 0;width:40%">Service</td>
        <td style="font-weight:600">{{ $appointment->serviceType->name ?? 'Service' }}</td>
      </tr>
      <tr>
        <td style="color:#6b7280;padding:6px 0">Date & Time</td>
        <td style="font-weight:600">{{ $appointment->appointment_at ? \Carbon\Carbon::parse($appointment->appointment_at)->format('l, F j, Y \a\t g:i A') : '' }}</td>
      </tr>
      <tr>
        <td style="color:#6b7280;padding:6px 0">New status</td>
        <td>
          @php
            $cls = match($appointment->status) {
              'confirmed' => 'badge-confirmed',
              'completed' => 'badge-completed',
              'cancelled' => 'badge-cancelled',
              default     => 'badge-scheduled',
            };
          @endphp
          <span class="badge {{ $cls }}">{{ ucfirst($appointment->status) }}</span>
        </td>
      </tr>
    </table>

    @if ($appointment->status === 'confirmed')
    <p style="font-size:13px;background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;padding:10px 14px;border-radius:8px">
      Your appointment has been confirmed. Please be available at the scheduled time.
    </p>
    @elseif ($appointment->status === 'completed')
    <p style="font-size:13px;background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;padding:10px 14px;border-radius:8px">
      Your service has been completed. Thank you for choosing Ferosa!
    </p>
    @elseif ($appointment->status === 'cancelled')
    <p style="font-size:13px;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:10px 14px;border-radius:8px">
      Your appointment has been cancelled. Please contact us to reschedule if needed.
    </p>
    @endif

    @if ($appointment->notes)
    <p style="margin-top:14px;font-size:13px;color:#6b7280"><strong>Notes:</strong> {{ $appointment->notes }}</p>
    @endif

    <div class="footer">— Ferosa Garden & Landscaping</div>
  </div>
</body>
</html>
