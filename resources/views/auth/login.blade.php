@extends('layouts.auth')

@section('title', __('login.page_title') ?? 'Login')

@section('content')
    <h4 class="text-center mb-4">{{ __('login.welcome_back') }}</h4>
    
    {{-- Added 'ajax-form' class here to trigger AJAX handling --}}
    <form method="POST" action="{{ route('login') }}" class="ajax-form">
        @csrf
        
        {{-- Email Input --}}
        <div class="form-group">
            <label class="form-label" for="login">{{ __('login.email_label') }}</label>
            <input 
                type="text" 
                class="form-control" 
                placeholder="{{ __('login.email_placeholder') }}" 
                name="login" 
                id="login"
                value="{{ old('login') }}" 
                required 
                autofocus>
        </div>

        {{-- Password Input --}}
        <div class="mb-4 position-relative">
            <label class="form-label" for="password">{{ __('login.password_label') }}</label>
            <input 
                type="password" 
                id="password" 
                class="form-control" 
                placeholder="{{ __('login.password_placeholder') }}"
                name="password"
                required 
                autocomplete="current-password">
            
            <span class="show-pass eye">
                <i class="fa fa-eye-slash"></i>
                <i class="fa fa-eye"></i>
            </span>
        </div>

        {{-- Remember Me & Forgot Password --}}
        <div class="form-row d-flex flex-wrap justify-content-between mt-4 mb-2">
            <div class="form-group">
                <div class="form-check custom-checkbox ms-1">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">{{ __('login.remember_me') }}</label>
                </div>
            </div>
            <div class="form-group ms-2">
                @if (Route::has('password.request'))
                    <a class="btn-link" href="{{ route('password.request') }}">{{ __('login.forgot_password') }}</a>
                @endif
            </div>
        </div>

        {{-- Submit Button --}}
        <div class="text-center">
            <button type="submit" class="btn btn-primary btn-block">{{ __('login.submit_btn') }}</button>
        </div>
    </form>
@endsection

@section('scripts')
<!-- OFFLINE UI HANDLER -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // 1. Target your login form
        const loginForm = document.querySelector('form'); 

        if (loginForm) {
            loginForm.addEventListener('submit', function(e) {
                // Check if the browser is currently offline
                if (!navigator.onLine) {
                    e.preventDefault(); // Stop the form from trying to load a new page
                    // Also stop the layout's AJAX handler from executing
                    e.stopImmediatePropagation(); 
                    showOfflineWarning();
                }
            });
        }

        // 2. Custom Floating Toast Notification
        function showOfflineWarning() {
            // Prevent multiple toasts from stacking
            if (document.getElementById('offline-toast')) return;

            const toast = document.createElement('div');
            toast.id = 'offline-toast';
            
            // Styling the toast to look professional and modern
            toast.style.cssText = `
                position: fixed; 
                top: 20px; 
                left: 50%; 
                transform: translateX(-50%); 
                background-color: #ff4c4c; 
                color: #ffffff; 
                padding: 12px 24px; 
                border-radius: 8px; 
                z-index: 999999; 
                box-shadow: 0 4px 12px rgba(0,0,0,0.15); 
                font-family: 'Poppins', sans-serif; 
                font-weight: 500; 
                font-size: 14px;
                display: flex;
                align-items: center;
                gap: 10px;
                animation: slideDown 0.3s ease-out;
            `;

            toast.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.6 19.3a3.7 3.7 0 0 1-5.3 0 3.7 3.7 0 0 1 0-5.3l1.4-1.4"></path><path d="M14.8 14.8l1.4-1.4a3.7 3.7 0 0 0-5.3-5.3l-1.4 1.4"></path><line x1="8.5" y1="15.5" x2="15.5" y2="8.5"></line><line x1="2" y1="2" x2="22" y2="22"></line></svg>
                Internet connection is required to log in.
            `;

            // Add animation styles dynamically
            const style = document.createElement('style');
            style.innerHTML = `
                @keyframes slideDown {
                    from { top: -50px; opacity: 0; }
                    to { top: 20px; opacity: 1; }
                }
            `;
            document.head.appendChild(style);

            document.body.appendChild(toast);

            // Automatically remove the toast after 4 seconds
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        // Listen for the connection to come back
        window.addEventListener('online', () => {
            const existingToast = document.getElementById('offline-toast');
            if (existingToast) existingToast.remove();
        });
    });
</script>
@endsection