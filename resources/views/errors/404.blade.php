@extends('errors.layout')

@section('title', __('errors.page_title') . ' — ' . config('app.name'))

@section('error_badge')
    <div class="digitex-error-code">404</div>
@endsection

@section('content')
    <h1 class="digitex-error-title">{{ __('errors.404_title') }}</h1>

    <p class="digitex-error-message">{{ __('errors.404_message') }}</p>
    <p class="digitex-error-hint">{{ __('errors.404_hint') }}</p>

    <div class="digitex-error-actions">
        @auth
            <a href="{{ route('dashboard') }}" class="btn btn-primary">
                <i class="fa fa-gauge-high me-2"></i>{{ __('errors.go_dashboard') }}
            </a>
        @else
            <a href="{{ route('login') }}" class="btn btn-primary">
                <i class="fa fa-right-to-bracket me-2"></i>{{ __('errors.go_home') }}
            </a>
        @endauth

        <a href="{{ route('help.index') }}" class="btn btn-outline-secondary">
            <i class="fa fa-circle-question me-2"></i>{{ __('errors.go_help') }}
        </a>
    </div>
@endsection
