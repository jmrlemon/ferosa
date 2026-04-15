@extends('layouts.customer')

@section('content')
<main class="max-w-2xl mx-auto px-4 sm:px-6 py-10 space-y-6">

  <div>
    <h1 class="text-2xl font-display font-bold text-surface-900 mb-1">Account</h1>
    <p class="text-surface-400 text-sm">Manage your personal details and view your appointments.</p>
  </div>

  @if (session('status'))
    <div class="bg-brand-50 border border-brand-100 text-brand-700 px-4 py-2.5 rounded-lg text-sm">
      {{ session('status') }}
    </div>
  @endif

  @if ($errors->any())
    <div class="bg-red-50 border border-red-100 text-red-600 px-4 py-2.5 rounded-lg text-sm">
      <ul class="list-disc pl-4 space-y-0.5">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- ── Profile View ────────────────────────────────────── --}}
  <div id="profile-view" class="bg-white rounded-xl border border-surface-100 p-5 sm:p-6">
    <h2 class="text-sm font-semibold text-surface-900 mb-5">Profile Information</h2>

    <div class="space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center py-1">
        <span class="text-xs text-surface-400 sm:w-1/3 mb-0.5 sm:mb-0">Name</span>
        <span class="text-sm font-medium text-surface-900 sm:w-2/3">{{ $user->name }}</span>
      </div>
      <div class="flex flex-col sm:flex-row sm:items-center py-1">
        <span class="text-xs text-surface-400 sm:w-1/3 mb-0.5 sm:mb-0">Email</span>
        <span class="text-sm font-medium text-surface-900 sm:w-2/3">{{ $user->email }}</span>
      </div>
      <div class="flex flex-col sm:flex-row sm:items-center py-1">
        <span class="text-xs text-surface-400 sm:w-1/3 mb-0.5 sm:mb-0">Account Type</span>
        <span class="text-sm sm:w-2/3">
          <span class="px-2 py-0.5 rounded text-[11px] font-medium {{ $user->account_type === 'Business' ? 'bg-purple-50 text-purple-600' : 'bg-brand-50 text-brand-600' }}">
            {{ $user->account_type ?? 'Customer' }}
          </span>
        </span>
      </div>
      <div class="flex flex-col sm:flex-row sm:items-center py-1">
        <span class="text-xs text-surface-400 sm:w-1/3 mb-0.5 sm:mb-0">Role</span>
        <span class="text-sm sm:w-2/3">
          <span class="px-2 py-0.5 rounded text-[11px] font-medium
            {{ $user->role === 'admin' ? 'bg-indigo-50 text-indigo-600' : ($user->role === 'staff' ? 'bg-brand-50 text-brand-600' : 'bg-surface-100 text-surface-600') }}">
            {{ ucfirst($user->role) }}
          </span>
        </span>
      </div>
    </div>

    <div class="flex gap-3 border-t border-surface-100 pt-5 mt-5">
      <button onclick="toggleEditMode()" class="bg-surface-900 hover:bg-surface-800 text-white font-medium py-2 px-5 rounded-lg text-xs transition-colors">
        Edit Profile
      </button>
      <button onclick="document.getElementById('logout-form').submit();"
              class="border border-surface-200 text-surface-500 hover:text-red-600 hover:border-red-200 font-medium py-2 px-5 rounded-lg text-xs transition-colors">
        Sign Out
      </button>
    </div>
  </div>

  {{-- ── Edit Form ─────────────────────────────────────── --}}
  <div id="profile-edit" class="hidden">
    <form method="POST" action="{{ route('account.update') }}" class="bg-white rounded-xl border border-surface-100 p-5 sm:p-6">
      @csrf
      @method('PUT')
      <h2 class="text-sm font-semibold text-surface-900 mb-5">Edit Profile</h2>

      <div class="space-y-4">
        <div>
          <label for="name" class="block text-xs font-medium text-surface-500 mb-1">Name</label>
          <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                 class="w-full sm:max-w-sm border border-surface-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
        </div>
        <div>
          <label for="email" class="block text-xs font-medium text-surface-500 mb-1">Email</label>
          <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                 class="w-full sm:max-w-sm border border-surface-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
        </div>
        <div>
          <label for="password" class="block text-xs font-medium text-surface-500 mb-1">
            New Password <span class="text-surface-300 font-normal">(leave blank to keep current)</span>
          </label>
          <input type="password" name="password" id="password"
                 class="w-full sm:max-w-sm border border-surface-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
        </div>
        <div>
          <label for="password_confirmation" class="block text-xs font-medium text-surface-500 mb-1">Confirm Password</label>
          <input type="password" name="password_confirmation" id="password_confirmation"
                 class="w-full sm:max-w-sm border border-surface-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
        </div>
      </div>

      <div class="flex gap-3 border-t border-surface-100 pt-5 mt-5">
        <button type="submit" class="bg-surface-900 hover:bg-surface-800 text-white font-medium py-2 px-5 rounded-lg text-xs transition-colors">
          Save Changes
        </button>
        <button type="button" onclick="toggleEditMode()"
                class="border border-surface-200 text-surface-500 hover:bg-surface-50 font-medium py-2 px-5 rounded-lg text-xs transition-colors">
          Cancel
        </button>
      </div>
    </form>
  </div>

  {{-- ── Appointment History ──────────────────────────── --}}
  <div class="bg-white rounded-xl border border-surface-100 overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-surface-100">
      <div>
        <h2 class="text-sm font-semibold text-surface-900">My Appointments</h2>
        <p class="text-xs text-surface-400 mt-0.5">Your recent and upcoming service bookings.</p>
      </div>
      <a href="{{ route('schedule') }}"
         class="text-xs font-medium text-brand-600 hover:text-brand-700 transition-colors flex items-center gap-1">
        Book new
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg>
      </a>
    </div>

    @if ($appointments->isEmpty())
      <div class="px-5 py-10 text-center">
        <svg class="mx-auto mb-3 text-surface-200" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
        <p class="text-surface-400 text-sm mb-3">No appointments yet.</p>
        <a href="{{ route('schedule') }}" class="inline-block bg-surface-900 text-white text-xs font-medium px-5 py-2 rounded-lg hover:bg-surface-800 transition-colors">
          Book a Service
        </a>
      </div>
    @else
      <div class="divide-y divide-surface-100">
        @foreach ($appointments as $appt)
          @php
            $st = $appt->status;
            $badge = match($st) {
              'confirmed'  => 'bg-blue-50 text-blue-700 border-blue-100',
              'completed'  => 'bg-surface-900 text-white border-surface-900',
              'cancelled'  => 'bg-red-50 text-red-600 border-red-100',
              default      => 'bg-amber-50 text-amber-700 border-amber-100',
            };
            $isUpcoming = in_array($st, ['scheduled','confirmed'])
              && $appt->appointment_at
              && \Carbon\Carbon::parse($appt->appointment_at)->isFuture();
          @endphp
          <div class="px-5 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div class="flex items-start gap-3">
              <div class="w-9 h-9 rounded-lg bg-brand-50 flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="rgb(var(--brand-600))" stroke-width="2">
                  <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-surface-900">
                  {{ $appt->serviceType->name ?? 'Service' }}
                  @if ($isUpcoming)
                    <span class="ml-1 text-[10px] font-semibold bg-green-50 text-green-600 border border-green-100 px-1.5 py-0.5 rounded">Upcoming</span>
                  @endif
                </p>
                <p class="text-xs text-surface-400 mt-0.5">
                  {{ $appt->appointment_at ? \Carbon\Carbon::parse($appt->appointment_at)->format('D, M j, Y \a\t g:i A') : '—' }}
                </p>
                @if ($appt->notes)
                  <p class="text-xs text-surface-400 mt-0.5 italic">"{{ $appt->notes }}"</p>
                @endif
              </div>
            </div>
            <span class="text-[10px] font-semibold uppercase tracking-wide px-2 py-1 rounded border {{ $badge }} self-start sm:self-center flex-shrink-0">
              {{ ucfirst($st) }}
            </span>
          </div>
        @endforeach
      </div>
    @endif
  </div>

</main>

<form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>

@include('partials.mobile-bottom-customer')
@endsection

@section('scripts')
<script>
  function toggleEditMode() {
    document.getElementById('profile-view').classList.toggle('hidden');
    document.getElementById('profile-edit').classList.toggle('hidden');
  }
  @if ($errors->any())
    document.addEventListener('DOMContentLoaded', () => {
      document.getElementById('profile-view').classList.add('hidden');
      document.getElementById('profile-edit').classList.remove('hidden');
    });
  @endif
</script>
@endsection
