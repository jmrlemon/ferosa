<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body { font-family: system-ui, -apple-system, sans-serif; background: #f9fafb; color: #111827; margin: 0; padding: 24px; }
    .card { background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; max-width: 520px; margin: 0 auto; padding: 32px; }
    .badge { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
    .items { border: 1px solid #f3f4f6; border-radius: 8px; overflow: hidden; margin-top: 8px; }
    .item-row { display: flex; justify-content: space-between; padding: 10px 14px; border-bottom: 1px solid #f3f4f6; font-size: 13px; }
    .item-row:last-child { border-bottom: none; }
    .footer { margin-top: 24px; font-size: 12px; color: #9ca3af; }
  </style>
</head>
<body>
  <div class="card">
    <p style="font-size:20px;font-weight:700;margin:0 0 4px">Order Update</p>
    <p style="color:#6b7280;font-size:14px;margin:0 0 20px">Hi {{ $order->user->name ?? 'there' }}, your order status has changed.</p>

    <table style="width:100%;border-collapse:collapse;font-size:13px;margin-bottom:20px">
      <tr>
        <td style="color:#6b7280;padding:6px 0;width:40%">Order number</td>
        <td style="font-weight:600;font-family:monospace">{{ $order->order_number }}</td>
      </tr>
      <tr>
        <td style="color:#6b7280;padding:6px 0">New status</td>
        <td><span class="badge">{{ $order->status === 'delivered' && ! $order->customer_confirmed_at ? 'Delivered - Pending Confirmation' : ucfirst(str_replace('_', ' ', $order->status)) }}</span></td>
      </tr>
      <tr>
        <td style="color:#6b7280;padding:6px 0">Total</td>
        <td style="font-weight:600">₱{{ number_format((float) $order->total_amount, 2) }}</td>
      </tr>
    </table>

    @if (is_array($order->items) && count($order->items))
    <p style="font-size:13px;font-weight:600;margin-bottom:6px;color:#374151">Items</p>
    <div class="items">
      @foreach ($order->items as $line)
      <div class="item-row">
        <span>{{ $line['name'] ?? 'Item' }} &times; {{ (int) ($line['qty'] ?? 1) }}</span>
        <span style="color:#6b7280">₱{{ number_format((float) ($line['price'] ?? 0) * (int) ($line['qty'] ?? 1), 2) }}</span>
      </div>
      @endforeach
    </div>
    @endif

    @if ($order->status === 'cancelled')
    <p style="margin-top:16px;font-size:13px;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:10px 14px;border-radius:8px">
      Your order has been cancelled. If you have questions, please contact us.
    </p>
    @elseif ($order->status === 'delivered')
    <p style="margin-top:16px;font-size:13px;background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;padding:10px 14px;border-radius:8px">
      Your order has been delivered. Please confirm receipt in your Orders page after checking the delivery proof.
    </p>
    @elseif ($order->status === 'completed')
    <p style="margin-top:16px;font-size:13px;background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;padding:10px 14px;border-radius:8px">
      Your order is completed. Thank you for choosing Ferosa!
    </p>
    @else
    <p style="margin-top:16px;font-size:13px;color:#6b7280">
      You can track your order anytime in your <strong>Orders</strong> page.
    </p>
    @endif

    <div class="footer">— Ferosa Garden & Landscaping</div>
  </div>
</body>
</html>
