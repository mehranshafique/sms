@extends('errors.layout')

@section('title', __('errors.500_page_title') . ' — ' . config('app.name'))
@section('status_code', '500')

@section('error_badge')
    <div class="digitex-error-code">500</div>
@endsection

@section('content')
    <h1 class="digitex-error-title">{{ __('errors.500_title') }}</h1>
    <p class="digitex-error-message">{{ __('errors.500_message') }}</p>
    <p class="digitex-error-hint">{{ __('errors.500_hint') }}</p>

    @include('errors.partials.actions')
@endsection
