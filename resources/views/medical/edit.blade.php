@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('medical.edit_record') }}</h4>
                    <p class="mb-0">{{ $student->full_name }} &middot; {{ $student->admission_number ?? '—' }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('medical-records.show', $student->id) }}" class="btn btn-light shadow-sm">
                    <i class="fa fa-arrow-left me-2"></i> {{ __('medical.back_to_record') }}
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('medical-records.update', $student->id) }}" method="POST" class="ajax-form">
                            @csrf
                            @method('PUT')

                            <h5 class="text-primary mb-3">{{ __('medical.medical_information') }}</h5>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ __('medical.blood_group') }}</label>
                                    <select name="blood_group" class="form-control default-select">
                                        <option value="">—</option>
                                        @foreach($bloodGroups as $group)
                                            <option value="{{ $group }}" {{ old('blood_group', $profile->blood_group ?? $student->blood_group) === $group ? 'selected' : '' }}>{{ $group }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ __('medical.information_date') }}</label>
                                    <input type="date" name="information_date" class="form-control"
                                        value="{{ old('information_date', $profile->information_date?->format('Y-m-d')) }}">
                                    <small class="text-muted">{{ __('medical.information_date_help') }}</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label d-block">{{ __('medical.consent_first_aid') }}</label>
                                    <div class="form-check form-switch mt-2">
                                        <input type="hidden" name="consent_first_aid" value="0">
                                        <input class="form-check-input" type="checkbox" name="consent_first_aid" value="1" id="consentFirstAid"
                                            {{ old('consent_first_aid', $profile->consent_first_aid ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="consentFirstAid">{{ __('medical.consent_first_aid_help') }}</label>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('medical.allergies') }}</label>
                                    <textarea name="allergies" class="form-control" rows="3" placeholder="{{ __('medical.allergies_placeholder') }}">{{ old('allergies', $profile->allergies) }}</textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('medical.chronic_conditions') }}</label>
                                    <textarea name="chronic_conditions" class="form-control" rows="3" placeholder="{{ __('medical.chronic_conditions_placeholder') }}">{{ old('chronic_conditions', $profile->chronic_conditions) }}</textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('medical.current_medication') }}</label>
                                    <textarea name="current_medication" class="form-control" rows="3">{{ old('current_medication', $profile->current_medication) }}</textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('medical.medical_notes') }}</label>
                                    <textarea name="medical_notes" class="form-control" rows="3" placeholder="{{ __('medical.medical_notes_placeholder') }}">{{ old('medical_notes', $profile->medical_notes) }}</textarea>
                                </div>
                            </div>

                            <h5 class="text-primary mb-3 mt-2">{{ __('medical.emergency_contact') }}</h5>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">{{ __('medical.contact_name') }}</label>
                                    <input type="text" name="emergency_contact_name" class="form-control" value="{{ old('emergency_contact_name', $profile->emergency_contact_name) }}">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">{{ __('medical.contact_relation') }}</label>
                                    <input type="text" name="emergency_contact_relation" class="form-control" value="{{ old('emergency_contact_relation', $profile->emergency_contact_relation) }}">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">{{ __('medical.contact_phone') }}</label>
                                    <input type="text" name="emergency_contact_phone" class="form-control" value="{{ old('emergency_contact_phone', $profile->emergency_contact_phone) }}">
                                    <small class="text-muted">{{ __('medical.contact_phone_help') }}</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">{{ __('medical.alt_phone') }}</label>
                                    <input type="text" name="emergency_contact_alt_phone" class="form-control" value="{{ old('emergency_contact_alt_phone', $profile->emergency_contact_alt_phone) }}">
                                </div>
                            </div>

                            <h5 class="text-primary mb-3 mt-2">{{ __('medical.doctor_insurance') }}</h5>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">{{ __('medical.doctor_name') }}</label>
                                    <input type="text" name="doctor_name" class="form-control" value="{{ old('doctor_name', $profile->doctor_name) }}">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">{{ __('medical.doctor_phone') }}</label>
                                    <input type="text" name="doctor_phone" class="form-control" value="{{ old('doctor_phone', $profile->doctor_phone) }}">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">{{ __('medical.insurance_provider') }}</label>
                                    <input type="text" name="insurance_provider" class="form-control" value="{{ old('insurance_provider', $profile->insurance_provider) }}">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">{{ __('medical.insurance_number') }}</label>
                                    <input type="text" name="insurance_number" class="form-control" value="{{ old('insurance_number', $profile->insurance_number) }}">
                                </div>
                            </div>

                            <div class="alert alert-light border mt-2">
                                <i class="fa fa-lock me-2"></i>{{ __('medical.privacy_notice') }}
                            </div>

                            <button type="submit" class="btn btn-primary submit-btn">{{ __('medical.save') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
