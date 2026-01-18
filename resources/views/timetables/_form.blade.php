<form action="{{ isset($timetable) ? route('timetables.update', $timetable->id) : route('timetables.store') }}" method="POST" id="timetableForm">
    @csrf
    @if(isset($timetable))
        @method('PUT')
    @endif

    <div class="row">
        <div class="col-xl-12 col-lg-12">
            <div class="card">
                <div class="card-header border-0 pb-0">
                    <h4 class="card-title">{{ __('timetable.basic_information') }}</h4>
                </div>
                <div class="card-body">
                    <div class="basic-form">
                        <div class="row">
                            
                            {{-- Institution Field (Auto or Select) --}}
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

                            {{-- 1. Grade Level Selection --}}
                            <div class="mb-3 col-md-4">
                                <label class="form-label">{{ __('grade_level.grade_name') }} <span class="text-danger">*</span></label>
                                <select name="grade_level_id" id="gradeSelect" class="form-control default-select" data-live-search="true" required>
                                    <option value="">-- Select Grade --</option>
                                    @if(isset($gradeLevels))
                                        @foreach($gradeLevels as $id => $name)
                                            @php
                                                // Pre-select if editing or old input exists
                                                $selected = old('grade_level_id', isset($timetable) ? $timetable->classSection->grade_level_id : '') == $id;
                                            @endphp
                                            <option value="{{ $id }}" {{ $selected ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            {{-- 2. Class Selection (Dependent) --}}
                            <div class="mb-3 col-md-4">
                                <label class="form-label">{{ __('timetable.select_class') }} <span class="text-danger">*</span></label>
                                <select name="class_section_id" id="classSelect" class="form-control default-select" required disabled>
                                    <option value="">{{ __('timetable.select_class_first') }}</option>
                                </select>
                            </div>
                            
                            {{-- Subject Selection --}}
                            <div class="mb-3 col-md-4">
                                <label class="form-label">{{ __('timetable.select_subject') }} <span class="text-danger">*</span></label>
                                <select name="subject_id" class="form-control default-select" data-live-search="true" required>
                                    <option value="">{{ __('timetable.select_subject') }}</option>
                                    @if(isset($subjects))
                                        @foreach($subjects as $id => $name)
                                            <option value="{{ $id }}" {{ (old('subject_id', isset($timetable) ? $timetable->subject_id : '') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            {{-- Teacher Selection --}}
                            <div class="mb-3 col-md-4">
                                <label class="form-label">{{ __('timetable.select_teacher') }}</label>
                                <select name="staff_id" class="form-control default-select" data-live-search="true">
                                    <option value="">{{ __('timetable.select_teacher') }}</option>
                                    @if(isset($teachers))
                                        @foreach($teachers as $id => $name)
                                            <option value="{{ $id }}" {{ (old('staff_id', isset($timetable) ? $timetable->teacher_id : '') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            {{-- Day Selection --}}
                            <div class="mb-3 col-md-4">
                                <label class="form-label">{{ __('timetable.select_day') }} <span class="text-danger">*</span></label>
                                <select name="day" class="form-control default-select" required>
                                    <option value="monday" {{ (old('day', isset($timetable) ? $timetable->day_of_week : '') == 'monday') ? 'selected' : '' }}>{{ __('timetable.monday') }}</option>
                                    <option value="tuesday" {{ (old('day', isset($timetable) ? $timetable->day_of_week : '') == 'tuesday') ? 'selected' : '' }}>{{ __('timetable.tuesday') }}</option>
                                    <option value="wednesday" {{ (old('day', isset($timetable) ? $timetable->day_of_week : '') == 'wednesday') ? 'selected' : '' }}>{{ __('timetable.wednesday') }}</option>
                                    <option value="thursday" {{ (old('day', isset($timetable) ? $timetable->day_of_week : '') == 'thursday') ? 'selected' : '' }}>{{ __('timetable.thursday') }}</option>
                                    <option value="friday" {{ (old('day', isset($timetable) ? $timetable->day_of_week : '') == 'friday') ? 'selected' : '' }}>{{ __('timetable.friday') }}</option>
                                    <option value="saturday" {{ (old('day', isset($timetable) ? $timetable->day_of_week : '') == 'saturday') ? 'selected' : '' }}>{{ __('timetable.saturday') }}</option>
                                    <option value="sunday" {{ (old('day', isset($timetable) ? $timetable->day_of_week : '') == 'sunday') ? 'selected' : '' }}>{{ __('timetable.sunday') }}</option>
                                </select>
                            </div>

                            {{-- Room Number --}}
                            <div class="mb-3 col-md-4">
                                <label class="form-label">{{ __('timetable.room_number') }}</label>
                                <input type="text" name="room_number" class="form-control" value="{{ old('room_number', isset($timetable) ? $timetable->room_number : '') }}" placeholder="{{ __('timetable.enter_room') }}">
                            </div>

                            {{-- Start Time --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('timetable.start_time') }} <span class="text-danger">*</span></label>
                                <div class="input-group clockpicker">
                                    <input type="text" name="start_time" class="form-control timepicker" value="{{ old('start_time', (isset($timetable) && $timetable->start_time) ? $timetable->start_time->format('H:i') : '09:00') }}" required>
                                    <span class="input-group-text"><i class="far fa-clock"></i></span>
                                </div>
                            </div>

                            {{-- End Time --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('timetable.end_time') }} <span class="text-danger">*</span></label>
                                <div class="input-group clockpicker">
                                    <input type="text" name="end_time" class="form-control timepicker" value="{{ old('end_time', (isset($timetable) && $timetable->end_time) ? $timetable->end_time->format('H:i') : '10:00') }}" required>
                                    <span class="input-group-text"><i class="far fa-clock"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">{{ isset($timetable) ? __('timetable.update_routine') : __('timetable.save_routine') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- HELPER: Refresh UI Library (Bootstrap-Select) ---
        function refreshSelect(element) {
            if (typeof $ !== 'undefined' && $(element).is('select')) {
                if ($.fn.selectpicker) {
                     $(element).selectpicker('refresh');
                }
            }
        }

        // --- HELPER: Trigger Native Change ---
        function triggerChangeEvent(element) {
            element.dispatchEvent(new Event('change'));
        }

        // --- LOGIC: Grade -> Class Section ---
        const gradeSelect = document.getElementById('gradeSelect');
        const classSelect = document.getElementById('classSelect');
        // Get old value for Edit mode or Validation redirect
        const oldClassId = "{{ old('class_section_id', isset($timetable) ? $timetable->class_section_id : '') }}";

        if (gradeSelect) {
            gradeSelect.addEventListener('change', function() {
                // 1. Reset Class Dropdown
                classSelect.innerHTML = '<option value="">{{ __('timetable.select_class_first') }}</option>';
                classSelect.disabled = true;
                refreshSelect(classSelect);

                // 2. Fetch if value exists
                if (this.value) {
                    // Update Loading State
                    classSelect.innerHTML = '<option value="">{{ __('student.loading') }}</option>';
                    refreshSelect(classSelect);

                    fetch(`{{ route('students.get_sections') }}?grade_id=${this.value}`)
                        .then(response => response.json())
                        .then(data => {
                            classSelect.innerHTML = '<option value="">{{ __('timetable.select_class') }}</option>';
                            
                            // Iterate Object {id: name}
                            Object.entries(data).forEach(([id, name]) => {
                                let option = new Option(name, id);
                                if (String(id) === String(oldClassId)) option.selected = true;
                                classSelect.add(option);
                            });

                            if (Object.keys(data).length > 0) {
                                classSelect.disabled = false;
                            } else {
                                classSelect.innerHTML = '<option value="">{{ __('student.no_options') }}</option>';
                            }
                            
                            refreshSelect(classSelect);
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            classSelect.innerHTML = '<option value="">{{ __('student.error_loading') }}</option>';
                            refreshSelect(classSelect);
                        });
                }
            });

            // 3. Trigger Initial Load (Edit Mode)
            if (gradeSelect.value) {
                triggerChangeEvent(gradeSelect);
            }
        }
    });
</script>