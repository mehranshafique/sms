@php
    $editing = isset($schoolEvent) && $schoolEvent->exists;
@endphp
<form action="{{ $editing ? route('school-events.update', $schoolEvent) : route('school-events.store') }}" method="POST" id="schoolEventForm" class="ajax-form">
    @csrf
    @if($editing)
        @method('PUT')
    @endif
    <div class="row justify-content-center">
        <div class="col-xl-10 col-lg-11">
            <div class="card shadow-sm border-0" style="border-radius:15px;">
                <div class="card-header border-0 pb-0 pt-4 px-4 bg-transparent">
                    <h4 class="card-title fw-bold mb-0">{{ $editing ? __('school_event.edit') : __('school_event.create') }}</h4>
                    <p class="text-muted small mb-0 mt-1">{{ __('school_event.create_help') }}</p>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="row">
                        <div class="mb-3 col-md-12">
                            <label class="form-label fw-bold">{{ __('school_event.field_name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $schoolEvent->name ?? '') }}" placeholder="{{ __('school_event.field_name_placeholder') }}" required>
                        </div>

                        <div class="mb-3 col-md-6">
                            <label class="form-label fw-bold">{{ __('school_event.field_date') }} <span class="text-danger">*</span></label>
                            <input type="text" name="event_date" class="form-control datepicker" value="{{ old('event_date', isset($schoolEvent) ? $schoolEvent->event_date?->format('Y-m-d') : date('Y-m-d')) }}" placeholder="YYYY-MM-DD" autocomplete="off" required>
                        </div>

                        <div class="mb-3 col-md-6">
                            <label class="form-label fw-bold">{{ __('school_event.field_time') }}</label>
                            <input type="text" name="event_time" class="form-control timepicker" value="{{ old('event_time', isset($schoolEvent) && $schoolEvent->event_time ? substr((string) $schoolEvent->event_time, 0, 5) : '') }}" placeholder="HH:mm" autocomplete="off">
                        </div>

                        <div class="mb-3 col-md-6">
                            <label class="form-label fw-bold">{{ __('school_event.field_venue') }}</label>
                            <input type="text" name="venue" class="form-control" value="{{ old('venue', $schoolEvent->venue ?? '') }}" placeholder="{{ __('school_event.field_venue_placeholder') }}">
                        </div>

                        <div class="mb-3 col-md-6">
                            <label class="form-label fw-bold">{{ __('school_event.field_contact') }}</label>
                            <input type="text" name="contact" class="form-control" value="{{ old('contact', $schoolEvent->contact ?? '') }}" placeholder="{{ __('school_event.field_contact_placeholder') }}">
                        </div>

                        <div class="mb-3 col-md-6">
                            <label class="form-label fw-bold">{{ __('school_event.field_audience') }} <span class="text-danger">*</span></label>
                            @php $audienceValue = old('audience', $schoolEvent->audience ?? 'parents'); @endphp
                            <select name="audience" class="form-control default-select" required>
                                <option value="parents" @selected($audienceValue === 'parents')>{{ __('school_event.audience_parents') }}</option>
                                <option value="students" @selected($audienceValue === 'students')>{{ __('school_event.audience_students') }}</option>
                                <option value="staff" @selected($audienceValue === 'staff')>{{ __('school_event.audience_staff') }}</option>
                                <option value="class" @selected($audienceValue === 'class')>{{ __('school_event.audience_class') }}</option>
                            </select>
                            <small class="text-muted">{{ __('school_event.field_audience_help') }}</small>
                        </div>

                        <div class="mb-3 col-md-6">
                            <label class="form-label fw-bold">{{ __('school_event.field_classes') }}</label>
                            @php $selectedClassIds = collect(old('class_section_ids', $schoolEvent->class_section_ids ?? [])); @endphp
                            <select name="class_section_ids[]" class="form-control default-select multi-select" multiple data-live-search="true" title="{{ __('school_event.field_classes_placeholder') }}">
                                @foreach($sections as $section)
                                    <option value="{{ $section->id }}" @selected($selectedClassIds->contains($section->id))>{{ class_section_label($section) }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">{{ __('school_event.field_classes_help') }}</small>
                        </div>

                        <div class="mb-3 col-md-12">
                            <label class="form-label fw-bold">{{ __('school_event.field_description') }}</label>
                            <textarea name="description" class="form-control" rows="4" placeholder="{{ __('school_event.field_description_placeholder') }}">{{ old('description', $schoolEvent->description ?? '') }}</textarea>
                        </div>
                    </div>

                    <div class="text-end border-top pt-3 mt-2">
                        <a href="{{ $editing ? route('school-events.show', $schoolEvent) : route('school-events.index') }}" class="btn btn-light me-2">{{ __('school_event.cancel') }}</a>
                        <button type="submit" class="btn btn-primary shadow-sm">
                            <i class="fa fa-save me-1"></i> {{ __('school_event.save') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
