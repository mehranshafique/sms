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
                            {{-- Institution Selection (Super Admin Only) --}}
                            @if(!auth()->user()->institute_id)
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Institution <span class="text-danger">*</span></label>
                                    <select name="institution_id" class="form-control default-select" required>
                                        <option value="">Select Institution</option>
                                        @foreach($institutions as $id => $name)
                                            <option value="{{ $id }}" {{ (old('institution_id', $subject->institution_id ?? '') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @else
                                <input type="hidden" name="institution_id" value="{{ auth()->user()->institute_id }}">
                            @endif

                            {{-- Grade Level Selection --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('subject.select_grade') }} <span class="text-danger">*</span></label>
                                <select name="grade_level_id" class="form-control default-select" required>
                                    <option value="">{{ __('subject.select_grade') }}</option>
                                    @if(isset($gradeLevels) && count($gradeLevels) > 0)
                                        @foreach($gradeLevels as $id => $name)
                                            <option value="{{ $id }}" {{ (old('grade_level_id', $subject->grade_level_id ?? '') == $id) ? 'selected' : '' }}>{{ $name }}</option>
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

                            <div class="mb-3 col-md-4">
                                <label class="form-label">{{ __('subject.credit_hours') }}</label>
                                <input type="number" name="credit_hours" class="form-control" value="{{ old('credit_hours', $subject->credit_hours ?? 0) }}" placeholder="{{ __('subject.enter_credits') }}">
                            </div>

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