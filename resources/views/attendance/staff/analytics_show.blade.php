@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0 mb-4 pb-3 border-bottom">
            <div class="col-sm-6 p-md-0 d-flex align-items-center">
                <a href="{{ route('staff-attendance.analytics') }}" class="btn btn-outline-primary btn-sm me-3 shadow-sm">
                    <i class="fa fa-arrow-left me-1"></i> {{ __('attendance.back_to_list') }}
                </a>
                <div class="welcome-text">
                    <h4>{{ $staff->user->name ?? $staff->employee_id }} — {{ __('attendance.staff_analytics_title') }}</h4>
                    <p class="mb-0 text-muted">{{ $staff->employee_id }} · {{ $staff->designation }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <form method="GET">
                    <select name="period" class="form-control default-select" onchange="this.form.submit()" style="max-width: 180px;">
                        <option value="week" {{ $period == 'week' ? 'selected' : '' }}>{{ __('attendance.this_week') }}</option>
                        <option value="month" {{ $period == 'month' ? 'selected' : '' }}>{{ __('attendance.this_month') }}</option>
                        <option value="quarter" {{ $period == 'quarter' ? 'selected' : '' }}>{{ __('attendance.this_quarter') }}</option>
                        <option value="year" {{ $period == 'year' ? 'selected' : '' }}>{{ __('attendance.this_year') }}</option>
                    </select>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-4 col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <p class="text-muted mb-2">{{ __('attendance.average_arrival_time') }}</p>
                        <h2 class="text-primary fw-bold">{{ $stats['current_avg_time'] }}</h2>
                        <span class="badge badge-light text-dark mt-2 d-block text-start" style="white-space:normal;font-size:13px;">
                            {{ $stats['arrival_insight'] }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <p class="text-muted mb-2">{{ __('attendance.punctuality_score') }}</p>
                        <h2 class="text-success fw-bold">{{ $stats['current_punctuality'] }}%</h2>
                        <span class="badge badge-light text-dark mt-2 d-block text-start" style="white-space:normal;font-size:13px;">
                            {{ $stats['punctuality_insight'] }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <p class="text-muted mb-2">{{ __('attendance.attendance_rate') }}</p>
                        <h2 class="text-info fw-bold">{{ $stats['attendance_rate'] }}%</h2>
                        <div class="progress mt-3" style="height:6px;">
                            <div class="progress-bar bg-info" style="width: {{ $stats['attendance_rate'] }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><h4 class="card-title mb-0">{{ __('attendance.status_breakdown') }}</h4></div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            @foreach(['present','absent','late','excused','half_day'] as $key)
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span>{{ __('staff.'.$key) }}</span>
                                    <strong>{{ $stats['totals'][$key] ?? 0 }}</strong>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header"><h4 class="card-title mb-0">{{ __('attendance.detailed_logs') }}</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="bg-light">
                                    <tr>
                                        <th>{{ __('attendance.date') }}</th>
                                        <th>{{ __('attendance.status') }}</th>
                                        <th>{{ __('staff.check_in') }}</th>
                                        <th>{{ __('staff.check_out') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($stats['records'] as $log)
                                        <tr>
                                            <td class="fw-bold">{{ \Carbon\Carbon::parse($log->attendance_date)->format('D, d M Y') }}</td>
                                            <td>
                                                @if($log->status == 'present') <span class="badge badge-success">{{ __('staff.present') }}</span>
                                                @elseif($log->status == 'late') <span class="badge badge-warning">{{ __('staff.late') }}</span>
                                                @elseif($log->status == 'excused') <span class="badge badge-info">{{ __('staff.excused') }}</span>
                                                @elseif($log->status == 'half_day') <span class="badge badge-secondary">{{ __('staff.half_day') }}</span>
                                                @else <span class="badge badge-danger">{{ __('staff.absent') }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $log->check_in ? \Carbon\Carbon::parse($log->check_in)->format('H:i') : '—' }}</td>
                                            <td>{{ $log->check_out ? \Carbon\Carbon::parse($log->check_out)->format('H:i') : '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-4">{{ __('attendance.no_records_found') }}</td></tr>
                                    @endforelse
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
