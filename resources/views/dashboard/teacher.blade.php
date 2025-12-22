@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('dashboard.teacher_dashboard') }}</h4>
                    <p class="mb-0">{{ __('dashboard.welcome_back') }}, {{ Auth::user()->name }}</p>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- 1. My Courses --}}
            <div class="col-xl-4 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-primary text-primary"><i class="la la-book"></i></span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('dashboard.my_courses') }}</p>
                                <h4 class="mb-0">{{ $myCoursesCount }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. My Students --}}
            <div class="col-xl-4 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-warning text-warning"><i class="la la-users"></i></span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('dashboard.my_students') }}</p>
                                <h4 class="mb-0">{{ $myStudentsCount }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. Today's Classes --}}
            <div class="col-xl-4 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-info text-info"><i class="la la-calendar-check-o"></i></span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('dashboard.todays_classes') }}</p>
                                <h4 class="mb-0">{{ $todayClasses->count() }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Schedule & Quick Actions --}}
        <div class="row">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title">{{ __('dashboard.my_timetable') }} ({{ date('l') }})</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-responsive-sm">
                                <thead class="thead-light">
                                    <tr>
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
                                            <td><span class="text-primary fw-bold">{{ $class->start_time->format('H:i') }}</span></td>
                                            <td>{{ $class->classSection->name }}</td>
                                            <td>{{ $class->subject->name }}</td>
                                            <td>{{ $class->room_number ?? '-' }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('marks.create', ['class_section_id' => $class->class_section_id]) }}" class="btn btn-xs btn-outline-primary">{{ __('dashboard.marks') }}</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">{{ __('dashboard.no_classes_today') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card bg-secondary text-white">
                    <div class="card-body">
                        <div class="media align-items-center">
                            <span class="me-4">
                                <i class="la la-envelope" style="font-size: 2.5rem;"></i>
                            </span>
                            <div class="media-body">
                                <h3 class="text-white">{{ __('dashboard.communication') }}</h3>
                                <p class="mb-0 text-white opacity-75">Send SMS/Email to Parents</p>
                            </div>
                        </div>
                        <a href="#" class="btn btn-light btn-sm mt-3 w-100 text-secondary fw-bold">{{ __('dashboard.open_center') }}</a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection