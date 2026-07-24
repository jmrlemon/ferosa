@extends('layouts.customer')

@section('title', 'Messages – Ferosa Landscaping')

@section('styles')
<style>
  .bubble-mine {
    background: linear-gradient(135deg, #1a6320, #2d9a2d);
    color: #fff;
    border-radius: 20px 20px 4px 20px;
    box-shadow: 0 1px 3px rgba(26, 99, 32, 0.2);
  }
  .bubble-theirs {
    background: #fff;
    color: #171717;
    border-radius: 20px 20px 20px 4px;
    border: 1px solid #f0f0f0;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
  }
  #msg-list { scroll-behavior: smooth; }
  #msg-list::-webkit-scrollbar { width: 4px; }
  #msg-list::-webkit-scrollbar-thumb { background: #e5e5e5; border-radius: 10px; }

  .date-separator {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 20px 0;
  }
  .date-separator::before,
  .date-separator::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #e5e5e5;
  }

  /* Make the chat fill the viewport on both web and in-app */
  #chat-shell {
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 100vh;
    min-height: 100dvh;
  }

  /* In the Android WebView: pin compose to bottom, let messages scroll naturally */
  body.in-app .customer-page { padding-bottom: 0 !important; }
  body.in-app #chat-shell {
    min-height: auto;
    height: auto;
  }
  body.in-app #msg-list {
    padding-bottom: 80px;
  }
  body.in-app #compose-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 40;
    background: #fff;
    border-top: 1px solid #f5f5f5;
    padding-bottom: 0;
  }
</style>
@endsection

@section('content')
<div id="chat-shell" class="max-w-2xl mx-auto w-full bg-surface-50">

  {{-- Header --}}
  <div class="px-5 sm:px-6 py-4 border-b border-surface-100 bg-white flex items-center gap-3 flex-shrink-0">
    <div class="relative">
      <div class="w-11 h-11 rounded-full bg-gradient-to-br from-brand-500 to-brand-700 flex items-center justify-center flex-shrink-0 shadow-sm">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
        </svg>
      </div>
      <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-400 border-2 border-white rounded-full"></span>
    </div>
    <div class="flex-1">
      <p class="text-sm font-semibold text-surface-900">Ferosa Support</p>
      <p class="text-xs text-surface-400">Usually replies within a few hours</p>
    </div>
  </div>

  {{-- Status flash --}}
  @if(session('status'))
    <div class="mx-6 mt-3 px-4 py-2.5 bg-brand-50 border border-brand-200 text-brand-700 text-xs rounded-xl flex items-center gap-2">
      <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
      {{ session('status') }}
    </div>
  @endif

  {{-- Messages list --}}
  <div id="msg-list" class="flex-1 overflow-y-auto px-4 sm:px-6 py-5 space-y-1">

    @if($conversation->messages->isEmpty())
      <div class="flex flex-col items-center justify-center h-full py-12">
        <div class="w-16 h-16 rounded-full bg-brand-50 flex items-center justify-center mb-4">
          <svg class="w-8 h-8 text-brand-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
          </svg>
        </div>
        <h2 class="text-base font-semibold text-surface-900 mb-1">Start a conversation</h2>
        <p class="text-surface-400 text-sm text-center max-w-xs">Send us a message and our team will get back to you as soon as possible.</p>
      </div>
    @else
      @php $lastDate = null; @endphp
      @foreach($conversation->messages as $msg)
        @php
          $mine = $msg->sender_id === auth()->id();
          $msgDate = $msg->created_at->format('M j, Y');
        @endphp

        {{-- Date separator --}}
        @if($msgDate !== $lastDate)
          <div class="date-separator">
            <span class="text-[11px] font-medium text-surface-400 whitespace-nowrap">
              @if($msg->created_at->isToday()) Today
              @elseif($msg->created_at->isYesterday()) Yesterday
              @else {{ $msgDate }}
              @endif
            </span>
          </div>
          @php $lastDate = $msgDate; @endphp
        @endif

        <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }} mb-3" data-msg-id="{{ $msg->id }}" data-msg-time="{{ $msg->created_at->toISOString() }}">
          <div class="max-w-[78%] flex flex-col {{ $mine ? 'items-end' : 'items-start' }} gap-0.5">
            @if(!$mine)
              <span class="text-[10px] font-semibold text-brand-600 px-2 mb-0.5">Ferosa Support</span>
            @endif
            <div class="px-4 py-2.5 text-[13px] leading-relaxed {{ $mine ? 'bubble-mine' : 'bubble-theirs' }}">
              {{ $msg->body }}
            </div>
            <span class="text-[10px] text-surface-400 px-2 mt-0.5">
              {{ $msg->created_at->format('g:i A') }}
            </span>
          </div>
        </div>
      @endforeach
    @endif
  </div>

  {{-- Compose --}}
  <div id="compose-bar" class="border-t border-surface-100 bg-white px-4 sm:px-6 py-3 flex-shrink-0 pb-safe">
    <form id="msg-form" method="POST" action="{{ route('messages.store') }}" class="flex items-end gap-2.5">
      @csrf
      <textarea
        id="msg-body"
        name="body"
        rows="1"
        placeholder="Type a message…"
        required
        maxlength="2000"
        class="flex-1 resize-none border border-surface-200 rounded-2xl px-4 py-2.5 text-sm outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100 transition-all max-h-32 overflow-y-auto bg-surface-50 placeholder:text-surface-400"
        style="min-height:44px"
        onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();document.getElementById('msg-form').requestSubmit();}"
      ></textarea>
      <button type="submit" data-loading-label=""
        class="flex-shrink-0 w-10 h-10 bg-brand-600 hover:bg-brand-700 active:scale-95 rounded-full flex items-center justify-center transition-all shadow-sm">
        <svg class="w-4 h-4 text-white translate-x-[1px]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.269 20.876L5.999 12zm0 0h7.5"/>
        </svg>
      </button>
    </form>
    <p class="text-[10px] text-surface-400 text-center mt-2 hidden sm:block">Press Enter to send · Shift+Enter for new line</p>
  </div>
</div>
@endsection

@section('scripts')
<script>
  // Scroll to bottom on load
  const list = document.getElementById('msg-list');
  list.scrollTop = list.scrollHeight;

  // Auto-grow textarea
  const ta = document.getElementById('msg-body');
  ta.addEventListener('input', () => {
    ta.style.height = 'auto';
    ta.style.height = Math.min(ta.scrollHeight, 128) + 'px';
  });

  // Focus textarea on load
  ta.focus();

  // Poll for new messages every 5 seconds
  let lastMsgId = {{ $conversation->messages->max('id') ?? 0 }};
  let lastTime  = '{{ $conversation->messages->max('created_at')?->toISOString() ?? now()->toISOString() }}';

  function buildBubble(msg) {
    const wrap = document.createElement('div');
    wrap.className = `flex ${msg.is_mine ? 'justify-end' : 'justify-start'} mb-3`;
    wrap.dataset.msgId = msg.id;

    const time = new Date(msg.created_at).toLocaleString('en-US', {hour:'numeric', minute:'2-digit'});
    wrap.innerHTML = `
      <div class="max-w-[78%] flex flex-col ${msg.is_mine ? 'items-end' : 'items-start'} gap-0.5">
        ${!msg.is_mine ? `<span class="text-[10px] font-semibold text-brand-600 px-2 mb-0.5">Ferosa Support</span>` : ''}
        <div class="px-4 py-2.5 text-[13px] leading-relaxed ${msg.is_mine ? 'bubble-mine' : 'bubble-theirs'}">${msg.body}</div>
        <span class="text-[10px] text-surface-400 px-2 mt-0.5">${time}</span>
      </div>`;
    return wrap;
  }

  async function pollMessages() {
    try {
      const res = await fetch(`{{ route('messages.poll') }}?after=${encodeURIComponent(lastTime)}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      if (!res.ok) return;
      const data = await res.json();
      if (data.messages && data.messages.length) {
        data.messages.forEach(msg => {
          if (msg.id <= lastMsgId) return;
          lastMsgId = msg.id;
          lastTime  = msg.created_at;
          list.appendChild(buildBubble(msg));
        });
        list.scrollTop = list.scrollHeight;
      }
    } catch {}
  }

  setInterval(pollMessages, 5000);
</script>
@endsection
