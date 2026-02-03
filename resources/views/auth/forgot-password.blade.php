@extends('layouts.auth')

@section('title', __('login.forgot_password_title') ?? 'Forgot Password')

@section('content')
    <h4 class="text-center mb-4">{{ __('login.reset_password_header') ?? 'Reset Password' }}</h4>
    
    <p class="text-center mb-4">
        {{ __('login.forgot_password_desc') ?? 'Enter your email, username, or ID and we will send you a link to reset your password.' }}
    </p>

    {{-- We point to a custom route or the standard one, but we must handle the 'login' input in the backend --}}
    <form method="POST" action="{{ route('password.email') }}" >
        @csrf
        
        {{-- Login Input (Email/Username/Shortcode) --}}
        <div class="form-group">
            <label class="form-label" for="login">{{ __('login.email_username_label') ?? 'Email, Username or ID' }}</label>
            <input 
                type="text" 
                class="form-control @error('email') is-invalid @enderror" 
                placeholder="{{ __('login.login_placeholder') ?? 'Enter Email, Username or ID' }}" 
                name="login" 
                id="login"
                value="{{ old('login') }}" 
                required 
                autofocus>
                
            @error('email')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        {{-- Submit Button --}}
        <div class="text-center mt-4">
            <button type="submit" class="btn btn-primary btn-block">{{ __('login.send_reset_link') ?? 'Send Password Reset Link' }}</button>
        </div>
        
        <div class="text-center mt-3">
            <a href="{{ route('login') }}" class="text-primary">{{ __('login.back_to_login') ?? 'Back to Login' }}</a>
        </div>
    </form>
@endsection