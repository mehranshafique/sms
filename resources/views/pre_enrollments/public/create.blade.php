@extends('layouts.auth')

@section('title', __('pre_enrollment.public.page_title') . ' — ' . $institution->name)
@section('column_class', 'col-lg-8 col-md-10')

@section('content')
    <div class="text-center mb-3">
        @if($institution->logo)
            <img src="{{ asset('storage/'.$institution->logo) }}" alt="{{ $institution->name }}" class="mb-2" style="max-height:64px;">
        @endif
        <h4 class="mb-1">{{ $institution->name }}</h4>
        <p class="text-muted mb-0">{{ __('pre_enrollment.public.subtitle') }}</p>
        @if($session)
            <small class="text-muted">{{ __('pre_enrollment.session') }}: {{ $session->name }}</small>
        @endif
    </div>

    <div class="d-flex justify-content-end mb-3">
        <div class="btn-group btn-group-sm">
            <a href="{{ route('change-language', ['language' => 'fr']) }}" class="btn btn-outline-secondary {{ app()->getLocale() === 'fr' ? 'active' : '' }}">FR</a>
            <a href="{{ route('change-language', ['language' => 'en']) }}" class="btn btn-outline-secondary {{ app()->getLocale() === 'en' ? 'active' : '' }}">EN</a>
        </div>
    </div>

    <form method="POST" action="{{ route('public.pre-enrollments.store', $institution->code) }}">
        @csrf
        <input type="hidden" name="locale" value="{{ app()->getLocale() }}">
        {{-- Honeypot --}}
        <div style="position:absolute;left:-9999px;top:-9999px;" aria-hidden="true">
            <label>Website</label>
            <input type="text" name="website" tabindex="-1" autocomplete="off">
        </div>

        <h6 class="text-primary mb-3"><i class="fa fa-user me-2"></i>{{ __('pre_enrollment.personal') }}</h6>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">{{ __('pre_enrollment.first_name') }} <span class="text-danger">*</span></label>
                <input type="text" name="first_name" class="form-control" value="{{ old('first_name') }}" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">{{ __('pre_enrollment.last_name') }} <span class="text-danger">*</span></label>
                <input type="text" name="last_name" class="form-control" value="{{ old('last_name') }}" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">{{ __('pre_enrollment.post_name') }}</label>
                <input type="text" name="post_name" class="form-control" value="{{ old('post_name') }}">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">{{ __('pre_enrollment.gender') }} <span class="text-danger">*</span></label>
                <select name="gender" class="form-control" required>
                    <option value="">—</option>
                    <option value="male" @selected(old('gender')==='male')>{{ __('pre_enrollment.gender_male') }}</option>
                    <option value="female" @selected(old('gender')==='female')>{{ __('pre_enrollment.gender_female') }}</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">{{ __('pre_enrollment.dob') }} <span class="text-danger">*</span></label>
                <input type="date" name="dob" class="form-control" value="{{ old('dob') }}" required max="{{ now()->subYear()->toDateString() }}">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">{{ __('pre_enrollment.place_of_birth') }}</label>
                <input type="text" name="place_of_birth" class="form-control" value="{{ old('place_of_birth') }}">
            </div>
        </div>

        <hr class="my-3">
        <h6 class="text-primary mb-3"><i class="fa fa-users me-2"></i>{{ __('pre_enrollment.parent_block') }}</h6>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">{{ __('pre_enrollment.parent_name') }} <span class="text-danger">*</span></label>
                <input type="text" name="parent_name" class="form-control" value="{{ old('parent_name') }}" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">{{ __('pre_enrollment.parent_phone') }} <span class="text-danger">*</span></label>
                <input type="text" name="parent_phone" class="form-control" value="{{ old('parent_phone') }}" placeholder="+243..." required>
                <small class="text-muted">{{ __('pre_enrollment.parent_phone_help') }}</small>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">{{ __('pre_enrollment.parent_email') }}</label>
                <input type="email" name="parent_email" class="form-control" value="{{ old('parent_email') }}">
            </div>
        </div>

        <hr class="my-3">
        <h6 class="text-primary mb-3"><i class="fa fa-graduation-cap me-2"></i>{{ __('pre_enrollment.class_block') }}</h6>
        <div class="row">
            @if($grades->isNotEmpty())
            <div class="col-md-4 mb-3">
                <label class="form-label">{{ __('pre_enrollment.grade') }}</label>
                <select name="requested_grade_level_id" class="form-control">
                    <option value="">—</option>
                    @foreach($grades as $id => $name)
                        <option value="{{ $id }}" @selected(old('requested_grade_level_id')==$id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            @if($classes->isNotEmpty())
            <div class="col-md-4 mb-3">
                <label class="form-label">{{ __('pre_enrollment.class') }}</label>
                <select name="requested_class_section_id" class="form-control">
                    <option value="">—</option>
                    @foreach($classes as $id => $name)
                        <option value="{{ $id }}" @selected(old('requested_class_section_id')==$id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-md-4 mb-3">
                <label class="form-label">{{ __('pre_enrollment.option') }}</label>
                <input type="text" name="requested_option" class="form-control" value="{{ old('requested_option') }}" placeholder="{{ __('pre_enrollment.public.option_placeholder') }}">
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">{{ __('pre_enrollment.notes') }}</label>
                <textarea name="notes" class="form-control" rows="2" maxlength="1000">{{ old('notes') }}</textarea>
            </div>
        </div>

        <p class="small text-muted mb-3">{{ __('pre_enrollment.public.privacy_note') }}</p>

        <button type="submit" class="btn btn-primary w-100">
            {{ __('pre_enrollment.public.submit') }}
        </button>
    </form>

    <div class="text-center mt-4">
        <small class="text-muted">{{ __('pre_enrollment.public.powered_by') }}</small>
    </div>
@endsection
