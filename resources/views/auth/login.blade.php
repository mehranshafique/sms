@extends('layouts.auth')

@section('title', __('login.page_title') ?? 'Login')

@section('content')
    <h4 class="text-center mb-4">{{ __('login.welcome_back') }}</h4>

    @if(session('otp_sent'))
        <div class="alert alert-success">{{ __('login.otp_sent', ['phone' => session('otp_masked_phone', '****')]) }}</div>
    @endif

    <ul class="nav nav-pills nav-fill mb-4 bg-light rounded p-1" role="tablist">
        <li class="nav-item"><a class="nav-link active fw-bold" data-bs-toggle="tab" href="#tab-password">{{ __('login.tab_password') }}</a></li>
        <li class="nav-item"><a class="nav-link fw-bold" data-bs-toggle="tab" href="#tab-otp">{{ __('login.tab_otp') }}</a></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-password">
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="form-group mb-3">
                    <label class="form-label" for="login">{{ __('login.email_label') }}</label>
                    <input type="text" class="form-control" placeholder="{{ __('login.identifier_placeholder') }}" name="login" id="login" value="{{ old('login') }}" required autofocus>
                    <small class="text-muted">{{ __('login.identifier_help') }}</small>
                </div>
                <div class="mb-4 position-relative">
                    <label class="form-label" for="password">{{ __('login.password_label') }}</label>
                    <input type="password" id="password" class="form-control" name="password" required autocomplete="current-password">
                    <span class="show-pass eye"><i class="fa fa-eye-slash"></i><i class="fa fa-eye"></i></span>
                </div>
                <div class="form-row d-flex flex-wrap justify-content-between mt-2 mb-3">
                    <div class="form-check custom-checkbox ms-1">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">{{ __('login.remember_me') }}</label>
                    </div>
                    @if (Route::has('password.request'))
                        <a class="btn-link" href="{{ route('password.request') }}">{{ __('login.forgot_password') }}</a>
                    @endif
                </div>
                <button type="submit" class="btn btn-primary btn-block w-100">{{ __('login.submit_btn') }}</button>
            </form>
        </div>

        <div class="tab-pane fade" id="tab-otp">
            @if(!session('otp_sent'))
            <form method="POST" action="{{ route('login.otp.request') }}">
                @csrf
                <div class="form-group mb-3">
                    <label class="form-label fw-bold">{{ __('login.otp_identifier') }}</label>
                    <input type="text" class="form-control" name="identifier" value="{{ old('identifier') }}" placeholder="{{ __('login.identifier_placeholder') }}" required>
                    <small class="text-muted">{{ __('login.identifier_help') }}</small>
                </div>
                <button type="submit" class="btn btn-outline-primary w-100">{{ __('login.send_otp') }}</button>
            </form>
            @else
            <form method="POST" action="{{ route('login.otp.verify') }}">
                @csrf
                <div class="form-group mb-3">
                    <label class="form-label fw-bold">{{ __('login.otp_code') }}</label>
                    <input type="text" class="form-control text-center fs-4 letter-spacing-2" name="otp" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" placeholder="••••••" required autofocus>
                    <small class="text-muted d-block text-center mt-2">{{ __('login.otp_sent', ['phone' => session('otp_masked_phone', '****')]) }}</small>
                </div>
                <button type="submit" class="btn btn-primary w-100 mb-2">{{ __('login.verify_otp') }}</button>
                <a href="{{ route('login') }}" class="btn btn-link w-100 small">{{ __('login.resend_otp') }}</a>
            </form>
            @endif
        </div>
    </div>

    <div class="text-center mt-4 pt-3 border-top">
        <p class="text-muted small mb-2">{{ __('login.help_links') }}</p>
        <div class="d-flex flex-wrap justify-content-center gap-2 gap-md-3">
            <a href="{{ route('help.index') }}" class="btn-link small">{{ __('manual.hub_title') }}</a>
        </div>
    </div>
@endsection
