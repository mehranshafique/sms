@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0 mb-4 p-4 bg-white rounded shadow-sm align-items-center">
            <div class="col-sm-7 p-0">
                <div class="welcome-text">
                    <h4 class="text-primary fw-bold fs-20">{{ __('attendance.staff_analytics_title') }}</h4>
                    <p class="mb-0 text-muted fs-14">{{ __('attendance.staff_analytics_subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-5 p-0 text-sm-end mt-2 mt-sm-0">
                <form method="GET" class="d-inline-flex align-items-center gap-2">
                    <select name="period" class="form-control default-select" onchange="this.form.submit()" style="min-width:160px;">
                        <option value="week" {{ $period == 'week' ? 'selected' : '' }}>{{ __('attendance.this_week') }}</option>
                        <option value="month" {{ $period == 'month' ? 'selected' : '' }}>{{ __('attendance.this_month') }}</option>
                        <option value="quarter" {{ $period == 'quarter' ? 'selected' : '' }}>{{ __('attendance.this_quarter') }}</option>
                        <option value="year" {{ $period == 'year' ? 'selected' : '' }}>{{ __('attendance.this_year') }}</option>
                    </select>
                </form>
                <a href="{{ route('staff-attendance.create') }}" class="btn btn-primary btn-sm ms-2">{{ __('staff.mark_staff_attendance') }}</a>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-3 col-sm-6">
                <div class="card"><div class="card-body">
                    <p class="text-muted mb-1">{{ __('attendance.attendance_rate') }}</p>
                    <h2 class="text-success mb-0">{{ $dashboard['attendance_rate'] }}%</h2>
                </div></div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="card"><div class="card-body">
                    <p class="text-muted mb-1">{{ __('attendance.absence_rate') }}</p>
                    <h2 class="text-danger mb-0">{{ $dashboard['absence_rate'] }}%</h2>
                </div></div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="card"><div class="card-body">
                    <p class="text-muted mb-1">{{ __('attendance.late_rate') }}</p>
                    <h2 class="text-warning mb-0">{{ $dashboard['late_rate'] }}%</h2>
                </div></div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="card"><div class="card-body">
                    <p class="text-muted mb-1">{{ __('attendance.active_staff') }}</p>
                    <h2 class="text-primary mb-0">{{ $dashboard['active_staff'] }}</h2>
                </div></div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header border-0">
                        <h4 class="card-title mb-0">{{ __('attendance.trend_chart') }}</h4>
                    </div>
                    <div class="card-body">
                        <canvas id="staffTrendChart" height="120"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header border-0">
                        <h4 class="card-title mb-0">{{ __('attendance.status_breakdown') }}</h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            @foreach(['present','absent','late','excused','half_day'] as $key)
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span>{{ __('staff.'.$key) }}</span>
                                    <strong>{{ $dashboard['totals'][$key] ?? 0 }}</strong>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-0">
                <h4 class="card-title mb-0">{{ __('attendance.staff_leaderboard') }}</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('staff.full_name') }}</th>
                                <th>{{ __('staff.employee_id') }}</th>
                                <th>{{ __('attendance.days_marked') }}</th>
                                <th>{{ __('attendance.absent') }}</th>
                                <th>{{ __('attendance.late') }}</th>
                                <th>{{ __('attendance.attendance_rate') }}</th>
                                <th class="text-end">{{ __('attendance.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dashboard['leaderboard'] as $row)
                                <tr>
                                    <td class="fw-bold"><a href="{{ route('staff.show', $row['staff_id']) }}" class="text-primary">{{ $row['name'] }}</a></td>
                                    <td><a href="{{ route('staff.show', $row['staff_id']) }}" class="text-primary">{{ $row['employee_id'] }}</a></td>
                                    <td>{{ $row['days_marked'] }}</td>
                                    <td><span class="badge badge-danger">{{ $row['absent'] }}</span></td>
                                    <td><span class="badge badge-warning">{{ $row['late'] }}</span></td>
                                    <td>{{ $row['attendance_rate'] }}%</td>
                                    <td class="text-end">
                                        <a href="{{ route('staff-attendance.analytics.show', $row['staff_id']) }}?period={{ $period }}" class="btn btn-primary btn-xs sharp">
                                            <i class="fa fa-chart-line"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">{{ __('attendance.no_records_found') }}</td>
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
<script src="{{ asset('vendor/chart.js/Chart.bundle.min.js') }}"></script>
<script>
(function() {
    const trend = @json($dashboard['trend']);
    const ctx = document.getElementById('staffTrendChart');
    if (!ctx || typeof Chart === 'undefined') return;

    new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: trend.map(r => r.label),
            datasets: [
                { label: @json(__('attendance.present')), data: trend.map(r => r.present), borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,.12)', fill: true, tension: .3 },
                { label: @json(__('attendance.late')), data: trend.map(r => r.late), borderColor: '#d97706', backgroundColor: 'rgba(217,119,6,.08)', fill: true, tension: .3 },
                { label: @json(__('attendance.absent')), data: trend.map(r => r.absent), borderColor: '#dc2626', backgroundColor: 'rgba(220,38,38,.08)', fill: true, tension: .3 },
            ]
        },
        options: {
            responsive: true,
            legend: { display: true },
            scales: {
                yAxes: [{ ticks: { beginAtZero: true, precision: 0 } }]
            }
        }
    });
})();
</script>
@endsection
