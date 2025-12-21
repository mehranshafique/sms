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
                <div class="card bg-primary text-white shadow-sm border-0 mb-4">
                    <div class="card-body p-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="text-white font-w600 mb-1">{{ $timetable->classSection->name }}</h3>
                            <div class="d-flex text-white opacity-75 fs-14">
                                <span class="me-3"><i class="fa fa-university me-1"></i> {{ $timetable->institution->name }}</span>
                                <span><i class="fa fa-calendar-check-o me-1"></i> {{ $timetable->academicSession->name }}</span>
                            </div>
                        </div>
                        <div>
                            <a href="{{ route('timetables.print', $timetable->id) }}" target="_blank" class="btn btn-light text-primary btn-sm me-2">
                                <i class="fa fa-print me-1"></i> {{ __('timetable.print_routine') }}
                            </a>
                            <a href="{{ route('timetables.download', $timetable->id) }}" class="btn btn-light text-primary btn-sm">
                                <i class="fa fa-download me-1"></i> {{ __('timetable.download_pdf') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Horizontal Weekly Grid --}}
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0 routine-table" style="border-collapse: collapse;">
                                <thead>
                                    <tr>
                                        <th class="py-3 text-uppercase fs-13 font-w700 text-center" style="width: 120px; border-bottom: 2px solid var(--border-color);">
                                            {{ __('timetable.day') }}
                                        </th>
                                        <th class="py-3 text-uppercase fs-13 font-w700 ps-4" style="border-bottom: 2px solid var(--border-color);">
                                            {{ __('timetable.schedule') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day)
                                        <tr>
                                            {{-- Vertical Day Column --}}
                                            <td class="align-middle text-center fw-bold text-uppercase text-primary fs-13 p-3" style="border-right: 1px solid var(--border-color); background-color: var(--card-bg);">
                                                {{ __('timetable.'.$day) }}
                                            </td>
                                            
                                            {{-- Horizontal Lectures --}}
                                            <td class="p-2">
                                                <div class="d-flex flex-wrap gap-2">
                                                    @if(isset($weeklySchedule[$day]) && $weeklySchedule[$day]->count() > 0)
                                                        @foreach($weeklySchedule[$day] as $slot)
                                                            <div class="p-2 text-start rounded border border-primary shadow-sm {{ $slot->id == $timetable->id ? 'bg-primary-light' : '' }}" style="min-width: 180px; max-width: 200px; flex: 1 0 auto; background-color: var(--card-bg);">
                                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                                    <span class="badge badge-xs light badge-primary" style="font-size: 10px;">
                                                                        {{ $slot->start_time->format('H:i') }} - {{ $slot->end_time->format('H:i') }}
                                                                    </span>
                                                                    
                                                                    @can('timetable.update')
                                                                    <a href="{{ route('timetables.edit', $slot->id) }}" class="btn btn-xs text-primary p-0 ms-1" title="Edit"><i class="fa fa-pencil"></i></a>
                                                                    @endcan
                                                                </div>
                                                                <div class="fw-bold fs-12 mb-1 text-truncate" title="{{ $slot->subject->name }}">
                                                                    {{ $slot->subject->name }}
                                                                </div>
                                                                <div class="fs-11 text-muted d-flex align-items-center mb-1 text-truncate">
                                                                    <i class="fa fa-user me-1 text-info" style="font-size: 10px;"></i> 
                                                                    <span class="text-truncate" title="{{ $slot->teacher->user->name ?? 'N/A' }}">
                                                                        {{ $slot->teacher->user->name ?? 'N/A' }}
                                                                    </span>
                                                                </div>
                                                                @if($slot->room_number)
                                                                    <div class="fs-11 text-muted d-flex align-items-center">
                                                                        <i class="fa fa-map-marker me-1 text-danger" style="font-size: 10px;"></i> 
                                                                        {{ $slot->room_number }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    @else
                                                        <span class="text-muted fs-12 px-2 py-1 rounded d-inline-block">{{ __('timetable.no_classes') }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
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