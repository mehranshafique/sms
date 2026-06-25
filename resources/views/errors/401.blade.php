@extends('errors.layout')

@section('title', __('errors.401_page_title') . ' — ' . config('app.name'))
@section('status_code', '401')

@section('error_badge')
    <div class="digitex-error-code">401</div>
@endsection

@section('content')
    <h1 class="digitex-error-title">{{ __('errors.401_title') }}</h1>
    <p class="digitex-error-message">{{ __('errors.401_message') }}</p>
    <p class="digitex-error-hint">{{ __('errors.401_hint') }}</p>

    @include('errors.partials.actions')
@endsection
