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
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon.png') }}">

	<!-- STYLESHEETS -->
	<link rel="stylesheet" href="{{ asset('vendor/jqvmap/css/jqvmap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/global/global.min.css') }}">
	<link rel="stylesheet" href="{{ asset('vendor/chartist/css/chartist.min.css') }}">

    <link href="{{ asset('vendor/datatables/css/jquery.dataTables.min.css')  }}" rel="stylesheet">
    <link href="{{ asset('vendor/datatables/css/responsive.bootstrap.min.css')  }}" rel="stylesheet">

	<link rel="stylesheet" href="{{ asset('vendor/bootstrap-select/dist/css/bootstrap-select.min.css') }}">
    <link class="" rel="stylesheet" href="{{ asset('css/style.css') }}">
    
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
        .select2-selection{
            border: 1px solid #d9dee3 !important;
            border-radius: 4px !important;
            height: 38px !important;
        }
        .dtp { z-index: 9999 !important; }

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
    </style>
</head>
<body>

    <!--*******************
        Preloader start
    ********************-->
    <div id="preloader">
        <div class="sk-three-bounce">
            <div class="sk-child sk-bounce1"></div>
            <div class="sk-child sk-bounce2"></div>
            <div class="sk-child sk-bounce3"></div>
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
                        $institutionLogo = "https://e-digitex.com/public/images/smsslogonew.png";
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
                        <div class="header-left">
                            <div class="dashboard_bar h4">
								{{ $pageTitle ?? ''  }}
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

                                // Notification Setup
                                $notifications = collect();
                                $unreadCount = 0;

                                if ($user) {
                                    // 1. Institution Switcher Logic
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

                                    // 2. Smart Notification Logic
                                    $isAdmin = $user->hasRole([\App\Enums\RoleEnum::SUPER_ADMIN->value, \App\Enums\RoleEnum::HEAD_OFFICER->value, \App\Enums\RoleEnum::SCHOOL_ADMIN->value]);
                                    $isStudent = $user->hasRole(\App\Enums\RoleEnum::STUDENT->value);
                                    $isTeacher = $user->hasRole([\App\Enums\RoleEnum::TEACHER->value, \App\Enums\RoleEnum::STAFF->value]);
                                    $contextId = $activeId === 'global' ? null : $activeId;

                                    // A. Admin Notifications
                                    if ($isAdmin) {
                                        try {
                                            if (\Illuminate\Support\Facades\Schema::hasTable('budget_requests') || \Illuminate\Support\Facades\Schema::hasTable('fund_requests')) {
                                                $table = \Illuminate\Support\Facades\Schema::hasTable('budget_requests') ? 'budget_requests' : 'fund_requests';
                                                $q = \Illuminate\Support\Facades\DB::table($table)->where('status', 'pending');
                                                if ($contextId) $q->where('institution_id', $contextId);
                                                $count = $q->count();
                                                if ($count > 0) {
                                                    $notifications->push([
                                                        'icon' => 'fa-money-bill text-warning',
                                                        'title' => 'Pending Fund Requests',
                                                        'desc' => "{$count} pending fund requests to review.",
                                                        'link' => route('budgets.requests')
                                                    ]);
                                                    $unreadCount += $count;
                                                }
                                            }

                                            if (\Illuminate\Support\Facades\Schema::hasTable('student_requests')) {
                                                $q = \App\Models\StudentRequest::where('status', 'pending');
                                                if ($contextId) $q->where('institution_id', $contextId);
                                                $count = $q->count();
                                                if ($count > 0) {
                                                    $notifications->push([
                                                        'icon' => 'fa-envelope text-primary',
                                                        'title' => 'Pending Requests/Leaves',
                                                        'desc' => "{$count} new requests require your approval.",
                                                        'link' => route('requests.index')
                                                    ]);
                                                    $unreadCount += $count;
                                                }
                                            }
                                        } catch (\Exception $e) {}
                                    }

                                    // B. Student Notifications
                                    if ($isStudent) {
                                        try {
                                            $studentProfile = $user->student;
                                            if ($studentProfile) {
                                                $unpaid = \App\Models\Invoice::where('student_id', $studentProfile->id)
                                                    ->whereIn('status', ['unpaid', 'partial'])->count();
                                                if ($unpaid > 0) {
                                                    $notifications->push([
                                                        'icon' => 'fa-file-invoice text-danger',
                                                        'title' => 'Unpaid Fees',
                                                        'desc' => "You have {$unpaid} pending fee invoices.",
                                                        'link' => route('dashboard')
                                                    ]);
                                                    $unreadCount += $unpaid;
                                                }
                                                
                                                $elections = \App\Models\Election::where('status', 'published')
                                                    ->where('start_date', '<=', now())
                                                    ->where('end_date', '>=', now())
                                                    ->where('institution_id', $studentProfile->institution_id)->count();
                                                if ($elections > 0) {
                                                    $notifications->push([
                                                        'icon' => 'fa-vote-yea text-success',
                                                        'title' => 'Active Elections',
                                                        'desc' => "{$elections} elections are open for voting.",
                                                        'link' => route('student.elections.index')
                                                    ]);
                                                    $unreadCount += $elections;
                                                }

                                                $notices = \App\Models\Notice::whereIn('audience', ['all', 'student'])
                                                    ->where('is_published', true)
                                                    ->where('created_at', '>=', now()->subDays(5))
                                                    ->where(function($q) use ($studentProfile) {
                                                        $q->where('institution_id', $studentProfile->institution_id)->orWhereNull('institution_id');
                                                    })->count();
                                                if ($notices > 0) {
                                                    $notifications->push([
                                                        'icon' => 'fa-bullhorn text-info',
                                                        'title' => 'New Announcements',
                                                        'desc' => "{$notices} new notices posted recently.",
                                                        'link' => route('student.notices.index')
                                                    ]);
                                                    $unreadCount += $notices;
                                                }
                                            }
                                        } catch (\Exception $e) {}
                                    }

                                    // C. Staff / Teacher Notifications
                                    if ($isTeacher) {
                                        try {
                                            $notices = \App\Models\Notice::whereIn('audience', ['all', 'staff'])
                                                ->where('is_published', true)
                                                ->where('created_at', '>=', now()->subDays(5))
                                                ->where(function($q) use ($contextId) {
                                                    if ($contextId) $q->where('institution_id', $contextId)->orWhereNull('institution_id');
                                                })->count();
                                            if ($notices > 0) {
                                                $notifications->push([
                                                    'icon' => 'fa-bullhorn text-info',
                                                    'title' => 'New Staff Announcements',
                                                    'desc' => "{$notices} new notices posted.",
                                                    'link' => route('notices.index')
                                                ]);
                                                $unreadCount += $notices;
                                            }

                                            $reqs = \App\Models\StudentRequest::where('created_by', $user->id)
                                                ->whereIn('status', ['approved', 'rejected'])
                                                ->where('updated_at', '>=', now()->subDays(3))->count();
                                            if ($reqs > 0) {
                                                 $notifications->push([
                                                    'icon' => 'fa-check-circle text-success',
                                                    'title' => 'Request Updated',
                                                    'desc' => "{$reqs} of your requests have been reviewed.",
                                                    'link' => route('requests.index')
                                                ]);
                                                $unreadCount += $reqs;
                                            }
                                        } catch (\Exception $e) {}
                                    }
                                }
                            @endphp

                            {{-- Current Session Display --}}
                            @if($currentSessionTitle)
                            <li class="nav-item d-flex align-items-center me-3 d-none d-sm-flex">
                                <span class="badge badge-warning light text-warning fs-12 font-w600 shadow-sm">
                                    <i class="fa fa-calendar me-1"></i> {{ $currentSessionTitle }}
                                </span>
                            </li>
                            @endif

                            {{-- DYNAMIC NOTIFICATION BELL --}}
                            <li class="nav-item dropdown notification_dropdown">
                                <a class="nav-link bell ai-icon" href="#" role="button" data-bs-toggle="dropdown" title="Notifications">
                                    <i class="fa fa-bell"></i>
                                    @if($unreadCount > 0)
                                        <div class="pulse-css"></div>
                                        <span class="badge bg-danger rounded-circle text-white" style="position: absolute; top: 0px; right: 0px; font-size: 10px; padding: 3px 5px;">{{ $unreadCount }}</span>
                                    @endif
                                </a>
                                <div class="dropdown-menu dropdown-menu-end p-0" style="min-width: 320px;">
                                    <div class="p-3 border-bottom bg-light rounded-top d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 text-black fw-bold">Notifications</h6>
                                        <span class="badge bg-primary text-white">{{ $unreadCount }} New</span>
                                    </div>
                                    <div id="DZ_W_Notification1" class="widget-media dz-scroll p-3" style="height:auto; max-height:380px; overflow-y:auto;">
                                        <ul class="timeline">
                                            @forelse($notifications as $notif)
                                                <li>
                                                    <a href="{{ $notif['link'] }}" class="notification-link">
                                                        <div class="timeline-panel rounded p-2 mb-2 border">
                                                            <div class="media me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: #f8f9fa; border-radius: 50%;">
                                                                <i class="fa {{ $notif['icon'] }} fs-20"></i>
                                                            </div>
                                                            <div class="media-body">
                                                                <h6 class="mb-1 text-dark fw-bold">{{ $notif['title'] }}</h6>
                                                                <small class="d-block text-muted">{{ $notif['desc'] }}</small>
                                                            </div>
                                                        </div>
                                                    </a>
                                                </li>
                                            @empty
                                                <li class="text-center text-muted py-4">
                                                    <i class="fa fa-bell-slash fs-24 mb-2 d-block opacity-50"></i>
                                                    No new notifications
                                                </li>
                                            @endforelse
                                        </ul>
                                    </div>
                                </div>
                            </li>

                            {{-- Institution Context Switcher --}}
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
							<li class="nav-item dropdown notification_dropdown">
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
                            
                            {{-- Theme Toggle --}}
                            <li class="nav-item dropdown notification_dropdown">
                               <a class="nav-link bell dlab-theme-mode p-0" href="javascript:void(0);">
									<i id="icon-light" class="fas fa-sun"></i>
                                   <i id="icon-dark" class="fas fa-moon"></i>
                               </a>
							</li>

                            {{-- User Profile --}}
                            <li class="nav-item dropdown header-profile">
                                <a class="nav-link" href="javascript:void(0);" role="button" data-bs-toggle="dropdown">
                                    <div class="header-info me-2 d-flex align-items-center">
                                        <span class="text-black font-w600"></span>
                                    </div>
                                    @if(Auth::user()->profile_picture)
                                        <img src="{{ asset('storage/'.Auth::user()->profile_picture) }}" width="35" alt="Profile" style="object-fit: cover; border-radius: 50%;"/>
                                    @else
                                        <div class="profile-initials">
                                            {{ substr(Auth::user()->name, 0, 1) }}
                                        </div>
                                    @endif
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <div class="dropdown-header text-center border-bottom pb-3">
                                        @if(Auth::user()->profile_picture)
                                            <div class="mb-2">
                                                <img src="{{ asset('storage/'.Auth::user()->profile_picture) }}" width="60" height="60" alt="Profile" style="object-fit: cover; border-radius: 50%; border: 2px solid #eee;"/>
                                            </div>
                                        @endif
                                        <h6 class="text-black font-w600 mb-0">{{ Auth::user()->name }}</h6>
                                        <span class="fs-12 text-muted">{{ Auth::user()->email }}</span>
                                        <div class="fs-11 text-primary mt-1">{{ Auth::user()->roles->pluck('name')->first() ?? 'User' }}</div>
                                    </div>
                                    
                                    <a href="{{ route('profile.index') }}" class="dropdown-item ai-icon">
                                        <i class="fa fa-user text-primary me-2"></i>
                                        <span class="ms-2">{{ __('header.my_profile') ?? 'My Profile' }}</span>
                                    </a>
                                    
                                    <a href="#" class="dropdown-item ai-icon">
                                        <i class="fa fa-envelope text-success me-2"></i>
                                        <span class="ms-2">{{ __('header.inbox') ?? 'Inbox' }}</span>
                                    </a>
                                    
                                    <a href="{{ route('settings.index') }}" class="dropdown-item ai-icon">
                                        <i class="fa fa-cog text-warning me-2"></i>
                                        <span class="ms-2">{{ __('header.settings') ?? 'Settings' }}</span>
                                    </a>

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
            });
        </script>
</body>
</html>