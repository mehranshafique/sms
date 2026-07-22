@extends('layouts.help')

@section('title', $thread->title)

@section('content')
<div class="container">
    <div class="mb-3">
        <a href="{{ route('community.index') }}" class="text-muted small">
            <i class="fa fa-arrow-left me-1"></i>{{ __('community.back_to_forum') }}
        </a>
    </div>

    <div class="help-card mb-4">
        <div class="d-flex flex-wrap gap-2 mb-2">
            @if ($thread->is_pinned)
                <span class="badge bg-warning text-dark">{{ __('community.pinned') }}</span>
            @endif
            @if ($thread->is_locked)
                <span class="badge bg-secondary">{{ __('community.locked') }}</span>
            @endif
            <span class="badge bg-light text-dark border">{{ $categories[$thread->category] ?? $thread->category }}</span>
        </div>
        <h1 class="h4 mb-2">{{ $thread->title }}</h1>
        <div class="text-muted small mb-3">
            {{ __('community.by') }} <strong>{{ $thread->user->name ?? 'User' }}</strong>
            · {{ $thread->created_at->format('M j, Y H:i') }}
            · {{ $thread->views }} {{ __('community.views') }}
        </div>
        <div class="community-body">{!! nl2br(e($thread->body)) !!}</div>
    </div>

    <h5 class="mb-3">{{ __('community.replies') }} ({{ $thread->replies->count() }})</h5>

    @foreach ($thread->replies as $reply)
        <div class="help-card mb-3 py-3">
            <div class="d-flex justify-content-between mb-2">
                <strong>{{ $reply->user->name ?? 'User' }}</strong>
                <span class="text-muted small">{{ $reply->created_at->diffForHumans() }}</span>
            </div>
            <div>{!! nl2br(e($reply->body)) !!}</div>
            @if ($reply->is_solution)
                <span class="badge bg-success mt-2">{{ __('community.solution') }}</span>
            @endif
        </div>
    @endforeach

    @auth
        @if (!$thread->is_locked)
            <div class="help-card mt-4">
                <h6 class="mb-3">{{ __('community.post_reply') }}</h6>
                <form method="POST" action="{{ route('community.reply', $thread) }}" class="ajax-form">
                    @csrf
                    <div class="mb-3">
                        <textarea name="body" class="form-control @error('body') is-invalid @enderror" rows="4"
                            required placeholder="{{ __('community.reply_placeholder') }}">{{ old('body') }}</textarea>
                        @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary">{{ __('community.submit_reply') }}</button>
                </form>
            </div>
        @else
            <div class="alert alert-secondary">{{ __('community.thread_locked') }}</div>
        @endif
    @else
        <div class="alert alert-info">
            {{ __('community.login_to_reply') }}
            <a href="{{ route('login') }}">{{ __('help.nav_login') }}</a>
        </div>
    @endauth
</div>
@endsection
