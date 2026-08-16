@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-7 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('reenrollment.page_title') }}</h4>
                    <p class="mb-0">{{ __('reenrollment.manage_subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-5 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex flex-wrap gap-2">
                <a href="{{ route('reenrollments.manual') }}" class="btn btn-outline-primary btn-rounded">
                    <i class="fa fa-file-pdf-o me-2"></i> {{ __('reenrollment.manual_download') }}
                </a>
                @canany(['student_reenrollment.create', 'student_promotion.create'])
                    <button type="button" class="btn btn-primary btn-rounded" data-bs-toggle="modal" data-bs-target="#openCampaignModal">
                        <i class="fa fa-plus me-2"></i> {{ __('reenrollment.open_campaign') }}
                    </button>
                @endcanany
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fa fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fa fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fa fa-info-circle me-2"></i>{{ __('reenrollment.workflow_hint') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        @if(isset($messagingReady) && ! $messagingReady)
            <div class="alert alert-warning" role="alert">
                <i class="fa fa-triangle-exclamation me-2"></i>{{ __('reenrollment.messaging_off') }}
                @can('setting.view')
                    <a href="{{ route('configuration.index') }}" class="alert-link">{{ __('reenrollment.open_configuration') }}</a>
                @endcan
            </div>
        @endif

        @if($campaigns->isEmpty())
            <div class="card shadow-sm border-0">
                <div class="card-body text-center py-5">
                    <i class="la la-calendar-check-o text-muted" style="font-size:48px;"></i>
                    <p class="text-muted mb-3 mt-2">{{ __('reenrollment.no_campaign') }}</p>
                    @canany(['student_reenrollment.create', 'student_promotion.create'])
                        <button type="button" class="btn btn-primary btn-rounded" data-bs-toggle="modal" data-bs-target="#openCampaignModal">
                            <i class="fa fa-plus me-2"></i>{{ __('reenrollment.open_campaign') }}
                        </button>
                    @endcanany
                </div>
            </div>
        @else
            @if($activeCampaign)
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h5 class="mb-1">{{ $activeCampaign->name }}</h5>
                        <span class="text-muted small">
                            {{ $activeCampaign->fromSession->name ?? '?' }} → {{ $activeCampaign->toSession->name ?? '?' }}
                            @if($activeCampaign->opens_at)
                                · {{ localized_date($activeCampaign->opens_at, 'd M Y') }}
                                @if($activeCampaign->closes_at) — {{ localized_date($activeCampaign->closes_at, 'd M Y') }} @endif
                            @endif
                        </span>
                    </div>
                    <div class="text-sm-end">
                        <span class="badge badge-{{ $activeCampaign->status === 'open' ? 'success' : 'secondary' }} light">
                            {{ __('reenrollment.campaign_status.'.$activeCampaign->status) }}
                        </span>
                        @if((float) $activeCampaign->min_fee_amount > 0)
                            <div class="small text-muted mt-1">
                                {{ __('reenrollment.min_fee') }}:
                                {{ \App\Enums\CurrencySymbol::default() }} {{ number_format((float) $activeCampaign->min_fee_amount, 2) }}
                            </div>
                        @endif
                        @if($activeCampaign->invitations_sent_at)
                            <div class="small text-muted">
                                <i class="fa fa-paper-plane me-1"></i>
                                {{ __('reenrollment.invitations_sent_on', [
                                    'count' => $activeCampaign->invitations_sent_count,
                                    'date' => localized_date($activeCampaign->invitations_sent_at, 'd M Y H:i'),
                                ]) }}
                            </div>
                        @endif
                    </div>
                </div>
                @canany(['student_reenrollment.create', 'student_promotion.create'])
                <div class="card-footer border-0 bg-transparent pt-0">
                    <div class="d-flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('reenrollments.campaigns.invite', $activeCampaign) }}">
                            @csrf
                            <button class="btn btn-primary btn-sm btn-rounded" @disabled($activeCampaign->status !== 'open')>
                                <i class="fa fa-paper-plane me-2"></i>{{ __('reenrollment.send_invitations') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('reenrollments.campaigns.invite', $activeCampaign) }}">
                            @csrf
                            <input type="hidden" name="mode" value="reminder">
                            <button class="btn btn-outline-primary btn-sm btn-rounded" @disabled($activeCampaign->status !== 'open')>
                                <i class="fa fa-bell me-2"></i>{{ __('reenrollment.send_reminders') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('reenrollments.campaigns.sync', $activeCampaign) }}">
                            @csrf
                            <button class="btn btn-light btn-sm btn-rounded">
                                <i class="fa fa-rotate-right me-2"></i>{{ __('reenrollment.sync_students') }}
                            </button>
                        </form>
                        <a href="{{ route('reenrollments.export', request()->only(['campaign_id', 'status', 'q'])) }}" class="btn btn-light btn-sm btn-rounded">
                            <i class="fa fa-file-csv me-2"></i>{{ __('reenrollment.export') }}
                        </a>
                        @if($activeCampaign->status === 'open')
                            <form method="POST" action="{{ route('reenrollments.campaigns.close', $activeCampaign) }}"
                                  onsubmit="return confirm('{{ __('reenrollment.confirm_close') }}');">
                                @csrf
                                <button class="btn btn-outline-danger btn-sm btn-rounded">
                                    <i class="fa fa-lock me-2"></i>{{ __('reenrollment.close_campaign') }}
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('reenrollments.campaigns.reopen', $activeCampaign) }}">
                                @csrf
                                <button class="btn btn-outline-success btn-sm btn-rounded">
                                    <i class="fa fa-lock-open me-2"></i>{{ __('reenrollment.reopen_campaign') }}
                                </button>
                            </form>
                        @endif
                    </div>
                    <small class="text-muted d-block mt-2">{{ __('reenrollment.campaign_actions_hint') }}</small>
                </div>
                @endcanany
            </div>
            @endif

            @if(!empty($stats))
            <div class="row">
                @foreach([
                    ['concerned', 'stats_concerned', 'primary', 'la-users'],
                    ['pending', 'stats_pending', 'secondary', 'la-hourglass-half'],
                    ['queue', 'stats_queue', 'info', 'la-inbox'],
                    ['partial', 'stats_partial', 'warning', 'la-adjust'],
                    ['confirmed', 'stats_confirmed', 'success', 'la-check-circle'],
                    ['declined', 'stats_declined', 'dark', 'la-ban'],
                    ['rejected', 'stats_rejected', 'danger', 'la-times-circle'],
                ] as [$key, $label, $color, $icon])
                    <div class="col-xl col-lg-3 col-md-4 col-sm-6">
                        <div class="widget-stat card">
                            <div class="card-body p-4">
                                <div class="media ai-icon">
                                    <span class="me-3 bgl-{{ $color }} text-{{ $color }}">
                                        <i class="la {{ $icon }}"></i>
                                    </span>
                                    <div class="media-body">
                                        <p class="mb-1">{{ __('reenrollment.'.$label) }}</p>
                                        <h4 class="mb-0">{{ $stats[$key] ?? 0 }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @endif

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('reenrollment.campaign') }}</label>
                            <select name="campaign_id" class="form-control default-select" onchange="this.form.submit()">
                                @foreach($campaigns as $c)
                                    <option value="{{ $c->id }}" @selected(optional($activeCampaign)->id == $c->id)>
                                        {{ $c->name }} ({{ $c->fromSession->name ?? '?' }} → {{ $c->toSession->name ?? '?' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('reenrollment.status') }}</label>
                            <select name="status" class="form-control default-select">
                                <option value="queue" @selected($status === 'queue')>{{ __('reenrollment.queue') }}</option>
                                <option value="pending" @selected($status === 'pending')>{{ __('reenrollment.status_pending') }}</option>
                                <option value="partial_confirmation" @selected($status === 'partial_confirmation')>{{ __('reenrollment.status_partial_confirmation') }}</option>
                                <option value="pending_review" @selected($status === 'pending_review')>{{ __('reenrollment.status_pending_review') }}</option>
                                <option value="confirmed" @selected($status === 'confirmed')>{{ __('reenrollment.status_confirmed') }}</option>
                                <option value="declined" @selected($status === 'declined')>{{ __('reenrollment.status_declined') }}</option>
                                <option value="rejected" @selected($status === 'rejected')>{{ __('reenrollment.status_rejected') }}</option>
                                <option value="expired" @selected($status === 'expired')>{{ __('reenrollment.status_expired') }}</option>
                                <option value="all" @selected($status === 'all')>{{ __('reenrollment.all') }}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('reenrollment.search') }}</label>
                            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="{{ __('reenrollment.search_placeholder') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label d-none d-md-block">&nbsp;</label>
                            <button class="btn btn-primary btn-rounded w-100">
                                <i class="fa fa-search me-2"></i>{{ __('reenrollment.filter') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header border-0 pb-0">
                    <h4 class="card-title">{{ __('reenrollment.confirmations_list') }}</h4>
                    <span class="badge badge-light">{{ $confirmations->total() }}</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-responsive-md align-middle card-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('reenrollment.student') }}</th>
                                    <th>{{ __('reenrollment.admission_no') }}</th>
                                    <th>{{ __('reenrollment.current_class') }}</th>
                                    <th>{{ __('reenrollment.parent_confirmation') }}</th>
                                    <th>{{ __('reenrollment.payment') }}</th>
                                    <th>{{ __('reenrollment.status') }}</th>
                                    <th class="text-end">{{ __('reenrollment.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($confirmations as $i => $row)
                                    <tr>
                                        <td>{{ $confirmations->firstItem() + $i }}</td>
                                        <td>
                                            <a href="{{ route('reenrollments.show', $row) }}" class="fw-bold text-primary">
                                                {{ $row->student->full_name ?? '—' }}
                                            </a>
                                        </td>
                                        <td>{{ $row->student->admission_number ?? '—' }}</td>
                                        <td>{{ class_section_label($row->fromClassSection) }}</td>
                                        <td>
                                            @if($row->parent_confirmed_at)
                                                @php
                                                    $channelIcon = match($row->parent_confirmation_channel) {
                                                        'whatsapp' => 'fab fa-whatsapp text-success',
                                                        'physical' => 'la la-building text-primary',
                                                        default => 'la la-globe text-info',
                                                    };
                                                @endphp
                                                <span class="d-inline-flex align-items-center">
                                                    <i class="{{ $channelIcon }} me-1"></i>
                                                    {{ ucfirst($row->parent_confirmation_channel ?? '') }}
                                                </span>
                                                <div><small class="text-muted">{{ localized_date($row->parent_confirmed_at, 'd M Y H:i') }}</small></div>
                                            @elseif($row->invitation_sent_at)
                                                <span class="text-muted small">
                                                    <i class="fa fa-paper-plane me-1"></i>{{ __('reenrollment.invited_on', ['date' => localized_date($row->invitation_sent_at, 'd M Y')]) }}
                                                </span>
                                                @if($row->reminder_count)
                                                    <div><small class="text-muted">{{ __('reenrollment.reminders_count', ['count' => $row->reminder_count]) }}</small></div>
                                                @endif
                                            @else
                                                <span class="text-muted">{{ __('reenrollment.not_invited') }}</span>
                                            @endif
                                        </td>
                                        <td style="min-width:150px;">
                                            @php
                                                $required = (float) $row->amount_required;
                                                $paid = (float) $row->amount_paid;
                                                $pct = $required > 0 ? min(100, round(($paid / $required) * 100)) : ($paid > 0 ? 100 : 0);
                                                $payColor = $pct >= 100 ? 'success' : ($pct > 0 ? 'warning' : 'danger');
                                            @endphp
                                            <div class="small">
                                                <span class="fw-bold">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($paid, 2) }}</span>
                                                <span class="text-muted">/ {{ number_format($required, 2) }}</span>
                                            </div>
                                            @if($required > 0)
                                                <div class="progress mt-1" style="height:5px;">
                                                    <div class="progress-bar bg-{{ $payColor }}" style="width: {{ $pct }}%;"></div>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $badge = match($row->status) {
                                                    'confirmed' => 'success',
                                                    'pending_review' => 'info',
                                                    'partial_confirmation' => 'warning',
                                                    'rejected', 'declined' => 'danger',
                                                    default => 'secondary',
                                                };
                                            @endphp
                                            <span class="badge badge-{{ $badge }} light">{{ $row->statusLabel() }}</span>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('reenrollments.show', $row) }}" class="btn btn-primary btn-xs btn-rounded">
                                                <i class="fa fa-search me-1"></i>{{ __('reenrollment.review') }}
                                            </a>
                                            @if(! $row->parent_confirmed_at)
                                                @canany(['student_reenrollment.create', 'student_promotion.create'])
                                                    <form method="POST" action="{{ route('reenrollments.remind', $row) }}" class="d-inline">
                                                        @csrf
                                                        <button class="btn btn-light btn-xs btn-rounded" title="{{ __('reenrollment.send_reminder') }}">
                                                            <i class="fa fa-bell"></i>
                                                        </button>
                                                    </form>
                                                @endcanany
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <i class="la la-inbox text-muted" style="font-size:42px;"></i>
                                            <p class="text-muted mb-0 mt-2">{{ __('reenrollment.no_records') }}</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $confirmations->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

@canany(['student_reenrollment.create', 'student_promotion.create'])
<div class="modal fade" id="openCampaignModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('reenrollments.campaigns.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('reenrollment.open_campaign') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">{{ __('reenrollment.campaign') }}</label>
                    <input type="text" name="name" class="form-control" required placeholder="Re-enrollment 2027-2028">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('reenrollment.from_session') }}</label>
                        <select name="from_academic_session_id" class="form-control" required>
                            <option value="">—</option>
                            @foreach($sessions as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('reenrollment.to_session') }}</label>
                        <select name="to_academic_session_id" class="form-control" required>
                            <option value="">—</option>
                            @foreach($sessions as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('reenrollment.min_fee') }}</label>
                        <input type="number" step="0.01" min="0" name="min_fee_amount" class="form-control" value="0">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('reenrollment.opens_at') }}</label>
                        <input type="text" name="opens_at" class="form-control datepicker-modal" value="{{ now()->toDateString() }}" placeholder="YYYY-MM-DD" autocomplete="off">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('reenrollment.closes_at') }}</label>
                        <input type="text" name="closes_at" class="form-control datepicker-modal" placeholder="YYYY-MM-DD" autocomplete="off">
                    </div>
                </div>
                <div class="mb-0">
                    <label class="form-label">{{ __('reenrollment.notes') }}</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-rounded" data-bs-dismiss="modal">{{ __('reenrollment.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-rounded">
                    <i class="fa fa-check me-2"></i>{{ __('reenrollment.create_campaign') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endcanany

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('openCampaignModal');
    if (!modal || typeof jQuery === 'undefined' || !jQuery().bootstrapMaterialDatePicker) return;

    function initModalDatepickers() {
        jQuery(modal).find('.datepicker-modal').each(function () {
            const $el = jQuery(this);
            if ($el.data('plugin_bootstrapMaterialDatePicker')) {
                $el.bootstrapMaterialDatePicker('destroy');
            }
            $el.bootstrapMaterialDatePicker({
                weekStart: 0,
                time: false,
                format: 'YYYY-MM-DD',
                triggerEvent: 'click'
            });
        });
    }

    modal.addEventListener('shown.bs.modal', initModalDatepickers);
});
</script>
@endsection
