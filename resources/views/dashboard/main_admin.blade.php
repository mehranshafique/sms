@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('dashboard.main_admin_title') }}</h4>
                    <p class="mb-0">{{ __('dashboard.platform_overview') }}</p>
                </div>
            </div>
        </div>

        {{-- ROW 1: Institution Stats --}}
        <div class="row">
            {{-- Total Institutions --}}
            <div class="col-xl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card bg-primary text-white">
                    <div class="card-body p-4">
                        <div class="media">
                            <span class="me-3">
                                <i class="la la-university"></i>
                            </span>
                            <div class="media-body text-white">
                                <p class="mb-1 text-white opacity-75">{{ __('dashboard.total_institutions') }}</p>
                                <h3 class="text-white">{{ $totalInstitutions }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Institution Newcomer --}}
            <div class="col-xl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card bg-warning text-white">
                    <div class="card-body p-4">
                        <div class="media">
                            <span class="me-3">
                                <i class="la la-plus-circle"></i>
                            </span>
                            <div class="media-body text-white">
                                <p class="mb-1 text-white opacity-75">{{ __('dashboard.institution_newcomer') }}</p>
                                <h3 class="text-white">{{ $newInstitutionsCount }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Active Institutions --}}
            <div class="col-xl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card bg-success text-white">
                    <div class="card-body p-4">
                        <div class="media">
                            <span class="me-3">
                                <i class="la la-check-circle"></i>
                            </span>
                            <div class="media-body text-white">
                                <p class="mb-1 text-white opacity-75">{{ __('dashboard.active_institutions') }}</p>
                                <h3 class="text-white">{{ $activeInstitutionsCount }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Total Students --}}
            <div class="col-xl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card bg-info text-white">
                    <div class="card-body p-4">
                        <div class="media">
                            <span class="me-3">
                                <i class="la la-users"></i>
                            </span>
                            <div class="media-body text-white">
                                <p class="mb-1 text-white opacity-75">{{ __('dashboard.total_enrollment') }}</p>
                                <h3 class="text-white">{{ $totalStudents }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ROW 2: Financial & Staff --}}
        <div class="row">
            {{-- Funds Request (Platform Finance) --}}
            <div class="col-xl-6 col-lg-12">
                <div class="card">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title">{{ __('dashboard.funds_request') }} (Subscriptions)</h4>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <h4 class="text-warning mb-2">${{ number_format($pendingFunds, 2) }}</h4>
                                <span class="text-muted">{{ __('dashboard.pending') }}</span>
                            </div>
                            <div class="col-6">
                                <h4 class="text-success mb-2">${{ number_format($validatedFunds, 2) }}</h4>
                                <span class="text-muted">{{ __('dashboard.validated') }}</span>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('subscriptions.index') }}" class="btn btn-outline-primary btn-sm w-100">{{ __('dashboard.view_details') }}</a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Staff Count --}}
            <div class="col-xl-6 col-lg-12">
                <div class="card">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title">{{ __('dashboard.personnel') }} (Platform-wide)</h4>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <div class="text-center">
                            <h2 class="fs-36 text-primary">{{ $totalStaff }}</h2>
                            <span class="fs-14">Active Personnel</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ROW 3: Charts & Modules --}}
        <div class="row">
            {{-- Chart --}}
            <div class="col-xl-8 col-lg-8">
                <div class="card">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title">{{ __('dashboard.student_by_year') }}</h4>
                    </div>
                    <div class="card-body">
                        <canvas id="studentChart" height="100"></canvas>
                    </div>
                </div>
            </div>

            {{-- Activity & Alerts --}}
            <div class="col-xl-4 col-lg-4">
                <div class="card">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title">System Status</h4>
                    </div>
                    <div class="card-body">
                        <div class="widget-media">
                            <ul class="timeline">
                                <li>
                                    <div class="timeline-panel">
                                        <div class="media me-2">
                                            <span class="head-officer-icon bgl-danger text-danger">
                                                <i class="la la-exclamation-triangle"></i>
                                            </span>
                                        </div>
                                        <div class="media-body">
                                            <h5 class="mb-1">{{ $expiredInstitutions }} Expired</h5>
                                            <small class="d-block">Subscriptions needing renewal</small>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <div class="timeline-panel">
                                        <div class="media me-2">
                                            <span class="head-officer-icon bgl-info text-info">
                                                <i class="la la-eye"></i>
                                            </span>
                                        </div>
                                        <div class="media-body">
                                            <h5 class="mb-1">{{ $auditLogCount }} Logs</h5>
                                            <small class="d-block">System activities (24h)</small>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <a href="{{ route('institutes.index') }}" class="btn btn-block btn-link m-t-15">{{ __('dashboard.view_all') }}</a>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Recent Institutions Table --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title">Recent Institutions</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-responsive-sm">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Code</th>
                                        <th>City</th>
                                        <th>Status</th>
                                        <th>Date</th>
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
                                                {{ $inst->is_active ? 'Active' : 'Inactive' }}
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
        var ctx = document.getElementById("studentChart").getContext('2d');
        var myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($chartLabels) !!},
                datasets: [{
                    label: 'Students Joined',
                    data: {!! json_encode($chartValues) !!},
                    borderColor: '#4caf50',
                    borderWidth: 2,
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    pointBackgroundColor: '#4caf50',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        ticks: { beginAtZero: true }
                    }]
                }
            }
        });
    })(jQuery);
</script>
@endsection