<form action="{{ isset($class_section) ? route('class-sections.update', $class_section->id) : route('class-sections.store') }}" method="POST" id="classForm">
    @csrf
    @if(isset($class_section))
        @method('PUT')
    @endif
    
    {{-- Hidden Institution ID --}}
    <input type="hidden" name="institution_id" value="{{ auth()->user()->institute_id ?? 1 }}">

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{ __('class_section.basic_information') }}</h4>
                </div>
                <div class="card-body">
                    <div class="basic-form">
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('class_section.select_grade') }} <span class="text-danger">*</span></label>
                                <select name="grade_level_id" class="form-control default-select" required>
                                    <option value="">{{ __('class_section.select_grade') }}</option>
                                    @foreach($gradeLevels as $id => $name)
                                        <option value="{{ $id }}" {{ (old('grade_level_id', $class_section->grade_level_id ?? '') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('class_section.section_name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $class_section->name ?? '') }}" placeholder="{{ __('class_section.enter_name') }}" required>
                            </div>

                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('class_section.section_code') }}</label>
                                <input type="text" name="code" class="form-control" value="{{ old('code', $class_section->code ?? '') }}" placeholder="{{ __('class_section.enter_code') }}">
                            </div>

                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('class_section.select_campus') }}</label>
                                <select name="campus_id" class="form-control default-select">
                                    <option value="">{{ __('class_section.select_campus') }}</option>
                                    @foreach($campuses as $id => $name)
                                        <option value="{{ $id }}" {{ (old('campus_id', $class_section->campus_id ?? '') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('class_section.select_teacher') }}</label>
                                <select name="staff_id" class="form-control default-select">
                                    <option value="">{{ __('class_section.select_teacher') }}</option>
                                    @foreach($staff as $id => $name)
                                        <option value="{{ $id }}" {{ (old('staff_id', $class_section->staff_id ?? '') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3 col-md-3">
                                <label class="form-label">{{ __('class_section.room_number') }}</label>
                                <input type="text" name="room_number" class="form-control" value="{{ old('room_number', $class_section->room_number ?? '') }}" placeholder="{{ __('class_section.enter_room') }}">
                            </div>

                            <div class="mb-3 col-md-3">
                                <label class="form-label">{{ __('class_section.capacity') }} <span class="text-danger">*</span></label>
                                <input type="number" name="capacity" class="form-control" value="{{ old('capacity', $class_section->capacity ?? 40) }}" placeholder="{{ __('class_section.enter_capacity') }}" required>
                            </div>
                            
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('class_section.status_label') }}</label>
                                <select name="is_active" class="form-control default-select">
                                    <option value="1" {{ (old('is_active', $class_section->is_active ?? 1) == 1) ? 'selected' : '' }}>{{ __('class_section.active') }}</option>
                                    <option value="0" {{ (old('is_active', $class_section->is_active ?? 1) == 0) ? 'selected' : '' }}>{{ __('class_section.inactive') }}</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">{{ isset($class_section) ? __('class_section.update_class') : __('class_section.save_class') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>