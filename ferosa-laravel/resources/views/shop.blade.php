@extends('layouts.customer')

@section('styles')
<style>
  .product-card { transition: all 0.15s ease; }
  .product-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.06); }
  /* In-app: floating cart should sit above native nav bar (~80px) not web nav (~80px) */
  body.in-app #floating-cart { bottom: 90px; }
</style>
@endsection

@section('content')
<main class="max-w-5xl mx-auto px-4 sm:px-6 py-10">

  {{-- Header --}}
  <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
    <div>
      <h1 class="text-2xl font-display font-bold text-surface-900 mb-1">Shop</h1>
      <p class="text-surface-400 text-sm">Premium plants, tools, and materials curated by Ferosa.</p>
    </div>
    <span class="text-xs text-surface-400" id="product-count-label">
      {{ $products->count() }} product{{ $products->count() !== 1 ? 's' : '' }}
    </span>
  </div>

  {{-- Filters bar --}}
  <form method="GET" action="{{ route('shop') }}" id="filter-form"
        class="bg-white border border-surface-100 rounded-xl p-4 mb-6 grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
    {{-- Search --}}
    <div class="sm:col-span-2 relative">
      <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-surface-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
      </svg>
      <input type="search" name="q" value="{{ $q }}" placeholder="Search products…"
             class="w-full pl-8 pr-3 py-2 border border-surface-200 rounded-lg text-xs text-surface-700 outline-none focus:border-brand-500 transition-colors">
    </div>
    {{-- Category --}}
    <select name="category" onchange="this.form.submit()"
            class="border border-surface-200 rounded-lg px-3 py-2 text-xs text-surface-600 outline-none focus:border-brand-500 transition-colors">
      <option value="all" {{ $category === 'all' ? 'selected' : '' }}>All categories</option>
      @foreach ($categories as $cat)
        <option value="{{ $cat }}" {{ $category === $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
      @endforeach
    </select>
    {{-- Sort --}}
    <select name="sort" onchange="this.form.submit()"
            class="border border-surface-200 rounded-lg px-3 py-2 text-xs text-surface-600 outline-none focus:border-brand-500 transition-colors">
      <option value="name_asc"  {{ $sort === 'name_asc'  ? 'selected' : '' }}>Name A–Z</option>
      <option value="price_asc" {{ $sort === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
      <option value="price_desc"{{ $sort === 'price_desc'? 'selected' : '' }}>Price: High to Low</option>
      <option value="newest"    {{ $sort === 'newest'    ? 'selected' : '' }}>Newest</option>
    </select>
    {{-- Price filter + submit --}}
    <div class="sm:col-span-2 flex items-center gap-2">
      <label class="text-[10px] text-surface-400 whitespace-nowrap">Max price (₱)</label>
      <input type="number" name="max_price" value="{{ $maxPrice ?? '' }}" min="0" step="100" placeholder="Any"
             class="w-28 border border-surface-200 rounded-lg px-3 py-2 text-xs outline-none focus:border-brand-500 transition-colors">
      <button type="submit" class="bg-surface-900 text-white rounded-lg px-4 py-2 text-xs font-medium hover:bg-surface-800 transition-colors">Filter</button>
      <a href="{{ route('shop') }}" class="text-xs text-surface-400 hover:text-surface-700 transition-colors">Reset</a>
    </div>
  </form>

  {{-- Products grid --}}
  @if ($products->isEmpty())
    <div class="text-center py-16">
      <svg class="mx-auto mb-3 text-surface-200" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
      </svg>
      <p class="text-surface-400 text-sm mb-2">No products match your filters.</p>
      <a href="{{ route('shop') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">Reset filters</a>
    </div>
  @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      @foreach ($products as $product)
        @php
          $catBg = match(strtolower($product->category)) {
            'plants'    => 'bg-green-50',
            'tools'     => 'bg-surface-100',
            'materials' => 'bg-amber-50',
            default     => 'bg-surface-50',
          };
          $inStock = $product->stock_qty > 0;
          $lowStock = $product->stock_qty > 0 && $product->stock_qty <= 5;
        @endphp
        <div class="product-card bg-white border border-surface-100 rounded-xl overflow-hidden flex flex-col group"
             data-id="{{ $product->id }}"
             data-name="{{ $product->name }}"
             data-price="{{ (float) $product->price }}"
             data-category="{{ $product->category }}"
             data-stock="{{ $product->stock_qty }}">

          {{-- Image / placeholder --}}
          <div class="h-44 {{ $catBg }} relative flex items-center justify-center overflow-hidden">
            @if ($product->image_url)
              <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                   class="w-full h-full object-cover">
            @else
              <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.2">
                <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/>
                <polyline points="21 15 16 10 5 21"/>
              </svg>
            @endif

            {{-- Category badge --}}
            <span class="absolute top-3 left-3 bg-white/90 px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider text-surface-500">
              {{ $product->category }}
            </span>

            {{-- Stock badge --}}
            @if (! $inStock)
              <span class="absolute top-3 right-3 bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded">
                Out of Stock
              </span>
            @elseif ($lowStock)
              <span class="absolute top-3 right-3 bg-amber-400 text-white text-[10px] font-bold px-2 py-0.5 rounded">
                Only {{ $product->stock_qty }} left
              </span>
            @endif

            {{-- Add hover button --}}
            @if ($inStock)
            <button onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ (float) $product->price }})"
                    class="absolute bottom-3 right-3 bg-white text-surface-700 text-xs font-medium px-3 py-1.5 rounded-lg shadow-sm
                           opacity-0 group-hover:opacity-100 transition-opacity hover:bg-brand-50 hover:text-brand-700 flex items-center gap-1.5">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
              Add
            </button>
            @endif
          </div>

          {{-- Info --}}
          <div class="p-4 flex-1 flex flex-col">
            <h3 class="font-semibold text-surface-900 text-sm leading-snug mb-1 truncate">{{ $product->name }}</h3>
            @if ($product->description)
              <p class="text-[11px] text-surface-400 leading-snug mb-2 line-clamp-2">{{ $product->description }}</p>
            @endif
            <div class="mt-auto pt-3 flex items-center justify-between">
              <p class="text-lg font-display font-bold text-surface-900">&#8369;{{ number_format((float) $product->price, 2) }}</p>
              @if ($inStock)
                <button onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ (float) $product->price }})"
                        class="w-8 h-8 bg-surface-50 hover:bg-brand-600 hover:text-white text-surface-400 rounded-lg flex items-center justify-center transition-colors">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                </button>
              @else
                <span class="text-[10px] text-red-400 font-medium">Unavailable</span>
              @endif
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif
</main>

{{-- Floating cart button --}}
<a href="{{ route('checkout') }}" id="floating-cart"
   class="fixed bottom-20 lg:bottom-6 right-5 z-50 bg-surface-900 text-white w-12 h-12 rounded-full shadow-lg flex items-center justify-center hover:bg-surface-800 transition-colors hidden">
  <div class="relative">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
      <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
      <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
    </svg>
    <span id="floating-cart-count"
          class="absolute -top-2 -right-2 bg-brand-600 text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center">0</span>
  </div>
</a>

<div id="shop-toast-container" class="fixed top-4 right-4 z-50 flex flex-col gap-2 pointer-events-none"></div>

@include('partials.mobile-bottom-customer')
@endsection

@section('scripts')
<script>
  const cartIcon    = document.getElementById('floating-cart');
  const cartBadge   = document.getElementById('floating-cart-count');
  const toastCont   = document.getElementById('shop-toast-container');

  function getCart() {
    try { return JSON.parse(localStorage.getItem('ferosa_cart')) || []; } catch { return []; }
  }

  function updateCartCount() {
    const cart  = getCart();
    const count = cart.reduce((t, i) => t + i.qty, 0);
    cartBadge.textContent = count;
    cartIcon.classList.toggle('hidden', count === 0);
  }

  window.addToCart = function(id, name, price) {
    let cart = getCart();
    const ex = cart.find(i => i.id === id);
    if (ex) { ex.qty += 1; } else { cart.push({ id, name, price, qty: 1 }); }
    localStorage.setItem('ferosa_cart', JSON.stringify(cart));
    updateCartCount();

    const toast = document.createElement('div');
    toast.className = 'bg-surface-900 text-white text-xs font-medium px-4 py-2.5 rounded-lg shadow-lg flex items-center gap-2 transform translate-x-full transition-transform duration-200';
    toast.innerHTML = `<svg class="w-4 h-4 text-brand-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Added <strong>${name}</strong>`;
    toastCont.appendChild(toast);
    setTimeout(() => toast.classList.remove('translate-x-full'), 10);
    setTimeout(() => { toast.classList.add('translate-x-full'); setTimeout(() => toast.remove(), 200); }, 2200);
  };

  document.addEventListener('DOMContentLoaded', updateCartCount);
</script>
@endsection
