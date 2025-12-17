<!DOCTYPE html>
<html lang="en">
<head>
	<!-- Title -->
	<title>{{ $pageTitle ?? ''  }}</title>

	<!-- Meta -->
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="author" content="dexignlabs">
	<meta name="robots" content="index, follow">

	<meta name="keywords" content="admin, dashboard, admin dashboard, admin template, template, admin panel, administration, analytics, bootstrap, modern, responsive, creative, retina ready, modern Dashboard responsive dashboard, responsive template, user experience, user interface, Bootstrap Dashboard, Analytics Dashboard, Customizable Admin Panel, EduMin template, ui kit, web app, EduMin, School Management,Dashboard Template, academy, course, courses, e-learning, education, learning, learning management system, lms, school, student, teacher">

	<meta name="description" content="EduMin - Empower your educational institution with the all-in-one Education Admin Dashboard Template. Streamline administrative tasks, manage courses, track student performance, and gain valuable insights with ease. Elevate your education management experience with a modern, responsive, and feature-packed solution. Explore EduMin now for a smarter, more efficient approach to education administration.">

	<meta property="og:title" content="EduMin - Education Admin Dashboard Template | dexignlabs">
	<meta property="og:description" content="EduMin - Empower your educational institution with the all-in-one Education Admin Dashboard Template. Streamline administrative tasks, manage courses, track student performance, and gain valuable insights with ease. Elevate your education management experience with a modern, responsive, and feature-packed solution. Explore EduMin now for a smarter, more efficient approach to education administration.">

	<meta property="og:image" content="https://edumin.dexignlab.com/xhtml/social-image.png">

	<meta name="format-detection" content="telephone=no">

	<meta name="twitter:title" content="EduMin - Education Admin Dashboard Template | dexignlabs">
	<meta name="twitter:description" content="EduMin - Empower your educational institution with the all-in-one Education Admin Dashboard Template. Streamline administrative tasks, manage courses, track student performance, and gain valuable insights with ease. Elevate your education management experience with a modern, responsive, and feature-packed solution. Explore EduMin now for a smarter, more efficient approach to education administration.">

	<meta name="twitter:image" content="https://edumin.dexignlab.com/xhtml/social-image.png">
	<meta name="twitter:card" content="summary_large_image">
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" sizes="16x16" href="./images/favicon.png">

	<!-- STYLESHEETS -->
	<link rel="stylesheet" href="{{ asset('vendor/jqvmap/css/jqvmap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/global/global.min.css') }}">
	<link rel="stylesheet" href="{{ asset('vendor/chartist/css/chartist.min.css') }}">

    <link href="{{ asset('vendor/datatables/css/jquery.dataTables.min.css')  }}" rel="stylesheet">
    <link href="{{ asset('vendor/datatables/css/responsive.bootstrap.min.css')  }}" rel="stylesheet">

	<link rel="stylesheet" href="{{ asset('vendor/bootstrap-select/dist/css/bootstrap-select.min.css') }}">
    <link class="" rel="stylesheet" href="{{ asset('css/style.css') }}">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css')  }}">
    <link href="{{ asset('vendor/bootstrap-select/dist/css/bootstrap-select.min.css')  }}" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link href="{{asset('vendor/bootstrap-daterangepicker/daterangepicker.css')}}" rel="stylesheet">
    <link href="{{asset('vendor/clockpicker/css/bootstrap-clockpicker.min.css')}}" rel="stylesheet">
    <link href="{{asset('vendor/jquery-ascolorpicker/css/ascolorpicker.min.cs')}}s" rel="stylesheet">
    <link href="{{asset('vendor/bootstrap-material-datetimepicker/css/bootstrap-material-datetimepicker.css')}}" rel="stylesheet">
    <link rel="stylesheet" href="{{asset('vendor/pickadate/themes/default.css')}}">
    <link rel="stylesheet" href="{{asset('vendor/pickadate/themes/default.date.css')}}">
    <link href="{{ asset('https://fonts.googleapis.com/icon?family=Material+Icons')  }}" rel="stylesheet">
    
    <!-- Font Awesome for Spinner -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    @yield('styles')
    <style>
        .select2-selection{
            border: 2px solid #e0e7ff !important;
            border-radius: 4px !important;
            transition: all 0.3s ease !important;
            padding: 4px 4px !important;
            height: 38px !important;
        }
        .select2-selection__arrow{
            margin: 4px !important;
        }

        .word-icon {
            margin-top: 0px;
            padding: 6px;
            font-size: 22px;
        }
        .language-select {
            width: 140px;
            padding-left: 30px;
        }

        /* Button Processing State Styles */
        .btn {
            position: relative;
            transition: all 0.2s ease-in-out;
        }
        
        .btn.disabled, .btn:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }
        
        /* Interactive click effect */
        .btn:active {
            transform: scale(0.98);
        }
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
                <img src="https://e-digitex.com/public/images/smsslogonew.png" style="width: 117px;" alt="">
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
                            
							<li class="nav-item dropdown notification_dropdown">
                               <form action="{{ url('/change-language') }}" method="GET">
                                    <div class="d-flex position-relative">
                                        <i class="text-muted fas fa-globe word-icon position-absolute"></i>
                                        <select name="language" onchange="this.form.submit()" class="form-control form-control-sm language-select" style="width: 140px;">
                                            <option value="en" {{ app()->getLocale() === 'en' ? 'selected' : '' }}>English</option>
                                            <option value="fr" {{ app()->getLocale() === 'fr' ? 'selected' : '' }}>Français</option>
                                        </select>
                                    </div>
                                </form>
							</li>
                            <li class="nav-item dropdown notification_dropdown">
                               <a class="nav-link bell dlab-theme-mode p-0" href="javascript:void(0);">
									<i id="icon-light" class="fas fa-sun"></i>
                                   <i id="icon-dark" class="fas fa-moon"></i>
                               </a>
							</li>
                            <li class="nav-item dropdown header-profile">
                                <a class="nav-link" href="javascript:void(0);" role="button" data-bs-toggle="dropdown">
                                    <img src="{{ asset('images/profile/education/pic1.jpg') }}" width="20" alt=""/>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a href="./app-profile.html" class="dropdown-item ai-icon">
                                        <svg id="icon-user1" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-user"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                        <span class="ms-2">Profile </span>
                                    </a>

                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item ai-icon">
                                            <svg id="icon-logout" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-log-out"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                                            <span class="ms-2">Logout </span>
                                        </button>
                                    </form>
                                </div>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>
        </div>