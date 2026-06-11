<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>@yield('title', __('help.page_title')) — Digitex SMS</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon.png') }}">
    <link href="{{ asset('vendor/bootstrap-select/dist/css/bootstrap-select.min.css') }}" rel="stylesheet">
    <link class="main-css" rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        html, body { height: 100%; }
        body { display: flex; flex-direction: column; min-height: 100vh; margin: 0; }
        .help-topbar { background: #fff; border-bottom: 1px solid #eee; padding: .75rem 0; flex-shrink: 0; }
        .help-body { flex: 1 0 auto; background: #f8f9fa; padding: 2rem 0 4rem; }
        .help-sidebar .list-group-item { border: none; padding: .5rem 1rem; }
        .help-sidebar .list-group-item.active { background: var(--primary); border-color: var(--primary); }
        .help-article { background: #fff; border-radius: .5rem; padding: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        .help-article h1 { font-size: 1.75rem; margin-bottom: 1rem; }
        .help-article h2 { font-size: 1.35rem; margin-top: 1.75rem; margin-bottom: .75rem; }
        .help-article h3 { font-size: 1.1rem; margin-top: 1.25rem; }
        .help-article table { width: 100%; margin: 1rem 0; }
        .help-article table th, .help-article table td { border: 1px solid #dee2e6; padding: .5rem .75rem; }
        .help-article pre { background: #f1f3f5; padding: 1rem; border-radius: .375rem; overflow-x: auto; }
        .help-article code { background: #f1f3f5; padding: .15rem .35rem; border-radius: .25rem; font-size: .9em; }
        .help-article pre code { background: transparent; padding: 0; }
        .help-article hr { margin: 1.5rem 0; }
        .help-article ul, .help-article ol { padding-left: 1.25rem; }
        .help-article li { margin-bottom: .35rem; }
        .help-card { background: #fff; border-radius: .5rem; padding: 1.25rem; border: 1px solid #eee; transition: box-shadow .2s; }
        .help-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.08); }
        .help-search { max-width: 480px; }
        .help-search-snippet { font-size: .875rem; color: #6c757d; margin-bottom: .25rem; padding-left: .75rem; border-left: 3px solid #dee2e6; }
        footer.help-footer { flex-shrink: 0; }
        .manual-sidebar-wrap { position: sticky; top: 1rem; max-height: calc(100vh - 2rem); }
        .manual-sidebar-panel {
            display: flex; flex-direction: column; max-height: calc(100vh - 2rem);
            background: #fff; border-radius: .5rem; border: 1px solid #eee;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
        }
        .manual-sidebar-header { flex-shrink: 0; padding: .75rem 1rem; border-bottom: 1px solid #eee; }
        .manual-sidebar-body {
            overflow-y: auto; overflow-x: hidden; flex: 1 1 auto;
            -webkit-overflow-scrolling: touch; overscroll-behavior: contain;
        }
        .manual-sidebar-body a { word-break: break-word; }
        .manual-sidebar-body a.active { background: var(--primary); color: #fff !important; }
        .manual-sidebar-body a.active-mobile { background: #198754; color: #fff !important; }
        .locale-fallback-banner { background: #fff3cd; border: 1px solid #ffc107; border-radius: .375rem; padding: .75rem 1rem; margin-bottom: 1rem; font-size: .9rem; }
    </style>
    @yield('styles')
</head>
<body>
    <header class="help-topbar">
        <div class="container">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <a href="{{ route('help.index') }}" class="d-flex align-items-center text-decoration-none">
                    <img src="https://e-digitex.com/public/images/smsslogonew.png" alt="Digitex" style="height: 40px;">
                    <span class="ms-2 fw-semibold text-dark d-none d-sm-inline">{{ __('help.brand') }}</span>
                </a>
                <nav class="d-flex flex-wrap align-items-center gap-2 gap-md-3">
                    <a href="{{ route('help.index') }}" class="text-dark {{ request()->routeIs('help.index', 'manual.hub') ? 'fw-bold' : '' }}">
                        <i class="fa fa-home me-1"></i>{{ __('manual.nav_home') }}
                    </a>
                    <a href="{{ route('manual.web') }}" class="text-dark {{ request()->routeIs('manual.web*') ? 'fw-bold' : '' }}">
                        <i class="fa fa-desktop me-1"></i>{{ __('manual.nav_web') }}
                    </a>
                    <a href="{{ route('manual.mobile') }}" class="text-dark {{ request()->routeIs('manual.mobile*') ? 'fw-bold' : '' }}">
                        <i class="fa fa-mobile-alt me-1"></i>{{ __('manual.nav_mobile') }}
                    </a>
                    <a href="{{ route('community.index') }}" class="text-dark {{ request()->routeIs('community.*') ? 'fw-bold' : '' }}">
                        <i class="fa fa-comments me-1"></i>{{ __('help.nav_community') }}
                    </a>
                    <a href="{{ route('pay.lookup') }}" class="text-dark">
                        <i class="fa fa-credit-card me-1"></i>{{ __('help.nav_pay') }}
                    </a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn btn-sm btn-primary">{{ __('help.nav_dashboard') }}</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-sm btn-outline-primary">{{ __('help.nav_login') }}</a>
                    @endauth
                    <a href="{{ route('help.change_language', ['language' => 'en']) }}"
                       class="btn btn-sm {{ app()->getLocale() === 'en' ? 'btn-primary' : 'btn-outline-secondary' }}">EN</a>
                    <a href="{{ route('help.change_language', ['language' => 'fr']) }}"
                       class="btn btn-sm {{ app()->getLocale() === 'fr' ? 'btn-primary' : 'btn-outline-secondary' }}">FR</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="help-body">
        @if (session('success'))
            <div class="container mb-3">
                <div class="alert alert-success alert-dismissible fade show mb-0">{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        @endif
        @if (session('error'))
            <div class="container mb-3">
                <div class="alert alert-danger alert-dismissible fade show mb-0">{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        @endif
        @yield('content')
    </main>

    <footer class="help-footer py-3 border-top bg-white">
        <div class="container text-center text-muted small">
            &copy; {{ date('Y') }} Digitex SMS — {{ __('help.footer') }}
        </div>
    </footer>

    <script src="{{ asset('vendor/global/global.min.js') }}"></script>
    @yield('scripts')
</body>
</html>
