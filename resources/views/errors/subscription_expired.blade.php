<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Subscription Expired - Digitex</title>
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon.png') }}">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    
</head>

<body class="h-100">
    <div class="authincation h-100">
        <div class="container h-100">
            <div class="row justify-content-center h-100 align-items-center">
                <div class="col-md-6">
                    <div class="error-page">
                        <div class="error-inner text-center">
                            <div class="dz-error" data-text="403">403</div>
                            <h4 class="error-head"><i class="fa fa-exclamation-triangle text-warning"></i> Subscription Expired</h4>
                            <p class="error-text font-weight-bold">Your institution's subscription has expired.</p>
                            <p>Please contact the system administrator or renew your subscription to continue accessing the platform.</p>
                            
                            @if(auth()->user()->hasRole('Head Officer'))
                                <div class="mt-4">
                                    <a href="{{ route('dashboard') }}" class="btn btn-primary">Go to Dashboard (Limited Access)</a>
                                    {{-- You might want a link to a billing page here later --}}
                                </div>
                            @else
                                <div class="mt-4">
                                    <a href="{{ route('login') }}" class="btn btn-secondary">Back to Login</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>