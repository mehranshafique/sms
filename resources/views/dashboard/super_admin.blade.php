@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">

        @php
            $currency = \App\Enums\CurrencySymbol::default();
            $collectionPercent = $expectedTotal > 0 ? ($collectedTotal / $expectedTotal) * 100 : 0;
            $totalPayStudents = $paidCount + $unpaidCount;
            $paidPercent = $totalPayStudents > 0 ? round(($paidCount / $totalPayStudents) * 100) : 0;
        @endphp

        {{-- Welcome Banner --}}
        @include('dashboard.partials.welcome-banner', ['institution' => $institution, 'currentSession' => $currentSession ?? null])

        {{-- ROW 1: KEY STATS --}}
        <div class="row g-3 mb-2 dash-stat-grid">
            <div class="col-xl-3 col-sm-6 mb-3">
                @include('dashboard.partials.stat-card', [
                    'icon' => 'la la-users', 'tint' => 'primary',
                    'label' => __('dashboard.total_enrollment'), 'value' => $totalEnrollment,
                    'hint' => __('dashboard.new_students', ['count' => $newComers]), 'hintClass' => 'text-tint-primary',
                    'progress' => 100,
                ])
            </div>
            <div class="col-xl-3 col-sm-6 mb-3">
                @include('dashboard.partials.stat-card', [
                    'icon' => 'la la-check-circle', 'tint' => 'success',
                    'label' => __('dashboard.paid_students'), 'value' => $paidCount,
                    'hint' => $paidPercent . '% ' . __('dashboard.fully_settled'), 'hintClass' => 'text-tint-success',
                    'progress' => $paidPercent,
                ])
            </div>
            <div class="col-xl-3 col-sm-6 mb-3">
                @include('dashboard.partials.stat-card', [
                    'icon' => 'la la-exclamation-circle', 'tint' => 'danger',
                    'label' => __('dashboard.unpaid_students'), 'value' => $unpaidCount,
                    'hint' => __('dashboard.pending_dues'), 'hintClass' => 'text-tint-danger',
                    'progress' => $totalPayStudents > 0 ? round(($unpaidCount / $totalPayStudents) * 100) : 0,
                ])
            </div>
            <div class="col-xl-3 col-sm-6 mb-3">
                @include('dashboard.partials.stat-card', [
                    'icon' => 'la la-chalkboard-teacher', 'tint' => 'warning',
                    'label' => __('dashboard.personnel'), 'value' => $totalStaff,
                    'hint' => $totalTeachers . ' ' . __('dashboard.teachers'), 'hintClass' => 'text-tint-warning',
                    'progress' => $totalStaff > 0 ? round(($totalTeachers / max(1, $totalStaff)) * 100) : 0,
                ])
            </div>
        </div>

        {{-- ROW 2: FINANCIAL HEALTH + TODAY'S ATTENDANCE --}}
        <div class="row g-3">
            <div class="col-xl-8 mb-3">
                <div class="dash-panel h-100">
                    <div class="dash-panel__head">
                        <h4 class="dash-panel__title">{{ __('dashboard.financial_health') }}</h4>
                        <span class="badge rounded-pill dash-live-badge {{ $collectionPercent >= 70 ? 'is-good' : ($collectionPercent >= 40 ? 'is-warn' : 'is-bad') }}">
                            {{ number_format($collectionPercent, 1) }}% {{ __('dashboard.collected') }}
                        </span>
                    </div>
                    <div class="dash-panel__body">
                        <div class="row text-center">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <span class="dash-mini-label d-block mb-1">{{ __('dashboard.expected_revenue') }}</span>
                                <h3 class="fw-bold mb-0">{{ $currency }}{{ number_format($expectedTotal, 0) }}</h3>
                                <small class="dash-mini-label">{{ __('dashboard.based_on_enrollment') }}</small>
                            </div>
                            <div class="col-md-4 mb-3 mb-md-0">
                                <span class="dash-mini-label d-block mb-1">{{ __('dashboard.collected_revenue') }}</span>
                                <h3 class="text-tint-success fw-bold mb-0">{{ $currency }}{{ number_format($collectedTotal, 0) }}</h3>
                                <small class="dash-mini-label">{{ __('dashboard.collected') }}</small>
                            </div>
                            <div class="col-md-4">
                                <span class="dash-mini-label d-block mb-1">{{ __('dashboard.remaining_balance') }}</span>
                                <h3 class="text-tint-danger fw-bold mb-0">{{ $currency }}{{ number_format($remainingToCollect, 0) }}</h3>
                                <small class="dash-mini-label">{{ __('dashboard.outstanding') }}</small>
                            </div>
                        </div>
                        <div class="dash-progress mt-4">
                            <span class="bg-success" style="width: {{ min(100, $collectionPercent) }}%; background: var(--dash-success);"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 mb-3">
                <div class="dash-panel h-100">
                    <div class="dash-panel__head">
                        <h4 class="dash-panel__title">{{ __('dashboard.todays_attendance') }}</h4>
                        <a href="{{ route('attendance.overview') }}" class="text-tint-primary small">{{ __('dashboard.view_overview') }}</a>
                    </div>
                    <div class="dash-panel__body">
                        @php
                            $studentRateToday = ($studentsExpected ?? 0) > 0 ? (int) $attendanceRate : 0;
                            $staffRateToday = (int) ($staffAttendanceRate ?? 0);
                        @endphp
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="dash-mini-label">{{ __('attendance.overview_students') }}</span>
                            <span class="badge rounded-pill dash-live-badge {{ $studentRateToday >= 85 ? 'is-good' : ($studentRateToday >= 60 ? 'is-warn' : 'is-bad') }}">
                                {{ $studentRateToday }}%
                            </span>
                        </div>
                        <div class="dash-progress mb-3">
                            <span style="width: {{ min(100, $studentRateToday) }}%; background: var(--dash-success);"></span>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-4">
                                <div class="dash-metric-chip">
                                    <div class="dash-metric-chip__value">{{ $studentsExpected ?? 0 }}</div>
                                    <span class="dash-metric-chip__label">{{ __('attendance.expected') }}</span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="dash-metric-chip">
                                    <div class="dash-metric-chip__value text-tint-success">{{ $presentCount }}</div>
                                    <span class="dash-metric-chip__label">{{ __('dashboard.present') }}</span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="dash-metric-chip">
                                    <div class="dash-metric-chip__value text-tint-danger">{{ $absentCount }}</div>
                                    <span class="dash-metric-chip__label">{{ __('dashboard.absent') }}</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="dash-metric-chip">
                                    <div class="dash-metric-chip__value text-tint-warning">{{ $lateCount }}</div>
                                    <span class="dash-metric-chip__label">{{ __('dashboard.late') }}</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="dash-metric-chip">
                                    <div class="dash-metric-chip__value text-tint-info">{{ $studentsNotCheckedIn ?? 0 }}</div>
                                    <span class="dash-metric-chip__label">{{ __('attendance.not_checked_in') }}</span>
                                </div>
                            </div>
                        </div>
                        <hr class="my-2 dash-divider">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="dash-mini-label">{{ __('attendance.overview_staff') }}</span>
                            <span class="badge rounded-pill dash-live-badge {{ $staffRateToday >= 85 ? 'is-good' : ($staffRateToday >= 60 ? 'is-warn' : 'is-bad') }}">
                                {{ $staffRateToday }}%
                            </span>
                        </div>
                        <div class="dash-progress mb-3">
                            <span style="width: {{ min(100, $staffRateToday) }}%; background: var(--dash-primary);"></span>
                        </div>
                        <div class="row g-2">
                            <div class="col-4">
                                <div class="dash-metric-chip">
                                    <div class="dash-metric-chip__value">{{ $staffExpected ?? 0 }}</div>
                                    <span class="dash-metric-chip__label">{{ __('attendance.expected') }}</span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="dash-metric-chip">
                                    <div class="dash-metric-chip__value text-tint-success">{{ $staffPresent ?? 0 }}</div>
                                    <span class="dash-metric-chip__label">{{ __('dashboard.present') }}</span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="dash-metric-chip">
                                    <div class="dash-metric-chip__value text-tint-danger">{{ $staffAbsent ?? 0 }}</div>
                                    <span class="dash-metric-chip__label">{{ __('dashboard.absent') }}</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="dash-metric-chip">
                                    <div class="dash-metric-chip__value text-tint-warning">{{ $staffLate ?? 0 }}</div>
                                    <span class="dash-metric-chip__label">{{ __('dashboard.late') }}</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="dash-metric-chip">
                                    <div class="dash-metric-chip__value text-tint-info">{{ $staffNotCheckedIn ?? 0 }}</div>
                                    <span class="dash-metric-chip__label">{{ __('attendance.not_checked_in') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Students by class --}}
        <div class="row g-3">
            <div class="col-12 mb-3">
                <div class="dash-panel">
                    <div class="dash-panel__head">
                        <h4 class="dash-panel__title">{{ __('attendance.students_by_class') }}</h4>
                        <span class="dash-mini-label">{{ __('attendance.total_enrollment_label', ['count' => $totalEnrollment]) }}</span>
                    </div>
                    <div class="dash-panel__body">
                        @if(!empty($classesByEnrollment) && count($classesByEnrollment) > 0)
                            <div class="row g-2">
                                @foreach($classesByEnrollment as $class)
                                    <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                                        <a href="{{ route('students.index', ['class_section_id' => $class['class_section_id']]) }}" class="dash-link d-block h-100">
                                            <div class="dash-link__value">{{ $class['enrollment'] }}</div>
                                            <div class="dash-link__label">{{ $class['label'] }}</div>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-3">
                                <a href="{{ route('attendance.overview') }}" class="text-tint-primary small">{{ __('dashboard.view_overview') }}</a>
                            </div>
                        @else
                            <div class="text-center py-3 text-muted">
                                <span class="dash-mini-label">{{ __('attendance.no_classes_enrolled') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ROW 3: ENROLLMENT TREND + ACADEMIC QUICK LINKS --}}
        <div class="row g-3">
            <div class="col-xl-8 mb-3">
                <div class="dash-panel h-100">
                    <div class="dash-panel__head">
                        <h4 class="dash-panel__title">{{ __('dashboard.enrollment_trend') }}</h4>
                        <span class="dash-mini-label">{{ __('dashboard.last_7_days') }}</span>
                    </div>
                    <div class="dash-panel__body">
                        <canvas id="enrollmentChart" height="90"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 mb-3">
                <div class="row g-3 h-100">
                    <div class="col-sm-6 col-12 mb-3">
                        <a href="{{ route('subjects.index') }}" class="dash-link">
                            <span class="dash-stat__icon tint-primary"><i class="la la-book"></i></span>
                            <div>
                                <div class="dash-link__value">{{ $totalCourses }} / {{ $totalTeachers }}</div>
                                <div class="dash-link__label">{{ __('dashboard.courses_teachers') }}</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-6 col-12 mb-3">
                        <a href="{{ route('marks.create') }}" class="dash-link">
                            <span class="dash-stat__icon tint-danger"><i class="la la-trophy"></i></span>
                            <div>
                                <div class="dash-link__value">{{ $totalResults }}</div>
                                <div class="dash-link__label">{{ __('dashboard.results_published') }}</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-6 col-12 mb-3">
                        <a href="{{ route('timetables.index') }}" class="dash-link">
                            <span class="dash-stat__icon tint-success"><i class="la la-calendar"></i></span>
                            <div>
                                <div class="dash-link__value">{{ $totalTimetables }}</div>
                                <div class="dash-link__label">{{ __('dashboard.timetables') }}</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-6 col-12 mb-3">
                        <a href="{{ route('notices.index') }}" class="dash-link">
                            <span class="dash-stat__icon tint-info"><i class="la la-comments"></i></span>
                            <div>
                                <div class="dash-link__value">{{ $totalCommunication }}</div>
                                <div class="dash-link__label">{{ __('dashboard.communication') }}</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ROW 4: RECENT PAYMENTS + INSTALLMENTS --}}
        <div class="row g-3">
            <div class="col-xl-7 mb-3">
                <div class="dash-panel h-100">
                    <div class="dash-panel__head">
                        <h4 class="dash-panel__title">{{ __('dashboard.recent_payments') }}</h4>
                        <a href="{{ route('invoices.index') }}" class="text-tint-primary small">{{ __('dashboard.view_all') }}</a>
                    </div>
                    <div class="dash-panel__body">
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless align-middle mb-0">
                                <tbody>
                                    @forelse($recentPayments as $payment)
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $payment->invoice->student->full_name ?? __('dashboard.unknown_student') }}</div>
                                            <small class="dash-mini-label">{{ $payment->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td class="text-end fw-bold text-tint-success">+{{ $currency }}{{ number_format($payment->amount, 0) }}</td>
                                    </tr>
                                    @empty
                                    <tr><td class="text-center text-muted py-4">{{ __('dashboard.no_recent_payments') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-5 mb-3">
                <div class="dash-panel h-100">
                    <div class="dash-panel__head">
                        <h4 class="dash-panel__title">{{ __('dashboard.installment_breakdown') }}</h4>
                    </div>
                    <div class="dash-panel__body">
                        @if(count($installmentStats) > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless mb-0">
                                    <tbody>
                                        @foreach($installmentStats as $stat)
                                        <tr>
                                            <td>
                                                <span class="badge badge-xs light badge-primary me-2">{{ $stat['order'] }}</span>
                                                {{ $stat['label'] }}
                                            </td>
                                            <td class="text-end fw-bold">{{ $currency }}{{ number_format($stat['expected'], 0) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4 text-muted">
                                <i class="la la-info-circle" style="font-size: 24px;"></i><br>
                                <span class="dash-mini-label">{{ __('dashboard.no_installments') }}</span>
                            </div>
                        @endif
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

        var enrollEl = document.getElementById("enrollmentChart");
        if (enrollEl) {
            new Chart(enrollEl.getContext('2d'), {
                type: 'line',
                data: {
                    labels: {!! json_encode($chartLabels) !!},
                    datasets: [{
                        label: @json(__('dashboard.new_students_label')),
                        data: {!! json_encode($chartValues) !!},
                        borderColor: '#5b53e8',
                        borderWidth: 3,
                        backgroundColor: 'rgba(91, 83, 232, 0.08)',
                        pointBackgroundColor: '#5b53e8',
                        pointRadius: 4,
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
        }
    })(jQuery);
</script>
@endsection
