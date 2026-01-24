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
                            
                            {{-- Institution Field --}}
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

                            {{-- 1. Grade Level --}}
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

                            {{-- 2. Class Section --}}
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

                            {{-- 3. Subject --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('timetable.select_subject') }} <span class="text-danger">*</span></label>
                                <select name="subject_id" id="subjectSelect" class="form-control default-select" data-live-search="true" required disabled>
                                    <option value="">{{ __('timetable.select_class_first') }}</option>
                                </select>
                            </div>

                            {{-- 4. Teacher (READ ONLY) --}}
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
                    <h4 class="card-title text-primary"><i class="fa fa-calendar-o me-2"></i> Reserved Slots</h4>
                </div>
                <div class="card-body">
                    <div id="scheduleLoader" class="text-center d-none">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div id="scheduleVisuals" class="timeline-visuals">
                        <p class="text-muted small text-center">Select Class, Teacher, Room & Day to see reservations.</p>
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
    .timeline-item.conflict-Class { border-color: #ffb822; } /* Orange for Class Busy */
    .timeline-item.conflict-Class::before { background: #ffb822; }
    
    .timeline-item.conflict-Teacher { border-color: #f64e60; } /* Red for Teacher Busy */
    .timeline-item.conflict-Teacher::before { background: #f64e60; }
    
    .timeline-item.conflict-Room { border-color: #8950fc; } /* Purple for Room Busy */
    .timeline-item.conflict-Room::before { background: #8950fc; }

    .time-badge { font-weight: bold; font-size: 0.9em; display: block; margin-bottom: 2px; }
    .slot-info { font-size: 0.85em; color: #666; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        
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
        
        const oldClassId = "{{ old('class_section_id', isset($timetable) ? $timetable->class_section_id : '') }}";
        const oldSubjectId = "{{ old('subject_id', isset($timetable) ? $timetable->subject_id : '') }}";
        const editTeacherName = "{{ isset($timetable) && $timetable->teacher ? $timetable->teacher->user->name : '' }}";
        const editTeacherId = "{{ isset($timetable) ? $timetable->teacher_id : '' }}";

        // Pre-fill Edit Mode Data
        if(editTeacherId) {
            teacherHidden.value = editTeacherId;
            teacherDisplay.value = editTeacherName;
        }

        // --- CHECK AVAILABILITY LOGIC ---
        function checkAvailability() {
            const day = document.querySelector('input[name="day"]:checked')?.value;
            const classId = classSelect.value;
            const staffId = teacherHidden.value;
            const room = roomInput.value;

            if (!day || (!classId && !staffId && !room)) return;

            const loader = document.getElementById('scheduleLoader');
            const container = document.getElementById('scheduleVisuals');
            
            loader.classList.remove('d-none');
            container.innerHTML = '';

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
                    if(slots.length === 0) {
                        container.innerHTML = '<div class="alert alert-success light"><i class="fa fa-check-circle me-2"></i> No reservations found for selected criteria. Slot is likely free.</div>';
                        return;
                    }

                    let html = '';
                    slots.forEach(slot => {
                        // Determine conflict types
                        let conflictClass = slot.conflicts.length > 0 ? `conflict-${slot.conflicts[0]}` : '';
                        let conflictBadges = slot.conflicts.map(c => {
                            let color = c === 'Teacher' ? 'danger' : (c === 'Class' ? 'warning text-dark' : 'info');
                            return `<span class="badge badge-xs badge-${color} me-1">${c} Busy</span>`;
                        }).join('');

                        html += `
                        <div class="timeline-item ${conflictClass}">
                            <span class="time-badge">${slot.start_time} - ${slot.end_time}</span>
                            <div class="slot-info">
                                <strong>${slot.subject}</strong><br>
                                <span class="text-muted">Class:</span> ${slot.class}<br>
                                <span class="text-muted">Teacher:</span> ${slot.teacher}<br>
                                <span class="text-muted">Room:</span> ${slot.room}
                            </div>
                            <div class="mt-1">${conflictBadges}</div>
                        </div>`;
                    });
                    container.innerHTML = html;
                })
                .catch(err => {
                    loader.classList.add('d-none');
                    container.innerHTML = '<p class="text-danger small">Error checking availability.</p>';
                });
        }

        // Attach Availability Listeners
        dayInputs.forEach(input => input.addEventListener('change', checkAvailability));
        classSelect.addEventListener('change', checkAvailability);
        roomInput.addEventListener('input', checkAvailability); // Debounce ideally
        
        // --- EXISTING DROPDOWN LOGIC ---

        // 1. Grade -> Class
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

        // 2. Class -> Subject
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

        // 3. Subject -> Display Teacher & Trigger Check
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
                    checkAvailability(); // Check slots for this teacher
                } else {
                    teacherDisplay.value = '';
                    teacherHidden.value = '';
                    if(this.value) {
                        teacherDisplay.classList.add('is-invalid');
                        teacherWarning.classList.remove('d-none');
                    } else {
                        teacherDisplay.classList.remove('is-invalid');
                        teacherWarning.classList.add('d-none');
                        saveBtn.disabled = false;
                    }
                }
            });
        }

        // Trigger initial load if editing
        if (gradeSelect.value) {
            gradeSelect.dispatchEvent(new Event('change'));
        }
        
        // Initial Availability Check (if editing)
        setTimeout(checkAvailability, 1000); 
    });
</script>