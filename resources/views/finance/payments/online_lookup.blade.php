@extends('layouts.auth')

@section('title', __('online_pay.page_title'))

@section('content')
    <h4 class="text-center mb-2">{{ __('online_pay.lookup_title') }}</h4>
    <p class="text-center text-muted mb-4">{{ __('online_pay.lookup_subtitle') }}</p>

    <form method="POST" action="{{ route('pay.find') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label fw-bold">{{ __('online_pay.invoice_number') }}</label>
            <input type="text" name="invoice_number" class="form-control" value="{{ old('invoice_number') }}"
                placeholder="{{ __('online_pay.invoice_number_placeholder') }}" required autofocus>
        </div>
        <div class="mb-4">
            <label class="form-label fw-bold">{{ __('online_pay.admission_number') }}</label>
            <input type="text" name="admission_number" class="form-control" value="{{ old('admission_number') }}"
                placeholder="{{ __('online_pay.admission_number_placeholder') }}" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">{{ __('online_pay.find_invoice') }}</button>
    </form>

    <div class="text-center mt-4">
        <small class="text-muted">{{ __('online_pay.powered_by') }}</small>
    </div>
@endsection
