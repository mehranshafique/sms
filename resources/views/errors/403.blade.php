@extends('errors.layout')

@section('title', __('errors.403_page_title') . ' — ' . config('app.name'))
@section('status_code', '403')

@section('error_badge')
    <div class="digitex-error-code">403</div>
@endsection

@section('content')
    <h1 class="digitex-error-title">{{ __('errors.403_title') }}</h1>

    <p class="digitex-error-message">
        @if(isset($exception) && $exception->getMessage() && $exception->getMessage() !== 'This action is unauthorized.')
            {{ $exception->getMessage() }}
        @else
            {{ __('errors.403_message') }}
        @endif
    </p>
    <p class="digitex-error-hint">{{ __('errors.403_hint') }}</p>

    @include('errors.partials.actions')
@endsection
