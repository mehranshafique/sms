@extends('layout.layout')

@section('content')
<style>
    .ap-hero {
        border-radius: 18px;
        background: linear-gradient(120deg, #0b2a6b 0%, #13386e 50%, #2563eb 100%);
        position: relative;
        overflow: hidden;
    }
    .ap-hero::after {
        content: "";
        position: absolute;
        right: -40px;
        top: -60px;
        width: 220px;
        height: 220px;
        background: rgba(255,255,255,.08);
        border-radius: 50%;
    }
    .ap-hero__icon { font-size: 3.2rem; color: rgba(255,255,255,.35); }
    .ap-stat {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 14px;
        padding: 18px;
        height: 100%;
    }
    .ap-stat__icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        color: #fff;
        margin-bottom: 10px;
    }
    [data-theme-version="dark"] .ap-stat { background: #1e2746; border-color: #2b365c; color: #e8ebf5; }
    [data-theme-version="dark"] .ap-stat .text-muted { color: #9ca3af !important; }
    [data-theme-version="dark"] .ap-stat h4 { color: #fff !important; }
</style>
<div class="content-body">
    <div class="container-fluid">

        <div class="row mb-4">
            <div class="col-12">
                <div class="ap-hero shadow-sm">
                    <div class="d-flex flex-wrap justify-content-between align-items-center p-4" style="position:relative; z-index:1;">
                        <div>
                            <h3 class="text-white fw-bold mb-1">{{ __('agent.page_title') }}</h3>
                            <p class="mb-0 text-white opacity-75">{{ __('agent.subtitle') }}</p>
                        </div>
                        <div class="d-flex align-items-center gap-2 gap-md-3">
                            <i class="la la-hand-holding-usd ap-hero__icon d-none d-md-block"></i>
                            @if($isSuperAdmin)
                                <button type="button" class="btn btn-light fw-bold text-primary" data-bs-toggle="modal" data-bs-target="#periodModal">
                                    <i class="la la-calendar-plus me-1"></i> {{ __('agent.add_period') }}
                                </button>
                                <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#paymentModal">
                                    <i class="la la-plus me-1"></i> {{ __('agent.add_payment') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-sm-4 col-6">
                <div class="ap-stat">
                    <div class="ap-stat__icon" style="background:linear-gradient(135deg,#059669,#047857);"><i class="la la-check-circle"></i></div>
                    <div class="text-muted small">{{ __('agent.paid_count') }}</div>
                    <h4 class="fw-bold mb-0">{{ $paidCount }}</h4>
                </div>
            </div>
            <div class="col-sm-4 col-6">
                <div class="ap-stat">
                    <div class="ap-stat__icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="la la-clock"></i></div>
                    <div class="text-muted small">{{ __('agent.unpaid_count') }}</div>
                    <h4 class="fw-bold mb-0">{{ $unpaidCount }}</h4>
                </div>
            </div>
            <div class="col-sm-4 col-6">
                <div class="ap-stat">
                    <div class="ap-stat__icon" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);"><i class="la la-coins"></i></div>
                    <div class="text-muted small">{{ __('agent.total_ytd') }}</div>
                    <h4 class="fw-bold mb-0">{{ number_format($totalYtd, 2) }}</h4>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0" style="border-radius:15px;">
                    <div class="card-header border-0 pb-0 pt-4 px-4 bg-transparent">
                        <h4 class="card-title mb-0 fw-bold fs-18">{{ __('agent.payment_list') }}</h4>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div class="table-responsive digitex-dt-wrap">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>{{ __('agent.col_agent') }}</th>
                                        <th>{{ __('agent.col_period') }}</th>
                                        <th>{{ __('agent.col_amount') }}</th>
                                        <th>{{ __('agent.col_status') }}</th>
                                        <th class="text-end">{{ __('agent.col_actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse($payments as $p)
                                    <tr>
                                        <td class="fw-bold">{{ $p->agent?->name ?? '—' }}</td>
                                        <td>{{ $p->period?->label ?? '—' }}</td>
                                        <td>{{ number_format($p->amount, 2) }}</td>
                                        <td>
                                            <span class="badge badge-{{ $p->status === 'paid' ? 'success' : 'warning' }} light">
                                                {{ __('agent.status_' . $p->status) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            @if($isSuperAdmin && $p->status === 'unpaid')
                                                <form method="POST" action="{{ route('agent-payments.mark-paid', $p) }}" class="d-inline" onsubmit="return confirm(@json(__('agent.confirm_mark_paid')));">
                                                    @csrf @method('PATCH')
                                                    <button class="btn btn-success btn-xs shadow-sm">
                                                        <i class="la la-check"></i> {{ __('agent.mark_paid') }}
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-5">
                                            <i class="la la-hand-holding-usd fs-1 d-block mb-2 opacity-50"></i>
                                            {{ __('agent.empty') }}
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($payments->hasPages())
                            <div class="mt-3">{{ $payments->links() }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($isSuperAdmin)
<div class="modal fade" id="periodModal">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('agent-payments.periods.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">{{ __('agent.add_period') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">{{ __('agent.period_label') }} <span class="text-danger">*</span></label>
                    <input type="text" name="label" class="form-control" placeholder="{{ __('agent.period_label_placeholder') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">{{ __('agent.start_date') }} <span class="text-danger">*</span></label>
                    <input type="text" name="start_date" class="form-control datepicker-modal" placeholder="YYYY-MM-DD" autocomplete="off" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">{{ __('agent.end_date') }} <span class="text-danger">*</span></label>
                    <input type="text" name="end_date" class="form-control datepicker-modal" placeholder="YYYY-MM-DD" autocomplete="off" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('agent.cancel') }}</button>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i> {{ __('agent.save') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="paymentModal">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('agent-payments.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">{{ __('agent.add_payment') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">{{ __('agent.col_agent') }} <span class="text-danger">*</span></label>
                    <select name="user_id" id="paymentAgentSelect" class="form-control modal-select" required>
                        <option value="">{{ __('agent.select_agent') }}</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">{{ __('agent.col_period') }} <span class="text-danger">*</span></label>
                    <select name="agent_payment_period_id" id="paymentPeriodSelect" class="form-control modal-select" required>
                        <option value="">{{ __('agent.select_period') }}</option>
                        @foreach($periods as $period)
                            <option value="{{ $period->id }}">{{ $period->label }}</option>
                        @endforeach
                    </select>
                    @if($periods->isEmpty())
                        <small class="text-warning d-block mt-1">{{ __('agent.no_periods_hint') }}</small>
                    @endif
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">{{ __('agent.col_amount') }} <span class="text-danger">*</span></label>
                    <input type="number" name="amount" class="form-control" min="0" step="0.01" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">{{ __('agent.notes') }}</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('agent.cancel') }}</button>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i> {{ __('agent.save') }}</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@section('js')
<script>
const AGENT_PERIODS = @json($periods->map(fn ($p) => ['id' => $p->id, 'label' => $p->label])->values());

function initModalDatepickers($modal) {
    if (!jQuery().bootstrapMaterialDatePicker) {
        return;
    }
    $modal.find('.datepicker-modal').each(function () {
        const $el = $(this);
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

function initModalSelects($modal) {
    if (typeof jQuery.fn.selectpicker === 'undefined') {
        return;
    }
    $modal.find('.modal-select').each(function () {
        const $el = $(this);
        if ($el.data('selectpicker')) {
            $el.selectpicker('destroy');
        }
        $el.selectpicker({
            liveSearch: true,
            size: 10,
            width: '100%',
            container: 'body',
            dropupAuto: false
        });
    });
}

function refreshPeriodOptions() {
    const $sel = $('#paymentPeriodSelect');
    if (!$sel.length) {
        return;
    }
    const current = $sel.val();
    $sel.find('option:not(:first)').remove();
    AGENT_PERIODS.forEach(function (period) {
        $sel.append($('<option></option>').val(period.id).text(period.label));
    });
    if (current) {
        $sel.val(current);
    }
    if ($sel.data('selectpicker')) {
        $sel.selectpicker('refresh');
    }
}

$(function () {
    $('#periodModal').on('shown.bs.modal', function () {
        initModalDatepickers($(this));
    });

    $('#paymentModal').on('shown.bs.modal', function () {
        refreshPeriodOptions();
        initModalSelects($(this));
    });
});
</script>
@endsection
