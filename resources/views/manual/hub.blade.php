@extends('layouts.help')

@section('title', __('manual.hub_title'))

@section('content')
<div class="container">
    <div class="text-center mb-5">
        <h1 class="mb-2">{{ __('manual.hub_title') }}</h1>
        <p class="text-muted col-lg-8 mx-auto">{{ __('manual.hub_subtitle') }}</p>
        <form action="{{ route('help.index') }}" method="GET" class="help-search mx-auto mt-4">
            <div class="input-group input-group-lg">
                <input type="search" name="q" class="form-control" placeholder="{{ __('manual.search_all') }}"
                    value="{{ $query }}">
                <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i></button>
            </div>
        </form>
    </div>

    @if ($query)
        <h5 class="mb-3">{{ __('help.search_results', ['q' => $query]) }}</h5>
        @if (count($manualResults) === 0 && count($helpResults) === 0)
            <div class="alert alert-light border mb-4">{{ __('help.no_results') }}</div>
        @else
            @if (count($manualResults) > 0)
                <h6 class="text-muted text-uppercase small">{{ __('manual.from_manual') }}</h6>
                <div class="row g-3 mb-4">
                    @foreach ($manualResults as $item)
                        <div class="col-md-6">
                            <a href="{{ $item['url'] }}" class="text-decoration-none text-dark d-block help-card">
                                <span class="badge bg-light text-dark border mb-1">{{ $item['type'] === 'mobile' ? 'Mobile' : 'Web' }}</span>
                                <h6 class="mb-1">{{ $item['title'] }}</h6>
                                @if (!empty($item['snippets']))
                                    @foreach ($item['snippets'] as $snippet)
                                        <p class="help-search-snippet mb-1">{{ $snippet }}</p>
                                    @endforeach
                                @else
                                    <p class="text-muted small mb-0">{{ $item['summary'] }}</p>
                                @endif
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
            @if (count($helpResults) > 0)
                <h6 class="text-muted text-uppercase small">{{ __('manual.from_guides') }}</h6>
                <div class="row g-3 mb-4">
                    @foreach ($helpResults as $article)
                        <div class="col-md-6">
                            <a href="{{ route('help.show', $article['slug']) }}" class="text-decoration-none text-dark d-block help-card">
                                <h6 class="mb-1">{{ $article['title'] }}</h6>
                                @if (!empty($article['snippets']))
                                    @foreach ($article['snippets'] as $snippet)
                                        <p class="help-search-snippet mb-1">{{ $snippet }}</p>
                                    @endforeach
                                @else
                                    <p class="text-muted small mb-0">{{ $article['summary'] }}</p>
                                @endif
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
            <hr class="mb-5">
        @endif
    @endif

    <div class="row g-4 mb-5 align-items-start">
        <div class="col-lg-6">
            <div class="help-card h-100 border-primary border-2">
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                        <i class="fa fa-desktop fa-2x text-primary"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h4>{{ __('manual.web_manual_title') }}</h4>
                        <p class="text-muted">{{ __('manual.web_manual_desc', ['count' => $webModuleCount]) }}</p>
                        <ul class="small text-muted mb-3">
                            @foreach (array_slice($webParts, 0, 5) as $part)
                                <li>Part {{ $part['id'] }} — {{ $part['title'] }}</li>
                            @endforeach
                            @if (count($webParts) > 5)
                                <li>… {{ count($webParts) - 5 }} {{ __('manual.more_parts') }}</li>
                            @endif
                        </ul>
                        <a href="{{ route('manual.web') }}" class="btn btn-primary">
                            {{ __('manual.open_web_manual') }} <i class="fa fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="help-card h-100">
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded-circle bg-success bg-opacity-10 p-3">
                        <i class="fa fa-mobile-alt fa-2x text-success"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h4>{{ __('manual.mobile_manual_title') }}</h4>
                        <p class="text-muted">{{ __('manual.mobile_manual_desc', ['count' => $mobilePartCount]) }}</p>
                        <ul class="small text-muted mb-3">
                            @foreach (array_slice($mobileParts, 0, 4) as $part)
                                <li>Part {{ $part['number'] }} — {{ Str::limit($part['title'], 40) }}</li>
                            @endforeach
                            @if (count($mobileParts) > 4)
                                <li>… {{ count($mobileParts) - 4 }} {{ __('manual.more_parts') }}</li>
                            @endif
                        </ul>
                        <a href="{{ route('manual.mobile') }}" class="btn btn-outline-success">
                            {{ __('manual.open_mobile_manual') }} <i class="fa fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5 align-items-start">
        <div class="col-lg-8">
            <h5 class="mb-3"><i class="fa fa-bolt text-warning me-2"></i>{{ __('manual.quick_guides') }}</h5>
            <div class="row g-3">
                @foreach ($categories as $cat)
                    @foreach ($cat['articles'] as $article)
                        <div class="col-md-6">
                            <a href="{{ route('help.show', $article['slug']) }}" class="help-card d-block text-decoration-none text-dark">
                                <span class="badge bg-light text-muted border mb-1">{{ $cat['title'] }}</span>
                                <h6 class="mb-1">{{ $article['title'] }}</h6>
                                <p class="text-muted small mb-0">{{ $article['summary'] }}</p>
                            </a>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
        <div class="col-lg-4 align-self-start">
            <div class="help-card bg-primary bg-opacity-10 border-0">
                <h5><i class="fa fa-comments me-2"></i>{{ __('help.nav_community') }}</h5>
                <p class="text-muted small">{{ __('manual.community_desc') }}</p>
                <a href="{{ route('community.index') }}" class="btn btn-primary btn-sm">{{ __('manual.visit_forum') }}</a>
            </div>
            <div class="help-card mt-3">
                <h6>{{ __('manual.no_login_required') }}</h6>
                <p class="text-muted small mb-2">{{ __('manual.public_note') }}</p>
                <a href="{{ route('login') }}" class="btn btn-sm btn-outline-secondary">{{ __('help.nav_login') }}</a>
                <a href="{{ route('pay.lookup') }}" class="btn btn-sm btn-outline-secondary ms-1">{{ __('help.nav_pay') }}</a>
            </div>
        </div>
    </div>
</div>
@endsection
