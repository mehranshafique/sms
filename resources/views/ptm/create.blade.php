@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('ptm.create_title') }}</h4>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('ptm.index') }}" class="btn btn-light">{{ __('ptm.back') }}</a>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-xl-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <form action="{{ route('ptm.store') }}" method="POST" class="ajax-form">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">{{ __('ptm.field_student') }} <span class="text-danger">*</span></label>
                                    <select name="student_id" class="form-control default-select" data-live-search="true" required>
                                        <option value="">{{ __('ptm.field_student') }}</option>
                                        @foreach($students as $id => $name)
                                            <option value="{{ $id }}" @selected(old('student_id') == $id)>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">{{ __('ptm.field_date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="preferred_date" class="form-control" value="{{ old('preferred_date', date('Y-m-d')) }}" required>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label fw-bold">{{ __('ptm.field_topic') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="topic" class="form-control" value="{{ old('topic') }}" maxlength="200" required placeholder="{{ __('ptm.topic_placeholder') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">{{ __('ptm.field_status') }}</label>
                                    <select name="status" class="form-control default-select">
                                        @foreach(['confirmed', 'pending', 'completed'] as $st)
                                            <option value="{{ $st }}" @selected(old('status', 'confirmed') === $st)>{{ __('ptm.status_' . $st) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">{{ __('ptm.field_notes') }}</label>
                                    <textarea name="notes" class="form-control" rows="3" placeholder="{{ __('ptm.notes_placeholder') }}">{{ old('notes') }}</textarea>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">{{ __('ptm.field_staff_notes') }}</label>
                                    <textarea name="staff_notes" class="form-control" rows="3" placeholder="{{ __('ptm.staff_notes_placeholder') }}">{{ old('staff_notes') }}</textarea>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary px-4">{{ __('ptm.save') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
