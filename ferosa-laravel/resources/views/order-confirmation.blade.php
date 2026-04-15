@extends('layouts.customer')

@section('content')
<main class="max-w-lg mx-auto px-4 sm:px-6 py-10">
  <div class="bg-white rounded-xl border border-surface-100 p-6">
    <div class="w-10 h-10 rounded-full bg-brand-50 text-brand-600 flex items-center justify-center mb-4">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
    </div>
    <h1 class="text-xl font-display font-bold text-surface-900 mb-1">Thank you — order received</h1>
    <p class="text-sm text-surface-400 mb-5">We sent a confirmation email to <strong class="text-surface-600">{{ $order->user->email }}</strong>.</p>

    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs border-t border-surface-100 pt-5">
      <div>
        <dt class="text-surface-400 mb-0.5">Order number</dt>
        <dd class="font-mono font-semibold text-surface-900">{{ $order->order_number }}</dd>
      </div>
      <div>
        <dt class="text-surface-400 mb-0.5">Total</dt>
        <dd class="font-semibold text-surface-900">&#8369;{{ number_format((float) $order->total_amount, 2) }}</dd>
      </div>
      <div class="sm:col-span-2">
        <dt class="text-surface-400 mb-2">Items</dt>
        <dd>
          @if ($order->orderItems->count())
            <ul class="divide-y divide-surface-100 rounded-lg border border-surface-100">
              @foreach ($order->orderItems as $line)
                <li class="flex justify-between gap-3 px-3 py-2.5 text-xs">
                  <span class="text-surface-800">{{ $line->name }} &times; {{ $line->qty }}</span>
                  <span class="text-surface-500">&#8369;{{ number_format((float) $line->price * $line->qty, 2) }}</span>
                </li>
              @endforeach
            </ul>
          @elseif (is_array($order->items) && count($order->items))
            <ul class="divide-y divide-surface-100 rounded-lg border border-surface-100">
              @foreach ($order->items as $line)
                <li class="flex justify-between gap-3 px-3 py-2.5 text-xs">
                  <span class="text-surface-800">{{ $line['name'] ?? 'Item' }} &times; {{ (int) ($line['qty'] ?? 1) }}</span>
                  <span class="text-surface-500">&#8369;{{ number_format((float) ($line['price'] ?? 0) * (int) ($line['qty'] ?? 1), 2) }}</span>
                </li>
              @endforeach
            </ul>
          @else
            <span class="text-surface-400">Line items not stored for this order.</span>
          @endif
        </dd>
      </div>
    </dl>

    <div class="mt-6 flex flex-wrap gap-2">
      <a href="{{ route('orders') }}" class="bg-surface-900 hover:bg-surface-800 text-white font-medium py-2 px-5 rounded-lg text-xs transition-colors">View my orders</a>
      <a href="{{ route('shop') }}" class="border border-surface-200 text-surface-500 hover:bg-surface-50 font-medium py-2 px-5 rounded-lg text-xs transition-colors">Continue shopping</a>
    </div>
  </div>
</main>

@include('partials.mobile-bottom-customer')

<script>
  @if(session('clear_cart'))
  try { localStorage.removeItem('ferosa_cart'); } catch (e) {}
  @endif
</script>
@endsection
