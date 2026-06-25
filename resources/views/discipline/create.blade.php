@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('discipline.create_new') }}</h4>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('discipline.index') }}" class="btn btn-light">{{ __('budget.cancel') }}</a>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-xl-9">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <form action="{{ route('discipline.store') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">{{ __('discipline.student') }} <span class="text-danger">*</span></label>
                                    <select name="student_id" class="form-control default-select" data-live-search="true" required>
                                        <option value="">{{ __('discipline.student') }}</option>
                                        @foreach($students as $id => $name)
                                            <option value="{{ $id }}" @selected(old('student_id') == $id)>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">{{ __('discipline.incident_date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="incident_date" class="form-control" value="{{ old('incident_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">{{ __('discipline.incident_type') }} <span class="text-danger">*</span></label>
                                    <select name="incident_type" class="form-control default-select" required>
                                        @foreach(\App\Models\DisciplinaryRecord::TYPES as $tp)
                                            <option value="{{ $tp }}" @selected(old('incident_type') === $tp)>{{ __('discipline.type_' . $tp) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">{{ __('discipline.severity') }} <span class="text-danger">*</span></label>
                                    <select name="severity" class="form-control default-select" required>
                                        @foreach(\App\Models\DisciplinaryRecord::SEVERITIES as $sv)
                                            <option value="{{ $sv }}" @selected(old('severity', 'minor') === $sv)>{{ __('discipline.severity_' . $sv) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label fw-bold">{{ __('discipline.title') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control" value="{{ old('title') }}" required maxlength="255" placeholder="e.g. Repeated late arrival to morning assembly">
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">{{ __('discipline.description') }}</label>
                                    <textarea name="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">{{ __('discipline.action_taken') }}</label>
                                    <textarea name="action_taken" class="form-control" rows="3" placeholder="e.g. Verbal warning issued, parent to be contacted">{{ old('action_taken') }}</textarea>
                                </div>
                                <div class="col-12 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="notify_parents" value="1" id="notifyParents" @checked(old('notify_parents', true))>
                                        <label class="form-check-label" for="notifyParents">{{ __('discipline.notify_parents') }}</label>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary px-4">{{ __('budget.save') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
