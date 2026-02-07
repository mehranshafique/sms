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
                            
                            {{-- Institution Logic --}}
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

                            {{-- Grade Level --}}
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
                            
                            {{-- Basic Info --}}
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

                            {{-- UNIVERSITY SPECIFIC FIELDS --}}
                            <div id="universityFields" style="display: none;" class="col-12 p-0">
                                <div class="row m-0">
                                    <div class="col-12 mb-3">
                                        <hr class="border-secondary opacity-25">
                                        <h6 class="text-primary"><i class="fa fa-university me-1"></i> University Configuration</h6>
                                    </div>

                                    {{-- Program Filter (UI Only) --}}
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label">{{ __('lmd.program_name') }} <small class="text-muted">(Filter for UEs)</small></label>
                                        <select id="programFilter" class="form-control default-select" data-live-search="true">
                                            <option value="">-- All Programs --</option>
                                            @if(isset($programs))
                                                @foreach($programs as $id => $name)
                                                    <option value="{{ $id }}" {{ (isset($selectedProgramId) && $selectedProgramId == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>

                                    {{-- Academic Unit (UE) --}}
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label">{{ __('lmd.ue_title') }} <span class="text-danger">*</span></label>
                                        <select name="academic_unit_id" id="unitSelect" class="form-control default-select" data-live-search="true">
                                            <option value="">-- Select Academic Unit (UE) --</option>
                                            @if(isset($units))
                                                @foreach($units as $id => $name)
                                                    <option value="{{ $id }}" {{ (old('academic_unit_id', $subject->academic_unit_id ?? '') == $id) ? 'selected' : '' }}>
                                                        {{ $name }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>

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

                                    <div class="mb-3 col-md-3">
                                        <label class="form-label">{{ __('subject.credit_hours') }}</label>
                                        <input type="number" name="credit_hours" class="form-control" value="{{ old('credit_hours', $subject->credit_hours ?? 0) }}" placeholder="e.g. 3.0" step="0.5">
                                    </div>
                                    
                                    <div class="mb-3 col-md-3">
                                        <label class="form-label">{{ __('lmd.coefficient') }}</label>
                                        <input type="number" name="coefficient" class="form-control" value="{{ old('coefficient', $subject->coefficient ?? 1) }}" placeholder="e.g. 1" step="0.1">
                                    </div>

                                    <div class="mb-3 col-md-6">
                                        <label class="form-label">{{ __('subject.semester') }}</label>
                                        <input type="text" name="semester" class="form-control" value="{{ old('semester', $subject->semester ?? '') }}" placeholder="{{ __('subject.enter_semester') }}">
                                    </div>
                                    
                                    <div class="col-12"><hr class="border-secondary opacity-25"></div>
                                </div>
                            </div>
                            {{-- END UNIVERSITY --}}

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
        const programFilter = document.getElementById('programFilter');
        const unitSelect = document.getElementById('unitSelect');
        const uniFields = document.getElementById('universityFields');

        // Toggle University Section based on Grade Cycle
        function toggleUniFields() {
            if (!gradeSelect) return;
            const selectedOption = gradeSelect.options[gradeSelect.selectedIndex];
            const cycle = selectedOption.getAttribute('data-cycle');
            
            if (cycle === 'university' || cycle === 'lmd') { 
                uniFields.style.display = 'block';
            } else {
                uniFields.style.display = 'none';
            }
        }

        if (gradeSelect) {
            gradeSelect.addEventListener('change', toggleUniFields);
            toggleUniFields(); // Init on load
        }

        // AJAX: Filter Academic Units by Program
        if (programFilter) {
            programFilter.addEventListener('change', function() {
                const programId = this.value;
                const gradeId = gradeSelect.value;
                
                // Reset Unit Dropdown
                unitSelect.innerHTML = '<option value="">{{ __('student.loading') }}</option>';
                unitSelect.disabled = true;
                if($.fn.selectpicker) $(unitSelect).selectpicker('refresh');

                // Build URL
                let url = "{{ route('subjects.get_units') }}"; // Route to be created in web.php
                let params = new URLSearchParams();
                if(programId) params.append('program_id', programId);
                // Optional: Filter by Grade too if desired, though Programs usually span grades
                // params.append('grade_level_id', gradeId); 

                fetch(`${url}?${params.toString()}`)
                    .then(response => response.json())
                    .then(data => {
                        unitSelect.innerHTML = '<option value="">-- Select Academic Unit (UE) --</option>';
                        Object.entries(data).forEach(([id, name]) => {
                            unitSelect.add(new Option(name, id));
                        });
                        unitSelect.disabled = false;
                        if($.fn.selectpicker) $(unitSelect).selectpicker('refresh');
                    });
            });
        }
    });
</script>