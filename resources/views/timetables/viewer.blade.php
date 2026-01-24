@extends('layout.layout')

@section('styles')
<style>
    /* --- Filter Tabs Styling --- */
    .filter-tabs .nav-link {
        border-radius: 50px;
        padding: 8px 20px;
        font-weight: 600;
        color: #777;
        background: #fff;
        border: 1px solid #eee;
        margin-right: 10px;
        transition: all 0.3s;
    }
    .filter-tabs .nav-link.active {
        background-color: var(--primary);
        color: #fff;
        border-color: var(--primary);
        box-shadow: 0 4px 10px rgba(var(--primary-rgb), 0.3);
    }
    
    /* --- Schedule Grid Styling --- */
    .routine-table {
        border-collapse: separate;
        border-spacing: 0 10px; /* Gap between days */
        width: 100%;
    }
    .routine-table tr {
        background: transparent;
    }
    .routine-table td {
        border: none;
        vertical-align: top;
    }
    
    /* Day Column */
    .day-cell-wrapper {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.03);
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 20px 10px;
        min-width: 100px;
        border-left: 5px solid var(--primary);
    }
    .day-name {
        font-size: 16px;
        font-weight: 800;
        text-transform: uppercase;
        color: #333;
        margin-bottom: 5px;
    }
    .day-slots-count {
        font-size: 11px;
        color: #999;
        font-weight: 600;
    }

    /* Slots Container */
    .slots-wrapper {
        padding-left: 15px;
    }
    
    /* Slot Card */
    .slot-card {
        background: #fff;
        border-radius: 12px;
        padding: 15px;
        position: relative;
        transition: transform 0.2s, box-shadow 0.2s;
        border: 1px solid #f0f0f0;
        box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 100%;
        min-width: 200px;
        max-width: 240px;
        flex: 1 0 auto;
    }
    .slot-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        border-color: var(--primary);
    }
    
    /* Left Border Indicator based on Subject Name Hash (simulated via CSS classes if needed) */
    .slot-card { border-left: 4px solid #ddd; }
    .slot-card:nth-child(odd) { border-left-color: #6c5ce7; }
    .slot-card:nth-child(even) { border-left-color: #00cec9; }
    .slot-card:nth-child(3n) { border-left-color: #fd79a8; }

    .slot-time {
        font-size: 12px;
        font-weight: 700;
        color: #888;
        background: #f8f9fa;
        padding: 4px 8px;
        border-radius: 6px;
        display: inline-block;
        margin-bottom: 10px;
    }
    .slot-subject {
        font-size: 15px;
        font-weight: 700;
        color: #333;
        margin-bottom: 5px;
        line-height: 1.3;
    }
    .slot-detail {
        font-size: 12px;
        color: #666;
        display: flex;
        align-items: center;
        margin-bottom: 3px;
    }
    .slot-detail i {
        width: 20px;
        color: #aaa;
    }
    .slot-actions {
        margin-top: 12px;
        padding-top: 10px;
        border-top: 1px dashed #eee;
        display: flex;
        justify-content: flex-end;
    }

    /* Dark Mode */
    [data-theme-version="dark"] .day-cell-wrapper,
    [data-theme-version="dark"] .slot-card {
        background: #1e1e1e;
        border-color: #333;
    }
    [data-theme-version="dark"] .day-name,
    [data-theme-version="dark"] .slot-subject {
        color: #fff;
    }
</style>
@endsection

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        {{-- Header --}}
        <div class="row page-titles mx-0 mb-4 p-4 bg-white rounded shadow-sm align-items-center">
            <div class="col-sm-6 p-0">
                <div class="welcome-text">
                    <h4 class="text-primary fw-bold">{{ __('timetable.class_routine') }}</h4>
                    <p class="mb-0 text-muted">{{ __('timetable.manage_list_subtitle') }}</p>
                </div>
            </div>
        </div>

        {{-- Filter Section (Tabs for Admin/Teacher) --}}
        @if(!auth()->user()->hasRole(['Student']))
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0" style="border-radius: 15px;">
                    <div class="card-body">
                        
                        {{-- 1. Filter Tabs --}}
                        <ul class="nav nav-pills filter-tabs mb-4 justify-content-center" id="filterTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="tab-class" data-bs-toggle="pill" data-bs-target="#pills-class" type="button" role="tab">
                                    <i class="fa fa-users me-2"></i> By Class
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab-teacher" data-bs-toggle="pill" data-bs-target="#pills-teacher" type="button" role="tab">
                                    <i class="fa fa-user me-2"></i> By Teacher
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab-room" data-bs-toggle="pill" data-bs-target="#pills-room" type="button" role="tab">
                                    <i class="fa fa-map-marker me-2"></i> By Room
                                </button>
                            </li>
                        </ul>

                        <form method="GET" action="{{ route('timetables.routine') }}" id="filterForm">
                            <div class="tab-content" id="pills-tabContent">
                                
                                {{-- Tab: By Class --}}
                                <div class="tab-pane fade show active" id="pills-class" role="tabpanel">
                                    <div class="row justify-content-center align-items-end">
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label fw-bold">{{ __('grade_level.grade_name') }}</label>
                                            <select id="filter_grade" class="form-control default-select">
                                                <option value="">-- {{ __('timetable.all_grades') }} --</option>
                                                @if(isset($gradeLevels))
                                                    @foreach($gradeLevels as $id => $name)
                                                        <option value="{{ $id }}" {{ (request('grade_id') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label fw-bold">{{ __('timetable.select_class') }}</label>
                                            <select name="class_section_id" id="filter_class" class="form-control default-select input-filter">
                                                <option value="">-- {{ __('timetable.select_class') }} --</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <button type="submit" class="btn btn-primary w-100"><i class="fa fa-search me-2"></i> {{ __('timetable.view') }}</button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Tab: By Teacher --}}
                                <div class="tab-pane fade" id="pills-teacher" role="tabpanel">
                                    <div class="row justify-content-center align-items-end">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label fw-bold">{{ __('timetable.select_teacher') }}</label>
                                            <select name="teacher_id" class="form-control default-select input-filter" data-live-search="true">
                                                <option value="">-- {{ __('timetable.select_teacher') }} --</option>
                                                @if(isset($teachers))
                                                    @foreach($teachers as $id => $name)
                                                        <option value="{{ $id }}" {{ (request('teacher_id') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <button type="submit" class="btn btn-primary w-100"><i class="fa fa-search me-2"></i> {{ __('timetable.view') }}</button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Tab: By Room --}}
                                <div class="tab-pane fade" id="pills-room" role="tabpanel">
                                    <div class="row justify-content-center align-items-end">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label fw-bold">{{ __('timetable.room') }}</label>
                                            <select name="room_number" class="form-control default-select input-filter" data-live-search="true">
                                                <option value="">-- {{ __('timetable.select_room') }} --</option>
                                                @if(isset($rooms))
                                                    @foreach($rooms as $r)
                                                        <option value="{{ $r }}" {{ (request('room_number') == $r) ? 'selected' : '' }}>{{ $r }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <button type="submit" class="btn btn-primary w-100"><i class="fa fa-search me-2"></i> {{ __('timetable.view') }}</button>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Schedule Display --}}
        @if(isset($weeklySchedule))
            
            {{-- Context Info --}}
            <div class="row">
                <div class="col-12 text-center mb-4">
                    <h3 class="fw-bold mb-1">{{ $headerTitle ?? 'Timetable' }}</h3>
                    <div class="d-inline-flex align-items-center bg-white px-4 py-2 rounded-pill shadow-sm text-muted fs-14">
                        <span class="me-3"><i class="fa fa-calendar-check-o me-2 text-primary"></i> {{ $session->name ?? 'Active Session' }}</span>
                        @if(isset($institution))
                            <span><i class="fa fa-university me-2 text-primary"></i> {{ $institution->name }}</span>
                        @endif
                        
                        <div class="vr mx-3"></div>
                        
                        <a href="{{ route('timetables.print_filtered', request()->all()) }}" target="_blank" class="text-secondary text-decoration-none">
                            <i class="fa fa-print me-1"></i> Print
                        </a>
                    </div>
                </div>
            </div>

            {{-- The Grid --}}
            <div class="row">
                <div class="col-12">
                    <table class="routine-table">
                        <tbody>
                            @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day)
                                @php 
                                    $slots = $weeklySchedule[$day] ?? collect(); 
                                @endphp
                                @if($slots->count() > 0 || !auth()->user()->hasRole(['Student'])) {{-- Hide empty days for students if preferred, but usually good to show all --}}
                                <tr>
                                    {{-- Day Column --}}
                                    <td width="150">
                                        <div class="day-cell-wrapper">
                                            <span class="day-name">{{ substr($day, 0, 3) }}</span>
                                            <span class="day-slots-count">{{ $slots->count() }} Classes</span>
                                        </div>
                                    </td>
                                    
                                    {{-- Slots Column --}}
                                    <td class="slots-wrapper">
                                        <div class="d-flex flex-wrap gap-3 pb-3">
                                            @if($slots->count() > 0)
                                                @foreach($slots as $slot)
                                                    <div class="slot-card">
                                                        <div>
                                                            <span class="slot-time">
                                                                <i class="far fa-clock me-1"></i> {{ $slot->start_time->format('H:i') }} - {{ $slot->end_time->format('H:i') }}
                                                            </span>
                                                            <div class="slot-subject" title="{{ $slot->subject->name }}">
                                                                {{ Str::limit($slot->subject->name, 25) }}
                                                            </div>
                                                            
                                                            {{-- Details --}}
                                                            <div class="mt-3">
                                                                {{-- If filtered by Teacher/Room, show Class Name --}}
                                                                @if(!request('class_section_id') && !auth()->user()->hasRole('Student'))
                                                                    <div class="slot-detail" title="Class">
                                                                        <i class="fa fa-users"></i>
                                                                        <span class="fw-bold text-primary">{{ $slot->classSection->name ?? 'N/A' }}</span>
                                                                    </div>
                                                                @endif

                                                                {{-- If filtered by Class, show Teacher --}}
                                                                @if(!request('teacher_id'))
                                                                    <div class="slot-detail" title="Teacher">
                                                                        <i class="fa fa-user-circle"></i>
                                                                        <span>{{ Str::limit($slot->teacher->user->name ?? 'N/A', 20) }}</span>
                                                                    </div>
                                                                @endif

                                                                @if($slot->room_number)
                                                                    <div class="slot-detail" title="Room">
                                                                        <i class="fa fa-map-marker"></i>
                                                                        <span>{{ $slot->room_number }}</span>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        {{-- Admin Actions --}}
                                                        @can('timetable.update')
                                                        <div class="slot-actions">
                                                            <a href="{{ route('timetables.edit', $slot->id) }}" class="btn btn-xs btn-outline-primary rounded-pill px-3">
                                                                Edit
                                                            </a>
                                                        </div>
                                                        @endcan
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="d-flex align-items-center justify-content-center w-100 h-100 text-muted" style="min-height: 100px; background: #fafafa; border-radius: 12px; border: 2px dashed #eee;">
                                                    <small>{{ __('timetable.no_classes') }}</small>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            {{-- Empty State --}}
            <div class="row mt-5">
                <div class="col-12 text-center">
                    <div class="mb-4">
                        <img src="{{ asset('images/empty_calendar.svg') }}" alt="Select Filter" style="width: 150px; opacity: 0.5;">
                    </div>
                    <h4 class="text-muted">Please select a filter above to view the schedule.</h4>
                </div>
            </div>
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

        // --- TAB LOGIC: Clear inputs of hidden tabs to prevent mixed filters ---
        const tabs = document.querySelectorAll('button[data-bs-toggle="pill"]');
        const form = document.getElementById('filterForm');

        tabs.forEach(tab => {
            tab.addEventListener('show.bs.tab', function (event) {
                // Find target pane
                const targetId = event.target.getAttribute('data-bs-target');
                
                // Clear all inputs in the form
                const allInputs = form.querySelectorAll('.input-filter');
                allInputs.forEach(input => {
                    input.value = ''; // Reset value
                    if($(input).is('select') && $.fn.selectpicker) {
                        $(input).selectpicker('refresh');
                    }
                });

                // (Optional) Reset Grade if needed, or keep it persistent
            });
        });

        // Set Active Tab based on URL params on Page Load
        const params = new URLSearchParams(window.location.search);
        if (params.has('teacher_id')) {
            new bootstrap.Tab(document.querySelector('#tab-teacher')).show();
        } else if (params.has('room_number')) {
            new bootstrap.Tab(document.querySelector('#tab-room')).show();
        } else {
            new bootstrap.Tab(document.querySelector('#tab-class')).show();
        }

        // --- FILTER LOGIC (Grade -> Class) ---
        const gradeFilter = document.getElementById('filter_grade');
        const classFilter = document.getElementById('filter_class');
        const oldClassId = "{{ request('class_section_id') }}";

        if(gradeFilter) {
            gradeFilter.addEventListener('change', function() {
                classFilter.innerHTML = '<option value="">{{ __('student.loading') }}</option>';
                classFilter.disabled = true;
                refreshSelect(classFilter);

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
                    classFilter.innerHTML = '<option value="">-- {{ __('timetable.select_class') }} --</option>';
                    refreshSelect(classFilter);
                }
            });

            // Trigger on load if value exists
            if(gradeFilter.value) {
                gradeFilter.dispatchEvent(new Event('change'));
            }
        }
    });
</script>
@endsection