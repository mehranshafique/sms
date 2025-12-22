@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        {{-- Error State Handling --}}
        @if(isset($error) || !isset($student))
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-danger solid shadow-sm">
                        <div class="d-flex align-items-center">
                            <i class="fa fa-exclamation-triangle me-2 fs-20"></i>
                            <div>
                                <h5 class="text-white mb-1">Profile Not Found</h5>
                                <p class="mb-0 fs-13">{{ $error ?? 'Your user account is not linked to a student profile. Please contact the administrator.' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            {{-- Main Dashboard Content (Only renders if Student exists) --}}
            
            <div class="row page-titles mx-0">
                <div class="col-sm-6 p-md-0">
                    <div class="welcome-text">
                        <h5 class="text-primary">{{ __('dashboard.student_dashboard') }}</h5>
                        <p class="mb-0 fs-12">{{ __('dashboard.welcome_back') }}, {{ Auth::user()->name }}</p>
                    </div>
                </div>
            </div>

            <div class="row">
                {{-- 1. My Fees --}}
                <div class="col-xl-4 col-sm-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <span class="me-3 text-muted">
                                    <i class="la la-money fs-24"></i>
                                </span>
                                <div>
                                    <p class="mb-0 fs-12 text-muted">{{ __('dashboard.my_fees') }}</p>
                                    <h5 class="mb-0 text-dark">${{ number_format($paidFees ?? 0, 2) }} <small class="text-muted fs-10">Paid</small></h5>
                                    <small class="text-muted fs-11">Rest: ${{ number_format($unpaidInvoices ?? 0, 2) }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. My Results --}}
                <div class="col-xl-4 col-sm-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <span class="me-3 text-muted">
                                    <i class="la la-graduation-cap fs-24"></i>
                                </span>
                                <div>
                                    <p class="mb-0 fs-12 text-muted">{{ __('dashboard.my_results') }}</p>
                                    <h5 class="mb-0 text-dark">{{ $resultsCount ?? 0 }}</h5>
                                    <small class="text-muted fs-11">{{ __('dashboard.published_exams') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 3. Attendance --}}
                <div class="col-xl-4 col-sm-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <span class="me-3 text-muted">
                                    <i class="la la-calendar-check-o fs-24"></i>
                                </span>
                                <div>
                                    <p class="mb-0 fs-12 text-muted">{{ __('dashboard.attendance') }}</p>
                                    <h5 class="mb-0 text-dark">{{ $attendancePercentage ?? 0 }}%</h5>
                                    <small class="text-muted fs-11">{{ __('dashboard.present') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                {{-- Timetable --}}
                <div class="col-xl-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header border-0 pb-0 pt-3">
                            <h6 class="card-title fs-14">{{ __('dashboard.todays_schedule') }}</h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="fs-12">{{ __('dashboard.time') }}</th>
                                            <th class="fs-12">{{ __('dashboard.subject') }}</th>
                                            <th class="fs-12">{{ __('dashboard.teacher') }}</th>
                                            <th class="fs-12">{{ __('dashboard.room') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($todayClasses as $class)
                                            <tr>
                                                <td class="fs-12">{{ $class->start_time->format('H:i') }} - {{ $class->end_time->format('H:i') }}</td>
                                                <td class="fs-12 fw-bold">{{ $class->subject->name }}</td>
                                                <td class="fs-12">{{ $class->teacher->user->name ?? 'N/A' }}</td>
                                                <td class="fs-12">{{ $class->room_number ?? '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted fs-12 py-3">{{ __('dashboard.no_classes_today') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Profile --}}
                <div class="col-xl-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center p-3">
                            <div class="profile-photo mb-2">
                                @if($student->student_photo)
                                    <img src="{{ asset('storage/'.$student->student_photo) }}" class="rounded-circle" width="60" height="60" style="object-fit:cover;">
                                @else
                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto" style="width: 60px; height: 60px;">
                                        <span class="fs-20 fw-bold text-muted">{{ substr($student->first_name, 0, 1) }}</span>
                                    </div>
                                @endif
                            </div>
                            <h5 class="mb-1 fs-14">{{ $student->full_name }}</h5>
                            <p class="mb-0 text-muted fs-11">{{ $student->admission_number }}</p>
                            <p class="text-muted fs-11 mb-0">{{ $student->enrollments->last()->classSection->name ?? '' }}</p>
                        </div>
                    </div>
                </div>
            </div>

        @endif

    </div>
</div>
@endsection