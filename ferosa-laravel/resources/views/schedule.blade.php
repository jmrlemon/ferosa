@extends('layouts.customer')

@section('title', 'Book a Service')

@section('styles')
<style>
  .cal-day { transition: background .12s, color .12s; }
  .cal-day:not(.past):not(.empty):hover { background: #f0faf0; color: #1a6320; }
  .cal-day.selected { background: #1f7a1f; color: #fff; font-weight: 600; border-radius: 8px; }
  .cal-day.today { outline: 2px solid #1f7a1f; border-radius: 8px; outline-offset: -2px; }
  .cal-day.past { color: #d4d4d4; cursor: not-allowed; }
  .time-slot { transition: border-color .12s, background .12s, color .12s; }
  .time-slot.selected { border-color: #1f7a1f; background: #f0faf0; color: #1a6320; font-weight: 600; }
</style>
@endsection

@section('content')
<main class="max-w-3xl mx-auto px-4 sm:px-6 py-10 pb-28">

  {{-- Page header --}}
  <div class="mb-8">
    <h1 class="text-2xl font-display font-bold text-surface-900 mb-1" id="page-title">Book a Service</h1>
    <p class="text-surface-400 text-sm">Select a date and time for your landscaping consultation or service.</p>
  </div>

  {{-- Success flash --}}
  @if (session('status'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm flex items-center gap-2">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
      {{ session('status') }}
    </div>
  @endif

  {{-- Validation errors --}}
  @if ($errors->any())
    <div class="mb-6 bg-red-50 border border-red-200 text-red-600 rounded-xl px-4 py-3 text-sm">
      <ul class="list-disc pl-4 space-y-0.5">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- Booking form --}}
  <form method="POST" action="{{ route('schedule.store') }}" id="booking-form">
    @csrf

    {{-- Hidden inputs populated by JS --}}
    <input type="hidden" name="service_type_id" id="hidden-service-type">
    <input type="hidden" name="appointment_at"  id="hidden-appointment-at">
    <input type="hidden" name="notes"           id="hidden-notes">

    <div id="booking-container" class="grid grid-cols-1 md:grid-cols-5 gap-6">

      {{-- Calendar --}}
      <div class="md:col-span-3 bg-white rounded-xl border border-surface-100 p-5">
        <h3 class="text-sm font-semibold text-surface-900 mb-4">Select Date</h3>
        <div class="border border-surface-100 rounded-lg overflow-hidden">

          {{-- Month nav --}}
          <div class="flex justify-between items-center px-4 py-3 bg-surface-50 border-b border-surface-100">
            <button type="button" onclick="prevMonth()" class="p-1 hover:bg-surface-100 rounded text-surface-400 transition-colors">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <span class="text-xs font-semibold text-surface-700" id="cal-month-label"></span>
            <button type="button" onclick="nextMonth()" class="p-1 hover:bg-surface-100 rounded text-surface-400 transition-colors">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
          </div>

          {{-- Day-of-week headers --}}
          <div class="grid grid-cols-7 gap-0.5 px-3 pt-3 text-center text-[10px] text-surface-400 font-medium">
            <div>Su</div><div>Mo</div><div>Tu</div><div>We</div><div>Th</div><div>Fr</div><div>Sa</div>
          </div>

          {{-- Day cells rendered by JS --}}
          <div class="grid grid-cols-7 gap-0.5 p-3" id="cal-grid"></div>
        </div>
      </div>

      {{-- Time & Details --}}
      <div class="md:col-span-2 space-y-5">

        {{-- Time slots --}}
        <div class="bg-white rounded-xl border border-surface-100 p-5">
          <h3 class="text-sm font-semibold text-surface-900 mb-3">Select Time</h3>
          <div class="grid grid-cols-2 gap-2" id="time-slots">
            @foreach (['09:00', '10:30', '13:00', '14:30', '16:00'] as $t)
              <button type="button"
                class="time-slot border border-surface-200 py-2 rounded-lg text-xs font-medium text-surface-500"
                data-time="{{ $t }}"
                onclick="selectTime(this)">
                {{ \Carbon\Carbon::createFromFormat('H:i', $t)->format('h:i A') }}
              </button>
            @endforeach
            <button type="button" disabled
              class="border border-surface-100 py-2 rounded-lg text-xs font-medium text-surface-300 bg-surface-50 cursor-not-allowed">
              05:30 PM
            </button>
          </div>
        </div>

        {{-- Service & Notes --}}
        <div class="bg-white rounded-xl border border-surface-100 p-5">
          <h3 class="text-sm font-semibold text-surface-900 mb-3">Service Details</h3>
          <select id="service-type-select"
            class="w-full border border-surface-200 rounded-lg px-3 py-2 text-sm text-surface-700 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 mb-3 transition-colors">
            @foreach (($serviceTypes ?? []) as $st)
              <option value="{{ $st->id }}">{{ $st->name }}</option>
            @endforeach
          </select>
          <textarea id="notes-field"
            placeholder="Any specific notes for our team?"
            class="w-full border border-surface-200 rounded-lg px-3 py-2 text-sm text-surface-700 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 h-20 resize-none transition-colors"></textarea>
        </div>

        {{-- Submit --}}
        <div id="selection-summary" class="text-xs text-surface-400 text-center hidden">
          <span id="summary-text"></span>
        </div>

        <button type="button" onclick="submitBooking()"
          class="w-full bg-surface-900 hover:bg-surface-800 text-white font-medium py-2.5 rounded-lg text-sm transition-colors">
          Confirm Booking
        </button>
      </div>
    </div>
  </form>

</main>

@include('partials.mobile-bottom-customer')
@endsection

@section('scripts')
<script>
  // ── Calendar state ────────────────────────────────────────────────────────
  const MONTHS = ['January','February','March','April','May','June',
                  'July','August','September','October','November','December'];

  const today = new Date();
  today.setHours(0,0,0,0);

  let viewYear  = today.getFullYear();
  let viewMonth = today.getMonth();   // 0-based

  let selectedDate = null;  // Date object
  let selectedTime = null;  // '09:00'

  function renderCalendar() {
    const label = document.getElementById('cal-month-label');
    label.textContent = MONTHS[viewMonth] + ' ' + viewYear;

    const grid = document.getElementById('cal-grid');
    grid.innerHTML = '';

    const firstDay = new Date(viewYear, viewMonth, 1).getDay(); // 0=Sun
    const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();

    // Empty leading cells
    for (let i = 0; i < firstDay; i++) {
      const blank = document.createElement('div');
      blank.className = 'cal-day empty py-1.5 text-center text-xs';
      grid.appendChild(blank);
    }

    // Day cells
    for (let d = 1; d <= daysInMonth; d++) {
      const date = new Date(viewYear, viewMonth, d);
      const isPast = date < today;
      const isToday = date.getTime() === today.getTime();
      const isSelected = selectedDate && date.getTime() === selectedDate.getTime();

      const cell = document.createElement('div');
      cell.textContent = d;
      cell.className = 'cal-day py-1.5 text-center text-xs rounded-lg cursor-pointer select-none';

      if (isPast) {
        cell.classList.add('past');
      } else {
        if (isToday) cell.classList.add('today');
        if (isSelected) cell.classList.add('selected');
        cell.addEventListener('click', () => pickDate(date));
      }

      grid.appendChild(cell);
    }

    updateSummary();
  }

  function pickDate(date) {
    selectedDate = date;
    renderCalendar();
    updateSummary();
  }

  function prevMonth() {
    if (viewMonth === 0) { viewMonth = 11; viewYear--; }
    else { viewMonth--; }
    renderCalendar();
  }

  function nextMonth() {
    if (viewMonth === 11) { viewMonth = 0; viewYear++; }
    else { viewMonth++; }
    renderCalendar();
  }

  // ── Time slots ────────────────────────────────────────────────────────────
  function selectTime(btn) {
    document.querySelectorAll('.time-slot').forEach(t => t.classList.remove('selected'));
    btn.classList.add('selected');
    selectedTime = btn.dataset.time;
    updateSummary();
  }

  // Auto-select first time slot
  (function () {
    const first = document.querySelector('.time-slot:not([disabled])');
    if (first) { first.classList.add('selected'); selectedTime = first.dataset.time; }
  })();

  // ── Summary ───────────────────────────────────────────────────────────────
  function updateSummary() {
    const sum = document.getElementById('selection-summary');
    const txt = document.getElementById('summary-text');
    if (selectedDate && selectedTime) {
      const fmtDate = selectedDate.toLocaleDateString('en-US', { weekday:'short', month:'short', day:'numeric', year:'numeric' });
      const [h, m]  = selectedTime.split(':');
      const ampm    = +h >= 12 ? 'PM' : 'AM';
      const hh      = +h > 12 ? +h - 12 : (+h === 0 ? 12 : +h);
      txt.textContent = `📅 ${fmtDate} at ${hh}:${m} ${ampm}`;
      sum.classList.remove('hidden');
    } else {
      sum.classList.add('hidden');
    }
  }

  // ── Submit ────────────────────────────────────────────────────────────────
  function submitBooking() {
    if (!selectedDate) { alert('Please select a date.'); return; }
    if (!selectedTime)  { alert('Please select a time.'); return; }

    const serviceTypeId = document.getElementById('service-type-select').value;
    if (!serviceTypeId) { alert('Please select a service type.'); return; }

    // Build appointment_at as "YYYY-MM-DD HH:MM:00"
    const yyyy = selectedDate.getFullYear();
    const mm   = String(selectedDate.getMonth() + 1).padStart(2, '0');
    const dd   = String(selectedDate.getDate()).padStart(2, '0');
    const [hh, min] = selectedTime.split(':');
    const appointmentAt = `${yyyy}-${mm}-${dd} ${hh}:${min}:00`;

    document.getElementById('hidden-service-type').value   = serviceTypeId;
    document.getElementById('hidden-appointment-at').value = appointmentAt;
    document.getElementById('hidden-notes').value          = document.getElementById('notes-field').value;

    document.getElementById('booking-form').submit();
  }

  // ── Init ──────────────────────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', renderCalendar);
</script>
@endsection
