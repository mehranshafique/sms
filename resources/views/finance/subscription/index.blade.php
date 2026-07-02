@extends('layout.layout')

@section('content')
<style>
    .sub-hero { border-radius:18px; background:linear-gradient(120deg,#0b2a6b 0%,#13386e 50%,#2563eb 100%); position:relative; overflow:hidden; }
    .sub-stat { background:#fff; border:1px solid #eef0f4; border-radius:14px; padding:18px; height:100%; }
    .sub-stat__icon { width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; color:#fff; margin-bottom:10px; }
    .sub-card { background:#fff; border:1px solid #eef0f4; border-radius:16px; overflow:hidden; }
    .sub-table thead th { font-size:.78rem; text-transform:uppercase; letter-spacing:.04em; color:#6b7280; border-bottom:1px solid #eef0f4; background:#fafbfc; }
    .sub-table tbody tr:hover { background:#f8fafc; }
    .sub-plan-badge { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px; font-size:.78rem; font-weight:600; background:#eff6ff; color:#1d4ed8; }
    .sub-days { display:inline-flex; align-items:center; gap:4px; padding:4px 10px; border-radius:999px; font-size:.78rem; font-weight:600; }
    .sub-days.ok { background:#ecfdf5; color:#059669; }
    .sub-days.warn { background:#fffbeb; color:#d97706; }
    .sub-days.expired { background:#fef2f2; color:#dc2626; }
    [data-theme-version="dark"] .sub-stat, [data-theme-version="dark"] .sub-card { background:#1e2746; border-color:#2b365c; color:#e8ebf5; }
    [data-theme-version="dark"] .sub-table thead th { background:#243054 !important; color:#e8ebf5 !important; border-color:#2b365c !important; }
    [data-theme-version="dark"] .sub-stat h4 { color: #fff !important; }
    [data-theme-version="dark"] .sub-table tbody tr:hover { background: rgba(255,255,255,.04); }
</style>
<div class="content-body">
    <div class="container-fluid">

        <div class="row mb-4">
            <div class="col-12">
                <div class="sub-hero shadow-sm">
                    <div class="d-flex flex-wrap justify-content-between align-items-center p-4" style="position:relative; z-index:1;">
                        <div>
                            <h3 class="text-white fw-bold mb-1">{{ __('subscription.page_title') }}</h3>
                            <p class="mb-0 text-white opacity-75">{{ __('subscription.manage_subscriptions') }}</p>
                        </div>
                        <a href="{{ route('subscriptions.create') }}" class="btn btn-light btn-rounded fw-semibold px-4">
                            <i class="fa fa-plus me-2"></i> {{ __('subscription.create_subscription') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="sub-stat">
                    <div class="sub-stat__icon" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);"><i class="la la-list"></i></div>
                    <div class="text-muted small">{{ __('subscription.total_subscriptions') }}</div>
                    <h4 class="fw-bold mb-0">{{ $stats['total'] ?? 0 }}</h4>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="sub-stat">
                    <div class="sub-stat__icon" style="background:linear-gradient(135deg,#059669,#047857);"><i class="la la-check-circle"></i></div>
                    <div class="text-muted small">{{ __('subscription.active') }}</div>
                    <h4 class="fw-bold mb-0">{{ $stats['active'] ?? 0 }}</h4>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="sub-stat">
                    <div class="sub-stat__icon" style="background:linear-gradient(135deg,#d97706,#b45309);"><i class="la la-clock"></i></div>
                    <div class="text-muted small">{{ __('subscription.expiring_soon') }}</div>
                    <h4 class="fw-bold mb-0">{{ $stats['expiring'] ?? 0 }}</h4>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="sub-stat">
                    <div class="sub-stat__icon" style="background:linear-gradient(135deg,#dc2626,#b91c1c);"><i class="la la-times-circle"></i></div>
                    <div class="text-muted small">{{ __('subscription.expired') }}</div>
                    <h4 class="fw-bold mb-0">{{ $stats['expired'] ?? 0 }}</h4>
                </div>
            </div>
        </div>

        <div class="sub-card shadow-sm">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="la la-building text-primary"></i> {{ __('subscription.subscription_list') }}</h6>
            </div>
            <div class="table-responsive">
                <table class="table sub-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('subscription.institution') }}</th>
                            <th>{{ __('subscription.plan') }}</th>
                            <th>{{ __('subscription.start_date') }}</th>
                            <th>{{ __('subscription.end_date') }}</th>
                            <th>{{ __('subscription.days_left') }}</th>
                            <th>{{ __('subscription.status') }}</th>
                            <th class="text-end">{{ __('subscription.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subscriptions as $sub)
                            @php
                                $displayStatus = $sub->displayStatus();
                                $days = $sub->daysLeft();
                                $daysClass = $sub->isExpired() ? 'expired' : ($days <= 30 ? 'warn' : 'ok');
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $sub->institution->name ?? '—' }}</div>
                                    <small class="text-muted">{{ $sub->institution->code ?? '' }}</small>
                                </td>
                                <td><span class="sub-plan-badge"><i class="la la-gem"></i> {{ $sub->package->name ?? 'Custom' }}</span></td>
                                <td>{{ $sub->start_date->format('d M, Y') }}</td>
                                <td>{{ $sub->end_date->format('d M, Y') }}</td>
                                <td>
                                    <span class="sub-days {{ $daysClass }}">
                                        @if($sub->isExpired())
                                            {{ __('subscription.expired') }}
                                        @else
                                            {{ $days }} {{ __('subscription.days') }}
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    @if($displayStatus === 'active')
                                        <span class="badge bg-success">{{ __('subscription.active') }}</span>
                                    @elseif($displayStatus === 'expired')
                                        <span class="badge bg-danger">{{ __('subscription.expired') }}</span>
                                    @elseif($displayStatus === 'cancelled')
                                        <span class="badge bg-secondary">{{ __('subscription.cancelled') }}</span>
                                    @else
                                        <span class="badge bg-warning text-dark">{{ ucfirst(str_replace('_', ' ', $displayStatus)) }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="{{ route('subscriptions.edit', $sub->id) }}" class="btn btn-primary shadow btn-xs sharp" title="{{ __('subscription.edit_subscription') }}">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                        <form action="{{ route('subscriptions.destroy', $sub->id) }}" method="POST" class="d-inline sub-delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger shadow btn-xs sharp" title="{{ __('subscription.delete_subscription') }}">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-5">{{ __('subscription.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.sub-delete-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            Swal.fire({
                title: @json(__('subscription.delete_confirm_title')),
                text: @json(__('subscription.delete_confirm_text')),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: @json(__('subscription.delete_confirm_yes')),
                cancelButtonText: @json(__('subscription.cancel'))
            }).then(function (result) {
                if (result.isConfirmed) form.submit();
            });
        });
    });
});
</script>
@endsection
