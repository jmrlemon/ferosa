@php
  $r = request()->route()?->getName();
  $active = fn (string $name) => $r === $name ? 'text-brand-700 font-semibold' : 'text-surface-400';
  $ordersActive = in_array($r, ['orders', 'orders.confirmation'], true) ? 'text-brand-700 font-semibold' : 'text-surface-400';
  $shopActive = in_array($r, ['shop', 'checkout'], true) ? 'text-brand-700 font-semibold' : 'text-surface-400';
@endphp
<nav class="lg:hidden fixed bottom-0 inset-x-0 z-50 border-t border-surface-100 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/80 safe-area-pb" aria-label="Primary">
  <div class="grid grid-cols-7 max-w-lg mx-auto text-[10px] text-center py-1.5">
    <a href="{{ route('home') }}" class="flex flex-col items-center gap-0.5 py-1 {{ $active('home') }}">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
      Home
    </a>
    <a href="{{ route('shop') }}" class="flex flex-col items-center gap-0.5 py-1 {{ $shopActive }}">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
      Shop
    </a>
    <a href="{{ route('orders') }}" class="flex flex-col items-center gap-0.5 py-1 {{ $ordersActive }}">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      Orders
    </a>
    <a href="{{ route('schedule') }}" class="flex flex-col items-center gap-0.5 py-1 {{ $active('schedule') }}">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      Book
    </a>
    <a href="{{ route('account') }}" class="flex flex-col items-center gap-0.5 py-1 {{ $active('account') }}">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
      Account
    </a>
    <a href="{{ route('estimator') }}" class="flex flex-col items-center gap-0.5 py-1 {{ $active('estimator') }}">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m-6 4h6m-6 4h2M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z"/></svg>
      Estimate
    </a>
    <a href="{{ route('feedback') }}" class="flex flex-col items-center gap-0.5 py-1 {{ $active('feedback') }}">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
      Feedback
    </a>
  </div>
</nav>
