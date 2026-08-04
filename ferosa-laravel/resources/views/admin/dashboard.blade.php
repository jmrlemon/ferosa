<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="ferosa-user-role" content="{{ auth()->user()?->role ?? 'staff' }}">
  <title>Admin Dashboard - Ferosa Landscaping</title>

  <link rel="stylesheet" href="{{ asset('fonts/ferosa-fonts.css') }}">

  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <style>
    .tab-content { display: none; }
    .tab-content.active { display: block; }
    .tab-btn { transition: all 0.15s; }
    .tab-btn:hover { background-color: rgba(0,0,0,0.03); }
    .tab-btn.active { background-color: #e8f2ec; color: #1b5239; font-weight: 700; box-shadow: inset 3px 0 0 #236746; }
    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #d4d4d8; border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #a1a1aa; }
    :focus-visible { outline: 3px solid rgba(52,127,87,.3); outline-offset: 3px; }
    #admin-sidebar { background: linear-gradient(180deg, rgba(238,247,241,.78), transparent 190px), #fff; }
    .admin-panel {
      border-color: #e8e4db !important;
      box-shadow: 0 1px 2px rgba(18,52,38,.025), 0 10px 32px rgba(18,52,38,.04);
    }
    #tabs-main { background:
      radial-gradient(circle at 90% 0%, rgba(130,189,152,.10), transparent 24rem),
      #f8f7f3;
    }
    #tabs-main > main { max-width: 1600px; margin-inline: auto; }
    #tabs-main .overflow-x-auto { scrollbar-gutter: stable; }
    #tabs-main table thead { background: rgba(248,247,243,.86); }
    #tabs-main .tab-content > .bg-white {
      border-color: #e8e4db !important;
      border-radius: 1rem !important;
      box-shadow: 0 1px 2px rgba(18,52,38,.025), 0 12px 36px rgba(18,52,38,.04);
    }
    #tabs-main .tab-content h2 { letter-spacing: -.01em; }
    .admin-chat-bubble {
      padding: .625rem 1rem;
      font-size: .875rem;
      line-height: 1.45;
      overflow-wrap: anywhere;
    }
    .admin-chat-bubble--mine {
      color: #fff;
      background: linear-gradient(135deg, #1a6320, #2d9a2d);
      border-radius: 1.25rem 1.25rem .3rem 1.25rem;
      box-shadow: 0 1px 3px rgba(26, 99, 32, .2);
    }
    .admin-chat-bubble--customer {
      color: #292824;
      background: #fff;
      border: 1px solid #e8e4db;
      border-radius: 1.25rem 1.25rem 1.25rem .3rem;
      box-shadow: 0 1px 2px rgba(18, 52, 38, .05);
    }
    #tabs-main input:not([type="checkbox"]):not([type="radio"]),
    #tabs-main select,
    #tabs-main textarea { min-height: 2.5rem; }
    summary::-webkit-details-marker { display: none; }
    @media (max-width: 767px) {
      #admin-sidebar { position: fixed; inset: 0 auto 0 0; width: 272px; z-index: 60; transform: translateX(-100%); transition: transform .22s ease; }
      #admin-sidebar.open { transform: translateX(0); }
      #admin-overlay { display: block; opacity: 0; pointer-events: none; transition: opacity .22s ease; }
      #admin-overlay.open { opacity: 1; pointer-events: auto; }
      #tabs-main > main { padding: 1rem !important; }
      #tab-messages > .flex { min-width: 0; }
      #tab-messages .admin-conversation-list { width: 100%; min-width: 0; }
      #tab-messages .admin-message-thread { display: none; min-width: 0; }
      #tab-messages.thread-open .admin-conversation-list { display: none; }
      #tab-messages.thread-open .admin-message-thread { display: flex; }
      #tabs-main table { min-width: 720px; }
      #tabs-main .admin-compact-table { min-width: 100%; }
      #tabs-main form[method="GET"] > div { min-width: min(100%, 15rem); flex: 1 1 auto; max-width: none; }
      #tabs-main form[method="GET"] > button { min-height: 2.75rem; flex: 1 1 auto; }
    }
    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after { transition-duration: .01ms !important; animation-duration: .01ms !important; scroll-behavior: auto !important; }
    }
  </style>
  @include('admin.partials.type-scale')
  <script>
    window.addEventListener('pageshow', function (event) {
      if (event.persisted) window.location.reload();
    });

    function toggleAdminSidebar(forceClose = false) {
      const sidebar = document.getElementById('admin-sidebar');
      const overlay = document.getElementById('admin-overlay');
      const trigger = document.getElementById('admin-sidebar-trigger');
      if (!sidebar || !overlay) return;
      const shouldOpen = !forceClose && !sidebar.classList.contains('open');
      sidebar.classList.toggle('open', shouldOpen);
      overlay.classList.toggle('open', shouldOpen);
      trigger?.setAttribute('aria-expanded', String(shouldOpen));
      document.body.style.overflow = shouldOpen ? 'hidden' : '';
      if (shouldOpen) {
        window.setTimeout(() => document.getElementById('admin-sidebar-close')?.focus(), 100);
      } else if (window.innerWidth < 768 && document.activeElement?.closest('#admin-sidebar')) {
        trigger?.focus();
      }
    }

    window.addEventListener('resize', function () {
      if (window.innerWidth >= 768) toggleAdminSidebar(true);
    });

    document.addEventListener('click', function (event) {
      if (window.innerWidth >= 768) return;
      if (event.target.closest('#admin-sidebar a, #admin-sidebar button')) {
        setTimeout(() => toggleAdminSidebar(true), 80);
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && document.getElementById('admin-sidebar')?.classList.contains('open')) {
        toggleAdminSidebar(true);
      }
      if (event.key === 'Escape' && !document.getElementById('admin-notif-panel')?.classList.contains('hidden')) {
        document.getElementById('admin-notif-panel')?.classList.add('hidden');
        const trigger = document.getElementById('admin-notif-trigger');
        trigger?.setAttribute('aria-expanded', 'false');
        trigger?.focus();
      }
    });
  </script>
  @include('partials.a11y-focus')
</head>
<body class="flex h-screen h-[100dvh] bg-surface-50 text-surface-800 overflow-hidden font-sans antialiased">
  <a href="#admin-main" class="skip-link">Skip to admin content</a>
  @php
    $isAdmin = auth()->user()?->isAdmin();
    $isStaffOrAdmin = auth()->user()?->isStaffOrAdmin();
    $availableTabs = ['overview', 'appointments', 'orders', 'services', 'products', 'messages', 'archived', 'audit', 'users', 'feedbacks'];
    if ($isAdmin) $availableTabs[] = 'payment';
    $routeTab = match (request()->route()?->getName()) {
        'admin.service-scheduling' => 'appointments',
        'admin.ordering-delivery' => 'orders',
        default => null,
    };
    // $activeTab is resolved by AdminController so it can load only this tab's
    // data; the fallback keeps the view renderable on its own.
    $activeTab = $activeTab ?? $routeTab ?? (in_array(request('tab'), $availableTabs, true) ? request('tab') : 'overview');
    $tabClass = fn (string $tab, string $extra = '') => trim('tab-content '.$extra.' '.($activeTab === $tab ? 'active' : ''));
    $tabButtonClass = fn (string $tab) => 'tab-btn '.($activeTab === $tab ? 'active ' : '').'flex items-center gap-2.5 w-full text-left px-2.5 py-2 text-[13px] text-surface-500 rounded-lg';
    $badgeCount = fn (int $count) => $count > 9 ? '9+' : (string) $count;
    // Tabs navigate server-side: the controller loads only the active tab's
    // data, so switching client-side would show empty tables.
    $tabUrl = fn (string $tab) => match ($tab) {
        'appointments' => route('admin.service-scheduling'),
        'orders' => route('admin.ordering-delivery'),
        'overview' => route('admin.dashboard'),
        default => route('admin.dashboard', ['tab' => $tab]),
    };
  @endphp

  <div id="admin-overlay" class="hidden fixed inset-0 bg-black/30 backdrop-blur-sm z-50" onclick="toggleAdminSidebar()" aria-hidden="true"></div>

  <!-- Sidebar -->
  <aside id="admin-sidebar" aria-label="Admin navigation" class="w-64 bg-white border-r border-surface-100 flex flex-col justify-between flex-shrink-0 z-20">
    <div class="min-h-0 overflow-y-auto">
      <div class="px-4 py-4 border-b border-surface-100 flex items-center gap-3 sticky top-0 bg-white/95 backdrop-blur z-10">
        <div class="w-9 h-9 bg-brand-950 rounded-xl flex items-center justify-center shadow-sm">
          <svg class="w-5 h-5 text-brand-100" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 4.5c-6.6.2-11.3 3-13.5 8.3M5.2 19c1.1-5.2 4.8-8.7 10.8-10.7M19.5 4.5c.4 6.8-2.8 11.3-8.1 11.8-2.3.2-4.3-.8-5.4-3.5"/></svg>
        </div>
        <div class="min-w-0 flex-1">
          <span class="block font-display text-base font-semibold leading-none text-brand-950">Ferosa</span>
          <span class="mt-1 block text-[9px] font-bold uppercase tracking-[.18em] text-surface-400">Admin workspace</span>
        </div>
        <button id="admin-sidebar-close" type="button" onclick="toggleAdminSidebar(true)" class="md:hidden flex h-10 w-10 items-center justify-center rounded-xl text-surface-500 hover:bg-surface-50" aria-label="Close admin navigation">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="m6 6 12 12M18 6 6 18"/></svg>
        </button>
      </div>

      <nav aria-label="Primary admin" class="flex flex-col w-full py-3 px-3 space-y-0.5">
        <p class="text-[10px] font-semibold text-surface-400 uppercase tracking-wider px-2 mb-1">Dashboard</p>
        <a href="{{ $tabUrl('overview') }}" class="{{ $tabButtonClass('overview') }}" id="btn-overview">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
          Overview
        </a>

        <p class="text-[10px] font-semibold text-surface-400 uppercase tracking-wider px-2 mt-4 mb-1">Operations</p>
        <a href="{{ route('admin.service-scheduling') }}" class="{{ $tabButtonClass('appointments') }}" id="btn-appointments">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          <span class="truncate">Appointments</span>
          @if($overdueAppointments > 0)
            <span aria-label="{{ $overdueAppointments }} overdue appointment{{ $overdueAppointments !== 1 ? 's' : '' }}" title="{{ $overdueAppointments }} overdue appointment{{ $overdueAppointments !== 1 ? 's' : '' }}" class="ml-auto inline-flex min-h-[20px] flex-shrink-0 items-center justify-center whitespace-nowrap rounded-full border border-red-200 bg-red-50 px-2 text-[9px] font-bold leading-none text-red-700">{{ $badgeCount($overdueAppointments) }} overdue</span>
          @elseif($appointmentsNeedingConfirmation > 0)
            <span aria-label="{{ $appointmentsNeedingConfirmation }} appointment{{ $appointmentsNeedingConfirmation !== 1 ? 's' : '' }} awaiting confirmation" title="{{ $appointmentsNeedingConfirmation }} appointment{{ $appointmentsNeedingConfirmation !== 1 ? 's' : '' }} awaiting confirmation" class="ml-auto inline-flex min-h-[20px] flex-shrink-0 items-center justify-center whitespace-nowrap rounded-full border border-amber-200 bg-amber-50 px-2 text-[9px] font-bold leading-none text-amber-700">{{ $badgeCount($appointmentsNeedingConfirmation) }} pending</span>
          @endif
        </a>
        <a href="{{ route('admin.ordering-delivery') }}" class="{{ $tabButtonClass('orders') }}" id="btn-orders">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 12 3l9 4.5M5 8.5V17l7 4 7-4V8.5M12 12v9m-7-12.5 7 4 7-4"/></svg>
          <span class="truncate">Orders &amp; Delivery</span>
          @if(($orderFlowStats['pending'] ?? 0) > 0)
            <span aria-label="{{ $orderFlowStats['pending'] }} order{{ $orderFlowStats['pending'] !== 1 ? 's' : '' }} awaiting processing" title="{{ $orderFlowStats['pending'] }} order{{ $orderFlowStats['pending'] !== 1 ? 's' : '' }} awaiting processing" class="ml-auto inline-flex min-h-[20px] flex-shrink-0 items-center justify-center whitespace-nowrap rounded-full border border-amber-200 bg-amber-50 px-2 text-[9px] font-bold leading-none text-amber-700">{{ $badgeCount($orderFlowStats['pending']) }} actions</span>
          @endif
        </a>
        <a href="{{ $tabUrl('services') }}" class="{{ $tabButtonClass('services') }}" id="btn-services">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          Services
        </a>
        <a href="{{ $tabUrl('products') }}" class="{{ $tabButtonClass('products') }}" id="btn-products">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
          Inventory
          @if($lowStockProducts->count() > 0)
            <span title="{{ $lowStockProducts->count() }} low-stock product{{ $lowStockProducts->count() !== 1 ? 's' : '' }}" class="ml-auto bg-red-500 text-white text-[10px] font-bold min-w-[18px] h-[18px] px-1.5 rounded-full flex items-center justify-center leading-none">{{ $badgeCount($lowStockProducts->count()) }}</span>
          @endif
        </a>
        <a href="{{ route('admin.projects.index') }}" class="{{ $tabButtonClass('projects') }}">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20h16M6 20V9l6-5 6 5v11M9 20v-6h6v6"/></svg>
          Project Portfolio
        </a>

        <a href="{{ $tabUrl('messages') }}" class="{{ $tabButtonClass('messages') }}" id="btn-messages">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
          Messages
          @if($totalUnreadMessages > 0)
            <span title="{{ $totalUnreadMessages }} unread message{{ $totalUnreadMessages !== 1 ? 's' : '' }}" class="ml-auto bg-red-500 text-white text-[10px] font-bold min-w-[18px] h-[18px] px-1.5 rounded-full flex items-center justify-center leading-none">{{ $badgeCount($totalUnreadMessages) }}</span>
          @endif
        </a>

        @if($isAdmin)
        <a href="{{ $tabUrl('payment') }}" class="{{ $tabButtonClass('payment') }}" id="btn-payment">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2m2-6h2a2 2 0 012 2v2a2 2 0 01-2 2h-2m0-6v6"/></svg>
          Billing
        </a>
        @endif

        <p class="text-[10px] font-semibold text-surface-400 uppercase tracking-wider px-2 mt-4 mb-1">System</p>
        @if($isAdmin)
          <a href="{{ route('admin.business-profile.edit') }}" class="{{ $tabButtonClass('business-profile') }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 10h.01M9 14h.01M15 10h.01M15 14h.01M10 21v-4h4v4"/></svg>
            Business Profile
          </a>
        @endif
        <a href="{{ $tabUrl('archived') }}" class="{{ $tabButtonClass('archived') }}" id="btn-archived">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
          Archived
        </a>
        <a href="{{ $tabUrl('audit') }}" class="{{ $tabButtonClass('audit') }}" id="btn-audit">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          Audit Logs
        </a>
        <a href="{{ $tabUrl('feedbacks') }}" class="{{ $tabButtonClass('feedbacks') }}" id="btn-feedbacks">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
          Feedback
          @if($feedbacks->total() > 0)
            <span id="admin-feedback-badge" data-count="{{ $feedbacks->total() }}" title="{{ $feedbacks->total() }} feedback submission{{ $feedbacks->total() !== 1 ? 's' : '' }}" class="ml-auto bg-red-500 text-white text-[10px] font-bold min-w-[18px] h-[18px] px-1.5 rounded-full flex items-center justify-center leading-none">{{ $badgeCount($feedbacks->total()) }}</span>
          @endif
        </a>
        @if($isAdmin)
          <a href="{{ $tabUrl('users') }}" class="{{ $tabButtonClass('users') }}" id="btn-users">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            Users
          </a>
        @endif
      </nav>
    </div>

    <div class="border-t border-surface-100 bg-white/70 p-3">
      <div class="flex items-center gap-3 rounded-xl border border-surface-200 bg-surface-50/80 p-2.5">
        <a href="{{ route('admin.account.edit') }}" class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-brand-700 text-sm font-bold text-white" aria-label="Open admin account">
          {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </a>
        <a href="{{ route('admin.account.edit') }}" class="min-w-0 flex-1">
          <span class="block truncate text-[13px] font-bold text-surface-800">{{ auth()->user()->name }}</span>
          <span class="block text-[10px] font-medium text-surface-400">Admin account</span>
        </a>
        <form method="POST" action="{{ route('logout') }}" class="flex-shrink-0">
          @csrf
          <button type="submit" aria-label="Sign out" title="Sign out" class="flex h-9 w-9 items-center justify-center rounded-lg text-surface-400 transition-colors hover:bg-red-50 hover:text-red-600">
            <svg class="h-[17px] w-[17px]" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0-4-4m4 4H7m6 4v1a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1"/></svg>
          </button>
        </form>
      </div>
    </div>
  </aside>

  <!-- Main Content -->
  <div class="flex-1 flex flex-col overflow-hidden w-full">
    <header class="h-[68px] bg-white/90 backdrop-blur-xl border-b border-surface-100 flex items-center justify-between gap-4 px-4 sm:px-5 flex-shrink-0 relative z-30">
      <div class="flex items-center gap-3 min-w-0">
        <button id="admin-sidebar-trigger" type="button" onclick="toggleAdminSidebar()" class="md:hidden w-10 h-10 flex items-center justify-center rounded-xl border border-surface-200 text-surface-500" aria-label="Open admin navigation" aria-controls="admin-sidebar" aria-expanded="false">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
        <div class="min-w-0">
          <p class="text-[10px] font-bold uppercase tracking-[.14em] text-brand-600">Operations center</p>
          <p class="truncate text-sm font-bold text-surface-800">Ferosa workspace</p>
        </div>
      </div>
      <div class="flex items-center justify-end gap-1.5">
      <a href="{{ $tabUrl('messages') }}" class="w-9 h-9 flex items-center justify-center text-surface-400 hover:text-surface-700 hover:bg-surface-50 rounded-lg transition-colors relative" title="Messages" aria-label="Open messages">
        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
        @if($totalUnreadMessages > 0)
          <span class="absolute top-1 right-1 bg-red-500 text-white text-[8px] font-bold min-w-[13px] h-[13px] px-0.5 rounded-full flex items-center justify-center leading-none">{{ $totalUnreadMessages > 9 ? '9+' : $totalUnreadMessages }}</span>
        @endif
      </a>

      <div class="relative">
        <button id="admin-notif-trigger" type="button" onclick="toggleAdminNotifPanel()" class="w-9 h-9 flex items-center justify-center text-surface-400 hover:text-surface-700 hover:bg-surface-50 rounded-lg transition-colors relative" title="Notifications" aria-label="Open notifications" aria-controls="admin-notif-panel" aria-expanded="false">
          <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
          @if($adminUnreadNotifications > 0)
            <span id="admin-notif-count" class="absolute top-1 right-1 bg-red-500 text-white text-[8px] font-bold min-w-[13px] h-[13px] px-0.5 rounded-full flex items-center justify-center leading-none">{{ $adminUnreadNotifications > 9 ? '9+' : $adminUnreadNotifications }}</span>
          @endif
        </button>

        <div id="admin-notif-panel" class="hidden absolute right-0 top-full mt-2 w-[calc(100vw-2rem)] sm:w-80 bg-white border border-surface-200 rounded-xl shadow-lg z-50 overflow-hidden">
          <div class="flex items-center justify-between px-4 py-2.5 border-b border-surface-100">
            <span class="text-[11px] font-semibold text-surface-500 uppercase tracking-wider">Notifications</span>
            <button type="button" onclick="markAdminNotificationsRead()" class="text-[11px] text-brand-600 hover:text-brand-700 font-medium">Mark all read</button>
          </div>
          <div id="admin-notif-list" class="max-h-72 overflow-y-auto divide-y divide-surface-100">
            <div class="px-4 py-6 text-center text-xs text-surface-400">Loading...</div>
          </div>
        </div>
      </div>
      <div class="ml-1 hidden items-center gap-2.5 border-l border-surface-100 pl-3 sm:flex">
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-950 text-xs font-bold text-white">
          {{ strtoupper(substr(auth()->user()?->name ?? 'A', 0, 1)) }}
        </div>
        <div class="hidden min-w-0 lg:block">
          <p class="max-w-36 truncate text-xs font-bold text-surface-800">{{ auth()->user()?->name ?? 'Administrator' }}</p>
          <p class="mt-0.5 text-[9px] font-bold uppercase tracking-wider text-surface-400">{{ $isAdmin ? 'Administrator' : 'Staff member' }}</p>
        </div>
      </div>
      </div>
    </header>

    {{-- Messages tab (full-height messenger, outside the scrollable main) --}}
    <div id="tab-messages" style="display:{{ $activeTab === 'messages' ? 'flex' : 'none' }};flex:1;min-height:0;overflow:hidden;">
      <div class="flex h-full w-full">

        {{-- Left: Conversation list --}}
        <div class="admin-conversation-list w-80 min-w-0 border-r border-surface-100 flex flex-col bg-white flex-shrink-0">
          <div class="px-5 py-4 border-b border-surface-100 flex-shrink-0">
            <h2 class="text-sm font-semibold text-surface-900">Messages</h2>
            <p class="text-xs text-surface-400 mt-0.5">{{ $conversations->count() }} conversation{{ $conversations->count() !== 1 ? 's' : '' }}</p>
          </div>
          <div class="overflow-y-auto flex-1">
            @forelse($conversations as $convo)
              @php $unread = $convo->unread_count; $latest = $convo->latestMessage; @endphp
              <button type="button"
                onclick="openConversation({{ $convo->id }}, this.dataset.customerName)"
                data-convo-id="{{ $convo->id }}"
                data-customer-name="{{ $convo->customer->name }}"
                class="convo-btn w-full text-left px-4 py-3.5 border-b border-surface-100 hover:bg-surface-50 transition-colors flex items-start gap-3">
                <div class="w-10 h-10 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center text-sm font-bold flex-shrink-0">
                  {{ strtoupper(substr($convo->customer->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between gap-2">
                    <span class="text-sm {{ $unread > 0 ? 'font-bold text-surface-900' : 'font-semibold text-surface-600' }} truncate">{{ $convo->customer->name }}</span>
                    @if($unread > 0)
                      <span class="bg-red-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full flex-shrink-0">{{ $unread }}</span>
                    @endif
                  </div>
                  <p class="text-xs {{ $unread > 0 ? 'text-surface-700 font-medium' : 'text-surface-400' }} truncate mt-0.5">
                    {{ $latest?->body ?? 'No messages yet' }}
                  </p>
                  @if($convo->last_message_at)
                    <p class="text-[10px] text-surface-300 mt-0.5">{{ $convo->last_message_at->diffForHumans() }}</p>
                  @endif
                </div>
              </button>
            @empty
              <div class="flex flex-col items-center justify-center py-16 text-center px-6">
                <svg class="w-10 h-10 text-surface-200 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                <p class="text-sm text-surface-400">No conversations yet</p>
              </div>
            @endforelse
          </div>
        </div>

        {{-- Right: Thread --}}
        <div class="admin-message-thread min-w-0 flex-1 flex flex-col bg-surface-50 overflow-hidden">
          {{-- Empty state --}}
          <div id="thread-empty" class="flex-1 flex flex-col items-center justify-center text-center px-6">
            <div class="w-16 h-16 bg-brand-50 rounded-full flex items-center justify-center mb-4">
              <svg class="w-8 h-8 text-brand-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            </div>
            <p class="text-sm font-semibold text-surface-600">Select a conversation</p>
            <p class="text-xs text-surface-400 mt-1">Click a customer on the left to start chatting</p>
          </div>

          {{-- Active thread --}}
          <div id="thread-panel" style="display:none;flex-direction:column;height:100%;overflow:hidden;">
            {{-- Header --}}
            <div class="px-4 sm:px-5 py-3 border-b border-surface-100 bg-white flex-shrink-0 flex items-center gap-3">
              <button type="button" onclick="closeMobileConversation()" class="md:hidden flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-surface-500 hover:bg-surface-50" aria-label="Back to conversations">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
              </button>
              <div id="thread-avatar" class="w-9 h-9 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center text-sm font-bold flex-shrink-0"></div>
              <div class="min-w-0">
                <p id="thread-name" class="truncate text-sm font-semibold text-surface-900"></p>
                <p class="text-xs text-surface-400">Customer</p>
              </div>
            </div>

            {{-- Messages --}}
            <div id="thread-messages" class="flex-1 overflow-y-auto px-5 py-4 space-y-3" style="scroll-behavior:smooth">
              <div class="flex items-center justify-center h-full">
                <div class="w-5 h-5 border-2 border-brand-500 border-t-transparent rounded-full animate-spin"></div>
              </div>
            </div>

            {{-- Compose --}}
            <div class="border-t border-surface-100 bg-white px-4 py-3 flex-shrink-0">
              {{-- Selected-file chip --}}
              <div id="reply-attach-preview" class="hidden items-center gap-2 mb-2 rounded-xl border border-surface-200 bg-surface-50 px-3 py-2">
                <img id="reply-attach-thumb" alt="" class="hidden h-10 w-10 rounded-lg object-cover">
                <svg id="reply-attach-icon" class="hidden w-5 h-5 text-brand-600 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" d="M14 2v6h6"/>
                </svg>
                <span class="min-w-0 flex-1">
                  <span id="reply-attach-name" class="block truncate text-[12px] font-semibold text-surface-800"></span>
                  <span id="reply-attach-size" class="block text-[10px] text-surface-400"></span>
                </span>
                <button type="button" id="reply-attach-clear" aria-label="Remove attachment"
                  class="shrink-0 w-7 h-7 rounded-full hover:bg-surface-200 flex items-center justify-center text-surface-500">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>

              <form id="reply-form" method="POST" enctype="multipart/form-data" class="flex items-end gap-2">
                @csrf
                <input type="file" id="reply-attachment" name="attachment" class="hidden"
                       accept="{{ \App\Support\MessageAttachment::accept() }}">
                <button type="button" id="reply-attach-btn" aria-label="Attach a file or picture"
                  class="flex-shrink-0 w-11 h-11 rounded-full border border-surface-200 hover:bg-surface-50 flex items-center justify-center transition-colors text-surface-500">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/>
                  </svg>
                </button>
                <textarea
                  id="reply-body"
                  name="body"
                  rows="1"
                  placeholder="Type a reply&hellip;"
                  maxlength="2000"
                  class="flex-1 resize-none border border-surface-200 rounded-2xl px-4 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 transition-all overflow-y-auto"
                  style="min-height:42px;max-height:120px"
                  onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();document.getElementById('reply-form').requestSubmit();}"
                ></textarea>
                <button type="submit" aria-label="Send message"
                  class="flex-shrink-0 w-11 h-11 bg-brand-600 hover:bg-brand-700 rounded-full flex items-center justify-center transition-colors">
                  <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.269 20.876L5.999 12zm0 0h7.5"/>
                  </svg>
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- All other tabs in a scrollable wrapper --}}
    <div id="tabs-main" class="flex-1 overflow-y-auto" style="{{ $activeTab === 'messages' ? 'display:none' : '' }}">
    <main id="admin-main" tabindex="-1" class="w-full max-w-none px-4 sm:px-6 py-5 sm:py-6">

    @if (session('status'))
      <div role="status" class="bg-brand-50 border border-brand-100 text-brand-700 px-4 py-2.5 rounded-lg text-sm mb-5">
        {{ session('status') }}
      </div>
    @endif

    @if ($errors->any())
      <div role="alert" class="bg-red-50 border border-red-100 text-red-600 px-4 py-2.5 rounded-lg text-sm mb-5">
        <ul class="list-disc pl-4 space-y-0.5">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div id="admin-toast-stack" class="fixed top-4 right-4 z-[70] space-y-2 pointer-events-none" aria-live="polite" aria-atomic="true"></div>

    <!-- OVERVIEW TAB -->
    <div id="tab-overview" class="{{ $tabClass('overview', 'space-y-5') }}">
      @php
        $adminName = trim((string) auth()->user()?->name);
        $adminFirstName = $adminName !== '' ? explode(' ', $adminName)[0] : 'there';
        $greeting = match (true) {
          now()->hour < 12 => 'Good morning',
          now()->hour < 18 => 'Good afternoon',
          default => 'Good evening',
        };
        $liveOrderQueue = (int) ($orderFlowStats['pending'] ?? 0);
        $attentionTotal = $liveOrderQueue
          + (int) $pendingAppointments
          + (int) ($orderFlowStats['unpaid'] ?? 0)
          + (int) $lowStockProducts->count()
          + (int) $totalUnreadMessages;
      @endphp

      <section aria-labelledby="admin-overview-title" class="relative overflow-hidden rounded-[1.75rem] bg-brand-950 px-5 py-6 text-white shadow-[0_24px_70px_rgba(8,29,21,.18)] sm:px-7 sm:py-7">
        <div aria-hidden="true" class="absolute -right-16 -top-24 h-72 w-72 rounded-full border border-white/10 bg-white/[.035]"></div>
        <div aria-hidden="true" class="absolute -bottom-24 right-28 h-48 w-48 rounded-full border border-brand-400/20"></div>
        <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
          <div class="max-w-2xl">
            <div class="mb-4 flex flex-wrap items-center gap-2 text-[11px] font-bold uppercase tracking-[.16em] text-brand-200">
              <span class="inline-flex h-2 w-2 rounded-full bg-brand-300 shadow-[0_0_0_5px_rgba(130,189,152,.12)]"></span>
              {{ now()->format('l, F j') }}
            </div>
            <h1 id="admin-overview-title" class="font-display text-3xl font-semibold leading-tight sm:text-4xl">{{ $greeting }}, {{ $adminFirstName }}.</h1>
            <p class="mt-3 max-w-xl text-sm leading-6 text-brand-100/80 sm:text-[15px]">
              Keep today&rsquo;s customer work moving. You have {{ number_format($attentionTotal) }} item{{ $attentionTotal === 1 ? '' : 's' }} across the live operations queues.
            </p>
          </div>
          <div class="flex flex-wrap gap-2.5">
            <a href="{{ route('admin.reports.overview', array_filter(['sales_from' => $salesFrom ?? request('sales_from'), 'sales_to' => $salesTo ?? request('sales_to')])) }}"
               class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-white/15 bg-white/10 px-4 text-sm font-semibold text-white transition hover:bg-white/15">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-6m4 6V7m4 10v-3M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"/></svg>
              View report
            </a>
            <a href="{{ route('admin.service-scheduling') }}"
               class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-white px-4 text-sm font-bold text-brand-950 shadow-sm transition hover:bg-brand-50">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"/></svg>
              Open schedule
            </a>
          </div>
        </div>
      </section>

      <section aria-labelledby="needs-attention-title">
        <div class="mb-3 flex items-end justify-between gap-4">
          <div>
            <p class="text-[10px] font-bold uppercase tracking-[.16em] text-brand-600">Live operations</p>
            <h2 id="needs-attention-title" class="mt-1 font-display text-xl font-semibold text-surface-900">Needs attention</h2>
          </div>
          <p class="hidden text-xs text-surface-400 sm:block">Select a queue to start working</p>
        </div>
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
          <a href="{{ $tabUrl('orders') }}" class="group min-h-[122px] rounded-2xl border border-amber-200/70 bg-amber-50/70 p-4 text-left transition hover:-translate-y-0.5 hover:bg-amber-50 hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
              <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
              </span>
              <svg class="h-4 w-4 text-amber-500 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
            </div>
            <p class="mt-4 text-2xl font-bold text-amber-900">{{ $liveOrderQueue }}</p>
            <p class="mt-0.5 text-xs font-semibold text-amber-800">Orders to process</p>
            @if($ordersAwaitingConfirmation > 0)
              <p class="mt-1 text-[10px] text-amber-700">{{ $ordersAwaitingConfirmation }} awaiting receipt</p>
            @endif
          </a>

          <a href="{{ $tabUrl('appointments') }}" class="group min-h-[122px] rounded-2xl border border-blue-200/70 bg-blue-50/70 p-4 text-left transition hover:-translate-y-0.5 hover:bg-blue-50 hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
              <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-100 text-blue-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              </span>
              <svg class="h-4 w-4 text-blue-500 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
            </div>
            <p class="mt-4 text-2xl font-bold text-blue-900">{{ $pendingAppointments }}</p>
            <p class="mt-0.5 text-xs font-semibold text-blue-800">Active appointments</p>
            <p class="mt-1 text-[10px] text-blue-700">
              {{ $todayAppointments }} today
              @if($overdueAppointments > 0)
                &middot; {{ $overdueAppointments }} overdue
              @endif
            </p>
          </a>

          <a href="{{ $tabUrl($isAdmin ? 'payment' : 'orders') }}" class="group min-h-[122px] rounded-2xl border border-rose-200/70 bg-rose-50/70 p-4 text-left transition hover:-translate-y-0.5 hover:bg-rose-50 hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
              <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-100 text-rose-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h2m4 0h4M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"/></svg>
              </span>
              <svg class="h-4 w-4 text-rose-500 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
            </div>
            <p class="mt-4 text-2xl font-bold text-rose-900">{{ $orderFlowStats['unpaid'] ?? 0 }}</p>
            <p class="mt-0.5 text-xs font-semibold text-rose-800">Unpaid orders</p>
            <p class="mt-1 text-[10px] text-rose-700">Review billing status</p>
          </a>

          <a href="{{ $tabUrl('products') }}" class="group min-h-[122px] rounded-2xl border {{ $lowStockProducts->count() > 0 ? 'border-orange-200/70 bg-orange-50/70 hover:bg-orange-50' : 'border-brand-200/70 bg-brand-50/70 hover:bg-brand-50' }} p-4 text-left transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
              <span class="flex h-9 w-9 items-center justify-center rounded-xl {{ $lowStockProducts->count() > 0 ? 'bg-orange-100 text-orange-700' : 'bg-brand-100 text-brand-700' }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
              </span>
              <svg class="h-4 w-4 {{ $lowStockProducts->count() > 0 ? 'text-orange-500' : 'text-brand-500' }} transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
            </div>
            <p class="mt-4 text-2xl font-bold {{ $lowStockProducts->count() > 0 ? 'text-orange-900' : 'text-brand-900' }}">{{ $lowStockProducts->count() }}</p>
            <p class="mt-0.5 text-xs font-semibold {{ $lowStockProducts->count() > 0 ? 'text-orange-800' : 'text-brand-800' }}">Low-stock items</p>
            <p class="mt-1 text-[10px] {{ $lowStockProducts->count() > 0 ? 'text-orange-700' : 'text-brand-700' }}">{{ $lowStockProducts->count() > 0 ? 'Reorder soon' : 'Inventory is healthy' }}</p>
          </a>

          <a href="{{ $tabUrl('messages') }}" class="group col-span-2 min-h-[122px] rounded-2xl border border-violet-200/70 bg-violet-50/70 p-4 text-left transition hover:-translate-y-0.5 hover:bg-violet-50 hover:shadow-md lg:col-span-1">
            <div class="flex items-start justify-between gap-3">
              <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-100 text-violet-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
              </span>
              <svg class="h-4 w-4 text-violet-500 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
            </div>
            <p class="mt-4 text-2xl font-bold text-violet-900">{{ $totalUnreadMessages }}</p>
            <p class="mt-0.5 text-xs font-semibold text-violet-800">Unread messages</p>
            <p class="mt-1 text-[10px] text-violet-700">Reply to customers</p>
          </a>
        </div>
      </section>

      <section aria-labelledby="business-snapshot-title" class="rounded-2xl border border-surface-100 bg-white p-4 shadow-[0_10px_35px_rgba(18,52,38,.04)] sm:p-5">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-2">
          <div>
            <p class="text-[10px] font-bold uppercase tracking-[.16em] text-brand-600">Performance</p>
            <h2 id="business-snapshot-title" class="mt-1 font-display text-xl font-semibold text-surface-900">Business snapshot</h2>
          </div>
          <p class="text-xs text-surface-400">{{ ($salesFrom || $salesTo) ? 'Filtered reporting period' : 'All-time reporting period' }}</p>
        </div>
        <div class="grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-surface-100 bg-surface-100 lg:grid-cols-4">
          <div class="bg-white p-4 sm:p-5">
            <p class="text-[10px] font-bold uppercase tracking-wider text-surface-400">Paid revenue</p>
            <p class="mt-2 text-xl font-bold text-brand-800 sm:text-2xl">&#8369;{{ number_format($recognizedRevenue, 2) }}</p>
            <p class="mt-1 text-[11px] text-surface-400">Paid, non-cancelled orders</p>
          </div>
          <div class="bg-white p-4 sm:p-5">
            <p class="text-[10px] font-bold uppercase tracking-wider text-surface-400">Order value</p>
            <p class="mt-2 text-xl font-bold text-surface-900 sm:text-2xl">&#8369;{{ number_format($totalSales, 2) }}</p>
            <p class="mt-1 text-[11px] text-surface-400">{{ number_format($totalOrders) }} order{{ $totalOrders === 1 ? '' : 's' }} recorded</p>
          </div>
          <div class="bg-white p-4 sm:p-5">
            <p class="text-[10px] font-bold uppercase tracking-wider text-surface-400">Fulfilled orders</p>
            <p class="mt-2 text-xl font-bold text-surface-900 sm:text-2xl">{{ number_format($deliveredOrders) }}</p>
            <p class="mt-1 text-[11px] text-surface-400">Delivered or completed</p>
          </div>
          <div class="bg-white p-4 sm:p-5">
            <p class="text-[10px] font-bold uppercase tracking-wider text-surface-400">Customer rating</p>
            <p class="mt-2 text-xl font-bold text-surface-900 sm:text-2xl">
              @if($avgRating){{ number_format((float) $avgRating, 1) }}<span class="text-sm font-medium text-amber-500"> / 5</span>@else<span class="text-surface-300">Not rated</span>@endif
            </p>
            <p class="mt-1 text-[11px] text-surface-400">From {{ number_format($feedbacks->total()) }} review{{ $feedbacks->total() === 1 ? '' : 's' }}</p>
          </div>
        </div>
      </section>

      <section aria-label="Priority work queues" class="grid grid-cols-1 gap-5 xl:grid-cols-2">
        <div class="overflow-hidden rounded-2xl border border-surface-100 bg-white shadow-[0_10px_35px_rgba(18,52,38,.04)]">
          <div class="flex items-center justify-between gap-3 border-b border-surface-100 px-5 py-4">
            <div>
              <h2 class="font-display text-lg font-semibold text-surface-900">Priority appointments</h2>
              <p class="mt-0.5 text-xs text-surface-400">Overdue and nearest schedules first</p>
            </div>
            <a href="{{ route('admin.service-scheduling') }}" class="rounded-lg px-2 py-1 text-xs font-bold text-brand-700 hover:bg-brand-50">View all</a>
          </div>
          <div class="divide-y divide-surface-100">
            @forelse($priorityAppointments as $priorityAppointment)
              @php
                $priorityApptAt = $priorityAppointment->appointment_at;
                $priorityApptOverdue = $priorityApptAt && $priorityApptAt->isPast();
              @endphp
              <a href="{{ route('admin.appointments.show', $priorityAppointment) }}" class="group flex min-h-[72px] items-center gap-3 px-4 py-3 transition hover:bg-surface-50 sm:px-5">
                <div class="flex h-11 w-11 shrink-0 flex-col items-center justify-center rounded-xl {{ $priorityApptOverdue ? 'bg-rose-50 text-rose-700' : 'bg-brand-50 text-brand-700' }}">
                  <span class="text-[9px] font-bold uppercase tracking-wider">{{ $priorityApptAt?->format('M') ?? 'TBD' }}</span>
                  <span class="text-base font-bold leading-none">{{ $priorityApptAt?->format('d') ?? '--' }}</span>
                </div>
                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-2">
                    <p class="truncate text-sm font-bold text-surface-900">{{ $priorityAppointment->user->name ?? 'Customer' }}</p>
                    @if($priorityApptOverdue)<span class="shrink-0 rounded-full bg-rose-50 px-2 py-0.5 text-[9px] font-bold uppercase text-rose-700">Overdue</span>@endif
                  </div>
                  <p class="mt-0.5 truncate text-xs text-surface-500">{{ $priorityAppointment->serviceType->name ?? 'Service visit' }} &middot; {{ $priorityApptAt?->format('g:i A') ?? 'Time pending' }}</p>
                </div>
                <svg class="h-4 w-4 shrink-0 text-surface-300 transition group-hover:translate-x-0.5 group-hover:text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
              </a>
            @empty
              <div class="px-5 py-10 text-center">
                <p class="text-sm font-semibold text-surface-600">Schedule is clear</p>
                <p class="mt-1 text-xs text-surface-400">No active appointments need attention.</p>
              </div>
            @endforelse
          </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-surface-100 bg-white shadow-[0_10px_35px_rgba(18,52,38,.04)]">
          <div class="flex items-center justify-between gap-3 border-b border-surface-100 px-5 py-4">
            <div>
              <h2 class="font-display text-lg font-semibold text-surface-900">Orders requiring action</h2>
              <p class="mt-0.5 text-xs text-surface-400">New, confirmed, and delivery-stage orders</p>
            </div>
            <a href="{{ route('admin.ordering-delivery') }}" class="rounded-lg px-2 py-1 text-xs font-bold text-brand-700 hover:bg-brand-50">View all</a>
          </div>
          <div class="divide-y divide-surface-100">
            @forelse($priorityOrders as $priorityOrder)
              @php
                $priorityOrderTone = match($priorityOrder->status) {
                  'pending' => 'bg-amber-50 text-amber-700',
                  'confirmed' => 'bg-blue-50 text-blue-700',
                  'out_for_delivery' => 'bg-violet-50 text-violet-700',
                  default => 'bg-brand-50 text-brand-700',
                };
              @endphp
              <a href="{{ route('admin.orders.show', $priorityOrder) }}" class="group flex min-h-[72px] items-center gap-3 px-4 py-3 transition hover:bg-surface-50 sm:px-5">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-surface-50 text-xs font-bold text-surface-700 ring-1 ring-surface-100">
                  {{ strtoupper(substr($priorityOrder->user->name ?? '?', 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-2">
                    <p class="truncate text-sm font-bold text-surface-900">{{ $priorityOrder->order_number }}</p>
                    <span class="shrink-0 rounded-full px-2 py-0.5 text-[9px] font-bold uppercase {{ $priorityOrderTone }}">{{ str_replace('_', ' ', $priorityOrder->status) }}</span>
                  </div>
                  <p class="mt-0.5 truncate text-xs text-surface-500">{{ $priorityOrder->user->name ?? 'Customer' }} &middot; &#8369;{{ number_format((float) $priorityOrder->total_amount, 2) }}</p>
                </div>
                <svg class="h-4 w-4 shrink-0 text-surface-300 transition group-hover:translate-x-0.5 group-hover:text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
              </a>
            @empty
              <div class="px-5 py-10 text-center">
                <p class="text-sm font-semibold text-surface-600">Order queue is clear</p>
                <p class="mt-1 text-xs text-surface-400">No active orders need attention.</p>
              </div>
            @endforelse
          </div>
        </div>
      </section>

      <!-- Filters -->
      <div class="bg-white rounded-2xl border border-surface-100 p-5">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
          <div>
            <h2 class="font-display text-lg font-semibold text-surface-900">Reporting window</h2>
            <p class="text-xs text-surface-400 mt-0.5">Filter financial metrics without changing the live operations queues.</p>
          </div>
          <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-wrap items-end gap-2">
            <input type="hidden" name="tab" value="overview">
            <div>
              <label class="block text-[10px] font-medium text-surface-400 mb-1">From</label>
              <input type="date" name="sales_from" value="{{ $salesFrom ?? request('sales_from') }}"
                     class="border border-surface-200 rounded-lg px-3 py-1.5 text-xs outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 text-surface-700 transition-colors">
            </div>
            <div>
              <label class="block text-[10px] font-medium text-surface-400 mb-1">To</label>
              <input type="date" name="sales_to" value="{{ $salesTo ?? request('sales_to') }}"
                     class="border border-surface-200 rounded-lg px-3 py-1.5 text-xs outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 text-surface-700 transition-colors">
            </div>
            <button class="bg-surface-900 text-white rounded-lg px-4 py-1.5 text-xs font-medium hover:bg-surface-800 transition-colors">Apply</button>
            <a href="{{ route('admin.reports.overview', array_filter(['sales_from' => $salesFrom ?? request('sales_from'), 'sales_to' => $salesTo ?? request('sales_to')])) }}"
               class="inline-flex items-center gap-1.5 border border-surface-200 bg-white text-surface-600 rounded-lg px-4 py-1.5 text-xs font-medium hover:bg-surface-50 transition-colors">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-6m4 6V7m4 10v-3M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"/></svg>
              View Report
            </a>
            <a href="{{ route('admin.dashboard', ['tab' => 'overview']) }}" class="text-xs text-surface-400 hover:text-surface-700 py-1.5 transition-colors">Reset</a>
          </form>
        </div>
      </div>

      <!-- System readiness -->
      <details class="group overflow-hidden rounded-2xl border border-surface-100 bg-white">
        <summary class="flex cursor-pointer list-none flex-col gap-2 px-5 py-4 marker:hidden sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 class="text-sm font-semibold text-surface-900">System readiness</h2>
            <p class="text-xs text-surface-400 mt-0.5">Configuration and module status for administrators.</p>
          </div>
          <span class="inline-flex items-center gap-2 self-start rounded-full bg-brand-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-brand-700 sm:self-auto">
            View {{ count($moduleCards) }} modules
            <svg class="h-3.5 w-3.5 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
          </span>
        </summary>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 p-5">
          @foreach($moduleCards as $module)
            @php
              $tone = $module['tone'] ?? 'brand';
              $toneClass = match($tone) {
                'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                'blue' => 'bg-blue-50 text-blue-700 border-blue-100',
                'amber' => 'bg-amber-50 text-amber-700 border-amber-100',
                'indigo' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
                'sky' => 'bg-sky-50 text-sky-700 border-sky-100',
                'purple' => 'bg-purple-50 text-purple-700 border-purple-100',
                'green' => 'bg-brand-50 text-brand-700 border-brand-100',
                'rose' => 'bg-rose-50 text-rose-700 border-rose-100',
                default => 'bg-brand-50 text-brand-700 border-brand-100',
              };
            @endphp
            <div class="rounded-xl border border-surface-100 bg-surface-50 p-4 hover:bg-white hover:shadow-sm transition-all">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <h3 class="text-sm font-semibold text-surface-900">{{ $module['name'] }}</h3>
                  <p class="mt-1 text-xs leading-5 text-surface-500">{{ $module['description'] }}</p>
                </div>
                <span class="shrink-0 rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ $toneClass }}">{{ $module['status'] }}</span>
              </div>
              <div class="mt-3 flex items-center justify-between gap-3">
                <span class="text-xs font-semibold text-surface-700">{{ $module['metric'] }}</span>
                @if(!empty($module['tab']))
                  <a href="{{ $tabUrl($module['tab']) }}" class="text-xs font-semibold text-brand-700 hover:text-brand-800">Open</a>
                @else
                  <span class="text-xs font-medium text-surface-400">Customer side</span>
                @endif
              </div>
            </div>
          @endforeach
        </div>
      </details>

      <!-- Legacy KPI Cards (replaced by the business snapshot above) -->
      <div class="hidden" aria-hidden="true">
        <div class="bg-surface-900 rounded-xl p-5 lg:col-span-2 text-white">
          <p class="text-xs font-medium text-surface-400">Total Sales</p>
          <p class="text-2xl font-display font-bold mt-1">PHP {{ number_format($totalSales, 2) }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 border border-surface-100">
          <p class="text-[10px] font-medium text-surface-400 uppercase tracking-wider">Total Orders</p>
          <p class="text-2xl font-bold text-surface-900 mt-2">{{ $totalOrders }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 border border-surface-100">
          <p class="text-[10px] font-medium text-surface-400 uppercase tracking-wider">Pending Orders</p>
          <p class="text-2xl font-bold text-amber-600 mt-2">{{ $pendingOrders }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 border border-surface-100">
          <p class="text-[10px] font-medium text-surface-400 uppercase tracking-wider">Pending Appts</p>
          <p class="text-2xl font-bold text-blue-600 mt-2">{{ $pendingAppointments }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 border border-surface-100">
          <p class="text-[10px] font-medium text-surface-400 uppercase tracking-wider">Total Users</p>
          <p class="text-2xl font-bold text-surface-900 mt-2">{{ $totalUsers }}</p>
        </div>
      </div>

      <!-- Charts Row 1 -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="bg-white rounded-xl border border-surface-100 p-5">
          <h2 class="text-sm font-semibold text-surface-900 mb-5">Monthly order value (last 6 months)</h2>
          @php $maxMonthly = max(1, (float) $monthlySales->max('total')); @endphp
          <div class="grid grid-cols-6 gap-3 items-end min-h-[160px]">
            @foreach ($monthlySales as $row)
              @php $heightPercent = (int) round(($row['total'] / $maxMonthly) * 100); @endphp
              <div class="flex flex-col items-center gap-2 group relative" tabindex="0" role="img" aria-label="{{ $row['label'] }} order value: PHP {{ number_format($row['total'], 2) }}">
                <div aria-hidden="true" class="absolute -top-8 bg-surface-900 text-white text-[10px] py-1 px-2 rounded opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-10">
                  PHP {{ number_format($row['total'], 2) }}
                </div>
                <div class="w-full bg-surface-50 rounded-lg h-36 flex items-end overflow-hidden">
                  <div class="w-full bg-brand-500 rounded-lg transition-all duration-700" style="height: {{ max(8, $heightPercent) }}%;"></div>
                </div>
                <p class="text-[10px] font-medium text-surface-400 uppercase">{{ $row['label'] }}</p>
              </div>
            @endforeach
          </div>
        </div>

        <div class="bg-white rounded-xl border border-surface-100 overflow-hidden flex flex-col">
          <div class="px-5 py-4 border-b border-surface-100">
            <h2 class="text-sm font-semibold text-surface-900">Order value by status</h2>
          </div>
          <div class="overflow-x-auto flex-1">
            <table class="w-full text-xs">
              <thead>
                <tr class="text-left text-surface-400 text-[10px] uppercase tracking-wider border-b border-surface-100">
                  <th class="px-5 py-3 font-medium">Status</th>
                  <th class="px-5 py-3 font-medium text-right">Orders</th>
                  <th class="px-5 py-3 font-medium text-right">Sales</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-surface-100">
                @forelse ($salesByStatus as $row)
                  <tr class="hover:bg-surface-50 transition-colors">
                    <td class="px-5 py-3">
                      <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-surface-100 text-surface-700">
                        {{ ucfirst(str_replace('_', ' ', $row->status)) }}
                      </span>
                    </td>
                    <td class="px-5 py-3 text-right font-medium text-surface-700">{{ $row->order_count }}</td>
                    <td class="px-5 py-3 text-right font-semibold text-surface-900">PHP {{ number_format((float) $row->sales_total, 2) }}</td>
                  </tr>
                @empty
                  <tr><td class="px-5 py-6 text-surface-400 text-center" colspan="3">No order data yet.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Charts Row 2 -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="bg-white rounded-xl border border-surface-100 p-5">
          <h2 class="text-sm font-semibold text-surface-900 mb-4">Week vs week order value</h2>
          <div class="grid grid-cols-2 gap-4">
            <div class="bg-surface-50 rounded-lg p-4 border border-surface-100">
              <p class="text-[10px] text-surface-400 font-medium uppercase tracking-wider mb-1">This week</p>
              <p class="text-lg font-bold text-brand-600">PHP {{ number_format($thisWeekSales, 2) }}</p>
            </div>
            <div class="bg-surface-50 rounded-lg p-4 border border-surface-100">
              <p class="text-[10px] text-surface-400 font-medium uppercase tracking-wider mb-1">Last week</p>
              <p class="text-lg font-bold text-surface-600">PHP {{ number_format($lastWeekSales, 2) }}</p>
            </div>
          </div>
          @if ($weekSalesDeltaPct !== null)
            <div class="mt-4 flex items-center gap-2">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $weekSalesDeltaPct >= 0 ? 'bg-brand-50 text-brand-700' : 'bg-red-50 text-red-600' }}">
                {{ $weekSalesDeltaPct >= 0 ? '+' : '' }}{{ $weekSalesDeltaPct }}%
              </span>
              <span class="text-xs text-surface-400">vs last week</span>
            </div>
          @else
            <p class="mt-4 text-xs text-surface-400">Compare appears once last week has sales.</p>
          @endif
        </div>

        <div class="bg-white rounded-xl border border-surface-100 overflow-hidden flex flex-col">
          <div class="px-5 py-4 border-b border-surface-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-surface-900">Top Products</h2>
            <span class="text-[10px] font-medium text-surface-400 uppercase tracking-wider bg-surface-50 px-2 py-0.5 rounded">By Vol</span>
          </div>
          <div class="overflow-x-auto flex-1">
            <table class="w-full text-xs">
              <thead>
                <tr class="text-left text-surface-400 text-[10px] uppercase tracking-wider border-b border-surface-100">
                  <th class="px-5 py-3 font-medium">Product</th>
                  <th class="px-5 py-3 font-medium text-right">Units Sold</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-surface-100">
                @forelse ($topProducts as $row)
                  <tr class="hover:bg-surface-50 transition-colors">
                    <td class="px-5 py-3 font-medium text-surface-800">{{ $row['name'] }}</td>
                    <td class="px-5 py-3 text-right font-semibold text-surface-700">{{ $row['qty'] }}</td>
                  </tr>
                @empty
                  <tr><td class="px-5 py-6 text-surface-400 text-center" colspan="2">No line items recorded yet.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- APPOINTMENTS TAB -->
    <div id="tab-appointments" class="{{ $tabClass('appointments') }}">
      <div class="bg-white rounded-xl border border-surface-100 overflow-hidden">
        <div class="p-5 border-b border-surface-100">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <div>
              <h2 class="text-sm font-semibold text-surface-900">Appointments</h2>
              <p class="text-xs text-surface-400 mt-0.5">Confirm new bookings first, then manage upcoming visits and completed work.</p>
            </div>
          </div>
          <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-wrap items-end gap-2">
            <input type="hidden" name="tab" value="appointments">
            <div>
              <label class="block text-[10px] font-medium text-surface-400 mb-1">Status</label>
              <select name="appt_status" class="border border-surface-200 rounded-lg px-3 py-1.5 text-xs outline-none focus:border-brand-500 text-surface-600 min-w-[120px]">
                <option value="">All</option>
                @foreach (['scheduled', 'confirmed', 'completed', 'cancelled'] as $st)
                  <option value="{{ $st }}" {{ ($apptStatus ?? '') === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                @endforeach
              </select>
            </div>
            <div class="relative flex-1 max-w-[240px]">
              <label class="block text-[10px] font-medium text-surface-400 mb-1">Search</label>
              <div class="relative">
                <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-surface-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input type="search" name="appt_q" value="{{ $apptQ ?? '' }}" placeholder="Name, email, service..."
                       class="pl-8 pr-3 py-1.5 border border-surface-200 rounded-lg text-xs outline-none focus:border-brand-500 w-full transition-colors">
              </div>
            </div>
            <button type="submit" class="bg-surface-900 text-white rounded-lg px-4 py-1.5 text-xs font-medium hover:bg-surface-800 transition-colors">Filter</button>
            <a href="{{ route('admin.dashboard', ['tab' => 'appointments']) }}" class="text-xs text-surface-400 hover:text-surface-700 py-1.5">Reset</a>
          </form>
        </div>

        <div class="divide-y divide-surface-100 md:hidden">
          @forelse ($appointments as $appt)
            @php
              $mobileApptBadge = match($appt->status) {
                'scheduled' => 'bg-amber-50 text-amber-700',
                'confirmed' => 'bg-blue-50 text-blue-700',
                'completed' => 'bg-brand-50 text-brand-700',
                'cancelled' => 'bg-red-50 text-red-600',
                default => 'bg-surface-50 text-surface-600',
              };
            @endphp
            <article class="p-4">
              <div class="flex items-start gap-3">
                <div class="flex h-11 w-11 shrink-0 flex-col items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                  <span class="text-[9px] font-bold uppercase">{{ $appt->appointment_at?->format('M') ?? 'TBD' }}</span>
                  <span class="text-base font-bold leading-none">{{ $appt->appointment_at?->format('d') ?? '--' }}</span>
                </div>
                <div class="min-w-0 flex-1">
                  <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                      <h3 class="truncate text-sm font-bold text-surface-900">{{ $appt->user->name ?? 'Customer' }}</h3>
                      <p class="mt-0.5 truncate text-xs text-surface-500">{{ $appt->serviceType->name ?? 'Service visit' }}</p>
                    </div>
                    <span class="shrink-0 rounded-full px-2 py-1 text-[9px] font-bold uppercase {{ $mobileApptBadge }}">{{ $appt->status }}</span>
                  </div>
                  <p class="mt-2 text-xs text-surface-500">{{ $appt->appointment_at?->format('M j, Y \a\t g:i A') ?? 'Schedule pending' }}</p>
                  <div class="mt-3 flex items-center justify-between gap-3 border-t border-surface-100 pt-3">
                    <span class="text-[10px] font-bold uppercase tracking-wide {{ ($appt->payment_status ?? 'unpaid') === 'paid' ? 'text-brand-700' : 'text-rose-600' }}">{{ ucfirst($appt->payment_status ?? 'unpaid') }}</span>
                    <a href="{{ route('admin.appointments.show', $appt) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-950 px-4 text-sm font-bold text-white">Manage</a>
                  </div>
                </div>
              </div>
            </article>
          @empty
            <div class="px-5 py-10 text-center text-sm text-surface-400">No appointments match your filters.</div>
          @endforelse
        </div>

        <div class="hidden overflow-x-auto md:block">
          <table class="w-full text-xs whitespace-nowrap">
            <thead>
              <tr class="text-left text-surface-400 text-[10px] uppercase tracking-wider border-b border-surface-100">
                <th class="px-5 py-3 font-medium">Customer</th>
                <th class="px-5 py-3 font-medium">Service</th>
                <th class="px-5 py-3 font-medium">Date & Time</th>
                <th class="px-5 py-3 font-medium">Status</th>
                <th class="px-5 py-3 font-medium text-right">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
              @forelse ($appointments as $appt)
                @php
                  $apptBadge = match($appt->status) {
                    'scheduled' => 'bg-amber-50 text-amber-700 border-amber-100',
                    'confirmed' => 'bg-blue-50 text-blue-700 border-blue-100',
                    'completed' => 'bg-brand-50 text-brand-700 border-brand-100',
                    'cancelled' => 'bg-red-50 text-red-600 border-red-100',
                    default     => 'bg-surface-50 text-surface-600 border-surface-200',
                  };
                @endphp
                <tr class="hover:bg-surface-50 transition-colors">
                  <td class="px-5 py-3">
                    <div class="flex items-center gap-2.5">
                      <div class="h-7 w-7 rounded-full bg-brand-50 text-brand-600 flex items-center justify-center text-[10px] font-bold border border-brand-100 shrink-0">
                        {{ strtoupper(substr($appt->user->name ?? '?', 0, 1)) }}
                      </div>
                      <div>
                        <p class="font-medium text-surface-900">{{ $appt->user->name ?? 'N/A' }}</p>
                        <p class="text-[10px] text-surface-400">{{ $appt->user->email ?? '' }}</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-5 py-3 text-surface-700 font-medium">{{ $appt->serviceType->name ?? 'N/A' }}</td>
                  <td class="px-5 py-3 text-surface-500">
                    {{ $appt->appointment_at ? \Carbon\Carbon::parse($appt->appointment_at)->format('M d, Y · g:i A') : 'N/A' }}
                  </td>
                  <td class="px-5 py-3">
                    <span class="admin-status-badge px-2 py-0.5 rounded text-[10px] font-medium border {{ $apptBadge }}">{{ ucfirst($appt->status) }}</span>
                    <div class="mt-1">
                      <span class="admin-payment-badge text-[9px] font-semibold border px-1.5 py-0.5 rounded uppercase tracking-wide
                        {{ $appt->payment_status === 'paid' ? 'bg-brand-50 text-brand-700 border-brand-100' : 'bg-surface-50 text-surface-500 border-surface-200' }}">
                        {{ ucfirst($appt->payment_status ?? 'unpaid') }}
                      </span>
                    </div>
                  </td>
                  <td class="px-5 py-3">
                    <div class="flex items-center justify-end gap-1.5">
                      {{-- View detail --}}
                      <button type="button"
                        onclick="openApptDetail({{ json_encode([
                          'id'             => $appt->id,
                          'status'         => $appt->status,
                          'payment_status' => $appt->payment_status ?? 'unpaid',
                          'appointment_amount' => number_format((float) ($appt->appointment_amount ?? $appt->serviceType->default_fee ?? 0), 2),
                          'service'        => $appt->serviceType->name ?? 'N/A',
                          'appointment_at' => $appt->appointment_at ? \Carbon\Carbon::parse($appt->appointment_at)->format('D, M j, Y \a\t g:i A') : 'N/A',
                          'notes'          => $appt->notes ?? '',
                          'customer_name'  => $appt->user->name ?? 'N/A',
                          'customer_email' => $appt->user->email ?? '',
                          'feedback_rating' => $appt->feedback?->rating,
                          'feedback_comment'=> $appt->feedback?->comment ?? '',
                        ]) }})"
                        class="text-surface-300 hover:text-brand-600 transition-colors" title="View Details">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                      </button>

                      {{-- Status + payment form --}}
                      <form method="POST" action="{{ route('admin.appointments.status', $appt) }}" class="admin-status-form flex items-center gap-1.5" data-detail-url="{{ route('admin.appointments.show', $appt) }}">
                        @csrf
                        @method('PUT')
                        <div class="flex flex-col gap-1 min-w-[90px]">
                          <select name="status" class="border border-surface-200 rounded px-2 py-0.5 text-[10px] text-surface-600 outline-none focus:border-brand-500 w-full" {{ $isStaffOrAdmin ? '' : 'disabled' }}>
                            @foreach (['scheduled','confirmed','completed','cancelled'] as $st)
                              <option value="{{ $st }}" {{ $appt->status === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                            @endforeach
                          </select>
                          <select name="payment_status" class="border border-surface-200 rounded px-2 py-0.5 text-[10px] {{ $appt->payment_status === 'paid' ? 'text-brand-600 bg-brand-50 font-medium' : 'text-surface-600' }} outline-none focus:border-brand-500 w-full" {{ $isStaffOrAdmin ? '' : 'disabled' }}>
                            <option value="unpaid" {{ ($appt->payment_status ?? 'unpaid') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                            <option value="paid" {{ $appt->payment_status === 'paid' ? 'selected' : '' }}>Paid</option>
                          </select>
                        </div>
                        @if($isStaffOrAdmin)
                          <button class="bg-brand-600 text-white rounded px-2.5 py-2 text-[10px] font-medium hover:bg-brand-700 disabled:opacity-70 disabled:cursor-wait transition-colors h-full" data-saving-label="Saving...">Save</button>
                        @endif
                      </form>

                      @if($isStaffOrAdmin)
                        <form method="POST" action="{{ route('admin.appointments.archive', $appt) }}" onsubmit="return confirm('Archive this appointment?');">
                          @csrf
                          @method('PUT')
                          <button class="p-1 text-surface-300 hover:text-red-500 rounded transition-colors" title="Archive">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                          </button>
                        </form>
                      @endif
                    </div>
                  </td>
                </tr>
              @empty
                <tr><td class="px-5 py-8 text-surface-400 text-center" colspan="5">No appointments match your filters.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if (method_exists($appointments, 'links'))
          <div class="p-4 border-t border-surface-100">
            {{ $appointments->appends(['tab' => 'appointments', 'appt_status' => request('appt_status'), 'appt_q' => request('appt_q')])->links() }}
          </div>
        @endif
      </div>
    </div>

    <!-- ORDERS TAB -->
    <div id="tab-orders" class="{{ $tabClass('orders') }}">
      <div class="bg-white rounded-xl border border-surface-100 overflow-hidden">
        <div class="p-5 border-b border-surface-100">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <div>
              <h2 class="text-sm font-semibold text-surface-900">Orders &amp; Delivery</h2>
              <p class="text-xs text-surface-400 mt-0.5">Process new orders first, then manage billing, fulfilment, and delivery proof.</p>
            </div>
            <a href="{{ route('admin.reports.orders-csv', array_filter(['order_status' => request('order_status'), 'order_q' => request('order_q')])) }}"
               class="inline-flex items-center gap-1.5 text-xs font-medium border border-surface-200 bg-white px-3 py-1.5 rounded-lg text-surface-600 hover:bg-surface-50 transition-colors">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
              Export CSV
            </a>
          </div>

          <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            <div class="rounded-lg border border-amber-100 bg-amber-50 px-3 py-2">
              <p class="text-[10px] font-semibold uppercase tracking-wide text-amber-700">Order Queue</p>
              <p class="mt-1 text-lg font-bold text-amber-700">{{ $orderFlowStats['pending'] ?? 0 }}</p>
            </div>
            <div class="rounded-lg border border-blue-100 bg-blue-50 px-3 py-2">
              <p class="text-[10px] font-semibold uppercase tracking-wide text-blue-700">Delivery Flow</p>
              <p class="mt-1 text-lg font-bold text-blue-700">{{ $orderFlowStats['for_delivery'] ?? 0 }}</p>
            </div>
            <div class="rounded-lg border border-brand-100 bg-brand-50 px-3 py-2">
              <p class="text-[10px] font-semibold uppercase tracking-wide text-brand-700">Completed</p>
              <p class="mt-1 text-lg font-bold text-brand-700">{{ $orderFlowStats['completed'] ?? 0 }}</p>
            </div>
            <div class="rounded-lg border border-red-100 bg-red-50 px-3 py-2">
              <p class="text-[10px] font-semibold uppercase tracking-wide text-red-600">Unpaid Billing</p>
              <p class="mt-1 text-lg font-bold text-red-600">{{ $orderFlowStats['unpaid'] ?? 0 }}</p>
            </div>
          </div>

          <div class="flex flex-col xl:flex-row xl:items-end justify-between gap-3">
            <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-wrap items-end gap-2 flex-1">
              <input type="hidden" name="tab" value="orders">
              <div>
                <label class="block text-[10px] font-medium text-surface-400 mb-1">Status</label>
                <select name="order_status" class="border border-surface-200 rounded-lg px-3 py-1.5 text-xs outline-none focus:border-brand-500 text-surface-600 min-w-[120px]">
                  <option value="">All</option>
                  @foreach (['pending', 'confirmed', 'out_for_delivery', 'delivered', 'completed', 'cancelled'] as $st)
                    <option value="{{ $st }}" {{ request('order_status') === $st ? 'selected' : '' }}>
                      {{ ucfirst(str_replace('_', ' ', $st)) }}
                    </option>
                  @endforeach
                </select>
              </div>
              <div class="relative flex-1 max-w-[240px]">
                <label class="block text-[10px] font-medium text-surface-400 mb-1">Search</label>
                <div class="relative">
                  <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-surface-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                  <input type="search" name="order_q" value="{{ request('order_q') }}" placeholder="Order #, name, email"
                         class="pl-8 pr-3 py-1.5 border border-surface-200 rounded-lg text-xs outline-none focus:border-brand-500 w-full transition-colors">
                </div>
              </div>
              <button type="submit" class="bg-surface-900 text-white rounded-lg px-4 py-1.5 text-xs font-medium hover:bg-surface-800 transition-colors">Filter</button>
              <a href="{{ route('admin.dashboard', ['tab' => 'orders']) }}" class="text-xs text-surface-400 hover:text-surface-700 py-1.5">Reset</a>
            </form>

            <form method="POST" action="{{ route('admin.orders.bulk-status') }}" class="flex items-center gap-2 bg-surface-50 p-1.5 rounded-lg border border-surface-100" id="admin-bulk-orders-form">
              @csrf
              <span class="text-[10px] font-medium text-surface-400 uppercase tracking-wider ml-1 hidden sm:block">Bulk:</span>
              <select name="status" class="border border-surface-200 rounded-lg px-2 py-1 text-[10px] text-surface-600 outline-none focus:border-brand-500 w-28">
                @foreach (['pending', 'confirmed', 'cancelled'] as $st)
                  <option value="{{ $st }}">{{ ucfirst(str_replace('_', ' ', $st)) }}</option>
                @endforeach
              </select>
              <button type="submit" class="bg-white border border-surface-200 text-surface-700 rounded-lg px-2.5 py-1 text-[10px] font-medium hover:bg-surface-50 transition-colors">Apply</button>
            </form>
          </div>
        </div>

        <div class="divide-y divide-surface-100 md:hidden">
          @forelse ($adminOrders as $order)
            @php
              $mobileOrderBadge = match($order->status) {
                'pending' => 'bg-amber-50 text-amber-700',
                'confirmed' => 'bg-blue-50 text-blue-700',
                'out_for_delivery' => 'bg-violet-50 text-violet-700',
                'delivered', 'completed' => 'bg-brand-50 text-brand-700',
                'cancelled' => 'bg-red-50 text-red-600',
                default => 'bg-surface-50 text-surface-600',
              };
            @endphp
            <article class="p-4">
              <div class="flex items-start gap-3">
                <input type="checkbox" name="order_ids[]" value="{{ $order->id }}" class="admin-order-cb mt-1 h-5 w-5 shrink-0 rounded border-surface-300 text-brand-600 focus:ring-brand-500" form="admin-bulk-orders-form" aria-label="Select order {{ $order->order_number }}">
                <div class="min-w-0 flex-1">
                  <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                      <h3 class="font-mono text-xs font-bold text-surface-900">{{ $order->order_number }}</h3>
                      <p class="mt-1 truncate text-sm font-semibold text-surface-700">{{ $order->user->name ?? 'Customer' }}</p>
                    </div>
                    <span class="shrink-0 rounded-full px-2 py-1 text-[9px] font-bold uppercase {{ $mobileOrderBadge }}">{{ str_replace('_', ' ', $order->status) }}</span>
                  </div>
                  <div class="mt-3 grid grid-cols-2 gap-3 rounded-xl bg-surface-50 p-3">
                    <div>
                      <p class="text-[9px] font-bold uppercase tracking-wide text-surface-400">Amount</p>
                      <p class="mt-1 text-sm font-bold text-surface-900">&#8369;{{ number_format((float) $order->total_amount, 2) }}</p>
                    </div>
                    <div>
                      <p class="text-[9px] font-bold uppercase tracking-wide text-surface-400">Payment</p>
                      <p class="mt-1 text-sm font-bold {{ ($order->payment_status ?? 'unpaid') === 'paid' ? 'text-brand-700' : (($order->payment_status ?? 'unpaid') === 'rejected' ? 'text-red-700' : 'text-amber-700') }}">{{ ucfirst(str_replace('_', ' ', $order->payment_status ?? 'unpaid')) }}</p>
                    </div>
                  </div>
                  <div class="mt-3 flex items-center justify-between gap-3">
                    <span class="text-[11px] text-surface-400">{{ optional($order->created_at)->format('M j, Y') }}</span>
                    <a href="{{ route('admin.orders.show', $order) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-950 px-4 text-sm font-bold text-white">Manage</a>
                  </div>
                </div>
              </div>
            </article>
          @empty
            <div class="px-5 py-10 text-center text-sm text-surface-400">No orders match your filters.</div>
          @endforelse
        </div>

        <div class="hidden overflow-x-auto md:block">
          <table class="w-full min-w-[1120px] text-xs whitespace-nowrap">
            <thead>
              <tr class="text-left text-surface-400 text-[10px] uppercase tracking-wider border-b border-surface-100">
                <th class="px-5 py-3 w-8">
                  <input type="checkbox" id="admin-select-all-orders" class="w-3.5 h-3.5 rounded border-surface-300 text-brand-600 focus:ring-brand-500" form="admin-bulk-orders-form">
                </th>
                <th class="px-5 py-3 font-medium">Order #</th>
                <th class="px-5 py-3 font-medium">Customer</th>
                <th class="px-5 py-3 font-medium">Amount</th>
                <th class="px-5 py-3 font-medium">Date</th>
                <th class="px-5 py-3 font-medium">Status</th>
                <th class="px-5 py-3 font-medium text-right">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
              @forelse ($adminOrders as $order)
                <tr class="hover:bg-surface-50 transition-colors">
                  <td class="px-5 py-3">
                    <input type="checkbox" name="order_ids[]" value="{{ $order->id }}" class="admin-order-cb w-3.5 h-3.5 rounded border-surface-300 text-brand-600 focus:ring-brand-500" form="admin-bulk-orders-form">
                  </td>
                  <td class="px-5 py-3">
                    <div class="flex items-center gap-1.5">
                      <span class="font-mono font-medium text-surface-800 bg-surface-50 px-1.5 py-0.5 rounded text-[10px]">{{ $order->order_number }}</span>
                      <button type="button"
                        onclick="openOrderDetail({{ json_encode([
                          'id'              => $order->id,
                          'order_number'    => $order->order_number,
                          'status'          => $order->status,
                          'payment_status'  => $order->payment_status ?? 'unpaid',
                          'total_amount'    => number_format((float)$order->total_amount, 2),
                          'created_at'      => optional($order->created_at)->format('M d, Y h:i A'),
                          'delivery_method' => $order->delivery_method ?? 'delivery',
                          'payment_method'  => $order->payment_method ?? 'cod',
                          'delivery_name'   => $order->delivery_name,
                          'delivery_phone'  => $order->delivery_phone,
                          'delivery_address'=> $order->delivery_address,
                          'delivery_city'   => $order->delivery_city,
                          'delivery_notes'  => $order->delivery_notes,
                          'delivery_proof_url' => $order->delivery_proof_url,
                          'delivered_at' => optional($order->delivered_at)->format('M d, Y h:i A'),
                          'customer_confirmed_at' => optional($order->customer_confirmed_at)->format('M d, Y h:i A'),
                          'cancel_reason' => $order->cancel_reason,
                          'cancelled_at' => optional($order->cancelled_at)->format('M d, Y h:i A'),
                          'customer_name'   => $order->user->name ?? 'N/A',
                          'customer_email'  => $order->user->email ?? '',
                          'customer_phone'  => $order->user->phone_number ?? '',
                          'items'           => $order->items ?? [],
                        ]) }})"
                        class="text-surface-300 hover:text-brand-600 transition-colors" title="View Details">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                      </button>
                    </div>
                  </td>
                  <td class="px-5 py-3">
                    <div class="flex items-center gap-2">
                      <div class="h-6 w-6 rounded-full bg-surface-100 text-surface-500 flex items-center justify-center text-[10px] font-bold border border-surface-200">
                        {{ strtoupper(substr($order->user->name ?? '?', 0, 1)) }}
                      </div>
                      <div>
                        <p class="font-medium text-surface-800">{{ $order->user->name ?? 'N/A' }}</p>
                        <p class="text-[10px] text-surface-400">{{ $order->user->email ?? '' }}</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-5 py-3 font-semibold text-surface-700">PHP {{ number_format((float) $order->total_amount, 2) }}</td>
                  <td class="px-5 py-3 text-surface-500">{{ optional($order->created_at)->format('M d, Y h:i A') }}</td>
                  <td class="px-5 py-3">
                    @php
                      $orderBadge = match($order->status) {
                        'pending' => 'bg-amber-50 text-amber-700 border-amber-100',
                        'confirmed' => 'bg-blue-50 text-blue-700 border-blue-100',
                        'out_for_delivery' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
                        'delivered' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                        'completed' => 'bg-brand-50 text-brand-700 border-brand-100',
                        'cancelled' => 'bg-red-50 text-red-600 border-red-100',
                        default => 'bg-surface-50 text-surface-600 border-surface-200',
                      };
                    @endphp
                    <span class="admin-status-badge px-2 py-0.5 rounded text-[10px] font-medium border {{ $orderBadge }}">
                      {{ $order->status === 'delivered' && ! $order->customer_confirmed_at ? 'Delivered - Pending Confirmation' : ucfirst(str_replace('_', ' ', $order->status)) }}
                    </span>
                    <div class="mt-1.5 space-y-0.5">
                      @php
                        $odm = $order->delivery_method ?? 'delivery';
                        $opm = $order->payment_method ?? 'cod';
                      @endphp
                      <div>
                        @if($odm === 'pickup')
                          <span class="px-1.5 py-0.5 rounded text-[9px] font-semibold border bg-purple-50 text-purple-700 border-purple-100 uppercase tracking-wide">Pick-up</span>
                        @else
                          <span class="px-1.5 py-0.5 rounded text-[9px] font-semibold border bg-blue-50 text-blue-700 border-blue-100 uppercase tracking-wide">Delivery</span>
                        @endif
                        @if($opm === 'gcash')
                          <span class="px-1.5 py-0.5 rounded text-[9px] font-semibold border bg-sky-50 text-sky-700 border-sky-100 uppercase tracking-wide">GCash</span>
                        @else
                          <span class="px-1.5 py-0.5 rounded text-[9px] font-semibold border bg-amber-50 text-amber-700 border-amber-100 uppercase tracking-wide">COD</span>
                        @endif
                        <span class="admin-payment-badge px-1.5 py-0.5 rounded text-[9px] font-semibold border uppercase tracking-wide {{ match($order->payment_status ?? 'unpaid') { 'paid' => 'bg-brand-50 text-brand-700 border-brand-100', 'pending_verification' => 'bg-amber-50 text-amber-700 border-amber-100', 'rejected' => 'bg-red-50 text-red-700 border-red-100', 'refunded' => 'bg-purple-50 text-purple-700 border-purple-100', default => 'bg-surface-50 text-surface-500 border-surface-200' } }}">
                          {{ ucfirst(str_replace('_', ' ', $order->payment_status ?? 'unpaid')) }}
                        </span>
                        @if($order->delivery_proof_url)
                          <span class="admin-proof-badge px-1.5 py-0.5 rounded text-[9px] font-semibold border bg-emerald-50 text-emerald-700 border-emerald-100 uppercase tracking-wide">Proof</span>
                        @endif
                      </div>
                      @if($odm === 'delivery' && $order->delivery_address)
                        <p class="text-[9px] text-surface-400 truncate max-w-[140px]" title="{{ $order->delivery_address }}, {{ $order->delivery_city }}">
                          {{ $order->delivery_address }}, {{ $order->delivery_city }}
                        </p>
                      @endif
                      @if($order->status === 'cancelled')
                        <p class="text-[9px] text-red-500 truncate max-w-[140px]" title="{{ $order->cancel_reason }}">
                          Cancelled {{ optional($order->cancelled_at)->format('M d, Y h:i A') ?? 'recorded' }}
                        </p>
                      @endif
                    </div>
                  </td>
                  <td class="px-5 py-3">
                    <div class="flex items-center justify-end gap-1.5">
                      @php
                        $inlineStatuses = match($order->status) {
                          'pending' => ['pending', 'confirmed', 'cancelled'],
                          'confirmed' => ['confirmed', 'cancelled'],
                          default => [$order->status],
                        };
                      @endphp
                      <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="admin-status-form flex items-center gap-1.5" data-detail-url="{{ route('admin.orders.show', $order) }}">
                        @csrf
                        @method('PUT')
                        <div class="flex flex-col gap-1 min-w-[100px]">
                          <select name="status" class="border border-surface-200 rounded px-2 py-0.5 text-[10px] text-surface-600 outline-none focus:border-brand-500 w-full" {{ $isAdmin ? '' : 'disabled' }}>
                            @foreach ($inlineStatuses as $status)
                              <option value="{{ $status }}" {{ $order->status === $status ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                              </option>
                            @endforeach
                          </select>
                          <select name="payment_status" class="border border-surface-200 rounded px-2 py-0.5 text-[10px] {{ $order->payment_status === 'paid' ? 'text-brand-600 bg-brand-50 font-medium' : 'text-surface-600' }} outline-none focus:border-brand-500 w-full" {{ $isAdmin ? '' : 'disabled' }}>
                            <option value="unpaid" {{ $order->payment_status === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                            <option value="pending_verification" {{ $order->payment_status === 'pending_verification' ? 'selected' : '' }}>Pending verification</option>
                            <option value="paid" {{ $order->payment_status === 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="rejected" {{ $order->payment_status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            <option value="refunded" {{ $order->payment_status === 'refunded' ? 'selected' : '' }}>Refunded</option>
                          </select>
                          @if(in_array($order->status, ['confirmed', 'out_for_delivery'], true))
                            <a href="{{ route('admin.orders.show', $order) }}" class="text-[9px] font-medium text-indigo-600 hover:text-indigo-800">Open details for proof</a>
                          @endif
                        </div>
                        @if($isAdmin)
                          <button type="submit" class="bg-brand-600 text-white rounded px-2.5 py-2 text-[10px] font-medium hover:bg-brand-700 disabled:opacity-70 disabled:cursor-wait transition-colors h-full" data-saving-label="Saving...">Save</button>
                        @endif
                      </form>
                      @if($isAdmin)
                        <form method="POST" action="{{ route('admin.orders.archive', $order) }}" onsubmit="return confirm('Archive this order?');">
                          @csrf
                          @method('PUT')
                          <button class="p-1 text-surface-300 hover:text-red-500 rounded transition-colors" title="Archive">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                          </button>
                        </form>
                      @endif
                    </div>
                  </td>
                </tr>
              @empty
                <tr><td class="px-5 py-8 text-surface-400 text-center" colspan="7">No orders match your filters.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- SERVICES TAB -->
    <div id="tab-services" class="{{ $tabClass('services') }}">
      <div class="space-y-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <div class="flex items-center gap-3">
              <h1 class="text-2xl font-bold text-surface-900">Services</h1>
              <span class="inline-flex min-w-8 items-center justify-center rounded-lg bg-brand-600 px-2.5 py-1 text-sm font-bold text-white">{{ $serviceStats['total'] ?? (method_exists($services, 'total') ? $services->total() : $services->count()) }}</span>
            </div>
            <p class="mt-1 text-sm text-surface-500">Manage customer-bookable services used in scheduling and cost estimation.</p>
          </div>
          @if($isStaffOrAdmin)
            <a href="{{ route('admin.services.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-brand-800">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7H5"/></svg>
              Add Service
            </a>
          @endif
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
          <div class="rounded-xl border border-surface-100 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-surface-400">Service Types</p>
            <p class="mt-2 text-2xl font-bold text-surface-900">{{ $serviceStats['total'] ?? 0 }}</p>
          </div>
          <div class="rounded-xl border border-brand-100 bg-brand-50 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Active Services</p>
            <p class="mt-2 text-2xl font-bold text-brand-800">{{ $serviceStats['active'] ?? 0 }}</p>
          </div>
          <div class="rounded-xl border border-surface-100 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-surface-400">Scheduling Module</p>
            <p class="mt-2 text-sm font-bold text-surface-900">Customer-bookable</p>
          </div>
        </div>

        <form method="GET" action="{{ route('admin.dashboard') }}" class="rounded-xl border border-surface-100 bg-white p-4 shadow-sm">
          <input type="hidden" name="tab" value="services">
          <div class="grid grid-cols-1 gap-2 md:grid-cols-12">
            <div class="relative md:col-span-9">
              <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-surface-300" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M11 19a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z"/></svg>
              <input type="search" name="service_q" value="{{ $serviceQ ?? request('service_q') }}" placeholder="Search services..." class="h-10 w-full rounded-lg border border-surface-200 px-3 pl-9 text-sm outline-none transition-colors focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
            </div>
            <div class="flex gap-2 md:col-span-3">
              <button type="submit" class="h-10 flex-1 rounded-lg bg-brand-700 px-4 text-sm font-semibold text-white transition-colors hover:bg-brand-800">Search</button>
              <a href="{{ route('admin.dashboard', ['tab' => 'services']) }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-surface-200 px-4 text-sm font-medium text-surface-500 transition-colors hover:border-surface-300 hover:text-surface-800">Reset</a>
            </div>
          </div>
        </form>

        <div class="space-y-3">
          @forelse ($services as $service)
            <article class="rounded-xl border border-surface-100 bg-white p-4 shadow-sm transition-all hover:border-brand-100 hover:shadow-md">
              <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-3 min-w-0">
                  <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-brand-100 bg-brand-50 text-brand-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/></svg>
                  </div>
                  <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                      <h3 class="truncate text-base font-bold text-surface-900">{{ $service->name }}</h3>
                      <span class="rounded-lg border px-2.5 py-1 text-xs font-semibold {{ $service->is_active ? 'border-brand-100 bg-brand-50 text-brand-700' : 'border-surface-200 bg-surface-50 text-surface-500' }}">{{ $service->is_active ? 'Active' : 'Hidden' }}</span>
                    </div>
                    <p class="mt-1 text-sm text-surface-500">Customer-bookable service for scheduling and estimation.</p>
                  </div>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                  <div class="rounded-lg border border-surface-100 bg-surface-50 px-4 py-3 sm:min-w-40">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-surface-400">Default Fee</p>
                    <p class="mt-1 text-sm font-bold text-surface-900">PHP {{ number_format((float) $service->default_fee, 2) }}</p>
                  </div>
                  <a href="{{ route('admin.services.edit', $service) }}" class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-brand-600 px-3 py-2.5 text-sm font-semibold text-brand-700 transition-colors hover:bg-brand-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                    Edit
                  </a>
                </div>
              </div>
            </article>
          @empty
            <div class="rounded-xl border border-surface-100 bg-white py-12 text-center text-sm text-surface-400">No services found.</div>
          @endforelse
        </div>

        @if (method_exists($services, 'links'))
          <div class="rounded-xl border border-surface-100 bg-white p-4">
            {{ $services->appends(['tab' => 'services', 'service_q' => $serviceQ ?? null])->links() }}
          </div>
        @endif
      </div>
    </div>
    <!-- PRODUCTS TAB -->
    <div id="tab-products" class="{{ $tabClass('products') }}">
      <div class="space-y-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <div class="flex items-center gap-3">
              <h1 class="text-2xl font-bold text-surface-900">Inventory</h1>
              <span class="inline-flex min-w-8 items-center justify-center rounded-lg bg-brand-600 px-2.5 py-1 text-sm font-bold text-white">{{ method_exists($products, 'total') ? $products->total() : $products->count() }}</span>
            </div>
            <p class="mt-1 text-sm text-surface-500">Manage materials, supplies, stock visibility, and AR-ready products.</p>
          </div>
          @if($isStaffOrAdmin)
            <a href="{{ route('admin.products.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-brand-800">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7H5"/></svg>
              Add Inventory Item
            </a>
          @endif
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
          <div class="rounded-xl border border-surface-100 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-surface-400">Inventory Items</p>
            <p class="mt-2 text-2xl font-bold text-surface-900">{{ $productStats['total'] ?? 0 }}</p>
          </div>
          <div class="rounded-xl border border-brand-100 bg-brand-50 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Active in Shop</p>
            <p class="mt-2 text-2xl font-bold text-brand-800">{{ $productStats['active'] ?? 0 }}</p>
          </div>
          <div class="rounded-xl border border-amber-100 bg-amber-50 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Low Stock</p>
            <p class="mt-2 text-2xl font-bold text-amber-700">{{ $productStats['low_stock'] ?? 0 }}</p>
          </div>
          <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">AR Ready</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700">{{ $productStats['ar_ready'] ?? 0 }}</p>
          </div>
        </div>

        <form method="GET" action="{{ route('admin.dashboard') }}" class="rounded-xl border border-surface-100 bg-white p-4 shadow-sm">
          <input type="hidden" name="tab" value="products">
          <div class="grid grid-cols-1 gap-2 md:grid-cols-12">
            <div class="relative md:col-span-5">
              <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-surface-300" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M11 19a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z"/></svg>
              <input type="search" name="product_q" value="{{ $productQ ?? request('product_q') }}" placeholder="Search by product or category..." class="h-10 w-full rounded-lg border border-surface-200 px-3 pl-9 text-sm outline-none transition-colors focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
            </div>
            <select name="product_category" class="h-10 rounded-lg border border-surface-200 bg-white px-3 text-sm outline-none transition-colors focus:border-brand-500 focus:ring-1 focus:ring-brand-500 md:col-span-4">
              <option value="">All Categories</option>
              @foreach(($productCategories ?? collect()) as $categoryOption)
                <option value="{{ $categoryOption }}" @selected(($productCategory ?? '') === $categoryOption)>{{ ucfirst($categoryOption) }}</option>
              @endforeach
            </select>
            <div class="flex gap-2 md:col-span-3">
              <button type="submit" class="h-10 flex-1 rounded-lg bg-brand-700 px-4 text-sm font-semibold text-white transition-colors hover:bg-brand-800">Filter</button>
              <a href="{{ route('admin.dashboard', ['tab' => 'products']) }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-surface-200 px-4 text-sm font-medium text-surface-500 transition-colors hover:border-surface-300 hover:text-surface-800">Reset</a>
            </div>
          </div>
        </form>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
          @forelse ($products as $product)
            @php
              $stockClass = $product->stock_qty === 0 ? 'bg-red-50 text-red-600 border-red-100' : ($product->stock_qty <= 5 ? 'bg-amber-50 text-amber-700 border-amber-100' : 'bg-brand-50 text-brand-700 border-brand-100');
            @endphp
            <article class="group overflow-hidden rounded-xl border border-surface-100 bg-white shadow-sm transition-all hover:-translate-y-0.5 hover:border-brand-100 hover:shadow-md">
              <div class="relative flex h-44 items-center justify-center bg-brand-50">
                @if($product->image_url)
                  <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]">
                @else
                  <svg class="h-12 w-12 text-brand-200" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 19.5h16.5A1.5 1.5 0 0 0 21.75 18V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z"/></svg>
                @endif
                <span class="absolute left-3 top-3 rounded-full border border-white/70 bg-white/90 px-2.5 py-1 text-[11px] font-semibold text-surface-700 shadow-sm">{{ ucfirst($product->category) }}</span>
              </div>
              <div class="p-4">
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <h3 class="truncate text-base font-bold text-surface-900">{{ $product->name }}</h3>
                    <p class="mt-1 line-clamp-2 min-h-[2.5rem] text-sm leading-5 text-surface-500">{{ $product->description ?: 'No description added yet.' }}</p>
                  </div>
                  <span class="shrink-0 rounded-lg border px-2.5 py-1 text-xs font-semibold {{ $product->is_active ? 'border-brand-100 bg-brand-50 text-brand-700' : 'border-surface-200 bg-surface-50 text-surface-500' }}">{{ $product->is_active ? 'Active' : 'Hidden' }}</span>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-2 rounded-lg border border-surface-100 bg-surface-50 p-3">
                  <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-surface-400">Price</p>
                    <p class="mt-1 text-sm font-bold text-surface-900">&#8369;{{ number_format((float) $product->price, 2) }}</p>
                  </div>
                  <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-surface-400">Stock Qty</p>
                    <p class="mt-1 text-sm font-bold {{ $product->stock_qty === 0 ? 'text-red-600' : ($product->stock_qty <= 5 ? 'text-amber-700' : 'text-brand-700') }}">{{ $product->stock_qty }} units</p>
                  </div>
                </div>
                <div class="mt-3 flex items-center justify-between gap-3">
                  <span class="rounded-lg border px-2.5 py-1 text-xs font-semibold {{ $stockClass }}">{{ $product->stock_qty === 0 ? 'Out of stock' : ($product->stock_qty <= 5 ? 'Low stock' : 'In stock') }}</span>
                  @if($product->plantModel)
                    <span class="rounded-lg border border-emerald-100 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">AR Ready</span>
                  @else
                    <span class="rounded-lg border border-surface-200 bg-surface-50 px-2.5 py-1 text-xs font-semibold text-surface-400">No AR</span>
                  @endif
                </div>
                <a href="{{ route('admin.products.edit', $product) }}" class="mt-4 flex items-center justify-center gap-1.5 rounded-lg border border-brand-600 px-3 py-2.5 text-sm font-semibold text-brand-700 transition-colors hover:bg-brand-50">
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                  Edit
                </a>
              </div>
            </article>
          @empty
            <div class="col-span-full rounded-xl border border-surface-100 bg-white py-12 text-center text-sm text-surface-400">No products found.</div>
          @endforelse
        </div>

        @if (method_exists($products, 'links'))
          <div class="rounded-xl border border-surface-100 bg-white p-4">
            {{ $products->appends(['tab' => 'products', 'product_q' => $productQ ?? null, 'product_category' => $productCategory ?? null])->links() }}
          </div>
        @endif
      </div>
    </div>

    <!-- BILLING TAB -->
    @if($isAdmin)
    <div id="tab-payment" class="{{ $tabClass('payment') }}">
      <div class="space-y-5 max-w-5xl">
        <div>
          <h1 class="text-2xl font-bold text-surface-900">Billing</h1>
          <p class="mt-1 text-sm text-surface-500">Manage payment setup, unpaid records, and customer checkout billing details.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div class="rounded-xl border border-red-100 bg-red-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-red-600">Unpaid Orders</p>
            <p class="mt-2 text-2xl font-bold text-red-600">{{ $orderFlowStats['unpaid'] ?? 0 }}</p>
          </div>
          <div class="rounded-xl border border-brand-100 bg-brand-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Billing Setup</p>
            <p class="mt-2 text-sm font-bold text-brand-800">{{ (!empty($gcashSettings['name']) || !empty($gcashSettings['number'])) ? 'Configured' : 'Needs Setup' }}</p>
          </div>
          <div class="rounded-xl border border-surface-100 bg-white p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-surface-400">Receipt Support</p>
            <p class="mt-2 text-sm font-bold text-surface-900">Orders & appointments</p>
          </div>
        </div>

      <div class="bg-white rounded-xl border border-surface-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-surface-100">
          <h2 class="text-sm font-semibold text-surface-900">Billing Configuration</h2>
          <p class="text-xs text-surface-400 mt-0.5">These details appear during customer checkout when they choose GCash.</p>
        </div>

        <form method="POST" action="{{ route('admin.payment-settings.update') }}" enctype="multipart/form-data" class="p-5 grid grid-cols-1 lg:grid-cols-3 gap-5">
          @csrf
          @method('PUT')

          <div class="lg:col-span-2 space-y-4">
            <div>
              <label class="block text-[10px] font-medium text-surface-400 mb-1">GCash Account Name</label>
              <input name="gcash_name" value="{{ old('gcash_name', $gcashSettings['name'] ?? '') }}" placeholder="e.g. Maria Santos"
                     class="w-full border border-surface-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-brand-500 transition-colors">
            </div>

            <div>
              <label class="block text-[10px] font-medium text-surface-400 mb-1">GCash Number</label>
              <input name="gcash_number" value="{{ old('gcash_number', $gcashSettings['number'] ?? '') }}" placeholder="e.g. 0917 123 4567"
                     class="w-full border border-surface-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-brand-500 transition-colors">
            </div>

            <div>
              <label class="block text-[10px] font-medium text-surface-400 mb-1">Upload GCash QR</label>
              <input name="gcash_qr" type="file" accept="image/*"
                     class="w-full text-xs text-surface-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-surface-100 file:text-surface-700 hover:file:bg-surface-200">
              <p class="text-[11px] text-surface-400 mt-1">PNG or JPG, up to 5MB.</p>
            </div>

            @if(!empty($gcashSettings['qr_url']))
              <label class="inline-flex items-center gap-2 text-xs text-surface-500">
                <input type="checkbox" name="remove_gcash_qr" value="1" class="w-3.5 h-3.5 rounded border-surface-300 text-red-500 focus:ring-red-500">
                Remove current QR image
              </label>
            @endif

            <button class="bg-surface-900 text-white rounded-lg px-4 py-2 text-xs font-medium hover:bg-surface-800 transition-colors">
              Save Billing Details
            </button>
          </div>

          <div class="rounded-xl border border-surface-100 bg-surface-50 p-4">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-surface-400 mb-3">Customer Preview</p>
            @if(!empty($gcashSettings['qr_url']))
              <img src="{{ $gcashSettings['qr_url'] }}" alt="GCash QR code" class="w-full aspect-square object-contain rounded-lg bg-white border border-surface-100 mb-3">
            @else
              <div class="w-full aspect-square rounded-lg bg-white border border-dashed border-surface-200 flex items-center justify-center text-center text-xs text-surface-400 mb-3 px-4">
                No QR uploaded yet
              </div>
            @endif
            <p class="text-xs text-surface-500">Name</p>
            <p class="text-sm font-semibold text-surface-900 mb-2">{{ $gcashSettings['name'] ?: 'Not set' }}</p>
            <p class="text-xs text-surface-500">Number</p>
            <p class="text-sm font-semibold text-surface-900">{{ $gcashSettings['number'] ?: 'Not set' }}</p>
          </div>
        </form>
      </div>
      </div>
    </div>
    @endif

    <!-- ARCHIVED TAB -->
    <div id="tab-archived" class="{{ $tabClass('archived', 'space-y-5') }}">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <!-- Archived Inventory -->
        <div class="bg-white rounded-xl border border-surface-100 overflow-hidden flex flex-col">
          <div class="px-5 py-4 border-b border-surface-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-surface-900">Archived Inventory</h2>
            <span class="text-[10px] text-surface-400">{{ ($archivedProducts ?? collect([]))->count() }} items</span>
          </div>
          <div class="p-5 flex-1 space-y-2">
            @forelse (($archivedProducts ?? collect([])) as $p)
              <div class="flex items-center justify-between p-3 bg-surface-50 border border-surface-100 rounded-lg">
                <div>
                  <p class="text-xs font-medium text-surface-800">{{ $p->name }}</p>
                  <p class="text-[10px] text-surface-400 mt-0.5">{{ $p->category }} &middot; &#8369;{{ number_format((float) ($p->price ?? 0), 2) }}</p>
                </div>
                @if($isStaffOrAdmin)
                  <form method="POST" action="{{ route('admin.products.restore', $p) }}">
                    @csrf
                    @method('PUT')
                    <button class="text-[10px] font-medium text-brand-600 hover:text-brand-700 transition-colors">Restore</button>
                  </form>
                @endif
              </div>
            @empty
              <p class="text-xs text-surface-400 text-center py-4">No archived inventory.</p>
            @endforelse
          </div>
        </div>

        <!-- Archived Services -->
        <div class="bg-white rounded-xl border border-surface-100 overflow-hidden flex flex-col">
          <div class="px-5 py-4 border-b border-surface-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-surface-900">Archived Services</h2>
            <span class="text-[10px] text-surface-400">{{ ($archivedServices ?? collect([]))->count() }} items</span>
          </div>
          <div class="p-5 flex-1 space-y-2">
            @forelse (($archivedServices ?? collect([])) as $s)
              <div class="flex items-center justify-between p-3 bg-surface-50 border border-surface-100 rounded-lg">
                <div>
                  <p class="text-xs font-medium text-surface-800">{{ $s->name }}</p>
                  <p class="text-[10px] text-surface-400 mt-0.5">&#8369;{{ number_format((float) ($s->default_fee ?? 0), 2) }}</p>
                </div>
                @if($isStaffOrAdmin)
                  <form method="POST" action="{{ route('admin.services.restore', $s) }}">
                    @csrf
                    @method('PUT')
                    <button class="text-[10px] font-medium text-brand-600 hover:text-brand-700 transition-colors">Restore</button>
                  </form>
                @endif
              </div>
            @empty
              <p class="text-xs text-surface-400 text-center py-4">No archived services.</p>
            @endforelse
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <!-- Archived Orders -->
        <div class="bg-white rounded-xl border border-surface-100 overflow-hidden flex flex-col">
          <div class="px-5 py-4 border-b border-surface-100">
            <h2 class="text-sm font-semibold text-surface-900">Archived Orders</h2>
          </div>
          <div class="p-5 flex-1 space-y-2">
            @forelse (($archivedOrders ?? []) as $o)
              <div class="flex items-center justify-between p-3 bg-surface-50 border border-surface-100 rounded-lg">
                <div>
                  <p class="text-xs font-medium text-surface-800 font-mono">{{ $o->order_number }}</p>
                  <p class="text-[10px] text-surface-400 mt-0.5">{{ $o->user->name ?? 'N/A' }} &middot; &#8369;{{ number_format((float) ($o->total_amount ?? 0), 2) }}</p>
                </div>
                @if($isAdmin)
                  <form method="POST" action="{{ route('admin.orders.restore', $o) }}">
                    @csrf
                    @method('PUT')
                    <button class="text-[10px] font-medium text-brand-600 hover:text-brand-700 transition-colors">Restore</button>
                  </form>
                @endif
              </div>
            @empty
              <p class="text-xs text-surface-400 text-center py-4">No archived orders.</p>
            @endforelse
          </div>
          @if (isset($archivedOrders) && method_exists($archivedOrders, 'links'))
            <div class="p-4 border-t border-surface-100">
              {{ $archivedOrders->appends(['tab' => 'archived'])->links() }}
            </div>
          @endif
        </div>

        <!-- Archived Appointments -->
        <div class="bg-white rounded-xl border border-surface-100 overflow-hidden flex flex-col">
          <div class="px-5 py-4 border-b border-surface-100">
            <h2 class="text-sm font-semibold text-surface-900">Archived Appointments</h2>
          </div>
          <div class="p-5 flex-1 space-y-2">
            @forelse (($archivedAppointments ?? []) as $a)
              <div class="flex items-center justify-between p-3 bg-surface-50 border border-surface-100 rounded-lg">
                <div>
                  <p class="text-xs font-medium text-surface-800">{{ $a->user->name ?? 'N/A' }}</p>
                  <p class="text-[10px] text-surface-400 mt-0.5">{{ $a->serviceType->name ?? 'N/A' }} &middot; {{ optional($a->appointment_at)->format('M d, Y') }}</p>
                </div>
                @if($isStaffOrAdmin)
                  <form method="POST" action="{{ route('admin.appointments.restore', $a) }}">
                    @csrf
                    @method('PUT')
                    <button class="text-[10px] font-medium text-brand-600 hover:text-brand-700 transition-colors">Restore</button>
                  </form>
                @endif
              </div>
            @empty
              <p class="text-xs text-surface-400 text-center py-4">No archived appointments.</p>
            @endforelse
          </div>
          @if (isset($archivedAppointments) && method_exists($archivedAppointments, 'links'))
            <div class="p-4 border-t border-surface-100">
              {{ $archivedAppointments->appends(['tab' => 'archived'])->links() }}
            </div>
          @endif
        </div>
      </div>
    </div>

    <!-- AUDIT TAB -->
    <div id="tab-audit" class="{{ $tabClass('audit') }}">
      <div class="bg-white rounded-xl border border-surface-100 overflow-hidden">
        <div class="p-5 border-b border-surface-100 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
          <div>
            <h2 class="text-sm font-semibold text-surface-900">Audit Logs</h2>
            <p class="text-xs text-surface-400 mt-0.5">See who changed a record, what they did, and when it happened.</p>
          </div>
          <form method="GET" action="{{ route('admin.dashboard') }}" class="flex items-end gap-2">
            <input type="hidden" name="tab" value="audit">
            <div class="relative">
              <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-surface-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
              <input type="search" name="audit_q" value="{{ $auditQ ?? request('audit_q') }}" placeholder="Activity, user, target, or ID"
                     class="pl-8 pr-3 py-1.5 border border-surface-200 rounded-lg text-xs outline-none focus:border-brand-500 w-52 transition-colors">
            </div>
            <button type="submit" class="bg-surface-900 text-white rounded-lg px-4 py-1.5 text-xs font-medium hover:bg-surface-800 transition-colors">Search</button>
            <a href="{{ route('admin.dashboard', ['tab' => 'audit']) }}" class="text-xs text-surface-400 hover:text-surface-700 py-1.5">Reset</a>
          </form>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-xs whitespace-nowrap">
            <thead>
              <tr class="text-left text-surface-400 text-[10px] uppercase tracking-wider border-b border-surface-100">
                <th class="px-5 py-3 font-medium">Time</th>
                <th class="px-5 py-3 font-medium">Actor</th>
                <th class="px-5 py-3 font-medium">What the user did</th>
                <th class="px-5 py-3 font-medium">Target</th>
                <th class="px-5 py-3 font-medium">IP</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
              @forelse (($auditLogs ?? []) as $log)
                <tr class="hover:bg-surface-50 transition-colors">
                  <td class="px-5 py-3 text-surface-500">{{ optional($log->created_at)->format('M d, Y h:i A') }}</td>
                  <td class="px-5 py-3">
                    <div class="flex items-center gap-2">
                      <div class="h-6 w-6 rounded-full bg-surface-100 text-surface-500 flex items-center justify-center text-[10px] font-bold border border-surface-200">
                        {{ strtoupper(substr($log->actor->name ?? 'S', 0, 1)) }}
                      </div>
                      <div>
                        <p class="font-medium text-surface-800">{{ $log->actor->name ?? 'System' }}</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-5 py-3">
                    @php
                      $actionBadge = match(true) {
                        str_contains($log->action, 'create') => 'bg-brand-50 text-brand-700 border-brand-100',
                        str_contains($log->action, 'restore') => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                        str_contains($log->action, 'archive'),
                        str_contains($log->action, 'cancel') => 'bg-red-50 text-red-700 border-red-100',
                        str_contains($log->action, 'update'),
                        str_contains($log->action, 'confirmed') => 'bg-blue-50 text-blue-700 border-blue-100',
                        default => 'bg-surface-50 text-surface-600 border-surface-200',
                      };
                    @endphp
                    <p class="max-w-lg whitespace-normal font-medium leading-5 text-surface-800">{{ $log->description }}</p>
                    <span class="mt-1 inline-flex px-2 py-0.5 rounded text-[10px] font-medium border {{ $actionBadge }}" title="Technical action code">
                      {{ $log->action_label }}
                    </span>
                  </td>
                  <td class="px-5 py-3">
                    <span class="text-[10px] bg-surface-50 px-1.5 py-0.5 rounded text-surface-600">{{ $log->target_label }}</span>
                  </td>
                  <td class="px-5 py-3 font-mono text-[10px] text-surface-400">{{ $log->ip ?? '-' }}</td>
                </tr>
              @empty
                <tr><td class="px-5 py-8 text-surface-400 text-center" colspan="5">No audit logs match your filters.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if (isset($auditLogs) && method_exists($auditLogs, 'links'))
          <div class="p-4 border-t border-surface-100">
            {{ $auditLogs->appends(['tab' => 'audit'])->links() }}
          </div>
        @endif
      </div>
    </div>

    <!-- USERS TAB -->
    @if($isAdmin)
    <div id="tab-users" class="{{ $tabClass('users') }}">
      <div class="bg-white rounded-xl border border-surface-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-surface-100">
          <h2 class="text-sm font-semibold text-surface-900">User Directory</h2>
          <p class="text-xs text-surface-400 mt-0.5">Manage system access roles for all users.</p>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-xs whitespace-nowrap">
            <thead>
              <tr class="text-left text-surface-400 text-[10px] uppercase tracking-wider border-b border-surface-100">
                <th class="px-5 py-3 font-medium">User</th>
                <th class="px-5 py-3 font-medium">Account Type</th>
                <th class="px-5 py-3 font-medium">Joined</th>
                <th class="px-5 py-3 font-medium">Role</th>
                <th class="px-5 py-3 font-medium text-right">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
              @foreach ($users as $u)
                <tr class="hover:bg-surface-50 transition-colors {{ $u->id === auth()->id() ? 'bg-brand-50/30' : '' }}">
                  <td class="px-5 py-3">
                    <div class="flex items-center gap-2.5">
                      <div class="h-7 w-7 rounded-full {{ $u->id === auth()->id() ? 'bg-brand-50 text-brand-600 border-brand-100' : 'bg-surface-100 text-surface-500 border-surface-200' }} flex items-center justify-center text-[10px] font-bold border">
                        {{ strtoupper(substr($u->name, 0, 1)) }}
                      </div>
                      <div>
                        <p class="font-medium text-surface-900">
                          {{ $u->name }}
                          @if($u->id === auth()->id())
                            <span class="ml-1 text-[10px] font-medium text-brand-600 bg-brand-50 px-1.5 py-0.5 rounded">You</span>
                          @endif
                        </p>
                        <p class="text-[10px] text-surface-400">{{ $u->email }}</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-5 py-3">
                    <span class="px-2 py-0.5 rounded text-[10px] font-medium border {{ $u->account_type === 'Business' ? 'bg-purple-50 text-purple-600 border-purple-100' : 'bg-surface-50 text-surface-600 border-surface-200' }}">
                      {{ $u->account_type ?? 'Customer' }}
                    </span>
                  </td>
                  <td class="px-5 py-3 text-surface-500">{{ $u->created_at->format('M d, Y') }}</td>
                  <td class="px-5 py-3">
                    @php
                      $roleBadge = match($u->role) {
                        'admin' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
                        'staff' => 'bg-brand-50 text-brand-700 border-brand-100',
                        default => 'bg-surface-50 text-surface-600 border-surface-200',
                      };
                    @endphp
                    <span class="px-2 py-0.5 rounded text-[10px] font-medium border {{ $roleBadge }}">
                      {{ ucfirst($u->role) }}
                    </span>
                  </td>
                  <td class="px-5 py-3">
                    <div class="flex items-center justify-end">
                      @if($u->id !== auth()->id())
                        <form method="POST" action="{{ route('admin.users.role', $u) }}" class="flex items-center gap-1.5">
                          @csrf
                          @method('PUT')
                          <select name="role" class="border border-surface-200 rounded-lg px-2 py-1 text-[10px] text-surface-600 outline-none focus:border-brand-500 w-20">
                            <option value="user" {{ $u->role === 'user' ? 'selected' : '' }}>User</option>
                            <option value="staff" {{ $u->role === 'staff' ? 'selected' : '' }}>Staff</option>
                            <option value="admin" {{ $u->role === 'admin' ? 'selected' : '' }}>Admin</option>
                          </select>
                          <button class="bg-surface-900 text-white rounded-lg px-2.5 py-1 text-[10px] font-medium hover:bg-surface-800 transition-colors">Set</button>
                        </form>
                      @else
                        <span class="text-[10px] text-surface-300">Cannot edit self</span>
                      @endif
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
    @endif

    {{-- ─── Feedbacks Tab ─────────────────────────────────────────────── --}}
    <div id="tab-feedbacks" class="{{ $tabClass('feedbacks') }}">
      <div class="bg-white rounded-xl border border-surface-100 overflow-hidden mb-4">
        <div class="px-5 py-4 border-b border-surface-100 flex items-center justify-between gap-4 flex-wrap">
          <div>
            <h2 class="text-sm font-semibold text-surface-900">Customer Feedback</h2>
            <p class="text-xs text-surface-400 mt-0.5">
              {{ $feedbacks->total() }} submission{{ $feedbacks->total() !== 1 ? 's' : '' }}
              @if($avgRating)
                &mdash; avg rating
                <span class="font-semibold text-amber-500">{{ number_format($avgRating, 1) }}&nbsp;�
</span>
              @endif
            </p>
          </div>
          <form method="GET" action="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
            <input type="hidden" name="tab" value="feedbacks">
            <input type="text" name="feedback_q" value="{{ $feedbackQ }}" placeholder="Search…"
              class="border border-surface-200 rounded-lg px-3 py-1.5 text-xs outline-none focus:border-brand-500 w-44">
            <button class="bg-surface-900 text-white text-xs px-3 py-1.5 rounded-lg hover:bg-surface-800 transition-colors">Search</button>
            @if($feedbackQ)
              <a href="{{ route('admin.dashboard', ['tab' => 'feedbacks']) }}" class="text-xs text-surface-400 hover:text-surface-700 py-1.5">Reset</a>
            @endif
          </form>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-xs whitespace-nowrap">
            <thead>
              <tr class="text-left text-surface-400 text-[10px] uppercase tracking-wider border-b border-surface-100">
                <th class="px-5 py-3 font-medium">User</th>
                <th class="px-5 py-3 font-medium">Rating</th>
                <th class="px-5 py-3 font-medium">About</th>
                <th class="px-5 py-3 font-medium">Comment</th>
                <th class="px-5 py-3 font-medium">Date</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
              @forelse ($feedbacks as $fb)
                <tr class="hover:bg-surface-50 transition-colors">
                  <td class="px-5 py-3">
                    <p class="font-medium text-surface-900">{{ $fb->user->name }}</p>
                    <p class="text-[10px] text-surface-400">{{ $fb->user->email }}</p>
                  </td>
                  <td class="px-5 py-3">
                    <span class="text-amber-400 tracking-tighter text-sm">
                      {{ str_repeat('�
', $fb->rating) }}<span class="text-surface-200">{{ str_repeat('�
', 5 - $fb->rating) }}</span>
                    </span>
                  </td>
                  <td class="px-5 py-3">
                    @if($fb->order)
                      <span class="px-2 py-0.5 rounded text-[10px] font-medium border bg-brand-50 text-brand-700 border-brand-100">
                        Order {{ $fb->order->order_number }}
                      </span>
                    @elseif($fb->appointment)
                      <span class="px-2 py-0.5 rounded text-[10px] font-medium border bg-amber-50 text-amber-700 border-amber-100">
                        Appointment {{ optional($fb->appointment->appointment_at)->format('M d, Y') }}
                      </span>
                    @elseif($fb->product)
                      <span class="px-2 py-0.5 rounded text-[10px] font-medium border bg-blue-50 text-blue-700 border-blue-100">{{ $fb->product->name }}</span>
                    @elseif($fb->serviceType)
                      <span class="px-2 py-0.5 rounded text-[10px] font-medium border bg-purple-50 text-purple-700 border-purple-100">{{ $fb->serviceType->name }}</span>
                    @endif
                  </td>
                  <td class="px-5 py-3 max-w-xs whitespace-normal text-surface-600">{{ $fb->comment ?: '—' }}</td>
                  <td class="px-5 py-3 text-surface-400">{{ $fb->created_at->format('M d, Y') }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="px-5 py-8 text-center text-surface-400">No feedback submitted yet.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      {{ $feedbacks->appends(['tab' => 'feedbacks', 'feedback_q' => $feedbackQ])->links() }}
    </div>

    </main>
    </div>{{-- /tabs-main --}}
  </div>{{-- /flex-col wrapper --}}


  {{-- ── Admin Order Detail Modal ──────────────────────────────────────── --}}
  <div id="admin-order-modal" class="fixed inset-0 z-50 hidden flex items-end sm:items-center justify-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="od-dialog-title">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeOrderDetail()" aria-hidden="true"></div>
    <div id="od-dialog-panel" tabindex="-1" class="relative bg-white w-full sm:max-w-2xl max-h-[92vh] sm:max-h-[90vh] flex flex-col sm:rounded-2xl rounded-t-2xl shadow-2xl animate-od-up sm:animate-od-in overflow-hidden">

      {{-- drag handle - mobile only --}}
      <div class="flex justify-center pt-3 pb-1 sm:hidden">
        <div class="w-10 h-1 bg-surface-200 rounded-full"></div>
      </div>

      {{-- Header --}}
      <div class="flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-b border-surface-100">
        <div>
          <h2 id="od-dialog-title" class="text-base font-semibold text-surface-900">Order Details</h2>
          <p class="text-xs text-surface-400 mt-0.5">Order <span id="od-order-number" class="font-mono text-brand-600 font-semibold"></span></p>
        </div>
        <div class="flex items-center gap-2">
          <span id="od-status-badge" class="text-[10px] font-semibold uppercase tracking-wide px-2.5 py-1 rounded border"></span>
          <button type="button" onclick="closeOrderDetail()" class="ml-1 flex h-10 w-10 items-center justify-center rounded-xl text-surface-400 hover:bg-surface-50 hover:text-surface-700 transition-colors" aria-label="Close order details">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
      </div>

      {{-- Body (scrollable) --}}
      <div class="overflow-y-auto flex-1 px-4 sm:px-6 py-4 sm:py-5 space-y-4 sm:space-y-5">

        {{-- Customer + Meta row --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

          {{-- Customer Info --}}
          <div class="bg-surface-50 rounded-xl p-4 border border-surface-100">
            <p class="text-[10px] font-semibold text-surface-400 uppercase tracking-wider mb-2">Customer</p>
            <div class="flex items-center gap-3 mb-3">
              <div id="od-avatar" class="h-9 w-9 rounded-full bg-brand-50 text-brand-600 flex items-center justify-center text-sm font-bold border border-brand-100 shrink-0"></div>
              <div class="min-w-0">
                <p id="od-customer-name" class="text-sm font-semibold text-surface-900 truncate"></p>
                <p id="od-customer-email" class="text-xs text-surface-400 truncate"></p>
              </div>
            </div>
            <div class="space-y-1">
              <div class="flex items-center gap-1.5 text-xs text-surface-500">
                <svg class="w-3.5 h-3.5 text-surface-300 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                <span id="od-customer-phone">—</span>
              </div>
            </div>
          </div>

          {{-- Order Meta --}}
          <div class="bg-surface-50 rounded-xl p-4 border border-surface-100 space-y-2">
            <p class="text-[10px] font-semibold text-surface-400 uppercase tracking-wider mb-2">Order Info</p>
            <div class="flex justify-between text-xs">
              <span class="text-surface-400">Date Placed</span>
              <span id="od-created-at" class="font-medium text-surface-700"></span>
            </div>
            <div class="flex justify-between text-xs">
              <span class="text-surface-400">Total Amount</span>
              <span id="od-total" class="font-bold text-surface-900 text-sm"></span>
            </div>
            <div class="flex justify-between text-xs">
              <span class="text-surface-400">Payment</span>
              <span id="od-payment-badge" class="font-medium"></span>
            </div>
            <div class="flex justify-between text-xs">
              <span class="text-surface-400">Payment Status</span>
              <span id="od-payment-status-badge"></span>
            </div>
          </div>
        </div>

        {{-- Delivery Info --}}
        <div class="bg-surface-50 rounded-xl p-4 border border-surface-100">
          <p class="text-[10px] font-semibold text-surface-400 uppercase tracking-wider mb-3">Delivery / Pickup</p>
          <div id="od-delivery-content" class="text-sm text-surface-600 space-y-1"></div>
        </div>

        {{-- Delivery Proof --}}
        <div class="bg-surface-50 rounded-xl p-4 border border-surface-100">
          <p class="text-[10px] font-semibold text-surface-400 uppercase tracking-wider mb-3">Delivery Proof</p>
          <div id="od-proof-content" class="text-sm text-surface-600"></div>
        </div>

        {{-- Cancellation Record --}}
        <div id="od-cancel-record" class="hidden bg-red-50 rounded-xl p-4 border border-red-100">
          <p class="text-[10px] font-semibold text-red-500 uppercase tracking-wider mb-2">Cancellation Record</p>
          <div id="od-cancel-content" class="text-sm text-surface-600 space-y-1"></div>
        </div>

        {{-- Items --}}
        <div>
          <p class="text-[10px] font-semibold text-surface-400 uppercase tracking-wider mb-2">Items Ordered</p>
          <div class="rounded-xl border border-surface-100 overflow-x-auto">
            <table class="w-full text-xs min-w-[380px]">
              <thead class="bg-surface-50">
                <tr class="text-left text-surface-400 text-[10px] uppercase tracking-wider border-b border-surface-100">
                  <th class="px-4 py-2.5 font-medium">Product</th>
                  <th class="px-4 py-2.5 font-medium text-center">Qty</th>
                  <th class="px-4 py-2.5 font-medium text-right">Unit Price</th>
                  <th class="px-4 py-2.5 font-medium text-right">Subtotal</th>
                </tr>
              </thead>
              <tbody id="od-items-tbody" class="divide-y divide-surface-100"></tbody>
              <tfoot class="border-t border-surface-200 bg-surface-50">
                <tr>
                  <td colspan="3" class="px-4 py-2.5 text-right text-xs font-semibold text-surface-700">Total</td>
                  <td class="px-4 py-2.5 text-right text-sm font-bold text-surface-900" id="od-items-total"></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

      </div>{{-- /body --}}
    </div>
  </div>

  {{-- ── Admin Appointment Detail Modal ─────────────────────────────────── --}}
  <div id="admin-appt-modal" class="fixed inset-0 z-50 hidden flex items-end sm:items-center justify-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="ad-dialog-title">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeApptDetail()" aria-hidden="true"></div>
    <div id="ad-dialog-panel" tabindex="-1" class="relative bg-white w-full sm:max-w-lg max-h-[92vh] sm:max-h-[88vh] flex flex-col sm:rounded-2xl rounded-t-2xl shadow-2xl overflow-hidden" style="animation: od-in .18s ease-out">

      <div class="flex justify-center pt-3 pb-1 sm:hidden">
        <div class="w-10 h-1 bg-surface-200 rounded-full"></div>
      </div>

      {{-- Header --}}
      <div class="flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-b border-surface-100">
        <div>
          <h2 id="ad-dialog-title" class="text-base font-semibold text-surface-900">Appointment Details</h2>
          <p class="text-xs text-surface-400 mt-0.5" id="ad-service-label"></p>
        </div>
        <div class="flex items-center gap-2">
          <span id="ad-status-badge" class="text-[10px] font-semibold uppercase tracking-wide px-2.5 py-1 rounded border"></span>
          <button type="button" onclick="closeApptDetail()" class="ml-1 flex h-10 w-10 items-center justify-center rounded-xl text-surface-400 hover:bg-surface-50 hover:text-surface-700 transition-colors" aria-label="Close appointment details">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
      </div>

      {{-- Body --}}
      <div class="overflow-y-auto flex-1 px-4 sm:px-6 py-4 sm:py-5 space-y-4">

        {{-- Customer + Appointment row --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="bg-surface-50 rounded-xl p-4 border border-surface-100">
            <p class="text-[10px] font-semibold text-surface-400 uppercase tracking-wider mb-2">Customer</p>
            <div class="flex items-center gap-3">
              <div id="ad-avatar" class="h-9 w-9 rounded-full bg-brand-50 text-brand-600 flex items-center justify-center text-sm font-bold border border-brand-100 shrink-0"></div>
              <div class="min-w-0">
                <p id="ad-customer-name" class="text-sm font-semibold text-surface-900 truncate"></p>
                <p id="ad-customer-email" class="text-xs text-surface-400 truncate"></p>
              </div>
            </div>
          </div>

          <div class="bg-surface-50 rounded-xl p-4 border border-surface-100 space-y-2">
            <p class="text-[10px] font-semibold text-surface-400 uppercase tracking-wider mb-2">Appointment Info</p>
            <div class="flex justify-between text-xs">
              <span class="text-surface-400">Date & Time</span>
              <span id="ad-date" class="font-medium text-surface-700 text-right max-w-[140px]"></span>
            </div>
            <div class="flex justify-between text-xs">
              <span class="text-surface-400">Payment</span>
              <span id="ad-payment-badge"></span>
            </div>
            <div class="flex justify-between text-xs">
              <span class="text-surface-400">Amount</span>
              <span id="ad-amount" class="font-semibold text-surface-900"></span>
            </div>
          </div>
        </div>

        {{-- Notes --}}
        <div class="bg-surface-50 rounded-xl p-4 border border-surface-100">
          <p class="text-[10px] font-semibold text-surface-400 uppercase tracking-wider mb-2">Notes</p>
          <p id="ad-notes" class="text-sm text-surface-600 italic leading-relaxed"></p>
        </div>

        {{-- Feedback --}}
        <div id="ad-feedback-section" class="bg-surface-50 rounded-xl p-4 border border-surface-100">
          <p class="text-[10px] font-semibold text-surface-400 uppercase tracking-wider mb-2">Customer Feedback</p>
          <div id="ad-feedback-stars" class="flex gap-0.5 mb-1.5"></div>
          <p id="ad-feedback-comment" class="text-xs text-surface-500 italic"></p>
        </div>

      </div>
    </div>
  </div>

  <style>
    @keyframes od-in  { from { opacity:0; transform:scale(.97) translateY(8px); } to { opacity:1; transform:none; } }
    @keyframes od-up  { from { transform:translateY(100%); opacity:0; } to { transform:translateY(0); opacity:1; } }
    .animate-od-in { animation: od-in .18s ease-out; }
    .animate-od-up { animation: od-up .25s cubic-bezier(.32,1,.5,1); }
    #admin-order-modal > div:last-child { animation: od-in .18s ease-out; }
  </style>

  <script>
    /* ── tab switching ── */
    let adminNotifLoaded = false;

    function adminCsrfToken() {
      return document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    }

    function toggleAdminNotifPanel() {
      const panel = document.getElementById('admin-notif-panel');
      if (!panel) return;
      const isHidden = panel.classList.contains('hidden');
      panel.classList.toggle('hidden', !isHidden);
      document.getElementById('admin-notif-trigger')?.setAttribute('aria-expanded', String(isHidden));
      if (isHidden && !adminNotifLoaded) loadAdminNotifications();
    }

    function loadAdminNotifications() {
      fetch('{{ route('notifications') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
          adminNotifLoaded = true;
          renderAdminNotifications(data.notifications || []);
        })
        .catch(() => {
          const list = document.getElementById('admin-notif-list');
          if (list) list.innerHTML = '<div class="px-4 py-6 text-center text-xs text-surface-400">Could not load notifications.</div>';
        });
    }

    function renderAdminNotifications(items) {
      const list = document.getElementById('admin-notif-list');
      if (!list) return;

      if (!items.length) {
        list.innerHTML = '<div class="px-4 py-6 text-center text-xs text-surface-400">No notifications yet.</div>';
        return;
      }

      list.innerHTML = items.map(n => {
        const unread = !n.read_at;
        const message = escapeAdminHtml(n.data?.message || 'Notification');
        const createdAt = escapeAdminHtml(n.created_at || '');
        const url = String(n.data?.url || '').replace(/'/g, '&#39;');
        return `<button type="button" class="w-full text-left px-4 py-3 flex items-start gap-3 ${unread ? 'bg-brand-50' : ''} hover:bg-surface-50 transition-colors" onclick="readAdminNotification('${n.id}', '${url}', this)">
          <span class="flex-shrink-0 mt-1.5 w-2 h-2 rounded-full ${unread ? 'bg-red-500' : 'bg-surface-200'}"></span>
          <span class="flex-1 min-w-0">
            <span class="block text-xs text-surface-800 leading-snug">${message}</span>
            <span class="block text-[11px] text-surface-400 mt-0.5">${createdAt}</span>
          </span>
        </button>`;
      }).join('');
    }

    function escapeAdminHtml(value) {
      return String(value).replace(/[&<>"']/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
      }[char]));
    }

    function clearAdminNotifCount() {
      document.getElementById('admin-notif-count')?.remove();
    }

    function readAdminNotification(id, url, el) {
      fetch(`{{ url('/notifications') }}/${id}/read`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': adminCsrfToken(), 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
      }).finally(() => {
        el?.classList.remove('bg-brand-50');
        el?.querySelector('.bg-red-500')?.classList.replace('bg-red-500', 'bg-surface-200');
        clearAdminNotifCount();
        if (url) window.location = url;
      });
    }

    function markAdminNotificationsRead() {
      fetch('{{ route('notifications.read-all') }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': adminCsrfToken(), 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
      }).then(() => {
        clearAdminNotifCount();
        document.querySelectorAll('#admin-notif-list .bg-brand-50').forEach(el => el.classList.remove('bg-brand-50'));
        document.querySelectorAll('#admin-notif-list .bg-red-500').forEach(el => el.classList.replace('bg-red-500', 'bg-surface-200'));
      });
    }

    document.addEventListener('click', function(e) {
      const panel = document.getElementById('admin-notif-panel');
      if (!panel || panel.classList.contains('hidden')) return;
      const wrapper = panel.closest('.relative');
      if (wrapper && !wrapper.contains(e.target)) {
        panel.classList.add('hidden');
        document.getElementById('admin-notif-trigger')?.setAttribute('aria-expanded', 'false');
      }
    });

    function clearFeedbackBadge() {
      const badge = document.getElementById('admin-feedback-badge');
      if (!badge) return;
      localStorage.setItem('adminFeedbackSeenCount', badge.dataset.count || '0');
      badge.remove();
    }

    function restoreFeedbackBadgeState() {
      const badge = document.getElementById('admin-feedback-badge');
      if (!badge) return;
      const current = parseInt(badge.dataset.count || '0', 10);
      const seen = parseInt(localStorage.getItem('adminFeedbackSeenCount') || '0', 10);
      if (seen >= current) badge.remove();
    }

    /* Tabs are server-rendered: AdminController loads only the active tab's data
       and every tab control is a real link, so there is no client-side switch. */

    function enhanceServiceCards() {
      document.querySelectorAll('#tab-services form[action*="/services/"]').forEach(form => {
        const methodInput = form.querySelector('input[name="_method"]');
        if (!methodInput || methodInput.value.toUpperCase() !== 'PUT') return;

        const card = form.closest('div.border');
        if (!card || card.dataset.serviceEnhanced === 'true') return;
        card.dataset.serviceEnhanced = 'true';

        const name = form.querySelector('[name="name"]')?.value || 'Service';
        const feeValue = parseFloat(form.querySelector('[name="default_fee"]')?.value || '0');
        const isActive = !!form.querySelector('[name="is_active"]')?.checked;

        const detail = document.createElement('div');
        detail.className = 'service-detail-panel hidden border-t border-surface-100 bg-surface-50/60 p-4';
        while (card.firstChild) detail.appendChild(card.firstChild);

        const summary = document.createElement('div');
        summary.className = 'p-4 flex flex-col md:flex-row md:items-center gap-4';
        summary.innerHTML = `
          <div class="flex items-center gap-3 flex-1 min-w-0">
            <div class="w-10 h-10 rounded-lg border border-brand-100 bg-brand-50 text-brand-700 flex items-center justify-center shrink-0">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M18.25 7.5 18 8.25l-.25-.75a2 2 0 0 0-1.25-1.25L15.75 6l.75-.25a2 2 0 0 0 1.25-1.25L18 3.75l.25.75a2 2 0 0 0 1.25 1.25l.75.25-.75.25a2 2 0 0 0-1.25 1.25Z"/></svg>
            </div>
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <h3 class="service-summary-name text-sm font-semibold text-surface-900 truncate"></h3>
                <span class="service-summary-active text-[10px] font-medium px-2 py-0.5 rounded border"></span>
              </div>
              <p class="text-xs text-surface-400 mt-0.5">Customer-bookable service</p>
            </div>
          </div>
          <div class="md:w-36 text-xs">
            <p class="text-[10px] text-surface-400 uppercase tracking-wider">Default Fee</p>
            <p class="service-summary-fee text-surface-900 font-semibold"></p>
          </div>
          <button type="button" class="service-detail-toggle inline-flex items-center justify-center gap-1.5 border border-surface-200 text-surface-600 hover:text-surface-900 hover:border-surface-300 rounded-lg px-3 py-2 text-xs font-medium transition-colors" aria-expanded="false">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0Z"/></svg>
            <span>Details / Edit</span>
          </button>
        `;

        summary.querySelector('.service-summary-name').textContent = name;
        summary.querySelector('.service-summary-fee').textContent = 'PHP ' + feeValue.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const activeBadge = summary.querySelector('.service-summary-active');
        activeBadge.textContent = isActive ? 'Active' : 'Hidden';
        activeBadge.className += isActive ? ' bg-brand-50 text-brand-700 border-brand-100' : ' bg-surface-50 text-surface-400 border-surface-200';

        const toggle = summary.querySelector('.service-detail-toggle');
        toggle.addEventListener('click', () => {
          const isOpen = !detail.classList.contains('hidden');
          detail.classList.toggle('hidden', isOpen);
          toggle.setAttribute('aria-expanded', String(!isOpen));
          toggle.querySelector('span').textContent = isOpen ? 'Details / Edit' : 'Hide Details';
        });

        card.className = 'border border-surface-100 rounded-lg overflow-hidden hover:border-surface-200 transition-colors';
        card.appendChild(summary);
        card.appendChild(detail);
      });
    }

    function enhanceStatusControls() {
      document.querySelectorAll('.admin-status-form').forEach(form => {
        if (form.dataset.statusEnhanced === 'true') return;
        form.dataset.statusEnhanced = 'true';

        form.classList.add('hidden');

        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'inline-flex items-center gap-1 border border-surface-200 text-surface-500 hover:text-surface-900 hover:border-surface-300 rounded px-2.5 py-2 text-[10px] font-medium transition-colors';
        toggle.setAttribute('aria-expanded', 'false');
        toggle.innerHTML = `
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.37.774.78.907.21.068.414.153.61.253.382.194.84.15 1.194-.094l.739-.51a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.51.738c-.245.354-.288.812-.094 1.194.1.196.185.4.253.61.133.41.483.71.907.78l.894.149c.542.09.94.56.94 1.11v1.093c0 .55-.398 1.02-.94 1.11l-.894.149c-.424.07-.774.37-.907.78a6.03 6.03 0 01-.253.61c-.194.382-.15.84.094 1.194l.51.739c.32.448.269 1.061-.12 1.45l-.774.773a1.125 1.125 0 01-1.45.12l-.738-.51c-.354-.245-.812-.288-1.194-.094a6.03 6.03 0 01-.61.253c-.41.133-.71.483-.78.907l-.149.894c-.09.542-.56.94-1.11.94h-1.093c-.55 0-1.02-.398-1.11-.94l-.149-.894c-.07-.424-.37-.774-.78-.907a6.03 6.03 0 01-.61-.253c-.382-.194-.84-.15-1.194.094l-.739.51a1.125 1.125 0 01-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.51-.738c.245-.354.288-.812.094-1.194a6.03 6.03 0 01-.253-.61c-.133-.41-.483-.71-.907-.78l-.894-.149A1.125 1.125 0 013 13.546v-1.093c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.774-.37.907-.78.068-.21.153-.414.253-.61.194-.382.15-.84-.094-1.194l-.51-.739a1.125 1.125 0 01.12-1.45l.774-.773a1.125 1.125 0 011.45-.12l.738.51c.354.245.812.288 1.194.094.196-.1.4-.185.61-.253.41-.133.71-.483.78-.907l.149-.894Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0Z"/></svg>
          <span>Manage</span>
        `;

        toggle.addEventListener('click', () => {
          if (form.dataset.detailUrl) {
            window.location.href = form.dataset.detailUrl;
            return;
          }

          const isOpen = !form.classList.contains('hidden');
          form.classList.toggle('hidden', isOpen);
          toggle.setAttribute('aria-expanded', String(!isOpen));
          toggle.querySelector('span').textContent = isOpen ? 'Manage' : 'Hide';
        });

        form.parentElement?.insertBefore(toggle, form);
      });
    }

    document.addEventListener('DOMContentLoaded', () => {
      restoreFeedbackBadgeState();
      enhanceServiceCards();
      enhanceStatusControls();

      // The active tab arrives already rendered from the server.
      if (@json($activeTab) === 'feedbacks') clearFeedbackBadge();

      const selectAll = document.getElementById('admin-select-all-orders');
      if (selectAll) {
        selectAll.addEventListener('change', function () {
          document.querySelectorAll('.admin-order-cb').forEach(cb => { cb.checked = selectAll.checked; });
        });
      }

      const bulkForm = document.getElementById('admin-bulk-orders-form');
      if (bulkForm) {
        bulkForm.addEventListener('submit', function (e) {
          if (document.querySelectorAll('.admin-order-cb:checked').length === 0) {
            e.preventDefault();
            alert('Select at least one order.');
          }
        });
      }

      document.querySelectorAll('.admin-status-form').forEach(form => {
        form.addEventListener('submit', async (event) => {
          event.preventDefault();

          const button = form.querySelector('button[type="submit"], button:not([type])');
          const selects = Array.from(form.querySelectorAll('select'));
          const files = Array.from(form.querySelectorAll('input[type="file"]'));
          const originalLabel = button?.textContent.trim() || 'Save';
          const payload = new FormData(form);

          if (button) {
            button.textContent = button.dataset.savingLabel || 'Saving...';
            button.disabled = true;
          }
          selects.forEach(select => {
            select.disabled = true;
            select.classList.add('opacity-70');
          });
          files.forEach(file => {
            file.disabled = true;
            file.classList.add('opacity-70');
          });

          try {
            const response = await fetch(form.action, {
              method: 'POST',
              body: payload,
              headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
              },
            });

            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
              throw new Error(data.message || 'Update failed.');
            }

            updateStatusRow(form, data);
            showAdminToast(data.message || 'Saved successfully.');
          } catch (error) {
            showAdminToast(error.message || 'Update failed. Please try again.', 'error');
          } finally {
            selects.forEach(select => {
              select.disabled = false;
              select.classList.remove('opacity-70');
            });
            files.forEach(file => {
              file.disabled = false;
              file.classList.remove('opacity-70');
            });
            if (button) {
              button.textContent = originalLabel;
              button.disabled = false;
            }
          }
        });
      });

      document.querySelectorAll('form button[data-saving-label]').forEach(button => {
        const form = button.closest('form');
        if (!form || form.classList.contains('admin-status-form')) return;
        form.addEventListener('submit', () => {
          button.dataset.originalLabel = button.textContent.trim();
          button.textContent = button.dataset.savingLabel || 'Saving...';
          button.disabled = true;
          button.classList.add('opacity-70', 'cursor-wait');
        }, { once: true });
      });
    });

    function showAdminToast(message, type = 'success') {
      const stack = document.getElementById('admin-toast-stack');
      if (!stack) return;

      const toast = document.createElement('div');
      toast.className = [
        'pointer-events-auto min-w-[240px] max-w-sm rounded-lg border px-4 py-3 text-sm shadow-lg transition-all',
        type === 'error'
          ? 'bg-red-50 border-red-100 text-red-700'
          : 'bg-brand-50 border-brand-100 text-brand-700'
      ].join(' ');
      toast.textContent = message;
      stack.appendChild(toast);

      setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-6px)';
        setTimeout(() => toast.remove(), 180);
      }, 2800);
    }

    function paymentBadgeClass(paymentStatus) {
      const tones = {
        paid: 'bg-brand-50 text-brand-700 border-brand-100',
        pending_verification: 'bg-amber-50 text-amber-700 border-amber-100',
        rejected: 'bg-red-50 text-red-700 border-red-100',
        refunded: 'bg-purple-50 text-purple-700 border-purple-100',
        unpaid: 'bg-surface-50 text-surface-500 border-surface-200',
      };
      return 'admin-payment-badge text-[9px] font-semibold border px-1.5 py-0.5 rounded uppercase tracking-wide '
        + (tones[paymentStatus] || tones.unpaid);
    }

    function updateSelectPaymentStyle(select, paymentStatus) {
      select.classList.remove('text-brand-600', 'bg-brand-50', 'font-medium', 'text-surface-600');
      if (paymentStatus === 'paid') {
        select.classList.add('text-brand-600', 'bg-brand-50', 'font-medium');
      } else {
        select.classList.add('text-surface-600');
      }
    }

    function updateStatusRow(form, data) {
      const row = form.closest('tr');
      if (!row) return;

      const statusSelect = form.querySelector('select[name="status"]');
      const paymentSelect = form.querySelector('select[name="payment_status"]');
      const proofInput = form.querySelector('input[name="delivery_proof"]');
      const statusBadge = row.querySelector('.admin-status-badge');
      const paymentBadge = row.querySelector('.admin-payment-badge');

      if (statusSelect && data.status) statusSelect.value = data.status;
      if (paymentSelect && data.payment_status) {
        paymentSelect.value = data.payment_status;
        updateSelectPaymentStyle(paymentSelect, data.payment_status);
      }

      if (statusBadge && data.status) {
        const badgeMap = form.action.includes('/appointments/')
          ? APPT_STATUS_BADGE
          : STATUS_BADGE_CLASSES;
        statusBadge.textContent = data.status === 'delivered' && !data.customer_confirmed_at
          ? 'Delivered - Pending Confirmation'
          : (data.status_label || data.status.replace(/_/g, ' '));
        statusBadge.className = 'admin-status-badge px-2 py-0.5 rounded text-[10px] font-medium border '
          + (badgeMap[data.status] || 'bg-surface-50 text-surface-600 border-surface-200');
      }

      if (paymentBadge && data.payment_status) {
        paymentBadge.textContent = data.payment_label || (data.payment_status === 'paid' ? 'Paid' : 'Unpaid');
        paymentBadge.className = paymentBadgeClass(data.payment_status);
      }

      if (proofInput) proofInput.value = '';
      if (data.delivery_proof_url && !row.querySelector('.admin-proof-badge')) {
        const badgeWrap = paymentBadge?.parentElement;
        if (badgeWrap) {
          badgeWrap.insertAdjacentHTML('beforeend', '<span class="admin-proof-badge px-1.5 py-0.5 rounded text-[9px] font-semibold border bg-emerald-50 text-emerald-700 border-emerald-100 uppercase tracking-wide">Proof</span>');
        }
      }
    }

    /* ── Order Detail Modal ── */
    function escapeAdminHtml(value) {
      const node = document.createElement('div');
      node.textContent = String(value ?? '');
      return node.innerHTML;
    }

    function safeAdminUrl(value) {
      if (!value) return '';
      try {
        const url = new URL(String(value), window.location.origin);
        return ['http:', 'https:'].includes(url.protocol) ? url.href : '';
      } catch {
        return '';
      }
    }

    const STATUS_BADGE_CLASSES = {
      pending:          'bg-amber-50 text-amber-700 border-amber-100',
      confirmed:        'bg-blue-50 text-blue-700 border-blue-100',
      out_for_delivery: 'bg-indigo-50 text-indigo-700 border-indigo-100',
      delivered:        'bg-emerald-50 text-emerald-700 border-emerald-100',
      completed:        'bg-brand-50 text-brand-700 border-brand-100',
      cancelled:        'bg-red-50 text-red-600 border-red-100',
    };

    let _lastAdminDialogTrigger = null;

    function openOrderDetail(order) {
      const modal = document.getElementById('admin-order-modal');
      _lastAdminDialogTrigger = document.activeElement;

      // Header
      document.getElementById('od-order-number').textContent = order.order_number;
      const statusBadge = document.getElementById('od-status-badge');
      statusBadge.textContent = order.status === 'delivered' && !order.customer_confirmed_at
        ? 'Delivered - Pending Confirmation'
        : order.status.replace(/_/g, ' ');
      statusBadge.className = 'text-[10px] font-semibold uppercase tracking-wide px-2.5 py-1 rounded border '
        + (STATUS_BADGE_CLASSES[order.status] || 'bg-surface-50 text-surface-600 border-surface-200');

      // Customer
      document.getElementById('od-avatar').textContent = (order.customer_name || '?')[0].toUpperCase();
      document.getElementById('od-customer-name').textContent  = order.customer_name || 'N/A';
      document.getElementById('od-customer-email').textContent = order.customer_email || '—';
      document.getElementById('od-customer-phone').textContent = order.customer_phone || '—';

      // Order meta
      document.getElementById('od-created-at').textContent = order.created_at || '—';
      document.getElementById('od-total').textContent = '₱' + order.total_amount;

      const pm = order.payment_method;
      const pmBadge = document.getElementById('od-payment-badge');
      pmBadge.textContent = pm === 'gcash' ? 'GCash' : 'Cash on Delivery';
      pmBadge.className = pm === 'gcash' ? 'font-medium text-sky-600' : 'font-medium text-amber-600';

      const psBadge = document.getElementById('od-payment-status-badge');
      const paymentStatus = order.payment_status || 'unpaid';
      psBadge.textContent = paymentStatus.replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase());
      psBadge.className = paymentBadgeClass(paymentStatus).replace('admin-payment-badge', '');

      // Delivery
      const del = document.getElementById('od-delivery-content');
      if (order.delivery_method === 'pickup') {
        del.innerHTML = '<span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold border bg-purple-50 text-purple-700 border-purple-100 uppercase tracking-wide mb-1">Pick-up</span>'
          + '<p class="text-surface-400 text-xs">A. Arellano Ave. Mulawin, Orani, Philippines 2112</p>';
      } else {
        let html = '<span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold border bg-blue-50 text-blue-700 border-blue-100 uppercase tracking-wide mb-2">Delivery</span><div class="space-y-0.5 text-xs">';
        if (order.delivery_name)    html += `<p><span class="text-surface-400">Name:</span> <span class="font-medium text-surface-800">${escapeAdminHtml(order.delivery_name)}</span></p>`;
        if (order.delivery_phone)   html += `<p><span class="text-surface-400">Phone:</span> <span class="text-surface-700">${escapeAdminHtml(order.delivery_phone)}</span></p>`;
        if (order.delivery_address) html += `<p><span class="text-surface-400">Address:</span> <span class="text-surface-700">${escapeAdminHtml(order.delivery_address)}${order.delivery_city ? ', ' + escapeAdminHtml(order.delivery_city) : ''}</span></p>`;
        if (order.delivery_notes)   html += `<p class="text-surface-400 italic">Notes: ${escapeAdminHtml(order.delivery_notes)}</p>`;
        html += '</div>';
        del.innerHTML = html;
      }

      const proof = document.getElementById('od-proof-content');
      const proofUrl = safeAdminUrl(order.delivery_proof_url);
      if (proofUrl) {
        const confirmed = order.customer_confirmed_at
          ? `<p class="text-brand-700 font-medium mt-1">Customer confirmed: ${escapeAdminHtml(order.customer_confirmed_at)}</p>`
          : '<p class="text-amber-700 font-medium mt-1">Waiting for customer confirmation.</p>';
        proof.innerHTML = `
          <a href="${escapeAdminHtml(proofUrl)}" target="_blank" rel="noopener noreferrer" class="inline-block rounded-lg overflow-hidden border border-surface-100 bg-white mb-2">
            <img src="${escapeAdminHtml(proofUrl)}" alt="Delivery proof" class="w-full max-h-56 object-cover">
          </a>
          <p class="text-xs text-surface-500">Delivered: ${escapeAdminHtml(order.delivered_at || 'Pending timestamp')}</p>
          ${confirmed}
        `;
      } else {
        proof.innerHTML = '<p class="text-xs text-surface-400">No delivery proof uploaded yet. Upload a photo when marking this order as delivered.</p>';
      }

      const cancelRecord = document.getElementById('od-cancel-record');
      const cancelContent = document.getElementById('od-cancel-content');
      if (order.status === 'cancelled') {
        cancelContent.innerHTML = `
          <p><span class="text-surface-400">Cancelled:</span> <span class="font-medium text-surface-800">${escapeAdminHtml(order.cancelled_at || 'Recorded')}</span></p>
          <p><span class="text-surface-400">Reason:</span> <span class="text-surface-700">${escapeAdminHtml(order.cancel_reason || 'No reason provided.')}</span></p>
        `;
        cancelRecord.classList.remove('hidden');
      } else {
        cancelContent.innerHTML = '';
        cancelRecord.classList.add('hidden');
      }

      // Items table
      const tbody = document.getElementById('od-items-tbody');
      tbody.innerHTML = '';
      const items = Array.isArray(order.items) ? order.items : [];
      let grandTotal = 0;
      if (items.length) {
        items.forEach(line => {
          const qty   = parseInt(line.qty ?? line.quantity ?? 1);
          const price = parseFloat(line.price ?? 0);
          const name  = line.name ?? 'Item';
          const sub   = qty * price;
          grandTotal += sub;
          tbody.insertAdjacentHTML('beforeend',
            `<tr class="hover:bg-surface-50">
              <td class="px-4 py-2.5 font-medium text-surface-800">${escapeAdminHtml(name)}</td>
              <td class="px-4 py-2.5 text-center text-surface-600">${qty}</td>
              <td class="px-4 py-2.5 text-right text-surface-600">₱${price.toLocaleString('en-PH', {minimumFractionDigits:2})}</td>
              <td class="px-4 py-2.5 text-right font-semibold text-surface-900">₱${sub.toLocaleString('en-PH', {minimumFractionDigits:2})}</td>
            </tr>`);
        });
      } else {
        tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-4 text-center text-surface-400 text-xs">No line items recorded.</td></tr>';
      }
      document.getElementById('od-items-total').textContent = '₱' + grandTotal.toLocaleString('en-PH', {minimumFractionDigits:2});

      modal.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
      window.requestAnimationFrame(() => document.getElementById('od-dialog-panel')?.focus());
    }

    function closeOrderDetail() {
      const modal = document.getElementById('admin-order-modal');
      if (!modal || modal.classList.contains('hidden')) return;
      modal.classList.add('hidden');
      document.body.style.overflow = '';
      _lastAdminDialogTrigger?.focus?.();
    }

    /* ── Appointment Detail Modal ── */
    const APPT_STATUS_BADGE = {
      scheduled: 'bg-amber-50 text-amber-700 border-amber-100',
      confirmed:  'bg-blue-50 text-blue-700 border-blue-100',
      completed:  'bg-brand-50 text-brand-700 border-brand-100',
      cancelled:  'bg-red-50 text-red-600 border-red-100',
    };

    function openApptDetail(appt) {
      const modal = document.getElementById('admin-appt-modal');
      _lastAdminDialogTrigger = document.activeElement;

      document.getElementById('ad-service-label').textContent = appt.service;

      const badge = document.getElementById('ad-status-badge');
      badge.textContent = appt.status.replace(/_/g, ' ');
      badge.className = 'text-[10px] font-semibold uppercase tracking-wide px-2.5 py-1 rounded border '
        + (APPT_STATUS_BADGE[appt.status] || 'bg-surface-50 text-surface-600 border-surface-200');

      document.getElementById('ad-avatar').textContent = (appt.customer_name || '?')[0].toUpperCase();
      document.getElementById('ad-customer-name').textContent  = appt.customer_name || 'N/A';
      document.getElementById('ad-customer-email').textContent = appt.customer_email || '—';
      document.getElementById('ad-date').textContent = appt.appointment_at || '—';

      document.getElementById('ad-amount').textContent = 'PHP ' + (appt.appointment_amount || '0.00');

      const payBadge = document.getElementById('ad-payment-badge');
      const isPaid = appt.payment_status === 'paid';
      payBadge.textContent = isPaid ? 'Paid' : 'Unpaid';
      payBadge.className = isPaid
        ? 'text-[10px] font-semibold px-2 py-0.5 rounded border bg-brand-50 text-brand-700 border-brand-100'
        : 'text-[10px] font-semibold px-2 py-0.5 rounded border bg-surface-50 text-surface-500 border-surface-200';

      document.getElementById('ad-notes').textContent = appt.notes || 'No notes provided.';

      const feedbackSection = document.getElementById('ad-feedback-section');
      const starsEl  = document.getElementById('ad-feedback-stars');
      const commentEl = document.getElementById('ad-feedback-comment');
      if (appt.feedback_rating) {
        starsEl.innerHTML = '';
        for (let i = 1; i <= 5; i++) {
          const s = document.createElement('span');
          s.textContent = '�
';
          s.className = 'text-lg ' + (i <= appt.feedback_rating ? 'text-amber-400' : 'text-surface-200');
          starsEl.appendChild(s);
        }
        commentEl.textContent = appt.feedback_comment || '';
        feedbackSection.classList.remove('hidden');
      } else {
        feedbackSection.classList.add('hidden');
      }

      modal.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
      window.requestAnimationFrame(() => document.getElementById('ad-dialog-panel')?.focus());
    }

    function closeApptDetail() {
      const modal = document.getElementById('admin-appt-modal');
      if (!modal || modal.classList.contains('hidden')) return;
      modal.classList.add('hidden');
      document.body.style.overflow = '';
      _lastAdminDialogTrigger?.focus?.();
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeOrderDetail(); closeApptDetail(); } });

    /* ── Messages Tab ── */
    let _activeConvoId  = null;

    // Real implementation is installed once the picker is wired up below; the
    // no-op keeps the reply handler safe if the elements are not on the page.
    let clearReplyAttachment = () => {};
    let _convoTimer     = null;
    const _renderedMsgIds = new Set();

    function openConversation(convoId, customerName) {
      _activeConvoId = convoId;
      _renderedMsgIds.clear();
      document.getElementById('tab-messages')?.classList.add('thread-open');

      // Keep the open thread in the URL so a refresh (or a link passed to a
      // colleague) lands back on the same conversation.
      const url = new URL(window.location.href);
      url.searchParams.set('tab', 'messages');
      url.searchParams.set('convo', convoId);
      window.history.replaceState({}, '', url);

      // Show thread panel, hide empty state
      document.getElementById('thread-empty').style.display  = 'none';
      const panel = document.getElementById('thread-panel');
      panel.style.display = 'flex';

      // Set header
      document.getElementById('thread-name').textContent   = customerName;
      document.getElementById('thread-avatar').textContent = customerName.charAt(0).toUpperCase();

      // Highlight active conversation
      document.querySelectorAll('.convo-btn').forEach(btn => {
        const isActive = parseInt(btn.dataset.convoId) === convoId;
        btn.classList.toggle('bg-brand-50', isActive);
        btn.classList.toggle('border-l-2', isActive);
        btn.classList.toggle('border-brand-500', isActive);
        if (isActive) {
          const badge = btn.querySelector('.bg-red-500');
          if (badge) badge.remove();
        }
      });

      // Wire reply via AJAX
      const form = document.getElementById('reply-form');
      form.onsubmit = async function (e) {
        e.preventDefault();
        const body  = document.getElementById('reply-body');
        const files = document.getElementById('reply-attachment');

        // An attachment on its own is a valid reply, so this cannot bail out
        // on empty text alone.
        if (!body.value.trim() && !files?.files[0]) return;

        const fd   = new FormData(form);
        const sbtn = form.querySelector('button[type=submit]');
        sbtn.disabled = true;
        try {
          // Ask for JSON so a rejected file comes back as a 422 we can read.
          // Without this Laravel answers with a redirect, fetch follows it, and
          // a failed upload looks like success.
          const response = await fetch(`{{ url('/admin/conversations') }}/${convoId}/reply`, {
            method: 'POST',
            body: fd,
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
              'Accept': 'application/json',
            },
          });

          if (!response.ok) {
            const problem = await response.json().catch(() => null);
            const detail = problem?.errors ? Object.values(problem.errors).flat()[0] : problem?.message;
            throw new Error(detail || 'Message could not be sent.');
          }

          body.value = '';
          body.style.height = 'auto';
          clearReplyAttachment();
          loadThread(convoId, true);
        } catch (error) {
          showAdminToast(error.message || 'Message could not be sent. Please try again.', 'error');
        }
        sbtn.disabled = false;
      };

      loadThread(convoId, true);

      clearInterval(_convoTimer);
      _convoTimer = setInterval(() => {
        if (_activeConvoId === convoId) loadThread(convoId, false);
      }, 5000);
    }

    function closeMobileConversation() {
      document.getElementById('tab-messages')?.classList.remove('thread-open');
      _activeConvoId = null;
      clearInterval(_convoTimer);

      const url = new URL(window.location.href);
      url.searchParams.delete('convo');
      window.history.replaceState({}, '', url);

      document.querySelector('.convo-btn')?.focus();
    }

    // Filenames come from customers, so they are set with textContent rather
    // than interpolated into markup.
    function buildAdminAttachment(att) {
      const link = document.createElement('a');
      link.href = att.url;
      link.target = '_blank';
      link.rel = 'noopener';

      if (att.is_image) {
        link.className = 'block overflow-hidden rounded-xl border border-surface-200 max-w-[220px]';
        const img = document.createElement('img');
        img.src = att.url;
        img.alt = att.name || '';
        img.loading = 'lazy';
        img.className = 'block w-full h-auto';
        link.appendChild(img);
        return link;
      }

      link.className = 'flex items-center gap-2.5 rounded-xl border border-surface-200 bg-white px-3 py-2.5 max-w-[220px] hover:border-brand-400 transition-colors';
      link.innerHTML = '<svg class="w-5 h-5 text-brand-600 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14 2v6h6"/></svg>';

      const meta = document.createElement('span');
      meta.className = 'min-w-0';
      const nameEl = document.createElement('span');
      nameEl.className = 'block truncate text-[12px] font-semibold text-surface-800';
      nameEl.textContent = att.name || 'Attachment';
      const sizeEl = document.createElement('span');
      sizeEl.className = 'block text-[10px] text-surface-400';
      sizeEl.textContent = att.size_label || '';
      meta.append(nameEl, sizeEl);
      link.appendChild(meta);
      return link;
    }

    function loadThread(convoId, initial) {
      const scrollToBottom = initial;
      fetch(`{{ url('/admin/conversations') }}/${convoId}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(r => r.json())
      .then(data => {
        const box = document.getElementById('thread-messages');
        const wasAtBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 60;

        if (!data.messages || !data.messages.length) {
          box.innerHTML = '<p class="text-sm text-surface-400 text-center py-12">No messages yet.</p>';
          _renderedMsgIds.clear();
          return;
        }

        // Only append messages that are not on screen yet. Rebuilding the whole
        // thread every poll wiped text selection and fought the scroll position
        // while staff were reading.
        if (initial) {
          box.innerHTML = '';
          _renderedMsgIds.clear();
        }

        data.messages.forEach(msg => {
          if (_renderedMsgIds.has(msg.id)) return;
          _renderedMsgIds.add(msg.id);
          const wrap = document.createElement('div');
          wrap.className = `flex items-end gap-2 ${msg.is_admin ? 'justify-end' : 'justify-start'}`;

          if (!msg.is_admin) {
            const av = document.createElement('div');
            av.className = 'w-7 h-7 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center text-[10px] font-bold flex-shrink-0 mb-4';
            av.textContent = msg.sender.charAt(0).toUpperCase();
            wrap.appendChild(av);
          }

          const inner = document.createElement('div');
          inner.className = `max-w-[75%] flex flex-col ${msg.is_admin ? 'items-end' : 'items-start'} gap-0.5`;

          if (msg.attachment) inner.appendChild(buildAdminAttachment(msg.attachment));

          // Attachment-only messages carry no body, so skip the empty bubble.
          if (msg.body) {
            const bbl = document.createElement('div');
            bbl.className = msg.is_admin
              ? 'admin-chat-bubble admin-chat-bubble--mine'
              : 'admin-chat-bubble admin-chat-bubble--customer';
            bbl.textContent = msg.body;
            inner.appendChild(bbl);
          }

          const ts = document.createElement('span');
          ts.className = 'text-[10px] text-surface-400 px-1';
          ts.textContent = msg.created_at;
          inner.appendChild(ts);

          wrap.appendChild(inner);
          box.appendChild(wrap);
        });

        if (scrollToBottom || wasAtBottom) box.scrollTop = box.scrollHeight;
      })
      .catch(() => {});
    }

    // Auto-grow reply textarea
    document.addEventListener('DOMContentLoaded', () => {
      const rb = document.getElementById('reply-body');
      if (rb) {
        rb.addEventListener('input', () => {
          rb.style.height = 'auto';
          rb.style.height = Math.min(rb.scrollHeight, 120) + 'px';
        });
      }

      // --- Reply attachment picker ---------------------------------------
      const REPLY_MAX_BYTES = {{ \App\Support\MessageAttachment::MAX_KB }} * 1024;
      const rInput   = document.getElementById('reply-attachment');
      const rBtn     = document.getElementById('reply-attach-btn');
      const rPreview = document.getElementById('reply-attach-preview');
      const rThumb   = document.getElementById('reply-attach-thumb');
      const rIcon    = document.getElementById('reply-attach-icon');
      const rName    = document.getElementById('reply-attach-name');
      const rSize    = document.getElementById('reply-attach-size');
      let rThumbUrl = null;

      if (rInput && rBtn) {
        // Assigned to the outer binding so the reply handler in
        // openConversation() can reset the picker after a successful send.
        clearReplyAttachment = () => {
          rInput.value = '';
          rPreview.classList.add('hidden');
          rPreview.classList.remove('flex');
          if (rThumbUrl) { URL.revokeObjectURL(rThumbUrl); rThumbUrl = null; }
          rThumb.classList.add('hidden');
          rIcon.classList.add('hidden');
        };

        rBtn.addEventListener('click', () => rInput.click());
        document.getElementById('reply-attach-clear').addEventListener('click', clearReplyAttachment);

        rInput.addEventListener('change', () => {
          const file = rInput.files[0];
          if (!file) return clearReplyAttachment();

          if (file.size > REPLY_MAX_BYTES) {
            alert('That file is larger than {{ round(\App\Support\MessageAttachment::MAX_KB / 1024) }} MB. Please choose a smaller one.');
            return clearReplyAttachment();
          }

          rName.textContent = file.name;
          rSize.textContent = file.size >= 1048576
            ? (file.size / 1048576).toFixed(1) + ' MB'
            : Math.max(1, Math.round(file.size / 1024)) + ' KB';

          if (rThumbUrl) URL.revokeObjectURL(rThumbUrl);
          if (file.type.startsWith('image/')) {
            rThumbUrl = URL.createObjectURL(file);
            rThumb.src = rThumbUrl;
            rThumb.classList.remove('hidden');
            rIcon.classList.add('hidden');
          } else {
            rThumbUrl = null;
            rThumb.classList.add('hidden');
            rIcon.classList.remove('hidden');
          }

          rPreview.classList.remove('hidden');
          rPreview.classList.add('flex');
        });

      }

      // Reopen the conversation named in ?convo= after a reload.
      const wanted = new URLSearchParams(window.location.search).get('convo');
      if (!wanted) return;

      const btn = document.querySelector(`.convo-btn[data-convo-id="${CSS.escape(wanted)}"]`);
      if (btn) openConversation(parseInt(wanted, 10), btn.dataset.customerName);
    });

    // Stop polling a thread nobody is looking at.
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        clearInterval(_convoTimer);
      } else if (_activeConvoId !== null) {
        loadThread(_activeConvoId, false);
        clearInterval(_convoTimer);
        _convoTimer = setInterval(() => {
          if (_activeConvoId !== null) loadThread(_activeConvoId, false);
        }, 5000);
      }
    });
  </script>
</body>
</html>
