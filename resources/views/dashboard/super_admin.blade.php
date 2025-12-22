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

        {{-- NEW CARDS SECTION (Based on Request) --}}
        <div class="row">
            {{-- 1. Total Enrollment --}}
            <div class="col-xl-4 col-xxl-4 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-primary text-primary">
                                <i class="la la-users"></i>
                            </span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('dashboard.total_enrollment') }}</p>
                                <h4 class="mb-0">{{ $totalEnrollment }}</h4>
                                <small class="text-muted">{{ $currentSession->name ?? '' }}</small>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('students.index') }}" class="btn btn-outline-primary btn-sm w-100">{{ __('dashboard.view_details') }}</a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. New Comers --}}
            <div class="col-xl-4 col-xxl-4 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-info text-info">
                                <i class="la la-user-plus"></i>
                            </span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('dashboard.new_comers') }}</p>
                                <h4 class="mb-0">{{ $newComers }}</h4>
                                <small class="text-muted">{{ __('dashboard.this_session') }}</small>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('students.index', ['type' => 'new']) }}" class="btn btn-outline-info btn-sm w-100">{{ __('dashboard.view_details') }}</a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. Personnel --}}
            <div class="col-xl-4 col-xxl-4 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-warning text-warning">
                                <i class="la la-chalkboard-teacher"></i>
                            </span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('dashboard.personnel') }}</p>
                                <h4 class="mb-0">{{ $totalStaff }}</h4>
                                <small class="text-muted">{{ $currentSession->name ?? '' }}</small>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('staff.index') }}" class="btn btn-outline-warning btn-sm w-100">{{ __('dashboard.view_details') }}</a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. Fees Summary --}}
            <div class="col-xl-6 col-xxl-6 col-lg-6">
                <div class="card">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title">{{ __('dashboard.fees_summary') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <span class="d-block text-muted">{{ __('dashboard.paid') }}</span>
                                <h4 class="text-success mb-0">${{ number_format($sessionFeesPaid, 2) }}</h4>
                            </div>
                            <div>
                                <span class="d-block text-muted text-end">{{ __('dashboard.rest') }}</span>
                                <h4 class="text-danger mb-0 text-end">${{ number_format($sessionFeesRest, 2) }}</h4>
                            </div>
                        </div>
                        <div class="progress" style="height: 10px;">
                            @php
                                $total = $sessionFeesPaid + $sessionFeesRest;
                                $percent = $total > 0 ? ($sessionFeesPaid / $total) * 100 : 0;
                            @endphp
                            <div class="progress-bar bg-success" style="width: {{ $percent }}%"></div>
                        </div>
                        <div class="mt-3 text-center">
                            <a href="{{ route('invoices.index') }}" class="btn-link">{{ __('dashboard.view_details') }} <i class="fa fa-angle-right ms-1"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 5. Budget Summary --}}
            <div class="col-xl-6 col-xxl-6 col-lg-6">
                <div class="card">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title">{{ __('dashboard.budget_spend') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <span class="d-block text-muted">{{ __('dashboard.spend') }}</span>
                                <h4 class="text-primary mb-0">${{ number_format($budgetSpend, 2) }}</h4>
                            </div>
                            <div>
                                <span class="d-block text-muted text-end">{{ __('dashboard.rest') }}</span>
                                <h4 class="text-secondary mb-0 text-end">${{ number_format($budgetRest, 2) }}</h4>
                            </div>
                        </div>
                        <div class="progress" style="height: 10px;">
                            {{-- Placeholder progress --}}
                            <div class="progress-bar bg-primary" style="width: 40%"></div>
                        </div>
                        <div class="mt-3 text-center">
                            <a href="#" class="btn-link">{{ __('dashboard.view_details') }} <i class="fa fa-angle-right ms-1"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 6. Courses & Teachers --}}
            <div class="col-xl-3 col-sm-6">
                <div class="widget-stat card bg-dark text-white">
                    <div class="card-body p-4">
                        <div class="media">
                            <span class="me-3">
                                <i class="la la-book"></i>
                            </span>
                            <div class="media-body text-white">
                                <p class="mb-1 text-white opacity-75">{{ __('dashboard.courses_teachers') }}</p>
                                <h4 class="text-white">{{ $totalCourses }} / {{ $totalTeachers }}</h4>
                            </div>
                        </div>
                        <a href="{{ route('subjects.index') }}" class="text-white mt-2 d-block small underline">{{ __('dashboard.view_details') }}</a>
                    </div>
                </div>
            </div>

            {{-- 7. Results --}}
            <div class="col-xl-3 col-sm-6">
                <div class="widget-stat card bg-danger text-white">
                    <div class="card-body p-4">
                        <div class="media">
                            <span class="me-3">
                                <i class="la la-trophy"></i>
                            </span>
                            <div class="media-body text-white">
                                <p class="mb-1 text-white opacity-75">{{ __('dashboard.results') }}</p>
                                <h4 class="text-white">{{ $totalResults }} {{ __('dashboard.published') }}</h4>
                            </div>
                        </div>
                        <a href="{{ route('marks.create') }}" class="text-white mt-2 d-block small underline">{{ __('dashboard.view_details') }}</a>
                    </div>
                </div>
            </div>

            {{-- 8. Timetables --}}
            <div class="col-xl-3 col-sm-6">
                <div class="widget-stat card bg-success text-white">
                    <div class="card-body p-4">
                        <div class="media">
                            <span class="me-3">
                                <i class="la la-calendar"></i>
                            </span>
                            <div class="media-body text-white">
                                <p class="mb-1 text-white opacity-75">{{ __('dashboard.timetables') }}</p>
                                <h4 class="text-white">{{ $totalTimetables }} {{ __('dashboard.classes') }}</h4>
                            </div>
                        </div>
                        <a href="{{ route('timetables.index') }}" class="text-white mt-2 d-block small underline">{{ __('dashboard.view_details') }}</a>
                    </div>
                </div>
            </div>

            {{-- 9. Communication --}}
            <div class="col-xl-3 col-sm-6">
                <div class="widget-stat card bg-info text-white">
                    <div class="card-body p-4">
                        <div class="media">
                            <span class="me-3">
                                <i class="la la-comments"></i>
                            </span>
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
@endsection