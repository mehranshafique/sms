@extends('layouts.auth')

@section('title', __('pre_enrollment.public.closed') . ' — ' . $institution->name)
@section('column_class', 'col-lg-6 col-md-8')

@section('content')
    <div class="text-center py-4">
        <div class="mb-3 text-warning" style="font-size:42px;">
            <i class="fa fa-lock"></i>
        </div>
        <h4 class="mb-2">{{ $institution->name }}</h4>
        <p class="text-muted mb-0">{{ __('pre_enrollment.public.closed') }}</p>
        <p class="small text-muted mt-2">{{ __('pre_enrollment.public.closed_hint') }}</p>
    </div>
@endsection
