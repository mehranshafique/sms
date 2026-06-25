@extends('errors.layout')

@section('title', __('errors.503_page_title') . ' — ' . config('app.name'))
@section('status_code', '503')

@section('error_badge')
    <div class="digitex-error-code">503</div>
@endsection

@section('content')
    <h1 class="digitex-error-title">{{ __('errors.503_title') }}</h1>
    <p class="digitex-error-message">{{ __('errors.503_message') }}</p>
    <p class="digitex-error-hint">{{ __('errors.503_hint') }}</p>

    <div class="digitex-error-actions">
        <a href="javascript:location.reload()" class="btn btn-primary">
            <i class="fa fa-rotate-right me-2"></i>{{ __('errors.try_again') }}
        </a>
        @auth
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                <i class="fa fa-gauge-high me-2"></i>{{ __('errors.go_dashboard') }}
            </a>
        @else
            <a href="{{ route('login') }}" class="btn btn-outline-secondary">
                <i class="fa fa-right-to-bracket me-2"></i>{{ __('errors.go_home') }}
            </a>
        @endauth
    </div>
@endsection
