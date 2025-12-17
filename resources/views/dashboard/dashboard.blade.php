@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        {{-- 1. Welcome Banner --}}
        <div class="row">
            <div class="col-xl-12">
                <div class="card bg-primary">
                    <div class="card-header border-0 pb-0">
                        <h3 class="card-title text-white">
                            {{ __('dashboard.welcome_back') }}, {{ Auth::user()->name }}!
                        </h3>
                    </div>
                    <div class="card-body text-white pt-2">
                        <p class="mb-0 fs-14">
                            @if(isset($currentSession))
                                {{ __('dashboard.current_session') }}: <strong>{{ $currentSession->name }}</strong>
                                <span class="opacity-75 ms-2">
                                    ({{ $currentSession->start_date ? $currentSession->start_date->format('M Y') : '' }} - 
                                     {{ $currentSession->end_date ? $currentSession->end_date->format('M Y') : '' }})
                                </span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. Stats Cards Row --}}
        <div class="row">
            {{-- Students --}}
            <div class="col-xl-3 col-xxl-3 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-primary text-primary">
                                <i class="la la-users"></i>
                            </span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('dashboard.total_students') }}</p>
                                <h4 class="mb-0">{{ $totalStudents }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Staff --}}
            <div class="col-xl-3 col-xxl-3 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-warning text-warning">
                                <i class="la la-chalkboard-teacher"></i>
                            </span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('dashboard.total_staff') }}</p>
                                <h4 class="mb-0">{{ $totalStaff }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Campuses --}}
            <div class="col-xl-3 col-xxl-3 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-danger text-danger">
                                <i class="la la-building"></i>
                            </span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('dashboard.campuses') }}</p>
                                <h4 class="mb-0">{{ $totalCampuses }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Institutes --}}
            <div class="col-xl-3 col-xxl-3 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-success text-success">
                                <i class="la la-university"></i>
                            </span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('dashboard.institutes') }}</p>
                                <h4 class="mb-0">{{ $totalInstitutes }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. Charts & Tables Row --}}
        <div class="row">
            {{-- Registration Chart --}}
            <div class="col-xl-6 col-xxl-6 col-lg-12 col-md-12">
                <div class="card">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title">{{ __('dashboard.new_student_registrations') }}</h4>
                    </div>
                    <div class="card-body">
                        <canvas id="studentChart" height="150"></canvas>
                    </div>
                </div>
            </div>

            {{-- Recent Students Table --}}
            <div class="col-xl-6 col-xxl-6 col-lg-12 col-md-12">
                <div class="card">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title">{{ __('dashboard.recent_students') }}</h4>
                    </div>
                    <div class="card-body p-0"> 
                        <div class="table-responsive">
                            <table class="table table-responsive-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ __('dashboard.name') }}</th>
                                        <th>{{ __('dashboard.institute') }}</th>
                                        <th>{{ __('dashboard.status') }}</th>
                                        <th class="text-end">{{ __('dashboard.date') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentStudents as $student)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" style="width:35px; height:35px; font-weight:bold;">
                                                        {{ substr($student->first_name, 0, 1) }}
                                                    </div>
                                                    {{ $student->first_name }} {{ $student->last_name }}
                                                </div>
                                            </td>
                                            <td>{{ $student->institute->name ?? 'N/A' }}</td>
                                            <td>
                                                @if($student->status == 'active')
                                                    <span class="badge light badge-success">Active</span>
                                                @else
                                                    <span class="badge light badge-warning">{{ ucfirst($student->status) }}</span>
                                                @endif
                                            </td>
                                            <td class="text-end text-muted">{{ $student->created_at->format('d M, Y') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">{{ __('dashboard.no_recent_students') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer border-0 pt-0 text-center">
                        <a href="{{ route('students.index') }}" class="btn-link">{{ __('dashboard.view_all_students') }} <i class="fa fa-angle-right ms-2"></i></a>
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
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('studentChart').getContext('2d');
        
        // Data from Controller
        const labels = @json($chartLabels);
        const data = @json($chartValues);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: '{{ __("dashboard.new_students") }}',
                    data: data,
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderColor: '#667eea',
                    borderWidth: 2,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#667eea',
                    pointHoverBackgroundColor: '#667eea',
                    pointHoverBorderColor: '#ffffff',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: "rgba(0, 0, 0, 0.05)",
                            drawBorder: false
                        },
                        ticks: { stepSize: 1 }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false 
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                }
            }
        });
    });
</script>
@endsection