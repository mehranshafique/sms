@extends('layouts.help')

@section('title', __('help.page_title'))

@section('content')
<div class="container">
    <div class="text-center mb-4">
        <h1 class="mb-2">{{ __('help.page_title') }}</h1>
        <p class="text-muted mb-4">{{ __('help.page_subtitle') }}</p>
        <form action="{{ route('help.index') }}" method="GET" class="help-search mx-auto">
            <div class="input-group">
                <input type="search" name="q" class="form-control" placeholder="{{ __('help.search_placeholder') }}"
                    value="{{ $query }}" autofocus>
                <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i></button>
            </div>
        </form>
    </div>

    @if ($query)
        <h5 class="mb-3">{{ __('help.search_results', ['q' => $query]) }}</h5>
        @if (count($results) === 0)
            <div class="alert alert-light border">{{ __('help.no_results') }}</div>
        @else
            <div class="row g-3 mb-4">
                @foreach ($results as $article)
                    <div class="col-md-6">
                        <a href="{{ route('help.show', $article['slug']) }}" class="text-decoration-none text-dark d-block help-card">
                            <h6 class="mb-1">{{ $article['title'] }}</h6>
                            <p class="text-muted small mb-0">{{ $article['summary'] }}</p>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
        <hr>
    @endif

    <div class="row g-4">
        @foreach ($categories as $cat)
            <div class="col-lg-6">
                <div class="help-card">
                    <h5 class="mb-3">
                        <i class="fa {{ $cat['icon'] }} text-primary me-2"></i>{{ $cat['title'] }}
                    </h5>
                    @if (count($cat['articles']) === 0)
                        <p class="text-muted small mb-0">{{ __('help.no_articles') }}</p>
                    @else
                        <ul class="list-unstyled mb-0">
                            @foreach ($cat['articles'] as $article)
                                <li class="mb-2">
                                    <a href="{{ route('help.show', $article['slug']) }}">{{ $article['title'] }}</a>
                                    <div class="text-muted small">{{ $article['summary'] }}</div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="text-center mt-5">
        <p class="text-muted">{{ __('help.community_cta') }}</p>
        <a href="{{ route('community.index') }}" class="btn btn-outline-primary">
            <i class="fa fa-comments me-1"></i>{{ __('help.nav_community') }}
        </a>
    </div>
</div>
@endsection
