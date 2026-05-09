@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        <div class="row page-titles mx-0 mb-4 pb-3 border-bottom">
            <div class="col-sm-6 p-md-0 d-flex align-items-center">
                @if(!auth()->user()->hasRole('Student'))
                    <a href="{{ route('attendance.analytics.index') }}" class="btn btn-outline-primary btn-sm me-3 shadow-sm"><i class="fa fa-arrow-left me-1"></i> {{ __('attendance.back_to_list') }}</a>
                @endif
                <div class="welcome-text">
                    <h4>{{ $student->full_name }} - {{ __('attendance.analytics_title') }}</h4>
                    <p class="mb-0 text-muted">{{ __('attendance.comparative_report') }}: {{ __('attendance.period_' . $period) }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <form method="GET" class="d-flex align-items-center">
                    <select name="period" class="form-control default-select me-2" onchange="this.form.submit()">
                        <option value="week" {{ $period == 'week' ? 'selected' : '' }}>{{ __('attendance.this_week') }}</option>
                        <option value="month" {{ $period == 'month' ? 'selected' : '' }}>{{ __('attendance.this_month') }}</option>
                        <option value="quarter" {{ $period == 'quarter' ? 'selected' : '' }}>{{ __('attendance.this_quarter') }}</option>
                        <option value="semester" {{ $period == 'semester' ? 'selected' : '' }}>{{ __('attendance.this_semester') }}</option>
                        <option value="year" {{ $period == 'year' ? 'selected' : '' }}>{{ __('attendance.this_year') }}</option>
                    </select>
                </form>
            </div>
        </div>

        {{-- Top Insights Cards --}}
        <div class="row">
            <div class="col-xl-4 col-lg-4 col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <p class="text-muted mb-2">{{ __('attendance.average_arrival_time') }}</p>
                        <h2 class="text-primary fw-bold">{{ $stats['current_avg_time'] }}</h2>
                        <span class="badge badge-light text-dark mt-2 d-block text-start" style="white-space: normal; font-size:13px;">
                            {!! $stats['arrival_insight'] !!}
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-4 col-lg-4 col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <p class="text-muted mb-2">{{ __('attendance.punctuality_score') }}</p>
                        <h2 class="text-success fw-bold">{{ $stats['current_punctuality'] }}%</h2>
                        <span class="badge badge-light text-dark mt-2 d-block text-start" style="white-space: normal; font-size:13px;">
                            {!! $stats['punctuality_insight'] !!}
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-4 col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <p class="text-muted mb-2">{{ __('attendance.participation_frequency') }}</p>
                        <h2 class="text-info fw-bold">{{ $stats['participation_rate'] }}%</h2>
                        <div class="progress mt-3" style="height: 6px;">
                            <div class="progress-bar bg-info" style="width: {{ $stats['participation_rate'] }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Raw Logs Table --}}
        <div class="row mt-3">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header border-bottom">
                        <h4 class="card-title">{{ __('attendance.detailed_logs') }} ({{ __('attendance.period_' . $period) }})</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="bg-light">
                                    <tr>
                                        <th>{{ __('attendance.date') }}</th>
                                        <th>{{ __('attendance.status') }}</th>
                                        <th>{{ __('attendance.check_in_hardware') }}</th>
                                        <th>{{ __('attendance.check_out_hardware') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($stats['records'] as $log)
                                    <tr>
                                        <td class="fw-bold">{{ \Carbon\Carbon::parse($log->attendance_date)->format('D, d M Y') }}</td>
                                        <td>
                                            @if($log->status == 'present') <span class="badge badge-success">{{ __('attendance.on_time') }}</span>
                                            @elseif($log->status == 'late') <span class="badge badge-warning">{{ __('attendance.late') }}</span>
                                            @else <span class="badge badge-danger">{{ __('attendance.absent') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $log->check_in ? \Carbon\Carbon::parse($log->check_in)->format('h:i A') : '--' }}</td>
                                        <td>{{ $log->check_out ? \Carbon\Carbon::parse($log->check_out)->format('h:i A') : '--' }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">{{ __('attendance.no_hardware_logs') }}</td>
                                    </tr>
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

@section('js')
<script>
    if($.fn.selectpicker) $('.default-select').selectpicker();
</script>
@endsection