@extends('errors.layout')

@section('title', __('errors.page_title') . ' — ' . config('app.name'))
@section('status_code', '404')

@section('error_badge')
    <div class="digitex-error-code">404</div>
@endsection

@section('content')
    <h1 class="digitex-error-title">{{ __('errors.404_title') }}</h1>

    <p class="digitex-error-message">{{ __('errors.404_message') }}</p>
    <p class="digitex-error-hint">{{ __('errors.404_hint') }}</p>

    @include('errors.partials.actions')
@endsection
