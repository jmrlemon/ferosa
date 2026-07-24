@extends('layouts.customer')

@section('content')
<main class="customer-page max-w-2xl space-y-6">

  <div>
    <h1 class="text-2xl font-display font-bold text-surface-900 mb-1">Account</h1>
    <p class="text-surface-400 text-sm">Manage your personal details and preferences.</p>
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
  <div id="profile-view" class="customer-card p-5 sm:p-6">
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
        <span class="text-xs text-surface-400 sm:w-1/3 mb-0.5 sm:mb-0">Phone</span>
        <span class="text-sm font-medium text-surface-900 sm:w-2/3">{{ $user->phone_number ?? '—' }}</span>
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
      <button onclick="toggleEditMode()" class="customer-action bg-surface-900 hover:bg-surface-800 text-white font-medium py-2 px-5 text-xs">
        Edit Profile
      </button>
      <form action="{{ route('logout') }}" method="POST" class="inline-flex">
        @csrf
        <button type="submit" data-loading-label="Signing out..."
                class="border border-surface-200 text-surface-500 hover:text-red-600 hover:border-red-200 font-medium py-2 px-5 rounded-lg text-xs transition-colors">
          Sign Out
        </button>
      </form>
    </div>
  </div>

  {{-- ── Edit Form ─────────────────────────────────────── --}}
  <div id="profile-edit" class="hidden">
    <form method="POST" action="{{ route('account.update') }}" class="customer-card p-5 sm:p-6">
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
          <label for="phone_number" class="block text-xs font-medium text-surface-500 mb-1">Phone Number <span class="text-surface-300 font-normal">(used for SMS notifications)</span></label>
          <input type="text" name="phone_number" id="phone_number" value="{{ old('phone_number', $user->phone_number) }}"
                 class="w-full sm:max-w-sm border border-surface-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors" placeholder="09XXXXXXXXX">
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
        <button type="submit" data-loading-label="Saving..." class="customer-action bg-surface-900 hover:bg-surface-800 text-white font-medium py-2 px-5 text-xs">
          Save Changes
        </button>
        <button type="button" onclick="toggleEditMode()"
                class="border border-surface-200 text-surface-500 hover:bg-surface-50 font-medium py-2 px-5 rounded-lg text-xs transition-colors">
          Cancel
        </button>
      </div>
    </form>
  </div>

</main>

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
