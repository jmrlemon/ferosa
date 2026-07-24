@extends('layouts.customer')

@section('content')
@php
  $gcashName = $gcashSettings['name'] ?? null;
  $gcashNumber = $gcashSettings['number'] ?? null;
  $gcashQrUrl = $gcashSettings['qr_url'] ?? null;
  $gcashAvailable = filled($gcashNumber) || filled($gcashQrUrl);
  $selectedPaymentMethod = old('payment_method', 'cod');
@endphp
<main class="customer-page max-w-4xl">
  <div class="mb-8">
    <a href="{{ route('shop') }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-surface-400 hover:text-surface-600 mb-4">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
      Back to Shop
    </a>
    <h1 class="text-2xl font-display font-bold text-surface-900 mb-1">Checkout</h1>
    <p class="text-surface-400 text-sm">Review your items and place your order.</p>
  </div>

  @if($errors->any())
    <div class="mb-6 p-3 bg-red-50 border border-red-100 text-red-600 rounded-lg text-sm">
      <ul class="list-disc pl-4 space-y-0.5">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('checkout.store') }}" id="checkout-form" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="checkout_token" value="{{ $checkoutToken }}">
    <input type="hidden" name="cart_data" id="cart-data-input">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Left column: Items + Delivery + Payment -->
      <div class="lg:col-span-2 space-y-6">

        <!-- Cart Items -->
        <div id="cart-items-container" class="customer-card p-5 sm:p-6">
          <ul id="cart-list" class="divide-y divide-surface-100"></ul>
          <div id="empty-cart-msg" class="hidden customer-empty shadow-none border-0">
            <div class="customer-empty-icon">
              <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            </div>
            <h2 class="text-sm font-semibold text-surface-900 mb-1">Your cart is empty</h2>
            <p class="text-surface-400 text-sm mb-4">Add products from the shop before placing an order.</p>
            <a href="{{ route('shop') }}" class="customer-action bg-surface-900 text-white font-medium text-xs px-5 py-2 hover:bg-surface-800">Browse Shop</a>
          </div>
        </div>

        <!-- Delivery Details -->
        <div class="customer-card p-5 sm:p-6">
          <h2 class="text-sm font-semibold text-surface-900 mb-4">Delivery Details</h2>

          <!-- Toggle Tabs -->
          <div class="flex gap-2 mb-5">
            <button type="button" id="tab-delivery"
              onclick="setDeliveryMethod('delivery')"
              class="flex-1 py-2 text-xs font-medium rounded-lg border transition-colors border-surface-900 bg-surface-900 text-white">
              Delivery
            </button>
            <button type="button" id="tab-pickup"
              onclick="setDeliveryMethod('pickup')"
              class="flex-1 py-2 text-xs font-medium rounded-lg border transition-colors border-surface-200 bg-white text-surface-600 hover:bg-surface-50">
              Pick-up
            </button>
          </div>

          <input type="hidden" name="delivery_method" id="delivery_method_input" value="delivery">

          <!-- Delivery Fields -->
          <div id="delivery-fields" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-medium text-surface-600 mb-1">Full Name <span class="text-red-500">*</span></label>
                <input type="text" name="delivery_name" value="{{ old('delivery_name') }}"
                  placeholder="e.g. Juan Dela Cruz"
                  class="w-full border border-surface-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
              </div>
              <div>
                <label class="block text-xs font-medium text-surface-600 mb-1">Phone Number <span class="text-red-500">*</span></label>
                <input type="text" name="delivery_phone" value="{{ old('delivery_phone') }}"
                  placeholder="e.g. 09XX XXX XXXX"
                  class="w-full border border-surface-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
              </div>
            </div>
            <div>
              <label class="block text-xs font-medium text-surface-600 mb-1">Street Address <span class="text-red-500">*</span></label>
              <input type="text" name="delivery_address" value="{{ old('delivery_address') }}"
                placeholder="House/Unit No., Street, Barangay"
                class="w-full border border-surface-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
            </div>
            <div>
              <label class="block text-xs font-medium text-surface-600 mb-1">City / Municipality <span class="text-red-500">*</span></label>
              <input type="text" name="delivery_city" value="{{ old('delivery_city') }}"
                placeholder="e.g. Quezon City"
                class="w-full border border-surface-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
            </div>
            <div>
              <label class="block text-xs font-medium text-surface-600 mb-1">Delivery Notes <span class="text-surface-400 font-normal">(optional)</span></label>
              <textarea name="delivery_notes" rows="2"
                placeholder="e.g. Leave at the gate, call upon arrival"
                class="w-full border border-surface-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors resize-none">{{ old('delivery_notes') }}</textarea>
            </div>
          </div>

          <!-- Pick-up Info -->
          <div id="pickup-info" class="hidden">
            <div class="bg-brand-50 border border-brand-100 rounded-lg p-4 text-sm text-brand-800">
              <div class="flex gap-3 items-start">
                <svg class="w-5 h-5 text-brand-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                <div>
                  <p class="font-semibold text-brand-900 mb-0.5">Pick up at Ferosa:</p>
                  <p>A. Arellano Ave. Mulawin, Orani,<br>Philippines 2112</p>
                  <p class="text-brand-600 text-xs mt-1">Mon-Sat &bull; 8:00 AM - 5:00 PM</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Payment Method -->
        <div class="customer-card p-5 sm:p-6">
          <h2 class="text-sm font-semibold text-surface-900 mb-4">Payment Method</h2>

          <div class="space-y-3">
            <!-- COD -->
            <label class="flex items-start gap-3 cursor-pointer p-3 rounded-lg border border-surface-200 hover:border-brand-400 transition-colors has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
              <input type="radio" name="payment_method" value="cod" {{ $selectedPaymentMethod !== 'gcash' || ! $gcashAvailable ? 'checked' : '' }}
                onchange="setPaymentMethod('cod')"
                class="mt-0.5 w-4 h-4 text-brand-600 border-surface-300 focus:ring-brand-500">
              <div>
                <p class="text-sm font-medium text-surface-900">Cash on Delivery (COD)</p>
                <p class="text-xs text-surface-400">Pay when your order arrives at your door.</p>
              </div>
            </label>

            <!-- GCash -->
            <label class="flex items-start gap-3 {{ $gcashAvailable ? 'cursor-pointer hover:border-brand-400 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50' : 'cursor-not-allowed opacity-60 bg-surface-50' }} p-3 rounded-lg border border-surface-200 transition-colors">
              <input type="radio" name="payment_method" value="gcash"
                onchange="setPaymentMethod('gcash')"
                {{ $selectedPaymentMethod === 'gcash' && $gcashAvailable ? 'checked' : '' }}
                {{ $gcashAvailable ? '' : 'disabled' }}
                class="mt-0.5 w-4 h-4 text-brand-600 border-surface-300 focus:ring-brand-500">
              <div class="flex-1">
                <p class="text-sm font-medium text-surface-900">GCash</p>
                <p class="text-xs text-surface-400">
                  {{ $gcashAvailable ? 'Scan the QR or send to the listed number, then provide the reference number.' : 'GCash payment is not available right now.' }}
                </p>
              </div>
            </label>
          </div>

          <!-- GCash reference input -->
          <div id="gcash-reference-field" class="hidden mt-4">
            <div class="rounded-xl border border-sky-100 bg-sky-50 p-3 mb-4">
              <div class="grid grid-cols-1 sm:grid-cols-[140px,1fr] gap-3">
                @if($gcashQrUrl)
                  <button type="button" onclick="openGcashQrPreview()" class="block rounded-lg overflow-hidden bg-white border border-sky-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <img src="{{ $gcashQrUrl }}" alt="GCash QR code" class="w-full aspect-square object-contain">
                  </button>
                @else
                  <div class="rounded-lg bg-white border border-dashed border-sky-100 aspect-square flex items-center justify-center text-xs text-surface-400 text-center px-3">
                    QR not uploaded
                  </div>
                @endif
                <div class="text-sm">
                  <p class="text-[10px] uppercase tracking-wider font-semibold text-sky-700 mb-2">Send payment to</p>
                  <div class="space-y-2">
                    <div>
                      <p class="text-xs text-surface-400">Account Name</p>
                      <p class="font-semibold text-surface-900">{{ $gcashName ?: 'Ferosa Landscaping' }}</p>
                    </div>
                    <div>
                      <p class="text-xs text-surface-400">GCash Number</p>
                      <p class="font-mono font-semibold text-surface-900">{{ $gcashNumber ?: 'Not set' }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <label class="block text-xs font-medium text-surface-600 mb-1">GCash Reference Number <span class="text-red-500">*</span></label>
            <input type="text" name="payment_reference" value="{{ old('payment_reference') }}"
              placeholder="e.g. 1234567890"
              class="w-full border border-surface-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
            <p class="text-xs text-surface-400 mt-1">Enter the reference number after sending your payment.</p>
            <label class="mt-4 block text-xs font-medium text-surface-600">Payment Receipt <span class="text-red-500">*</span>
              <input type="file" name="payment_proof" accept="image/jpeg,image/png,image/webp"
                class="mt-2 block w-full rounded-lg border border-surface-200 bg-white text-sm text-surface-600 file:mr-3 file:border-0 file:bg-sky-100 file:px-3 file:py-2.5 file:font-semibold file:text-sky-800">
            </label>
            <p class="mt-1 text-xs text-surface-400">Upload the GCash confirmation screen. JPG, PNG, or WebP; maximum 5 MB.</p>
            <div class="mt-3 rounded-lg border border-amber-100 bg-amber-50 px-3 py-2 text-xs leading-5 text-amber-800">
              Your payment will show as <strong>Pending verification</strong> until an administrator checks the reference and receipt.
            </div>
          </div>
        </div>

      </div>

      <!-- Summary -->
      <div class="lg:col-span-1">
        <div class="customer-card p-5 sm:p-6 sticky top-6">
          <h2 class="text-sm font-semibold text-surface-900 mb-5">Order Summary</h2>

          <div class="flex justify-between items-center mb-3 text-xs text-surface-500">
            <span>Subtotal (<span id="summary-items">0</span> items)</span>
            <span id="summary-subtotal" class="font-medium text-surface-700">&#8369;0.00</span>
          </div>
          <div class="flex justify-between items-center mb-5 text-xs text-surface-500">
            <span>Delivery</span>
            <span class="text-brand-600 font-medium">Free</span>
          </div>

          <div class="border-t border-surface-100 pt-4 mb-6 flex justify-between items-center">
            <span class="text-sm font-semibold text-surface-900">Total</span>
            <span id="summary-total" class="text-xl font-display font-bold text-surface-900">&#8369;0.00</span>
          </div>

          <button type="submit" id="checkout-btn" data-loading-label="Placing order..." class="customer-action w-full bg-surface-900 hover:bg-surface-800 text-white font-medium py-2.5 text-sm">
            Place Order
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
          </button>
        </div>
      </div>
    </div>
  </form>
</main>

@if($gcashQrUrl)
<div id="gcash-qr-modal" class="fixed inset-0 z-[80] hidden items-center justify-center bg-black/70 p-4">
  <button type="button" onclick="closeGcashQrPreview()" class="absolute inset-0 cursor-default" aria-label="Close QR preview"></button>
  <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-4">
    <div class="flex items-center justify-between mb-3">
      <div>
        <p class="text-sm font-semibold text-surface-900">GCash QR Code</p>
        <p class="text-xs text-surface-400">{{ $gcashName ?: 'Ferosa Landscaping' }}</p>
      </div>
      <button type="button" onclick="closeGcashQrPreview()" class="w-9 h-9 rounded-full border border-surface-200 text-surface-500 hover:bg-surface-50 flex items-center justify-center">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <img src="{{ $gcashQrUrl }}" alt="GCash QR code enlarged" class="w-full aspect-square object-contain rounded-xl border border-surface-100 bg-white">
    @if($gcashNumber)
      <p class="text-center text-sm font-mono font-semibold text-surface-900 mt-3">{{ $gcashNumber }}</p>
    @endif
  </div>
</div>
@endif

<script>
  // ── Delivery method toggle ───────────────────────────────────────────────
  function setDeliveryMethod(method) {
    document.getElementById('delivery_method_input').value = method;

    const deliveryFields = document.getElementById('delivery-fields');
    const pickupInfo     = document.getElementById('pickup-info');
    const tabDelivery    = document.getElementById('tab-delivery');
    const tabPickup      = document.getElementById('tab-pickup');

    if (method === 'delivery') {
      deliveryFields.classList.remove('hidden');
      pickupInfo.classList.add('hidden');
      tabDelivery.classList.remove('border-surface-200', 'bg-white', 'text-surface-600', 'hover:bg-surface-50');
      tabDelivery.classList.add('border-surface-900', 'bg-surface-900', 'text-white');
      tabPickup.classList.remove('border-surface-900', 'bg-surface-900', 'text-white');
      tabPickup.classList.add('border-surface-200', 'bg-white', 'text-surface-600', 'hover:bg-surface-50');
    } else {
      deliveryFields.classList.add('hidden');
      pickupInfo.classList.remove('hidden');
      tabPickup.classList.remove('border-surface-200', 'bg-white', 'text-surface-600', 'hover:bg-surface-50');
      tabPickup.classList.add('border-surface-900', 'bg-surface-900', 'text-white');
      tabDelivery.classList.remove('border-surface-900', 'bg-surface-900', 'text-white');
      tabDelivery.classList.add('border-surface-200', 'bg-white', 'text-surface-600', 'hover:bg-surface-50');
    }
  }

  // ── Payment method toggle ────────────────────────────────────────────────
  function setPaymentMethod(method) {
    const gcashField = document.getElementById('gcash-reference-field');
    const referenceInput = document.querySelector('[name="payment_reference"]');
    const proofInput = document.querySelector('[name="payment_proof"]');
    if (method === 'gcash') {
      gcashField.classList.remove('hidden');
      if (referenceInput) referenceInput.required = true;
      if (proofInput) proofInput.required = true;
    } else {
      gcashField.classList.add('hidden');
      if (referenceInput) referenceInput.required = false;
      if (proofInput) proofInput.required = false;
    }
  }

  function openGcashQrPreview() {
    const modal = document.getElementById('gcash-qr-modal');
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
  }

  function closeGcashQrPreview() {
    const modal = document.getElementById('gcash-qr-modal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
  }

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeGcashQrPreview();
  });

  // ── Cart rendering ───────────────────────────────────────────────────────
  function getCart() {
    try { return JSON.parse(localStorage.getItem('ferosa_cart')) || []; } catch { return []; }
  }

  function saveCart(cart) {
    localStorage.setItem('ferosa_cart', JSON.stringify(cart));
    renderCart();
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

  async function updateQty(id, delta) {
    const cart = getCart();
    const item = cart.find(i => i.id === id);
    if (!item) return;
    const quantity = item.qty + delta;

    try {
      const data = await cartRequest(`{{ url('/api/cart/items') }}/${id}`, {
        method: quantity <= 0 ? 'DELETE' : 'PUT',
        body: quantity <= 0 ? undefined : JSON.stringify({ quantity }),
      });
      saveCart(data.items);
      window.dispatchEvent(new CustomEvent('cartUpdated', { detail: data }));
    } catch (error) {
      window.alert(error.message);
    }
  }

  function renderCart() {
    const cart = getCart();
    const list = document.getElementById('cart-list');
    const emptyMsg = document.getElementById('empty-cart-msg');
    const checkoutBtn = document.getElementById('checkout-btn');
    const cartDataInput = document.getElementById('cart-data-input');
    list.innerHTML = '';

    if (cart.length === 0) {
      emptyMsg.classList.remove('hidden');
      checkoutBtn.disabled = true;
      checkoutBtn.classList.add('opacity-40', 'pointer-events-none');
      document.getElementById('summary-items').textContent = '0';
      document.getElementById('summary-subtotal').textContent = '\u20B10.00';
      document.getElementById('summary-total').textContent = '\u20B10.00';
      cartDataInput.value = '';
      return;
    }

    emptyMsg.classList.add('hidden');
    checkoutBtn.disabled = false;
    checkoutBtn.classList.remove('opacity-40', 'pointer-events-none');

    let totalItems = 0, totalPrice = 0;

    cart.forEach(item => {
      totalItems += item.qty;
      const subtotal = item.price * item.qty;
      totalPrice += subtotal;

      const li = document.createElement('li');
      li.className = 'py-4 flex gap-4 items-center';
      li.innerHTML = `
        <div class="w-12 h-12 bg-brand-50 rounded-lg flex items-center justify-center flex-shrink-0">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1f7a1f" stroke-width="1.5"><path d="M12 2a9 9 0 0 1 9 9c0 6-9 13-9 13S3 17 3 11a9 9 0 0 1 9-9z"/><circle cx="12" cy="11" r="3"/></svg>
        </div>
        <div class="flex-1 min-w-0">
          <h3 class="text-sm font-medium text-surface-900 truncate">${item.name}</h3>
          <p class="text-xs text-surface-400">\u20B1${item.price.toLocaleString()} each</p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
          <div class="flex items-center gap-2 border border-surface-200 rounded-lg px-1.5 py-0.5">
            <button type="button" onclick="updateQty(${item.id}, -1)" class="w-5 h-5 flex items-center justify-center text-surface-400 hover:text-surface-700 transition-colors">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </button>
            <span class="text-xs font-medium text-surface-900 min-w-[1rem] text-center">${item.qty}</span>
            <button type="button" onclick="updateQty(${item.id}, 1)" class="w-5 h-5 flex items-center justify-center text-surface-400 hover:text-surface-700 transition-colors">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </button>
          </div>
          <p class="text-sm font-semibold text-surface-900 w-20 text-right">\u20B1${subtotal.toLocaleString()}</p>
        </div>
      `;
      list.appendChild(li);
    });

    document.getElementById('summary-items').textContent = totalItems;
    document.getElementById('summary-subtotal').textContent = '\u20B1' + totalPrice.toLocaleString();
    document.getElementById('summary-total').textContent = '\u20B1' + totalPrice.toLocaleString();
    cartDataInput.value = JSON.stringify(cart);
  }

  async function loadServerCart() {
    const legacy = getCart();
    try {
      const data = legacy.length
        ? await cartRequest('{{ url('/api/cart/sync') }}', { method: 'POST', body: JSON.stringify({ items: legacy }) })
        : await cartRequest('{{ url('/api/cart') }}');
      saveCart(data.items);
      window.dispatchEvent(new CustomEvent('cartUpdated', { detail: data }));
    } catch (error) {
      renderCart();
    }
  }

  setPaymentMethod(@json($selectedPaymentMethod === 'gcash' && $gcashAvailable ? 'gcash' : 'cod'));
  loadServerCart();
</script>
@include('partials.mobile-bottom-customer')
@endsection
