<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ __('subscription.title') }} - {{ config('app.name') }}</title>
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon.png') }}">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    
    <style>
        .authincation-content {
            background: #fff;
            box-shadow: 0 0 35px 0 rgba(154, 161, 171, 0.15);
            border-radius: 5px; 
            padding: 40px;
        }
        .error-page {
            background: transparent;
            padding: 0;
        }
        .error-inner {
            background: #fff; 
            padding: 50px 30px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }
        .dz-error {
            font-size: 100px;
            font-weight: 700;
            color: #ff5e5e;
            line-height: 1;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        .error-head {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #3d4465;
        }
        .error-text {
            font-size: 18px;
            color: #7e7e7e;
            margin-bottom: 30px;
        }
        .btn-primary {
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 50px;
            box-shadow: 0 5px 15px rgba(var(--primary-rgb), 0.3);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(var(--primary-rgb), 0.4);
        }
        .icon-box {
            width: 80px;
            height: 80px;
            background: rgba(255, 94, 94, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
        }
        .icon-box i {
            font-size: 40px;
            color: #ff5e5e;
        }
    </style>
</head>

<body class="h-100" style="background: #fbfbfb;">
    <div class="authincation h-100">
        <div class="container h-100">
            <div class="row justify-content-center h-100 align-items-center">
                <div class="col-md-6">
                    <div class="error-inner text-center">
                        <div class="icon-box">
                            <i class="fa fa-lock"></i>
                        </div>
                        
                        <h4 class="error-head">{{ __('subscription.expired_title') }}</h4>
                        
                        <p class="error-text font-weight-bold text-danger mb-2">
                            {{ __('subscription.expired_message') }}
                        </p>
                        
                        <p class="text-muted mb-4 px-4">
                            {{ __('subscription.contact_admin') }}
                        </p>
                        
                        <div class="d-flex justify-content-center gap-3">
                            @if(auth()->check() && auth()->user()->hasRole('Head Officer'))
                                <a href="{{ route('dashboard') }}" class="btn btn-primary">
                                    <i class="fa fa-tachometer me-2"></i> {{ __('subscription.go_to_dashboard') }}
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-primary">
                                    <i class="fa fa-sign-in me-2"></i> {{ __('subscription.back_to_login') }}
                                </a>
                            @endif
                        </div>
                        
                        <div class="mt-4 pt-3 border-top">
                            <small class="text-muted">Error Code: <strong>{{ __('subscription.error_code') }}</strong></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>