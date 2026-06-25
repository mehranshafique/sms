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
