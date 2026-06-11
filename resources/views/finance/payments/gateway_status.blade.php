@extends('layouts.auth')

@section('title', __('payment_gateway.status_title'))

@section('content')
    <div class="text-center mb-4">
        <h4>{{ __('payment_gateway.status_title') }}</h4>
        <p class="text-muted mb-0">Ref: {{ $transaction->external_id }}</p>
    </div>

    @if($transaction->isCompleted())
        <div class="alert alert-success text-center">{{ __('payment_gateway.payment_success') }}</div>
        <a href="{{ route('pay.show', $token) }}" class="btn btn-primary w-100">{{ __('payment_gateway.back_to_invoice') }}</a>
    @elseif($transaction->status === 'failed')
        <div class="alert alert-danger text-center">{{ __('payment_gateway.payment_failed') }}</div>
        <a href="{{ route('pay.show', $token) }}" class="btn btn-outline-primary w-100">{{ __('payment_gateway.try_again') }}</a>
    @else
        <div class="alert alert-info text-center" id="statusMessage">
            <i class="fa fa-spinner fa-spin me-2"></i>{{ __('payment_gateway.pawapay_confirm_phone') }}
        </div>
        <p class="text-center text-muted small">{{ __('payment_gateway.polling_hint') }}</p>
        <a href="{{ route('pay.show', $token) }}" class="btn btn-light w-100 mt-3">{{ __('payment_gateway.back_to_invoice') }}</a>
    @endif
@endsection

@section('scripts')
@if(!$transaction->isCompleted() && $transaction->status !== 'failed')
<script>
(function poll() {
    setTimeout(function() {
        window.location.reload();
    }, 8000);
})();
</script>
@endif
@endsection
