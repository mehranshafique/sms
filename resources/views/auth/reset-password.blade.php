@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
    <h4 class="text-center mb-4">Set New Password</h4>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div class="form-group">
            <label class="form-label" for="email">{{ __('login.email_label') ?? 'Email Address' }}</label>
            <input 
                type="email" 
                class="form-control" 
                name="email" 
                id="email"
                value="{{ old('email', $request->email) }}" 
                required 
                autofocus>
        </div>

        <!-- Password -->
        <div class="mb-4 position-relative">
            <label class="form-label" for="password">New Password</label>
            <input 
                type="password" 
                id="password" 
                class="form-control" 
                name="password" 
                required 
                autocomplete="new-password">
            <span class="show-pass eye">
                <i class="fa fa-eye-slash"></i>
                <i class="fa fa-eye"></i>
            </span>
        </div>

        <!-- Confirm Password -->
        <div class="mb-4 position-relative">
            <label class="form-label" for="password_confirmation">Confirm Password</label>
            <input 
                type="password" 
                id="password_confirmation" 
                class="form-control" 
                name="password_confirmation" 
                required 
                autocomplete="new-password">
            <span class="show-pass eye">
                <i class="fa fa-eye-slash"></i>
                <i class="fa fa-eye"></i>
            </span>
        </div>

        <div class="text-center mt-4">
            <button type="submit" class="btn btn-primary btn-block">
                {{ __('Reset Password') }}
            </button>
        </div>
    </form>
@endsection