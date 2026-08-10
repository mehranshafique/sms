@extends('layout.layout')

@section('content')
@php
    $candidate = $candidate ?? null;
    $isEdit = (bool) $candidate;
    $val = function (string $field, $default = null) use ($candidate) {
        return old($field, $candidate ? ($candidate->{$field} ?? $default) : $default);
    };
    $dobValue = $candidate && $candidate->dob ? $candidate->dob->format('Y-m-d') : null;
@endphp
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-7 p-md-0">
                <div class="welcome-text">
                    <h4>{{ $isEdit ? __('pre_enrollment.edit_title') : __('pre_enrollment.create_title') }}</h4>
                    <p class="mb-0">
                        {{ $isEdit ? $candidate->temporary_id . ' — ' . $candidate->fullName() : __('pre_enrollment.manage_subtitle') }}
                    </p>
                </div>
            </div>
            <div class="col-sm-5 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ $isEdit ? route('pre-enrollments.show', $candidate) : route('pre-enrollments.index') }}" class="btn btn-light btn-rounded">
                    <i class="fa fa-arrow-left me-2"></i>{{ __('pre_enrollment.back') }}
                </a>
            </div>
        </div>

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fa fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <form method="POST"
              action="{{ $isEdit ? route('pre-enrollments.update', $candidate) : route('pre-enrollments.store') }}"
              class="card h-auto shadow-sm border-0">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif
            <div class="card-body">
                <h5 class="mb-3 text-primary"><i class="la la-user me-2"></i>{{ __('pre_enrollment.personal') }}</h5>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('pre_enrollment.first_name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ $val('first_name') }}" required>
                        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('pre_enrollment.last_name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ $val('last_name') }}" required>
                        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('pre_enrollment.post_name') }}</label>
                        <input type="text" name="post_name" class="form-control" value="{{ $val('post_name') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('pre_enrollment.gender') }}</label>
                        <select name="gender" class="form-control default-select">
                            <option value="">—</option>
                            <option value="male" @selected($val('gender')==='male')>{{ __('pre_enrollment.gender_male') }}</option>
                            <option value="female" @selected($val('gender')==='female')>{{ __('pre_enrollment.gender_female') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('pre_enrollment.dob') }}</label>
                        <input type="text" name="dob" class="form-control datepicker @error('dob') is-invalid @enderror" value="{{ old('dob', $dobValue) }}" placeholder="YYYY-MM-DD" autocomplete="off">
                        @error('dob')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('pre_enrollment.place_of_birth') }}</label>
                        <input type="text" name="place_of_birth" class="form-control" value="{{ $val('place_of_birth') }}">
                    </div>
                </div>

                <hr class="my-4">
                <h5 class="mb-3 text-primary"><i class="la la-users me-2"></i>{{ __('pre_enrollment.parent_block') }}</h5>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('pre_enrollment.parent_name') }}</label>
                        <input type="text" name="parent_name" class="form-control" value="{{ $val('parent_name') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('pre_enrollment.parent_phone') }}</label>
                        <input type="text" name="parent_phone" class="form-control @error('parent_phone') is-invalid @enderror" value="{{ $val('parent_phone') }}" placeholder="+243...">
                        @error('parent_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">{{ __('pre_enrollment.parent_phone_help') }}</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('pre_enrollment.parent_email') }}</label>
                        <input type="email" name="parent_email" class="form-control @error('parent_email') is-invalid @enderror" value="{{ $val('parent_email') }}">
                        @error('parent_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <hr class="my-4">
                <h5 class="mb-3 text-primary"><i class="la la-graduation-cap me-2"></i>{{ __('pre_enrollment.class_block') }}</h5>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('pre_enrollment.grade') }}</label>
                        <select name="requested_grade_level_id" class="form-control default-select">
                            <option value="">—</option>
                            @foreach($grades as $id => $name)
                                <option value="{{ $id }}" @selected($val('requested_grade_level_id')==$id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('pre_enrollment.class') }}</label>
                        <select name="requested_class_section_id" class="form-control default-select">
                            <option value="">—</option>
                            @foreach($classes as $id => $name)
                                <option value="{{ $id }}" @selected($val('requested_class_section_id')==$id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('pre_enrollment.option') }}</label>
                        <input type="text" name="requested_option" class="form-control" value="{{ $val('requested_option') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('pre_enrollment.session') }}</label>
                        <select name="academic_session_id" class="form-control default-select">
                            <option value="">—</option>
                            @foreach($sessions as $id => $name)
                                <option value="{{ $id }}" @selected($val('academic_session_id')==$id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label class="form-label">{{ __('pre_enrollment.notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2">{{ $val('notes') }}</textarea>
                    </div>
                </div>

                @if(! $isEdit)
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="allow_duplicate" value="1" id="allowDuplicate" @checked(old('allow_duplicate'))>
                        <label class="form-check-label" for="allowDuplicate">{{ __('pre_enrollment.allow_duplicate') }}</label>
                    </div>
                @endif
            </div>
            <div class="card-footer border-0 bg-transparent d-flex justify-content-end gap-2">
                <a href="{{ $isEdit ? route('pre-enrollments.show', $candidate) : route('pre-enrollments.index') }}" class="btn btn-light btn-rounded">{{ __('pre_enrollment.cancel') }}</a>
                <button class="btn btn-primary btn-rounded">
                    <i class="fa fa-save me-2"></i>{{ $isEdit ? __('pre_enrollment.update') : __('pre_enrollment.save') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
