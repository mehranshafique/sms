<form action="{{ isset($subject) ? route('subjects.update', $subject->id) : route('subjects.store') }}" method="POST" id="subjectForm">
    @csrf
    @if(isset($subject))
        @method('PUT')
    @endif

    <div class="row">
        <div class="col-xl-12 col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{ __('subject.basic_information') }}</h4>
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
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">{{ __('subject.institution_label') }} <span class="text-danger">*</span></label>
                                    <select name="institution_id" class="form-control default-select" required>
                                        <option value="">{{ __('subject.select_institution') }}</option>
                                        @foreach($institutions as $id => $name)
                                            <option value="{{ $id }}" {{ (old('institution_id', $subject->institution_id ?? ($hasContext ? $institutionId : '')) == $id) ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            {{-- Grade Level Selection (Enhanced with Data Attributes for JS) --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('subject.select_grade') }} <span class="text-danger">*</span></label>
                                <select name="grade_level_id" id="gradeSelect" class="form-control default-select" required>
                                    <option value="">{{ __('subject.select_grade') }}</option>
                                    @if(isset($grades))
                                        @foreach($grades as $grade)
                                            <option value="{{ $grade['id'] }}" 
                                                    data-cycle="{{ $grade['cycle'] ?? 'primary' }}"
                                                    {{ (old('grade_level_id', $subject->grade_level_id ?? '') == $grade['id']) ? 'selected' : '' }}>
                                                {{ $grade['name'] }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('subject.subject_name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $subject->name ?? '') }}" placeholder="{{ __('subject.enter_name') }}" required>
                            </div>

                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('subject.subject_code') }}</label>
                                <input type="text" name="code" class="form-control" value="{{ old('code', $subject->code ?? '') }}" placeholder="{{ __('subject.enter_code') }}">
                            </div>

                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('subject.subject_type') }} <span class="text-danger">*</span></label>
                                <select name="type" class="form-control default-select" required>
                                    <option value="theory" {{ (old('type', $subject->type ?? '') == 'theory') ? 'selected' : '' }}>{{ __('subject.theory') }}</option>
                                    <option value="practical" {{ (old('type', $subject->type ?? '') == 'practical') ? 'selected' : '' }}>{{ __('subject.practical') }}</option>
                                    <option value="both" {{ (old('type', $subject->type ?? '') == 'both') ? 'selected' : '' }}>{{ __('subject.both') }}</option>
                                </select>
                            </div>

                            {{-- UNIVERSITY FIELDS CONTAINER --}}
                            <div id="universityFields" style="display: none;" class="col-12 p-0">
                                <div class="row m-0">
                                    {{-- Department --}}
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label">{{ __('subject.department') }}</label>
                                        <select name="department_id" class="form-control default-select">
                                            <option value="">-- {{ __('subject.select_department') }} --</option>
                                            @if(isset($departments))
                                                @foreach($departments as $id => $name)
                                                    <option value="{{ $id }}" {{ (old('department_id', $subject->department_id ?? '') == $id) ? 'selected' : '' }}>
                                                        {{ $name }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>

                                    {{-- Prerequisite --}}
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label">{{ __('subject.prerequisite') }}</label>
                                        <select name="prerequisite_id" class="form-control default-select" data-live-search="true">
                                            <option value="">-- {{ __('subject.select_prerequisite') }} --</option>
                                            @if(isset($prerequisites))
                                                @foreach($prerequisites as $id => $name)
                                                    <option value="{{ $id }}" {{ (old('prerequisite_id', $subject->prerequisite_id ?? '') == $id) ? 'selected' : '' }}>
                                                        {{ $name }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>

                                    {{-- Semester --}}
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label">{{ __('subject.semester') }}</label>
                                        <input type="text" name="semester" class="form-control" value="{{ old('semester', $subject->semester ?? '') }}" placeholder="{{ __('subject.enter_semester') }}">
                                    </div>

                                    {{-- Credit Hours (Moved inside University fields) --}}
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label">{{ __('subject.credit_hours') }}</label>
                                        <input type="number" name="credit_hours" class="form-control" value="{{ old('credit_hours', $subject->credit_hours ?? 0) }}" placeholder="{{ __('subject.enter_credits') }}" step="0.5">
                                    </div>
                                </div>
                            </div>
                            {{-- END UNIVERSITY FIELDS --}}

                            <div class="mb-3 col-md-4">
                                <label class="form-label">{{ __('subject.total_marks') }} <span class="text-danger">*</span></label>
                                <input type="number" name="total_marks" class="form-control" value="{{ old('total_marks', $subject->total_marks ?? 100) }}" placeholder="{{ __('subject.enter_total') }}" required>
                            </div>

                            <div class="mb-3 col-md-4">
                                <label class="form-label">{{ __('subject.passing_marks') }} <span class="text-danger">*</span></label>
                                <input type="number" name="passing_marks" class="form-control" value="{{ old('passing_marks', $subject->passing_marks ?? 40) }}" placeholder="{{ __('subject.enter_pass') }}" required>
                            </div>
                            
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('subject.status_label') }}</label>
                                <select name="is_active" class="form-control default-select">
                                    <option value="1" {{ (old('is_active', $subject->is_active ?? 1) == 1) ? 'selected' : '' }}>{{ __('subject.active') }}</option>
                                    <option value="0" {{ (old('is_active', $subject->is_active ?? 1) == 0) ? 'selected' : '' }}>{{ __('subject.inactive') }}</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">{{ isset($subject) ? __('subject.update_subject') : __('subject.save_subject') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const gradeSelect = document.getElementById('gradeSelect');
        const uniFields = document.getElementById('universityFields');

        function toggleUniFields() {
            if (!gradeSelect) return;
            
            // Get selected option
            const selectedOption = gradeSelect.options[gradeSelect.selectedIndex];
            const cycle = selectedOption.getAttribute('data-cycle');
            
            // Show if University, hide otherwise
            // Note: 'university' here must match the enum value in GradeLevel migration
            if (cycle === 'university' || cycle === 'cycle_lmd') { 
                uniFields.style.display = 'block';
            } else {
                uniFields.style.display = 'none';
            }
        }

        if (gradeSelect) {
            gradeSelect.addEventListener('change', toggleUniFields);
            toggleUniFields();
        }
    });
</script>