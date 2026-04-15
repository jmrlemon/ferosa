@extends('layouts.customer')

@section('content')
<main class="max-w-4xl mx-auto px-4 sm:px-6 py-10">
  <div class="mb-8">
    <h1 class="text-2xl font-display font-bold text-surface-900 mb-1">Track Delivery</h1>
    <p class="text-surface-400 text-sm">Enter your order ID to see the real-time status of your delivery.</p>
  </div>

  <!-- Search -->
  <div class="max-w-xl bg-white rounded-xl border border-surface-100 p-2 flex gap-2 mb-10">
    <div class="flex-1 relative">
      <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-surface-300" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <input type="text" id="order-id" placeholder="e.g. FRS-98243" class="w-full text-sm pl-9 pr-3 py-2 rounded-lg bg-surface-50 outline-none focus:ring-1 focus:ring-brand-500 transition-all font-mono uppercase">
    </div>
    <button onclick="trackOrder()" class="bg-surface-900 hover:bg-surface-800 text-white font-medium px-5 py-2 rounded-lg text-sm transition-colors">
      Track
    </button>
  </div>

  <!-- Tracking Result -->
  <div id="tracking-result" class="hidden max-w-2xl bg-white rounded-xl border border-surface-100 p-5 sm:p-6 mb-10">
    <div class="flex justify-between items-start mb-5 pb-4 border-b border-surface-100">
      <div>
        <h3 class="text-sm font-semibold text-surface-900 mb-0.5">Order <span id="display-id" class="text-brand-600 font-mono"></span></h3>
        <p class="text-xs text-surface-400" id="track-placed-at"></p>
      </div>
      <span class="text-[10px] font-semibold px-2 py-1 rounded border uppercase tracking-wide" id="track-status-badge"></span>
    </div>

    <div class="relative ml-3">
      <div class="absolute left-[7px] top-1 bottom-4 w-px bg-surface-200"></div>
      <div class="absolute left-[7px] top-1 w-px bg-brand-500" id="track-progress-bar" style="height:0"></div>

      <div class="relative flex gap-4 mb-6">
        <div class="relative z-10 w-4 h-4 rounded-full bg-brand-500 flex items-center justify-center ring-2 ring-white shrink-0">
          <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div>
          <p class="text-sm font-medium text-surface-900">Order Placed</p>
          <p class="text-xs text-surface-400" id="track-date-placed"></p>
        </div>
      </div>

      <div class="relative flex gap-4 mb-6" id="track-step-confirmed">
        <div class="relative z-10 w-4 h-4 rounded-full ring-2 ring-white shrink-0 flex items-center justify-center" id="track-dot-confirmed"></div>
        <div>
          <p class="text-sm font-medium" id="track-label-confirmed">Confirmed</p>
          <p class="text-xs text-surface-400" id="track-desc-confirmed">Preparing your order</p>
        </div>
      </div>

      <div class="relative flex gap-4 mb-6" id="track-step-ofd">
        <div class="relative z-10 w-4 h-4 rounded-full ring-2 ring-white shrink-0 flex items-center justify-center" id="track-dot-ofd"></div>
        <div>
          <p class="text-sm font-medium" id="track-label-ofd">Out for Delivery</p>
          <p class="text-xs text-surface-400" id="track-desc-ofd">Pending</p>
        </div>
      </div>

      <div class="relative flex gap-4">
        <div class="relative z-10 w-4 h-4 rounded-full ring-2 ring-white shrink-0" id="track-dot-delivered"></div>
        <div>
          <p class="text-sm font-medium" id="track-label-delivered">Delivered</p>
          <p class="text-xs text-surface-400" id="track-desc-delivered">Pending</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Empty State -->
  <div id="empty-state" class="hidden text-center py-8 mb-10">
    <svg class="mx-auto mb-2 text-surface-200" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <p class="text-surface-400 text-sm">Order not found. Please check the ID and try again.</p>
  </div>

  <!-- Order History -->
  <div class="border-t border-surface-100 pt-8">
    <div class="flex items-end justify-between gap-4 mb-6">
      <div>
        <h2 class="text-lg font-display font-bold text-surface-900 mb-0.5">Order History</h2>
        <p class="text-xs text-surface-400">View your past orders, items, and delivery status.</p>
      </div>
    </div>

    <!-- Filters -->
    <form method="GET" action="{{ route('orders') }}" class="bg-white border border-surface-100 rounded-xl p-4 mb-5">
      <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
        <div>
          <label class="block text-[10px] font-medium text-surface-400 mb-1">Status</label>
          <select name="status" class="w-full border border-surface-200 rounded-lg px-3 py-2 text-xs text-surface-600 outline-none focus:border-brand-500 transition-colors">
            <option value="">All</option>
            @foreach (['pending','confirmed','out_for_delivery','delivered','cancelled'] as $st)
              <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>
                {{ ucfirst(str_replace('_',' ', $st)) }}
              </option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-[10px] font-medium text-surface-400 mb-1">From</label>
          <input type="date" name="from" value="{{ request('from') }}" class="w-full border border-surface-200 rounded-lg px-3 py-2 text-xs text-surface-600 outline-none focus:border-brand-500 transition-colors">
        </div>
        <div>
          <label class="block text-[10px] font-medium text-surface-400 mb-1">To</label>
          <input type="date" name="to" value="{{ request('to') }}" class="w-full border border-surface-200 rounded-lg px-3 py-2 text-xs text-surface-600 outline-none focus:border-brand-500 transition-colors">
        </div>
        <div class="flex gap-2">
          <button class="flex-1 bg-surface-900 hover:bg-surface-800 text-white font-medium py-2 rounded-lg text-xs transition-colors">Filter</button>
          <a href="{{ route('orders') }}" class="px-3 py-2 rounded-lg border border-surface-200 text-xs font-medium text-surface-500 hover:bg-surface-50 transition-colors">Reset</a>
        </div>
      </div>
      @if ($errors->any())
        <div class="mt-3 rounded-lg border border-red-100 bg-red-50 text-red-600 px-3 py-2 text-xs">
          <ul class="list-disc pl-4 space-y-0.5">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif
    </form>

    <!-- Orders List -->
    <div class="space-y-4">
      @forelse ($orders as $order)
        @php $order->loadMissing('feedback'); @endphp
        @php
          $status = $order->status ?? 'pending';
          $badge =
            $status === 'pending' ? 'bg-amber-50 text-amber-700 border-amber-100' :
            ($status === 'confirmed' ? 'bg-blue-50 text-blue-700 border-blue-100' :
            ($status === 'out_for_delivery' ? 'bg-brand-50 text-brand-700 border-brand-100' :
            ($status === 'delivered' ? 'bg-surface-900 text-white border-surface-900' :
            'bg-red-50 text-red-600 border-red-100')));
        @endphp

        <div class="bg-white border border-surface-100 rounded-xl overflow-hidden">
          <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 border-b border-surface-100">
            <div>
              <div class="flex flex-wrap items-center gap-2">
                <h3 class="text-sm font-semibold text-surface-900">
                  Order <span class="font-mono text-brand-600">{{ $order->order_number }}</span>
                </h3>
                <span class="text-[10px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded border {{ $badge }}">
                  {{ ucfirst(str_replace('_',' ', $status)) }}
                </span>
              </div>
              <p class="text-xs text-surface-400 mt-0.5">
                Placed {{ optional($order->created_at)->format('M d, Y h:i A') }}
              </p>
            </div>
            <div class="sm:text-right flex flex-row sm:flex-col items-center sm:items-end justify-between sm:justify-start gap-2">
              <div>
                <p class="text-[10px] text-surface-400 uppercase tracking-wider">Total</p>
                <p class="text-lg font-display font-bold text-surface-900">&#8369;{{ number_format((float) $order->total_amount, 2) }}</p>
              </div>
              <div class="flex items-center flex-wrap gap-1.5 justify-end">
                @if ($status === 'delivered' && !$order->feedback)
                  <button onclick="openFeedbackModal({{ $order->id }}, '{{ $order->order_number }}')"
                    class="inline-flex items-center gap-1 text-[11px] font-medium text-amber-600 hover:text-amber-800 border border-amber-200 hover:border-amber-400 bg-amber-50 hover:bg-amber-100 px-2.5 py-1.5 rounded-lg transition-colors touch-manipulation min-h-[34px]">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    Leave Feedback
                  </button>
                @elseif ($status === 'delivered' && $order->feedback)
                  <span class="inline-flex items-center gap-1 text-[11px] font-medium text-green-600 border border-green-200 bg-green-50 px-2.5 py-1.5 rounded-lg min-h-[34px]">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    Reviewed
                  </span>
                @endif
                <a href="{{ route('orders.receipt', $order) }}" target="_blank"
                   class="inline-flex items-center gap-1 text-[11px] font-medium text-surface-400 hover:text-surface-700 border border-surface-200 hover:border-surface-300 px-2.5 py-1.5 rounded-lg transition-colors touch-manipulation min-h-[34px]">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
                  </svg>
                  Receipt
                </a>
              </div>
            </div>
          </div>

          <div class="p-4 sm:p-5 grid grid-cols-1 lg:grid-cols-3 gap-5">
            <!-- Items -->
            <div>
              <h4 class="text-xs font-semibold text-surface-900 mb-2">Items</h4>
              @if (is_array($order->items) && count($order->items))
                <ul class="divide-y divide-surface-100 rounded-lg border border-surface-100">
                  @foreach ($order->items as $line)
                    @php
                      $qty = (int) ($line['qty'] ?? $line['quantity'] ?? 1);
                      $price = (float) ($line['price'] ?? 0);
                      $name = (string) ($line['name'] ?? 'Item');
                    @endphp
                    <li class="flex items-center justify-between gap-3 px-3 py-2.5 text-xs">
                      <div class="min-w-0">
                        <p class="font-medium text-surface-900 truncate">{{ $name }}</p>
                        <p class="text-surface-400">Qty: {{ $qty }}</p>
                      </div>
                      <div class="text-right shrink-0">
                        <p class="font-medium text-surface-900">&#8369;{{ number_format($price * $qty, 2) }}</p>
                        <p class="text-surface-400">&#8369;{{ number_format($price, 2) }} each</p>
                      </div>
                    </li>
                  @endforeach
                </ul>
              @else
                <p class="text-xs text-surface-400">No line items stored for this order.</p>
              @endif
            </div>

            <!-- Delivery Info -->
            <div>
              <h4 class="text-xs font-semibold text-surface-900 mb-2">Delivery Info</h4>
              <div class="rounded-lg border border-surface-100 p-3 space-y-2 text-xs">
                @php
                  $dMethod = $order->delivery_method ?? 'delivery';
                  $pMethod = $order->payment_method ?? 'cod';
                @endphp
                <div class="flex items-center gap-2">
                  <span class="text-surface-400">Method:</span>
                  @if($dMethod === 'pickup')
                    <span class="bg-purple-50 text-purple-700 border border-purple-100 px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide">Pick-up</span>
                  @else
                    <span class="bg-blue-50 text-blue-700 border border-blue-100 px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide">Delivery</span>
                  @endif
                </div>
                @if($dMethod === 'delivery' && $order->delivery_address)
                  <div>
                    <p class="text-surface-400 mb-0.5">Address:</p>
                    <p class="text-surface-700 leading-relaxed">
                      {{ $order->delivery_name }}<br>
                      {{ $order->delivery_address }}, {{ $order->delivery_city }}<br>
                      {{ $order->delivery_phone }}
                    </p>
                  </div>
                @elseif($dMethod === 'pickup')
                  <p class="text-surface-400 text-xs">A. Arellano Ave. Mulawin, Orani, Philippines 2112</p>
                @endif
                <div class="flex items-center gap-2 pt-1 border-t border-surface-100">
                  <span class="text-surface-400">Payment:</span>
                  @if($pMethod === 'gcash')
                    <span class="bg-sky-50 text-sky-700 border border-sky-100 px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide">GCash</span>
                  @else
                    <span class="bg-amber-50 text-amber-700 border border-amber-100 px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide">COD</span>
                  @endif
                </div>
              </div>
            </div>

            <!-- Timeline -->
            <div>
              <h4 class="text-xs font-semibold text-surface-900 mb-2">Delivery Timeline</h4>
              @php
                $step = $status === 'pending' ? 1 : ($status === 'confirmed' ? 2 : ($status === 'out_for_delivery' ? 3 : ($status === 'delivered' ? 4 : 0)));
                $steps = [
                  ['label' => 'Order Placed', 'desc' => optional($order->created_at)->format('M d, Y h:i A')],
                  ['label' => 'Confirmed', 'desc' => $status === 'pending' ? 'Pending' : 'Preparing your items'],
                  ['label' => 'Out for Delivery', 'desc' => $status === 'out_for_delivery' ? 'Driver is on the way' : 'Pending'],
                  ['label' => 'Delivered', 'desc' => $status === 'delivered' ? 'Delivered' : 'Pending'],
                ];
              @endphp

              <div class="relative ml-1.5">
                <div class="absolute left-[5px] top-1 bottom-1 w-px bg-surface-200"></div>
                <div class="space-y-4">
                  @foreach ($steps as $i => $s)
                    @php
                      $idx = $i + 1;
                      $done = $step > 0 && $idx < $step;
                      $active = $step === $idx;
                      $circle =
                        $done ? 'bg-brand-600 ring-brand-50' :
                        ($active ? 'bg-brand-600 ring-brand-50' : 'bg-white border-surface-300 ring-white');
                      $text =
                        $done ? 'text-surface-900' :
                        ($active ? 'text-brand-700' : 'text-surface-300');
                    @endphp
                    <div class="relative flex gap-3">
                      <div class="relative z-10 w-3 h-3 mt-0.5 rounded-full ring-2 {{ $circle }} flex items-center justify-center border {{ $done || $active ? 'border-brand-600' : 'border-surface-300' }}">
                        @if ($done)
                          <svg width="7" height="7" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        @elseif ($active)
                          <div class="w-1 h-1 bg-white rounded-full"></div>
                        @endif
                      </div>
                      <div class="min-w-0">
                        <p class="text-xs font-medium {{ $text }}">{{ $s['label'] }}</p>
                        <p class="text-[10px] {{ $active ? 'text-surface-600' : 'text-surface-400' }}">{{ $s['desc'] }}</p>
                        @if ($status === 'cancelled' && $i === 0)
                          <p class="text-[10px] text-red-600 font-semibold mt-0.5">Cancelled</p>
                        @endif
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
            </div>
          </div>
        </div>
      @empty
        <div class="bg-white border border-surface-100 rounded-xl p-8 text-center">
          <svg class="mx-auto mb-2 text-surface-200" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
          <p class="text-surface-400 text-sm">No orders found for your filters.</p>
        </div>
      @endforelse
    </div>

    @if (method_exists($orders, 'links'))
      <div class="mt-6">
        {{ $orders->links() }}
      </div>
    @endif
  </div>
</main>

{{-- ── Feedback Modal ─────────────────────────────────────────────── --}}
<div id="feedback-modal" class="fixed inset-0 z-50 hidden flex items-end sm:items-center justify-center sm:p-4">
  {{-- backdrop --}}
  <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeFeedbackModal()"></div>

  <div class="relative bg-white w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl shadow-2xl animate-modal-up sm:animate-fade-in">
    {{-- drag handle - mobile only --}}
    <div class="flex justify-center pt-3 pb-1 sm:hidden">
      <div class="w-10 h-1 bg-surface-200 rounded-full"></div>
    </div>

    <div class="px-4 sm:px-6 pt-2 sm:pt-5 pb-5 sm:pb-6">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="text-base font-semibold text-surface-900">Rate Your Order</h3>
          <p class="text-xs text-surface-400 mt-0.5">Order <span id="modal-order-number" class="font-mono text-brand-600 font-semibold"></span></p>
        </div>
        <button onclick="closeFeedbackModal()" class="p-1.5 text-surface-300 hover:text-surface-600 transition-colors rounded-lg">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>

      <form method="POST" action="{{ route('feedback.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="order_id" id="modal-order-id">

        {{-- Star rating --}}
        <div>
          <label class="block text-xs font-medium text-surface-600 mb-3">Rating <span class="text-red-500">*</span></label>
          <div class="flex justify-between sm:justify-start sm:gap-3" id="modal-star-group">
            @for ($i = 1; $i <= 5; $i++)
              <button type="button" data-value="{{ $i }}"
                class="modal-star text-5xl sm:text-4xl text-surface-200 hover:text-amber-400 active:scale-90 transition-all focus:outline-none leading-none touch-manipulation"
                aria-label="{{ $i }} star">&#9733;</button>
            @endfor
          </div>
          <input type="hidden" name="rating" id="modal-rating-input" value="">
        </div>

        {{-- Comment --}}
        <div>
          <label class="block text-xs font-medium text-surface-600 mb-1">Comment <span class="text-surface-300">(optional)</span></label>
          <textarea name="comment" rows="3"
            class="w-full border border-surface-200 rounded-xl px-3 py-2.5 text-sm text-surface-700 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-100 resize-none transition-all"
            placeholder="Tell us about your experience…"></textarea>
        </div>

        <div class="flex flex-col-reverse sm:flex-row gap-2 sm:gap-3 pt-1">
          <button type="button" onclick="closeFeedbackModal()"
            class="py-3 sm:py-2.5 sm:px-4 rounded-xl border border-surface-200 text-sm font-medium text-surface-500 hover:bg-surface-50 transition-colors touch-manipulation">
            Cancel
          </button>
          <button type="submit" id="modal-submit-btn"
            class="flex-1 bg-surface-900 hover:bg-surface-800 active:bg-surface-700 text-white text-sm font-medium py-3 sm:py-2.5 rounded-xl transition-colors touch-manipulation">
            Submit Feedback
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
@keyframes fade-in   { from { opacity:0; transform:scale(.95); }    to { opacity:1; transform:scale(1); } }
@keyframes modal-up  { from { transform:translateY(100%); opacity:0; } to { transform:translateY(0); opacity:1; } }
.animate-fade-in  { animation: fade-in  .18s ease-out; }
.animate-modal-up { animation: modal-up .25s cubic-bezier(.32,1,.5,1); }
</style>

@include('partials.mobile-bottom-customer')
@endsection

@section('scripts')
<script>
  const CSRF = document.querySelector('meta[name="csrf-token"]')?.content
            || '{{ csrf_token() }}';

  // ── Status helpers ─────────────────────────────────────────────────────
  const STATUS_BADGE = {
    pending:          'bg-amber-50 text-amber-700 border-amber-100',
    confirmed:        'bg-blue-50 text-blue-700 border-blue-100',
    out_for_delivery: 'bg-brand-50 text-brand-700 border-brand-100',
    delivered:        'bg-surface-900 text-white border-surface-900',
    cancelled:        'bg-red-50 text-red-600 border-red-100',
  };

  // step index: pending=1, confirmed=2, out_for_delivery=3, delivered=4
  function stepOf(status) {
    return { pending:1, confirmed:2, out_for_delivery:3, delivered:4 }[status] ?? 0;
  }

  function applyDot(dotEl, labelEl, descEl, done, active, label, desc) {
    dotEl.className = 'relative z-10 w-4 h-4 rounded-full ring-2 ring-white shrink-0 flex items-center justify-center border ';
    if (done) {
      dotEl.className += 'bg-brand-500 border-brand-500';
      dotEl.innerHTML = '<svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>';
      labelEl.className = 'text-sm font-medium text-surface-900';
    } else if (active) {
      dotEl.className += 'bg-brand-500 border-brand-500';
      dotEl.innerHTML = '<div class="w-1.5 h-1.5 bg-white rounded-full"></div>';
      labelEl.className = 'text-sm font-medium text-brand-600';
    } else {
      dotEl.className += 'bg-white border-surface-300';
      dotEl.innerHTML = '';
      labelEl.className = 'text-sm font-medium text-surface-300';
    }
    if (label) labelEl.textContent = label;
    if (desc)  { descEl.textContent = desc; descEl.className = (done || active) ? 'text-xs text-surface-600' : 'text-xs text-surface-300'; }
  }

  async function trackOrder() {
    const val    = document.getElementById('order-id').value.trim().toUpperCase();
    const result = document.getElementById('tracking-result');
    const empty  = document.getElementById('empty-state');

    if (!val) { result.classList.add('hidden'); empty.classList.add('hidden'); return; }

    try {
      const res  = await fetch('{{ route('orders.track') }}', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body:    JSON.stringify({ order_number: val }),
      });
      const data = await res.json();

      if (!data.found) {
        result.classList.add('hidden');
        empty.classList.remove('hidden');
        return;
      }

      // ── Populate header ────────────────────────────
      document.getElementById('display-id').textContent    = data.order_number;
      document.getElementById('track-placed-at').textContent = 'Placed on ' + (data.created_at || '');

      const badgeCls = STATUS_BADGE[data.status] ?? 'bg-surface-100 text-surface-600 border-surface-200';
      const badge    = document.getElementById('track-status-badge');
      badge.className = 'text-[10px] font-semibold px-2 py-1 rounded border uppercase tracking-wide ' + badgeCls;
      badge.textContent = data.status.replace(/_/g,' ');

      // ── Timeline dots ──────────────────────────────
      const step = stepOf(data.status);
      const placed = document.getElementById('track-date-placed');
      placed.textContent = data.created_at || '';

      applyDot(
        document.getElementById('track-dot-confirmed'),
        document.getElementById('track-label-confirmed'),
        document.getElementById('track-desc-confirmed'),
        step > 2, step === 2, 'Confirmed', step >= 2 ? 'Preparing your items' : 'Pending'
      );
      applyDot(
        document.getElementById('track-dot-ofd'),
        document.getElementById('track-label-ofd'),
        document.getElementById('track-desc-ofd'),
        step > 3, step === 3, 'Out for Delivery', step >= 3 ? 'Driver is on the way' : 'Pending'
      );
      applyDot(
        document.getElementById('track-dot-delivered'),
        document.getElementById('track-label-delivered'),
        document.getElementById('track-desc-delivered'),
        step >= 4, false, 'Delivered', step >= 4 ? 'Order delivered' : 'Pending'
      );

      // Progress bar height (rough %)
      const pct = { 0:'0%', 1:'8%', 2:'38%', 3:'65%', 4:'100%' }[step] ?? '0%';
      document.getElementById('track-progress-bar').style.height = pct;

      empty.classList.add('hidden');
      result.classList.remove('hidden');

    } catch (err) {
      console.error('Track error', err);
      result.classList.add('hidden');
      empty.classList.remove('hidden');
    }
  }

  // allow Enter key in the input
  document.getElementById('order-id')?.addEventListener('keydown', e => { if (e.key === 'Enter') trackOrder(); });

  // ── Feedback Modal ──────────────────────────────────────────────────────
  const feedbackModal   = document.getElementById('feedback-modal');
  const modalOrderId    = document.getElementById('modal-order-id');
  const modalOrderNum   = document.getElementById('modal-order-number');
  const modalRatingInput = document.getElementById('modal-rating-input');
  const modalStars      = document.querySelectorAll('.modal-star');
  let modalSelected = 0;

  function paintModal(upTo) {
    modalStars.forEach((s, i) => {
      s.classList.toggle('text-amber-400', i < upTo);
      s.classList.toggle('text-surface-200', i >= upTo);
    });
  }

  modalStars.forEach((btn, idx) => {
    btn.addEventListener('mouseenter', () => paintModal(idx + 1));
    btn.addEventListener('mouseleave', () => paintModal(modalSelected));
    btn.addEventListener('click', () => {
      modalSelected = idx + 1;
      modalRatingInput.value = modalSelected;
      paintModal(modalSelected);
    });
  });

  function openFeedbackModal(orderId, orderNum) {
    modalOrderId.value   = orderId;
    modalOrderNum.textContent = orderNum;
    modalSelected = 0;
    modalRatingInput.value = '';
    paintModal(0);
    feedbackModal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
  }

  function closeFeedbackModal() {
    feedbackModal.classList.add('hidden');
    document.body.style.overflow = '';
  }

  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeFeedbackModal(); });
</script>
@endsection
