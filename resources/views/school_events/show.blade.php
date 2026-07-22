@extends('layout.layout')

@section('content')
@include('school_events.partials.styles')
<div class="content-body">
    <div class="container-fluid">

        <div class="row page-titles mx-0 mb-4 p-4 bg-white rounded shadow-sm align-items-center">
            <div class="col-sm-7 p-0">
                <div class="welcome-text">
                    <h4 class="text-primary fw-bold fs-20 mb-1">{{ $schoolEvent->name }}</h4>
                    <p class="mb-0 text-muted fs-14">
                        <i class="la la-calendar me-1"></i>{{ localized_date($schoolEvent->event_date, 'd M Y') }}
                        @if($schoolEvent->event_time)
                            · <i class="la la-clock me-1"></i>{{ substr((string) $schoolEvent->event_time, 0, 5) }}
                        @endif
                        @if($schoolEvent->venue)
                            · <i class="la la-map-marker me-1"></i>{{ $schoolEvent->venue }}
                        @endif
                    </p>
                </div>
            </div>
            <div class="col-sm-5 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex gap-2">
                <a href="{{ route('school-events.index') }}" class="btn btn-light">{{ __('school_event.back') }}</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show">{{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="se-stat">
                    <div class="text-muted small">{{ __('school_event.field_status') }}</div>
                    <h5 class="fw-bold mb-0">
                        <span id="eventStatusBadge" class="badge badge-{{ $schoolEvent->status === 'sent' ? 'success' : ($schoolEvent->status === 'sending' ? 'info' : 'warning') }} light">
                            {{ __('school_event.status_' . $schoolEvent->status) }}
                        </span>
                    </h5>
                </div>
            </div>
            <div class="col-md-4">
                <div class="se-stat">
                    <div class="text-muted small">{{ __('school_event.invitation_count') }}</div>
                    <h5 class="fw-bold mb-0">{{ $schoolEvent->invitations->count() }}</h5>
                </div>
            </div>
            <div class="col-md-4">
                <div class="se-stat">
                    <div class="text-muted small">{{ __('school_event.field_audience') }}</div>
                    <h5 class="fw-bold mb-0">{{ __('school_event.audience_' . $schoolEvent->audience) }}</h5>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4" style="border-radius:15px;">
            <div class="card-header border-0 pt-4 px-4 bg-transparent">
                <h5 class="card-title fw-bold mb-0">{{ __('school_event.actions_title') }}</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="d-flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('school-events.build-invitations', $schoolEvent) }}">@csrf
                        <button class="btn btn-secondary shadow-sm"><i class="la la-users me-1"></i> {{ __('school_event.build_invitations') }}</button>
                    </form>
                    <button type="button" class="btn btn-outline-primary shadow-sm" id="previewBtn">
                        <i class="la la-eye me-1"></i> {{ __('school_event.preview') }}
                    </button>
                    <button type="button" class="btn btn-primary shadow-sm" id="sendInvitationsBtn"
                        data-empty="{{ $schoolEvent->invitations->isEmpty() ? '1' : '0' }}"
                        {{ $schoolEvent->status === 'sending' || $schoolEvent->invitations->isEmpty() ? 'disabled' : '' }}>
                        <i class="la la-paper-plane me-1"></i>
                        <span id="sendInvitationsBtnLabel">{{ __('school_event.send_invitations') }}</span>
                    </button>
                </div>
                <div id="sendProgressAlert" class="alert alert-info border mt-3 mb-0 {{ $schoolEvent->status === 'sending' ? '' : 'd-none' }}">
                    <div class="d-flex align-items-center gap-2">
                        <div class="spinner-border spinner-border-sm text-info" role="status"></div>
                        <div>
                            <strong>{{ __('school_event.job_running_title') }}</strong>
                            <div class="small mb-0">{{ __('school_event.job_queued') }}</div>
                        </div>
                    </div>
                </div>
                <div id="previewBox" class="alert alert-light border mt-3 d-none mb-0">
                    <div class="small text-muted mb-1">{{ __('school_event.preview') }}</div>
                    <pre class="mb-0 small" id="previewText" style="white-space:pre-wrap;"></pre>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0" style="border-radius:15px;">
            <div class="card-header border-0 pt-4 px-4 bg-transparent d-flex justify-content-between align-items-center">
                <h5 class="card-title fw-bold mb-0">{{ __('school_event.recipients_title') }}</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="table-responsive digitex-dt-wrap">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>{{ __('school_event.recipient') }}</th>
                                <th>{{ __('school_event.phone') }}</th>
                                <th>{{ __('school_event.telegram') }}</th>
                                <th>{{ __('school_event.field_status') }}</th>
                                <th>{{ __('school_event.channels') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($schoolEvent->invitations as $inv)
                            <tr>
                                <td>{{ $inv->recipient_name }}</td>
                                <td>{{ $inv->recipient_phone ?: '—' }}</td>
                                <td>{{ $inv->recipient_telegram_chat_id ?: '—' }}</td>
                                <td>
                                    @php
                                        $deliveryKey = 'school_event.delivery_' . $inv->delivery_status;
                                        $deliveryLabel = __($deliveryKey);
                                        if ($deliveryLabel === $deliveryKey) {
                                            $deliveryLabel = ucfirst($inv->delivery_status);
                                        }
                                        $deliveryBadge = match ($inv->delivery_status) {
                                            'sent' => 'success',
                                            'partial' => 'warning',
                                            'failed' => 'danger',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge badge-{{ $deliveryBadge }} light">{{ $deliveryLabel }}</span>
                                </td>
                                <td class="small">
                                    @php $meta = $inv->delivery_meta ?? []; @endphp
                                    @if(empty($meta))
                                        —
                                    @else
                                        @foreach(['sms','whatsapp','email','telegram'] as $ch)
                                            @if(isset($meta[$ch]))
                                                <div>
                                                    <strong>{{ strtoupper($ch) }}:</strong>
                                                    <span class="text-{{ $meta[$ch] === 'sent' ? 'success' : 'danger' }}">{{ $meta[$ch] }}</span>
                                                    @if(!empty($meta[$ch . '_error']))
                                                        <span class="text-muted">({{ $meta[$ch . '_error'] }})</span>
                                                    @endif
                                                </div>
                                            @endif
                                        @endforeach
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">{{ __('school_event.no_recipients') }}</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function () {
    const sendUrl = @json(route('school-events.send', $schoolEvent));
    const statusUrl = @json(route('school-events.send-status', $schoolEvent));
    const csrf = @json(csrf_token());
    const statusLabels = {
        draft: @json(__('school_event.status_draft')),
        sending: @json(__('school_event.status_sending')),
        sent: @json(__('school_event.status_sent')),
    };

    let pollTimer = null;
    let pollCount = 0;

    function setSendingUi(isSending) {
        const btn = document.getElementById('sendInvitationsBtn');
        const label = document.getElementById('sendInvitationsBtnLabel');
        const alertBox = document.getElementById('sendProgressAlert');
        const badge = document.getElementById('eventStatusBadge');

        if (btn) {
            btn.disabled = isSending || btn.dataset.empty === '1';
        }
        if (label) {
            label.textContent = isSending
                ? @json(__('school_event.sending_btn'))
                : @json(__('school_event.send_invitations'));
        }
        if (alertBox) {
            alertBox.classList.toggle('d-none', !isSending);
        }
        if (badge) {
            badge.className = 'badge light badge-' + (isSending ? 'info' : (badge.dataset.after || 'warning'));
            badge.textContent = isSending ? statusLabels.sending : (badge.dataset.afterLabel || statusLabels.draft);
        }
    }

    function stopPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
        pollCount = 0;
    }

    function startPolling() {
        stopPolling();
        pollTimer = setInterval(async function () {
            pollCount++;
            if (window.DigitexNotifications && typeof window.DigitexNotifications.sync === 'function') {
                window.DigitexNotifications.sync({ force: true });
            }
            try {
                const res = await fetch(statusUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (data.status && data.status !== 'sending') {
                    stopPolling();
                    setSendingUi(false);
                    const badge = document.getElementById('eventStatusBadge');
                    if (badge) {
                        badge.className = 'badge light badge-' + (data.status === 'sent' ? 'success' : 'warning');
                        badge.textContent = statusLabels[data.status] || data.status;
                    }
                    Swal.fire({
                        icon: data.status === 'sent' ? 'success' : 'info',
                        title: @json(__('school_event.job_done_title')),
                        text: @json(__('school_event.job_done_reload')),
                        confirmButtonText: @json(__('school_event.reload_page')),
                    }).then(function () {
                        window.location.reload();
                    });
                }
            } catch (e) {}
            if (pollCount >= 120) {
                stopPolling();
            }
        }, 4000);
    }

    $('#previewBtn').on('click', function () {
        $.get('{{ route('school-events.preview', $schoolEvent) }}', function (res) {
            $('#previewBox').removeClass('d-none');
            $('#previewText').text(res.preview || '');
        }).fail(function (xhr) {
            Swal.fire({
                icon: 'warning',
                title: @json(__('school_event.preview')),
                text: xhr.responseJSON?.message || @json(__('school_event.error_generic'))
            });
        });
    });

    $('#sendInvitationsBtn').on('click', function () {
        Swal.fire({
            title: @json(__('school_event.confirm_send')),
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: @json(__('school_event.send_invitations')),
            cancelButtonText: @json(__('school_event.back')),
        }).then(function (result) {
            if (!result.isConfirmed) return;

            Swal.fire({
                title: @json(__('school_event.job_running_title')),
                html: @json(__('school_event.job_starting')),
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: function () {
                    Swal.showLoading();
                },
            });

            fetch(sendUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({}),
            })
            .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
            .then(function (result) {
                if (!result.ok || result.data.status === 'error') {
                    Swal.fire({
                        icon: 'error',
                        title: @json(__('school_event.error_generic')),
                        text: result.data.message || @json(__('school_event.error_generic')),
                    });
                    return;
                }

                setSendingUi(true);
                Swal.fire({
                    icon: 'info',
                    title: @json(__('school_event.job_queued_title')),
                    text: result.data.message || @json(__('school_event.job_queued')),
                    confirmButtonText: 'OK',
                });
                startPolling();
            })
            .catch(function () {
                Swal.fire({
                    icon: 'error',
                    title: @json(__('school_event.error_generic')),
                    text: @json(__('school_event.error_generic')),
                });
            });
        });
    });

    @if($schoolEvent->status === 'sending')
    setSendingUi(true);
    startPolling();
    @endif
})();
</script>
@endsection
