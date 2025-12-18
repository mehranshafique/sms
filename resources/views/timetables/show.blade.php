@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        {{-- Header & Breadcrumb --}}
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('timetable.class_routine') }}</h4>
                    <p class="mb-0">{{ $timetable->classSection->name }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('timetables.index') }}">{{ __('timetable.timetable_list') }}</a></li>
                    <li class="breadcrumb-item active"><a href="javascript:void(0)">{{ $timetable->classSection->name }}</a></li>
                </ol>
            </div>
        </div>

        {{-- Top Info Card --}}
        <div class="row">
            <div class="col-xl-12">
                <div class="card bg-primary text-white shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h3 class="text-white font-w600 mb-1">{{ $timetable->classSection->name }}</h3>
                                <p class="mb-0 op-8">
                                    <i class="fa fa-university me-2"></i> {{ $timetable->institution->name }} 
                                    <span class="mx-2">|</span> 
                                    <i class="fa fa-calendar-check-o me-2"></i> {{ $timetable->academicSession->name }}
                                </p>
                            </div>
                            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                @if($timetable->classSection->classTeacher)
                                    <span class="d-block text-white fs-14">{{ __('timetable.class_teacher') }}</span>
                                    <h5 class="text-white">{{ $timetable->classSection->classTeacher->user->name }}</h5>
                                @else
                                    <span class="badge badge-warning text-dark">{{ __('timetable.no_teacher_assigned') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Weekly Grid --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-0 pb-0 d-flex justify-content-between">
                        <h4 class="card-title">{{ __('timetable.weekly_schedule') }}</h4>
                        <button class="btn btn-outline-primary btn-sm" onclick="window.print()">
                            <i class="fa fa-print me-1"></i> {{ __('timetable.print_routine') }}
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="row gx-3">
                            @foreach($weeklySchedule as $day => $slots)
                                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-4">
                                    <div class="card h-100 border bg-light">
                                        <div class="card-header bg-white border-bottom py-2 text-center d-block">
                                            <h5 class="text-primary mb-0 text-uppercase fs-14 fw-bold">{{ __('timetable.'.$day) }}</h5>
                                        </div>
                                        <div class="card-body p-2">
                                            @forelse($slots as $slot)
                                                <div class="card mb-2 shadow-sm border-0 {{ ($slot->id == $timetable->id) ? 'border-primary border-2' : '' }}">
                                                    <div class="card-body p-3">
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <span class="badge badge-xs light badge-primary">{{ $slot->start_time->format('H:i') }} - {{ $slot->end_time->format('H:i') }}</span>
                                                        </div>
                                                        <h6 class="fs-14 font-w600 mb-1 text-black">
                                                            {{ $slot->subject->name }}
                                                        </h6>
                                                        <div class="d-flex align-items-center fs-12 text-muted">
                                                            <i class="fa fa-user me-1"></i> {{ $slot->teacher->user->name ?? 'N/A' }}
                                                        </div>
                                                        @if($slot->room_number)
                                                            <div class="d-flex align-items-center fs-12 text-muted mt-1">
                                                                <i class="fa fa-map-marker me-1"></i> {{ $slot->room_number }}
                                                            </div>
                                                        @endif
                                                        
                                                        @can('timetable.update')
                                                        <div class="text-end mt-2 border-top pt-2">
                                                            <a href="{{ route('timetables.edit', $slot->id) }}" class="btn btn-xs btn-primary sharp"><i class="fa fa-pencil"></i></a>
                                                        </div>
                                                        @endcan
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="text-center py-4 text-muted fs-12">
                                                    {{ __('timetable.no_classes') }}
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection