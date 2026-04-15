<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Receipt {{ $order->order_number }} — Ferosa</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: system-ui, -apple-system, sans-serif; font-size: 13px; color: #111827; background: #f9fafb; padding: 32px 16px; }
    .receipt { background: #fff; max-width: 480px; margin: 0 auto; padding: 36px 32px; border-radius: 12px; border: 1px solid #e5e7eb; }
    .logo { font-size: 20px; font-weight: 800; letter-spacing: -.5px; color: #15803d; margin-bottom: 4px; }
    .sub { font-size: 11px; color: #9ca3af; margin-bottom: 24px; }
    .divider { border: none; border-top: 1px solid #f3f4f6; margin: 16px 0; }
    .row { display: flex; justify-content: space-between; padding: 6px 0; }
    .row .label { color: #6b7280; }
    .row .value { font-weight: 600; text-align: right; }
    .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
    .badge-pending   { background:#fefce8; color:#ca8a04; border:1px solid #fef08a; }
    .badge-confirmed { background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; }
    .badge-delivered { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }
    .badge-cancelled { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
    .badge-other     { background:#f3f4f6; color:#6b7280; border:1px solid #e5e7eb; }
    table { width: 100%; border-collapse: collapse; }
    thead th { font-size: 10px; text-transform: uppercase; letter-spacing: .06em; color: #9ca3af; padding: 8px 0; border-bottom: 1px solid #f3f4f6; text-align: left; }
    thead th:last-child { text-align: right; }
    tbody td { padding: 10px 0; border-bottom: 1px solid #f9fafb; vertical-align: top; font-size: 13px; }
    tbody tr:last-child td { border-bottom: none; }
    td:last-child { text-align: right; }
    .total-row td { padding-top: 14px; font-weight: 700; font-size: 14px; border-bottom: none; }
    .footer { margin-top: 28px; text-align: center; font-size: 11px; color: #9ca3af; }
    .print-btn { display: block; text-align: center; margin: 20px auto 0; max-width: 480px; }
    .print-btn button { background: #111827; color: #fff; border: none; padding: 10px 28px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
    .print-btn button:hover { background: #1f2937; }
    @media print {
      body { background: #fff; padding: 0; }
      .receipt { border: none; border-radius: 0; padding: 24px; }
      .print-btn { display: none; }
    }
  </style>
</head>
<body>

<div class="receipt">
  <div class="logo">Ferosa</div>
  <div class="sub">Garden & Landscaping · ferosa.app</div>

  <div class="row">
    <span class="label">Receipt</span>
    <span class="value" style="font-family:monospace">{{ $order->order_number }}</span>
  </div>
  <div class="row">
    <span class="label">Date</span>
    <span class="value">{{ optional($order->created_at)->format('F j, Y') }}</span>
  </div>
  <div class="row">
    <span class="label">Customer</span>
    <span class="value">{{ $order->user->name }}</span>
  </div>
  <div class="row">
    <span class="label">Email</span>
    <span class="value">{{ $order->user->email }}</span>
  </div>
  <div class="row">
    <span class="label">Status</span>
    <span class="value">
      @php
        $st  = $order->status ?? 'pending';
        $cls = match($st) {
          'pending'          => 'badge-pending',
          'confirmed'        => 'badge-confirmed',
          'delivered'        => 'badge-delivered',
          'out_for_delivery' => 'badge-delivered',
          'cancelled'        => 'badge-cancelled',
          default            => 'badge-other',
        };
      @endphp
      <span class="badge {{ $cls }}">{{ ucfirst(str_replace('_',' ', $st)) }}</span>
    </span>
  </div>

  <hr class="divider">

  <table>
    <thead>
      <tr>
        <th>Item</th>
        <th style="text-align:center;width:60px">Qty</th>
        <th style="text-align:right">Unit</th>
        <th>Subtotal</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($order->orderItems as $line)
      <tr>
        <td>{{ $line->name }}</td>
        <td style="text-align:center;color:#6b7280">{{ $line->qty }}</td>
        <td style="text-align:right;color:#6b7280">₱{{ number_format((float) $line->price, 2) }}</td>
        <td>₱{{ number_format((float) $line->price * $line->qty, 2) }}</td>
      </tr>
      @endforeach
      <tr class="total-row">
        <td colspan="3">Total</td>
        <td>₱{{ number_format((float) $order->total_amount, 2) }}</td>
      </tr>
    </tbody>
  </table>

  <hr class="divider">

  @php
    $dMethod = $order->delivery_method ?? 'delivery';
    $pMethod = $order->payment_method ?? 'cod';
  @endphp

  <div class="row">
    <span class="label">Delivery Method</span>
    <span class="value">{{ $dMethod === 'pickup' ? 'Pick-up' : 'Delivery' }}</span>
  </div>
  @if($dMethod === 'delivery' && $order->delivery_address)
  <div class="row">
    <span class="label">Delivery Name</span>
    <span class="value">{{ $order->delivery_name }}</span>
  </div>
  <div class="row">
    <span class="label">Phone</span>
    <span class="value">{{ $order->delivery_phone }}</span>
  </div>
  <div class="row">
    <span class="label">Address</span>
    <span class="value" style="max-width:60%;text-align:right">{{ $order->delivery_address }}, {{ $order->delivery_city }}</span>
  </div>
  @if($order->delivery_notes)
  <div class="row">
    <span class="label">Notes</span>
    <span class="value" style="max-width:60%;text-align:right">{{ $order->delivery_notes }}</span>
  </div>
  @endif
  @elseif($dMethod === 'pickup')
  <div class="row">
    <span class="label">Pickup Location</span>
    <span class="value">A. Arellano Ave. Mulawin, Orani, Philippines 2112</span>
  </div>
  @endif
  <div class="row">
    <span class="label">Payment Method</span>
    <span class="value">{{ $pMethod === 'gcash' ? 'GCash' : 'Cash on Delivery' }}</span>
  </div>
  @if($pMethod === 'gcash' && $order->payment_reference)
  <div class="row">
    <span class="label">GCash Reference</span>
    <span class="value" style="font-family:monospace">{{ $order->payment_reference }}</span>
  </div>
  @endif
  <div class="row">
    <span class="label">Delivery Fee</span>
    <span class="value" style="color:#16a34a">Free</span>
  </div>

  <div class="footer">
    Thank you for choosing Ferosa Garden & Landscaping.<br>
    This receipt was generated on {{ now()->format('F j, Y \a\t g:i A') }}.
  </div>
</div>

<div class="print-btn">
  <button onclick="window.print()">
    Print / Save as PDF
  </button>
  &nbsp;
  <a href="{{ route('orders') }}" style="font-size:13px;color:#6b7280;text-decoration:none;margin-left:12px">← Back to Orders</a>
</div>

</body>
</html>
