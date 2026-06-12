@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">

        @php
            $roleName = Auth::user()->roles->first()?->name ?? __('dashboard.default_role');
            $currency = \App\Enums\CurrencySymbol::default();
            $collectedPercent = $totalRevenue > 0 ? round(($collectedRevenue / $totalRevenue) * 100) : 0;
        @endphp

        @include('dashboard.partials.welcome-banner', [
            'institution' => null,
            'currentSession' => null,
            'subtitle' => __('dashboard.my_schools_overview') . ' — ' . __('dashboard.global_dashboard'),
            'showIcon' => false,
        ])

        <div class="row mb-3">
            <div class="col-sm-12 d-flex justify-content-sm-end">
                 @can('institution.create')
                 <a href="{{ route('institutes.create') }}" class="btn btn-primary btn-sm btn-rounded"><i class="fa fa-plus"></i> {{ __('dashboard.add_new_school') }}</a>
                 @endcan
            </div>
        </div>

        {{-- KEY STATS --}}
        <div class="row g-3 mb-2">
            <div class="col-xl-3 col-lg-6 col-sm-6 mb-3">
                @include('dashboard.partials.stat-card', [
                    'icon' => 'la la-university', 'tint' => 'primary',
                    'label' => __('dashboard.my_schools'), 'value' => $totalSchools,
                    'hint' => __('dashboard.active_count', ['count' => $activeSchools]), 'hintClass' => 'text-tint-primary',
                ])
            </div>
            <div class="col-xl-3 col-lg-6 col-sm-6 mb-3">
                @include('dashboard.partials.stat-card', [
                    'icon' => 'la la-users', 'tint' => 'info',
                    'label' => __('dashboard.total_students'), 'value' => number_format($totalStudents),
                    'hint' => __('dashboard.students'),
                ])
            </div>
            <div class="col-xl-3 col-lg-6 col-sm-6 mb-3">
                @include('dashboard.partials.stat-card', [
                    'icon' => 'la la-chalkboard-teacher', 'tint' => 'warning',
                    'label' => __('dashboard.total_staff'), 'value' => number_format($totalStaff),
                    'hint' => __('dashboard.personnel'),
                ])
            </div>
            <div class="col-xl-3 col-lg-6 col-sm-6 mb-3">
                @include('dashboard.partials.stat-card', [
                    'icon' => 'la la-check-circle', 'tint' => 'success',
                    'label' => __('dashboard.active_schools'), 'value' => $activeSchools,
                    'hint' => __('dashboard.of_total_schools', ['total' => $totalSchools]), 'hintClass' => 'text-tint-success',
                ])
            </div>
        </div>

        {{-- FINANCIALS + ACTIVITY --}}
        <div class="row g-3">
            <div class="col-xl-8 col-lg-12 mb-3">
                <div class="dash-panel h-100">
                    <div class="dash-panel__head">
                        <h4 class="dash-panel__title">{{ __('dashboard.financial_overview') }}</h4>
                        <span class="badge rounded-pill" style="background: rgba(43,182,115,.12); color: var(--dash-success);">{{ $collectedPercent }}% {{ __('dashboard.collected') }}</span>
                    </div>
                    <div class="dash-panel__body">
                        <div class="row text-center mb-3">
                            <div class="col-4 border-end">
                                <h4 class="text-tint-primary mb-1">{{ $currency }}{{ number_format($totalRevenue, 0) }}</h4>
                                <span class="dash-mini-label">{{ __('dashboard.total_invoiced') }}</span>
                            </div>
                            <div class="col-4 border-end">
                                <h4 class="text-tint-success mb-1">{{ $currency }}{{ number_format($collectedRevenue, 0) }}</h4>
                                <span class="dash-mini-label">{{ __('dashboard.collected') }}</span>
                            </div>
                            <div class="col-4">
                                <h4 class="text-tint-warning mb-1">{{ $currency }}{{ number_format($pendingRevenue, 0) }}</h4>
                                <span class="dash-mini-label">{{ __('dashboard.pending') }}</span>
                            </div>
                        </div>
                        <div class="dash-progress">
                            <span style="width: {{ $collectedPercent }}%; background: var(--dash-success);"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-12 mb-3">
                <div class="dash-panel h-100">
                    <div class="dash-panel__head">
                        <h4 class="dash-panel__title">{{ __('dashboard.recent_activity') }}</h4>
                    </div>
                    <div class="dash-panel__body d-flex flex-column justify-content-center">
                        <div class="d-flex align-items-center">
                            <span class="dash-stat__icon tint-primary me-3"><i class="la la-bell"></i></span>
                            <div>
                                <h5 class="mb-0">{{ $auditLogCount }} {{ __('dashboard.actions') }}</h5>
                                <p class="mb-0 dash-mini-label">{{ __('dashboard.system_actions_desc') }}</p>
                            </div>
                        </div>
                        <div class="mt-3 text-end">
                            <a href="{{ route('audit-logs.index') }}" class="text-tint-primary small">{{ __('dashboard.view_logs') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- INSTITUTIONS LIST --}}
        <div class="row g-3">
            <div class="col-12 mb-3">
                <div class="dash-panel">
                    <div class="dash-panel__head">
                        <h4 class="dash-panel__title">{{ __('dashboard.my_institutions_list') }}</h4>
                    </div>
                    <div class="dash-panel__body">
                        <div class="table-responsive">
                            <table class="table table-borderless align-middle table-hover mb-0">
                                <thead>
                                    <tr class="dash-mini-label">
                                        <th>{{ __('dashboard.name') }}</th>
                                        <th>{{ __('dashboard.code') }}</th>
                                        <th>{{ __('dashboard.city') }}</th>
                                        <th>{{ __('dashboard.students') }}</th>
                                        <th>{{ __('dashboard.staff') }}</th>
                                        <th>{{ __('dashboard.status') }}</th>
                                        <th class="text-end">{{ __('dashboard.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($institutes as $inst)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($inst->logo)
                                                    <img src="{{ asset('storage/'.$inst->logo) }}" class="rounded-circle me-2" width="35" height="35" style="object-fit:cover;">
                                                @else
                                                    <div class="rounded-circle tint-primary d-flex align-items-center justify-content-center me-2" style="width:35px; height:35px;">
                                                        {{ substr($inst->name, 0, 1) }}
                                                    </div>
                                                @endif
                                                <div class="d-flex flex-column">
                                                    <strong>{{ $inst->name }}</strong>
                                                    <span class="dash-mini-label">{{ $inst->type }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $inst->code }}</td>
                                        <td>{{ $inst->city }}</td>
                                        <td><span class="badge badge-sm light badge-info">{{ $inst->student_count }}</span></td>
                                        <td><span class="badge badge-sm light badge-secondary">{{ $inst->staff_count }}</span></td>
                                        <td>
                                            <span class="badge badge-xs light badge-{{ $inst->is_active ? 'success' : 'danger' }}">
                                                {{ $inst->is_active ? __('dashboard.active') : __('dashboard.inactive') }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('institution.switch', $inst->id) }}" class="btn btn-primary btn-xs shadow sharp me-1" title="{{ __('dashboard.switch_dashboard') }}">
                                                <i class="fa fa-external-link-alt"></i>
                                            </a>
                                            <a href="{{ route('institutes.edit', $inst->id) }}" class="btn btn-warning btn-xs shadow sharp" title="{{ __('dashboard.edit') }}">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                        </td>
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