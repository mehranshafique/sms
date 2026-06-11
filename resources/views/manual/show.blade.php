@extends('layouts.help')

@section('title', $page['title'])

@section('content')
<div class="container">
    <div class="row">
        <aside class="col-lg-3 mb-4 d-none d-lg-block">
            @include('manual._sidebar', ['manualType' => $manualType, 'toc' => $toc, 'currentSlug' => $page['slug']])
        </aside>
        <article class="col-lg-9">
            @if (!empty($contentFallback))
                <div class="locale-fallback-banner">
                    <i class="fa fa-language me-1"></i>{{ __('manual.locale_fallback') }}
                </div>
            @endif

            <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
                <a href="{{ $manualType === 'mobile' ? route('manual.mobile') : route('manual.web') }}" class="text-muted small">
                    <i class="fa fa-arrow-left me-1"></i>{{ __('manual.back_toc') }}
                </a>
                @if ($page['part_title'] ?? null)
                    <span class="badge bg-light text-dark border">{{ $page['part_title'] }}</span>
                @endif
                @if ($page['id'] ?? null)
                    <span class="badge bg-primary">{{ $manualType === 'mobile' ? __('manual.part_badge') : __('manual.module_badge') }} {{ $page['id'] }}</span>
                @endif
            </div>

            <div class="help-article">
                <h1>{{ $page['title'] }}</h1>
                {!! $page['html'] !!}
            </div>

            <div class="d-flex flex-wrap justify-content-between gap-2 mt-4 pt-3 border-top">
                @if ($page['prev'])
                    <a href="{{ $manualType === 'mobile' ? route('manual.mobile.show', $page['prev']['slug']) : route('manual.web.show', $page['prev']['slug']) }}"
                       class="btn btn-light btn-sm">
                        <i class="fa fa-arrow-left me-1"></i>{{ Str::limit($page['prev']['title'], 35) }}
                    </a>
                @else
                    <span></span>
                @endif
                @if ($page['next'])
                    <a href="{{ $manualType === 'mobile' ? route('manual.mobile.show', $page['next']['slug']) : route('manual.web.show', $page['next']['slug']) }}"
                       class="btn btn-primary btn-sm">
                        {{ Str::limit($page['next']['title'], 35) }} <i class="fa fa-arrow-right ms-1"></i>
                    </a>
                @endif
            </div>

            <div class="mt-4 text-center">
                <p class="text-muted small">{{ __('manual.still_need_help') }}</p>
                <a href="{{ route('community.index') }}" class="btn btn-sm btn-outline-primary">{{ __('manual.ask_forum') }}</a>
            </div>
        </article>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var body = document.getElementById('manual-sidebar-body');
    if (!body) return;
    var active = body.querySelector('.active, .active-mobile');
    if (active) {
        active.scrollIntoView({ block: 'center', behavior: 'auto' });
    }
});
</script>
@endsection
