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
@php
  $activeFilters = collect([
    filled($q) ? 'search' : null,
    $category !== 'all' ? 'category' : null,
    filled($maxPrice) ? 'price' : null,
  ])->filter()->count();
@endphp
<main class="customer-page max-w-5xl">

  {{-- Header --}}
  <x-page-head
    kicker="From the nursery"
    title="Plants and garden essentials"
    sub="Everything Ferosa uses on site — healthy stock, honest pricing, and delivery across Orani, Bataan.">
    <span class="badge badge-neutral" id="product-count-label">
      {{ $products->count() }} item{{ $products->count() !== 1 ? 's' : '' }}
    </span>
    <a href="{{ route('checkout') }}" class="btn btn-secondary btn-sm">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
      View cart
    </a>
  </x-page-head>

  {{-- Category quick filters --}}
  <div class="mb-4 flex flex-wrap items-center gap-2 reveal reveal-1">
    <a href="{{ route('shop', array_filter(['q' => $q, 'sort' => $sort, 'max_price' => $maxPrice])) }}"
       class="chip {{ $category === 'all' ? 'chip-active' : '' }}">All</a>
    @foreach ($categories as $cat)
      <a href="{{ route('shop', array_filter(['category' => $cat, 'q' => $q, 'sort' => $sort, 'max_price' => $maxPrice])) }}"
         class="chip {{ $category === $cat ? 'chip-active' : '' }}">{{ ucfirst($cat) }}</a>
    @endforeach
  </div>

  {{-- Filters bar --}}
  <form method="GET" action="{{ route('shop') }}" id="filter-form"
        class="toolbar mb-6 reveal reveal-1">
    <input type="hidden" name="category" value="{{ $category }}">
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-12 sm:items-end">
      {{-- Search --}}
      <div class="sm:col-span-5">
        <label for="shop-search" class="field-label">Search</label>
        <div class="field-icon">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
          </svg>
          <input type="search" id="shop-search" name="q" value="{{ $q }}" placeholder="Try “fern”, “soil”, “shears”…" class="field">
        </div>
      </div>
      {{-- Sort --}}
      <div class="sm:col-span-3">
        <label for="shop-sort" class="field-label">Sort by</label>
        <select id="shop-sort" name="sort" onchange="this.form.submit()" class="field">
          <option value="name_asc"  {{ $sort === 'name_asc'  ? 'selected' : '' }}>Name A-Z</option>
          <option value="price_asc" {{ $sort === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
          <option value="price_desc"{{ $sort === 'price_desc'? 'selected' : '' }}>Price: High to Low</option>
          <option value="newest"    {{ $sort === 'newest'    ? 'selected' : '' }}>Newest</option>
        </select>
      </div>
      {{-- Max price --}}
      <div class="sm:col-span-2">
        <label for="shop-max-price" class="field-label">Max price</label>
        <input type="number" id="shop-max-price" name="max_price" value="{{ $maxPrice ?? '' }}" min="0" step="100" placeholder="Any" class="field">
      </div>
      {{-- Actions --}}
      <div class="flex items-center gap-2 sm:col-span-2">
        <button type="submit" data-loading-label="Filtering..." class="btn btn-primary btn-sm flex-1">Apply</button>
        @if($activeFilters)
          <a href="{{ route('shop') }}" class="btn btn-ghost btn-sm" title="Clear all filters">Reset</a>
        @endif
      </div>
    </div>
  </form>

  {{-- Products grid --}}
  @if ($products->isEmpty())
    <div class="customer-empty reveal reveal-2">
      <div class="customer-empty-icon">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
      </div>
      <h2 class="text-base font-bold text-surface-900 mb-1">No products found</h2>
      <p class="text-surface-500 text-sm mb-5">Try a different keyword, category, or price range.</p>
      <a href="{{ route('shop') }}" class="btn btn-primary btn-sm">Reset filters</a>
    </div>
  @else
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5 reveal reveal-2">
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
              {{-- Product images are remote URLs, so the whole grid used to be
                   fetched at once on page load. Lazy loading keeps the shop
                   usable on a phone: only the cards actually on screen fetch. --}}
              <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                   loading="lazy" decoding="async"
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
              <span class="absolute bottom-3 left-3 inline-flex items-center gap-1 bg-brand-800/90 backdrop-blur px-2.5 py-1 rounded-full text-[10px] font-bold text-white shadow-sm">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7V4h3M17 4h3v3M20 17v3h-3M7 20H4v-3M9 9h6v6H9z"/></svg>
                AR preview
              </span>
            @endif

            {{-- Stock badge --}}
            @if (! $inStock)
              <span class="absolute top-3 right-3 rounded-full bg-white/92 backdrop-blur px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-red-600 shadow-sm">
                Out of stock
              </span>
            @elseif ($lowStock)
              <span class="absolute top-3 right-3 rounded-full bg-white/92 backdrop-blur px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-amber-700 shadow-sm">
                {{ $product->stock_qty }} left
              </span>
            @endif

          </a>

          {{-- Info --}}
          <div class="p-5 flex-1 flex flex-col">
            <h3 class="font-bold text-surface-900 text-base leading-snug mb-1.5"><a href="{{ route('products.show', $product) }}" class="hover:text-brand-700 transition-colors">{{ $product->name }}</a></h3>
            @if ($product->description)
              <p class="text-[13px] text-surface-500 leading-5 mb-3 line-clamp-2">{{ $product->description }}</p>
            @endif
            <a href="{{ route('products.show', $product) }}" class="mb-3 inline-flex items-center gap-1 text-[11px] font-bold text-brand-700 hover:text-brand-900 transition-colors">
              View details &amp; guidance
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
            </a>
            <div class="mt-auto pt-4 flex items-end justify-between gap-3 border-t border-surface-100">
              <div>
                <p class="font-display text-xl font-bold leading-none text-surface-900">&#8369;{{ number_format((float) $product->price, 2) }}</p>
                @if($inStock)
                  <p class="mt-1.5 inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wide {{ $lowStock ? 'text-amber-700' : 'text-brand-600' }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $lowStock ? 'bg-amber-500' : 'bg-brand-500' }}"></span>
                    {{ $lowStock ? 'Low stock' : 'In stock' }}
                  </p>
                @endif
              </div>
              @if ($inStock)
                <button onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ (float) $product->price }})"
                        aria-label="Add {{ $product->name }} to cart"
                        class="btn btn-primary btn-sm">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                  Add
                </button>
              @else
                <span class="badge badge-danger">Unavailable</span>
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
    toast.className = 'text-white text-xs font-bold px-4 py-3 rounded-xl shadow-lg flex items-center gap-2 transform translate-x-full transition-transform duration-200 '
      + (success ? 'bg-brand-700' : 'bg-red-600');
    toast.textContent = message;
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
