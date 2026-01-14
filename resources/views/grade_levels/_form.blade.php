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
                            
                            {{-- LOGIC: Auto-Assign vs Select Institute --}}
                            @php
                                $hasContext = isset($institutionId) && $institutionId;
                                $isSuperAdmin = auth()->user()->hasRole('Super Admin');
                                
                                // FIX: Correctly extract cycle value for auto-select
                                $currentCycle = '';
                                if(isset($grade_level) && $grade_level->education_cycle) {
                                    // If cast to Enum, get value; otherwise use string
                                    $currentCycle = is_object($grade_level->education_cycle) 
                                        ? $grade_level->education_cycle->value 
                                        : $grade_level->education_cycle;
                                }
                                // Fallback to old input on validation error
                                $currentCycle = old('education_cycle', $currentCycle);
                            @endphp

                            @if($hasContext && !$isSuperAdmin)
                                <input type="hidden" name="institution_id" value="{{ $institutionId }}">
                            @else
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">{{ __('grade_level.institution') }} <span class="text-danger">*</span></label>
                                    <select name="institution_id" class="form-control default-select" required>
                                        <option value="">-- {{ __('grade_level.select_institution') }} --</option>
                                        @foreach($institutes as $id => $name)
                                            <option value="{{ $id }}" {{ (old('institution_id', $grade_level->institution_id ?? ($hasContext ? $institutionId : '')) == $id) ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('grade_level.name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $grade_level->name ?? '') }}" placeholder="{{ __('grade_level.enter_name') }}" required>
                            </div>
                            
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('grade_level.code') }}</label>
                                <input type="text" name="code" class="form-control" value="{{ old('code', $grade_level->code ?? '') }}" placeholder="{{ __('grade_level.enter_code') }}">
                            </div>

                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('grade_level.order') }} <span class="text-danger">*</span></label>
                                <input type="number" name="order_index" class="form-control" value="{{ old('order_index', $grade_level->order_index ?? 0) }}" required min="0">
                                <small class="text-muted">{{ __('grade_level.order_hint') }}</small>
                            </div>

                            {{-- Academic Cycle Selection --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('grade_level.education_cycle') }} <span class="text-danger">*</span></label>
                                <select name="education_cycle" class="form-control default-select" required>
                                    <option value="">-- {{ __('grade_level.select_cycle') }} --</option>
                                    <option value="primary" {{ ($currentCycle == 'primary') ? 'selected' : '' }}>{{ __('grade_level.cycle_primary') }}</option>
                                    <option value="secondary" {{ ($currentCycle == 'secondary') ? 'selected' : '' }}>{{ __('grade_level.cycle_secondary') }}</option>
                                    {{-- FIX: Value is 'university' to match Database Enum --}}
                                    <option value="university" {{ ($currentCycle == 'university') ? 'selected' : '' }}>{{ __('grade_level.cycle_university') }}</option>
                                    <option value="vocational" {{ ($currentCycle == 'vocational') ? 'selected' : '' }}>{{ __('grade_level.cycle_vocational') }}</option>
                                </select>
                                <small class="text-muted">{{ __('grade_level.cycle_hint') }}</small>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">{{ isset($grade_level) ? __('grade_level.update') : __('grade_level.save') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>