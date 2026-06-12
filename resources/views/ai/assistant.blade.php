@extends('layout.layout')

@section('content')
@include('ai.partials.ai-styles')
<div class="content-body">
    <div class="container-fluid">

        {{-- Hero --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="ai-hero shadow-sm">
                    <div class="d-flex flex-wrap justify-content-between align-items-center p-4" style="position:relative; z-index:1;">
                        <div>
                            <span class="ai-hero__chip mb-2"><i class="la la-magic"></i> {{ __('ai.powered_by') }}</span>
                            <h3 class="text-white fw-bold mb-1">{{ __('ai.assistant_title') }}</h3>
                            <p class="mb-0 text-white opacity-75">{{ __('ai.assistant_subtitle') }}</p>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            @if($unlimited)
                                <span class="ai-quota-pill is-unlimited"><i class="la la-infinity"></i> {{ __('ai.unlimited') }}</span>
                            @else
                                <span class="ai-quota-pill {{ ($remaining !== null && $remaining <= 5) ? 'is-low' : '' }}" id="aiQuotaPill">
                                    <i class="la la-bolt"></i> <span id="aiRemaining">{{ $remaining }}</span> {{ __('ai.left_this_month') }}
                                </span>
                            @endif
                            <a href="{{ route('ai.assistant') }}" class="btn btn-light fw-bold text-primary ai-new-chat-btn">
                                <i class="la la-plus"></i> {{ __('ai.new_chat') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(!$configured)
            <div class="alert alert-warning d-flex align-items-center gap-2">
                <i class="la la-exclamation-triangle fs-4"></i>
                <div>{{ __('ai.not_configured_notice') }}</div>
            </div>
        @endif

        <div class="ai-chat-wrap">
            {{-- Conversation list --}}
            <div class="ai-sidebar">
                <a href="{{ route('ai.assistant') }}" class="btn btn-sm btn-primary w-100 ai-new-chat-btn"><i class="la la-edit"></i> {{ __('ai.new_chat') }}</a>
                <div class="ai-conv-list mt-2">
                    @forelse($conversations as $c)
                        <a href="{{ route('ai.assistant', ['c' => $c->id]) }}"
                           class="ai-conv {{ $active && $active->id === $c->id ? 'active' : '' }}"
                           data-id="{{ $c->id }}">
                            <i class="la la-comment-dots"></i>
                            <span class="ai-conv__title">{{ $c->title ?: __('ai.untitled_chat') }}</span>
                        </a>
                    @empty
                        <p class="text-muted small text-center mt-3">{{ __('ai.no_chats') }}</p>
                    @endforelse
                </div>
            </div>

            {{-- Chat --}}
            <div class="ai-chat">
                <div class="ai-thread" id="aiThread">
                    @if($messages->isEmpty())
                        <div class="text-center text-muted py-5" id="aiEmptyState">
                            <i class="la la-robot" style="font-size:3rem; color:#c4b5fd;"></i>
                            <h5 class="mt-3">{{ __('ai.empty_title') }}</h5>
                            <p>{{ __('ai.empty_subtitle') }}</p>
                        </div>
                    @endif
                    @foreach($messages as $m)
                        <div class="ai-msg {{ $m->role === 'user' ? 'user' : 'assistant' }}">
                            <div class="ai-msg__avatar">
                                <i class="la {{ $m->role === 'user' ? 'la-user' : 'la-robot' }}"></i>
                            </div>
                            <div class="ai-msg__bubble">{!! nl2br(e($m->content)) !!}</div>
                        </div>
                    @endforeach
                </div>

                <form class="ai-composer" id="aiForm" autocomplete="off">
                    <textarea class="form-control" id="aiInput" rows="1" placeholder="{{ __('ai.input_placeholder') }}"></textarea>
                    <button type="submit" class="btn btn-primary px-3" id="aiSend"><i class="la la-paper-plane"></i></button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
(function () {
    var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var assistantBase = "{{ route('ai.assistant') }}";
    var sendUrl = "{{ route('ai.assistant.send') }}";
    var untitledLabel = @json(__('ai.untitled_chat'));
    var convList = document.querySelector('.ai-conv-list');
    var thread = document.getElementById('aiThread');
    var form = document.getElementById('aiForm');
    var input = document.getElementById('aiInput');
    var sendBtn = document.getElementById('aiSend');
    var conversationId = {{ $active ? $active->id : 'null' }};
    var busy = false;

    function scrollDown(){ thread.scrollTop = thread.scrollHeight; }
    scrollDown();

    function esc(s){ var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    function addMessage(role, html){
        var empty = document.getElementById('aiEmptyState');
        if (empty) empty.remove();
        var wrap = document.createElement('div');
        wrap.className = 'ai-msg ' + (role === 'user' ? 'user' : 'assistant');
        wrap.innerHTML = '<div class="ai-msg__avatar"><i class="la ' + (role === 'user' ? 'la-user' : 'la-robot') + '"></i></div>' +
                         '<div class="ai-msg__bubble">' + html + '</div>';
        thread.appendChild(wrap);
        scrollDown();
        return wrap;
    }

    function addTyping(){
        var wrap = document.createElement('div');
        wrap.className = 'ai-msg assistant';
        wrap.id = 'aiTyping';
        wrap.innerHTML = '<div class="ai-msg__avatar"><i class="la la-robot"></i></div>' +
                         '<div class="ai-msg__bubble"><span class="ai-typing"><span></span><span></span><span></span></span></div>';
        thread.appendChild(wrap);
        scrollDown();
    }
    function removeTyping(){ var t = document.getElementById('aiTyping'); if (t) t.remove(); }

    function upsertSidebarEntry(id, title) {
        if (!convList || !id) return;
        var empty = convList.querySelector('p.text-muted');
        if (empty) empty.remove();
        convList.querySelectorAll('.ai-conv').forEach(function(el){ el.classList.remove('active'); });
        var link = convList.querySelector('.ai-conv[data-id="' + id + '"]');
        if (!link) {
            link = document.createElement('a');
            link.className = 'ai-conv';
            link.dataset.id = String(id);
            link.href = assistantBase + '?c=' + id;
            link.innerHTML = '<i class="la la-comment-dots"></i><span class="ai-conv__title"></span>';
            convList.insertBefore(link, convList.firstChild);
        }
        link.classList.add('active');
        link.querySelector('.ai-conv__title').textContent = title || untitledLabel;
    }

    function resetNewChat() {
        conversationId = null;
        thread.innerHTML = '<div class="text-center text-muted py-5" id="aiEmptyState">' +
            '<i class="la la-robot" style="font-size:3rem; color:#c4b5fd;"></i>' +
            '<h5 class="mt-3">{{ __('ai.empty_title') }}</h5>' +
            '<p>{{ __('ai.empty_subtitle') }}</p></div>';
        if (convList) {
            convList.querySelectorAll('.ai-conv').forEach(function(el){ el.classList.remove('active'); });
        }
        if (history.replaceState) {
            history.replaceState(null, '', assistantBase);
        }
        input.focus();
    }

    document.querySelectorAll('.ai-new-chat-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            resetNewChat();
        });
    });

    input.addEventListener('input', function(){
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 140) + 'px';
    });
    input.addEventListener('keydown', function(e){
        if (e.key === 'Enter' && !e.shiftKey){ e.preventDefault(); form.requestSubmit(); }
    });

    form.addEventListener('submit', function(e){
        e.preventDefault();
        if (busy) return;
        var text = input.value.trim();
        if (!text) return;

        busy = true; sendBtn.disabled = true;
        addMessage('user', esc(text).replace(/\n/g,'<br>'));
        input.value = ''; input.style.height = 'auto';
        addTyping();

        fetch(sendUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ message: text, conversation_id: conversationId })
        })
        .then(function(r){ return r.json(); })
        .then(function(data){
            removeTyping();
            if (data.ok){
                conversationId = data.conversation_id;
                addMessage('assistant', data.reply_html);
                upsertSidebarEntry(conversationId, data.title);
                var rem = document.getElementById('aiRemaining');
                if (rem && data.remaining !== null && typeof data.remaining !== 'undefined') rem.textContent = data.remaining;
                if (history.replaceState) history.replaceState(null, '', assistantBase + '?c=' + conversationId);
            } else {
                addMessage('assistant', '<span class="text-danger">' + esc(data.message) + '</span>');
            }
        })
        .catch(function(){
            removeTyping();
            addMessage('assistant', '<span class="text-danger">{{ __('ai.error_generic') }}</span>');
        })
        .finally(function(){ busy = false; sendBtn.disabled = false; input.focus(); });
    });
})();
</script>
@endsection
