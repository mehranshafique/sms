@extends('layouts.help')

@section('title', __('community.page_title'))

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h1 class="mb-1">{{ __('community.page_title') }}</h1>
            <p class="text-muted mb-0">{{ __('community.page_subtitle') }}</p>
        </div>
        @auth
            <a href="{{ route('community.create') }}" class="btn btn-primary">
                <i class="fa fa-plus me-1"></i>{{ __('community.new_thread') }}
            </a>
        @else
            <a href="{{ route('login') }}" class="btn btn-outline-primary">{{ __('community.login_to_post') }}</a>
        @endauth
    </div>

    <div class="mb-3 d-flex flex-wrap gap-2">
        <a href="{{ route('community.index') }}"
           class="btn btn-sm {{ !$category ? 'btn-primary' : 'btn-light' }}">{{ __('community.all') }}</a>
        @foreach ($categories as $key => $label)
            <a href="{{ route('community.index', ['category' => $key]) }}"
               class="btn btn-sm {{ $category === $key ? 'btn-primary' : 'btn-light' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="help-card p-0 overflow-hidden">
        @forelse ($threads as $thread)
            <div class="p-3 border-bottom {{ $loop->last ? 'border-0' : '' }}">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <div class="flex-grow-1">
                        @if ($thread->is_pinned)
                            <span class="badge bg-warning text-dark me-1">{{ __('community.pinned') }}</span>
                        @endif
                        <span class="badge bg-light text-dark border">{{ $categories[$thread->category] ?? $thread->category }}</span>
                        <h6 class="mt-2 mb-1">
                            <a href="{{ route('community.show', $thread) }}" class="text-dark text-decoration-none">
                                {{ $thread->title }}
                            </a>
                        </h6>
                        <div class="text-muted small">
                            {{ __('community.by') }} {{ $thread->user->name ?? 'User' }}
                            · {{ $thread->created_at->diffForHumans() }}
                            · {{ $thread->replies_count }} {{ __('community.replies') }}
                            · {{ $thread->views }} {{ __('community.views') }}
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-4 text-center text-muted">{{ __('community.no_threads') }}</div>
        @endforelse
    </div>

    <div class="mt-3">{{ $threads->withQueryString()->links() }}</div>

    <div class="mt-4 text-center">
        <p class="text-muted small">{{ __('community.help_hint') }}</p>
        <a href="{{ route('help.index') }}" class="btn btn-sm btn-light">{{ __('help.nav_help') }}</a>
    </div>
</div>
@endsection
