<form action="{{ isset($enrollment) ? route('enrollments.update', $enrollment->id) : route('enrollments.store') }}" method="POST" id="enrollmentForm">
    @csrf
    @if(isset($enrollment))
        @method('PUT')
    @endif
    
    <div class="row">
        <div class="col-xl-12 col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{ __('enrollment.basic_information') }}</h4>
                </div>
                <div class="card-body">
                    <div class="basic-form">
                        <div class="row">
                            
                            {{-- Student Selection (Disabled on Edit usually) --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('enrollment.select_student') }} <span class="text-danger">*</span></label>
                                <select name="student_id" class="form-control default-select" required {{ isset($enrollment) ? 'disabled' : '' }}>
                                    <option value="">{{ __('enrollment.select_student') }}</option>
                                    @foreach($students as $id => $name)
                                        <option value="{{ $id }}" {{ (old('student_id', $enrollment->student_id ?? '') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Class Selection --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('enrollment.select_class') }} <span class="text-danger">*</span></label>
                                <select name="class_section_id" class="form-control default-select" required>
                                    <option value="">{{ __('enrollment.select_class') }}</option>
                                    @foreach($classes as $id => $name)
                                        <option value="{{ $id }}" {{ (old('class_section_id', $enrollment->class_section_id ?? '') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3 col-md-4">
                                <label class="form-label">{{ __('enrollment.roll_number') }}</label>
                                <input type="text" name="roll_number" class="form-control" value="{{ old('roll_number', $enrollment->roll_number ?? '') }}" placeholder="{{ __('enrollment.enter_roll') }}">
                            </div>

                            <div class="mb-3 col-md-4">
                                <label class="form-label">{{ __('enrollment.enrolled_at') }} <span class="text-danger">*</span></label>
                                <input type="date" name="enrolled_at" class="form-control" value="{{ old('enrolled_at', isset($enrollment) && $enrollment->enrolled_at ? $enrollment->enrolled_at->format('Y-m-d') : date('Y-m-d')) }}" required>
                            </div>

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