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
        <div class="row g-3 mb-2">
            <div class="col-xl-3 col-sm-6 mb-3">
                @include('dashboard.partials.stat-card', [
                    'icon' => 'la la-users', 'tint' => 'primary',
                    'label' => __('dashboard.total_enrollment'), 'value' => $totalEnrollment,
                    'hint' => __('dashboard.new_students', ['count' => $newComers]), 'hintClass' => 'text-tint-primary',
                ])
            </div>
            <div class="col-xl-3 col-sm-6 mb-3">
                @include('dashboard.partials.stat-card', [
                    'icon' => 'la la-check-circle', 'tint' => 'success',
                    'label' => __('dashboard.paid_students'), 'value' => $paidCount,
                    'hint' => $paidPercent . '% ' . __('dashboard.fully_settled'), 'hintClass' => 'text-tint-success',
                ])
            </div>
            <div class="col-xl-3 col-sm-6 mb-3">
                @include('dashboard.partials.stat-card', [
                    'icon' => 'la la-exclamation-circle', 'tint' => 'danger',
                    'label' => __('dashboard.unpaid_students'), 'value' => $unpaidCount,
                    'hint' => __('dashboard.pending_dues'), 'hintClass' => 'text-tint-danger',
                ])
            </div>
            <div class="col-xl-3 col-sm-6 mb-3">
                @include('dashboard.partials.stat-card', [
                    'icon' => 'la la-chalkboard-teacher', 'tint' => 'warning',
                    'label' => __('dashboard.personnel'), 'value' => $totalStaff,
                    'hint' => $totalTeachers . ' ' . __('dashboard.teachers'), 'hintClass' => 'text-tint-warning',
                ])
            </div>
        </div>

        {{-- ROW 2: FINANCIAL HEALTH + TODAY'S ATTENDANCE --}}
        <div class="row g-3">
            <div class="col-xl-8 mb-3">
                <div class="dash-panel h-100">
                    <div class="dash-panel__head">
                        <h4 class="dash-panel__title">{{ __('dashboard.financial_health') }}</h4>
                        <span class="badge rounded-pill" style="background: rgba(91,83,232,.12); color: var(--dash-primary);">
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
                    </div>
                    <div class="dash-panel__body text-center">
                        @if($attendanceMarked > 0)
                            <div class="d-flex justify-content-center align-items-center mb-3" style="position: relative;">
                                <canvas id="attendanceDonut" height="150" width="150"></canvas>
                                <div style="position:absolute; text-align:center;">
                                    <div class="fw-bold" style="font-size: 26px; color: var(--dash-ink);">{{ $attendanceRate }}%</div>
                                    <div class="dash-mini-label">{{ __('dashboard.present') }}</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-4">
                                    <div class="fw-bold text-tint-success">{{ $presentCount }}</div>
                                    <small class="dash-mini-label">{{ __('dashboard.present') }}</small>
                                </div>
                                <div class="col-4">
                                    <div class="fw-bold text-tint-warning">{{ $lateCount }}</div>
                                    <small class="dash-mini-label">{{ __('dashboard.late') }}</small>
                                </div>
                                <div class="col-4">
                                    <div class="fw-bold text-tint-danger">{{ $absentCount }}</div>
                                    <small class="dash-mini-label">{{ __('dashboard.absent') }}</small>
                                </div>
                            </div>
                        @else
                            <div class="py-5 text-muted">
                                <i class="la la-calendar-check-o" style="font-size: 32px;"></i>
                                <p class="mb-0 mt-2 dash-mini-label">{{ __('dashboard.no_attendance_today') }}</p>
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

        var donutEl = document.getElementById("attendanceDonut");
        if (donutEl) {
            new Chart(donutEl.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: [@json(__('dashboard.present')), @json(__('dashboard.late')), @json(__('dashboard.absent'))],
                    datasets: [{
                        data: [{{ $presentCount }}, {{ $lateCount }}, {{ $absentCount }}],
                        backgroundColor: ['#2bb673', '#f5a623', '#ef5675'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: false,
                    maintainAspectRatio: false,
                    cutoutPercentage: 72,
                    legend: { display: false }
                }
            });
        }
    })(jQuery);
</script>
@endsection
