<form action="{{ isset($grade_level) ? route('grade-levels.update', $grade_level->id) : route('grade-levels.store') }}" method="POST" id="gradeForm">
    @csrf
    @if(isset($grade_level))
        @method('PUT')
    @endif

    <div class="row">
        <div class="col-xl-12 col-lg-12">
            <div class="card">
                <div class="card-header border-0 pb-0">
                    <h4 class="card-title">{{ __('grade_level.basic_information') }}</h4>
                </div>
                <div class="card-body">
                    <div class="basic-form">
                        <div class="row">
                            {{-- Institution Selection (Visible only if user has no fixed institute) --}}
                            @if(!auth()->user()->institute_id)
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Institution <span class="text-danger">*</span></label>
                                    <select name="institution_id" class="form-control default-select" required>
                                        <option value="">Select Institution</option>
                                        @foreach($institutions as $id => $name)
                                            <option value="{{ $id }}" {{ (old('institution_id', $grade_level->institution_id ?? '') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @else
                                <input type="hidden" name="institution_id" value="{{ auth()->user()->institute_id }}">
                            @endif

                            {{-- Name --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('grade_level.grade_name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" value="{{ old('name', $grade_level->name ?? '') }}" class="form-control" placeholder="{{ __('grade_level.enter_name') }}" required>
                            </div>

                            {{-- Code --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('grade_level.grade_code') }}</label>
                                <input type="text" name="code" value="{{ old('code', $grade_level->code ?? '') }}" class="form-control" placeholder="{{ __('grade_level.enter_code') }}">
                            </div>

                            {{-- Order Index --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('grade_level.order_index') }} <span class="text-danger">*</span></label>
                                <input type="number" name="order_index" value="{{ old('order_index', $grade_level->order_index ?? ($nextOrder ?? 0)) }}" class="form-control" required>
                                <small class="text-muted d-block mt-1">Used for sorting (1, 2, 3...)</small>
                            </div>

                            {{-- Education Cycle --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('grade_level.education_cycle') }} <span class="text-danger">*</span></label>
                                <select name="education_cycle" class="form-control default-select" required>
                                    <option value="">-- {{ __('grade_level.select_cycle') }} --</option>
                                    <option value="primary" {{ (old('education_cycle', $grade_level->education_cycle ?? '') == 'primary') ? 'selected' : '' }}>{{ __('grade_level.primary') }}</option>
                                    <option value="secondary" {{ (old('education_cycle', $grade_level->education_cycle ?? '') == 'secondary') ? 'selected' : '' }}>{{ __('grade_level.secondary') }}</option>
                                    <option value="university" {{ (old('education_cycle', $grade_level->education_cycle ?? '') == 'university') ? 'selected' : '' }}>{{ __('grade_level.university') }}</option>
                                    <option value="vocational" {{ (old('education_cycle', $grade_level->education_cycle ?? '') == 'vocational') ? 'selected' : '' }}>{{ __('grade_level.vocational') }}</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">{{ isset($grade_level) ? __('grade_level.update') : __('grade_level.save') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>