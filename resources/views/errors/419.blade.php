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
        {{-- Must GET (not reload): reload() re-POSTs the expired form and loops on 419. --}}
        <a href="{{ Auth::check() ? url()->current() : route('login') }}" class="btn btn-primary" data-419-refresh>
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
    <script>
        // Guard: if opened via history back into a POST 419, prefer a clean GET.
        document.querySelector('[data-419-refresh]')?.addEventListener('click', function (e) {
            e.preventDefault();
            window.location.replace(this.href);
        });
    </script>
@endsection
