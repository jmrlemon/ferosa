<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <title>@yield('title', 'Ferosa Landscaping Services')</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">

  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: {
              50: '#f0faf0',
              100: '#d9f2d9',
              200: '#b3e6b3',
              300: '#7ad17a',
              400: '#4aba4a',
              500: '#2d9a2d',
              600: '#1f7a1f',
              700: '#1a6320',
              800: '#184f1e',
              900: '#15411c',
              950: '#0a230e',
            },
            surface: {
              0: '#ffffff',
              50: '#fafafa',
              100: '#f5f5f5',
              200: '#e5e5e5',
              300: '#d4d4d4',
              400: '#a3a3a3',
              500: '#737373',
              600: '#525252',
              700: '#404040',
              800: '#262626',
              900: '#171717',
            }
          },
          fontFamily: {
            display: ['"Playfair Display"', 'serif'],
            sans: ['"Inter"', 'system-ui', 'sans-serif'],
          },
          boxShadow: {
            'soft': '0 1px 3px 0 rgba(0,0,0,0.04), 0 1px 2px -1px rgba(0,0,0,0.04)',
            'card': '0 2px 8px -2px rgba(0,0,0,0.06), 0 1px 2px -1px rgba(0,0,0,0.04)',
          }
        }
      }
    }
  </script>
  <style>
    * { font-family: 'Inter', system-ui, sans-serif; }
    .font-display { font-family: 'Playfair Display', serif; }

    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #e5e5e5; border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #d4d4d4; }

    .sidebar-transition { transition: transform 0.25s ease; }

    .nav-link {
      transition: all 0.15s ease;
      border-radius: 8px;
      margin: 0 12px;
    }
    .nav-link:hover { background: rgba(0,0,0,0.04); color: #171717; }
    .nav-link.active { background: #f0faf0; color: #1a6320; font-weight: 600; }

    /* When running inside the Android WebView app:
       • Hide the web mobile header & bottom nav — the native app provides its own
       • Make the body and content wrapper scrollable (no fixed-height clipping)
       • Add bottom padding so content clears the native bottom nav bar */
    body.in-app header.mobile-app-header {
      display: none !important;
    }
    /* Hide the web bottom nav bar — the native app has its own */
    body.in-app nav[aria-label="Primary"] {
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
      padding-bottom: 80px;
    }
  </style>

  @yield('styles')

  <script>
    window.addEventListener('pageshow', function (event) {
      if (event.persisted) {
        window.location.reload();
      }
    });
  </script>
</head>
<body class="flex h-screen bg-surface-50 text-surface-900 overflow-hidden font-sans antialiased" id="app-body">

  <!-- Mobile Overlay -->
  <div id="mobile-overlay" class="fixed inset-0 bg-black/20 z-40 hidden lg:hidden backdrop-blur-sm transition-opacity opacity-0" onclick="toggleSidebar()"></div>

  <!-- Sidebar -->
  <aside id="customer-sidebar" class="fixed lg:static inset-y-0 left-0 w-[260px] bg-white border-r border-surface-200 text-surface-600 flex flex-col justify-between flex-shrink-0 z-50 transform -translate-x-full lg:translate-x-0 sidebar-transition">
    <div class="overflow-y-auto flex-1">

      <!-- Brand -->
      <div class="px-6 py-5 flex items-center justify-between">
        <a href="{{ route('home') }}" class="flex items-center gap-3">
          <div class="w-9 h-9 bg-brand-600 rounded-lg flex items-center justify-center">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
              <path d="M12 3C12 3 7 6.5 7 12c0 2.76 1.34 5.22 3.4 6.74.38-.48.93-.74 1.6-.74s1.22.26 1.6.74C15.66 17.22 17 14.76 17 12c0-5.5-5-9-5-9z" fill="white"/>
            </svg>
          </div>
          <span class="font-semibold text-lg text-surface-900 tracking-tight">Ferosa</span>
        </a>
        <button class="lg:hidden text-surface-400 hover:text-surface-600 p-1" onclick="toggleSidebar()">
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
        <a href="{{ route('shop') }}" class="nav-link flex items-center gap-3 w-full text-left px-4 py-2.5 text-[13px] {{ request()->routeIs('shop') ? 'active' : '' }}">
          <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
          Shop
        </a>
        @auth
        <a href="{{ route('orders') }}" class="nav-link flex items-center gap-3 w-full text-left px-4 py-2.5 text-[13px] {{ request()->routeIs('orders') || request()->routeIs('orders.*') || request()->routeIs('checkout*') ? 'active' : '' }}">
          <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
          Orders
        </a>
        <a href="{{ route('schedule') }}" class="nav-link flex items-center gap-3 w-full text-left px-4 py-2.5 text-[13px] {{ request()->routeIs('schedule') ? 'active' : '' }}">
          <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
          Schedule
        </a>
        @endauth

        <span class="px-7 pt-5 pb-2 text-[11px] font-semibold text-surface-400 uppercase tracking-wider">Tools</span>

        <a href="{{ route('estimator') }}" class="nav-link flex items-center gap-3 w-full text-left px-4 py-2.5 text-[13px] {{ request()->routeIs('estimator') ? 'active' : '' }}">
          <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
          Cost Estimator
        </a>


        @auth
        <span class="px-7 pt-5 pb-2 text-[11px] font-semibold text-surface-400 uppercase tracking-wider">Account</span>

        <a href="{{ route('account') }}" class="nav-link flex items-center gap-3 w-full text-left px-4 py-2.5 text-[13px] {{ request()->routeIs('account') ? 'active' : '' }}">
          <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
          Profile
        </a>

        <a href="{{ route('feedback') }}" class="nav-link flex items-center gap-3 w-full text-left px-4 py-2.5 text-[13px] {{ request()->routeIs('feedback') ? 'active' : '' }}">
          <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
          Feedback
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
    <div class="p-3 border-t border-surface-100">
      @auth
      <form id="logout-form" method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="button" onclick="document.getElementById('logout-form').submit();" class="w-full flex items-center gap-3 px-4 py-2.5 text-[13px] font-medium text-surface-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
          <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
          Sign Out
        </button>
      </form>
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
      <button class="w-9 h-9 flex items-center justify-center text-surface-500 hover:text-surface-700 rounded-lg transition-colors" onclick="toggleSidebar()">
        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
      </button>
    </header>

    <!-- Page Content -->
    <main class="flex-1 overflow-y-auto relative w-full h-full bg-surface-50">
      @yield('content')
    </main>
  </div>

  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById('customer-sidebar');
      const overlay = document.getElementById('mobile-overlay');

      if (sidebar.classList.contains('-translate-x-full')) {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
        setTimeout(() => overlay.classList.remove('opacity-0'), 10);
      } else {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('opacity-0');
        setTimeout(() => overlay.classList.add('hidden'), 300);
      }
    }
  </script>
  @yield('scripts')
</body>
</html>
