@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">

        @if(isset($error) || !isset($student))
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-danger solid shadow-sm">
                        <div class="d-flex align-items-center">
                            <i class="fa fa-exclamation-triangle me-2 fs-20"></i>
                            <div>
                                <h5 class="text-white mb-1">{{ __('dashboard.profile_not_found') }}</h5>
                                <p class="mb-0 fs-13">{{ $error ?? __('dashboard.no_student_profile') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            @php $currency = \App\Enums\CurrencySymbol::default(); @endphp

            @include('dashboard.partials.welcome-banner', [
                'institution' => $institution ?? null,
                'currentSession' => $currentSession ?? null,
                'subtitle' => __('dashboard.student_dashboard'),
            ])

            {{-- KEY STATS --}}
            <div class="row g-3 mb-2">
                <div class="col-xl-4 col-sm-6 mb-3">
                    @include('dashboard.partials.stat-card', [
                        'icon' => 'la la-graduation-cap', 'tint' => 'primary',
                        'label' => __('dashboard.my_results'), 'value' => $resultsCount ?? 0,
                        'hint' => __('dashboard.published_exams'),
                    ])
                </div>
                <div class="col-xl-4 col-sm-6 mb-3">
                    @include('dashboard.partials.stat-card', [
                        'icon' => 'la la-calendar-check-o', 'tint' => 'success',
                        'label' => __('dashboard.attendance'), 'value' => ($attendancePercentage ?? 0) . '%',
                        'hint' => __('dashboard.present_days', ['present' => $presentDays ?? 0, 'total' => $totalDays ?? 0]),
                        'hintClass' => 'text-tint-success',
                    ])
                </div>
                <div class="col-xl-4 col-sm-6 mb-3">
                    @include('dashboard.partials.stat-card', [
                        'icon' => 'la la-money', 'tint' => ($unpaidInvoices ?? 0) > 0 ? 'danger' : 'success',
                        'label' => __('dashboard.outstanding_balance'),
                        'value' => $currency . number_format($unpaidInvoices ?? 0, 0),
                        'hint' => __('dashboard.of_total', ['total' => $currency . number_format($totalFees ?? 0, 0)]),
                    ])
                </div>
            </div>

            <div class="row g-3">
                {{-- Fee progress + attendance --}}
                <div class="col-xl-4 mb-3">
                    <div class="dash-panel h-100">
                        <div class="dash-panel__head">
                            <h4 class="dash-panel__title">{{ __('dashboard.fee_status') }}</h4>
                        </div>
                        <div class="dash-panel__body">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="dash-mini-label">{{ __('dashboard.paid') }}</span>
                                <span class="fw-bold text-tint-success">{{ $currency }}{{ number_format($paidFees ?? 0, 0) }}</span>
                            </div>
                            <div class="dash-progress mb-2">
                                <span style="width: {{ $feePercent ?? 0 }}%; background: var(--dash-success);"></span>
                            </div>
                            <small class="dash-mini-label">{{ $feePercent ?? 0 }}% {{ __('dashboard.of_fees_paid') }}</small>

                            <hr class="dash-divider my-3">

                            <div class="row text-center">
                                <div class="col-6 border-end">
                                    <div class="fw-bold text-tint-success">{{ $presentDays ?? 0 }}</div>
                                    <small class="dash-mini-label">{{ __('dashboard.present') }}</small>
                                </div>
                                <div class="col-6">
                                    <div class="fw-bold text-tint-danger">{{ $absentDays ?? 0 }}</div>
                                    <small class="dash-mini-label">{{ __('dashboard.absent') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Today's schedule --}}
                <div class="col-xl-5 mb-3">
                    <div class="dash-panel h-100">
                        <div class="dash-panel__head">
                            <h4 class="dash-panel__title">{{ __('dashboard.todays_schedule') }}</h4>
                        </div>
                        <div class="dash-panel__body">
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless align-middle mb-0">
                                    <thead>
                                        <tr class="dash-mini-label">
                                            <th class="fs-12">{{ __('dashboard.time') }}</th>
                                            <th class="fs-12">{{ __('dashboard.subject') }}</th>
                                            <th class="fs-12">{{ __('dashboard.teacher') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($todayClasses as $class)
                                            <tr>
                                                <td class="fs-12">{{ $class->start_time->format('H:i') }}</td>
                                                <td class="fs-12 fw-bold">{{ $class->subject->name }}</td>
                                                <td class="fs-12">{{ $class->teacher->user->name ?? __('dashboard.not_available') }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="text-center text-muted fs-12 py-4">{{ __('dashboard.no_classes_today') }}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Profile --}}
                <div class="col-xl-3 mb-3">
                    <div class="dash-panel h-100">
                        <div class="dash-panel__body text-center">
                            <div class="profile-photo mb-2">
                                @if($student->student_photo)
                                    <img src="{{ asset('storage/'.$student->student_photo) }}" class="rounded-circle" width="70" height="70" style="object-fit:cover;">
                                @else
                                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto tint-primary" style="width: 70px; height: 70px;">
                                        <span class="fs-24 fw-bold">{{ substr($student->first_name, 0, 1) }}</span>
                                    </div>
                                @endif
                            </div>
                            <h5 class="mb-1 fs-15">{{ $student->full_name }}</h5>
                            <p class="mb-1 dash-mini-label">{{ $student->admission_number }}</p>
                            <span class="badge rounded-pill tint-primary">{{ $student->enrollments->last()->classSection->name ?? '' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Notices --}}
            @if(isset($recentNotices) && $recentNotices->count())
            <div class="row g-3">
                <div class="col-12 mb-3">
                    <div class="dash-panel">
                        <div class="dash-panel__head">
                            <h4 class="dash-panel__title">{{ __('dashboard.latest_notices') }}</h4>
                        </div>
                        <div class="dash-panel__body">
                            @foreach($recentNotices as $notice)
                                <div class="d-flex align-items-start mb-3">
                                    <span class="dash-stat__icon tint-info me-3" style="width:38px;height:38px;font-size:16px;"><i class="la la-bullhorn"></i></span>
                                    <div>
                                        <div class="fw-bold text-dark fs-14">{{ $notice->title }}</div>
                                        <small class="dash-mini-label">{{ $notice->created_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif

        @endif

    </div>
</div>
@endsection