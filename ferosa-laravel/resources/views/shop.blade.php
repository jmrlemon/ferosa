@extends('layouts.customer')

@section('styles')
<style>
  .product-card { transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease; }
  .product-card:hover { transform: translateY(-2px); border-color: #cbded1; box-shadow: 0 14px 36px rgba(18,52,38,.07); }
  .product-card img { transition: transform .45s cubic-bezier(.22,1,.36,1); }
  .product-card:hover img { transform: scale(1.035); }
  /* In-app: floating cart should sit above native nav bar (~80px) not web nav (~80px) */
  body.in-app #floating-cart { bottom: 90px; }
</style>
@endsection

@section('content')
<main class="customer-page max-w-5xl">

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
        class="customer-card p-4 mb-6 grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
    {{-- Search --}}
    <div class="sm:col-span-2 relative">
      <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-surface-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
      </svg>
      <input type="search" name="q" value="{{ $q }}" placeholder="Search products..."
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
      <option value="name_asc"  {{ $sort === 'name_asc'  ? 'selected' : '' }}>Name A-Z</option>
      <option value="price_asc" {{ $sort === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
      <option value="price_desc"{{ $sort === 'price_desc'? 'selected' : '' }}>Price: High to Low</option>
      <option value="newest"    {{ $sort === 'newest'    ? 'selected' : '' }}>Newest</option>
    </select>
    {{-- Price filter + submit --}}
    <div class="sm:col-span-2 flex items-center gap-2">
      <label class="text-[10px] text-surface-400 whitespace-nowrap">Max price (PHP)</label>
      <input type="number" name="max_price" value="{{ $maxPrice ?? '' }}" min="0" step="100" placeholder="Any"
             class="w-28 border border-surface-200 rounded-lg px-3 py-2 text-xs outline-none focus:border-brand-500 transition-colors">
      <button type="submit" data-loading-label="Filtering..." class="customer-action bg-surface-900 text-white px-4 py-2 text-xs font-medium hover:bg-surface-800">Filter</button>
      <a href="{{ route('shop') }}" class="text-xs text-surface-400 hover:text-surface-700 transition-colors">Reset</a>
    </div>
  </form>

  {{-- Products grid --}}
  @if ($products->isEmpty())
    <div class="customer-empty">
      <div class="customer-empty-icon">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
      </div>
      <h2 class="text-sm font-semibold text-surface-900 mb-1">No products found</h2>
      <p class="text-surface-400 text-sm mb-4">Try a different keyword, category, or price range.</p>
      <a href="{{ route('shop') }}" class="customer-action bg-surface-900 text-white text-xs font-medium px-5 py-2 hover:bg-surface-800">Reset filters</a>
    </div>
  @else
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
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
        <article class="product-card bg-white border border-surface-200 rounded-[1.15rem] overflow-hidden flex flex-col group"
             data-id="{{ $product->id }}"
             data-name="{{ $product->name }}"
             data-price="{{ (float) $product->price }}"
             data-category="{{ $product->category }}"
             data-stock="{{ $product->stock_qty }}">

          {{-- Image / placeholder --}}
          <a href="{{ route('products.show', $product) }}" class="aspect-[4/3] {{ $catBg }} relative flex items-center justify-center overflow-hidden" aria-label="View {{ $product->name }} details">
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
            <span class="absolute top-3 left-3 bg-white/90 backdrop-blur px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider text-surface-600 shadow-sm">
              {{ $product->category }}
            </span>

            @if($product->plantModel)
              <span class="absolute bottom-3 left-3 bg-brand-800/90 backdrop-blur px-2.5 py-1 rounded-full text-[10px] font-bold text-white shadow-sm">
                AR preview ready
              </span>
            @endif

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

          </a>

          {{-- Info --}}
          <div class="p-5 flex-1 flex flex-col">
            <h3 class="font-bold text-surface-900 text-base leading-snug mb-1.5"><a href="{{ route('products.show', $product) }}" class="hover:text-brand-700">{{ $product->name }}</a></h3>
            @if ($product->description)
              <p class="text-[13px] text-surface-500 leading-5 mb-3 line-clamp-2">{{ $product->description }}</p>
            @endif
            <a href="{{ route('products.show', $product) }}" class="mb-3 inline-flex text-[11px] font-bold text-brand-700">View details &amp; guidance &rarr;</a>
            <div class="mt-auto pt-4 flex items-center justify-between gap-3 border-t border-surface-100">
              <div>
                <p class="text-lg font-display font-bold text-surface-900">&#8369;{{ number_format((float) $product->price, 2) }}</p>
                @if($inStock && !$lowStock)
                  <p class="mt-0.5 text-[10px] font-semibold text-brand-600">In stock</p>
                @endif
              </div>
              @if ($inStock)
                <button onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ (float) $product->price }})"
                        aria-label="Add {{ $product->name }} to cart"
                        class="customer-action min-h-[42px] bg-brand-700 hover:bg-brand-800 text-white px-3.5 py-2 text-xs font-bold">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                  Add to cart
                </button>
              @else
                <span class="text-[10px] text-red-400 font-medium">Unavailable</span>
              @endif
            </div>
          </div>
        </article>
      @endforeach
    </div>
  @endif
</main>

{{-- Floating cart button --}}
<a href="{{ route('checkout') }}" id="floating-cart"
   aria-label="Open shopping cart"
   class="fixed bottom-20 lg:bottom-6 right-5 z-50 bg-brand-800 text-white w-12 h-12 rounded-2xl shadow-lg flex items-center justify-center hover:bg-brand-900 transition-colors hidden">
  <div class="relative">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
      <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
      <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
    </svg>
    <span id="floating-cart-count"
          class="absolute -top-2 -right-2 bg-brand-600 text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center">0</span>
  </div>
</a>

<div id="shop-toast-container" class="fixed top-4 right-4 z-50 flex flex-col gap-2 pointer-events-none" aria-live="polite" aria-atomic="true"></div>

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

  function cacheCart(cart) {
    localStorage.setItem('ferosa_cart', JSON.stringify(cart));
  }

  function updateCartCount(cartOverride = null) {
    const cart  = cartOverride || getCart();
    const count = cart.reduce((t, i) => t + i.qty, 0);
    cartBadge.textContent = count;
    cartIcon.classList.toggle('hidden', count === 0);
  }

  function showCartToast(message, success = true) {
    const toast = document.createElement('div');
    toast.className = 'bg-surface-900 text-white text-xs font-medium px-4 py-2.5 rounded-lg shadow-lg flex items-center gap-2 transform translate-x-full transition-transform duration-200';
    toast.textContent = message;
    if (!success) toast.classList.add('bg-red-700');
    toastCont.appendChild(toast);
    setTimeout(() => toast.classList.remove('translate-x-full'), 10);
    setTimeout(() => { toast.classList.add('translate-x-full'); setTimeout(() => toast.remove(), 200); }, 2200);
  }

  async function cartRequest(url, options = {}) {
    const response = await fetch(url, {
      ...options,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
        'X-Requested-With': 'XMLHttpRequest',
        ...(options.headers || {}),
      },
    });
    const data = await response.json();
    if (!response.ok) throw new Error(data.message || Object.values(data.errors || {})[0]?.[0] || 'Cart update failed.');
    return data;
  }

  window.addToCart = async function(id, name) {
    try {
      const data = await cartRequest('{{ url('/api/cart/items') }}', {
        method: 'POST',
        body: JSON.stringify({ product_id: id, quantity: 1 }),
      });
      cacheCart(data.items);
      updateCartCount(data.items);
      window.dispatchEvent(new CustomEvent('cartUpdated', { detail: data }));
      showCartToast(`Added ${name}`);
    } catch (error) {
      showCartToast(error.message, false);
    }
  };

  async function loadServerCart() {
    try {
      const legacy = getCart();
      const data = legacy.length
        ? await cartRequest('{{ url('/api/cart/sync') }}', { method: 'POST', body: JSON.stringify({ items: legacy }) })
        : await cartRequest('{{ url('/api/cart') }}');
      cacheCart(data.items);
      updateCartCount(data.items);
      window.dispatchEvent(new CustomEvent('cartUpdated', { detail: data }));
    } catch (error) {
      updateCartCount();
    }
  }

  document.addEventListener('DOMContentLoaded', loadServerCart);
</script>
@endsection
