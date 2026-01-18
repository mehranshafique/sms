@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        {{-- Welcome Banner --}}
        <div class="row mb-4">
            <div class="col-xl-12">
                <div class="card bg-primary text-white shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center p-4">
                        <div>
                            <h3 class="text-white fw-bold mb-1">
                                {{ __('dashboard.welcome_back') }}, {{ Auth::user()->name }}!
                            </h3>
                            <p class="mb-0 opacity-75">
                                @if(isset($currentSession))
                                    {{ __('dashboard.current_session') }}: <strong>{{ $currentSession->name }}</strong>
                                    ({{ $currentSession->start_date->format('M Y') }} - {{ $currentSession->end_date->format('M Y') }})
                                @endif
                            </p>
                        </div>
                        <i class="la la-graduation-cap opacity-25" style="font-size: 3rem;"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- ROW 1: ENROLLMENT & PAYMENT STATUS --}}
        <div class="row">
            {{-- 1. Total Enrollment --}}
            <div class="col-xl-3 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-primary text-primary">
                                <i class="la la-users"></i>
                            </span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('dashboard.total_enrollment') }}</p>
                                <h4 class="mb-0">{{ $totalEnrollment }}</h4>
                                <small class="text-muted">{{ __('dashboard.students') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. Fully Paid Students --}}
            <div class="col-xl-3 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-success text-success">
                                <i class="la la-check-circle"></i>
                            </span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('dashboard.paid_students') }}</p> {{-- Ensure key exists or use fallback --}}
                                <h4 class="mb-0">{{ $paidCount }}</h4>
                                <small class="text-muted">{{ __('dashboard.fully_settled') ?? 'Fully Settled' }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. Unpaid / Partial Students --}}
            <div class="col-xl-3 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-danger text-danger">
                                <i class="la la-exclamation-circle"></i>
                            </span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('dashboard.unpaid_students') }}</p> {{-- Ensure key exists --}}
                                <h4 class="mb-0">{{ $unpaidCount }}</h4>
                                <small class="text-muted">{{ __('dashboard.pending_dues') ?? 'Pending Dues' }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. Personnel --}}
            <div class="col-xl-3 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-warning text-warning">
                                <i class="la la-chalkboard-teacher"></i>
                            </span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('dashboard.personnel') }}</p>
                                <h4 class="mb-0">{{ $totalStaff }}</h4>
                                <small class="text-muted">{{ __('dashboard.active_staff') ?? 'Active Staff' }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ROW 2: FINANCIAL HEALTH OVERVIEW --}}
        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title">{{ __('dashboard.financial_health') ?? 'Financial Health (Enrollment Based)' }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            {{-- Expected Revenue --}}
                            <div class="col-md-4 border-end">
                                <span class="text-muted d-block mb-1">{{ __('dashboard.expected_revenue') ?? 'Expected Revenue' }}</span>
                                <h3 class="fw-bold mb-0">${{ number_format($expectedTotal, 2) }}</h3>
                                <small class="text-primary">{{ __('dashboard.based_on_enrollment') ?? 'Based on Active Enrollments' }}</small>
                            </div>
                            
                            {{-- Collected Revenue --}}
                            <div class="col-md-4 border-end">
                                <span class="text-muted d-block mb-1">{{ __('dashboard.collected_revenue') ?? 'Collected Revenue' }}</span>
                                <h3 class="text-success fw-bold mb-0">${{ number_format($collectedTotal, 2) }}</h3>
                                
                                {{-- Progress Bar --}}
                                @php
                                    $collectionPercent = $expectedTotal > 0 ? ($collectedTotal / $expectedTotal) * 100 : 0;
                                @endphp
                                <div class="progress mt-2 mx-auto" style="height: 6px; width: 60%;">
                                    <div class="progress-bar bg-success" style="width: {{ $collectionPercent }}%"></div>
                                </div>
                                <small class="text-muted">{{ number_format($collectionPercent, 1) }}% {{ __('dashboard.collected') }}</small>
                            </div>

                            {{-- Remaining Balance --}}
                            <div class="col-md-4">
                                <span class="text-muted d-block mb-1">{{ __('dashboard.remaining_balance') ?? 'Remaining to Collect' }}</span>
                                <h3 class="text-danger fw-bold mb-0">${{ number_format($remainingToCollect, 2) }}</h3>
                                <small class="text-muted">{{ __('dashboard.outstanding') ?? 'Outstanding' }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ROW 3: INSTALLMENT BREAKDOWN & ACADEMIC STATS --}}
        <div class="row">
            {{-- Installment Breakdown --}}
            <div class="col-xl-6 col-lg-12">
                <div class="card" style="min-height: 300px;">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title">{{ __('dashboard.installment_breakdown') ?? 'Installment Projections' }}</h4>
                    </div>
                    <div class="card-body">
                        @if(count($installmentStats) > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless">
                                    <thead>
                                        <tr>
                                            <th>{{ __('dashboard.installment') ?? 'Installment' }}</th>
                                            <th class="text-end">{{ __('dashboard.expected') ?? 'Expected' }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($installmentStats as $stat)
                                        <tr>
                                            <td>
                                                <span class="badge badge-xs light badge-primary me-2">{{ $stat['order'] }}</span>
                                                {{ $stat['label'] }}
                                            </td>
                                            <td class="text-end fw-bold">${{ number_format($stat['expected'], 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5 text-muted">
                                <i class="la la-info-circle fs-24 mb-2"></i><br>
                                {{ __('dashboard.no_installments') ?? 'No installment data available.' }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Academic Stats Grid --}}
            <div class="col-xl-6 col-lg-12">
                <div class="row">
                    {{-- Courses --}}
                    <div class="col-sm-6">
                        <div class="widget-stat card bg-dark text-white">
                            <div class="card-body p-4">
                                <div class="media">
                                    <span class="me-3"><i class="la la-book"></i></span>
                                    <div class="media-body text-white">
                                        <p class="mb-1 text-white opacity-75">{{ __('dashboard.courses_teachers') }}</p>
                                        <h4 class="text-white">{{ $totalCourses }} / {{ $totalTeachers }}</h4>
                                    </div>
                                </div>
                                <a href="{{ route('subjects.index') }}" class="text-white mt-2 d-block small underline">{{ __('dashboard.view_details') }}</a>
                            </div>
                        </div>
                    </div>

                    {{-- Results --}}
                    <div class="col-sm-6">
                        <div class="widget-stat card bg-danger text-white">
                            <div class="card-body p-4">
                                <div class="media">
                                    <span class="me-3"><i class="la la-trophy"></i></span>
                                    <div class="media-body text-white">
                                        <p class="mb-1 text-white opacity-75">{{ __('dashboard.results') }}</p>
                                        <h4 class="text-white">{{ $totalResults }} {{ __('dashboard.published') }}</h4>
                                    </div>
                                </div>
                                <a href="{{ route('marks.create') }}" class="text-white mt-2 d-block small underline">{{ __('dashboard.view_details') }}</a>
                            </div>
                        </div>
                    </div>

                    {{-- Timetables --}}
                    <div class="col-sm-6">
                        <div class="widget-stat card bg-success text-white">
                            <div class="card-body p-4">
                                <div class="media">
                                    <span class="me-3"><i class="la la-calendar"></i></span>
                                    <div class="media-body text-white">
                                        <p class="mb-1 text-white opacity-75">{{ __('dashboard.timetables') }}</p>
                                        <h4 class="text-white">{{ $totalTimetables }} {{ __('dashboard.classes') }}</h4>
                                    </div>
                                </div>
                                <a href="{{ route('timetables.index') }}" class="text-white mt-2 d-block small underline">{{ __('dashboard.view_details') }}</a>
                            </div>
                        </div>
                    </div>

                    {{-- Communication --}}
                    <div class="col-sm-6">
                        <div class="widget-stat card bg-info text-white">
                            <div class="card-body p-4">
                                <div class="media">
                                    <span class="me-3"><i class="la la-comments"></i></span>
                                    <div class="media-body text-white">
                                        <p class="mb-1 text-white opacity-75">{{ __('dashboard.communication') }}</p>
                                        <h4 class="text-white">SMS/Email</h4>
                                    </div>
                                </div>
                                <a href="#" class="text-white mt-2 d-block small underline">{{ __('dashboard.view_details') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection