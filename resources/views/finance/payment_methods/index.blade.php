@extends('layout.layout')

@section('styles')
<style>
    .method-row.disabled-row { opacity: 0.55; }
    .method-badge { font-size: 11px; }
</style>
@endsection

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0 mb-4 p-4 bg-white rounded shadow-sm">
            <div class="col-sm-8 p-0">
                <h4>{{ __('payment_methods.page_title') }}</h4>
                <p class="mb-0 text-muted">{{ __('payment_methods.subtitle') }}</p>
            </div>
        </div>

        <form id="paymentMethodsForm">
            @csrf
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">{{ __('payment_methods.online_payments') }}</h5>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch">
                        <input type="hidden" name="online_payments_enabled" value="0">
                        <input class="form-check-input" type="checkbox" name="online_payments_enabled" id="onlinePaymentsEnabled" value="1"
                            {{ !empty($config['online_payments_enabled']) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="onlinePaymentsEnabled">{{ __('payment_methods.enable_online') }}</label>
                    </div>
                    <small class="text-muted d-block mt-2">{{ __('payment_methods.online_payments_help') }}</small>
                    <div class="mt-3 p-3 bg-light rounded">
                        <div class="small text-muted mb-1">{{ __('online_pay.lookup_alt') }}</div>
                        <code>{{ url('/pay') }}</code>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">{{ __('payment_gateway.settings_title') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">{{ __('payment_gateway.provider') }}</label>
                            <select name="provider" class="form-control" id="gatewayProvider">
                                <option value="none" {{ ($gatewayConfig['provider'] ?? 'none') === 'none' ? 'selected' : '' }}>{{ __('payment_gateway.provider_none') }}</option>
                                @foreach($gatewayProviders as $key => $meta)
                                    @if($key !== 'none' && isset($meta['label']))
                                    <option value="{{ $key }}" {{ ($gatewayConfig['provider'] ?? '') === $key ? 'selected' : '' }}>{{ $meta['label'] }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <small class="text-muted">{{ __('payment_gateway.provider_help') }}</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">{{ __('payment_gateway.environment') }}</label>
                            <select name="environment" class="form-control">
                                <option value="sandbox" {{ ($gatewayConfig['environment'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                                <option value="production" {{ ($gatewayConfig['environment'] ?? '') === 'production' ? 'selected' : '' }}>Production</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input type="hidden" name="manual_proof_enabled" value="0">
                                <input class="form-check-input" type="checkbox" name="manual_proof_enabled" value="1"
                                    {{ !empty($gatewayConfig['manual_proof_enabled']) ? 'checked' : '' }}>
                                <label class="form-check-label">{{ __('payment_gateway.manual_proof_enabled') }}</label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mt-2 gateway-creds" data-gateway="pawapay">
                        <div class="col-12"><strong>PawaPay</strong> <span class="text-muted small">— {{ $gatewayProviders['pawapay']['description'] ?? '' }}</span></div>
                        <div class="col-md-6">
                            <input type="password" class="form-control" name="credentials[pawapay][api_token]" placeholder="API Bearer Token"
                                value="{{ $gatewayConfig['credentials']['pawapay']['api_token'] ?? '' }}">
                        </div>
                    </div>
                    <div class="row g-3 mt-2 gateway-creds" data-gateway="cinetpay">
                        <div class="col-12"><strong>CinetPay</strong> <span class="text-muted small">— {{ $gatewayProviders['cinetpay']['description'] ?? '' }}</span></div>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="credentials[cinetpay][api_key]" placeholder="API Key"
                                value="{{ $gatewayConfig['credentials']['cinetpay']['api_key'] ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="credentials[cinetpay][site_id]" placeholder="Site ID"
                                value="{{ $gatewayConfig['credentials']['cinetpay']['site_id'] ?? '' }}">
                        </div>
                    </div>
                    <div class="row g-3 mt-2 gateway-creds" data-gateway="flutterwave">
                        <div class="col-12"><strong>Flutterwave</strong> <span class="text-muted small">— {{ $gatewayProviders['flutterwave']['description'] ?? '' }}</span></div>
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="credentials[flutterwave][public_key]" placeholder="Public Key"
                                value="{{ $gatewayConfig['credentials']['flutterwave']['public_key'] ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <input type="password" class="form-control" name="credentials[flutterwave][secret_key]" placeholder="Secret Key"
                                value="{{ $gatewayConfig['credentials']['flutterwave']['secret_key'] ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="credentials[flutterwave][secret_hash]" placeholder="Webhook Secret Hash"
                                value="{{ $gatewayConfig['credentials']['flutterwave']['secret_hash'] ?? '' }}">
                        </div>
                    </div>
                    <div class="mt-3 small text-muted">
                        {{ __('payment_gateway.webhook_urls') }}:
                        <div><code>{{ route('webhooks.payments.pawapay') }}</code></div>
                        <div><code>{{ route('webhooks.payments.cinetpay') }}</code></div>
                        <div><code>{{ route('webhooks.payments.flutterwave') }}</code></div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('payment_methods.method') }}</h5>
                    <button type="submit" class="btn btn-primary btn-sm" id="saveBtn">
                        <i class="fa fa-save me-1"></i> {{ __('payment_methods.save') }}
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>{{ __('payment_methods.method') }}</th>
                                    <th class="text-center">{{ __('payment_methods.enabled') }}</th>
                                    <th>{{ __('payment_methods.merchant_code') }}</th>
                                    <th>{{ __('payment_methods.instructions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($definitions as $key => $def)
                                    @php $row = $config['methods'][$key] ?? []; @endphp
                                    <tr class="method-row" data-method="{{ $key }}">
                                        <td class="align-middle">
                                            <strong>{{ __('payment.' . $key) }}</strong>
                                            <span class="badge badge-light method-badge ms-1">
                                                {{ ($def['mobile'] ?? false) ? __('payment_methods.mobile_money') : __('payment_methods.standard') }}
                                            </span>
                                            @if($key === 'bank_transfer')
                                                <div class="mt-2 row g-2">
                                                    <div class="col-md-4">
                                                        <input type="text" class="form-control form-control-sm" name="methods[{{ $key }}][bank_name]"
                                                            value="{{ $row['bank_name'] ?? '' }}" placeholder="{{ __('payment_methods.bank_name') }}">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <input type="text" class="form-control form-control-sm" name="methods[{{ $key }}][account_name]"
                                                            value="{{ $row['account_name'] ?? '' }}" placeholder="{{ __('payment_methods.account_name') }}">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <input type="text" class="form-control form-control-sm" name="methods[{{ $key }}][account_number]"
                                                            value="{{ $row['account_number'] ?? '' }}" placeholder="{{ __('payment_methods.account_number') }}">
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-center align-middle">
                                            <input type="hidden" name="methods[{{ $key }}][enabled]" value="0">
                                            <input type="checkbox" class="form-check-input method-toggle" name="methods[{{ $key }}][enabled]" value="1"
                                                {{ !empty($row['enabled']) ? 'checked' : '' }}>
                                        </td>
                                        <td class="align-middle" style="min-width:160px">
                                            @if($def['mobile'] ?? false)
                                                <input type="text" class="form-control form-control-sm" name="methods[{{ $key }}][merchant_code]"
                                                    value="{{ $row['merchant_code'] ?? '' }}" placeholder="*144#...">
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td class="align-middle" style="min-width:220px">
                                            <textarea class="form-control form-control-sm" rows="2" name="methods[{{ $key }}][instructions]"
                                                placeholder="{{ __('payment_methods.instructions_placeholder') }}">{{ $row['instructions'] ?? '' }}</textarea>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function() {
    $('#paymentMethodsForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#saveBtn');
        btn.prop('disabled', true);

        fetch(@json(route('payment-methods.update')), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json',
            },
            body: new FormData(this),
        })
        .then(r => r.json().then(data => ({ ok: r.ok, data })))
        .then(({ ok, data }) => {
            btn.prop('disabled', false);
            if (ok) {
                if (typeof toastr !== 'undefined') toastr.success(data.message);
                else Swal.fire({ icon: 'success', title: data.message, timer: 2000, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'error', text: data.message || 'Error' });
            }
        })
        .catch(() => {
            btn.prop('disabled', false);
            Swal.fire({ icon: 'error', text: 'Request failed' });
        });
    });
});
</script>
@endsection
