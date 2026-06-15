@extends('layouts.auth')

@section('title', __('online_pay.pay_title') . ' #' . $invoice->invoice_number)

@section('styles')
<style>
    .pay-summary { background: #f8f9fa; border-radius: 8px; padding: 16px; }
    .merchant-box { background: #e8f4fd; border-left: 4px solid #002b80; padding: 12px; border-radius: 4px; }
    .nav-pills .nav-link { font-size: 13px; padding: 8px 12px; }
    .nav-pills .nav-link.active { background: #002b80; }
</style>
@endsection

@section('content')
    <div class="text-center mb-3">
        <h4 class="mb-1">{{ $invoice->institution->name ?? config('app.name') }}</h4>
        <p class="text-muted mb-0">{{ __('online_pay.pay_title') }} <strong>#{{ $invoice->invoice_number }}</strong></p>
    </div>

    @php $due = max(0, $invoice->total_amount - $invoice->paid_amount); @endphp

    <div class="pay-summary mb-3">
        <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">{{ __('online_pay.student') }}</span>
            <strong>{{ $invoice->student->full_name }}</strong>
        </div>
        <div class="d-flex justify-content-between">
            <span class="text-muted">{{ __('online_pay.balance_due') }}</span>
            <strong class="text-danger">{{ $currency }} {{ number_format($due, 2) }}</strong>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    @if($invoice->status === 'paid' || $due <= 0.01)
        <div class="alert alert-success text-center mb-0">{{ __('online_pay.already_paid') }}</div>
    @else
        <ul class="nav nav-pills nav-fill mb-3" role="tablist">
            @if($gatewayActive && !empty($gatewayMethods))
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-gateway" type="button">{{ __('payment_gateway.pay_now') }}</button>
            </li>
            @endif
            @if($manualProofEnabled)
            <li class="nav-item">
                <button class="nav-link {{ (!$gatewayActive || empty($gatewayMethods)) ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#tab-proof" type="button">{{ __('payment_proof.upload_tab') }}</button>
            </li>
            @endif
        </ul>

        <div class="tab-content">
            @if($gatewayActive && !empty($gatewayMethods))
            <div class="tab-pane fade show active" id="tab-gateway">
                @if(($gatewayEnvironment ?? 'production') === 'sandbox' && $gatewayProvider === 'pawapay')
                <div class="alert alert-warning small mb-3">
                    <strong>{{ __('payment_gateway.sandbox_mode_title') }}</strong><br>
                    {{ __('payment_gateway.sandbox_pawapay_help', ['success' => '0893456789']) }}
                </div>
                @endif
                <div class="alert alert-light border small mb-3">
                    {{ __('payment_gateway.pay_now_help', ['provider' => config('payment_gateways.providers.'.$gatewayProvider.'.label') ?? $gatewayProvider]) }}
                </div>
                <form method="POST" action="{{ route('pay.gateway', $invoice->payment_token) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">{{ __('online_pay.payer_name') }} *</label>
                        <input type="text" name="payer_name" class="form-control" value="{{ old('payer_name') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('online_pay.payer_phone') }} *</label>
                        <input type="text" name="payer_phone" class="form-control" placeholder="243..." value="{{ old('payer_phone') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('online_pay.amount') }} *</label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01" max="{{ $due }}" value="{{ old('amount', number_format($due, 2, '.', '')) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('online_pay.method') }} *</label>
                        <select name="method" class="form-control" required>
                            @foreach($gatewayMethods as $key => $method)
                                <option value="{{ $key }}">{{ __('payment.' . $key) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">{{ __('payment_gateway.continue_checkout') }}</button>
                </form>
            </div>
            @endif

            @if($manualProofEnabled)
            <div class="tab-pane fade {{ (!$gatewayActive || empty($gatewayMethods)) ? 'show active' : '' }}" id="tab-proof">
                <div class="alert alert-light border small mb-3">{{ __('payment_proof.upload_help') }}</div>
                <form method="POST" action="{{ route('pay.proof', $invoice->payment_token) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">{{ __('online_pay.payer_name') }} *</label>
                        <input type="text" name="payer_name" class="form-control" value="{{ old('payer_name') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('online_pay.payer_phone') }} *</label>
                        <input type="text" name="payer_phone" class="form-control" value="{{ old('payer_phone') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('online_pay.amount') }} *</label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01" max="{{ $due }}" value="{{ old('amount', number_format($due, 2, '.', '')) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('online_pay.method') }} *</label>
                        <select name="method" id="proofMethod" class="form-control" required>
                            @foreach($methods as $key => $method)
                                <option value="{{ $key }}" data-config='@json($method)'>{{ __('payment.' . $key) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="merchant-box mb-3" id="proofMerchantBox" style="display:none;">
                        <strong>{{ __('online_pay.merchant_info') }}</strong>
                        <div id="proofMerchantContent" class="small mt-2"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('payment_proof.paid_at') }} *</label>
                        <input type="datetime-local" name="paid_at" class="form-control" value="{{ old('paid_at', now()->format('Y-m-d\TH:i')) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('payment_proof.transaction_reference') }} *</label>
                        <input type="text" name="transaction_reference" class="form-control" value="{{ old('transaction_reference') }}" required>
                        <small class="text-muted">{{ __('online_pay.mobile_reference_help') }}</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('payment_proof.receipt') }}</label>
                        <input type="file" name="receipt" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                        <small class="text-muted">{{ __('payment_proof.receipt_help') }}</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('online_pay.notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100">{{ __('payment_proof.submit') }}</button>
                </form>
            </div>
            @endif
        </div>

        @if(!$gatewayActive && !$manualProofEnabled)
            <div class="alert alert-warning">{{ __('payment.no_methods_enabled') }}</div>
        @endif
    @endif

    <div class="text-center mt-4">
        <a href="{{ route('pay.lookup') }}" class="small">{{ __('online_pay.lookup_alt') }}</a>
    </div>
@endsection

@section('scripts')
<script>
(function() {
    const sel = document.getElementById('proofMethod');
    if (!sel) return;
    function render() {
        const cfg = JSON.parse(sel.options[sel.selectedIndex].getAttribute('data-config') || '{}');
        const box = document.getElementById('proofMerchantBox');
        const content = document.getElementById('proofMerchantContent');
        let html = '';
        if (cfg.merchant_code) html += '<div><strong>Code:</strong> ' + cfg.merchant_code + '</div>';
        if (cfg.bank_name) html += '<div><strong>Bank:</strong> ' + cfg.bank_name + '</div>';
        if (cfg.account_name) html += '<div><strong>Account:</strong> ' + cfg.account_name + ' / ' + (cfg.account_number||'') + '</div>';
        if (cfg.instructions) html += '<div class="mt-2">' + cfg.instructions.replace(/\n/g,'<br>') + '</div>';
        if (html) { content.innerHTML = html; box.style.display = 'block'; } else { box.style.display = 'none'; }
    }
    sel.addEventListener('change', render);
    render();
})();
</script>
@endsection
