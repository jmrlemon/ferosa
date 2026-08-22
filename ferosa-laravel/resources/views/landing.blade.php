{{-- Public front door, shown to guests at `/`.

     Deliberately standalone rather than extending layouts.customer: that layout
     is a signed-in dashboard — sidebar, cart badge, notification bell — and its
     chrome reads as nonsense to a visitor with no account. This page is the one
     view a stranger sees first, so it carries its own shell and nothing else.

     Every record rendered here is already public (the shop and projects pages
     serve the same rows to guests); the controller applies the same is_active /
     archived_at filters so nothing unpublished leaks onto an indexable page. --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ferosa Landscaping — Plants, materials, and garden design in {{ $businessProfile['service_area'] ?: 'Orani, Bataan' }}</title>
  <meta name="description" content="Ferosa Landscaping supplies plants, soil, grass, and stone and designs gardens across {{ $businessProfile['service_area'] ?: 'Orani, Bataan' }}. Browse the nursery or book a site visit.">
  <meta property="og:type" content="website">
  <meta property="og:title" content="Ferosa Landscaping">
  <meta property="og:description" content="Plan. Book. Grow beautifully in {{ $businessProfile['service_area'] ?: 'Orani, Bataan' }}.">
  <meta property="og:image" content="{{ asset('og.png') }}">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="Ferosa Landscaping">
  <meta name="twitter:description" content="Plan. Book. Grow beautifully in {{ $businessProfile['service_area'] ?: 'Orani, Bataan' }}.">
  <meta name="twitter:image" content="{{ asset('og.png') }}">

  <link rel="stylesheet" href="{{ asset('fonts/ferosa-fonts.css') }}">
  @vite(['resources/css/app.css'])
  <style>
    * { font-family: 'DM Sans', system-ui, sans-serif; }
    .font-display { font-family: 'Fraunces', Georgia, serif; }
    :focus-visible { outline: 3px solid rgba(52, 127, 87, .28); outline-offset: 3px; }
    html { scroll-behavior: smooth; }
    @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } }
  </style>
</head>
<body class="bg-surface-50 text-surface-900 antialiased">

  <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-xl focus:bg-brand-700 focus:px-4 focus:py-2 focus:text-sm focus:font-bold focus:text-white">Skip to content</a>

  {{-- Header --}}
  <header class="sticky top-0 z-40 border-b border-surface-200/80 bg-surface-50/90 backdrop-blur">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-3.5">
      <a href="{{ route('landing') }}" class="flex items-center gap-2.5" aria-label="Ferosa Landscaping home">
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-900">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 3C12 3 7 6.5 7 12c0 2.76 1.34 5.22 3.4 6.74.38-.48.93-.74 1.6-.74s1.22.26 1.6.74C15.66 17.22 17 14.76 17 12c0-5.5-5-9-5-9z" fill="#fff"/>
          </svg>
        </span>
        <span class="leading-tight">
          <span class="block font-display text-lg font-bold tracking-[-.02em] text-surface-950">{{ $businessProfile['business_name'] ?: 'Ferosa' }}</span>
          <span class="block text-[10px] font-bold uppercase tracking-[.16em] text-surface-500">Landscaping</span>
        </span>
      </a>

      <nav class="hidden items-center gap-1 md:flex" aria-label="Primary">
        <a href="{{ route('shop') }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-surface-600 transition hover:bg-brand-50 hover:text-brand-700">Shop</a>
        <a href="#services" class="rounded-lg px-3 py-2 text-sm font-semibold text-surface-600 transition hover:bg-brand-50 hover:text-brand-700">Services</a>
        <a href="{{ route('projects.index') }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-surface-600 transition hover:bg-brand-50 hover:text-brand-700">Our work</a>
        <a href="#contact" class="rounded-lg px-3 py-2 text-sm font-semibold text-surface-600 transition hover:bg-brand-50 hover:text-brand-700">Contact</a>
      </nav>
    </div>
  </header>

  <main id="main">

    {{-- Hero --}}
    <section class="mx-auto max-w-6xl px-5 pt-8 sm:pt-12">
      <div class="overflow-hidden rounded-[1.6rem] bg-brand-950 px-6 py-11 text-white sm:px-10 sm:py-16">
        <div class="grid gap-10 lg:grid-cols-[1.35fr_1fr] lg:items-end">
          <div>
            <p class="text-[11px] font-bold uppercase tracking-[.18em] text-brand-200">Nursery &amp; landscaping &middot; {{ $businessProfile['service_area'] ?: 'Orani, Bataan' }}</p>
            <h1 class="mt-4 max-w-3xl font-display text-4xl font-bold leading-[1.08] tracking-[-.03em] sm:text-6xl">
              Grow a garden that suits the way you actually live.
            </h1>
            <p class="mt-5 max-w-2xl text-base leading-8 text-brand-100/80">
              Ferosa supplies the plants, soil, grass, and stone we use on our own sites, and designs, builds, and maintains the gardens they go into. Browse the nursery at honest prices, or have us walk your property first.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
              <a href="{{ route('schedule') }}" class="inline-flex min-h-[48px] items-center rounded-xl bg-white px-5 text-sm font-bold text-brand-900 transition hover:bg-brand-50">Book a site visit</a>
              <a href="{{ route('shop') }}" class="inline-flex min-h-[48px] items-center rounded-xl border border-white/20 bg-white/5 px-5 text-sm font-bold text-white transition hover:bg-white/10">Browse the shop</a>
            </div>
            <p class="mt-4 text-xs leading-6 text-brand-100/55">{{ $businessProfile['booking_notice'] ?: 'Appointments must be booked at least 24 hours in advance.' }}</p>
          </div>

          <div class="rounded-2xl border border-white/10 bg-white/5 p-6">
            <p class="text-[10px] font-bold uppercase tracking-wider text-brand-200">Find us</p>
            <p class="mt-3 text-sm font-bold leading-6">{{ $businessProfile['business_address'] ?: 'A. Arellano Ave. Mulawin, Orani, Bataan 2112' }}</p>
            @if ($businessProfile['business_hours'])
              <p class="mt-3 text-xs leading-6 text-brand-100/70">{{ $businessProfile['business_hours'] }}</p>
            @endif
            @if ($businessProfile['business_phone'])
              <a href="tel:{{ preg_replace('/[^0-9+]/', '', $businessProfile['business_phone']) }}" class="mt-4 block text-sm font-bold text-white underline decoration-brand-300 underline-offset-4">{{ $businessProfile['business_phone'] }}</a>
            @endif
            @if ($businessProfile['business_email'])
              <a href="mailto:{{ $businessProfile['business_email'] }}" class="mt-1.5 block break-all text-xs text-brand-100/70 underline underline-offset-4">{{ $businessProfile['business_email'] }}</a>
            @endif
          </div>
        </div>
      </div>
    </section>

    {{-- Services --}}
    <section id="services" class="mx-auto max-w-6xl scroll-mt-24 px-5 pt-16">
      <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p class="text-[11px] font-bold uppercase tracking-[.18em] text-brand-600">What we do</p>
          <h2 class="mt-2 font-display text-3xl font-bold tracking-[-.025em] text-surface-950 sm:text-4xl">Services, priced before we start</h2>
          <p class="mt-3 max-w-2xl text-sm leading-7 text-surface-500">Every job begins with a visit so the quote reflects your actual space. The rates below are starting fees.</p>
        </div>
        <a href="{{ route('schedule') }}" class="inline-flex min-h-[44px] items-center rounded-xl border border-surface-200 bg-white px-4 text-sm font-bold text-brand-700 transition hover:border-brand-200">Request a quote</a>
      </div>

      @if ($featuredServices->isEmpty())
        <div class="mt-7 rounded-[1.25rem] border border-dashed border-surface-200 bg-white p-8 text-center">
          <p class="text-sm leading-6 text-surface-500">Our service list is being updated. Message the team and we will quote your project directly.</p>
          <a href="{{ route('login') }}" class="mt-4 inline-flex min-h-[44px] items-center rounded-xl bg-brand-700 px-4 text-sm font-bold text-white transition hover:bg-brand-900">Sign in to enquire</a>
        </div>
      @else
        <div class="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          @foreach ($featuredServices as $service)
            <article class="flex flex-col rounded-[1.25rem] border border-surface-200 bg-white p-6 transition duration-200 hover:-translate-y-1 hover:shadow-card">
              <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 3C12 3 7 6.5 7 12c0 2.76 1.34 5.22 3.4 6.74M12 3c0 0 5 3.5 5 9 0 2.76-1.34 5.22-3.4 6.74M12 21v-3"/>
                </svg>
              </span>
              <h3 class="mt-4 font-display text-lg font-bold leading-snug text-surface-950">{{ $service->name }}</h3>
              <p class="mt-auto pt-4 text-xs font-bold uppercase tracking-wider text-surface-400">Starts at</p>
              <p class="font-display text-xl font-bold text-brand-700">&#8369;{{ number_format((float) $service->default_fee, 2) }}</p>
            </article>
          @endforeach
        </div>
      @endif
    </section>

    {{-- From the nursery --}}
    <section class="mx-auto max-w-6xl px-5 pt-16">
      <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p class="text-[11px] font-bold uppercase tracking-[.18em] text-brand-600">From the nursery</p>
          <h2 class="mt-2 font-display text-3xl font-bold tracking-[-.025em] text-surface-950 sm:text-4xl">In stock this week</h2>
          <p class="mt-3 max-w-2xl text-sm leading-7 text-surface-500">The same stock we use on site, at the same prices. Delivery across {{ $businessProfile['service_area'] ?: 'Orani, Bataan' }}.</p>
        </div>
        <a href="{{ route('shop') }}" class="inline-flex min-h-[44px] items-center rounded-xl border border-surface-200 bg-white px-4 text-sm font-bold text-brand-700 transition hover:border-brand-200">See the full catalogue</a>
      </div>

      @if ($featuredProducts->isEmpty())
        <div class="mt-7 rounded-[1.25rem] border border-dashed border-surface-200 bg-white p-8 text-center">
          <p class="text-sm leading-6 text-surface-500">We are restocking right now. Check the catalogue again shortly.</p>
        </div>
      @else
        <div class="mt-7 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
          @foreach ($featuredProducts as $product)
            <a href="{{ route('products.show', $product) }}" class="group flex flex-col overflow-hidden rounded-[1.15rem] border border-surface-200 bg-white transition duration-200 hover:-translate-y-1 hover:border-brand-200 hover:shadow-card">
              <div class="relative flex aspect-[4/3] items-center justify-center overflow-hidden bg-surface-50">
                @if ($product->image_url)
                  <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy" decoding="async"
                       class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.035]">
                @else
                  <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.2" aria-hidden="true">
                    <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
                  </svg>
                @endif
                <span class="absolute left-3 top-3 rounded-full bg-white/90 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-surface-600 shadow-sm backdrop-blur">{{ $product->category }}</span>
              </div>
              <div class="flex flex-1 flex-col p-5">
                <h3 class="font-display text-lg font-bold leading-snug text-surface-950">{{ $product->name }}</h3>
                @if ($product->description)
                  <p class="mt-2 line-clamp-2 text-sm leading-6 text-surface-500">{{ $product->description }}</p>
                @endif
                <p class="mt-auto pt-4 font-display text-xl font-bold text-surface-950">&#8369;{{ number_format((float) $product->price, 2) }}</p>
                <p class="mt-1 text-xs font-bold text-brand-700">View details &rarr;</p>
              </div>
            </a>
          @endforeach
        </div>
      @endif
    </section>

    {{-- Recent work --}}
    @if ($featuredProjects->isNotEmpty())
      <section class="mx-auto max-w-6xl px-5 pt-16">
        <div class="flex flex-wrap items-end justify-between gap-4">
          <div>
            <p class="text-[11px] font-bold uppercase tracking-[.18em] text-brand-600">Recent work</p>
            <h2 class="mt-2 font-display text-3xl font-bold tracking-[-.025em] text-surface-950 sm:text-4xl">Gardens we have finished</h2>
            <p class="mt-3 max-w-2xl text-sm leading-7 text-surface-500">Real projects, published by the Ferosa team.</p>
          </div>
          <a href="{{ route('projects.index') }}" class="inline-flex min-h-[44px] items-center rounded-xl border border-surface-200 bg-white px-4 text-sm font-bold text-brand-700 transition hover:border-brand-200">See all projects</a>
        </div>

        <div class="mt-7 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
          @foreach ($featuredProjects as $project)
            <a href="{{ route('projects.show', $project) }}" class="group overflow-hidden rounded-[1.25rem] border border-surface-200 bg-white transition duration-200 hover:-translate-y-1 hover:shadow-card">
              <div class="aspect-[4/3] overflow-hidden bg-brand-50">
                @if ($project->cover_image_url)
                  <img src="{{ $project->cover_image_url }}" alt="{{ $project->title }}" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.035]">
                @else
                  <div class="flex h-full items-center justify-center text-brand-300">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true"><path d="M4 20h16M6 20V9l6-5 6 5v11M9 20v-6h6v6"/></svg>
                  </div>
                @endif
              </div>
              <div class="p-5">
                <div class="flex flex-wrap gap-2 text-[10px] font-bold uppercase tracking-wider text-brand-650">
                  @if ($project->service_name)<span>{{ $project->service_name }}</span>@endif
                  @if ($project->location)<span class="text-surface-400">&middot; {{ $project->location }}</span>@endif
                </div>
                <h3 class="mt-2 font-display text-xl font-bold text-surface-950">{{ $project->title }}</h3>
                <p class="mt-2 line-clamp-2 text-sm leading-6 text-surface-500">{{ $project->summary }}</p>
              </div>
            </a>
          @endforeach
        </div>
      </section>
    @endif

    {{-- How it works --}}
    <section class="mx-auto max-w-6xl px-5 pt-16">
      <div class="rounded-[1.6rem] border border-surface-200 bg-white px-6 py-10 sm:px-10">
        <p class="text-[11px] font-bold uppercase tracking-[.18em] text-brand-600">How it works</p>
        <h2 class="mt-2 font-display text-3xl font-bold tracking-[-.025em] text-surface-950 sm:text-4xl">Three steps, no surprises</h2>
        <ol class="mt-8 grid gap-8 sm:grid-cols-3">
          <li>
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-900 font-display text-sm font-bold text-white">1</span>
            <h3 class="mt-4 text-base font-bold text-surface-950">Tell us about the space</h3>
            <p class="mt-2 text-sm leading-7 text-surface-500">Create an account and book a visit, or add nursery stock straight to your cart if you already know what you need.</p>
          </li>
          <li>
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-900 font-display text-sm font-bold text-white">2</span>
            <h3 class="mt-4 text-base font-bold text-surface-950">We quote it on site</h3>
            <p class="mt-2 text-sm leading-7 text-surface-500">We measure, agree the plan, and confirm the fee before any work starts. You can message the team throughout.</p>
          </li>
          <li>
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-900 font-display text-sm font-bold text-white">3</span>
            <h3 class="mt-4 text-base font-bold text-surface-950">Delivered and planted</h3>
            <p class="mt-2 text-sm leading-7 text-surface-500">Track your order and appointment from your account, from scheduling right through to completion.</p>
          </li>
        </ol>
      </div>
    </section>

    {{-- Closing call to action --}}
    <section id="contact" class="mx-auto max-w-6xl scroll-mt-24 px-5 py-16">
      <div class="overflow-hidden rounded-[1.6rem] bg-brand-800 px-6 py-12 text-center text-white sm:px-10">
        <h2 class="font-display text-3xl font-bold tracking-[-.025em] sm:text-4xl">Ready to start?</h2>
        <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-brand-100/80">
          Create an account to book a service, order from the nursery, and message the team about your project.
        </p>
        <div class="mt-7 flex flex-wrap justify-center gap-3">
          <a href="{{ route('register') }}" class="inline-flex min-h-[48px] items-center rounded-xl bg-white px-5 text-sm font-bold text-brand-900 transition hover:bg-brand-50">Create an account</a>
          <a href="{{ route('login') }}" class="inline-flex min-h-[48px] items-center rounded-xl border border-white/20 bg-white/5 px-5 text-sm font-bold text-white transition hover:bg-white/10">I already have one</a>
        </div>
        @if ($businessProfile['service_guarantee'])
          <p class="mx-auto mt-6 max-w-xl text-xs leading-6 text-brand-100/60">{{ $businessProfile['service_guarantee'] }}</p>
        @endif
      </div>
    </section>
  </main>

  {{-- Footer --}}
  <footer class="border-t border-surface-200 bg-white">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-5 py-8">
      <div>
        <p class="font-display text-base font-bold text-surface-950">{{ $businessProfile['business_name'] ?: 'Ferosa Landscaping' }}</p>
        <p class="mt-1 text-xs leading-6 text-surface-500">{{ $businessProfile['business_address'] ?: 'A. Arellano Ave. Mulawin, Orani, Bataan 2112' }}</p>
      </div>
      <nav class="flex flex-wrap gap-x-5 gap-y-2 text-xs font-semibold text-surface-500" aria-label="Footer">
        <a href="{{ route('shop') }}" class="transition hover:text-brand-700">Shop</a>
        <a href="{{ route('projects.index') }}" class="transition hover:text-brand-700">Our work</a>
        <a href="{{ route('login') }}" class="transition hover:text-brand-700">Sign in</a>
        <a href="{{ route('register') }}" class="transition hover:text-brand-700">Create account</a>
      </nav>
      <p class="text-xs text-surface-400">&copy; {{ now()->year }} {{ $businessProfile['business_name'] ?: 'Ferosa Landscaping' }}</p>
    </div>
  </footer>

</body>
</html>
