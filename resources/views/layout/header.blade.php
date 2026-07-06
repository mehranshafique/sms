<!DOCTYPE html>
<html lang="en">
<head>
	<!-- Title -->
	<title>{{ $pageTitle ?? config('app.name') }}</title>

	<!-- Meta -->
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="author" content="dexignlabs">
	<meta name="robots" content="index, follow">
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon.png') }}">
    <!-- PWA Meta Tags -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#002b80">
    <link rel="apple-touch-icon" href="{{ asset('images/favicon.png') }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">

	<!-- STYLESHEETS -->
	<link rel="stylesheet" href="{{ asset('vendor/jqvmap/css/jqvmap.min.css') }}">
	<link rel="stylesheet" href="{{ asset('vendor/global/global.min.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
	<link rel="stylesheet" href="{{ asset('vendor/chartist/css/chartist.min.css') }}">

    <link href="{{ asset('vendor/datatables/css/jquery.dataTables.min.css')  }}" rel="stylesheet">
    <link href="{{ asset('vendor/datatables/css/responsive.bootstrap.min.css')  }}" rel="stylesheet">

	<link rel="stylesheet" href="{{ asset('vendor/bootstrap-select/dist/css/bootstrap-select.min.css') }}">
    <link class="" rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/digitex-preloader.css') }}">
    
    <!-- Date & Time Picker CSS -->
    <link href="{{asset('vendor/bootstrap-daterangepicker/daterangepicker.css')}}" rel="stylesheet">
    <link href="{{asset('vendor/clockpicker/css/bootstrap-clockpicker.min.css')}}" rel="stylesheet">
    <link href="{{asset('vendor/jquery-ascolorpicker/css/ascolorpicker.min.css')}}" rel="stylesheet">
    <link href="{{asset('vendor/bootstrap-material-datetimepicker/css/bootstrap-material-datetimepicker.css')}}" rel="stylesheet">
    
    <link rel="stylesheet" href="{{asset('vendor/pickadate/themes/default.css')}}">
    <link rel="stylesheet" href="{{asset('vendor/pickadate/themes/default.date.css')}}">
    
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    @yield('styles')
    
    <style>
        /* Header specific fixes */
        .content-body {
            padding-bottom: 5rem;
        }
        /* Pages wrap their own .content-body — keep alerts + page in one column */
        .content-body > .content-body {
            margin-left: 0 !important;
            width: 100% !important;
            min-height: auto !important;
            padding-top: 0 !important;
            padding-bottom: 0;
        }
        .setup-alerts-inner .container-fluid {
            padding-top: 0.35rem;
            padding-bottom: 0;
        }
        .setup-alerts-inner + .content-body > .container-fluid,
        .setup-alerts-inner + .content-body > .container {
            padding-top: 0.35rem;
        }
        .setup-alerts-inner .alert {
            margin-bottom: 0.5rem;
        }
        .dlabnav .nav-text {
            white-space: normal;
            word-break: break-word;
        }
        .select2-selection{
            border: 1px solid #d9dee3 !important;
            border-radius: 4px !important;
            height: 38px !important;
        }
        .dtp { z-index: 9999 !important; }

        /* Global form-switch: keep knob circular when track is enlarged (ON/OFF) */
        .form-switch .form-check-input {
            cursor: pointer;
            background-repeat: no-repeat !important;
            background-size: 1em 1em !important;
            background-position: left center !important;
        }
        .form-switch .form-check-input:checked {
            background-position: right center !important;
            background-size: 1em 1em !important;
        }
        .form-switch .form-check-input:focus,
        .form-switch .form-check-input:active {
            background-size: 1em 1em !important;
        }

        /* Hide bootstrap-select bleed-through inside SweetAlert modals */
        body.swal2-shown .bootstrap-select.open,
        body.swal2-shown .bootstrap-select.show,
        body.swal2-shown .bootstrap-select .dropdown-menu.show {
            display: none !important;
            visibility: hidden !important;
        }
        body.swal2-shown .bs-container.bootstrap-select {
            z-index: 1040 !important;
        }
        .swal2-container .bootstrap-select,
        .swal2-container .dropdown.bootstrap-select {
            display: none !important;
        }

        .select2-selection__arrow{ margin: 4px !important; }

        .word-icon {
            margin-top: 0px;
            padding: 6px;
            font-size: 22px;
        }

        /* Button Processing State */
        .btn { position: relative; transition: all 0.2s ease-in-out; }
        .btn.disabled, .btn:disabled { opacity: 0.65; cursor: not-allowed; }
        .btn:active { transform: scale(0.98); }
        
        /* Profile Image Fallback */
        .profile-initials {
            width: 35px; 
            height: 35px; 
            border-radius: 50%; 
            background: var(--primary); 
            color: #fff; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-weight: bold;
            font-size: 14px;
        }

        /* Pro plan — Google-style gradient ring on avatar */
        .profile-avatar-wrap {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            padding: 2px;
            background: transparent;
        }
        .profile-avatar-wrap.is-pro {
            padding: 3px;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 25%, #7c3aed 55%, #2563eb 100%);
            box-shadow: 0 0 0 1px rgba(124, 58, 237, 0.15), 0 4px 14px rgba(124, 58, 237, 0.25);
        }
        .profile-avatar-wrap.is-pro::after {
            content: '';
            position: absolute;
            bottom: -1px;
            right: -1px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            border: 2px solid #fff;
            box-shadow: 0 1px 4px rgba(0,0,0,.15);
            z-index: 2;
        }
        .profile-avatar-inner {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            overflow: hidden;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .profile-avatar-inner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }
        .profile-avatar-inner .profile-initials {
            width: 100%;
            height: 100%;
            font-size: 14px;
        }

        /* Header plan pill (visible without opening dropdown) */
        .header-plan-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
            transition: transform .15s, box-shadow .15s;
        }
        .header-plan-pill:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,.1);
        }
        .header-plan-pill.is-pro {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 40%, #ddd6fe 100%);
            color: #5b21b6;
            border: 1px solid rgba(124, 58, 237, 0.2);
        }
        .header-plan-pill.is-pro i.la-crown {
            color: #d97706;
        }
        .header-plan-pill.is-standard {
            background: #eef2ff;
            color: #3730a3;
            border: 1px solid #c7d2fe;
        }
        .header-plan-pill.is-expired {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        .header-pro-badge {
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
            padding: 2px 6px;
            border-radius: 4px;
            background: linear-gradient(135deg, #7c3aed, #2563eb);
            color: #fff;
            margin-left: 2px;
        }

        /* Mobile header menu panel (opens under header toggle area) */
        .header-mobile-panel {
            min-width: 280px;
            max-width: min(320px, 92vw);
            padding: 0.75rem;
            border-radius: 12px;
            box-shadow: 0 12px 32px rgba(0,0,0,.12);
            margin-top: 0.35rem;
        }
        .header-mobile-panel__plan {
            display: block;
            padding: 0.65rem 0.75rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
        }
        .header-mobile-panel__plan.is-pro {
            background: linear-gradient(135deg, #fef3c7, #ddd6fe);
            color: #5b21b6;
        }
        .header-mobile-panel__item {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.55rem 0.75rem;
            border-radius: 8px;
            color: inherit;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .header-mobile-panel__item:hover { background: #f3f4f6; }

        @media (max-width: 991.98px) {
            .header-item-desktop-only { display: none !important; }
            .dashboard_bar {
                font-size: 1rem;
                max-width: 42vw;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            /* Sidebar slides in directly under the fixed header bar */
            [data-sidebar-style="overlay"] .dlabnav {
                top: var(--dz-header-height) !important;
                height: calc(100dvh - var(--dz-header-height)) !important;
                width: min(290px, 88vw) !important;
                z-index: 10001 !important;
            }
            #main-wrapper.menu-toggle::after {
                content: '';
                position: fixed;
                left: 0;
                right: 0;
                bottom: 0;
                top: var(--dz-header-height);
                background: rgba(15, 23, 42, 0.45);
                z-index: 10000;
            }
            .header .header-content {
                padding-left: 5rem;
            }
        }
        @media (min-width: 992px) {
            .header-mobile-menu-btn { display: none !important; }
        }

        /* School Switcher Search */
        .school-search-container {
            padding: 10px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
        }
        .school-search-input {
            border-radius: 20px;
            border: 1px solid #ddd;
            padding: 5px 15px;
            width: 100%;
        }

        /* Notifications Tweaks */
        .notification-link { text-decoration: none; color: inherit; display: block; }
        .notification-link:hover .timeline-panel { background-color: #f8f9fa; }

        /* Global Search */
        .global-search-wrap {
            position: relative;
            width: 40px;
            height: 40px;
            flex-shrink: 0;
            transition: width 0.28s ease;
        }
        .global-search-wrap.is-expanded {
            width: min(380px, 42vw);
        }
        .global-search-toggle {
            position: absolute;
            inset: 0;
            border: 1px solid #e6e6e6;
            border-radius: 50%;
            background: #fff;
            color: #888;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 2;
            transition: opacity 0.2s ease;
        }
        .global-search-wrap.is-expanded .global-search-toggle {
            opacity: 0;
            pointer-events: none;
        }
        .global-search-field {
            display: none;
            align-items: center;
            gap: 10px;
            width: 100%;
            height: 40px;
            border: 1px solid #e6e6e6;
            border-radius: 20px;
            background: #fff;
            padding: 0 14px 0 12px;
            overflow: hidden;
        }
        .global-search-wrap.is-expanded .global-search-field {
            display: flex;
        }
        .global-search-field:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(0, 43, 128, 0.08);
            background: #fff;
        }
        .global-search-icon-wrap {
            flex-shrink: 0;
            width: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            line-height: 1;
        }
        /* Override theme .header-left input rules (grey bg, min-height) */
        .header-left .global-search-wrap .global-search-input {
            flex: 1;
            min-width: 0 !important;
            min-height: 0 !important;
            height: auto !important;
            border: none !important;
            outline: none !important;
            padding: 0 !important;
            margin: 0 !important;
            font-size: 13px;
            background: transparent !important;
            box-shadow: none !important;
            line-height: 1.4;
            color: #333;
        }
        .header-left .global-search-wrap .global-search-input:focus,
        .header-left .global-search-wrap .global-search-input:active {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
        }
        .header-left .global-search-wrap .global-search-input::placeholder {
            color: #aaa !important;
        }
        .header-left .global-search-wrap .global-search-input:-webkit-autofill,
        .header-left .global-search-wrap .global-search-input:-webkit-autofill:hover,
        .header-left .global-search-wrap .global-search-input:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0 1000px #fff inset !important;
            box-shadow: 0 0 0 1000px #fff inset !important;
            -webkit-text-fill-color: #333 !important;
        }
        .global-search-dropdown {
            display: none;
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            max-height: 360px;
            overflow-y: auto;
            z-index: 1050;
        }
        .global-search-dropdown.show { display: block; }
        .global-search-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            text-decoration: none;
            color: inherit;
            border-bottom: 1px solid #f3f3f3;
            cursor: pointer;
        }
        .global-search-item:last-child { border-bottom: 0; }
        .global-search-item:hover,
        .global-search-item.active { background: #f8f9fa; }
        .global-search-item-icon {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #eef2ff;
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .global-search-item-body { min-width: 0; flex: 1; }
        .global-search-item-label {
            font-weight: 600;
            font-size: 13px;
            color: #222;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .global-search-item-sub {
            font-size: 11px;
            color: #888;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .global-search-item-type {
            font-size: 10px;
            text-transform: uppercase;
            color: #aaa;
            letter-spacing: 0.5px;
        }
        .global-search-empty {
            padding: 16px;
            text-align: center;
            color: #999;
            font-size: 13px;
        }
        @media (max-width: 991px) {
            .global-search-wrap.is-expanded { width: min(280px, 55vw); }
        }
    </style>
    @include('layout.partials.theme-dark')
    @include('layout.partials.nav-layout-styles')
</head>
<body>

    <!--*******************
        Preloader start
    ********************-->
    <div id="preloader">
        <div class="digitex-preloader">
            <div class="digitex-preloader__ring"></div>
            <img src="{{ asset('images/digitex-logo.png') }}" alt="Digitex" class="digitex-preloader__logo">
            <p class="digitex-preloader__text">{{ __('configuration.loading') ?? 'Loading' }}</p>
        </div>
    </div>
    <div id="digitex-ajax-loader" aria-hidden="true">
        <div class="digitex-ajax-loader__card">
            <div class="digitex-preloader">
                <div class="digitex-preloader__ring"></div>
                <img src="{{ asset('images/digitex-logo.png') }}" alt="Digitex" class="digitex-preloader__logo">
            </div>
            <p class="digitex-ajax-loader__label">{{ __('configuration.processing') ?? 'Processing...' }}</p>
        </div>
    </div>
    <!--*******************
        Preloader end
    ********************-->

    <!--**********************************
        Main wrapper start
    ***********************************-->
    <div id="main-wrapper">

        <!--**********************************
            Nav header start
        ***********************************-->
        <div class="nav-header" style="background-color: transparent">
            <a href="{{ route('dashboard')  }}" class="brand-logo">
                @php
                    $activeInstId = session('active_institution_id');
                    $user = Auth::user();
                    $institutionLogo = null;
                    $activeInst = null;

                    if ($user) {
                         $activeId = session('active_institution_id', $user->institute_id);

                         if ($activeId && $activeId !== 'global') {
                             $activeInst = \App\Models\Institution::find($activeId); 
                         }
                    }

                    if ($activeInst && $activeInst->logo) {
                        $institutionLogo = asset('storage/' . $activeInst->logo);
                    } else {
                        $institutionLogo = asset('images/digitex-logo.png');
                    }
                @endphp
                <img src="{{ $institutionLogo }}" style="max-width: 117px; max-height: 50px; object-fit: contain;" alt="Logo">
            </a>

            <div class="nav-control">
                <div class="hamburger">
                    <span class="line"></span><span class="line"></span><span class="line"></span>
                </div>
            </div>
        </div>
        <!--**********************************
            Nav header end
        ***********************************-->

        <!--**********************************
            Header start
        ***********************************-->
        <div class="header">
            <div class="header-content">
                <nav class="navbar navbar-expand">
                    <div class="collapse navbar-collapse justify-content-between">
                        <div class="header-left flex-grow-1">
                            <div class="d-flex align-items-center gap-3 w-100">
                                <div class="dashboard_bar h4 mb-0 text-nowrap">
                                    {{ $pageTitle ?? ''  }}
                                </div>
                                <div class="global-search-wrap d-none d-md-block" id="globalSearchWrap">
                                    <button type="button" class="global-search-toggle" id="globalSearchToggle" aria-label="{{ __('header.global_search_placeholder') }}">
                                        <i class="fa fa-search"></i>
                                    </button>
                                    <div class="global-search-field">
                                        <span class="global-search-icon-wrap" aria-hidden="true">
                                            <i class="fa fa-search"></i>
                                        </span>
                                        <input type="text"
                                               id="globalSearchInput"
                                               class="global-search-input"
                                               placeholder="{{ __('header.global_search_placeholder') }}"
                                               autocomplete="off"
                                               aria-label="{{ __('header.global_search_placeholder') }}">
                                    </div>
                                    <div id="globalSearchDropdown" class="global-search-dropdown"></div>
                                </div>
                            </div>
                        </div>

                        <ul class="navbar-nav header-right">
                            
                            @php
                                $user = Auth::user();
                                $showSwitcher = false;
                                $activeInstitutionName = __('header.select_institute');
                                $allowedInstitutions = collect();
                                $isActiveGlobal = session('active_institution_id') === 'global';
                                $hasMultipleSchools = false;
                                $currentSessionTitle = null;

                                if ($user) {
                                    if ($user->hasRole(\App\Enums\RoleEnum::SUPER_ADMIN->value)) {
                                        $allowedInstitutions = \App\Models\Institution::select('id', 'name', 'code')->orderBy('name')->get();
                                        $showSwitcher = true;
                                        $hasMultipleSchools = true;
                                    } elseif ($user->institutes && $user->institutes->count() > 0) {
                                        $allowedInstitutions = $user->institutes;
                                        $showSwitcher = true;
                                        $hasMultipleSchools = $user->institutes->count() > 1;
                                    } else {
                                        $activeInstitutionName = $user->institute->name ?? __('header.my_institute');
                                    }
                                    
                                    $activeId = session('active_institution_id', $user->institute_id);
                                    
                                    if ($activeId && $activeId !== 'global') {
                                        $activeInstObj = $allowedInstitutions->where('id', $activeId)->first();
                                        if (!$activeInstObj && $user->institute && $user->institute->id == $activeId) $activeInstObj = $user->institute;
                                        if(!$activeInstObj) $activeInstObj = \App\Models\Institution::find($activeId);

                                        if ($activeInstObj) {
                                            $activeInstitutionName = $activeInstObj->name;
                                        }

                                        $sessionObj = \App\Models\AcademicSession::where('institution_id', $activeId)
                                            ->where('is_current', true)
                                            ->select('name')
                                            ->first();
                                        if ($sessionObj) {
                                            $currentSessionTitle = $sessionObj->name;
                                        }
                                    }
                                    
                                    if($isActiveGlobal) {
                                        $activeInstitutionName = __('header.global_view') ?? 'Global Dashboard';
                                    }
                                }
                            @endphp

                            @php
                                $planCtx = $planCtx ?? [];
                                $headerPlanName = $planCtx['plan_name'] ?? null;
                                $headerPlanActive = $planCtx['is_active'] ?? false;
                                $headerIsPro = $planCtx['is_pro'] ?? false;
                                $headerIsSchoolAdmin = Auth::user()->hasAnyRole([
                                    \App\Enums\RoleEnum::SCHOOL_ADMIN->value,
                                    \App\Enums\RoleEnum::HEAD_OFFICER->value,
                                ]);
                                $headerIsSuperAdmin = Auth::user()->hasRole(\App\Enums\RoleEnum::SUPER_ADMIN->value);
                            @endphp

                            {{-- Mobile quick menu (plan, language, profile links) --}}
                            <li class="nav-item dropdown header-mobile-menu-btn d-lg-none">
                                <a class="nav-link bell ai-icon" href="#" role="button" data-bs-toggle="dropdown" aria-label="{{ __('header.mobile_menu') }}">
                                    <i class="fa fa-ellipsis-v"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end header-mobile-panel">
                                    @if($headerPlanName && ($headerIsSchoolAdmin || $headerIsSuperAdmin))
                                        <a href="{{ route('plan.index') }}"
                                           class="header-mobile-panel__plan {{ ($headerIsPro && $headerPlanActive) ? 'is-pro' : '' }}">
                                            <i class="la la-crown"></i> {{ $headerPlanName }}
                                            @if($headerIsPro && $headerPlanActive)<span class="header-pro-badge">PRO</span>@endif
                                        </a>
                                    @endif
                                    @if($currentSessionTitle)
                                        <div class="header-mobile-panel__item text-muted">
                                            <i class="fa fa-calendar text-warning"></i> {{ $currentSessionTitle }}
                                        </div>
                                    @endif
                                    <a href="{{ route('profile.index') }}" class="header-mobile-panel__item">
                                        <i class="fa fa-user text-primary"></i> {{ __('header.my_profile') }}
                                    </a>
                                    @if($headerIsSchoolAdmin)
                                        <a href="{{ route('plan.index') }}" class="header-mobile-panel__item">
                                            <i class="fa fa-gem text-info"></i> {{ __('plan.my_plan') }}
                                        </a>
                                    @elseif($headerIsSuperAdmin)
                                        <a href="{{ route('plan.requests') }}" class="header-mobile-panel__item">
                                            <i class="fa fa-arrow-up text-info"></i> {{ __('plan.upgrade_requests') }}
                                        </a>
                                    @endif
                                    <a href="{{ url('/change-language?language=en') }}" class="header-mobile-panel__item">
                                        <i class="fas fa-globe text-secondary"></i> English
                                    </a>
                                    <a href="{{ url('/change-language?language=fr') }}" class="header-mobile-panel__item">
                                        <i class="fas fa-globe text-secondary"></i> Français
                                    </a>
                                </div>
                            </li>

                            {{-- Current Session Display --}}
                            @if($currentSessionTitle)
                            <li class="nav-item d-flex align-items-center me-3 d-none d-sm-flex header-item-desktop-only">
                                <span class="badge badge-warning light text-warning fs-12 font-w600 shadow-sm">
                                    <i class="fa fa-calendar me-1"></i> {{ $currentSessionTitle }}
                                </span>
                            </li>
                            @endif

                            {{-- In-App Notifications --}}
                            @include('layout.partials.in-app-notifications')

                            {{-- Institution Context Switcher (Updated with Text on Desktop) --}}
                            @if($showSwitcher)
                            <li class="nav-item dropdown notification_dropdown">
                                <a class="nav-link {{ $isActiveGlobal ? 'bg-dark text-white' : 'bg-primary text-white' }} rounded d-flex align-items-center justify-content-center" href="#" role="button" data-bs-toggle="dropdown" title="{{ $activeInstitutionName }}" style="min-width: 40px; height: 40px; padding: 0 15px;">
                                    <i class="fa fa-university fs-16"></i>
                                    {{-- Text is visible on Medium (Tablet/Desktop) screens and hidden on Mobile --}}
                                    <span class="ms-2 d-none d-md-block font-w600 fs-14" style="white-space: nowrap;">{{ $activeInstitutionName }}</span>
                                </a>
                                
                                <div class="dropdown-menu dropdown-menu-end p-0" style="min-width: 320px; overflow: hidden;">
                                    
                                    {{-- Search Bar --}}
                                    <div class="school-search-container">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text border-0 bg-transparent ps-1"><i class="fa fa-search text-muted"></i></span>
                                            <input type="text" id="schoolSearchInput" class="form-control border-0 bg-transparent" placeholder="{{ __('header.search_school') ?? 'Search School...' }}">
                                        </div>
                                    </div>

                                    {{-- Global View Option --}}
                                    @if($hasMultipleSchools)
                                        <a href="{{ route('institution.switch', 'global') }}" class="dropdown-item py-2 border-bottom {{ $isActiveGlobal ? 'bg-light text-primary fw-bold' : '' }}">
                                            <i class="fa fa-globe me-2 text-info"></i> {{ __('header.global_dashboard') ?? 'Global Dashboard' }}
                                        </a>
                                    @endif
                                    
                                    {{-- Scrollable List --}}
                                    <div id="schoolListContainer" class="widget-media dz-scroll" style="height:auto; max-height:350px; overflow-y:auto;">
                                        <ul class="timeline p-3" id="schoolTimeline">
                                            @foreach($allowedInstitutions as $inst)
                                                <li class="school-item">
                                                    <div class="timeline-panel p-2 rounded hover-bg-light position-relative">
                                                        <div class="media-body">
                                                            <h6 class="mb-0">
                                                                <a href="{{ route('institution.switch', $inst->id) }}" class="stretched-link text-decoration-none {{ session('active_institution_id') == $inst->id ? 'text-primary fw-bold' : 'text-dark' }} school-name">
                                                                    {{ $inst->name }}
                                                                </a>
                                                            </h6>
                                                            <small class="d-block text-muted school-code fs-11">{{ $inst->code }}</small>
                                                        </div>
                                                        @if(session('active_institution_id') == $inst->id)
                                                            <i class="fa fa-check-circle text-success fs-18 ms-2"></i>
                                                        @endif
                                                    </div>
                                                </li>
                                            @endforeach
                                            <li id="noSchoolFound" class="text-center text-muted py-3" style="display: none;">{{ __('header.no_school_found') ?? 'No school found' }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </li>
                            @endif

                            {{-- Language Selector --}}
							<li class="nav-item dropdown notification_dropdown header-item-desktop-only">
                                <a class="nav-link bell ai-icon text-muted" href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-globe"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a href="{{ url('/change-language?language=en') }}" class="dropdown-item {{ app()->getLocale() === 'en' ? 'active' : '' }}">
                                        <span class="ms-2">English</span>
                                    </a>
                                    <a href="{{ url('/change-language?language=fr') }}" class="dropdown-item {{ app()->getLocale() === 'fr' ? 'active' : '' }}">
                                        <span class="ms-2">Français</span>
                                    </a>
                                </div>
							</li>
                            
                            {{-- Navigation layout toggle (desktop only) --}}
                            <li class="nav-item header-item-desktop-only header-item-nav-layout">
                                <a class="nav-link dlab-nav-layout-toggle p-0"
                                   href="javascript:void(0);"
                                   title="{{ __('header.nav_layout_toggle') }}"
                                   aria-label="{{ __('header.nav_layout_toggle') }}"
                                   aria-pressed="false"
                                   data-label-sidebar="{{ __('header.nav_layout_sidebar') }}"
                                   data-label-horizontal="{{ __('header.nav_layout_horizontal') }}">
                                    <i class="fas fa-bars nav-layout-icon-sidebar" aria-hidden="true"></i>
                                    <i class="fas fa-grip-lines nav-layout-icon-top" aria-hidden="true"></i>
                                </a>
                            </li>

                            {{-- Theme Toggle --}}
                            <li class="nav-item dropdown notification_dropdown header-item-desktop-only">
                               <a class="nav-link bell dlab-theme-mode p-0" href="javascript:void(0);">
									<i id="icon-light" class="fas fa-sun"></i>
                                   <i id="icon-dark" class="fas fa-moon"></i>
                               </a>
							</li>

                            {{-- Current plan pill (desktop header bar) --}}
                            @if($headerPlanName && ($headerIsSchoolAdmin || $headerIsSuperAdmin))
                            <li class="nav-item d-none d-lg-flex align-items-center me-2">
                                <a href="{{ $headerIsSuperAdmin && !($planCtx['institution_id'] ?? null) ? route('plan.requests') : route('plan.index') }}"
                                   class="header-plan-pill {{ !$headerPlanActive ? 'is-expired' : ($headerIsPro ? 'is-pro' : 'is-standard') }}"
                                   title="{{ __('plan.current_plan') }}">
                                    <i class="la la-crown"></i>
                                    <span>{{ $headerPlanName }}</span>
                                    @if($headerIsPro && $headerPlanActive)
                                        <span class="header-pro-badge">PRO</span>
                                    @endif
                                </a>
                            </li>
                            @endif

                            @php
                                $switchableRoles = app(\App\Services\ActiveRoleService::class)->availableRoles(Auth::user());
                                $activeSessionRole = app(\App\Services\ActiveRoleService::class)->getActiveRole(Auth::user());
                            @endphp
                            @if($switchableRoles->count() > 1)
                            <li class="nav-item dropdown header-item-desktop-only me-2">
                                <a class="nav-link d-flex align-items-center gap-1 px-2 py-1 border rounded" href="javascript:void(0);" role="button" data-bs-toggle="dropdown" title="{{ __('role.switch_role') }}">
                                    <i class="la la-user-tag"></i>
                                    <span class="d-none d-xl-inline fs-12">{{ $activeSessionRole }}</span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end p-2" style="min-width:220px;">
                                    <h6 class="dropdown-header px-2">{{ __('role.switch_role') }}</h6>
                                    @foreach($switchableRoles as $roleName)
                                        <form method="POST" action="{{ route('role.switch') }}" class="mb-1">
                                            @csrf
                                            <input type="hidden" name="role" value="{{ $roleName }}">
                                            <button type="submit" class="dropdown-item rounded {{ $roleName === $activeSessionRole ? 'active bg-primary text-white' : '' }}">
                                                {{ $roleName }}
                                                @if($roleName === $activeSessionRole)
                                                    <i class="fa fa-check float-end mt-1"></i>
                                                @endif
                                            </button>
                                        </form>
                                    @endforeach
                                </div>
                            </li>
                            @endif

                            {{-- User Profile --}}
                            <li class="nav-item dropdown header-profile">
                                <a class="nav-link d-flex align-items-center p-0" href="javascript:void(0);" role="button" data-bs-toggle="dropdown">
                                    <div class="profile-avatar-wrap {{ ($headerIsPro && $headerPlanActive) ? 'is-pro' : '' }}">
                                        <div class="profile-avatar-inner">
                                            @if(Auth::user()->profile_picture)
                                                <img src="{{ asset('storage/'.Auth::user()->profile_picture) }}" alt="Profile"/>
                                            @else
                                                <div class="profile-initials">
                                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <div class="dropdown-header text-center border-bottom pb-3">
                                        <div class="d-flex justify-content-center mb-2">
                                            <div class="profile-avatar-wrap {{ ($headerIsPro && $headerPlanActive) ? 'is-pro' : '' }}" style="padding:4px;">
                                                <div class="profile-avatar-inner" style="width:60px;height:60px;">
                                                    @if(Auth::user()->profile_picture)
                                                        <img src="{{ asset('storage/'.Auth::user()->profile_picture) }}" alt="Profile"/>
                                                    @else
                                                        <div class="profile-initials" style="font-size:22px;">
                                                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <h6 class="text-black font-w600 mb-0">{{ Auth::user()->name }}</h6>
                                        <span class="fs-12 text-muted">{{ Auth::user()->email }}</span>
                                        <div class="fs-11 text-primary mt-1">{{ app(\App\Services\ActiveRoleService::class)->getActiveRole(Auth::user()) ?? (Auth::user()->roles->pluck('name')->first() ?? 'User') }}</div>
                                        @if($headerPlanName)
                                            <a href="{{ route('plan.index') }}" class="badge {{ $headerPlanActive ? ($headerIsPro ? 'bg-warning text-dark' : 'bg-success') : 'bg-danger' }} mt-2 text-decoration-none">
                                                <i class="fa fa-crown me-1"></i> {{ $headerPlanName }}
                                                @if($headerIsPro && $headerPlanActive)
                                                    <span class="ms-1 fw-bold">PRO</span>
                                                @endif
                                            </a>
                                        @endif
                                    </div>
                                    
                                    <a href="{{ route('profile.index') }}" class="dropdown-item ai-icon">
                                        <i class="fa fa-user text-primary me-2"></i>
                                        <span class="ms-2">{{ __('header.my_profile') ?? 'My Profile' }}</span>
                                    </a>

                                    @if($headerIsSchoolAdmin)
                                    <a href="{{ route('plan.index') }}" class="dropdown-item ai-icon">
                                        <i class="fa fa-gem text-info me-2"></i>
                                        <span class="ms-2">{{ __('plan.my_plan') }}</span>
                                    </a>
                                    @elseif($headerIsSuperAdmin)
                                    <a href="{{ route('plan.requests') }}" class="dropdown-item ai-icon">
                                        <i class="fa fa-arrow-up text-info me-2"></i>
                                        <span class="ms-2">{{ __('plan.upgrade_requests') }}</span>
                                    </a>
                                    @endif
                                    
                                    <a href="#" class="dropdown-item ai-icon">
                                        <i class="fa fa-envelope text-success me-2"></i>
                                        <span class="ms-2">{{ __('header.inbox') ?? 'Inbox' }}</span>
                                    </a>
                                    
                                    @if(auth()->user()->can('setting.view') || auth()->user()->can('setting.manage'))
                                    <a href="{{ route('settings.index') }}" class="dropdown-item ai-icon">
                                        <i class="fa fa-cog text-warning me-2"></i>
                                        <span class="ms-2">{{ __('header.settings') ?? 'Settings' }}</span>
                                    </a>
                                    @endif

                                    <div class="dropdown-divider"></div>

                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item ai-icon text-danger">
                                            <svg id="icon-logout" xmlns="http://www.w3.org/2000/svg" class="text-danger" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                                            <span class="ms-2">{{ __('header.logout') ?? 'Logout' }}</span>
                                        </button>
                                    </form>
                                </div>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>
        </div>

        {{-- INLINE SCRIPT FOR SEARCH --}}
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                // Mobile: close sidebar when tapping outside (under the header bar)
                document.addEventListener('click', function(e) {
                    if (window.innerWidth >= 992) return;
                    var wrapper = document.getElementById('main-wrapper');
                    if (!wrapper || !wrapper.classList.contains('menu-toggle')) return;
                    var dlabnav = document.querySelector('.dlabnav');
                    var navControl = document.querySelector('.nav-control');
                    if (navControl && navControl.contains(e.target)) return;
                    if (dlabnav && dlabnav.contains(e.target)) return;
                    wrapper.classList.remove('menu-toggle');
                    var ham = document.querySelector('.hamburger');
                    if (ham) ham.classList.remove('is-active');
                });

                const searchInput = document.getElementById('schoolSearchInput');
                if(searchInput){
                    searchInput.addEventListener('keyup', function(e) {
                        const term = e.target.value.toLowerCase();
                        const items = document.querySelectorAll('.school-item');
                        let hasVisible = false;

                        items.forEach(item => {
                            const name = item.querySelector('.school-name').innerText.toLowerCase();
                            const code = item.querySelector('.school-code').innerText.toLowerCase();
                            if(name.includes(term) || code.includes(term)) {
                                item.style.display = 'block';
                                hasVisible = true;
                            } else {
                                item.style.display = 'none';
                            }
                        });

                        document.getElementById('noSchoolFound').style.display = hasVisible ? 'none' : 'block';
                    });
                }

                // Global Search
                const globalWrap = document.getElementById('globalSearchWrap');
                const globalToggle = document.getElementById('globalSearchToggle');
                const globalInput = document.getElementById('globalSearchInput');
                const globalDropdown = document.getElementById('globalSearchDropdown');

                const expandSearch = () => {
                    if (globalWrap) globalWrap.classList.add('is-expanded');
                    if (globalInput) setTimeout(() => globalInput.focus(), 50);
                };

                let hideDropdown = () => {};

                const collapseSearch = () => {
                    if (!globalWrap) return;
                    if (globalInput && globalInput.value.trim()) return;
                    globalWrap.classList.remove('is-expanded');
                    hideDropdown();
                };

                if (globalToggle) {
                    globalToggle.addEventListener('click', expandSearch);
                }

                if (globalInput && globalDropdown) {
                let debounceTimer = null;
                let activeIndex = -1;
                let currentResults = [];
                const searchUrl = @json(route('global-search.suggest'));
                const noResultsText = @json(__('header.global_search_no_results'));
                const hintText = @json(__('header.global_search_hint'));

                const escapeHtml = (str) => {
                    if (!str) return '';
                    return String(str)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;');
                };

                hideDropdown = () => {
                    globalDropdown.classList.remove('show');
                    globalDropdown.innerHTML = '';
                    activeIndex = -1;
                    currentResults = [];
                };

                const renderResults = (results) => {
                    currentResults = results;
                    activeIndex = -1;

                    if (!globalInput.value.trim()) {
                        hideDropdown();
                        return;
                    }

                    if (globalInput.value.trim().length < 2) {
                        globalDropdown.innerHTML = '<div class="global-search-empty">' + hintText + '</div>';
                        globalDropdown.classList.add('show');
                        return;
                    }

                    if (!results.length) {
                        globalDropdown.innerHTML = '<div class="global-search-empty">' + noResultsText + '</div>';
                        globalDropdown.classList.add('show');
                        return;
                    }

                    globalDropdown.innerHTML = results.map((item, index) => `
                        <a href="${escapeHtml(item.url)}" class="global-search-item" data-index="${index}">
                            <span class="global-search-item-icon"><i class="la ${escapeHtml(item.icon)}"></i></span>
                            <span class="global-search-item-body">
                                <div class="global-search-item-label">${escapeHtml(item.label)}</div>
                                ${item.subtitle ? `<div class="global-search-item-sub">${escapeHtml(item.subtitle)}</div>` : ''}
                            </span>
                            <span class="global-search-item-type">${escapeHtml(item.type_label)}</span>
                        </a>
                    `).join('');
                    globalDropdown.classList.add('show');
                };

                const fetchResults = (query) => {
                    fetch(searchUrl + '?q=' + encodeURIComponent(query), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(res => res.json())
                    .then(data => renderResults(data.results || []))
                    .catch(() => hideDropdown());
                };

                globalInput.addEventListener('input', function() {
                    clearTimeout(debounceTimer);
                    const query = this.value.trim();
                    if (query.length < 2) {
                        renderResults([]);
                        return;
                    }
                    debounceTimer = setTimeout(() => fetchResults(query), 280);
                });

                globalInput.addEventListener('keydown', function(e) {
                    const items = globalDropdown.querySelectorAll('.global-search-item');
                    if (!items.length) return;

                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        activeIndex = Math.min(activeIndex + 1, items.length - 1);
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        activeIndex = Math.max(activeIndex - 1, 0);
                    } else if (e.key === 'Enter' && activeIndex >= 0) {
                        e.preventDefault();
                        window.location.href = currentResults[activeIndex].url;
                        return;
                    } else if (e.key === 'Escape') {
                        hideDropdown();
                        collapseSearch();
                        return;
                    } else {
                        return;
                    }

                    items.forEach((el, i) => el.classList.toggle('active', i === activeIndex));
                    if (activeIndex >= 0) items[activeIndex].scrollIntoView({ block: 'nearest' });
                });

                globalInput.addEventListener('focus', expandSearch);

                document.addEventListener('click', function(e) {
                    const inSearch = globalWrap && globalWrap.contains(e.target);
                    if (!inSearch) {
                        hideDropdown();
                        collapseSearch();
                    }
                });
                }

            });
        </script>
         <script>
            // PWA Service Worker Registration
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function() {
                    navigator.serviceWorker.register('/sw.js').then(function(registration) {
                        console.log('ServiceWorker registration successful with scope: ', registration.scope);
                    }, function(err) {
                        console.log('ServiceWorker registration failed: ', err);
                    });
                });
            }

            // GLOBAL IN-APP OFFLINE LISTENER
            window.addEventListener('offline', function() {
                if (document.getElementById('global-offline-toast')) return;
                
                const toast = document.createElement('div');
                toast.id = 'global-offline-toast';
                toast.style.cssText = `
                    position: fixed; top: 20px; left: 50%; transform: translateX(-50%); 
                    background-color: #ff4c4c; color: #ffffff; padding: 12px 24px; 
                    border-radius: 8px; z-index: 999999; box-shadow: 0 4px 12px rgba(0,0,0,0.15); 
                    font-family: 'Poppins', sans-serif; font-size: 14px; display: flex; 
                    align-items: center; gap: 10px; animation: slideDown 0.3s ease-out;
                `;
                
                toast.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.6 19.3a3.7 3.7 0 0 1-5.3 0 3.7 3.7 0 0 1 0-5.3l1.4-1.4"></path><path d="M14.8 14.8l1.4-1.4a3.7 3.7 0 0 0-5.3-5.3l-1.4 1.4"></path><line x1="8.5" y1="15.5" x2="15.5" y2="8.5"></line><line x1="2" y1="2" x2="22" y2="22"></line></svg> 
                    Internet connection lost. You are currently offline.
                `;
                
                // Add keyframes if not exists
                if (!document.getElementById('offline-toast-keyframes')) {
                    const style = document.createElement('style');
                    style.id = 'offline-toast-keyframes';
                    style.innerHTML = `@keyframes slideDown { from { top: -50px; opacity: 0; } to { top: 20px; opacity: 1; } }`;
                    document.head.appendChild(style);
                }

                document.body.appendChild(toast);
            });

            window.addEventListener('online', function() {
                const toast = document.getElementById('global-offline-toast');
                if (toast) toast.remove();
            });
        </script>
</body>
</html>