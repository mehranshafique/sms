@extends('layouts.help')

@section('title', $article['title'])

@section('content')
<div class="container">
    <div class="row">
        <aside class="col-lg-3 mb-4 help-sidebar d-none d-lg-block">
            <div class="help-card p-0 overflow-hidden">
                <div class="p-3 border-bottom fw-semibold">{{ __('help.in_this_section') }}</div>
                @foreach ($categories as $cat)
                    @if (count($cat['articles']) > 0)
                        <div class="px-3 pt-2 pb-1 small text-muted text-uppercase">{{ $cat['title'] }}</div>
                        <div class="list-group list-group-flush">
                            @foreach ($cat['articles'] as $item)
                                <a href="{{ route('help.show', $item['slug']) }}"
                                   class="list-group-item list-group-item-action {{ $item['slug'] === $slug ? 'active' : '' }}">
                                    {{ $item['title'] }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </div>
            <div class="mt-3">
                <a href="{{ route('help.index') }}" class="btn btn-sm btn-light w-100">
                    <i class="fa fa-arrow-left me-1"></i>{{ __('help.back_to_help') }}
                </a>
            </div>
        </aside>
        <article class="col-lg-9">
            <div class="help-article">
                {!! $article['html'] !!}
            </div>
            <div class="mt-4 d-flex flex-wrap gap-2 justify-content-between">
                <a href="{{ route('help.index') }}" class="btn btn-light btn-sm">
                    <i class="fa fa-arrow-left me-1"></i>{{ __('help.back_to_help') }}
                </a>
                <div class="d-flex gap-2">
                    <a href="{{ route('manual.web') }}" class="btn btn-outline-secondary btn-sm">{{ __('manual.nav_web') }}</a>
                    <a href="{{ route('community.index') }}?category=payments" class="btn btn-outline-primary btn-sm">
                        {{ __('help.ask_community') }}
                    </a>
                </div>
            </div>
        </article>
    </div>
</div>
@endsection
