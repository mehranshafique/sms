<form action="{{ isset($enrollment) ? route('enrollments.update', $enrollment->id) : route('enrollments.store') }}" method="POST" id="enrollmentForm">
    @csrf
    @if(isset($enrollment))
        @method('PUT')
    @endif
    
    <div class="row">
        <div class="col-xl-12 col-lg-12">
            <div class="card">
                <div class="card-header border-0 pb-0">
                    <h4 class="card-title">{{ __('enrollment.basic_information') }}</h4>
                </div>
                <div class="card-body">
                    <div class="basic-form">
                        <div class="row">
                            
                            {{-- Class Selection (Moved to First Position for Logic Flow) --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('enrollment.select_class') }} <span class="text-danger">*</span></label>
                                <select name="class_section_id" class="form-control default-select" required>
                                    <option value="">{{ __('enrollment.select_class') }}</option>
                                    @foreach($classes as $id => $name)
                                        <option value="{{ $id }}" {{ (old('class_section_id', $enrollment->class_section_id ?? '') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Student Selection (Bulk / Single) --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('enrollment.select_student') }} <span class="text-danger">*</span></label>
                                
                                @if(isset($enrollment))
                                    {{-- Edit Mode: Single Student Readonly --}}
                                    <select name="student_id" class="form-control default-select" required disabled>
                                        @foreach($students as $id => $name)
                                            <option value="{{ $id }}" selected>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="student_id" value="{{ $enrollment->student_id }}">
                                @else
                                    {{-- Create Mode: Bulk Multi-Select --}}
                                    <select name="student_ids[]" class="form-control default-select" multiple data-live-search="true" required>
                                        @foreach($students as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">You can select multiple students to enroll them in this class at once.</small>
                                @endif
                            </div>

                            {{-- Roll Number (Only visible in Edit Mode, auto-generated/ignored in bulk) --}}
                            @if(isset($enrollment))
                            <div class="mb-3 col-md-4">
                                <label class="form-label">{{ __('enrollment.roll_number') }}</label>
                                <input type="text" name="roll_number" class="form-control" value="{{ old('roll_number', $enrollment->roll_number ?? '') }}" placeholder="{{ __('enrollment.enter_roll') }}">
                            </div>
                            @endif

                            {{-- Date Picker --}}
                            <div class="mb-3 col-md-4">
                                <label class="form-label">{{ __('enrollment.enrolled_at') }} <span class="text-danger">*</span></label>
                                <input type="text" name="enrolled_at" class="form-control datepicker" value="{{ old('enrolled_at', isset($enrollment) && $enrollment->enrolled_at ? $enrollment->enrolled_at->format('Y-m-d') : date('Y-m-d')) }}" placeholder="YYYY-MM-DD" required>
                            </div>

                            {{-- Status --}}
                            <div class="mb-3 col-md-4">
                                <label class="form-label">{{ __('enrollment.status_label') }}</label>
                                <select name="status" class="form-control default-select">
                                    <option value="active" {{ (old('status', $enrollment->status ?? '') == 'active') ? 'selected' : '' }}>{{ __('enrollment.active') }}</option>
                                    <option value="promoted" {{ (old('status', $enrollment->status ?? '') == 'promoted') ? 'selected' : '' }}>{{ __('enrollment.promoted') }}</option>
                                    <option value="detained" {{ (old('status', $enrollment->status ?? '') == 'detained') ? 'selected' : '' }}>{{ __('enrollment.detained') }}</option>
                                    <option value="left" {{ (old('status', $enrollment->status ?? '') == 'left') ? 'selected' : '' }}>{{ __('enrollment.left') }}</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">{{ isset($enrollment) ? __('enrollment.update_enrollment') : __('enrollment.save_enrollment') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>