@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">

        @php
            $roleName = Auth::user()->roles->first()?->name ?? __('dashboard.default_role');
            $currency = \App\Enums\CurrencySymbol::default();
            $activeInstId = session('active_institution_id');
            $platformInstitution = null;
            if ($activeInstId && $activeInstId !== 'global') {
                $platformInstitution = \App\Models\Institution::find($activeInstId);
            }
            $totalFunds = $pendingFunds + $validatedFunds;
            $validatedPercent = $totalFunds > 0 ? round(($validatedFunds / $totalFunds) * 100) : 0;
        @endphp

        @include('dashboard.partials.welcome-banner', [
            'institution' => $platformInstitution,
            'currentSession' => null,
            'subtitle' => __('dashboard.platform_overview'),
            'showIcon' => false,
        ])

        {{-- KEY STATS --}}
        <div class="row g-3 mb-2">
            <div class="col-xl-3 col-lg-6 col-sm-6 mb-3">
                @include('dashboard.partials.stat-card', [
                    'icon' => 'la la-university', 'tint' => 'primary',
                    'label' => __('dashboard.total_institutions'), 'value' => $totalInstitutions,
                    'hint' => __('dashboard.active_count', ['count' => $activeInstitutionsCount]), 'hintClass' => 'text-tint-primary',
                ])
            </div>
            <div class="col-xl-3 col-lg-6 col-sm-6 mb-3">
                @include('dashboard.partials.stat-card', [
                    'icon' => 'la la-plus-circle', 'tint' => 'success',
                    'label' => __('dashboard.institution_newcomer'), 'value' => $newInstitutionsCount,
                    'hint' => __('dashboard.last_30_days'), 'hintClass' => 'text-tint-success',
                ])
            </div>
            <div class="col-xl-3 col-lg-6 col-sm-6 mb-3">
                @include('dashboard.partials.stat-card', [
                    'icon' => 'la la-users', 'tint' => 'info',
                    'label' => __('dashboard.total_enrollment'), 'value' => number_format($totalStudents),
                    'hint' => __('dashboard.students'),
                ])
            </div>
            <div class="col-xl-3 col-lg-6 col-sm-6 mb-3">
                @include('dashboard.partials.stat-card', [
                    'icon' => 'la la-chalkboard-teacher', 'tint' => 'warning',
                    'label' => __('dashboard.active_personnel'), 'value' => number_format($totalStaff),
                    'hint' => __('dashboard.personnel_platform_wide'),
                ])
            </div>
        </div>

        {{-- FUNDS + SYSTEM STATUS --}}
        <div class="row g-3">
            <div class="col-xl-6 col-lg-12 mb-3">
                <div class="dash-panel h-100">
                    <div class="dash-panel__head">
                        <h4 class="dash-panel__title">{{ __('dashboard.funds_request_subscriptions') }}</h4>
                        <a href="{{ route('subscriptions.index') }}" class="text-tint-primary small">{{ __('dashboard.view_details') }}</a>
                    </div>
                    <div class="dash-panel__body">
                        <div class="row text-center mb-3">
                            <div class="col-6 border-end">
                                <h4 class="text-tint-warning mb-1">{{ $currency }}{{ number_format($pendingFunds, 0) }}</h4>
                                <span class="dash-mini-label">{{ __('dashboard.pending') }}</span>
                            </div>
                            <div class="col-6">
                                <h4 class="text-tint-success mb-1">{{ $currency }}{{ number_format($validatedFunds, 0) }}</h4>
                                <span class="dash-mini-label">{{ __('dashboard.validated') }}</span>
                            </div>
                        </div>
                        <div class="dash-progress">
                            <span style="width: {{ $validatedPercent }}%; background: var(--dash-success);"></span>
                        </div>
                        <small class="dash-mini-label d-block mt-2 text-center">{{ $validatedPercent }}% {{ __('dashboard.validated') }}</small>
                    </div>
                </div>
            </div>

            <div class="col-xl-6 col-lg-12 mb-3">
                <div class="dash-panel h-100">
                    <div class="dash-panel__head">
                        <h4 class="dash-panel__title">{{ __('dashboard.system_status') }}</h4>
                        <a href="{{ route('institutes.index') }}" class="text-tint-primary small">{{ __('dashboard.view_all') }}</a>
                    </div>
                    <div class="dash-panel__body">
                        <div class="d-flex align-items-center mb-3">
                            <span class="dash-stat__icon tint-danger me-3"><i class="la la-exclamation-triangle"></i></span>
                            <div>
                                <h5 class="mb-0">{{ __('dashboard.expired_subscriptions', ['count' => $expiredInstitutions]) }}</h5>
                                <small class="dash-mini-label">{{ __('dashboard.subscriptions_needing_renewal') }}</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="dash-stat__icon tint-info me-3"><i class="la la-eye"></i></span>
                            <div>
                                <h5 class="mb-0">{{ __('dashboard.audit_logs_count', ['count' => $auditLogCount]) }}</h5>
                                <small class="dash-mini-label">{{ __('dashboard.system_activities_24h') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- GROWTH CHART --}}
        <div class="row g-3">
            <div class="col-12 mb-3">
                <div class="dash-panel">
                    <div class="dash-panel__head">
                        <h4 class="dash-panel__title">{{ __('dashboard.student_by_year') }}</h4>
                        <span class="dash-mini-label">{{ __('dashboard.last_12_months') }}</span>
                    </div>
                    <div class="dash-panel__body">
                        <canvas id="studentChart" height="80"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- RECENT INSTITUTIONS --}}
        <div class="row g-3">
            <div class="col-12 mb-3">
                <div class="dash-panel">
                    <div class="dash-panel__head">
                        <h4 class="dash-panel__title">{{ __('dashboard.recent_institutions') }}</h4>
                    </div>
                    <div class="dash-panel__body">
                        <div class="table-responsive">
                            <table class="table table-borderless align-middle mb-0">
                                <thead>
                                    <tr class="dash-mini-label">
                                        <th>{{ __('dashboard.name') }}</th>
                                        <th>{{ __('dashboard.code') }}</th>
                                        <th>{{ __('dashboard.city') }}</th>
                                        <th>{{ __('dashboard.status') }}</th>
                                        <th>{{ __('dashboard.date') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentInstitutions as $inst)
                                    <tr>
                                        <td><strong>{{ $inst->name }}</strong></td>
                                        <td>{{ $inst->code }}</td>
                                        <td>{{ $inst->city }}</td>
                                        <td>
                                            <span class="badge badge-{{ $inst->is_active ? 'success' : 'danger' }}">
                                                {{ $inst->is_active ? __('dashboard.active') : __('dashboard.inactive') }}
                                            </span>
                                        </td>
                                        <td>{{ $inst->created_at->format('d M, Y') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('vendor/chart.js/Chart.bundle.min.js') }}"></script>
<script>
    (function($) {
        "use strict";
        if (typeof Chart === 'undefined') return;
        var el = document.getElementById("studentChart");
        if (!el) return;
        new Chart(el.getContext('2d'), {
            type: 'line',
            data: {
                labels: {!! json_encode($chartLabels) !!},
                datasets: [{
                    label: @json(__('dashboard.students_joined')),
                    data: {!! json_encode($chartValues) !!},
                    borderColor: '#5b53e8',
                    borderWidth: 3,
                    backgroundColor: 'rgba(91, 83, 232, 0.08)',
                    pointBackgroundColor: '#5b53e8',
                    pointRadius: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { display: false },
                scales: { yAxes: [{ ticks: { beginAtZero: true, precision: 0 } }] }
            }
        });
    })(jQuery);
</script>
@endsection
