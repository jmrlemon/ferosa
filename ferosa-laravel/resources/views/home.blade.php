@extends('layouts.customer')

@section('styles')
<style>
  html { scroll-behavior: smooth; }

  .hero-slide { transition: opacity 0.9s ease, transform 0.9s ease; }
  .hero-slide.active { opacity: 1; transform: scale(1); }
  .hero-slide:not(.active) { opacity: 0; transform: scale(1.03); pointer-events: none; }

  .dot { width: 24px; height: 3px; border-radius: 2px; background: rgba(255,255,255,0.35); transition: all 0.25s; }
  .dot.active { background: white; width: 36px; }
  .t-dot { width: 8px; height: 8px; border-radius: 50%; background: #e4e4e7; transition: all 0.2s; }
  .t-dot.active { background: #16a34a; transform: scale(1.2); }

  .service-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
  .service-card:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(0,0,0,0.07); }

  .tool-card { transition: all 0.15s ease; }
  .tool-card:hover { border-color: #bbf7d0; background: #f0fdf4; }

  .stat-card { transition: transform 0.2s; }
  .stat-card:hover { transform: translateY(-2px); }

  @keyframes fadeSlideUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
  .animate-in { animation: fadeSlideUp 0.55s ease both; }
  .delay-1 { animation-delay: 0.1s; }
  .delay-2 { animation-delay: 0.2s; }
  .delay-3 { animation-delay: 0.3s; }
</style>
@endsection

@section('content')

{{-- ═══════════════════════════════════════════════════════════
     HERO
═══════════════════════════════════════════════════════════ --}}
<section class="relative h-[88vh] min-h-[540px] max-h-[860px] overflow-hidden" id="hero">
  {{-- Slides --}}
  <div class="absolute inset-0">
    <div id="slide-0" class="hero-slide absolute inset-0 active" style="background:linear-gradient(140deg,#061a08 0%,#0f3313 35%,#1a5c1a 65%,#2d9a2d 100%)">
      <div class="absolute inset-0" style="background:radial-gradient(ellipse at 70% 50%,rgba(45,154,45,0.25) 0%,transparent 65%)"></div>
    </div>
    <div id="slide-1" class="hero-slide absolute inset-0" style="background:linear-gradient(140deg,#0a2310 0%,#155220 35%,#1f7a1f 65%,#4aba4a 100%)">
      <div class="absolute inset-0" style="background:radial-gradient(ellipse at 70% 50%,rgba(74,186,74,0.2) 0%,transparent 65%)"></div>
    </div>
    <div id="slide-2" class="hero-slide absolute inset-0" style="background:linear-gradient(140deg,#0e2f14 0%,#184f1e 35%,#2d9a2d 65%,#7ad17a 100%)">
      <div class="absolute inset-0" style="background:radial-gradient(ellipse at 70% 50%,rgba(122,209,122,0.18) 0%,transparent 65%)"></div>
    </div>
    {{-- Decorative overlay pattern --}}
    <div class="absolute inset-0 opacity-[0.04]" style="background-image:radial-gradient(circle,white 1px,transparent 1px);background-size:32px 32px;"></div>
    <div class="absolute inset-0 bg-gradient-to-r from-black/55 via-black/20 to-transparent"></div>
  </div>

  {{-- Content --}}
  <div class="relative z-10 h-full flex flex-col justify-center px-8 sm:px-14 lg:px-24">
    <div class="max-w-xl">
      <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/15 rounded-full px-3.5 py-1.5 mb-6 animate-in">
        <span class="w-1.5 h-1.5 bg-green-400 rounded-full"></span>
        <span class="text-white/80 text-[11px] font-medium tracking-[0.12em] uppercase">Landscaping & Garden Design</span>
      </div>

      <h1 id="hero-heading" class="text-white font-display text-4xl sm:text-5xl lg:text-[3.6rem] font-bold leading-[1.08] mb-5 animate-in delay-1">
        Design Your<br>Dream Garden
      </h1>
      <p id="hero-sub" class="text-white/65 text-base sm:text-lg mb-9 leading-relaxed font-light animate-in delay-2">
        From concept to creation, we bring your outdoor vision to life with expert craftsmanship.
      </p>

      <div class="flex flex-wrap gap-3 animate-in delay-3">
        <a href="{{ route('schedule') }}" class="inline-flex items-center gap-2 bg-white text-surface-900 font-semibold px-6 py-3 rounded-lg text-sm hover:bg-green-50 transition-colors shadow-lg">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          Book Consultation
        </a>
        <a href="{{ route('shop') }}" class="inline-flex items-center gap-2 border border-white/25 bg-white/8 backdrop-blur-sm text-white font-medium px-6 py-3 rounded-lg text-sm hover:bg-white/15 hover:border-white/40 transition-all">
          Browse Shop
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg>
        </a>
      </div>
    </div>
  </div>

  {{-- Slide dots --}}
  <div id="hero-dots" class="absolute bottom-8 left-8 sm:left-14 lg:left-24 z-20 flex gap-2 items-center">
    <div class="hero-dot dot active cursor-pointer"></div>
    <div class="hero-dot dot cursor-pointer"></div>
    <div class="hero-dot dot cursor-pointer"></div>
  </div>

  {{-- Scroll hint --}}
  <div class="absolute bottom-8 right-8 sm:right-14 z-20 hidden sm:flex flex-col items-center gap-1.5 opacity-40">
    <span class="text-white text-[10px] tracking-widest uppercase rotate-90 origin-center" style="writing-mode:vertical-rl">Scroll</span>
    <div class="w-px h-8 bg-white/50"></div>
  </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     STATS BAND
═══════════════════════════════════════════════════════════ --}}
<section class="bg-white border-b border-surface-100">
  <div class="max-w-5xl mx-auto px-6 py-10">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
      @foreach ([
        ['2,500+', 'Projects Completed', '#22c55e'],
        ['98%',    'Client Satisfaction', '#3b82f6'],
        ['150+',   'Plant Varieties',     '#f59e0b'],
        ['10+',    'Years Experience',    '#8b5cf6'],
      ] as [$num, $label, $color])
      <div class="stat-card text-center py-4 px-3 rounded-xl hover:bg-surface-50 transition-colors">
        <p class="font-display font-bold text-3xl sm:text-4xl text-surface-900 leading-none mb-1" style="color:{{ $color }}">{{ $num }}</p>
        <p class="text-surface-400 text-[11px] font-medium uppercase tracking-wider mt-2">{{ $label }}</p>
      </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     SERVICES
═══════════════════════════════════════════════════════════ --}}
<section id="services" class="py-20 sm:py-24 bg-surface-50">
  <div class="max-w-5xl mx-auto px-6">

    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-12">
      <div>
        <p class="text-brand-600 text-[11px] font-semibold tracking-[0.15em] uppercase mb-2">What We Offer</p>
        <h2 class="text-2xl sm:text-3xl font-display font-bold text-surface-900">Our Services</h2>
        <p class="text-surface-400 text-sm mt-2 max-w-sm leading-relaxed">Comprehensive landscaping solutions tailored to your space and budget.</p>
      </div>
      <a href="{{ route('schedule') }}" class="flex-shrink-0 inline-flex items-center gap-2 text-brand-600 font-medium text-sm hover:text-brand-700 transition-colors">
        Book a Service
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg>
      </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      @foreach ([
        [
          'icon' => '<path d="M12 2a9 9 0 0 1 9 9c0 6-9 13-9 13S3 17 3 11a9 9 0 0 1 9-9z"/><circle cx="12" cy="11" r="3"/>',
          'color' => 'bg-green-50 text-green-600',
          'title' => 'Plant Installation',
          'desc' => 'Expert selection and planting of trees, shrubs, and flowers.',
        ],
        [
          'icon' => '<rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/>',
          'color' => 'bg-blue-50 text-blue-600',
          'title' => 'Hardscaping',
          'desc' => 'Patios, walkways, and retaining walls built to last.',
        ],
        [
          'icon' => '<path d="M12 22V12m0 0C12 7 7 5 4 8M12 12c0-5 5-7 8-4"/><path d="M5 20s1-2 4-3 7-1 7-1"/>',
          'color' => 'bg-amber-50 text-amber-600',
          'title' => 'Lawn Maintenance',
          'desc' => 'Routine mowing, edging, fertilizing, and seasonal care.',
        ],
        [
          'icon' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
          'color' => 'bg-purple-50 text-purple-600',
          'title' => 'Irrigation Systems',
          'desc' => 'Smart sprinkler and drip irrigation design and installation.',
        ],
      ] as $s)
      <div class="service-card bg-white rounded-2xl border border-surface-100 p-6 flex flex-col gap-4">
        <div class="w-11 h-11 {{ $s['color'] }} rounded-xl flex items-center justify-center flex-shrink-0">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">{!! $s['icon'] !!}</svg>
        </div>
        <div>
          <h3 class="font-semibold text-surface-900 text-sm mb-1.5">{{ $s['title'] }}</h3>
          <p class="text-surface-400 text-xs leading-relaxed">{{ $s['desc'] }}</p>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     WHY CHOOSE US
═══════════════════════════════════════════════════════════ --}}
<section class="py-20 sm:py-24 bg-white">
  <div class="max-w-5xl mx-auto px-6">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">

      {{-- Left: text --}}
      <div>
        <p class="text-brand-600 text-[11px] font-semibold tracking-[0.15em] uppercase mb-2">Why Ferosa</p>
        <h2 class="text-2xl sm:text-3xl font-display font-bold text-surface-900 mb-4 leading-tight">
          We Make Outdoor<br>Living Beautiful
        </h2>
        <p class="text-surface-400 text-sm leading-relaxed mb-8 max-w-md">
          With over 10 years of experience, we combine horticultural expertise with creative design to deliver spaces you'll love year-round.
        </p>

        <div class="space-y-5">
          @foreach ([
            ['Licensed & insured team of certified landscaping professionals.', '#22c55e'],
            ['End-to-end service from consultation to final walkthrough.', '#3b82f6'],
            ['2-year quality guarantee on all installation work.', '#f59e0b'],
          ] as [$text, $color])
          <div class="flex items-start gap-3.5">
            <div class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5" style="background:{{ $color }}20">
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="{{ $color }}" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <p class="text-surface-600 text-sm leading-relaxed">{{ $text }}</p>
          </div>
          @endforeach
        </div>

        <a href="{{ route('schedule') }}" class="inline-flex items-center gap-2 mt-8 bg-brand-600 text-white font-semibold px-5 py-3 rounded-lg text-sm hover:bg-brand-700 transition-colors">
          Get Free Consultation
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg>
        </a>
      </div>

      {{-- Right: feature cards --}}
      <div class="grid grid-cols-2 gap-3">
        @foreach ([
          ['🌿', 'Eco-Friendly',    'We use sustainable, locally-sourced materials whenever possible.'],
          ['⚡', 'Fast Turnaround', 'Most projects completed within the agreed timeline — no delays.'],
          ['📐', 'Custom Designs',  'Every garden plan is built specifically for your space and style.'],
          ['🛡️', 'Quality Assured', 'All work backed by a 2-year installation warranty.'],
        ] as [$emoji, $title, $desc])
        <div class="bg-surface-50 rounded-2xl border border-surface-100 p-5">
          <span class="text-2xl mb-3 block">{{ $emoji }}</span>
          <h4 class="font-semibold text-surface-900 text-sm mb-1">{{ $title }}</h4>
          <p class="text-surface-400 text-xs leading-relaxed">{{ $desc }}</p>
        </div>
        @endforeach
      </div>
    </div>
  </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     QUICK TOOLS
═══════════════════════════════════════════════════════════ --}}
<section class="bg-surface-900 py-14 sm:py-16">
  <div class="max-w-5xl mx-auto px-6">
    <p class="text-surface-400 text-[11px] font-semibold tracking-[0.15em] uppercase mb-2 text-center">Quick Access</p>
    <h2 class="text-xl sm:text-2xl font-display font-bold text-white mb-8 text-center">Get Started in Seconds</h2>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      @foreach ([
        [
          'href'  => 'estimator',
          'icon'  => '<rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="10" x2="16" y2="10"/><line x1="8" y1="14" x2="12" y2="14"/>',
          'label' => 'Cost Estimator',
          'sub'   => 'Get instant project quotes',
          'color' => 'bg-green-500/15 text-green-400',
        ],
        [
          'href'  => 'schedule',
          'icon'  => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
          'label' => 'Schedule Service',
          'sub'   => 'Book your next appointment',
          'color' => 'bg-blue-500/15 text-blue-400',
        ],
        [
          'href'  => 'orders',
          'icon'  => '<rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
          'label' => 'Track Delivery',
          'sub'   => 'Real-time order tracking',
          'color' => 'bg-amber-500/15 text-amber-400',
        ],
      ] as $t)
      <a href="{{ route($t['href']) }}" class="tool-card flex items-center gap-4 bg-surface-800 border border-surface-700 hover:border-surface-600 rounded-xl p-5 group transition-all">
        <div class="w-11 h-11 {{ $t['color'] }} rounded-xl flex items-center justify-center flex-shrink-0">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">{!! $t['icon'] !!}</svg>
        </div>
        <div class="flex-1 min-w-0">
          <h3 class="font-semibold text-white text-sm">{{ $t['label'] }}</h3>
          <p class="text-surface-400 text-xs mt-0.5">{{ $t['sub'] }}</p>
        </div>
        <svg class="text-surface-600 group-hover:text-surface-400 transition-colors flex-shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
      </a>
      @endforeach
    </div>
  </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     TESTIMONIALS
═══════════════════════════════════════════════════════════ --}}
<section id="testimonials" class="py-20 sm:py-24 bg-white">
  <div class="max-w-5xl mx-auto px-6">
    <div class="text-center mb-12">
      <p class="text-brand-600 text-[11px] font-semibold tracking-[0.15em] uppercase mb-2">Client Stories</p>
      <h2 class="text-2xl sm:text-3xl font-display font-bold text-surface-900">What Our Clients Say</h2>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
      @foreach ([
        ['S', 'Sarah Mitchell',  'Homeowner',      '"Ferosa completely transformed our backyard. The garden design was beyond what we imagined."'],
        ['J', 'James Parker',    'Property Owner', '"From the first consultation, they understood exactly what we wanted. Our garden is now the envy of the neighborhood!"'],
        ['L', 'Linda Torres',    'Homeowner',      '"The irrigation system they installed has cut our water bill in half. Highly recommended!"'],
      ] as [$init, $name, $role, $quote])
      <div class="bg-surface-50 border border-surface-100 rounded-2xl p-6 flex flex-col gap-4">
        <div class="flex gap-0.5">
          @for ($i = 0; $i < 5; $i++)
          <svg width="14" height="14" viewBox="0 0 24 24" fill="#f59e0b"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          @endfor
        </div>
        <p class="text-surface-600 text-sm leading-relaxed flex-1">{{ $quote }}</p>
        <div class="flex items-center gap-3 pt-2 border-t border-surface-100">
          <div class="w-9 h-9 bg-brand-600 rounded-full flex items-center justify-center text-white font-semibold text-sm flex-shrink-0">{{ $init }}</div>
          <div>
            <p class="font-semibold text-surface-900 text-sm">{{ $name }}</p>
            <p class="text-surface-400 text-xs">{{ $role }}</p>
          </div>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     CTA BANNER
═══════════════════════════════════════════════════════════ --}}
<section class="py-20 sm:py-24 bg-brand-600 relative overflow-hidden">
  <div class="absolute inset-0 opacity-[0.06]" style="background-image:radial-gradient(circle,white 1px,transparent 1px);background-size:28px 28px;"></div>
  <div class="absolute -top-16 -right-16 w-64 h-64 bg-brand-500 rounded-full opacity-30 blur-3xl"></div>
  <div class="absolute -bottom-16 -left-16 w-64 h-64 bg-brand-700 rounded-full opacity-30 blur-3xl"></div>
  <div class="relative max-w-2xl mx-auto px-6 text-center">
    <h2 class="text-2xl sm:text-4xl font-display font-bold text-white mb-4 leading-tight">
      Ready to Transform<br>Your Outdoor Space?
    </h2>
    <p class="text-white/70 text-sm sm:text-base mb-8 max-w-md mx-auto leading-relaxed">
      Get a free consultation and custom garden design plan tailored to your vision and budget.
    </p>
    <div class="flex flex-wrap gap-3 justify-center">
      <a href="{{ route('schedule') }}" class="bg-white text-brand-700 font-semibold px-7 py-3.5 rounded-lg text-sm hover:bg-green-50 transition-colors shadow-lg">
        Book Free Consultation
      </a>
      <a href="{{ route('estimator') }}" class="border border-white/30 bg-white/10 backdrop-blur-sm text-white font-medium px-7 py-3.5 rounded-lg text-sm hover:bg-white/20 transition-colors">
        Get Estimate
      </a>
    </div>
  </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     FOOTER
═══════════════════════════════════════════════════════════ --}}
<footer class="bg-surface-900">
  <div class="max-w-5xl mx-auto px-6 pt-14 pb-10">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10">
      {{-- Brand --}}
      <div class="lg:col-span-1">
        <div class="flex items-center gap-2.5 mb-4">
          <div class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 3C12 3 7 6.5 7 12c0 2.76 1.34 5.22 3.4 6.74.38-.48.93-.74 1.6-.74s1.22.26 1.6.74C15.66 17.22 17 14.76 17 12c0-5.5-5-9-5-9z" fill="white"/></svg>
          </div>
          <span class="text-base font-semibold text-white">Ferosa</span>
        </div>
        <p class="text-surface-500 text-xs leading-relaxed max-w-[200px]">
          Expert garden & landscaping services transforming outdoor spaces since 2015.
        </p>
      </div>

      {{-- Quick Links --}}
      <div>
        <h4 class="text-surface-300 font-semibold text-[11px] uppercase tracking-wider mb-4">Quick Links</h4>
        <ul class="space-y-2.5">
          @foreach (['home' => 'Home', 'shop' => 'Shop', 'schedule' => 'Schedule', 'estimator' => 'Cost Estimator', 'orders' => 'Track Orders'] as $route => $label)
          <li><a href="{{ route($route) }}" class="text-surface-500 text-xs hover:text-white transition-colors">{{ $label }}</a></li>
          @endforeach
        </ul>
      </div>

      {{-- Contact --}}
      <div>
        <h4 class="text-surface-300 font-semibold text-[11px] uppercase tracking-wider mb-4">Contact</h4>
        <ul class="space-y-3">
          <li class="flex items-start gap-2.5">
            <svg class="flex-shrink-0 mt-0.5 text-surface-500" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <span class="text-surface-500 text-xs leading-relaxed">A. Arellano Ave. Mulawin, Orani, Bataan, Philippines 2112</span>
          </li>
          <li class="flex items-center gap-2.5">
            <svg class="flex-shrink-0 text-surface-500" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
            <span class="text-surface-500 text-xs">cb.landscapingph@gmail.com</span>
          </li>
        </ul>
      </div>

      {{-- Newsletter --}}
      <div>
        <h4 class="text-surface-300 font-semibold text-[11px] uppercase tracking-wider mb-4">Newsletter</h4>
        <p class="text-surface-500 text-xs leading-relaxed mb-3">Get gardening tips and exclusive offers in your inbox.</p>
        <div class="flex gap-2">
          <input type="email" placeholder="your@email.com"
            class="flex-1 bg-surface-800 border border-surface-700 text-white placeholder-surface-500 text-xs px-3 py-2.5 rounded-lg outline-none focus:border-brand-500 transition-colors min-w-0">
          <button class="bg-brand-600 hover:bg-brand-700 text-white font-medium text-xs px-3 py-2.5 rounded-lg transition-colors flex-shrink-0">Join</button>
        </div>
      </div>
    </div>
  </div>

  <div class="border-t border-surface-800">
    <div class="max-w-5xl mx-auto px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-2">
      <p class="text-surface-600 text-[11px]">&copy; {{ date('Y') }} Ferosa Landscaping Services. All rights reserved.</p>
      <div class="flex gap-4">
        <a href="#" class="text-surface-600 text-[11px] hover:text-surface-400 transition-colors">Privacy</a>
        <a href="#" class="text-surface-600 text-[11px] hover:text-surface-400 transition-colors">Terms</a>
      </div>
    </div>
  </div>
</footer>

@include('partials.mobile-bottom-customer')

<script>
  // ── Hero carousel ────────────────────────────────────────────
  const TOTAL = 3;
  let current = 0;
  const slides   = [0,1,2].map(i => document.getElementById('slide-'+i));
  const heroDots = document.querySelectorAll('.hero-dot');
  const headings = [
    'Design Your<br>Dream Garden',
    'Transform Your Space<br>with Ferosa',
    'Premium Outdoor<br>Living',
  ];
  const subs = [
    'From concept to creation, we bring your outdoor vision to life with expert craftsmanship.',
    'Expert garden & landscaping services for every season.',
    'Create stunning landscapes that elevate your lifestyle.',
  ];

  function goTo(idx) {
    current = (idx + TOTAL) % TOTAL;
    slides.forEach((s, i) => s.classList.toggle('active', i === current));
    heroDots.forEach((d, i) => d.classList.toggle('active', i === current));
    document.getElementById('hero-heading').innerHTML = headings[current];
    document.getElementById('hero-sub').textContent  = subs[current];
  }

  heroDots.forEach((d, i) => d.addEventListener('click', () => goTo(i)));
  let autoplay = setInterval(() => goTo(current + 1), 5500);
  const heroEl = document.getElementById('hero');
  heroEl.addEventListener('mouseenter', () => clearInterval(autoplay));
  heroEl.addEventListener('mouseleave', () => { autoplay = setInterval(() => goTo(current + 1), 5500); });
</script>
@endsection
