<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - Ferosa Landscaping</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Inter', 'sans-serif'],
            display: ['Playfair Display', 'serif'],
          },
          colors: {
            brand: {
              50:  '#f0fdf4', 100: '#dcfce7', 200: '#bbf7d0', 300: '#86efac',
              400: '#4ade80', 500: '#22c55e', 600: '#16a34a', 700: '#15803d',
              800: '#166534', 900: '#14532d', 950: '#052e16',
            },
            surface: {
              0: '#ffffff', 50: '#fafafa', 100: '#f4f4f5', 200: '#e4e4e7',
              300: '#d4d4d8', 400: '#a1a1aa', 500: '#71717a', 600: '#52525b',
              700: '#3f3f46', 800: '#27272a', 900: '#18181b',
            }
          }
        }
      }
    }
  </script>
  <style>
    .tab-content { display: none; }
    .tab-content.active { display: block; }
    .tab-btn { transition: all 0.15s; }
    .tab-btn:hover { background-color: rgba(0,0,0,0.03); }
    .tab-btn.active { background-color: rgb(240 253 244); color: rgb(21 128 61); font-weight: 500; }
    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #d4d4d8; border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #a1a1aa; }
  </style>
  <script>
    window.addEventListener('pageshow', function (event) {
      if (event.persisted) window.location.reload();
    });
  </script>
</head>
<body class="flex h-screen bg-surface-50 text-surface-800 overflow-hidden font-sans antialiased">
  @php
    $isAdmin = auth()->user()?->isAdmin();
    $isStaffOrAdmin = auth()->user()?->isStaffOrAdmin();
  @endphp

  <!-- Sidebar -->
  <aside class="w-56 bg-white border-r border-surface-100 flex flex-col justify-between flex-shrink-0 z-20">
    <div class="overflow-y-auto">
      <div class="px-5 py-5 border-b border-surface-100 flex items-center gap-2 sticky top-0 bg-white z-10">
        <div class="w-7 h-7 bg-brand-600 rounded-lg flex items-center justify-center">
          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        </div>
        <span class="font-semibold text-sm text-surface-900">Ferosa Admin</span>
      </div>

      <nav class="flex flex-col w-full py-3 px-3 space-y-0.5">
        <p class="text-[10px] font-semibold text-surface-400 uppercase tracking-wider px-2 mb-1">Dashboard</p>
        <button onclick="switchTab('overview')" class="tab-btn active flex items-center gap-2.5 w-full text-left px-2.5 py-2 text-[13px] text-surface-500 rounded-lg" id="btn-overview">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
          Overview
        </button>

        <p class="text-[10px] font-semibold text-surface-400 uppercase tracking-wider px-2 mt-4 mb-1">Manage</p>
        <button onclick="switchTab('appointments')" class="tab-btn flex items-center gap-2.5 w-full text-left px-2.5 py-2 text-[13px] text-surface-500 rounded-lg" id="btn-appointments">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          Appointments
        </button>
        <button onclick="switchTab('orders')" class="tab-btn flex items-center gap-2.5 w-full text-left px-2.5 py-2 text-[13px] text-surface-500 rounded-lg" id="btn-orders">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
          Orders
        </button>
        <button onclick="switchTab('services')" class="tab-btn flex items-center gap-2.5 w-full text-left px-2.5 py-2 text-[13px] text-surface-500 rounded-lg" id="btn-services">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          Services
        </button>
        <button onclick="switchTab('products')" class="tab-btn flex items-center gap-2.5 w-full text-left px-2.5 py-2 text-[13px] text-surface-500 rounded-lg" id="btn-products">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
          Products
        </button>

        <p class="text-[10px] font-semibold text-surface-400 uppercase tracking-wider px-2 mt-4 mb-1">System</p>
        <button onclick="switchTab('archived')" class="tab-btn flex items-center gap-2.5 w-full text-left px-2.5 py-2 text-[13px] text-surface-500 rounded-lg" id="btn-archived">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
          Archived
        </button>
        <button onclick="switchTab('audit')" class="tab-btn flex items-center gap-2.5 w-full text-left px-2.5 py-2 text-[13px] text-surface-500 rounded-lg" id="btn-audit">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          Audit Logs
        </button>
        <button onclick="switchTab('feedbacks')" class="tab-btn flex items-center gap-2.5 w-full text-left px-2.5 py-2 text-[13px] text-surface-500 rounded-lg" id="btn-feedbacks">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
          Feedbacks
        </button>
        @if($isAdmin)
          <button onclick="switchTab('users')" class="tab-btn flex items-center gap-2.5 w-full text-left px-2.5 py-2 text-[13px] text-surface-500 rounded-lg" id="btn-users">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            Users
          </button>
        @endif
      </nav>
    </div>

    <div class="p-3 border-t border-surface-100 space-y-1">
      <a href="{{ route('home') }}" class="flex items-center gap-2 w-full px-2.5 py-2 text-[13px] text-surface-400 hover:text-surface-700 hover:bg-surface-50 rounded-lg transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to App
      </a>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="w-full flex items-center gap-2 px-2.5 py-2 text-[13px] text-red-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
          Sign Out
        </button>
      </form>
    </div>
  </aside>

  <!-- Main Content -->
  <div class="flex-1 overflow-y-auto w-full">
    <main class="max-w-6xl px-6 py-6">
    
    @if($lowStockProducts->count() > 0)
      <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-5 flex items-start gap-3 shadow-sm">
        <div class="text-red-500 mt-0.5">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <div>
          <h3 class="text-sm font-semibold text-red-800">Low Stock Alert</h3>
          <p class="text-xs text-red-600 mt-0.5">The following products are running low on stock (5 or fewer) and need to be reordered:</p>
          <ul class="text-xs text-red-700 mt-2 list-disc pl-4 space-y-0.5">
            @foreach($lowStockProducts as $lowStock)
              <li><strong>{{ $lowStock->name }}</strong> (Only {{ $lowStock->stock_qty }} left in inventory)</li>
            @endforeach
          </ul>
        </div>
      </div>
    @endif

    @if (session('status'))
      <div class="bg-brand-50 border border-brand-100 text-brand-700 px-4 py-2.5 rounded-lg text-sm mb-5">
        {{ session('status') }}
      </div>
    @endif

    @if ($errors->any())
      <div class="bg-red-50 border border-red-100 text-red-600 px-4 py-2.5 rounded-lg text-sm mb-5">
        <ul class="list-disc pl-4 space-y-0.5">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <!-- OVERVIEW TAB -->
    <div id="tab-overview" class="tab-content active space-y-5">
      <!-- Filters -->
      <div class="bg-white rounded-xl border border-surface-100 p-5">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
          <div>
            <h2 class="text-sm font-semibold text-surface-900">Revenue Overview</h2>
            <p class="text-xs text-surface-400 mt-0.5">Filter your dashboard metrics and sales data.</p>
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
            <a href="{{ route('admin.dashboard', ['tab' => 'overview']) }}" class="text-xs text-surface-400 hover:text-surface-700 py-1.5 transition-colors">Reset</a>
          </form>
        </div>
      </div>

      <!-- KPI Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
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
          <h2 class="text-sm font-semibold text-surface-900 mb-5">Monthly Sales (Last 6 Months)</h2>
          @php $maxMonthly = max(1, (float) $monthlySales->max('total')); @endphp
          <div class="grid grid-cols-6 gap-3 items-end min-h-[160px]">
            @foreach ($monthlySales as $row)
              @php $heightPercent = (int) round(($row['total'] / $maxMonthly) * 100); @endphp
              <div class="flex flex-col items-center gap-2 group relative">
                <div class="absolute -top-8 bg-surface-900 text-white text-[10px] py-1 px-2 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-10">
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
            <h2 class="text-sm font-semibold text-surface-900">Sales by Status</h2>
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
          <h2 class="text-sm font-semibold text-surface-900 mb-4">Week vs Week Revenue</h2>
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
    <div id="tab-appointments" class="tab-content">
      <div class="bg-white rounded-xl border border-surface-100 overflow-hidden">
        <div class="p-5 border-b border-surface-100 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
          <div>
            <h2 class="text-sm font-semibold text-surface-900">Manage Appointments</h2>
            <p class="text-xs text-surface-400 mt-0.5">Search by customer, service, or notes.</p>
          </div>
          <form method="GET" action="{{ route('admin.dashboard') }}" class="flex items-end gap-2">
            <input type="hidden" name="tab" value="appointments">
            <div class="relative">
              <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-surface-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
              <input type="search" name="appt_q" value="{{ $apptQ ?? request('appt_q') }}" placeholder="Name, email..."
                     class="pl-8 pr-3 py-1.5 border border-surface-200 rounded-lg text-xs outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 w-48 transition-colors">
            </div>
            <button type="submit" class="bg-surface-900 text-white rounded-lg px-4 py-1.5 text-xs font-medium hover:bg-surface-800 transition-colors">Search</button>
            <a href="{{ route('admin.dashboard', ['tab' => 'appointments']) }}" class="text-xs text-surface-400 hover:text-surface-700 py-1.5">Reset</a>
          </form>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-xs whitespace-nowrap">
            <thead>
              <tr class="text-left text-surface-400 text-[10px] uppercase tracking-wider border-b border-surface-100">
                <th class="px-5 py-3 font-medium">Customer</th>
                <th class="px-5 py-3 font-medium">Service</th>
                <th class="px-5 py-3 font-medium">Date</th>
                <th class="px-5 py-3 font-medium w-1/5">Notes</th>
                <th class="px-5 py-3 font-medium">Status</th>
                <th class="px-5 py-3 font-medium text-right">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
              @forelse ($appointments as $appt)
                <tr class="hover:bg-surface-50 transition-colors">
                  <td class="px-5 py-3">
                    <div class="flex items-center gap-2.5">
                      <div class="h-7 w-7 rounded-full bg-brand-50 text-brand-600 flex items-center justify-center text-[10px] font-bold border border-brand-100">
                        {{ strtoupper(substr($appt->user->name ?? '?', 0, 1)) }}
                      </div>
                      <div>
                        <p class="font-medium text-surface-900">{{ $appt->user->name ?? 'N/A' }}</p>
                        <p class="text-[10px] text-surface-400">{{ $appt->user->email ?? '' }}</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-5 py-3 text-surface-700">{{ $appt->serviceType->name ?? 'N/A' }}</td>
                  <td class="px-5 py-3 text-surface-500">
                    {{ $appt->appointment_at ? \Carbon\Carbon::parse($appt->appointment_at)->format('M d, Y') : 'N/A' }}
                  </td>
                  <td class="px-5 py-3 text-surface-400 whitespace-normal min-w-[160px]">{{ $appt->notes ?: '-' }}</td>
                  <td class="px-5 py-3">
                    @php
                      $apptBadge = match($appt->status) {
                        'scheduled' => 'bg-amber-50 text-amber-700 border-amber-100',
                        'confirmed' => 'bg-blue-50 text-blue-700 border-blue-100',
                        'completed' => 'bg-brand-50 text-brand-700 border-brand-100',
                        'cancelled' => 'bg-red-50 text-red-600 border-red-100',
                        default => 'bg-surface-50 text-surface-600 border-surface-200',
                      };
                    @endphp
                    <span class="px-2 py-0.5 rounded text-[10px] font-medium border {{ $apptBadge }}">
                      {{ ucfirst($appt->status) }}
                    </span>
                  </td>
                  <td class="px-5 py-3">
                    <div class="flex items-center justify-end gap-1.5">
                      <form method="POST" action="{{ route('admin.appointments.status', $appt) }}" class="flex items-center gap-1.5">
                        @csrf
                        @method('PUT')
                        <div class="flex flex-col gap-1 min-w-[90px]">
                          <select name="status" class="border border-surface-200 rounded px-2 py-0.5 text-[10px] text-surface-600 outline-none focus:border-brand-500 w-full" {{ $isStaffOrAdmin ? '' : 'disabled' }}>
                            <option value="scheduled" {{ $appt->status === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                            <option value="confirmed" {{ $appt->status === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                            <option value="completed" {{ $appt->status === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ $appt->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                          </select>
                          <select name="payment_status" class="border border-surface-200 rounded px-2 py-0.5 text-[10px] {{ $appt->payment_status === 'paid' ? 'text-brand-600 bg-brand-50 font-medium' : 'text-surface-600' }} outline-none focus:border-brand-500 w-full" {{ $isStaffOrAdmin ? '' : 'disabled' }}>
                            <option value="unpaid" {{ $appt->payment_status === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                            <option value="paid" {{ $appt->payment_status === 'paid' ? 'selected' : '' }}>Paid</option>
                          </select>
                        </div>
                        @if($isStaffOrAdmin)
                          <button class="bg-brand-600 text-white rounded px-2.5 py-2 text-[10px] font-medium hover:bg-brand-700 transition-colors h-full">Save</button>
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
                <tr><td class="px-5 py-8 text-surface-400 text-center" colspan="6">No appointments booked yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @if (method_exists($appointments, 'links'))
          <div class="p-4 border-t border-surface-100">
            {{ $appointments->appends(['tab' => 'appointments'])->links() }}
          </div>
        @endif
      </div>
    </div>

    <!-- ORDERS TAB -->
    <div id="tab-orders" class="tab-content">
      <div class="bg-white rounded-xl border border-surface-100 overflow-hidden">
        <div class="p-5 border-b border-surface-100">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <div>
              <h2 class="text-sm font-semibold text-surface-900">Manage Orders</h2>
              <p class="text-xs text-surface-400 mt-0.5">Review and update customer orders.</p>
            </div>
            <a href="{{ route('admin.reports.orders-csv', array_filter(['order_status' => request('order_status'), 'order_q' => request('order_q')])) }}"
               class="inline-flex items-center gap-1.5 text-xs font-medium border border-surface-200 bg-white px-3 py-1.5 rounded-lg text-surface-600 hover:bg-surface-50 transition-colors">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
              Export CSV
            </a>
          </div>

          <div class="flex flex-col xl:flex-row xl:items-end justify-between gap-3">
            <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-wrap items-end gap-2 flex-1">
              <input type="hidden" name="tab" value="orders">
              <div>
                <label class="block text-[10px] font-medium text-surface-400 mb-1">Status</label>
                <select name="order_status" class="border border-surface-200 rounded-lg px-3 py-1.5 text-xs outline-none focus:border-brand-500 text-surface-600 min-w-[120px]">
                  <option value="">All</option>
                  @foreach (['pending', 'confirmed', 'out_for_delivery', 'delivered', 'cancelled'] as $st)
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
                @foreach (['pending', 'confirmed', 'out_for_delivery', 'delivered', 'cancelled'] as $st)
                  <option value="{{ $st }}">{{ ucfirst(str_replace('_', ' ', $st)) }}</option>
                @endforeach
              </select>
              <button type="submit" class="bg-white border border-surface-200 text-surface-700 rounded-lg px-2.5 py-1 text-[10px] font-medium hover:bg-surface-50 transition-colors">Apply</button>
            </form>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-xs whitespace-nowrap">
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
                        'delivered' => 'bg-brand-50 text-brand-700 border-brand-100',
                        'cancelled' => 'bg-red-50 text-red-600 border-red-100',
                        default => 'bg-surface-50 text-surface-600 border-surface-200',
                      };
                    @endphp
                    <span class="px-2 py-0.5 rounded text-[10px] font-medium border {{ $orderBadge }}">
                      {{ ucfirst(str_replace('_', ' ', $order->status)) }}
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
                      </div>
                      @if($odm === 'delivery' && $order->delivery_address)
                        <p class="text-[9px] text-surface-400 truncate max-w-[140px]" title="{{ $order->delivery_address }}, {{ $order->delivery_city }}">
                          {{ $order->delivery_address }}, {{ $order->delivery_city }}
                        </p>
                      @endif
                    </div>
                  </td>
                  <td class="px-5 py-3">
                    <div class="flex items-center justify-end gap-1.5">
                      <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="flex items-center gap-1.5">
                        @csrf
                        @method('PUT')
                        <div class="flex flex-col gap-1 min-w-[100px]">
                          <select name="status" class="border border-surface-200 rounded px-2 py-0.5 text-[10px] text-surface-600 outline-none focus:border-brand-500 w-full" {{ $isAdmin ? '' : 'disabled' }}>
                            @foreach (['pending', 'confirmed', 'out_for_delivery', 'delivered', 'cancelled'] as $status)
                              <option value="{{ $status }}" {{ $order->status === $status ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                              </option>
                            @endforeach
                          </select>
                          <select name="payment_status" class="border border-surface-200 rounded px-2 py-0.5 text-[10px] {{ $order->payment_status === 'paid' ? 'text-brand-600 bg-brand-50 font-medium' : 'text-surface-600' }} outline-none focus:border-brand-500 w-full" {{ $isAdmin ? '' : 'disabled' }}>
                            <option value="unpaid" {{ $order->payment_status === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                            <option value="paid" {{ $order->payment_status === 'paid' ? 'selected' : '' }}>Paid</option>
                          </select>
                        </div>
                        @if($isAdmin)
                          <button type="submit" class="bg-brand-600 text-white rounded px-2.5 py-2 text-[10px] font-medium hover:bg-brand-700 transition-colors h-full">Save</button>
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
    <div id="tab-services" class="tab-content">
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-5">
        @if($isStaffOrAdmin)
        <div class="lg:col-span-1 bg-white rounded-xl border border-surface-100 p-5 h-fit">
          <h2 class="text-sm font-semibold text-surface-900 mb-4">Add Service</h2>
          <form method="POST" action="{{ route('admin.services.store') }}" class="space-y-3">
            @csrf
            <div>
              <label class="block text-[10px] font-medium text-surface-400 mb-1">Service Name</label>
              <input name="name" required placeholder="e.g. Lawn Mowing" class="w-full border border-surface-200 rounded-lg px-3 py-2 text-xs outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
            </div>
            <div>
              <label class="block text-[10px] font-medium text-surface-400 mb-1">Default Fee (PHP)</label>
              <input name="default_fee" type="number" step="0.01" min="0" required placeholder="500.00" class="w-full border border-surface-200 rounded-lg px-3 py-2 text-xs outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
            </div>
            <label class="flex items-center gap-2 text-xs cursor-pointer">
              <input type="checkbox" name="is_active" value="1" checked class="w-3.5 h-3.5 rounded border-surface-300 text-brand-600 focus:ring-brand-500">
              <span class="text-surface-600">Active (Visible to customers)</span>
            </label>
            <button class="w-full bg-surface-900 text-white rounded-lg py-2 text-xs font-medium hover:bg-surface-800 transition-colors">Add Service</button>
          </form>
        </div>
        @endif

        <div class="lg:col-span-3 bg-white rounded-xl border border-surface-100 overflow-hidden flex flex-col">
          <div class="p-5 border-b border-surface-100 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
            <div>
              <h2 class="text-sm font-semibold text-surface-900">Manage Services</h2>
              <p class="text-xs text-surface-400 mt-0.5">Search and update existing services.</p>
            </div>
            <form method="GET" action="{{ route('admin.dashboard') }}" class="flex items-end gap-2">
              <input type="hidden" name="tab" value="services">
              <div class="relative">
                <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-surface-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input type="search" name="service_q" value="{{ $serviceQ ?? request('service_q') }}" placeholder="e.g. Lawn"
                       class="pl-8 pr-3 py-1.5 border border-surface-200 rounded-lg text-xs outline-none focus:border-brand-500 w-44 transition-colors">
              </div>
              <button type="submit" class="bg-surface-900 text-white rounded-lg px-4 py-1.5 text-xs font-medium hover:bg-surface-800 transition-colors">Search</button>
              <a href="{{ route('admin.dashboard', ['tab' => 'services']) }}" class="text-xs text-surface-400 hover:text-surface-700 py-1.5">Reset</a>
            </form>
          </div>

          <div class="p-5 flex-1 space-y-3">
            @forelse ($services as $service)
              <div class="flex flex-col md:flex-row gap-3 items-center justify-between border border-surface-100 rounded-lg p-4 hover:border-surface-200 transition-colors">
                <form method="POST" action="{{ route('admin.services.update', $service) }}" class="flex-1 flex flex-wrap md:flex-nowrap gap-3 items-center w-full">
                  @csrf
                  @method('PUT')
                  <div class="flex-1 min-w-[160px]">
                    <label class="block text-[10px] font-medium text-surface-400 mb-0.5">Name</label>
                    <input name="name" value="{{ $service->name }}" required {{ $isStaffOrAdmin ? '' : 'disabled' }} class="w-full border-b border-surface-200 bg-transparent px-0 py-1 text-sm text-surface-800 font-medium outline-none focus:border-brand-500 transition-colors">
                  </div>
                  <div>
                    <label class="block text-[10px] font-medium text-surface-400 mb-0.5">Fee</label>
                    <input name="default_fee" type="number" step="0.01" min="0" value="{{ $service->default_fee }}" required {{ $isStaffOrAdmin ? '' : 'disabled' }} class="w-24 border-b border-surface-200 bg-transparent px-0 py-1 text-sm text-surface-800 font-semibold outline-none focus:border-brand-500 transition-colors">
                  </div>
                  <label class="flex items-center gap-1.5 text-xs cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ $service->is_active ? 'checked' : '' }} {{ $isStaffOrAdmin ? '' : 'disabled' }} class="w-3.5 h-3.5 rounded border-surface-300 text-brand-600 focus:ring-brand-500">
                    <span class="{{ $service->is_active ? 'text-brand-700' : 'text-surface-400' }}">Active</span>
                  </label>
                  @if($isStaffOrAdmin)
                    <button class="bg-brand-600 text-white rounded-lg px-3 py-1.5 text-[10px] font-medium hover:bg-brand-700 transition-colors">Save</button>
                  @endif
                </form>
                @if($isStaffOrAdmin)
                  <form method="POST" action="{{ route('admin.services.delete', $service) }}" onsubmit="return confirm('Archive this service?');">
                    @csrf
                    @method('DELETE')
                    <button class="p-1.5 text-surface-300 hover:text-red-500 rounded transition-colors" title="Archive">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                  </form>
                @endif
              </div>
            @empty
              <div class="text-center py-8 text-surface-400 text-xs">No service types added yet.</div>
            @endforelse
          </div>

          @if (method_exists($services, 'links'))
            <div class="p-4 border-t border-surface-100">
              {{ $services->appends(['tab' => 'services'])->links() }}
            </div>
          @endif
        </div>
      </div>
    </div>

    <!-- PRODUCTS TAB -->
    <div id="tab-products" class="tab-content">
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-5">
        @if($isStaffOrAdmin)
        <div class="lg:col-span-1 bg-white rounded-xl border border-surface-100 p-5 h-fit">
          <h2 class="text-sm font-semibold text-surface-900 mb-4">Add Product</h2>
          <form method="POST" action="{{ route('admin.products.store') }}" class="space-y-3">
            @csrf
            <div>
              <label class="block text-[10px] font-medium text-surface-400 mb-1">Product Name</label>
              <input name="name" required placeholder="e.g. Premium Potting Soil" class="w-full border border-surface-200 rounded-lg px-3 py-2 text-xs outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
            </div>
            <div>
              <label class="block text-[10px] font-medium text-surface-400 mb-1">Description</label>
              <textarea name="description" placeholder="Brief description" rows="2" class="w-full border border-surface-200 rounded-lg px-3 py-2 text-xs outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 resize-y transition-colors"></textarea>
            </div>
            <div>
              <label class="block text-[10px] font-medium text-surface-400 mb-1">Image URL <span class="text-surface-300 font-normal">(optional)</span></label>
              <input name="image_url" type="url" placeholder="https://…" class="w-full border border-surface-200 rounded-lg px-3 py-2 text-xs outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
            </div>
            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="block text-[10px] font-medium text-surface-400 mb-1">Price (₱)</label>
                <input name="price" type="number" step="0.01" min="0" required placeholder="0.00" class="w-full border border-surface-200 rounded-lg px-3 py-2 text-xs outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
              </div>
              <div>
                <label class="block text-[10px] font-medium text-surface-400 mb-1">Stock Qty</label>
                <input name="stock_qty" type="number" min="0" value="0" placeholder="0" class="w-full border border-surface-200 rounded-lg px-3 py-2 text-xs outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
              </div>
            </div>
            <div>
              <label class="block text-[10px] font-medium text-surface-400 mb-1">Category</label>
              <input name="category" required placeholder="plants, tools, soils..." class="w-full border border-surface-200 rounded-lg px-3 py-2 text-xs outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
            </div>
            <label class="flex items-center gap-2 text-xs cursor-pointer">
              <input type="checkbox" name="is_active" value="1" checked class="w-3.5 h-3.5 rounded border-surface-300 text-brand-600 focus:ring-brand-500">
              <span class="text-surface-600">Active (Visible in shop)</span>
            </label>
            <button class="w-full bg-surface-900 text-white rounded-lg py-2 text-xs font-medium hover:bg-surface-800 transition-colors">Add Product</button>
          </form>
        </div>
        @endif

        <div class="lg:col-span-3 bg-white rounded-xl border border-surface-100 overflow-hidden flex flex-col">
          <div class="p-5 border-b border-surface-100 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
            <div>
              <h2 class="text-sm font-semibold text-surface-900">Manage Products</h2>
              <p class="text-xs text-surface-400 mt-0.5">Search and update existing products.</p>
            </div>
            <form method="GET" action="{{ route('admin.dashboard') }}" class="flex items-end gap-2">
              <input type="hidden" name="tab" value="products">
              <div class="relative">
                <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-surface-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input type="search" name="product_q" value="{{ $productQ ?? request('product_q') }}" placeholder="e.g. Gravel, plants"
                       class="pl-8 pr-3 py-1.5 border border-surface-200 rounded-lg text-xs outline-none focus:border-brand-500 w-44 transition-colors">
              </div>
              <button type="submit" class="bg-surface-900 text-white rounded-lg px-4 py-1.5 text-xs font-medium hover:bg-surface-800 transition-colors">Search</button>
              <a href="{{ route('admin.dashboard', ['tab' => 'products']) }}" class="text-xs text-surface-400 hover:text-surface-700 py-1.5">Reset</a>
            </form>
          </div>

          <div class="p-5 flex-1 space-y-3">
            @forelse ($products as $product)
              <div class="border border-surface-100 rounded-lg p-4 hover:border-surface-200 transition-colors">
                <form method="POST" action="{{ route('admin.products.update', $product) }}" class="space-y-2">
                  @csrf
                  @method('PUT')
                  <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-start">
                    <div class="md:col-span-3">
                      <label class="block text-[10px] font-medium text-surface-400 mb-0.5">Name</label>
                      <input name="name" value="{{ $product->name }}" required {{ $isStaffOrAdmin ? '' : 'disabled' }} class="w-full border-b border-surface-200 bg-transparent px-0 py-1 text-sm text-surface-800 font-medium outline-none focus:border-brand-500 transition-colors">
                    </div>
                    <div class="md:col-span-2">
                      <label class="block text-[10px] font-medium text-surface-400 mb-0.5">Category</label>
                      <input name="category" value="{{ $product->category }}" required {{ $isStaffOrAdmin ? '' : 'disabled' }} class="w-full border-b border-surface-200 bg-transparent px-0 py-1 text-xs text-surface-600 outline-none focus:border-brand-500 transition-colors">
                    </div>
                    <div class="md:col-span-2">
                      <label class="block text-[10px] font-medium text-surface-400 mb-0.5">Price (₱)</label>
                      <input name="price" type="number" step="0.01" min="0" value="{{ $product->price }}" required {{ $isStaffOrAdmin ? '' : 'disabled' }} class="w-full border-b border-surface-200 bg-transparent px-0 py-1 text-sm text-surface-800 font-semibold outline-none focus:border-brand-500 transition-colors">
                    </div>
                    <div class="md:col-span-1">
                      <label class="block text-[10px] font-medium text-surface-400 mb-0.5">Stock</label>
                      <input name="stock_qty" type="number" min="0" value="{{ $product->stock_qty }}" {{ $isStaffOrAdmin ? '' : 'disabled' }}
                             class="w-full border-b border-surface-200 bg-transparent px-0 py-1 text-sm outline-none focus:border-brand-500 transition-colors
                             {{ $product->stock_qty === 0 ? 'text-red-500 font-semibold' : ($product->stock_qty <= 5 ? 'text-amber-600 font-semibold' : 'text-surface-700') }}">
                    </div>
                    <div class="md:col-span-4 flex items-center gap-3 justify-end pt-3">
                      <label class="flex items-center gap-1.5 text-xs cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ $product->is_active ? 'checked' : '' }} {{ $isStaffOrAdmin ? '' : 'disabled' }} class="w-3.5 h-3.5 rounded border-surface-300 text-brand-600 focus:ring-brand-500">
                        <span class="{{ $product->is_active ? 'text-brand-700' : 'text-surface-400' }}">Active</span>
                      </label>
                      @if($isStaffOrAdmin)
                        <button class="bg-brand-600 text-white rounded-lg px-3 py-1.5 text-[10px] font-medium hover:bg-brand-700 transition-colors">Save</button>
                      @endif
                    </div>
                  </div>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div>
                      <label class="block text-[10px] font-medium text-surface-400 mb-0.5">Description</label>
                      <input name="description" value="{{ $product->description }}" placeholder="Description" {{ $isStaffOrAdmin ? '' : 'disabled' }} class="w-full border-b border-surface-200 bg-transparent px-0 py-1 text-xs text-surface-500 outline-none focus:border-brand-500 transition-colors">
                    </div>
                    <div>
                      <label class="block text-[10px] font-medium text-surface-400 mb-0.5">Image URL</label>
                      <input name="image_url" type="url" value="{{ $product->image_url }}" placeholder="https://…" {{ $isStaffOrAdmin ? '' : 'disabled' }} class="w-full border-b border-surface-200 bg-transparent px-0 py-1 text-xs text-surface-500 outline-none focus:border-brand-500 transition-colors">
                    </div>
                  </div>
                </form>
                @if($isStaffOrAdmin)
                  <div class="flex justify-end mt-2 pt-2 border-t border-surface-100">
                    <form method="POST" action="{{ route('admin.products.delete', $product) }}" onsubmit="return confirm('Archive this product?');">
                      @csrf
                      @method('DELETE')
                      <button class="text-[10px] text-surface-400 hover:text-red-500 transition-colors">Archive</button>
                    </form>
                  </div>
                @endif
              </div>
            @empty
              <div class="text-center py-8 text-surface-400 text-xs">No products added yet.</div>
            @endforelse
          </div>

          @if (method_exists($products, 'links'))
            <div class="p-4 border-t border-surface-100">
              {{ $products->appends(['tab' => 'products'])->links() }}
            </div>
          @endif
        </div>
      </div>
    </div>

    <!-- ARCHIVED TAB -->
    <div id="tab-archived" class="tab-content space-y-5">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <!-- Archived Products -->
        <div class="bg-white rounded-xl border border-surface-100 overflow-hidden flex flex-col">
          <div class="px-5 py-4 border-b border-surface-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-surface-900">Archived Products</h2>
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
              <p class="text-xs text-surface-400 text-center py-4">No archived products.</p>
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
    <div id="tab-audit" class="tab-content">
      <div class="bg-white rounded-xl border border-surface-100 overflow-hidden">
        <div class="p-5 border-b border-surface-100 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
          <div>
            <h2 class="text-sm font-semibold text-surface-900">Audit Logs</h2>
            <p class="text-xs text-surface-400 mt-0.5">Tracks create, update, archive, and restore actions.</p>
          </div>
          <form method="GET" action="{{ route('admin.dashboard') }}" class="flex items-end gap-2">
            <input type="hidden" name="tab" value="audit">
            <div class="relative">
              <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-surface-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
              <input type="search" name="audit_q" value="{{ $auditQ ?? request('audit_q') }}" placeholder="Action, user, type, id"
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
                <th class="px-5 py-3 font-medium">Action</th>
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
                      $actionBadge = match($log->action) {
                        'created' => 'bg-brand-50 text-brand-700 border-brand-100',
                        'updated' => 'bg-blue-50 text-blue-700 border-blue-100',
                        default => 'bg-surface-50 text-surface-600 border-surface-200',
                      };
                    @endphp
                    <span class="px-2 py-0.5 rounded text-[10px] font-medium border {{ $actionBadge }}">
                      {{ $log->action }}
                    </span>
                  </td>
                  <td class="px-5 py-3">
                    <span class="text-[10px] font-mono bg-surface-50 px-1.5 py-0.5 rounded text-surface-600">{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</span>
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
    <div id="tab-users" class="tab-content">
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
    <div id="tab-feedbacks" class="tab-content">
      <div class="bg-white rounded-xl border border-surface-100 overflow-hidden mb-4">
        <div class="px-5 py-4 border-b border-surface-100 flex items-center justify-between gap-4 flex-wrap">
          <div>
            <h2 class="text-sm font-semibold text-surface-900">Customer Feedback</h2>
            <p class="text-xs text-surface-400 mt-0.5">
              {{ $feedbacks->total() }} submission{{ $feedbacks->total() !== 1 ? 's' : '' }}
              @if($avgRating)
                &mdash; avg rating
                <span class="font-semibold text-amber-500">{{ number_format($avgRating, 1) }}&nbsp;★</span>
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
                      {{ str_repeat('★', $fb->rating) }}<span class="text-surface-200">{{ str_repeat('★', 5 - $fb->rating) }}</span>
                    </span>
                  </td>
                  <td class="px-5 py-3">
                    @if($fb->product)
                      <span class="px-2 py-0.5 rounded text-[10px] font-medium border bg-blue-50 text-blue-700 border-blue-100">{{ $fb->product->name }}</span>
                    @elseif($fb->serviceType)
                      <span class="px-2 py-0.5 rounded text-[10px] font-medium border bg-purple-50 text-purple-700 border-purple-100">{{ $fb->serviceType->name }}</span>
                    @else
                      <span class="text-surface-400">General</span>
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
  </div>


  {{-- ── Admin Order Detail Modal ──────────────────────────────────────── --}}
  <div id="admin-order-modal" class="fixed inset-0 z-50 hidden flex items-end sm:items-center justify-center sm:p-4" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeOrderDetail()"></div>
    <div class="relative bg-white w-full sm:max-w-2xl max-h-[92vh] sm:max-h-[90vh] flex flex-col sm:rounded-2xl rounded-t-2xl shadow-2xl animate-od-up sm:animate-od-in overflow-hidden">

      {{-- drag handle - mobile only --}}
      <div class="flex justify-center pt-3 pb-1 sm:hidden">
        <div class="w-10 h-1 bg-surface-200 rounded-full"></div>
      </div>

      {{-- Header --}}
      <div class="flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-b border-surface-100">
        <div>
          <h2 class="text-base font-semibold text-surface-900">Order Details</h2>
          <p class="text-xs text-surface-400 mt-0.5">Order <span id="od-order-number" class="font-mono text-brand-600 font-semibold"></span></p>
        </div>
        <div class="flex items-center gap-2">
          <span id="od-status-badge" class="text-[10px] font-semibold uppercase tracking-wide px-2.5 py-1 rounded border"></span>
          <button onclick="closeOrderDetail()" class="text-surface-300 hover:text-surface-600 transition-colors ml-2">
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

  <style>
    @keyframes od-in  { from { opacity:0; transform:scale(.97) translateY(8px); } to { opacity:1; transform:none; } }
    @keyframes od-up  { from { transform:translateY(100%); opacity:0; } to { transform:translateY(0); opacity:1; } }
    .animate-od-in { animation: od-in .18s ease-out; }
    .animate-od-up { animation: od-up .25s cubic-bezier(.32,1,.5,1); }
    #admin-order-modal > div:last-child { animation: od-in .18s ease-out; }
  </style>

  <script>
    /* ── existing tab/bulk JS ── */
    function switchTab(tabId) {
      document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
      document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
      document.getElementById('tab-' + tabId).classList.add('active');
      document.getElementById('btn-' + tabId).classList.add('active');
      localStorage.setItem('adminActiveTab', tabId);
    }

    document.addEventListener('DOMContentLoaded', () => {
      const params = new URLSearchParams(window.location.search);
      const tabParam = params.get('tab');
      if (tabParam && document.getElementById('tab-' + tabParam)) {
        switchTab(tabParam);
      } else {
        const savedTab = localStorage.getItem('adminActiveTab') || 'overview';
        if (document.getElementById('tab-' + savedTab)) switchTab(savedTab);
      }

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
    });

    /* ── Order Detail Modal ── */
    const STATUS_BADGE_CLASSES = {
      pending:          'bg-amber-50 text-amber-700 border-amber-100',
      confirmed:        'bg-blue-50 text-blue-700 border-blue-100',
      out_for_delivery: 'bg-indigo-50 text-indigo-700 border-indigo-100',
      delivered:        'bg-brand-50 text-brand-700 border-brand-100',
      cancelled:        'bg-red-50 text-red-600 border-red-100',
    };

    function openOrderDetail(order) {
      const modal = document.getElementById('admin-order-modal');

      // Header
      document.getElementById('od-order-number').textContent = order.order_number;
      const statusBadge = document.getElementById('od-status-badge');
      statusBadge.textContent = order.status.replace(/_/g, ' ');
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
      const isPaid = order.payment_status === 'paid';
      psBadge.textContent = isPaid ? 'Paid' : 'Unpaid';
      psBadge.className = isPaid
        ? 'text-[10px] font-semibold px-2 py-0.5 rounded border bg-brand-50 text-brand-700 border-brand-100'
        : 'text-[10px] font-semibold px-2 py-0.5 rounded border bg-red-50 text-red-600 border-red-100';

      // Delivery
      const del = document.getElementById('od-delivery-content');
      if (order.delivery_method === 'pickup') {
        del.innerHTML = '<span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold border bg-purple-50 text-purple-700 border-purple-100 uppercase tracking-wide mb-1">Pick-up</span>'
          + '<p class="text-surface-400 text-xs">A. Arellano Ave. Mulawin, Orani, Philippines 2112</p>';
      } else {
        let html = '<span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold border bg-blue-50 text-blue-700 border-blue-100 uppercase tracking-wide mb-2">Delivery</span><div class="space-y-0.5 text-xs">';
        if (order.delivery_name)    html += `<p><span class="text-surface-400">Name:</span> <span class="font-medium text-surface-800">${order.delivery_name}</span></p>`;
        if (order.delivery_phone)   html += `<p><span class="text-surface-400">Phone:</span> <span class="text-surface-700">${order.delivery_phone}</span></p>`;
        if (order.delivery_address) html += `<p><span class="text-surface-400">Address:</span> <span class="text-surface-700">${order.delivery_address}${order.delivery_city ? ', ' + order.delivery_city : ''}</span></p>`;
        if (order.delivery_notes)   html += `<p class="text-surface-400 italic">Notes: ${order.delivery_notes}</p>`;
        html += '</div>';
        del.innerHTML = html;
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
              <td class="px-4 py-2.5 font-medium text-surface-800">${name}</td>
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
    }

    function closeOrderDetail() {
      document.getElementById('admin-order-modal').classList.add('hidden');
      document.body.style.overflow = '';
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeOrderDetail(); });
  </script>
</body>
</html>
