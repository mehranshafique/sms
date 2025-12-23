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
                            
                            {{-- LOGIC: Auto-Assign vs Select Institute --}}
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
                                            {{-- If editing, check parent grade of the current section --}}
                                            @php
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
                                <select name="class_section_id" id="classSelect" class="form-control default-select" required {{ isset($timetable) ? '' : 'disabled' }}>
                                    <option value="">{{ isset($timetable) ? '-- Select Class --' : 'Select Grade First' }}</option>
                                    @if(isset($classes))
                                        @foreach($classes as $id => $name)
                                            <option value="{{ $id }}" {{ (old('class_section_id', isset($timetable) ? $timetable->class_section_id : '') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    @endif
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

                            {{-- Start Time (Using Class 'timepicker') --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('timetable.start_time') }} <span class="text-danger">*</span></label>
                                <div class="input-group clockpicker">
                                    <input type="text" name="start_time" class="form-control timepicker" value="{{ old('start_time', (isset($timetable) && $timetable->start_time) ? $timetable->start_time->format('H:i') : '09:00') }}" required>
                                    <span class="input-group-text"><i class="far fa-clock"></i></span>
                                </div>
                            </div>

                            {{-- End Time (Using Class 'timepicker') --}}
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
        // Requirement No 4: Ensure options are shown and selected correctly with Ajax
        $('#gradeSelect').on('change', function() {
            var gradeId = $(this).val();
            var $classSelect = $('#classSelect');
            var selectedClassId = "{{ old('class_section_id', isset($timetable) ? $timetable->class_section_id : '') }}";
            
            // Show loading state
            $classSelect.empty().append('<option value="">Loading...</option>').prop('disabled', true);
            if($.fn.selectpicker) $classSelect.selectpicker('refresh');

            if(gradeId) {
                // Reusing the Students getSections route as it provides the same data
                $.ajax({
                    url: "{{ route('students.get_sections') }}",
                    type: "GET",
                    data: { grade_id: gradeId },
                    success: function(data) {
                        $classSelect.empty().append('<option value="">-- Select Class --</option>');
                        if(Object.keys(data).length > 0) {
                            $.each(data, function(key, value) {
                                // Requirement No 4: Check dependent selection box value
                                var isSelected = (selectedClassId == key) ? 'selected' : '';
                                $classSelect.append('<option value="'+key+'" '+isSelected+'>'+value+'</option>');
                            });
                            $classSelect.prop('disabled', false);
                        } else {
                            $classSelect.append('<option value="">No classes found</option>');
                        }
                        
                        // Requirement No 4: Refresh logic to show selected option
                        // Using setTimeout ensures the DOM update is processed before refresh
                        setTimeout(function(){
                            if($.fn.selectpicker) {
                                $classSelect.selectpicker('refresh');
                                // Force render if value exists but UI didn't update
                                if(selectedClassId && $classSelect.find('option[value="'+selectedClassId+'"]').length > 0) {
                                    $classSelect.val(selectedClassId);
                                    $classSelect.selectpicker('render');
                                }
                            }
                        }, 100);
                    },
                    error: function() {
                        $classSelect.empty().append('<option value="">Error loading classes</option>');
                        if($.fn.selectpicker) $classSelect.selectpicker('refresh');
                    }
                });
            } else {
                $classSelect.empty().append('<option value="">Select Grade First</option>');
                if($.fn.selectpicker) $classSelect.selectpicker('refresh');
            }
        });

        // Trigger change if Grade is pre-selected (Edit mode or Validation error)
        var preSelectedGrade = $('#gradeSelect').val();
        if(preSelectedGrade) {
            $('#gradeSelect').trigger('change');
        }
    });
</script>