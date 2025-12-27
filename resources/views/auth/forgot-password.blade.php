@extends('layouts.auth')

@section('title', __('login.forgot_password_title') ?? 'Forgot Password')

@section('content')
    <h4 class="text-center mb-4">{{ __('login.reset_password_header') ?? 'Reset Password' }}</h4>
    
    <p class="text-center mb-4">
        {{ __('login.forgot_password_desc') ?? 'Enter your email address and we will send you a link to reset your password.' }}
    </p>

    <form method="POST" action="{{ route('password.email') }}" >
        @csrf
        
        {{-- Email Input --}}
        <div class="form-group">
            <label class="form-label" for="email">{{ __('login.email_label') ?? 'Email Address' }}</label>
            <input 
                type="email" 
                class="form-control @error('email') is-invalid @enderror" 
                placeholder="{{ __('login.email_placeholder') ?? 'hello@example.com' }}" 
                name="email" 
                id="email"
                value="{{ old('email') }}" 
                required 
                autofocus>
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