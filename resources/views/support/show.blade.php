@extends('layout.layout')

@section('content')
@include('support.partials.support-styles')
<div class="content-body">
    <div class="container-fluid">

        {{-- Top bar --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('support.index') }}" class="btn btn-light btn-sm"><i class="la la-arrow-left"></i></a>
                <div>
                    <h5 class="mb-0 fw-bold">{{ $ticket->subject }}</h5>
                    <small class="text-muted">{{ $ticket->ticket_number }} · {{ __('support.category_'.$ticket->category) }}</small>
                </div>
            </div>
            <span class="sp-pill pill-{{ $ticket->status }}" id="sp-status-pill"><span class="dot"></span>{{ __('support.status_'.$ticket->status) }}</span>
        </div>

        <div class="sp-chat">
            {{-- Conversation --}}
            <div class="sp-panel sp-conversation">
                <div class="sp-conversation__head">
                    <div class="d-flex align-items-center gap-2">
                        <span class="sp-ticket__avatar tint-primary" style="width:40px;height:40px;">{{ strtoupper(mb_substr($ticket->user->name ?? '?',0,1)) }}</span>
                        <div>
                            <div class="fw-bold" style="font-size:14px;">{{ $ticket->user->name ?? '—' }}</div>
                            <small class="text-muted">{{ $ticket->institution->name ?? __('support.platform_user') }}</small>
                        </div>
                    </div>
                    <span class="sp-prio prio-{{ $ticket->priority }}"><span class="dot"></span>{{ __('support.priority_'.$ticket->priority) }}</span>
                </div>

                <div class="sp-thread" id="sp-thread">
                    @foreach($ticket->messages as $m)
                        @include('support.partials.message', ['m' => $m, 'isSupport' => $isSupport])
                    @endforeach
                </div>

                @if($ticket->status === 'closed')
                    <div class="sp-composer text-center text-muted">
                        <i class="la la-lock"></i> {{ __('support.thread_closed') }}
                    </div>
                @else
                    <div class="sp-composer">
                        <form id="sp-reply-form" enctype="multipart/form-data">
                            @csrf
                            <div class="d-flex align-items-end gap-2">
                                <label class="sp-attach-btn mb-0" title="{{ __('support.attach') }}">
                                    <i class="la la-paperclip"></i>
                                    <input type="file" name="attachment" id="sp-attachment" hidden>
                                </label>
                                <div class="flex-grow-1">
                                    <span class="sp-attach-name" id="sp-attach-name"></span>
                                    @if(has_ai_access())
                                    <div class="mb-1">
                                        @include('ai.partials.embed-button', [
                                            'tool' => 'support_reply',
                                            'params' => ['ticket_id' => $ticket->id],
                                            'label' => __('ai.btn_support_reply'),
                                            'target' => '#sp-message',
                                        ])
                                    </div>
                                    @endif
                                    <textarea name="message" id="sp-message" rows="1" placeholder="{{ __('support.type_message') }}"></textarea>
                                </div>
                                <button type="submit" class="sp-send-btn" id="sp-send"><i class="la la-paper-plane"></i></button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>

            {{-- Info sidebar --}}
            <div>
                <div class="sp-panel p-3 mb-3">
                    <h6 class="fw-bold mb-2">{{ __('support.details') }}</h6>
                    <div class="sp-info-row"><span class="label">{{ __('support.ticket_no') }}</span><span class="value">{{ $ticket->ticket_number }}</span></div>
                    <div class="sp-info-row"><span class="label">{{ __('support.requester') }}</span><span class="value">{{ $ticket->user->name ?? '—' }}</span></div>
                    @if($ticket->institution)
                        <div class="sp-info-row"><span class="label">{{ __('support.school') }}</span><span class="value">{{ $ticket->institution->name }}</span></div>
                    @endif
                    <div class="sp-info-row"><span class="label">{{ __('support.category') }}</span><span class="value">{{ __('support.category_'.$ticket->category) }}</span></div>
                    <div class="sp-info-row"><span class="label">{{ __('support.priority') }}</span><span class="value sp-prio prio-{{ $ticket->priority }}"><span class="dot"></span>{{ __('support.priority_'.$ticket->priority) }}</span></div>
                    <div class="sp-info-row"><span class="label">{{ __('support.created') }}</span><span class="value">{{ $ticket->created_at->format('M d, Y') }}</span></div>
                    @if($ticket->assignee)
                        <div class="sp-info-row"><span class="label">{{ __('support.assigned_to') }}</span><span class="value">{{ $ticket->assignee->name }}</span></div>
                    @endif
                </div>

                {{-- Actions --}}
                <div class="sp-panel p-3">
                    <h6 class="fw-bold mb-3">{{ __('support.manage') }}</h6>
                    <form action="{{ route('support.status', $ticket->id) }}" method="POST" class="ajax-form" data-ajax-reload="1">
                        @csrf
                        @if($isSupport)
                            <label class="form-label small fw-bold mb-1">{{ __('support.field_status') }}</label>
                            <select name="status" class="form-control mb-3">
                                @foreach(\App\Models\SupportTicket::STATUSES as $s)
                                    <option value="{{ $s }}" @selected($ticket->status===$s)>{{ __('support.status_'.$s) }}</option>
                                @endforeach
                            </select>
                            <label class="form-label small fw-bold mb-1">{{ __('support.field_priority') }}</label>
                            <select name="priority" class="form-control mb-3">
                                @foreach(\App\Models\SupportTicket::PRIORITIES as $p)
                                    <option value="{{ $p }}" @selected($ticket->priority===$p)>{{ __('support.priority_'.$p) }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary w-100"><i class="la la-save"></i> {{ __('support.update') }}</button>
                        @else
                            @if(!$ticket->isClosed())
                                <button type="submit" name="status" value="resolved" class="btn btn-outline-success w-100 mb-2"><i class="la la-check"></i> {{ __('support.mark_resolved') }}</button>
                                <button type="submit" name="status" value="closed" class="btn btn-outline-secondary w-100"><i class="la la-times"></i> {{ __('support.close_ticket') }}</button>
                            @else
                                <button type="submit" name="status" value="open" class="btn btn-outline-primary w-100"><i class="la la-redo"></i> {{ __('support.reopen_ticket') }}</button>
                            @endif
                        @endif
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('js')
<script>
(function() {
    "use strict";
    var thread = document.getElementById('sp-thread');
    var form = document.getElementById('sp-reply-form');
    var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var msgUrl = "{{ route('support.messages', $ticket->id) }}";
    var replyUrl = "{{ route('support.reply', $ticket->id) }}";
    var lastId = {{ $ticket->messages->last()->id ?? 0 }};
    var statusLabels = @json(collect(\App\Models\SupportTicket::STATUSES)->mapWithKeys(fn($s) => [$s => __('support.status_'.$s)]));

    function scrollBottom() { if (thread) thread.scrollTop = thread.scrollHeight; }
    scrollBottom();

    function updateStatusPill(status, label) {
        var pill = document.getElementById('sp-status-pill');
        if (!pill || !status) return;
        pill.className = 'sp-pill pill-' + status;
        pill.innerHTML = '<span class="dot"></span>' + (label || status);
    }

    // Auto-grow textarea
    var ta = document.getElementById('sp-message');
    if (ta) {
        ta.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 140) + 'px';
        });
        ta.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); }
        });
    }

    // Attachment name display
    var fileInput = document.getElementById('sp-attachment');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            document.getElementById('sp-attach-name').textContent = this.files.length ? this.files[0].name : '';
        });
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var btn = document.getElementById('sp-send');
            var body = (ta.value || '').trim();
            if (!body && (!fileInput || !fileInput.files.length)) return;
            btn.disabled = true;

            var fd = new FormData(form);
            fetch(replyUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: fd
            })
            .then(function(r) { return r.json().then(function(d){ return { ok: r.ok, d: d }; }); })
            .then(function(res) {
                btn.disabled = false;
                if (!res.ok || !res.d.ok) {
                    if (window.toastr) toastr.error(res.d.message || 'Error');
                    return;
                }
                if (res.d.html) { thread.insertAdjacentHTML('beforeend', res.d.html); lastId = res.d.last_id; }
                ta.value = ''; ta.style.height = 'auto';
                if (fileInput) { fileInput.value = ''; document.getElementById('sp-attach-name').textContent = ''; }
                updateStatusPill(res.d.status, res.d.status_label);
                scrollBottom();
            })
            .catch(function() { btn.disabled = false; if (window.toastr) toastr.error('Network error'); });
        });
    }

    // Poll for new messages
    function poll() {
        fetch(msgUrl + '?after=' + lastId, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.html && d.html.trim()) {
                    var nearBottom = thread.scrollHeight - thread.scrollTop - thread.clientHeight < 120;
                    thread.insertAdjacentHTML('beforeend', d.html);
                    lastId = d.last_id;
                    if (nearBottom) scrollBottom();
                }
                updateStatusPill(d.status, d.status_label);
            })
            .catch(function() {});
    }
    setInterval(poll, 12000);
})();
</script>
@endsection
