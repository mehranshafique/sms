<form action="{{ route('school-events.store') }}" method="POST" id="schoolEventForm">
    @csrf
    <div class="row justify-content-center">
        <div class="col-xl-10 col-lg-11">
            <div class="card shadow-sm border-0" style="border-radius:15px;">
                <div class="card-header border-0 pb-0 pt-4 px-4 bg-transparent">
                    <h4 class="card-title fw-bold mb-0">{{ __('school_event.create') }}</h4>
                    <p class="text-muted small mb-0 mt-1">{{ __('school_event.create_help') }}</p>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="row">
                        <div class="mb-3 col-md-12">
                            <label class="form-label fw-bold">{{ __('school_event.field_name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="{{ __('school_event.field_name_placeholder') }}" required>
                        </div>

                        <div class="mb-3 col-md-6">
                            <label class="form-label fw-bold">{{ __('school_event.field_date') }} <span class="text-danger">*</span></label>
                            <input type="text" name="event_date" class="form-control datepicker" value="{{ old('event_date', date('Y-m-d')) }}" placeholder="YYYY-MM-DD" autocomplete="off" required>
                        </div>

                        <div class="mb-3 col-md-6">
                            <label class="form-label fw-bold">{{ __('school_event.field_time') }}</label>
                            <input type="text" name="event_time" class="form-control timepicker" value="{{ old('event_time') }}" placeholder="HH:mm" autocomplete="off">
                        </div>

                        <div class="mb-3 col-md-6">
                            <label class="form-label fw-bold">{{ __('school_event.field_venue') }}</label>
                            <input type="text" name="venue" class="form-control" value="{{ old('venue') }}" placeholder="{{ __('school_event.field_venue_placeholder') }}">
                        </div>

                        <div class="mb-3 col-md-6">
                            <label class="form-label fw-bold">{{ __('school_event.field_contact') }}</label>
                            <input type="text" name="contact" class="form-control" value="{{ old('contact') }}" placeholder="{{ __('school_event.field_contact_placeholder') }}">
                        </div>

                        <div class="mb-3 col-md-6">
                            <label class="form-label fw-bold">{{ __('school_event.field_audience') }} <span class="text-danger">*</span></label>
                            <select name="audience" class="form-control default-select" required>
                                <option value="parents" @selected(old('audience') === 'parents')>{{ __('school_event.audience_parents') }}</option>
                                <option value="students" @selected(old('audience') === 'students')>{{ __('school_event.audience_students') }}</option>
                                <option value="staff" @selected(old('audience') === 'staff')>{{ __('school_event.audience_staff') }}</option>
                                <option value="class" @selected(old('audience') === 'class')>{{ __('school_event.audience_class') }}</option>
                            </select>
                        </div>

                        <div class="mb-3 col-md-6">
                            <label class="form-label fw-bold">{{ __('school_event.field_classes') }}</label>
                            <select name="class_section_ids[]" class="form-control default-select multi-select" multiple data-live-search="true" title="{{ __('school_event.field_classes_placeholder') }}">
                                @foreach($sections as $section)
                                    <option value="{{ $section->id }}" @selected(collect(old('class_section_ids', []))->contains($section->id))>{{ class_section_label($section) }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">{{ __('school_event.field_classes_help') }}</small>
                        </div>

                        <div class="mb-3 col-md-12">
                            <label class="form-label fw-bold">{{ __('school_event.field_description') }}</label>
                            <textarea name="description" class="form-control" rows="4" placeholder="{{ __('school_event.field_description_placeholder') }}">{{ old('description') }}</textarea>
                        </div>
                    </div>

                    <div class="text-end border-top pt-3 mt-2">
                        <a href="{{ route('school-events.index') }}" class="btn btn-light me-2">{{ __('school_event.cancel') }}</a>
                        <button type="submit" class="btn btn-primary shadow-sm">
                            <i class="fa fa-save me-1"></i> {{ __('school_event.save') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
