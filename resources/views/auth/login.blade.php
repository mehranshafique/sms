@extends('layouts.auth')

@section('title', __('login.page_title') ?? 'Login')

@section('content')
    <h4 class="text-center mb-4">{{ __('login.welcome_back') }}</h4>
    
    {{-- Added 'ajax-form' class here to trigger AJAX handling --}}
    <form method="POST" action="{{ route('login') }}" >
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