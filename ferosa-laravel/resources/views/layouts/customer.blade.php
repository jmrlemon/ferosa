<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="ferosa-user-role" content="{{ auth()->user()?->role ?? 'user' }}">
  <title>@yield('title', 'Ferosa Landscaping Services')</title>
  <meta name="description" content="Plan landscaping projects, book services, shop garden essentials, and follow every Ferosa update in one place.">
  <meta property="og:type" content="website">
  <meta property="og:title" content="Ferosa Landscaping">
  <meta property="og:description" content="Plan. Book. Grow beautifully in Orani, Bataan.">
  <meta property="og:image" content="{{ asset('og.png') }}">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="Ferosa Landscaping">
  <meta name="twitter:description" content="Plan. Book. Grow beautifully in Orani, Bataan.">
  <meta name="twitter:image" content="{{ asset('og.png') }}">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <style>
    :root {
      --brand-50: 238 247 241;
      --brand-100: 216 236 223;
      --brand-500: 52 127 87;
      --brand-600: 35 103 70;
      --brand-700: 27 82 57;
      --ink: #183127;
      --paper: #f8f7f3;
    }
    * { font-family: 'DM Sans', system-ui, sans-serif; }
    .font-display { font-family: 'Fraunces', Georgia, serif; }

    :focus-visible {
      outline: 3px solid rgba(52, 127, 87, .28);
      outline-offset: 3px;
    }

    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #e5e5e5; border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #d4d4d4; }

    /* Hide scrollbars in sidebar */
    #customer-sidebar { overflow-x: hidden; }
    #customer-sidebar .overflow-y-auto {
      overflow-x: hidden;
      scrollbar-width: none; /* Firefox */
      -ms-overflow-style: none; /* IE/Edge */
    }
    #customer-sidebar .overflow-y-auto::-webkit-scrollbar {
      display: none; /* Chrome/Safari */
    }

    .sidebar-transition { transition: transform 0.25s ease; }

    .nav-link {
      position: relative;
      transition: background-color 0.18s ease, color 0.18s ease, transform 0.18s ease;
      border-radius: 12px;
      margin: 0 14px;
    }
    .nav-link:hover { background: #f4f5ef; color: #123426; transform: translateX(2px); }
    .nav-link.active { background: #e8f2ec; color: #1b5239; font-weight: 700; }
    .nav-link.active::before {
      content: '';
      position: absolute;
      left: -14px;
      width: 3px;
      height: 22px;
      border-radius: 0 999px 999px 0;
      background: #347f57;
    }

    .customer-page {
      width: 100%;
      max-width: 78rem;
      margin: 0 auto;
      padding: 2.25rem 1rem 7rem;
    }
    .customer-page.max-w-2xl { max-width: 42rem; }
    .customer-page.max-w-3xl { max-width: 48rem; }
    .customer-page.max-w-4xl { max-width: 56rem; }
    .customer-page.max-w-5xl { max-width: 64rem; }
    @media (min-width: 640px) {
      .customer-page { padding-left: 1.5rem; padding-right: 1.5rem; }
    }
    .customer-card {
      background: #fff;
      border: 1px solid #eae7df;
      border-radius: 1.125rem;
      box-shadow: 0 1px 2px rgba(18,52,38,0.03), 0 10px 30px rgba(18,52,38,0.045);
    }
    .customer-empty {
      background: #fff;
      border: 1px solid #eae7df;
      border-radius: 1.125rem;
      padding: 2rem;
      text-align: center;
      box-shadow: 0 1px 2px rgba(18,52,38,0.03), 0 10px 30px rgba(18,52,38,0.045);
    }
    .customer-empty-icon {
      width: 3.5rem;
      height: 3.5rem;
      margin: 0 auto 1rem;
      border-radius: 999px;
      background: #f0faf0;
      color: #1f7a1f;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .customer-action {
      min-height: 42px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: .5rem;
      border-radius: .8rem;
      transition: background .15s ease, color .15s ease, border-color .15s ease, opacity .15s ease;
    }
    .customer-action:hover { transform: translateY(-1px); }

    input, select, textarea { accent-color: #236746; }
    input:not([type='checkbox']):not([type='radio']), select, textarea {
      min-height: 42px;
      background-color: rgba(255,255,255,.92);
    }

    #customer-sidebar {
      background:
        linear-gradient(180deg, rgba(238,247,241,.72), transparent 190px),
        #fff;
      box-shadow: 12px 0 36px rgba(18,52,38,.025);
    }
    .portal-topbar {
      background: rgba(255,255,255,.9);
      backdrop-filter: blur(18px);
      -webkit-backdrop-filter: blur(18px);
    }
    .portal-icon-button {
      width: 40px;
      height: 40px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border: 1px solid #ece9e1;
      border-radius: 12px;
      background: #fff;
      color: #706b61;
      transition: color .18s ease, border-color .18s ease, background .18s ease, transform .18s ease;
    }
    .portal-icon-button:hover {
      color: #1b5239;
      border-color: #cfe3d6;
      background: #f4faf6;
      transform: translateY(-1px);
    }
    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after { scroll-behavior: auto !important; animation-duration: .01ms !important; transition-duration: .01ms !important; }
    }
    button[data-loading="true"] {
      opacity: .72;
      cursor: wait;
    }

    /* When running inside the Android WebView app:
       • Hide the web mobile header & bottom nav — the native app provides its own
       • Make the body and content wrapper scrollable (no fixed-height clipping)
       • Add bottom padding so content clears the native bottom nav bar */
    .app-only { display: none !important; }
    body.in-app .app-only { display: inline-flex !important; }
    body.in-app header.mobile-app-header {
      display: none !important;
    }
    /* Hide the web bottom nav bar — the native app has its own */
    body.in-app .mobile-customer-nav,
    body.in-app nav[aria-label="Primary navigation"] {
      display: none !important;
    }
    /* Hide sidebar & overlay — native app handles all navigation */
    body.in-app #customer-sidebar,
    body.in-app #mobile-overlay {
      display: none !important;
    }
    /* Remove the fixed h-screen / overflow-hidden constraints so the WebView
       can scroll normally */
    body.in-app {
      height: auto !important;
      overflow-y: auto !important;
      overflow-x: hidden !important;
      display: block !important;
    }
    body.in-app #app-content-wrapper {
      height: auto !important;
      overflow: visible !important;
      display: block !important;
    }
    body.in-app main {
      overflow: visible !important;
      height: auto !important;
      padding-bottom: 92px;
      background: #fafafa;
    }
    body.in-app .customer-page {
      padding: 1rem 1rem 7rem !important;
      max-width: 100% !important;
    }
    body.in-app .customer-card,
    body.in-app .customer-empty,
    body.in-app .product-card {
      border-radius: 1rem !important;
      box-shadow: 0 2px 8px -2px rgba(0,0,0,0.06), 0 1px 2px -1px rgba(0,0,0,0.04);
    }
    body.in-app input,
    body.in-app select,
    body.in-app textarea,
    body.in-app button,
    body.in-app a {
      -webkit-tap-highlight-color: transparent;
    }
  </style>

  @yield('styles')
  @include('partials.a11y-focus')

  <script>
    window.addEventListener('pageshow', function (event) {
      if (event.persisted) {
        window.location.reload();
      }
    });
  </script>
</head>
<body class="flex h-screen bg-surface-50 text-surface-900 overflow-hidden font-sans antialiased" id="app-body">

  <a href="#main-content" class="skip-link">Skip to main content</a>

  <!-- Mobile Overlay -->
  <div id="mobile-overlay" class="fixed inset-0 bg-black/20 z-40 hidden lg:hidden backdrop-blur-sm transition-opacity opacity-0" onclick="toggleSidebar()" aria-hidden="true"></div>

  <!-- Sidebar -->
  <aside id="customer-sidebar" aria-label="Navigation menu" class="fixed lg:static inset-y-0 left-0 w-[276px] bg-white border-r border-surface-200 text-surface-600 flex flex-col justify-between flex-shrink-0 z-50 transform -translate-x-full lg:translate-x-0 invisible lg:visible sidebar-transition overflow-x-hidden">
    <div class="overflow-y-auto flex-1">

      <!-- Brand -->
      <div class="px-6 pt-6 pb-5">
        <div class="flex items-center justify-between">
          <a href="{{ route('home') }}" class="flex items-center gap-3">
            <div class="w-10 h-10 bg-brand-700 rounded-xl flex items-center justify-center shadow-soft">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M12 3C12 3 7 6.5 7 12c0 2.76 1.34 5.22 3.4 6.74.38-.48.93-.74 1.6-.74s1.22.26 1.6.74C15.66 17.22 17 14.76 17 12c0-5.5-5-9-5-9z" fill="white"/>
              </svg>
            </div>
            <span>
              <span class="block font-display font-bold text-[19px] leading-5 text-surface-900 tracking-tight">Ferosa</span>
              <span class="block mt-0.5 text-[10px] font-semibold uppercase tracking-[.13em] text-surface-400">Customer portal</span>
            </span>
          </a>
        </div>
        <button id="customer-sidebar-close" type="button" aria-label="Close navigation" class="lg:hidden absolute top-6 right-4 text-surface-400 hover:text-surface-600 p-2" onclick="toggleSidebar()">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
      </div>

      <!-- Navigation -->
      <nav class="flex flex-col w-full py-2 space-y-0.5">
        <span class="px-7 py-2 text-[11px] font-semibold text-surface-400 uppercase tracking-wider">Menu</span>

        <a href="{{ route('home') }}" class="nav-link flex items-center gap-3 w-full text-left px-4 py-2.5 text-[13px] {{ request()->routeIs('home') ? 'active' : '' }}">
          <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
          Home
        </a>
        <a href="{{ route('shop') }}" class="nav-link flex items-center gap-3 w-full text-left px-4 py-2.5 text-[13px] {{ request()->routeIs('shop') || request()->routeIs('products.*') ? 'active' : '' }}">
          <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
          Shop
        </a>
        <a href="{{ route('projects.index') }}" class="nav-link flex items-center gap-3 w-full text-left px-4 py-2.5 text-[13px] {{ request()->routeIs('projects.*') ? 'active' : '' }}">
          <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20h16M6 20V9l6-5 6 5v11M9 20v-6h6v6"/></svg>
          Projects
        </a>
        @auth
        <a href="{{ route('orders') }}" class="nav-link flex items-center gap-3 w-full text-left px-4 py-2.5 text-[13px] {{ request()->routeIs('orders') || request()->routeIs('orders.*') || request()->routeIs('checkout*') ? 'active' : '' }}">
          <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
          Orders
          @if(isset($customerPendingConfirmations) && $customerPendingConfirmations > 0)
            <span title="{{ $customerPendingConfirmations }} order{{ $customerPendingConfirmations !== 1 ? 's' : '' }} waiting for confirmation" class="ml-auto bg-red-500 text-white text-[9px] font-bold min-w-[16px] h-[16px] px-1 rounded-full flex items-center justify-center leading-none">{{ $customerPendingConfirmations > 9 ? '9+' : $customerPendingConfirmations }}</span>
          @endif
        </a>
        <a href="{{ route('schedule') }}" class="nav-link flex items-center gap-3 w-full text-left px-4 py-2.5 text-[13px] {{ request()->routeIs('schedule') ? 'active' : '' }}">
          <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
          Schedule
        </a>
        <a href="{{ route('appointments') }}" class="nav-link flex items-center gap-3 w-full text-left px-4 py-2.5 text-[13px] {{ request()->routeIs('appointments') ? 'active' : '' }}">
          <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
          Appointments
        </a>
        @endauth

        <span class="px-7 pt-5 pb-2 text-[11px] font-semibold text-surface-400 uppercase tracking-wider">Tools</span>

        <a href="{{ route('estimator') }}" class="nav-link flex items-center gap-3 w-full text-left px-4 py-2.5 text-[13px] {{ request()->routeIs('estimator') ? 'active' : '' }}">
          <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
          Cost Estimator
        </a>


        @auth
        <span class="px-7 pt-5 pb-2 text-[11px] font-semibold text-surface-400 uppercase tracking-wider">Support</span>

        <a href="{{ route('messages') }}" class="nav-link flex items-center gap-3 w-full text-left px-4 py-2.5 text-[13px] {{ request()->routeIs('messages') ? 'active' : '' }}">
          <div class="relative flex-shrink-0">
            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            @if($customerUnreadMessages > 0)
              <span class="absolute -top-1.5 -right-1.5 bg-red-500 text-white text-[9px] font-bold min-w-[15px] h-[15px] px-0.5 rounded-full flex items-center justify-center leading-none">{{ $customerUnreadMessages > 9 ? '9+' : $customerUnreadMessages }}</span>
            @endif
          </div>
          Messages
        </a>

        @if(auth()->user()->isStaffOrAdmin())
        <span class="px-7 pt-5 pb-2 text-[11px] font-semibold text-surface-400 uppercase tracking-wider">Staff</span>

        <a href="{{ route('admin.dashboard') }}" class="nav-link flex items-center gap-3 w-full text-left px-4 py-2.5 text-[13px] {{ request()->routeIs('admin.*') ? 'active' : '' }}">
          <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
          Admin Dashboard
        </a>
        @endif
        @endauth
      </nav>
    </div>

    <!-- Bottom -->
    <div class="p-3 border-t border-surface-100 bg-white/70">
      @auth
      <div class="flex items-center gap-3 rounded-xl border border-surface-100 bg-surface-50/80 p-2.5">
        <a href="{{ route('account') }}" class="w-9 h-9 flex-shrink-0 rounded-lg bg-brand-700 text-white flex items-center justify-center text-sm font-bold" aria-label="Open account">
          {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </a>
        <a href="{{ route('account') }}" class="min-w-0 flex-1">
          <span class="block truncate text-[13px] font-bold text-surface-800">{{ auth()->user()->name }}</span>
          <span class="block text-[10px] font-medium text-surface-400">View account</span>
        </a>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" data-loading-label="" aria-label="Sign out" title="Sign out" class="w-9 h-9 flex items-center justify-center text-surface-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
            <svg class="w-[17px] h-[17px]" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
          </button>
        </form>
      </div>
      @else
      <a href="{{ route('login') }}" class="w-full flex items-center gap-3 px-4 py-2.5 text-[13px] font-medium text-brand-600 hover:bg-brand-50 rounded-lg transition-colors">
        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
        Log in
      </a>
      @endauth
    </div>
  </aside>

  <!-- Main Content Wrapper -->
  <div class="flex-1 flex flex-col h-screen overflow-hidden" id="app-content-wrapper">
    @php
      $portalPage = match (true) {
        request()->routeIs('home') => ['Home', 'Your garden at a glance'],
        request()->routeIs('shop') || request()->routeIs('products.*') || request()->routeIs('checkout*') => ['Shop', 'Plants and garden essentials'],
        request()->routeIs('projects.*') => ['Projects', 'Verified Ferosa work'],
        request()->routeIs('orders*') => ['Orders', 'Purchases and delivery activity'],
        request()->routeIs('schedule') => ['Book a service', 'Choose a convenient visit'],
        request()->routeIs('appointments*') => ['Appointments', 'Your scheduled services'],
        request()->routeIs('estimator') => ['Estimator', 'Plan your project budget'],
        request()->routeIs('messages') => ['Messages', 'Talk with the Ferosa team'],
        request()->routeIs('account') => ['Account', 'Personal details and preferences'],
        default => ['Ferosa', 'Landscaping made simple'],
      };
    @endphp
    <header class="portal-topbar hidden lg:flex h-[72px] flex-shrink-0 items-center justify-between gap-5 border-b border-surface-100 px-7 relative z-30">
      <div>
        <p class="text-[10px] font-bold uppercase tracking-[.14em] text-brand-600">Customer portal</p>
        <p class="mt-0.5 text-sm font-bold text-surface-800">{{ $portalPage[0] }} <span class="ml-2 font-normal text-surface-400">{{ $portalPage[1] }}</span></p>
      </div>
      @auth
      <div class="flex items-center gap-2">
        <a href="{{ route('checkout') }}" class="portal-icon-button relative" id="header-cart-icon" aria-label="Open cart" title="Cart">
          <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13 5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8 2a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z"/></svg>
          <span id="header-cart-count" class="hidden absolute -top-1 -right-1 bg-brand-600 text-white text-[8px] font-bold min-w-[16px] h-4 px-1 rounded-full items-center justify-center leading-none">0</span>
        </a>
        <a href="{{ route('messages') }}" class="portal-icon-button relative" aria-label="Open messages" title="Messages">
          <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-5l-5 5v-5Z"/></svg>
          @if($customerUnreadMessages > 0)
            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[8px] font-bold min-w-[16px] h-4 px-1 rounded-full flex items-center justify-center leading-none">{{ $customerUnreadMessages > 9 ? '9+' : $customerUnreadMessages }}</span>
          @endif
        </a>
        <button id="desktop-notif-trigger" type="button" onclick="toggleNotifPanel()" class="portal-icon-button relative" aria-label="Open notifications" title="Notifications" aria-haspopup="true" aria-controls="notif-panel-desktop" aria-expanded="false">
          <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6.002 6.002 0 0 0-4-5.659V5a2 2 0 1 0-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9"/></svg>
          @if($customerUnreadNotifications > 0)
            <span class="absolute -top-1 -right-1 bg-brand-600 text-white text-[8px] font-bold min-w-[16px] h-4 px-1 rounded-full flex items-center justify-center leading-none">{{ $customerUnreadNotifications > 9 ? '9+' : $customerUnreadNotifications }}</span>
          @endif
        </button>
        <span class="mx-2 h-7 w-px bg-surface-200"></span>
        <a href="{{ route('account') }}" class="flex items-center gap-2.5 rounded-xl p-1.5 pr-2.5 hover:bg-surface-50 transition-colors">
          <span class="w-8 h-8 rounded-lg bg-brand-700 text-white flex items-center justify-center text-xs font-bold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
          <span class="max-w-[140px] truncate text-xs font-bold text-surface-700">{{ auth()->user()->name }}</span>
        </a>
      </div>
      @endauth
    </header>
    <!-- Mobile Header (hidden when running inside Android app) -->
    <header class="mobile-app-header lg:hidden bg-white border-b border-surface-100 h-14 flex items-center justify-between px-4 flex-shrink-0 z-30">
      <a href="{{ route('home') }}" class="flex items-center gap-2.5">
        <div class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
            <path d="M12 3C12 3 7 6.5 7 12c0 2.76 1.34 5.22 3.4 6.74.38-.48.93-.74 1.6-.74s1.22.26 1.6.74C15.66 17.22 17 14.76 17 12c0-5.5-5-9-5-9z" fill="white"/>
          </svg>
        </div>
        <span class="font-semibold text-surface-900">Ferosa</span>
      </a>
      <div class="flex items-center gap-1">
        @auth
        <a href="{{ route('messages') }}" class="w-9 h-9 flex items-center justify-center text-surface-500 hover:text-surface-700 rounded-lg transition-colors relative">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
          @if($customerUnreadMessages > 0)
            <span class="absolute top-1 right-1 bg-red-500 text-white text-[8px] font-bold min-w-[13px] h-[13px] px-0.5 rounded-full flex items-center justify-center leading-none">{{ $customerUnreadMessages > 9 ? '9+' : $customerUnreadMessages }}</span>
          @endif
        </a>
        <div class="relative">
          <button type="button" aria-label="Open notifications" onclick="toggleNotifPanel()" class="w-9 h-9 flex items-center justify-center text-surface-500 hover:text-surface-700 rounded-lg transition-colors relative">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            @if($customerUnreadNotifications > 0)
              <span class="absolute top-1 right-1 bg-brand-500 text-white text-[8px] font-bold min-w-[13px] h-[13px] px-0.5 rounded-full flex items-center justify-center leading-none">{{ $customerUnreadNotifications > 9 ? '9+' : $customerUnreadNotifications }}</span>
            @endif
          </button>
          <!-- Notification dropdown panel -->
          <div id="notif-panel" class="hidden absolute right-0 top-full mt-2 w-80 bg-white border border-surface-200 rounded-xl shadow-lg z-50 overflow-hidden">
            <div class="flex items-center justify-between px-4 py-2.5 border-b border-surface-100">
              <span class="text-[11px] font-semibold text-surface-500 uppercase tracking-wider">Notifications</span>
              <button onclick="markAllRead()" class="text-[11px] text-brand-600 hover:text-brand-700 font-medium">Mark all read</button>
            </div>
            <div id="notif-list" class="max-h-72 overflow-y-auto divide-y divide-surface-100">
              <div class="px-4 py-6 text-center text-xs text-surface-400" id="notif-empty">Loading...</div>
            </div>
          </div>
        </div>
        @endauth
        <button type="button" aria-label="Open navigation" aria-controls="customer-sidebar" aria-expanded="false" data-sidebar-trigger class="w-9 h-9 flex items-center justify-center text-surface-500 hover:text-surface-700 rounded-lg transition-colors" onclick="toggleSidebar(this)">
          <svg fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
        </button>
      </div>
    </header>

    <!-- Page Content -->
    <div id="main-content" class="flex-1 overflow-y-auto relative w-full h-full bg-surface-50" tabindex="-1">
      @yield('content')
    </div>
  </div>

  <script>
    let lastSidebarTrigger = null;
    let sidebarIsOpen = false;
    let sidebarOverlayTimer = null;
    let sidebarFocusTimer = null;
    const mobileSidebarQuery = window.matchMedia('(max-width: 1023px)');

    function setSidebarInert(sidebar, shouldBeInert) {
      sidebar.inert = shouldBeInert;
      if (shouldBeInert) {
        sidebar.setAttribute('inert', '');
      } else {
        sidebar.removeAttribute('inert');
      }
    }

    function sidebarFocusableElements(sidebar) {
      return Array.from(sidebar.querySelectorAll(
        'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"]), [contenteditable="true"]'
      )).filter(element => element.getClientRects().length > 0 && element.getAttribute('aria-hidden') !== 'true');
    }

    function syncSidebarState(returnFocus = false) {
      const sidebar = document.getElementById('customer-sidebar');
      const overlay = document.getElementById('mobile-overlay');
      if (!sidebar || !overlay) return;

      window.clearTimeout(sidebarOverlayTimer);
      window.clearTimeout(sidebarFocusTimer);

      if (!mobileSidebarQuery.matches) {
        sidebarIsOpen = false;
        sidebar.classList.add('-translate-x-full');
        sidebar.classList.remove('invisible');
        sidebar.removeAttribute('aria-hidden');
        sidebar.removeAttribute('aria-modal');
        sidebar.removeAttribute('role');
        setSidebarInert(sidebar, false);
        overlay.classList.add('hidden', 'opacity-0');
        overlay.setAttribute('aria-hidden', 'true');
      } else if (sidebarIsOpen) {
        sidebar.classList.remove('invisible');
        sidebar.classList.remove('-translate-x-full');
        sidebar.setAttribute('aria-hidden', 'false');
        sidebar.setAttribute('aria-modal', 'true');
        sidebar.setAttribute('role', 'dialog');
        setSidebarInert(sidebar, false);
        overlay.classList.remove('hidden');
        overlay.setAttribute('aria-hidden', 'true');
        requestAnimationFrame(() => overlay.classList.remove('opacity-0'));
        sidebarFocusTimer = window.setTimeout(() => {
          document.getElementById('customer-sidebar-close')?.focus({ preventScroll: true });
        }, 120);
      } else {
        if (returnFocus && lastSidebarTrigger?.isConnected) {
          lastSidebarTrigger.focus({ preventScroll: true });
        }

        sidebar.classList.add('-translate-x-full');
        sidebar.setAttribute('aria-hidden', 'true');
        sidebar.removeAttribute('aria-modal');
        sidebar.removeAttribute('role');
        setSidebarInert(sidebar, true);
        overlay.classList.add('opacity-0');
        overlay.setAttribute('aria-hidden', 'true');
        sidebarOverlayTimer = window.setTimeout(() => {
          overlay.classList.add('hidden');
          sidebar.classList.add('invisible');
        }, 300);
      }

      document.querySelectorAll('[data-sidebar-trigger]').forEach(button => {
        button.setAttribute('aria-expanded', String(mobileSidebarQuery.matches && sidebarIsOpen));
      });
    }

    function toggleSidebar(trigger = null) {
      if (!mobileSidebarQuery.matches) return;
      if (trigger && !sidebarIsOpen) lastSidebarTrigger = trigger;

      const wasOpen = sidebarIsOpen;
      sidebarIsOpen = !sidebarIsOpen;
      syncSidebarState(wasOpen);
    }

    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape' && mobileSidebarQuery.matches && sidebarIsOpen) {
        sidebarIsOpen = false;
        syncSidebarState(true);
        return;
      }

      if (event.key !== 'Tab' || !mobileSidebarQuery.matches || !sidebarIsOpen) return;

      const sidebar = document.getElementById('customer-sidebar');
      if (!sidebar) return;
      const focusable = sidebarFocusableElements(sidebar);
      if (!focusable.length) {
        event.preventDefault();
        return;
      }

      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      const activeIndex = focusable.indexOf(document.activeElement);

      if (event.shiftKey && activeIndex <= 0) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && (activeIndex === -1 || activeIndex === focusable.length - 1)) {
        event.preventDefault();
        first.focus();
      }
    });

    function reconcileSidebarBreakpoint() {
      const sidebar = document.getElementById('customer-sidebar');
      const shouldReturnFocus = mobileSidebarQuery.matches && sidebar?.contains(document.activeElement);
      sidebarIsOpen = false;
      syncSidebarState(shouldReturnFocus);
    }

    if (typeof mobileSidebarQuery.addEventListener === 'function') {
      mobileSidebarQuery.addEventListener('change', reconcileSidebarBreakpoint);
    } else {
      mobileSidebarQuery.addListener(reconcileSidebarBreakpoint);
    }

    syncSidebarState(false);

    let notifLoaded = false;

    function positionDesktopNotifPanel() {
      const trigger = document.getElementById('desktop-notif-trigger');
      const panel = document.getElementById('notif-panel-desktop');
      if (!trigger || !panel) return;

      const triggerRect = trigger.getBoundingClientRect();
      const viewportGap = 16;
      const panelWidth = Math.min(320, window.innerWidth - (viewportGap * 2));
      const left = Math.min(
        window.innerWidth - panelWidth - viewportGap,
        Math.max(viewportGap, triggerRect.right - panelWidth)
      );

      panel.style.width = panelWidth + 'px';
      panel.style.left = left + 'px';
      panel.style.top = (triggerRect.bottom + 10) + 'px';
    }

    function toggleNotifPanel() {
      // Toggle both mobile and desktop panels
      const mobilePanel = document.getElementById('notif-panel');
      const desktopPanel = document.getElementById('notif-panel-desktop');

      // On mobile (< 1024px) use mobile panel, on desktop use desktop panel
      const isMobile = window.innerWidth < 1024;
      const panel = isMobile ? mobilePanel : desktopPanel;

      if (!panel) return;
      const isHidden = panel.classList.contains('hidden');

      // Close both first
      if (mobilePanel) mobilePanel.classList.add('hidden');
      if (desktopPanel) desktopPanel.classList.add('hidden');

      // Toggle the relevant one
      if (isHidden) {
        if (!isMobile) positionDesktopNotifPanel();
        panel.classList.remove('hidden');
        document.getElementById('desktop-notif-trigger')?.setAttribute('aria-expanded', String(!isMobile));
        if (!notifLoaded) loadNotifications();
      } else {
        document.getElementById('desktop-notif-trigger')?.setAttribute('aria-expanded', 'false');
      }
    }

    window.addEventListener('resize', function() {
      const desktopPanel = document.getElementById('notif-panel-desktop');
      if (desktopPanel && !desktopPanel.classList.contains('hidden')) {
        positionDesktopNotifPanel();
      }
    });

    // Close notification panel when clicking outside
    document.addEventListener('click', function(e) {
      const panel = document.getElementById('notif-panel-desktop');
      const mobilePanel = document.getElementById('notif-panel');
      const isNotifBtn = e.target.closest('[onclick*="toggleNotifPanel"]');
      if (!isNotifBtn) {
        if (panel && !panel.contains(e.target)) {
          panel.classList.add('hidden');
          document.getElementById('desktop-notif-trigger')?.setAttribute('aria-expanded', 'false');
        }
        if (mobilePanel && !mobilePanel.contains(e.target)) mobilePanel.classList.add('hidden');
      }
    });

    function loadNotifications() {
      fetch('{{ route('notifications') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
          notifLoaded = true;
          renderNotifications(data.notifications);
        })
        .catch(() => {
          [document.getElementById('notif-list'), document.getElementById('notif-list-desktop')].filter(Boolean).forEach(list => {
            const error = document.createElement('div');
            error.className = 'px-4 py-6 text-center text-xs text-surface-400';
            error.textContent = 'Could not load notifications.';
            list.replaceChildren(error);
          });
        });
    }

    function renderNotifications(items) {
      // Render into both mobile and desktop notification lists
      const lists = [document.getElementById('notif-list'), document.getElementById('notif-list-desktop')].filter(Boolean);
      const notifications = Array.isArray(items) ? items : [];

      lists.forEach(list => {
        list.replaceChildren();

        if (!notifications.length) {
          const empty = document.createElement('div');
          empty.className = 'px-4 py-6 text-center text-xs text-surface-400';
          empty.textContent = 'No notifications yet.';
          list.appendChild(empty);
          return;
        }

        notifications.forEach(notification => {
          const item = notification && typeof notification === 'object' ? notification : {};
          const data = item.data && typeof item.data === 'object' ? item.data : {};
          const unread = !item.read_at;

          const row = document.createElement('button');
          row.type = 'button';
          row.className = `w-full px-4 py-3 flex items-start gap-3 text-left ${unread ? 'bg-brand-50' : ''} hover:bg-surface-50 transition-colors`;
          row.addEventListener('click', () => readNotif(item.id, data.url, row));

          const dot = document.createElement('span');
          dot.className = `flex-shrink-0 w-2 h-2 rounded-full ${unread ? 'bg-brand-500' : 'bg-surface-200'} mt-1.5`;
          dot.setAttribute('data-notification-dot', '');
          dot.setAttribute('aria-hidden', 'true');

          const content = document.createElement('span');
          content.className = 'flex-1 min-w-0';

          const message = document.createElement('span');
          message.className = 'block text-xs text-surface-800 leading-snug';
          message.textContent = typeof data.message === 'string' ? data.message : '';

          const createdAt = document.createElement('span');
          createdAt.className = 'block text-[11px] text-surface-400 mt-0.5';
          createdAt.textContent = typeof item.created_at === 'string' ? item.created_at : '';

          content.append(message, createdAt);
          row.append(dot, content);
          list.appendChild(row);
        });
      });
    }

    function safeNotificationUrl(url) {
      if (!url) return '{{ route('home', absolute: false) }}';
      try {
        const parsed = new URL(url, window.location.origin);
        if (parsed.origin !== window.location.origin) {
          return '{{ route('home', absolute: false) }}';
        }
        return parsed.href;
      } catch {
        return '{{ route('home', absolute: false) }}';
      }
    }

    function readNotif(id, url, el) {
      const notificationId = String(id ?? '').trim();
      if (!/^[A-Za-z0-9-]{1,128}$/.test(notificationId)) return;

      fetch(`{{ url('/notifications') }}/${encodeURIComponent(notificationId)}/read`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]') ? document.querySelector('meta[name=csrf-token]').content : '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' }
      }).catch(() => {});
      el?.querySelector('[data-notification-dot]')?.classList.replace('bg-brand-500', 'bg-surface-200');
      el?.classList.remove('bg-brand-50');
      setTimeout(() => { window.location = safeNotificationUrl(url); }, 150);
    }

    function markAllRead() {
      fetch('{{ route('notifications.read-all') }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]') ? document.querySelector('meta[name=csrf-token]').content : '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' }
      }).then(() => {
        document.querySelectorAll('#notif-list .bg-brand-50, #notif-list-desktop .bg-brand-50').forEach(el => el.classList.remove('bg-brand-50'));
        document.querySelectorAll('#notif-list [data-notification-dot].bg-brand-500, #notif-list-desktop [data-notification-dot].bg-brand-500').forEach(el => el.classList.replace('bg-brand-500', 'bg-surface-200'));
        notifLoaded = false;
      });
    }

    // Close panel when clicking outside
    document.addEventListener('click', function(e) {
      const panel = document.getElementById('notif-panel');
      if (!panel || panel.classList.contains('hidden')) return;
      const wrapper = panel.closest('.relative');
      if (wrapper && !wrapper.contains(e.target)) {
        panel.classList.add('hidden');
      }
    });

    function setSubmitLoading(form) {
      if (!form || form.dataset.noLoading === 'true') return;
      const submitters = form.querySelectorAll('button[type="submit"], button:not([type])');
      submitters.forEach(button => {
        if (button.dataset.loading === 'true') return;
        button.dataset.originalLabel = button.innerHTML;
        button.dataset.loading = 'true';
        button.disabled = true;
        const label = Object.prototype.hasOwnProperty.call(button.dataset, 'loadingLabel')
          ? button.dataset.loadingLabel
          : 'Saving...';
        button.innerHTML = `<span class="inline-block w-3.5 h-3.5 border-2 border-current border-r-transparent rounded-full animate-spin"></span>${label ? `<span>${label}</span>` : ''}`;
      });
    }

    document.addEventListener('submit', function(e) {
      const form = e.target;
      if (!(form instanceof HTMLFormElement)) return;
      if ((form.method || '').toLowerCase() === 'get' && !form.dataset.loadingLabel) return;
      setSubmitLoading(form);
    }, true);
  </script>
  @yield('scripts')

  <!-- Global cart counter updater -->
  <script>
    function renderHeaderCartCount(count) {
      const cartIcon = document.getElementById('header-cart-icon');
      const cartBadge = document.getElementById('header-cart-count');
      if (!cartIcon || !cartBadge) return;

      cartBadge.textContent = count > 99 ? '99+' : count;
      cartBadge.classList.toggle('hidden', count === 0);
      cartBadge.classList.toggle('flex', count > 0);
    }

    function updateHeaderCartCount() {
      try {
        const cart = JSON.parse(localStorage.getItem('ferosa_cart')) || [];
        const count = cart.reduce((t, i) => t + i.qty, 0);
        renderHeaderCartCount(count);
      } catch (e) {
        renderHeaderCartCount(0);
      }
    }

    async function loadHeaderCartCount() {
      updateHeaderCartCount();
      try {
        const response = await fetch('{{ url('/api/cart') }}', { headers: { 'Accept': 'application/json' } });
        if (!response.ok) return;
        const data = await response.json();
        localStorage.setItem('ferosa_cart', JSON.stringify(data.items || []));
        renderHeaderCartCount(data.cart_count || 0);
      } catch (_) {}
    }

    document.addEventListener('DOMContentLoaded', loadHeaderCartCount);

    // Update when localStorage changes (e.g., items added from shop page)
    window.addEventListener('storage', function(e) {
      if (e.key === 'ferosa_cart') updateHeaderCartCount();
    });

    // Also listen for custom cart update event
    window.addEventListener('cartUpdated', function (event) {
      if (event.detail && Number.isInteger(event.detail.cart_count)) {
        renderHeaderCartCount(event.detail.cart_count);
      } else {
        updateHeaderCartCount();
      }
    });
  </script>

  <!-- Notification dropdown panel (positioned outside sidebar to avoid overflow issues) -->
  @auth
  <div id="notif-panel-desktop" class="hidden fixed w-80 bg-white border border-surface-200 rounded-2xl shadow-card z-[100] overflow-hidden">
    <div class="flex items-center justify-between px-4 py-2.5 border-b border-surface-100">
      <span class="text-[11px] font-semibold text-surface-500 uppercase tracking-wider">Notifications</span>
      <button onclick="markAllRead()" class="text-[11px] text-brand-600 hover:text-brand-700 font-medium">Mark all read</button>
    </div>
    <div id="notif-list-desktop" class="max-h-72 overflow-y-auto divide-y divide-surface-100">
      <div class="px-4 py-6 text-center text-xs text-surface-400">Loading...</div>
    </div>
  </div>
  @endauth
</body>
</html>
