<form action="{{ isset($timetable) ? route('timetables.update', $timetable->id) : route('timetables.store') }}" method="POST" id="timetableForm">
    @csrf
    @if(isset($timetable))
        @method('PUT')
    @endif

    <div class="row">
        <div class="col-xl-8 col-lg-8">
            <div class="card">
                <div class="card-header border-0 pb-0">
                    <h4 class="card-title">{{ __('timetable.basic_information') }}</h4>
                </div>
                <div class="card-body">
                    <div class="basic-form">
                        <div class="row">
                            
                            @php
                                $hasContext = isset($institutionId) && $institutionId;
                                $isSuperAdmin = auth()->user()->hasRole('Super Admin');
                            @endphp

                            @if($hasContext && !$isSuperAdmin)
                                <input type="hidden" name="institution_id" value="{{ $institutionId }}">
                            @else
                                <div class="mb-3 col-md-12">
                                    <label class="form-label">{{ __('academic_session.select_institution') }} <span class="text-danger">*</span></label>
                                    <select name="institution_id" class="form-control default-select" required>
                                        <option value="">-- Select Institution --</option>
                                        @foreach($institutions as $id => $name)
                                            <option value="{{ $id }}" {{ (old('institution_id', $timetable->institution_id ?? ($hasContext ? $institutionId : '')) == $id) ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            {{-- Grade Level --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('grade_level.grade_name') }} <span class="text-danger">*</span></label>
                                <select name="grade_level_id" id="gradeSelect" class="form-control default-select" data-live-search="true" required>
                                    <option value="">-- Select Grade --</option>
                                    @if(isset($gradeLevels))
                                        @foreach($gradeLevels as $id => $name)
                                            @php
                                                $selected = old('grade_level_id', isset($timetable) ? $timetable->classSection->grade_level_id : '') == $id;
                                            @endphp
                                            <option value="{{ $id }}" {{ $selected ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            {{-- Class Section --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('timetable.select_class') }} <span class="text-danger">*</span></label>
                                <select name="class_section_id" id="classSelect" class="form-check-input default-select" required disabled>
                                    <option value="">{{ __('timetable.select_class_first') }}</option>
                                </select>
                            </div>
                            
                            {{-- Day --}}
                            <div class="mb-3 col-md-12">
                                <label class="form-label">{{ __('timetable.select_day') }} <span class="text-danger">*</span></label>
                                <div class="d-flex flex-wrap gap-2" id="daySelector">
                                    @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day)
                                        <input type="radio" class="btn-check" name="day" id="day_{{ $day }}" value="{{ $day }}" {{ (old('day', isset($timetable) ? $timetable->day_of_week : '') == $day) ? 'checked' : '' }} required>
                                        <label class="btn btn-outline-primary btn-sm text-capitalize" for="day_{{ $day }}">{{ substr($day, 0, 3) }}</label>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Subject --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('timetable.select_subject') }} <span class="text-danger">*</span></label>
                                <select name="subject_id" id="subjectSelect" class="form-control default-select" data-live-search="true" required disabled>
                                    <option value="">{{ __('timetable.select_class_first') }}</option>
                                </select>
                            </div>

                            {{-- Teacher (Read Only) --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('timetable.select_teacher') }}</label>
                                <input type="text" id="teacherNameDisplay" class="form-control bg-light" readonly placeholder="{{ __('timetable.no_teacher_assigned') }}">
                                <input type="hidden" name="staff_id" id="staff_id_hidden">
                                <small id="teacherWarning" class="text-danger d-none">{{ __('timetable.assign_teacher_first') }}</small>
                            </div>

                            {{-- Room --}}
                            <div class="mb-3 col-md-12">
                                <label class="form-label">{{ __('timetable.room_number') }}</label>
                                <input type="text" name="room_number" id="roomInput" class="form-control" value="{{ old('room_number', isset($timetable) ? $timetable->room_number : '') }}" placeholder="{{ __('timetable.enter_room') }}">
                            </div>

                            {{-- Time Slots --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('timetable.start_time') }} <span class="text-danger">*</span></label>
                                <div class="input-group clockpicker">
                                    <input type="text" name="start_time" id="startTimeInput" class="form-control timepicker" value="{{ old('start_time', (isset($timetable) && $timetable->start_time) ? $timetable->start_time->format('H:i') : '09:00') }}" required>
                                    <span class="input-group-text"><i class="far fa-clock"></i></span>
                                </div>
                            </div>

                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('timetable.end_time') }} <span class="text-danger">*</span></label>
                                <div class="input-group clockpicker">
                                    <input type="text" name="end_time" id="endTimeInput" class="form-control timepicker" value="{{ old('end_time', (isset($timetable) && $timetable->end_time) ? $timetable->end_time->format('H:i') : '10:00') }}" required>
                                    <span class="input-group-text"><i class="far fa-clock"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary" id="saveBtn">{{ isset($timetable) ? __('timetable.update_routine') : __('timetable.save_routine') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- VISUAL SCHEDULE / AVAILABILITY CHECK --}}
        <div class="col-xl-4 col-lg-4">
            <div class="card h-auto">
                <div class="card-header border-0 pb-0">
                    <h4 class="card-title text-primary"><i class="fa fa-calendar-o me-2"></i> Schedule</h4>
                </div>
                <div class="card-body">
                    <div id="scheduleLoader" class="text-center d-none">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    
                    {{-- Busy Slots Container --}}
                    <h6 class="text-muted text-uppercase fs-12 font-w600 mt-2">Reserved Slots</h6>
                    <div id="scheduleVisuals" class="timeline-visuals mb-4">
                        <p class="text-muted small text-center">Select Class, Teacher, Room & Day to see reservations.</p>
                    </div>
                    
                    {{-- Available Slots Container (NEW) --}}
                    <h6 class="text-success text-uppercase fs-12 font-w600 mt-3"><i class="fa fa-check-circle me-1"></i> Available Slots</h6>
                    <div id="availableSlots" class="d-flex flex-wrap gap-2">
                        <p class="text-muted small">Select criteria to see suggestions.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
    .timeline-item {
        border-left: 3px solid #ddd;
        padding-left: 15px;
        margin-bottom: 15px;
        position: relative;
    }
    .timeline-item::before {
        content: '';
        width: 10px;
        height: 10px;
        background: #ddd;
        border-radius: 50%;
        position: absolute;
        left: -6.5px;
        top: 0;
    }
    .timeline-item.conflict-Class { border-color: #ffb822; }
    .timeline-item.conflict-Class::before { background: #ffb822; }
    
    .timeline-item.conflict-Teacher { border-color: #f64e60; }
    .timeline-item.conflict-Teacher::before { background: #f64e60; }
    
    .timeline-item.conflict-Room { border-color: #8950fc; }
    .timeline-item.conflict-Room::before { background: #8950fc; }

    .time-badge { font-weight: bold; font-size: 0.9em; display: block; margin-bottom: 2px; }
    .slot-info { font-size: 0.85em; color: #666; }
    
    .btn-suggestion {
        font-size: 11px;
        padding: 5px 10px;
        border-radius: 20px;
        background-color: #e6fffa;
        color: #009975;
        border: 1px solid #009975;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-suggestion:hover {
        background-color: #009975;
        color: #fff;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        const schoolStartStr = "{{ $schoolStart ?? '08:00' }}";
        const schoolEndStr = "{{ $schoolEnd ?? '15:00' }}";

        function refreshSelect(element) {
            if (typeof $ !== 'undefined' && $(element).is('select')) {
                if ($.fn.selectpicker) {
                     $(element).selectpicker('refresh');
                }
            }
        }

        const gradeSelect = document.getElementById('gradeSelect');
        const classSelect = document.getElementById('classSelect');
        const subjectSelect = document.getElementById('subjectSelect');
        const teacherDisplay = document.getElementById('teacherNameDisplay');
        const teacherHidden = document.getElementById('staff_id_hidden');
        const teacherWarning = document.getElementById('teacherWarning');
        const roomInput = document.getElementById('roomInput');
        const saveBtn = document.getElementById('saveBtn');
        const dayInputs = document.querySelectorAll('input[name="day"]');
        const startInput = document.getElementById('startTimeInput');
        const endInput = document.getElementById('endTimeInput');
        
        const oldClassId = "{{ old('class_section_id', isset($timetable) ? $timetable->class_section_id : '') }}";
        const oldSubjectId = "{{ old('subject_id', isset($timetable) ? $timetable->subject_id : '') }}";
        const editTeacherName = "{{ isset($timetable) && $timetable->teacher ? $timetable->teacher->user->name : '' }}";
        const editTeacherId = "{{ isset($timetable) ? $timetable->teacher_id : '' }}";

        if(editTeacherId) {
            teacherHidden.value = editTeacherId;
            teacherDisplay.value = editTeacherName;
        }

        // --- TIME HELPER FUNCTIONS ---
        function timeToMinutes(timeStr) {
            const [h, m] = timeStr.split(':').map(Number);
            return h * 60 + m;
        }

        function minutesToTime(minutes) {
            const h = Math.floor(minutes / 60);
            const m = minutes % 60;
            return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
        }

        // --- CHECK AVAILABILITY LOGIC ---
        function checkAvailability() {
            const day = document.querySelector('input[name="day"]:checked')?.value;
            const classId = classSelect.value;
            const staffId = teacherHidden.value;
            const room = roomInput.value;

            const loader = document.getElementById('scheduleLoader');
            const container = document.getElementById('scheduleVisuals');
            const suggestions = document.getElementById('availableSlots');
            
            container.innerHTML = '';
            suggestions.innerHTML = '';

            if (!day || (!classId && !staffId && !room)) return;

            loader.classList.remove('d-none');
            
            const params = new URLSearchParams({
                day: day,
                class_section_id: classId,
                staff_id: staffId,
                room_number: room
            });

            fetch(`{{ route('timetables.check_availability') }}?${params.toString()}`)
                .then(res => res.json())
                .then(slots => {
                    loader.classList.add('d-none');
                    let busyIntervals = [];

                    if(slots.length === 0) {
                        container.innerHTML = '<div class="text-muted small text-center mb-3">No reservations found.</div>';
                    } else {
                        let html = '';
                        slots.forEach(slot => {
                            // Store interval for calculation
                            busyIntervals.push([timeToMinutes(slot.start_time), timeToMinutes(slot.end_time)]);

                            let conflictClass = slot.conflicts.length > 0 ? `conflict-${slot.conflicts[0]}` : '';
                            let conflictBadges = slot.conflicts.map(c => {
                                let color = c === 'Teacher' ? 'danger' : (c === 'Class' ? 'warning text-dark' : 'info');
                                return `<span class="badge badge-xs badge-${color} me-1">${c} Busy</span>`;
                            }).join('');

                            html += `
                            <div class="timeline-item ${conflictClass}">
                                <span class="time-badge">${slot.start_time} - ${slot.end_time}</span>
                                <div class="slot-info">
                                    <strong>${slot.subject}</strong>
                                    <div class="mt-1">${conflictBadges}</div>
                                </div>
                            </div>`;
                        });
                        container.innerHTML = html;
                    }

                    // --- CALCULATE GREEN SLOTS ---
                    calculateFreeSlots(busyIntervals);
                })
                .catch(err => {
                    loader.classList.add('d-none');
                    container.innerHTML = '<p class="text-danger small">Error checking availability.</p>';
                });
        }

        function calculateFreeSlots(busyIntervals) {
            const suggestions = document.getElementById('availableSlots');
            const schoolStart = timeToMinutes(schoolStartStr);
            const schoolEnd = timeToMinutes(schoolEndStr);
            
            // Sort busy intervals by start time
            busyIntervals.sort((a, b) => a[0] - b[0]);

            let mergedBusy = [];
            if (busyIntervals.length > 0) {
                let current = busyIntervals[0];
                for (let i = 1; i < busyIntervals.length; i++) {
                    if (busyIntervals[i][0] < current[1]) { // Overlap
                        current[1] = Math.max(current[1], busyIntervals[i][1]);
                    } else {
                        mergedBusy.push(current);
                        current = busyIntervals[i];
                    }
                }
                mergedBusy.push(current);
            }

            // Find Gaps
            let freeSlots = [];
            let currentTime = schoolStart;

            mergedBusy.forEach(interval => {
                if (interval[0] > currentTime) {
                    freeSlots.push([currentTime, interval[0]]);
                }
                currentTime = Math.max(currentTime, interval[1]);
            });

            if (currentTime < schoolEnd) {
                freeSlots.push([currentTime, schoolEnd]);
            }

            // Render Buttons
            if(freeSlots.length > 0) {
                suggestions.innerHTML = '';
                freeSlots.forEach(slot => {
                    // Only show slots > 30 mins
                    if ((slot[1] - slot[0]) >= 30) {
                        let startStr = minutesToTime(slot[0]);
                        let endStr = minutesToTime(slot[0] + 60 > slot[1] ? slot[1] : slot[0] + 60); // Suggest 1 hour blocks
                        let label = `${minutesToTime(slot[0])} - ${minutesToTime(slot[1])}`;
                        
                        let btn = document.createElement('div');
                        btn.className = 'btn-suggestion';
                        btn.innerHTML = `<i class="fa fa-plus-circle me-1"></i> ${label}`;
                        btn.onclick = () => {
                            startInput.value = startStr;
                            endInput.value = endStr;
                        };
                        suggestions.appendChild(btn);
                    }
                });
            } else {
                suggestions.innerHTML = '<span class="text-danger small">No free slots available within school hours.</span>';
            }
        }

        // Attach Listeners
        dayInputs.forEach(input => input.addEventListener('change', checkAvailability));
        classSelect.addEventListener('change', checkAvailability);
        roomInput.addEventListener('input', checkAvailability);
        
        // --- DROPDOWN LOGIC (UNCHANGED) ---
        if (gradeSelect) {
            gradeSelect.addEventListener('change', function() {
                classSelect.innerHTML = '<option value="">{{ __('timetable.select_class_first') }}</option>';
                classSelect.disabled = true;
                subjectSelect.innerHTML = '<option value="">Select Class First</option>'; 
                subjectSelect.disabled = true;
                refreshSelect(classSelect);
                refreshSelect(subjectSelect);

                if (this.value) {
                    classSelect.innerHTML = '<option value="">{{ __('student.loading') }}</option>';
                    refreshSelect(classSelect);

                    fetch(`{{ route('students.get_sections') }}?grade_id=${this.value}`)
                        .then(response => response.json())
                        .then(data => {
                            classSelect.innerHTML = '<option value="">{{ __('timetable.select_class') }}</option>';
                            Object.entries(data).forEach(([id, name]) => {
                                let option = new Option(name, id);
                                if (String(id) === String(oldClassId)) option.selected = true;
                                classSelect.add(option);
                            });
                            classSelect.disabled = false;
                            refreshSelect(classSelect);
                            if (oldClassId) classSelect.dispatchEvent(new Event('change')); 
                        });
                }
            });
        }

        if (classSelect) {
            classSelect.addEventListener('change', function() {
                subjectSelect.innerHTML = '<option value="">{{ __('student.loading') }}</option>';
                subjectSelect.disabled = true;
                refreshSelect(subjectSelect);

                if (this.value) {
                    fetch(`{{ route('timetables.get_allocated_subjects') }}?class_section_id=${this.value}&grade_level_id=${gradeSelect.value}`)
                        .then(response => response.json())
                        .then(data => {
                            subjectSelect.innerHTML = '<option value="">{{ __('timetable.select_subject') }}</option>';
                            data.forEach(item => {
                                let option = new Option(item.name, item.id);
                                if(item.default_teacher) {
                                    option.setAttribute('data-teacher-id', item.default_teacher);
                                    option.setAttribute('data-teacher-name', item.teacher_name);
                                }
                                if (String(item.id) === String(oldSubjectId)) option.selected = true;
                                subjectSelect.add(option);
                            });
                            subjectSelect.disabled = false;
                            refreshSelect(subjectSelect);
                            if(oldSubjectId) subjectSelect.dispatchEvent(new Event('change'));
                        });
                }
            });
        }

        if (subjectSelect) {
            subjectSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const teacherId = selectedOption.getAttribute('data-teacher-id');
                const teacherName = selectedOption.getAttribute('data-teacher-name');

                if (teacherId) {
                    teacherDisplay.value = teacherName;
                    teacherHidden.value = teacherId;
                    teacherDisplay.classList.remove('is-invalid');
                    teacherWarning.classList.add('d-none');
                    saveBtn.disabled = false;
                    checkAvailability(); 
                } else {
                    teacherDisplay.value = '';
                    teacherHidden.value = '';
                    if(this.value) {
                        teacherDisplay.classList.add('is-invalid');
                        teacherWarning.classList.remove('d-none');
                        saveBtn.disabled = true; 
                    } else {
                        teacherDisplay.classList.remove('is-invalid');
                        teacherWarning.classList.add('d-none');
                        saveBtn.disabled = false;
                    }
                }
            });
        }

        if (gradeSelect.value) {
            gradeSelect.dispatchEvent(new Event('change'));
        }
        
        setTimeout(checkAvailability, 1000); 
    });
</script>