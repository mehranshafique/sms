@extends('layouts.help')

@section('title', __('manual.mobile_manual_title'))

@section('content')
<div class="container">
    <div class="mb-4">
        <a href="{{ route('help.index') }}" class="text-muted small"><i class="fa fa-arrow-left me-1"></i>{{ __('manual.back_hub') }}</a>
        <h1 class="mt-2 mb-1">{{ __('manual.mobile_manual_title') }}</h1>
        <p class="text-muted">{{ __('manual.mobile_manual_desc', ['count' => count($parts)]) }}</p>
        @if (!empty($contentFallback))
            <div class="locale-fallback-banner mt-2">{{ __('manual.locale_fallback') }}</div>
        @endif
    </div>

    @if ($introduction)
        <div class="help-card mb-4">
            <h5>{{ $introduction['title'] }}</h5>
            <a href="{{ route('manual.mobile.show', 'introduction') }}" class="btn btn-sm btn-success">{{ __('manual.read_intro') }}</a>
        </div>
    @endif

    <div class="help-card p-0 overflow-hidden">
        @foreach ($parts as $part)
            <a href="{{ route('manual.mobile.show', $part['slug']) }}"
               class="d-flex align-items-center justify-content-between p-3 border-bottom text-decoration-none text-dark {{ $loop->last ? 'border-0' : '' }}">
                <div>
                    <span class="badge bg-success me-2">{{ __('manual.mobile_part_label', ['num' => $part['number']]) }}</span>
                    <strong>{{ $part['title'] }}</strong>
                </div>
                <i class="fa fa-chevron-right text-muted"></i>
            </a>
        @endforeach
    </div>
</div>
@endsection
