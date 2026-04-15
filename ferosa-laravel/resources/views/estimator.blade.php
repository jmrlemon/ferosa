@extends('layouts.customer')

@section('styles')
<style>
  /* ── Project Type cards ─────────────────────────────────────── */
  .card-body { transition: border-color .15s, background-color .15s, box-shadow .15s; }

  .step-card input[type="radio"]:checked + .card-body {
    border-color: rgb(var(--brand-600));
    border-width: 2px;
    background-color: rgb(var(--brand-50));
    box-shadow: 0 0 0 3px rgba(var(--brand-600),.1);
  }
  .step-card input[type="radio"]:checked + .card-body .card-icon {
    background-color: rgb(var(--brand-600));
    color: white;
  }
  .step-card input[type="radio"]:checked + .card-body .card-title {
    color: rgb(var(--brand-700));
  }
  /* Checkmark badge: hidden by default, shown when checked */
  .card-check {
    opacity: 0;
    transform: scale(0.6);
    transition: opacity .15s, transform .15s;
  }
  .step-card input[type="radio"]:checked + .card-body .card-check {
    opacity: 1;
    transform: scale(1);
  }
  /* "Selected" label under the rate badge */
  .card-selected-label {
    display: none;
  }
  .step-card input[type="radio"]:checked + .card-body .card-selected-label {
    display: inline-flex;
  }
  .step-card input[type="radio"]:checked + .card-body .card-rate-label {
    display: none;
  }

  /* ── Quality Tier cards ─────────────────────────────────────── */
  .tier-body { transition: border-color .15s, background .15s; }

  .tier-card input[type="radio"]:checked + .tier-body {
    border-color: rgb(var(--brand-600));
    border-width: 2px;
    background: rgb(var(--brand-50));
    box-shadow: 0 0 0 3px rgba(var(--brand-600),.1);
  }
  .tier-check {
    opacity: 0;
    transform: scale(0.6);
    transition: opacity .15s, transform .15s;
  }
  .tier-card input[type="radio"]:checked + .tier-body .tier-check {
    opacity: 1;
    transform: scale(1);
  }
  /* hide plain dot when checked, show checkmark */
  .tier-dot { transition: opacity .1s; }
  .tier-card input[type="radio"]:checked + .tier-body .tier-dot {
    display: none;
  }

  /* ── Add-on checkboxes ──────────────────────────────────────── */
  .addon-box { transition: border-color .15s, background .15s, box-shadow .15s; }
  .addon-item input[type="checkbox"]:checked ~ .addon-box {
    border-color: rgb(var(--brand-500));
    border-width: 2px;
    background: rgb(var(--brand-50));
    box-shadow: 0 0 0 3px rgba(var(--brand-600),.08);
  }
  .addon-item input[type="checkbox"]:checked ~ .addon-box .check-box {
    background: rgb(var(--brand-600));
    border-color: rgb(var(--brand-600));
  }
  .addon-item input[type="checkbox"]:checked ~ .addon-box .check-icon {
    opacity: 1;
  }
  .addon-item input[type="checkbox"]:checked ~ .addon-box .addon-label {
    color: rgb(var(--brand-700));
    font-weight: 600;
  }
  /* Addon checkmark badge */
  .addon-check {
    opacity: 0;
    transform: scale(0.6);
    transition: opacity .15s, transform .15s;
  }
  .addon-item input[type="checkbox"]:checked ~ .addon-box .addon-check {
    opacity: 1;
    transform: scale(1);
  }
  /* Addon icon bg on checked */
  .addon-icon { transition: background-color .15s; }
  .addon-item input[type="checkbox"]:checked ~ .addon-box .addon-icon {
    background-color: rgb(var(--brand-600));
    color: white !important;
  }

  /* ── Slider ─────────────────────────────────────────────────── */
  input[type="range"] { -webkit-appearance: none; appearance: none; height: 6px; border-radius: 9999px; background: #e4e4e7; outline: none; }
  input[type="range"]::-webkit-slider-thumb { -webkit-appearance: none; width: 20px; height: 20px; border-radius: 50%; background: rgb(var(--brand-600)); cursor: pointer; box-shadow: 0 0 0 3px rgba(var(--brand-600),.18); transition: box-shadow .15s; }
  input[type="range"]::-webkit-slider-thumb:hover { box-shadow: 0 0 0 5px rgba(var(--brand-600),.22); }
  input[type="range"]::-moz-range-thumb { width: 20px; height: 20px; border-radius: 50%; background: rgb(var(--brand-600)); cursor: pointer; border: none; }

  /* ── Size quick-pick active state ───────────────────────────── */
  .size-btn { transition: border-color .15s, background .15s, color .15s; }
  .size-btn.is-active {
    border-color: rgb(var(--brand-600));
    background: rgb(var(--brand-600));
    color: white;
    font-weight: 600;
  }

  /* ── Misc ───────────────────────────────────────────────────── */
  .price-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #f4f4f5; }
  .price-row:last-child { border-bottom: none; }

  @keyframes countUp { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
  .price-animate { animation: countUp .25s ease both; }

  .step-badge { width: 24px; height: 24px; border-radius: 50%; font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
</style>
@endsection

@section('content')
<main class="bg-surface-50 min-h-screen pb-24">

  {{-- ── Page Header ─────────────────────────────────────────── --}}
  <div class="bg-white border-b border-surface-100">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
      <div class="flex items-start gap-4">
        <div class="w-11 h-11 rounded-xl bg-brand-50 flex items-center justify-center flex-shrink-0">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="rgb(var(--brand-600))" stroke-width="2">
            <path d="M9 7H6a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3"/>
            <rect x="9" y="3" width="6" height="4" rx="1"/>
            <path d="m9 14 2 2 4-4"/>
          </svg>
        </div>
        <div>
          <h1 class="text-xl sm:text-2xl font-display font-bold text-surface-900 leading-tight">Cost Estimator</h1>
          <p class="text-surface-400 text-sm mt-1">Answer a few questions to get a personalised project estimate in seconds.</p>
        </div>
      </div>
    </div>
  </div>

  <div class="max-w-5xl mx-auto px-4 sm:px-6 pt-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

      {{-- ── Left: Steps ──────────────────────────────────────── --}}
      <div class="lg:col-span-2 space-y-5">

        {{-- Step 1: Project Type --}}
        <div class="bg-white rounded-2xl border border-surface-100 shadow-sm overflow-hidden">
          <div class="flex items-center gap-3 px-5 py-4 border-b border-surface-50">
            <span class="step-badge bg-brand-600 text-white">1</span>
            <div>
              <h3 class="text-sm font-semibold text-surface-900">Project Type</h3>
              <p class="text-xs text-surface-400">What kind of project are you planning?</p>
            </div>
          </div>
          <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-3">
            @foreach ([
              ['design',       'Garden Design',   'Full landscape design, plant selection & installation.',   '₱50/sq m',  'M12 2a9 9 0 0 1 9 9c0 6-9 13-9 13S3 17 3 11a9 9 0 0 1 9-9z" /><circle cx="12" cy="11" r="3',   'bg-green-50 text-green-600'],
              ['maintenance',  'Maintenance',     'Regular lawn care, pruning, weeding & cleanup.',          '₱10/sq m',  'M3 12h18M3 6h18M3 18h12',                                                                      'bg-blue-50 text-blue-600'],
              ['hardscaping',  'Hardscaping',     'Patios, walkways, retaining walls & stonework.',          '₱120/sq m', 'M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" /><polyline points="9 22 9 12 15 12 15 22',  'bg-amber-50 text-amber-600'],
            ] as [$val, $title, $desc, $rate, $icon, $iconClass])
            <label class="step-card cursor-pointer">
              <input type="radio" name="project_type" value="{{ $val }}" class="sr-only" {{ $val === 'design' ? 'checked' : '' }} onchange="calculate()">
              <div class="card-body border border-surface-200 rounded-xl p-4 hover:border-brand-200 relative">
                {{-- Checkmark badge (top-right corner) --}}
                <div class="card-check absolute top-2.5 right-2.5 w-5 h-5 bg-brand-600 rounded-full flex items-center justify-center">
                  <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3.5"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <div class="card-icon w-9 h-9 rounded-lg {{ $iconClass }} flex items-center justify-center mb-3 transition-colors">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="{{ $icon }}"/></svg>
                </div>
                <p class="card-title text-sm font-semibold text-surface-900 mb-1">{{ $title }}</p>
                <p class="text-[11px] text-surface-400 leading-snug mb-3">{{ $desc }}</p>
                <span class="card-rate-label inline-block text-[10px] font-semibold bg-surface-100 text-surface-500 px-2 py-0.5 rounded-full">{{ $rate }}</span>
                <span class="card-selected-label items-center gap-1 text-[10px] font-semibold bg-brand-600 text-white px-2 py-0.5 rounded-full">
                  <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5"><polyline points="20 6 9 17 4 12"/></svg>
                  Selected
                </span>
              </div>
            </label>
            @endforeach
          </div>
        </div>

        {{-- Step 2: Property Size --}}
        <div class="bg-white rounded-2xl border border-surface-100 shadow-sm overflow-hidden">
          <div class="flex items-center gap-3 px-5 py-4 border-b border-surface-50">
            <span class="step-badge bg-brand-600 text-white">2</span>
            <div>
              <h3 class="text-sm font-semibold text-surface-900">Property Size</h3>
              <p class="text-xs text-surface-400">Drag the slider to set your area in square metres.</p>
            </div>
          </div>
          <div class="p-5">
            <div class="flex items-center justify-between mb-5">
              <span class="text-surface-500 text-xs">Area</span>
              <div class="flex items-center gap-2">
                <span id="size-label" class="text-lg font-display font-bold text-surface-900">100</span>
                <span class="text-surface-400 text-sm">sq m</span>
              </div>
            </div>
            <input type="range" id="size-slider" min="10" max="1000" step="10" value="100"
                   class="w-full cursor-pointer" oninput="updateSizeUI(); calculate()">
            <div class="flex justify-between mt-3 text-[10px] text-surface-300 font-medium">
              <span>10 sq m</span>
              <span>250 sq m</span>
              <span>500 sq m</span>
              <span>750 sq m</span>
              <span>1,000 sq m</span>
            </div>

            {{-- Quick-pick buttons --}}
            <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-surface-50">
              <span class="text-[11px] text-surface-400 self-center mr-1">Quick pick:</span>
              @foreach ([10, 50, 100, 200, 500, 1000] as $sz)
              <button type="button" onclick="setSize({{ $sz }})"
                      class="size-btn text-xs px-3 py-1.5 rounded-lg border border-surface-200 text-surface-600 hover:border-brand-400 hover:bg-brand-50 hover:text-brand-700 transition-colors font-medium">
                {{ $sz }} sq m
              </button>
              @endforeach
            </div>
          </div>
        </div>

        {{-- Step 3: Quality Tier --}}
        <div class="bg-white rounded-2xl border border-surface-100 shadow-sm overflow-hidden">
          <div class="flex items-center gap-3 px-5 py-4 border-b border-surface-50">
            <span class="step-badge bg-brand-600 text-white">3</span>
            <div>
              <h3 class="text-sm font-semibold text-surface-900">Quality Tier</h3>
              <p class="text-xs text-surface-400">Choose the finish level that matches your expectations.</p>
            </div>
          </div>
          <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-3">
            @foreach ([
              ['standard',  'Standard',  '1×', 'Budget-friendly materials with solid craftsmanship.',  'bg-zinc-100 text-zinc-500'],
              ['premium',   'Premium',   '1.6×','Higher-grade plants and materials with more detail.',  'bg-violet-50 text-violet-600'],
              ['luxury',    'Luxury',    '2.4×','Top-tier finishes, rare plants, and bespoke design.',  'bg-amber-50 text-amber-600'],
            ] as [$val, $label, $mult, $desc, $badgeClass])
            <label class="tier-card cursor-pointer">
              <input type="radio" name="quality_tier" value="{{ $val }}" data-mult="{{ $mult }}" class="sr-only" {{ $val === 'standard' ? 'checked' : '' }} onchange="calculate()">
              <div class="tier-body border border-surface-200 rounded-xl p-4 hover:border-brand-200">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-semibold text-surface-900">{{ $label }}</span>
                  {{-- Plain dot (unselected) --}}
                  <span class="tier-dot w-4 h-4 rounded-full bg-surface-200 border-2 border-surface-300"></span>
                  {{-- Checkmark (selected) --}}
                  <span class="tier-check w-5 h-5 bg-brand-600 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3.5"><polyline points="20 6 9 17 4 12"/></svg>
                  </span>
                </div>
                <p class="text-[11px] text-surface-400 leading-snug mb-3">{{ $desc }}</p>
                <span class="inline-block text-[10px] font-bold {{ $badgeClass }} px-2 py-0.5 rounded-full">{{ $mult }} multiplier</span>
              </div>
            </label>
            @endforeach
          </div>
        </div>

        {{-- Step 4: Add-ons --}}
        <div class="bg-white rounded-2xl border border-surface-100 shadow-sm overflow-hidden">
          <div class="flex items-center gap-3 px-5 py-4 border-b border-surface-50">
            <span class="step-badge bg-brand-600 text-white">4</span>
            <div class="flex-1">
              <div class="flex items-center gap-2">
                <h3 class="text-sm font-semibold text-surface-900">Extra Features</h3>
                <span id="addon-count-badge" class="hidden items-center gap-1 text-[10px] font-semibold bg-brand-600 text-white px-2 py-0.5 rounded-full">
                  <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5"><polyline points="20 6 9 17 4 12"/></svg>
                  <span id="addon-count-text">0</span> selected
                </span>
              </div>
              <p class="text-xs text-surface-400">Select any additional features you'd like included.</p>
            </div>
          </div>
          <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach ([
              ['addon-irrigation', 'Irrigation System',        '+ ₱40,000', 'Automated sprinkler & drip lines.',        'M12 2a10 10 0 0 1 0 20A10 10 0 0 1 12 2zm0 4v4m0 4h.01',                            'text-blue-500',   'bg-blue-50',   40000],
              ['addon-lighting',   'Outdoor Lighting',         '+ ₱25,000', 'Path lights, spotlights & accent LEDs.',   'M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41', 'text-yellow-500', 'bg-yellow-50', 25000],
              ['addon-water',      'Water Feature',            '+ ₱60,000', 'Custom pond, fountain or water wall.',     'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z', 'text-cyan-500',   'bg-cyan-50',   60000],
              ['addon-pergola',    'Pergola / Gazebo',         '+ ₱80,000', 'Shaded structure for outdoor living.',     'M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z',                                    'text-amber-600',  'bg-amber-50',  80000],
              ['addon-fence',      'Decorative Fencing',       '+ ₱20,000', 'Bamboo, wood or metal boundary fencing.',  'M4 4h16v2H4zm0 7h16v2H4zm0 7h16v2H4z',                                              'text-zinc-500',   'bg-zinc-100',  20000],
              ['addon-soil',       'Soil Preparation & Mulch', '+ ₱15,000', 'Deep aeration, enriched topsoil & mulch.', 'M3 3h18v18H3z" rx="2',                                                              'text-green-600',  'bg-green-50',  15000],
            ] as [$id, $label, $price, $desc, $icon, $iconColor, $iconBg, $amount])
            <label class="addon-item cursor-pointer">
              <input type="checkbox" id="{{ $id }}" data-amount="{{ $amount }}" class="sr-only" onchange="calculate(); updateAddonCount()">
              <div class="addon-box border border-surface-200 rounded-xl p-3.5 hover:border-brand-200 flex items-start gap-3 relative">
                {{-- Checkmark badge (top-right) --}}
                <div class="addon-check absolute top-2.5 right-2.5 w-5 h-5 bg-brand-600 rounded-full flex items-center justify-center">
                  <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3.5"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                {{-- Feature icon --}}
                <div class="addon-icon w-9 h-9 rounded-lg {{ $iconBg }} {{ $iconColor }} flex items-center justify-center flex-shrink-0">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="{{ $icon }}"/></svg>
                </div>
                {{-- Content --}}
                <div class="flex-1 min-w-0 pr-5">
                  <div class="flex items-start justify-between gap-2">
                    <span class="addon-label text-sm font-medium text-surface-800 transition-colors leading-snug">{{ $label }}</span>
                    <span class="text-xs font-semibold text-brand-600 flex-shrink-0">{{ $price }}</span>
                  </div>
                  <p class="text-[11px] text-surface-400 mt-0.5 leading-snug">{{ $desc }}</p>
                </div>
              </div>
            </label>
            @endforeach
          </div>
        </div>

      </div>{{-- end left col --}}

      {{-- ── Right: Estimate Card ──────────────────────────────── --}}
      <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl border border-surface-100 shadow-sm sticky top-6 overflow-hidden">

          {{-- Card header --}}
          <div class="bg-gradient-to-br from-surface-900 to-surface-800 px-5 py-5">
            <p class="text-[10px] font-semibold text-white/50 uppercase tracking-widest mb-1">Your Estimate</p>
            <div id="total-price" class="text-4xl font-display font-bold text-white price-animate">₱5,000</div>
            <p class="text-white/40 text-[11px] mt-1">Rough industry-average estimate</p>
          </div>

          {{-- Breakdown --}}
          <div class="px-5 py-4 border-b border-surface-100">
            <p class="text-[10px] font-semibold text-surface-400 uppercase tracking-wider mb-3">Breakdown</p>
            <div id="breakdown-list" class="space-y-0.5 text-sm">
              <div class="price-row">
                <span class="text-surface-600 text-xs" id="base-label">Base (Design · 100 sq m)</span>
                <span class="font-medium text-surface-800 text-xs" id="base-amount">₱5,000</span>
              </div>
              <div class="price-row">
                <span class="text-surface-600 text-xs">Quality tier</span>
                <span class="font-medium text-surface-800 text-xs" id="tier-label">Standard (1×)</span>
              </div>
              <div id="addon-rows"></div>
            </div>
          </div>

          {{-- Range note --}}
          <div class="px-5 py-3 bg-surface-50 border-b border-surface-100">
            <p class="text-[10px] text-surface-400 leading-relaxed">
              Actual range: <span class="font-semibold text-surface-700" id="range-low">₱4,000</span>
              &ndash; <span class="font-semibold text-surface-700" id="range-high">₱6,500</span>
            </p>
            <p class="text-[10px] text-surface-300 mt-0.5">Based on ±20% typical variation.</p>
          </div>

          {{-- CTA --}}
          <div class="p-5 space-y-2.5">
            <a href="{{ route('schedule') }}"
               class="w-full bg-brand-600 hover:bg-brand-700 text-white font-semibold py-3 rounded-xl text-sm transition-colors flex justify-center items-center gap-2 shadow-sm">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              Book Consultation
            </a>

            <button onclick="openArVisualizer()"
                    class="w-full bg-white hover:bg-surface-50 text-surface-700 font-medium py-3 rounded-xl text-sm border border-surface-200 transition-colors flex justify-center items-center gap-2">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 3l8 4v10l-8 4-8-4V7l8-4z"/><path d="M12 12l8-5"/><path d="M12 12v9"/><path d="M12 12L4 7"/></svg>
              Visualize in AR
            </button>

            <button onclick="shareEstimate()"
                    class="w-full text-surface-400 hover:text-surface-600 text-xs py-1 flex justify-center items-center gap-1.5 transition-colors">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
              Share this estimate
            </button>
          </div>

          {{-- Disclaimer --}}
          <div class="px-5 pb-5">
            <p class="text-[10px] text-surface-300 leading-relaxed text-center">
              Estimates are indicative only. A site visit may be required for an accurate quote.
            </p>
          </div>
        </div>
      </div>

    </div>{{-- end grid --}}
  </div>{{-- end container --}}
</main>

@include('partials.mobile-bottom-customer')
@endsection

@section('scripts')
<script>
  // ─── Pricing config ───────────────────────────────────────────────────────
  const BASE_RATES = { design: 50, maintenance: 10, hardscaping: 120 };   // ₱ per sq m
  const TIER_MULT  = { standard: 1.0, premium: 1.6, luxury: 2.4 };
  const TIER_LABEL = { standard: 'Standard (1×)', premium: 'Premium (1.6×)', luxury: 'Luxury (2.4×)' };

  // ─── Helpers ─────────────────────────────────────────────────────────────
  function fmt(n) {
    return '₱' + Math.round(n).toLocaleString('en-PH');
  }

  function updateSizeUI() {
    const v = document.getElementById('size-slider').value;
    document.getElementById('size-label').textContent = parseInt(v).toLocaleString();
    // highlight matching quick-pick
    document.querySelectorAll('.size-btn').forEach(btn => {
      const match = btn.textContent.trim().startsWith(v + ' ');
      btn.classList.toggle('is-active', match);
    });
  }

  function setSize(val) {
    document.getElementById('size-slider').value = val;
    updateSizeUI();
    calculate();
  }

  // ─── Main calculate ───────────────────────────────────────────────────────
  function calculate() {
    const typeEl = document.querySelector('input[name="project_type"]:checked');
    const tierEl = document.querySelector('input[name="quality_tier"]:checked');
    if (!typeEl || !tierEl) return;

    const type  = typeEl.value;
    const size  = parseInt(document.getElementById('size-slider').value) || 0;
    const rate  = BASE_RATES[type] || 50;
    const mult  = TIER_MULT[tierEl.value] || 1;
    const base  = rate * size * mult;

    // Extras
    let extrasTotal = 0;
    const addonRows = [];
    document.querySelectorAll('.addon-item input[type="checkbox"]').forEach(cb => {
      if (cb.checked) {
        const amount = parseInt(cb.dataset.amount) || 0;
        extrasTotal += amount;
        const label  = cb.closest('.addon-item').querySelector('.text-sm.font-medium')?.textContent?.trim() || '';
        const fmtAmt = cb.closest('.addon-item').querySelector('.text-xs.font-semibold')?.textContent?.trim() || '';
        addonRows.push({ label, fmtAmt, amount });
      }
    });

    const total = base + extrasTotal;

    // ── Update total ──
    const totalEl = document.getElementById('total-price');
    totalEl.textContent = fmt(total);
    totalEl.classList.remove('price-animate');
    void totalEl.offsetWidth;
    totalEl.classList.add('price-animate');

    // ── Update breakdown ──
    const typeLabel = { design: 'Design', maintenance: 'Maintenance', hardscaping: 'Hardscaping' }[type];
    document.getElementById('base-label').textContent  = `Base (${typeLabel} · ${size.toLocaleString()} sq m)`;
    document.getElementById('base-amount').textContent = fmt(base);
    document.getElementById('tier-label').textContent  = TIER_LABEL[tierEl.value];

    // Addon rows
    const addonContainer = document.getElementById('addon-rows');
    addonContainer.innerHTML = addonRows.map(r => `
      <div class="price-row">
        <span class="text-surface-600 text-xs">${r.label}</span>
        <span class="font-medium text-surface-800 text-xs">${fmt(r.amount)}</span>
      </div>
    `).join('') + (addonRows.length ? `
      <div class="price-row font-semibold">
        <span class="text-surface-800 text-xs">Total</span>
        <span class="text-surface-900 text-xs">${fmt(total)}</span>
      </div>
    ` : '');

    // ── Range ──
    document.getElementById('range-low').textContent  = fmt(total * 0.8);
    document.getElementById('range-high').textContent = fmt(total * 1.25);
  }

  // ─── AR Visualizer ───────────────────────────────────────────────────────
  function openArVisualizer() {
    const type  = document.querySelector('input[name="project_type"]:checked')?.value || 'design';
    const size  = document.getElementById('size-slider')?.value || '100';
    const cost  = document.getElementById('total-price')?.textContent?.replace(/[^0-9]/g, '') || '0';
    const id    = 'est-' + Date.now();
    const link  = `ferosa://ar?designId=${id}&type=${type}&size=${size}&cost=${cost}`;

    if (/android/i.test(navigator.userAgent)) {
      const t = setTimeout(() => {
        if (confirm('Ferosa AR app is required.\n\nWould you like to install it?'))
          window.location.href = 'https://play.google.com/store/apps/details?id=com.example.ferosa_landscaping';
      }, 1500);
      window.addEventListener('blur', () => clearTimeout(t), { once: true });
      window.location.href = link;
    } else {
      showArModal(link);
    }
  }

  function showArModal(deepLink) {
    document.getElementById('ar-modal')?.remove();
    const m = document.createElement('div');
    m.id = 'ar-modal';
    m.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm px-4';
    m.innerHTML = `
      <div class="bg-white rounded-2xl border border-surface-100 p-6 max-w-sm w-full shadow-xl">
        <div class="w-11 h-11 bg-brand-50 rounded-xl flex items-center justify-center mb-4">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="rgb(var(--brand-600))" stroke-width="1.75">
            <path d="M12 3l8 4v10l-8 4-8-4V7l8-4z"/><path d="M12 12l8-5"/><path d="M12 12v9"/><path d="M12 12L4 7"/>
          </svg>
        </div>
        <h3 class="text-sm font-semibold text-surface-900 mb-1">AR Visualization</h3>
        <p class="text-xs text-surface-400 mb-4">Open this page on your Android phone to place a 3D landscaping preview in your real space using ARCore.</p>
        <div class="bg-surface-50 rounded-lg p-3 border border-surface-100 mb-4">
          <p class="text-[10px] text-surface-400 mb-1">Deep link</p>
          <p class="text-xs font-mono text-surface-600 break-all">${deepLink}</p>
        </div>
        <button onclick="document.getElementById('ar-modal').remove()"
                class="w-full bg-surface-900 hover:bg-surface-800 text-white font-medium py-2.5 rounded-xl text-sm transition-colors">
          Got it
        </button>
      </div>`;
    m.addEventListener('click', e => { if (e.target === m) m.remove(); });
    document.body.appendChild(m);
  }

  // ─── Share ────────────────────────────────────────────────────────────────
  function shareEstimate() {
    const total = document.getElementById('total-price')?.textContent || '';
    const text  = `My Ferosa landscaping estimate: ${total}. Get yours at ${location.href}`;
    if (navigator.share) {
      navigator.share({ title: 'Ferosa Cost Estimate', text, url: location.href });
    } else {
      navigator.clipboard?.writeText(text).then(() => alert('Link copied to clipboard!'));
    }
  }

  // ─── Addon count badge ────────────────────────────────────────────────────
  function updateAddonCount() {
    const checked = document.querySelectorAll('.addon-item input[type="checkbox"]:checked').length;
    const badge   = document.getElementById('addon-count-badge');
    document.getElementById('addon-count-text').textContent = checked;
    badge.classList.toggle('hidden',  checked === 0);
    badge.classList.toggle('inline-flex', checked > 0);
  }

  // ─── Init ─────────────────────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', () => {
    updateSizeUI();
    calculate();
    updateAddonCount();
  });
</script>
@endsection
