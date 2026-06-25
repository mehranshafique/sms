@extends('errors.layout')

@section('title', __('errors.419_page_title') . ' — ' . config('app.name'))
@section('status_code', '419')

@section('error_badge')
    <div class="digitex-error-code">419</div>
@endsection

@section('content')
    <h1 class="digitex-error-title">{{ __('errors.419_title') }}</h1>
    <p class="digitex-error-message">{{ __('errors.419_message') }}</p>
    <p class="digitex-error-hint">{{ __('errors.419_hint') }}</p>

    <div class="digitex-error-actions">
        <a href="javascript:location.reload()" class="btn btn-primary">
            <i class="fa fa-rotate-right me-2"></i>{{ __('errors.refresh_page') }}
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
