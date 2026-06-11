@extends('layouts.help')

@section('title', __('manual.web_manual_title'))

@section('content')
<div class="container">
    <div class="mb-4">
        <a href="{{ route('help.index') }}" class="text-muted small"><i class="fa fa-arrow-left me-1"></i>{{ __('manual.back_hub') }}</a>
        <h1 class="mt-2 mb-1">{{ __('manual.web_manual_title') }}</h1>
        <p class="text-muted">{{ __('manual.web_manual_desc', ['count' => $moduleCount]) }}</p>
        @if (!empty($contentFallback))
            <div class="locale-fallback-banner mt-2">{{ __('manual.locale_fallback') }}</div>
        @endif
    </div>

    @if ($introduction)
        <div class="help-card mb-4">
            <h5>{{ $introduction['title'] }}</h5>
            <p class="text-muted small mb-2">{{ __('manual.read_first') }}</p>
            <a href="{{ route('manual.web.show', 'introduction') }}" class="btn btn-sm btn-primary">{{ __('manual.read_intro') }}</a>
        </div>
    @endif

    <div class="row g-4">
        @foreach ($parts as $part)
            <div class="col-lg-6">
                <div class="help-card h-100">
                    <h5 class="mb-1">
                        <span class="badge bg-primary me-2">{{ __('manual.part_label', ['id' => $part['id']]) }}</span>
                        {{ $part['title'] }}
                    </h5>
                    <p class="text-muted small mb-3">{{ count($part['modules']) }} {{ __('manual.modules') }}</p>
                    <ul class="list-unstyled mb-0">
                        @foreach ($part['modules'] as $mod)
                            <li class="mb-2">
                                <a href="{{ route('manual.web.show', $mod['slug']) }}">
                                    <strong>{{ $mod['id'] }}</strong> — {{ $mod['title'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
