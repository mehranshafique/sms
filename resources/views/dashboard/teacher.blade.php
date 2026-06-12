@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">

        @include('dashboard.partials.welcome-banner', [
            'institution' => $institution ?? null,
            'currentSession' => $currentSession ?? null,
            'subtitle' => __('dashboard.teacher_dashboard'),
        ])

        {{-- KEY STATS --}}
        <div class="row g-3 mb-2">
            <div class="col-xl-3 col-sm-6 mb-3">
                @include('dashboard.partials.stat-card', [
                    'icon' => 'la la-book', 'tint' => 'primary',
                    'label' => __('dashboard.my_courses'), 'value' => $myCoursesCount,
                    'hint' => __('dashboard.subjects_taught'),
                ])
            </div>
            <div class="col-xl-3 col-sm-6 mb-3">
                @include('dashboard.partials.stat-card', [
                    'icon' => 'la la-chalkboard', 'tint' => 'info',
                    'label' => __('dashboard.my_classes'), 'value' => $myClassesCount ?? 0,
                    'hint' => __('dashboard.class_sections'),
                ])
            </div>
            <div class="col-xl-3 col-sm-6 mb-3">
                @include('dashboard.partials.stat-card', [
                    'icon' => 'la la-users', 'tint' => 'warning',
                    'label' => __('dashboard.my_students'), 'value' => $myStudentsCount,
                    'hint' => __('dashboard.students'),
                ])
            </div>
            <div class="col-xl-3 col-sm-6 mb-3">
                @include('dashboard.partials.stat-card', [
                    'icon' => 'la la-calendar-check-o', 'tint' => 'success',
                    'label' => __('dashboard.todays_classes'), 'value' => $todayClasses->count(),
                    'hint' => __('dashboard.weekly_sessions', ['count' => $weeklyClassesCount ?? 0]),
                    'hintClass' => 'text-tint-success',
                ])
            </div>
        </div>

        {{-- SCHEDULE + WEEKLY LOAD --}}
        <div class="row g-3">
            <div class="col-xl-8 mb-3">
                <div class="dash-panel h-100">
                    <div class="dash-panel__head">
                        <h4 class="dash-panel__title">{{ __('dashboard.my_timetable') }} <span class="dash-mini-label fw-normal">({{ now()->translatedFormat('l') }})</span></h4>
                    </div>
                    <div class="dash-panel__body">
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless align-middle mb-0">
                                <thead>
                                    <tr class="dash-mini-label">
                                        <th>{{ __('dashboard.time') }}</th>
                                        <th>{{ __('dashboard.class') }}</th>
                                        <th>{{ __('dashboard.subject') }}</th>
                                        <th>{{ __('dashboard.room') }}</th>
                                        <th class="text-end">{{ __('dashboard.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($todayClasses as $class)
                                        <tr>
                                            <td><span class="text-tint-primary fw-bold">{{ $class->start_time->format('H:i') }}</span></td>
                                            <td>{{ $class->classSection->name }}</td>
                                            <td>{{ $class->subject->name }}</td>
                                            <td>{{ $class->room_number ?? '-' }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('marks.create', ['class_section_id' => $class->class_section_id]) }}" class="btn btn-xs btn-outline-primary">{{ __('dashboard.marks') }}</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-4">{{ __('dashboard.no_classes_today') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 mb-3">
                <div class="dash-panel h-100">
                    <div class="dash-panel__head">
                        <h4 class="dash-panel__title">{{ __('dashboard.weekly_load') }}</h4>
                    </div>
                    <div class="dash-panel__body">
                        <canvas id="weeklyLoadChart" height="180"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- NOTICES --}}
        @if(isset($recentNotices) && $recentNotices->count())
        <div class="row g-3">
            <div class="col-12 mb-3">
                <div class="dash-panel">
                    <div class="dash-panel__head">
                        <h4 class="dash-panel__title">{{ __('dashboard.latest_notices') }}</h4>
                    </div>
                    <div class="dash-panel__body">
                        <div class="row">
                            @foreach($recentNotices as $notice)
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-start">
                                        <span class="dash-stat__icon tint-info me-3" style="width:38px;height:38px;font-size:16px;"><i class="la la-bullhorn"></i></span>
                                        <div>
                                            <div class="fw-bold text-dark fs-14">{{ $notice->title }}</div>
                                            <small class="dash-mini-label">{{ $notice->created_at->diffForHumans() }}</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('vendor/chart.js/Chart.bundle.min.js') }}"></script>
<script>
    (function($) {
        "use strict";
        if (typeof Chart === 'undefined') return;
        var el = document.getElementById("weeklyLoadChart");
        if (el) {
            new Chart(el.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: {!! json_encode($weekChartLabels ?? []) !!},
                    datasets: [{
                        label: @json(__('dashboard.classes')),
                        data: {!! json_encode($weekChartValues ?? []) !!},
                        backgroundColor: 'rgba(91, 83, 232, 0.85)',
                        borderRadius: 6,
                        barPercentage: 0.6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: { display: false },
                    scales: { yAxes: [{ ticks: { beginAtZero: true, precision: 0 } }] }
                }
            });
        }
    })(jQuery);
</script>
@endsection
