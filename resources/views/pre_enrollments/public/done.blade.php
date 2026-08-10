@extends('layouts.auth')

@section('title', __('pre_enrollment.public.done_title') . ' — ' . $institution->name)
@section('column_class', 'col-lg-6 col-md-8')

@section('content')
    <div class="text-center py-3">
        <div class="mb-3 text-success" style="font-size:48px;">
            <i class="fa fa-check-circle"></i>
        </div>
        <h4 class="mb-2">{{ __('pre_enrollment.public.done_title') }}</h4>
        <p class="text-muted mb-3">{{ __('pre_enrollment.public.done_subtitle', ['school' => $institution->name]) }}</p>

        <div class="bg-light rounded p-3 mb-3">
            <div class="text-muted small">{{ __('pre_enrollment.temporary_id') }}</div>
            <div class="fs-4 fw-bold text-primary">{{ $temporaryId }}</div>
            @if($candidate)
                <div class="mt-2">{{ $candidate->fullName() }}</div>
            @endif
        </div>

        <p class="small text-muted mb-4">{{ __('pre_enrollment.public.keep_id') }}</p>

        <a href="{{ route('public.pre-enrollments.create', $institution->code) }}" class="btn btn-outline-primary btn-sm">
            {{ __('pre_enrollment.public.register_another') }}
        </a>
    </div>
@endsection
