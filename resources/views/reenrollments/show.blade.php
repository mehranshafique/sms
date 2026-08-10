@extends('layout.layout')

@section('content')
@php
    $currency = \App\Enums\CurrencySymbol::default();
    $inQueue = $confirmation->isInReviewQueue();
    $statusBadge = match($confirmation->status) {
        'confirmed' => 'success',
        'pending_review' => 'info',
        'partial_confirmation' => 'warning',
        'rejected', 'declined' => 'danger',
        default => 'secondary',
    };
    $att = $summary['attendance'];
    $attTotal = (int) ($att['present'] + $att['absent'] + $att['late'] + $att['excused']);
    $attRate = $attTotal > 0 ? round(($att['present'] / $attTotal) * 100) : null;
    $required = (float) $summary['amount_required'];
    $paid = (float) $summary['amount_paid'];
    $payPct = $required > 0 ? min(100, round(($paid / $required) * 100)) : ($paid > 0 ? 100 : 0);
    $initials = collect(explode(' ', trim((string) $summary['student_name'])))
        ->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
@endphp
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-7 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('reenrollment.review_title') }}</h4>
                    <p class="mb-0">{{ __('reenrollment.review_subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-5 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('reenrollments.index', ['campaign_id' => $confirmation->campaign_id, 'status' => 'queue']) }}" class="btn btn-light btn-rounded">
                    <i class="fa fa-arrow-left me-2"></i> {{ __('reenrollment.back_to_list') }}
                </a>
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

        {{-- STUDENT HEADER --}}
        <div class="card h-auto shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <div class="media align-items-center">
                            <div class="me-3 d-flex align-items-center justify-content-center rounded-circle bgl-primary text-primary fw-bold"
                                 style="width:64px;height:64px;font-size:22px;">{{ $initials ?: '—' }}</div>
                            <div class="media-body">
                                <h4 class="mb-1">{{ $summary['student_name'] }}</h4>
                                <span class="badge badge-light text-dark me-1">{{ $summary['admission_number'] }}</span>
                                <span class="badge badge-{{ $statusBadge }} light">{{ $confirmation->statusLabel() }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 mt-3 mt-lg-0">
                        <div class="row text-lg-end">
                            <div class="col-6 col-md-4 mb-2">
                                <div class="text-muted small">{{ __('reenrollment.admission_date') }}</div>
                                <div class="fw-bold">{{ $summary['admission_date'] ? localized_date($summary['admission_date'], 'd M Y') : '—' }}</div>
                            </div>
                            <div class="col-6 col-md-4 mb-2">
                                <div class="text-muted small">{{ __('reenrollment.years_in_school') }}</div>
                                <div class="fw-bold">{{ $summary['years_in_school'] }}</div>
                            </div>
                            <div class="col-12 col-md-4 mb-2">
                                <div class="text-muted small">{{ __('reenrollment.current_class') }} → {{ __('reenrollment.proposed_class') }}</div>
                                <div class="fw-bold">
                                    {{ $summary['current_class'] ?: '—' }}
                                    <i class="fa fa-arrow-right text-muted mx-1"></i>
                                    {{ $summary['proposed_class'] ?: '—' }}
                                </div>
                                <small class="text-muted">{{ $summary['from_session'] }} → {{ $summary['to_session'] }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- KPI ROW --}}
        <div class="row">
            <div class="col-xl-3 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-primary text-primary"><i class="la la-line-chart"></i></span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('reenrollment.exam_average') }}</p>
                                <h4 class="mb-0">{{ $summary['exam_average'] ?? '—' }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-danger text-danger"><i class="la la-money"></i></span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('reenrollment.fees_outstanding_session') }}</p>
                                <h4 class="mb-0">{{ $currency }} {{ number_format($summary['fees_outstanding_session'], 2) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-warning text-warning"><i class="la la-file-invoice-dollar"></i></span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('reenrollment.reenroll_fee_remaining') }}</p>
                                <h4 class="mb-0">{{ $currency }} {{ number_format($summary['remaining_for_reenroll'], 2) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-success text-success"><i class="la la-check-square-o"></i></span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('reenrollment.present') }}</p>
                                <h4 class="mb-0">{{ $attRate !== null ? $attRate.'%' : '—' }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-8">
                {{-- ACADEMIC --}}
                <div class="card h-auto shadow-sm border-0 mb-4">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title"><i class="la la-graduation-cap text-primary me-2"></i>{{ __('reenrollment.academic') }}</h4>
                    </div>
                    <div class="card-body row">
                        <div class="col-md-4 mb-2">
                            <div class="text-muted small">{{ __('reenrollment.exam_count') }}</div>
                            <div class="fw-bold">{{ $summary['exam_records_count'] }}</div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="text-muted small">{{ __('reenrollment.exam_average') }}</div>
                            <div class="fw-bold">{{ $summary['exam_average'] ?? '—' }}</div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="text-muted small">{{ __('reenrollment.annual_status') }}</div>
                            <div>{{ $summary['annual_status'] }}</div>
                        </div>
                    </div>
                </div>

                {{-- FEES --}}
                <div class="card h-auto shadow-sm border-0 mb-4">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title"><i class="la la-money text-success me-2"></i>{{ __('reenrollment.fees') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless align-middle mb-3">
                                <tbody>
                                    <tr>
                                        <td class="text-muted">{{ __('reenrollment.fees_paid_session') }}</td>
                                        <td class="text-end fw-bold">{{ $currency }} {{ number_format($summary['fees_paid_session'], 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">{{ __('reenrollment.fees_outstanding_session') }}</td>
                                        <td class="text-end fw-bold text-danger">{{ $currency }} {{ number_format($summary['fees_outstanding_session'], 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">{{ __('reenrollment.fees_outstanding_prior') }}</td>
                                        <td class="text-end fw-bold">{{ $currency }} {{ number_format($summary['fees_outstanding_prior'], 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">{{ __('reenrollment.reenroll_fee_paid') }} / {{ __('reenrollment.reenroll_fee_required') }}</span>
                            <span class="fw-bold">
                                {{ $currency }} {{ number_format($paid, 2) }}
                                <span class="text-muted">/ {{ number_format($required, 2) }}</span>
                            </span>
                        </div>
                        <div class="progress" style="height:8px;">
                            <div class="progress-bar bg-{{ $payPct >= 100 ? 'success' : ($payPct > 0 ? 'warning' : 'danger') }}" style="width: {{ $payPct }}%;"></div>
                        </div>
                        @if($summary['remaining_for_reenroll'] > 0)
                            <div class="small text-danger mt-2">
                                <i class="fa fa-exclamation-circle me-1"></i>
                                {{ __('reenrollment.reenroll_fee_remaining') }}: {{ $currency }} {{ number_format($summary['remaining_for_reenroll'], 2) }}
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ATTENDANCE --}}
                <div class="card h-auto shadow-sm border-0 mb-4">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title"><i class="la la-calendar-check-o text-info me-2"></i>{{ __('reenrollment.attendance') }}</h4>
                        @if($attRate !== null)
                            <span class="badge badge-{{ $attRate >= 90 ? 'success' : ($attRate >= 75 ? 'warning' : 'danger') }} light">{{ $attRate }}%</span>
                        @endif
                    </div>
                    <div class="card-body row">
                        @foreach([
                            ['present', 'success'],
                            ['absent', 'danger'],
                            ['late', 'warning'],
                            ['excused', 'info'],
                        ] as [$attKey, $attColor])
                            <div class="col-6 col-md-3 mb-2">
                                <div class="text-muted small">{{ __('reenrollment.'.$attKey) }}</div>
                                <div class="fw-bold text-{{ $attColor }} fs-5">{{ $att[$attKey] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- DISCIPLINE --}}
                <div class="card h-auto shadow-sm border-0 mb-4">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title"><i class="la la-gavel text-warning me-2"></i>{{ __('reenrollment.discipline') }}</h4>
                        <span class="badge badge-{{ $summary['discipline_count'] > 0 ? 'warning' : 'success' }} light">
                            {{ __('reenrollment.discipline_incidents') }}: {{ $summary['discipline_count'] }}
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="text-muted small mb-2">{{ __('reenrollment.conduct_records') }}</div>
                        @if($summary['conduct_records']->isEmpty())
                            <div class="text-muted">{{ __('reenrollment.none') }}</div>
                        @else
                            <ul class="list-group list-group-flush">
                                @foreach($summary['conduct_records'] as $c)
                                    <li class="list-group-item px-0 d-flex justify-content-between align-items-start">
                                        <div>
                                            <span class="badge badge-light text-dark me-2">{{ $c->scope_type }} / {{ $c->scope_key }}</span>
                                            @if($c->notes)<div class="small text-muted mt-1">{{ $c->notes }}</div>@endif
                                        </div>
                                        <span class="fw-bold">{{ $c->conduct }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>

                {{-- PARENT CONFIRMATION --}}
                <div class="card h-auto shadow-sm border-0 mb-4">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title"><i class="la la-user-check text-primary me-2"></i>{{ __('reenrollment.parent_block') }}</h4>
                    </div>
                    <div class="card-body">
                        @if($confirmation->parent_confirmed_at)
                            @php
                                $channelIcon = match($confirmation->parent_confirmation_channel) {
                                    'whatsapp' => 'fab fa-whatsapp text-success',
                                    'physical' => 'la la-building text-primary',
                                    default => 'la la-globe text-info',
                                };
                            @endphp
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <div class="text-muted small">{{ __('reenrollment.channel') }}</div>
                                    <div class="fw-bold">
                                        <i class="{{ $channelIcon }} me-1"></i>{{ ucfirst($confirmation->parent_confirmation_channel ?? '—') }}
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="text-muted small">{{ __('reenrollment.confirmed_at') }}</div>
                                    <div>{{ localized_date($confirmation->parent_confirmed_at, 'd M Y H:i') }}</div>
                                </div>
                                <div class="col-md-12">
                                    <div class="text-muted small">{{ __('reenrollment.parent_note') }}</div>
                                    <div>{{ $confirmation->parent_note ?: '—' }}</div>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-warning mb-3">
                                <i class="fa fa-clock me-2"></i>{{ __('reenrollment.not_confirmed_yet') }}
                            </div>
                            <div class="text-muted small mb-3">
                                @if($confirmation->invitation_sent_at)
                                    <i class="fa fa-paper-plane me-1"></i>
                                    {{ __('reenrollment.invited_on', ['date' => localized_date($confirmation->invitation_sent_at, 'd M Y H:i')]) }}
                                    @if($confirmation->reminder_count)
                                        · {{ __('reenrollment.reminders_count', ['count' => $confirmation->reminder_count]) }}
                                    @endif
                                @else
                                    <i class="fa fa-info-circle me-1"></i>{{ __('reenrollment.not_invited') }}
                                @endif
                            </div>
                            @canany(['student_reenrollment.create', 'student_promotion.create'])
                            <div class="d-flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('reenrollments.physical', $confirmation) }}">
                                    @csrf
                                    <input type="hidden" name="action" value="confirm">
                                    <button class="btn btn-success btn-rounded">
                                        <i class="fa fa-check me-2"></i>{{ __('reenrollment.physical_confirm') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('reenrollments.physical', $confirmation) }}">
                                    @csrf
                                    <input type="hidden" name="action" value="decline">
                                    <button class="btn btn-outline-danger btn-rounded">
                                        <i class="fa fa-times me-2"></i>{{ __('reenrollment.physical_decline') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('reenrollments.remind', $confirmation) }}">
                                    @csrf
                                    <button class="btn btn-outline-primary btn-rounded">
                                        <i class="fa fa-bell me-2"></i>{{ __('reenrollment.send_reminder') }}
                                    </button>
                                </form>
                            </div>
                            @endcanany
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                @canany(['student_reenrollment.update', 'student_promotion.create'])
                <div class="card h-auto shadow-sm border-0 mb-4">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title"><i class="la la-check-circle text-success me-2"></i>{{ __('reenrollment.decision') }}</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('reenrollments.proposed', $confirmation) }}" class="mb-4 pb-3 border-bottom">
                            @csrf
                            <label class="form-label">{{ __('reenrollment.proposed_class') }}</label>
                            <select name="proposed_class_section_id" class="form-control default-select mb-2">
                                <option value="">—</option>
                                @foreach($classes as $id => $label)
                                    <option value="{{ $id }}" @selected($confirmation->proposed_class_section_id == $id)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-light btn-rounded btn-sm">
                                <i class="fa fa-save me-2"></i>{{ __('reenrollment.save_proposed') }}
                            </button>
                        </form>

                        @if($inQueue)
                            <form method="POST" action="{{ route('reenrollments.approve', $confirmation) }}" class="mb-3">
                                @csrf
                                <label class="form-label">{{ __('reenrollment.target_class') }} <span class="text-danger">*</span></label>
                                <select name="approved_class_section_id" class="form-control default-select mb-2" required>
                                    <option value="">—</option>
                                    @foreach($classes as $id => $label)
                                        <option value="{{ $id }}" @selected(($confirmation->proposed_class_section_id ?: $confirmation->from_class_section_id) == $id)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <label class="form-label">{{ __('reenrollment.admin_note') }}</label>
                                <textarea name="admin_note" class="form-control mb-2" rows="2">{{ old('admin_note', $confirmation->admin_note) }}</textarea>
                                @if($summary['remaining_for_reenroll'] > 0)
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="force_without_fee" value="1" id="force_fee">
                                    <label class="form-check-label small" for="force_fee">{{ __('reenrollment.force_without_fee') }}</label>
                                </div>
                                @endif
                                <button class="btn btn-success btn-rounded w-100 mb-2">
                                    <i class="fa fa-check me-2"></i>{{ __('reenrollment.approve') }}
                                </button>
                            </form>

                            <form method="POST" action="{{ route('reenrollments.keep', $confirmation) }}" class="mb-3">
                                @csrf
                                <input type="hidden" name="admin_note" value="{{ $confirmation->admin_note }}">
                                <button class="btn btn-outline-primary btn-rounded w-100">
                                    <i class="fa fa-clock me-2"></i>{{ __('reenrollment.keep_pending') }}
                                </button>
                            </form>

                            <form method="POST" action="{{ route('reenrollments.reject', $confirmation) }}" onsubmit="return confirm('{{ __('reenrollment.confirm_reject') }}');">
                                @csrf
                                <textarea name="admin_note" class="form-control mb-2" rows="2" placeholder="{{ __('reenrollment.admin_note') }}">{{ old('admin_note') }}</textarea>
                                <button class="btn btn-outline-danger btn-rounded w-100">
                                    <i class="fa fa-times me-2"></i>{{ __('reenrollment.reject') }}
                                </button>
                            </form>
                        @else
                            <div class="alert alert-light border">
                                <i class="fa fa-info-circle me-2"></i>
                                @if($confirmation->status === \App\Models\ReenrollmentConfirmation::STATUS_CONFIRMED)
                                    {{ __('reenrollment.already_confirmed_hint', [
                                        'class' => class_section_label($confirmation->approvedClassSection) ?: '—',
                                        'session' => $confirmation->campaign->toSession->name ?? '—',
                                    ]) }}
                                @else
                                    {{ __('reenrollment.errors.not_in_queue') }}
                                @endif
                            </div>

                            @if(in_array($confirmation->status, [
                                \App\Models\ReenrollmentConfirmation::STATUS_DECLINED,
                                \App\Models\ReenrollmentConfirmation::STATUS_REJECTED,
                                \App\Models\ReenrollmentConfirmation::STATUS_EXPIRED,
                            ], true))
                                <form method="POST" action="{{ route('reenrollments.reopen', $confirmation) }}"
                                      onsubmit="return confirm('{{ __('reenrollment.confirm_reopen') }}');">
                                    @csrf
                                    <textarea name="admin_note" class="form-control mb-2" rows="2" placeholder="{{ __('reenrollment.admin_note') }}"></textarea>
                                    <button class="btn btn-outline-primary btn-rounded w-100">
                                        <i class="fa fa-rotate-left me-2"></i>{{ __('reenrollment.reopen') }}
                                    </button>
                                </form>
                                <small class="text-muted d-block mt-2">{{ __('reenrollment.reopen_hint') }}</small>
                            @endif

                            @if($confirmation->admin_note)
                                <hr>
                                <div class="text-muted small">{{ __('reenrollment.admin_note') }}</div>
                                <div>{{ $confirmation->admin_note }}</div>
                            @endif

                            @if($confirmation->reviewedBy)
                                <div class="small text-muted mt-3">
                                    <i class="fa fa-user-shield me-1"></i>
                                    {{ __('reenrollment.reviewed_by', [
                                        'name' => $confirmation->reviewedBy->name,
                                        'date' => $confirmation->reviewed_at ? localized_date($confirmation->reviewed_at, 'd M Y H:i') : '—',
                                    ]) }}
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
                @endcanany
            </div>
        </div>
    </div>
</div>
@endsection
