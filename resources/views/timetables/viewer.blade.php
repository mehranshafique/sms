@extends('layout.layout')

@section('styles')
<style>
    /* ... (Same styles as before) ... */
    .routine-table th, .routine-table td {
        vertical-align: middle;
        border-color: #eee;
    }
    .day-cell {
        background-color: #f8f9fa;
        color: #333;
        font-weight: bold;
        text-transform: uppercase;
        border-right: 1px solid #dee2e6;
    }
    .slot-container {
        background-color: #fff;
    }
    .slot-card {
        background: #fdfdfd;
        border: 1px solid #f2f2f2;
        transition: all 0.2s;
    }
    .slot-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.05);
    }
    [data-theme-version="dark"] .card {
        background-color: #1e1e1e !important;
        color: #fff;
    }
    [data-theme-version="dark"] .routine-table thead th {
        background-color: #2c2c2c !important;
        color: #fff !important;
        border-color: #444 !important;
    }
    [data-theme-version="dark"] .day-cell {
        background-color: #2c2c2c !important;
        color: #ddd !important;
        border-right: 1px solid #444 !important;
        border-bottom: 1px solid #444 !important;
    }
    [data-theme-version="dark"] .slot-container {
        background-color: #1e1e1e !important;
        border-color: #444 !important;
    }
    [data-theme-version="dark"] .slot-card {
        background-color: #2a2a2a !important;
        border-color: #444 !important;
    }
    [data-theme-version="dark"] .text-dark {
        color: #e0e0e0 !important;
    }
    [data-theme-version="dark"] .text-muted {
        color: #999 !important;
    }
</style>
@endsection

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('timetable.class_routine') }}</h4>
                    <p class="mb-0">{{ __('timetable.manage_list_subtitle') }}</p>
                </div>
            </div>
        </div>

        {{-- Filter Card (HIDDEN for Staff/Student to enforce strict view) --}}
        @if(!auth()->user()->hasRole(['Student', 'Staff']))
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-body p-3">
                        <form method="GET" action="{{ route('timetables.routine') }}" id="filterForm">
                            <div class="row align-items-end">
                                
                                {{-- Grade Filter --}}
                                <div class="col-md-3 mb-2">
                                    <label class="form-label mb-1">{{ __('grade_level.grade_name') }}</label>
                                    <select id="filter_grade" name="grade_id" class="form-control default-select form-control-sm">
                                        <option value="">-- {{ __('timetable.all_grades') }} --</option>
                                        @if(isset($gradeLevels))
                                            @foreach($gradeLevels as $id => $name)
                                                <option value="{{ $id }}" {{ (request('grade_id') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>

                                {{-- Class Filter --}}
                                <div class="col-md-3 mb-2">
                                    <label class="form-label mb-1">{{ __('timetable.select_class') }}</label>
                                    <select name="class_section_id" id="filter_class" class="form-control default-select form-control-sm">
                                        <option value="">-- {{ __('timetable.select_class') }} --</option>
                                    </select>
                                </div>

                                {{-- Teacher Filter --}}
                                <div class="col-md-2 mb-2">
                                    <label class="form-label mb-1">{{ __('timetable.select_teacher') }}</label>
                                    <select name="teacher_id" class="form-control default-select form-control-sm">
                                        <option value="">-- {{ __('timetable.select_teacher') }} --</option>
                                        @if(isset($teachers))
                                            @foreach($teachers as $id => $name)
                                                <option value="{{ $id }}" {{ (request('teacher_id') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>

                                {{-- Room Filter --}}
                                <div class="col-md-2 mb-2">
                                    <label class="form-label mb-1">{{ __('timetable.room') }}</label>
                                    <select name="room_number" class="form-control default-select form-control-sm">
                                        <option value="">-- {{ __('timetable.select_room') }} --</option>
                                        @if(isset($rooms))
                                            @foreach($rooms as $r)
                                                <option value="{{ $r }}" {{ (request('room_number') == $r) ? 'selected' : '' }}>{{ $r }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>

                                <div class="col-md-2 mb-2">
                                    <button type="submit" class="btn btn-primary w-100 btn-sm"><i class="fa fa-search"></i> {{ __('timetable.view') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if(isset($weeklySchedule))
            {{-- Info Header --}}
            <div class="row">
                <div class="col-xl-12">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body p-4 d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="font-w600 mb-1 text-dark">{{ $headerTitle ?? 'Timetable' }}</h3>
                                <div class="d-flex text-muted fs-14">
                                    <span><i class="fa fa-calendar-check-o me-1"></i> {{ $session->name ?? 'Current Session' }}</span>
                                    @if(isset($institution))
                                        <span class="ms-3"><i class="fa fa-university me-1"></i> {{ $institution->name }}</span>
                                    @endif
                                </div>
                            </div>
                            <div>
                                {{-- Use print_filtered route which correctly returns the print VIEW --}}
                                <a href="{{ route('timetables.print_filtered', request()->all()) }}" target="_blank" class="btn btn-outline-secondary btn-sm me-2">
                                    <i class="fa fa-print me-1"></i> {{ __('timetable.print_routine') }}
                                </a>
                                {{-- PDF Download (Optional, keeps old logic) --}}
                                @if(isset($timetable) && $timetable->id)
                                <a href="{{ route('timetables.download', $timetable->id) }}" class="btn btn-primary btn-sm">
                                    <i class="fa fa-download me-1"></i> {{ __('timetable.download_pdf') }}
                                </a>
                                @endif
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
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="py-3 text-uppercase fs-13 font-w700 text-center" style="width: 130px;">
                                                {{ __('timetable.day') }}
                                            </th>
                                            <th class="py-3 text-uppercase fs-13 font-w700 ps-4">
                                                {{ __('timetable.schedule') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day)
                                            <tr>
                                                {{-- Vertical Day Column --}}
                                                <td class="align-middle text-center p-3 day-cell">
                                                    {{ __('timetable.'.$day) }}
                                                </td>
                                                
                                                {{-- Horizontal Lectures --}}
                                                <td class="p-2 slot-container">
                                                    <div class="d-flex flex-wrap gap-2">
                                                        @if(isset($weeklySchedule[$day]) && $weeklySchedule[$day]->count() > 0)
                                                            @foreach($weeklySchedule[$day] as $slot)
                                                                <div class="p-2 text-start rounded border-start border-4 border-primary shadow-sm slot-card" style="min-width: 180px; max-width: 220px; flex: 1 0 auto; border-left-color: var(--primary) !important;">
                                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                                        <span class="badge badge-xs light badge-primary" style="font-size: 10px;">
                                                                            {{ $slot->start_time->format('H:i') }} - {{ $slot->end_time->format('H:i') }}
                                                                        </span>
                                                                        
                                                                        @can('timetable.update')
                                                                        <a href="{{ route('timetables.edit', $slot->id) }}" class="btn btn-xs text-primary p-0 ms-1" title="Edit"><i class="fa fa-pencil"></i></a>
                                                                        @endcan
                                                                    </div>
                                                                    <div class="fw-bold text-dark fs-12 mb-1 text-truncate" title="{{ $slot->subject->name }}">
                                                                        {{ $slot->subject->name }}
                                                                    </div>
                                                                    <div class="fs-11 text-muted d-flex align-items-center mb-1 text-truncate">
                                                                        <i class="fa fa-user me-1 text-info" style="font-size: 10px;"></i> 
                                                                        <span class="text-truncate" title="{{ $slot->teacher->user->name ?? 'N/A' }}">
                                                                            {{ $slot->teacher->user->name ?? 'N/A' }}
                                                                        </span>
                                                                    </div>
                                                                    
                                                                    {{-- Show Class Name if searching by teacher/room --}}
                                                                    @if(!request('class_section_id'))
                                                                        <div class="fs-11 text-muted d-flex align-items-center mb-1 text-truncate">
                                                                            <i class="fa fa-users me-1 text-success" style="font-size: 10px;"></i> 
                                                                            {{ $slot->classSection->name ?? 'N/A' }}
                                                                        </div>
                                                                    @endif

                                                                    @if($slot->room_number)
                                                                        <div class="fs-11 text-muted d-flex align-items-center">
                                                                            <i class="fa fa-map-marker me-1 text-danger" style="font-size: 10px;"></i> 
                                                                            {{ $slot->room_number }}
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        @else
                                                            <span class="text-muted fs-12 px-2 py-1 d-inline-block opacity-50">{{ __('timetable.no_classes') }}</span>
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
        @else
            <div class="alert alert-info text-center mt-4">Please select a Class, Teacher, or Room to view the schedule.</div>
        @endif

    </div>
</div>
@endsection

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- HELPER: Refresh UI Library ---
        function refreshSelect(element) {
            if (typeof $ !== 'undefined' && $(element).is('select') && $.fn.selectpicker) {
                 $(element).selectpicker('refresh');
            }
        }

        // --- FILTER LOGIC ---
        const gradeFilter = document.getElementById('filter_grade');
        const classFilter = document.getElementById('filter_class');
        // Get pre-selected class if any (from URL request)
        const oldClassId = "{{ request('class_section_id') }}";

        if(gradeFilter) {
            gradeFilter.addEventListener('change', function() {
                // 1. Reset Class Filter
                classFilter.innerHTML = '<option value="">{{ __('student.loading') }}</option>';
                classFilter.disabled = true;
                refreshSelect(classFilter);

                // 2. Fetch
                if(this.value) {
                    fetch(`{{ route('students.get_sections') }}?grade_id=${this.value}`)
                        .then(response => response.json())
                        .then(data => {
                            classFilter.innerHTML = '<option value="">-- {{ __('timetable.select_class') }} --</option>';
                            
                            Object.entries(data).forEach(([id, name]) => {
                                let option = new Option(name, id);
                                if(String(id) === String(oldClassId)) option.selected = true;
                                classFilter.add(option);
                            });
                            classFilter.disabled = false;
                            refreshSelect(classFilter);
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            classFilter.innerHTML = '<option value="">Error</option>';
                            refreshSelect(classFilter);
                        });
                } else {
                    // Reset to default
                    classFilter.innerHTML = '<option value="">-- {{ __('timetable.select_class') }} --</option>';
                    refreshSelect(classFilter);
                }
            });

            // 3. Trigger on load if value exists
            if(gradeFilter.value) {
                // Dispatch native change event so the listener above fires
                gradeFilter.dispatchEvent(new Event('change'));
            }
        }
    });
</script>
@endsection