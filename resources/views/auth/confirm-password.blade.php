@extends('layouts.auth')

@section('title', 'Confirm Password')

@section('content')
    <h4 class="text-center mb-4">Confirm Password</h4>
    
    <p class="text-center mb-4 text-muted">
        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" >
        @csrf

        <!-- Password -->
        <div class="mb-4 position-relative">
            <label class="form-label" for="password">Password</label>
            <input 
                type="password" 
                id="password" 
                class="form-control" 
                name="password" 
                required 
                autocomplete="current-password">
            <span class="show-pass eye">
                <i class="fa fa-eye-slash"></i>
                <i class="fa fa-eye"></i>
            </span>
        </div>

        <div class="text-center mt-4">
            <button type="submit" class="btn btn-primary btn-block">
                {{ __('Confirm') }}
            </button>
        </div>
    </form>
@endsection