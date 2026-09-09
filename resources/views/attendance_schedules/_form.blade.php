<form action="{{ isset($schedule) ? route('attendance-schedules.update', $schedule->id) : route('attendance-schedules.store') }}"
      method="POST" id="attendanceScheduleForm">
    @csrf
    @if(isset($schedule))
        @method('PUT')
    @endif

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{ __('attendance_schedule.basic_information') }}</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        @if(!$institutionId)
                        <div class="mb-3 col-md-6">
                            <label class="form-label">{{ __('campus.select_institution') }} <span class="text-danger">*</span></label>
                            <select name="institution_id" class="form-control default-select" required>
                                <option value="">-- {{ __('campus.select_institution') }} --</option>
                                @foreach($institutes as $id => $name)
                                    <option value="{{ $id }}" @selected(old('institution_id', $schedule->institution_id ?? '') == $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <div class="mb-3 col-md-6">
                            <label class="form-label">{{ __('attendance_schedule.name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control"
                                   value="{{ old('name', $schedule->name ?? '') }}" required>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label">{{ __('attendance_schedule.code') }}</label>
                            <input type="text" name="code" class="form-control"
                                   value="{{ old('code', $schedule->code ?? '') }}">
                        </div>
                        <div class="mb-3 col-md-4">
                            <label class="form-label">{{ __('attendance_schedule.check_in_time') }} <span class="text-danger">*</span></label>
                            <div class="input-group clockpicker">
                                <input type="text" name="check_in_time" class="form-control"
                                       value="{{ old('check_in_time', isset($schedule) ? $schedule->checkInTimeLabel() : '07:30') }}" required>
                                <span class="input-group-text"><i class="far fa-clock"></i></span>
                            </div>
                        </div>
                        <div class="mb-3 col-md-4">
                            <label class="form-label">{{ __('attendance_schedule.check_out_time') }}</label>
                            <div class="input-group clockpicker">
                                <input type="text" name="check_out_time" class="form-control"
                                       value="{{ old('check_out_time', isset($schedule) ? $schedule->checkOutTimeLabel() : '') }}">
                                <span class="input-group-text"><i class="far fa-clock"></i></span>
                            </div>
                        </div>
                        <div class="mb-3 col-md-4">
                            <label class="form-label">{{ __('attendance_schedule.late_margin_minutes') }} <span class="text-danger">*</span></label>
                            <input type="number" name="late_margin_minutes" class="form-control" min="0" max="240"
                                   value="{{ old('late_margin_minutes', $schedule->late_margin_minutes ?? 0) }}" required>
                            <small class="text-muted">{{ __('attendance_schedule.late_margin_help') }}</small>
                        </div>
                        <div class="mb-3 col-md-4">
                            <label class="form-label">{{ __('attendance_schedule.status') }}</label>
                            <select name="is_active" class="form-control default-select">
                                <option value="1" @selected(old('is_active', $schedule->is_active ?? 1) == 1)>{{ __('attendance_schedule.active') }}</option>
                                <option value="0" @selected(old('is_active', $schedule->is_active ?? 1) == 0)>{{ __('attendance_schedule.inactive') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{ __('attendance_schedule.assignments') }}</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted">{{ __('attendance_schedule.assignments_help') }}</p>
                    <div class="row">
                        <div class="mb-3 col-md-6">
                            <label class="form-label">{{ __('attendance_schedule.select_grades') }}</label>
                            <select name="grade_level_ids[]" class="form-control default-select" multiple>
                                @foreach($grades as $grade)
                                    <option value="{{ $grade->id }}" @selected(in_array($grade->id, old('grade_level_ids', $selectedGradeIds ?? [])))>
                                        {{ $grade->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label">{{ __('attendance_schedule.select_sections') }}</label>
                            <select name="class_section_ids[]" class="form-control default-select" multiple>
                                @foreach($sections as $section)
                                    <option value="{{ $section->id }}" @selected(in_array($section->id, old('class_section_ids', $selectedSectionIds ?? [])))>
                                        {{ class_section_label($section, 'grade_dash_section') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <p class="text-muted small">{{ __('attendance_schedule.fallback_note') }}</p>
                    <button type="submit" class="btn btn-primary mt-2">
                        {{ isset($schedule) ? __('attendance_schedule.update') : __('attendance_schedule.save') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
