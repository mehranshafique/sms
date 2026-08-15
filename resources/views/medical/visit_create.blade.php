@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('medical.record_visit') }}</h4>
                    <p class="mb-0">{{ __('medical.record_visit_subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('medical-records.index') }}" class="btn btn-light shadow-sm">
                    <i class="fa fa-arrow-left me-2"></i> {{ __('medical.back_to_log') }}
                </a>
            </div>
        </div>

        @if($profile && $profile->hasCriticalInfo())
        <div class="alert alert-danger">
            <h5 class="mb-2"><i class="fa fa-triangle-exclamation me-2"></i>{{ __('medical.critical_alert') }}</h5>
            @if(filled($profile->allergies))<div><strong>{{ __('medical.allergies') }}:</strong> {{ $profile->allergies }}</div>@endif
            @if(filled($profile->chronic_conditions))<div><strong>{{ __('medical.chronic_conditions') }}:</strong> {{ $profile->chronic_conditions }}</div>@endif
            @if(filled($profile->current_medication))<div><strong>{{ __('medical.current_medication') }}:</strong> {{ $profile->current_medication }}</div>@endif
        </div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('medical-records.visits.store') }}" method="POST" class="ajax-form">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('medical.student') }} <span class="text-danger">*</span></label>
                                    <select name="student_id" class="form-control default-select" required>
                                        <option value="">-- {{ __('medical.select_student') }} --</option>
                                        @foreach($students as $id => $name)
                                            <option value="{{ $id }}" {{ (int) old('student_id', $selectedStudent) === (int) $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('medical.visited_at') }} <span class="text-danger">*</span></label>
                                    <input type="datetime-local" name="visited_at" class="form-control"
                                        value="{{ old('visited_at', now()->format('Y-m-d\TH:i')) }}" required>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label class="form-label">{{ __('medical.reason') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="reason" class="form-control" placeholder="{{ __('medical.reason_placeholder') }}"
                                        value="{{ old('reason') }}" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('medical.observation') }}</label>
                                    <textarea name="observation" class="form-control" rows="3" placeholder="{{ __('medical.observation_placeholder') }}">{{ old('observation') }}</textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('medical.action_taken') }}</label>
                                    <textarea name="action_taken" class="form-control" rows="3" placeholder="{{ __('medical.action_taken_placeholder') }}">{{ old('action_taken') }}</textarea>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label">{{ __('medical.temperature') }}</label>
                                    <input type="text" name="temperature" class="form-control" placeholder="37.2" value="{{ old('temperature') }}">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">{{ __('medical.blood_pressure') }}</label>
                                    <input type="text" name="blood_pressure" class="form-control" placeholder="120/80" value="{{ old('blood_pressure') }}">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">{{ __('medical.outcome') }} <span class="text-danger">*</span></label>
                                    <select name="outcome" class="form-control default-select" required>
                                        @foreach($outcomes as $outcome)
                                            <option value="{{ $outcome }}" {{ old('outcome') === $outcome ? 'selected' : '' }}>{{ __('medical.outcome_' . $outcome) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label d-block">{{ __('medical.parent_informed') }}</label>
                                    <div class="form-check form-switch mt-2">
                                        <input type="hidden" name="parent_informed" value="0">
                                        <input class="form-check-input" type="checkbox" name="parent_informed" value="1" id="parentInformed"
                                            {{ old('parent_informed') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="parentInformed">{{ __('medical.parent_informed_help') }}</label>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary submit-btn">{{ __('medical.save_visit') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
