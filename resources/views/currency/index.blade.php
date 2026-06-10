@extends('layout.layout')

@section('styles')
<style>
    .currency-preview-card {
        background: linear-gradient(135deg, #002b80 0%, #0047ab 100%);
        border-radius: 12px;
        color: #fff;
        padding: 28px;
    }
    .currency-preview-amount {
        font-size: 2.25rem;
        font-weight: 700;
        letter-spacing: -0.5px;
    }
    .currency-option {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .currency-option-flag {
        font-size: 1.25rem;
        line-height: 1;
    }
    .currency-meta {
        font-size: 12px;
        color: #888;
    }
    .position-toggle .btn-check:checked + .btn-outline-primary {
        background: var(--primary);
        color: #fff;
    }
</style>
@endsection

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0 mb-4 p-4 bg-white rounded shadow-sm">
            <div class="col-sm-8 p-0">
                <div class="welcome-text">
                    <h4>{{ __('currency.page_title') }}</h4>
                    <p class="mb-0 text-muted">{{ __('currency.subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-4 p-0 text-end">
                @if($isGlobal)
                    <span class="badge badge-primary">{{ __('configuration.global_mode') }}</span>
                @endif
            </div>
        </div>

        <div class="row">
            <div class="col-xl-7 col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">{{ __('currency.settings_title') }}</h4>
                    </div>
                    <div class="card-body">
                        <form id="currencyForm">
                            @csrf
                            <div class="mb-4">
                                <label class="form-label fw-bold">{{ __('currency.select_currency') }}</label>
                                <select name="currency_code" id="currencyCode" class="form-control default-select" required>
                                    @foreach($currencies as $code => $meta)
                                        <option value="{{ $code }}"
                                            data-symbol="{{ $meta['symbol'] }}"
                                            data-flag="{{ $meta['flag'] }}"
                                            data-name="{{ $meta['name'] }}"
                                            {{ $settings['code'] === $code ? 'selected' : '' }}>
                                            {{ $meta['flag'] }} {{ $code }} — {{ $meta['name'] }} ({{ $meta['symbol'] }})
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted d-block mt-1">{{ __('currency.select_help') }}</small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">{{ __('currency.custom_symbol') }}</label>
                                <input type="text" name="currency_symbol" id="currencySymbol" class="form-control"
                                       value="{{ $settings['symbol'] }}" maxlength="12"
                                       placeholder="{{ __('currency.custom_symbol_placeholder') }}">
                                <small class="text-muted d-block mt-1">{{ __('currency.custom_symbol_help') }}</small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold d-block">{{ __('currency.symbol_position') }}</label>
                                <div class="btn-group position-toggle w-100" role="group">
                                    <input type="radio" class="btn-check" name="currency_position" id="posBefore" value="before"
                                        {{ $settings['position'] === 'before' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-primary w-50" for="posBefore">{{ __('currency.before_amount') }}</label>

                                    <input type="radio" class="btn-check" name="currency_position" id="posAfter" value="after"
                                        {{ $settings['position'] === 'after' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-primary w-50" for="posAfter">{{ __('currency.after_amount') }}</label>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">{{ __('currency.decimal_places') }}</label>
                                <select name="currency_decimals" id="currencyDecimals" class="form-control default-select">
                                    @foreach([0, 1, 2, 3, 4] as $d)
                                        <option value="{{ $d }}" {{ (int)$settings['decimals'] === $d ? 'selected' : '' }}>{{ $d }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary px-4" id="saveCurrencyBtn">
                                    <i class="fa fa-save me-1"></i> {{ __('currency.save') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-5 col-lg-4">
                <div class="currency-preview-card mb-4">
                    <p class="mb-1 opacity-75 text-uppercase" style="font-size:11px; letter-spacing:1px;">{{ __('currency.live_preview') }}</p>
                    <div class="currency-preview-amount" id="previewAmount">$ 1,250.00</div>
                    <p class="mb-0 mt-2 opacity-75" id="previewMeta">USD — US Dollar</p>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3"><i class="fa fa-info-circle text-primary me-1"></i> {{ __('currency.info_title') }}</h6>
                        <ul class="text-muted small mb-0 ps-3">
                            <li class="mb-2">{{ __('currency.info_invoices') }}</li>
                            <li class="mb-2">{{ __('currency.info_dashboard') }}</li>
                            <li>{{ __('currency.info_per_school') }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('currencyForm');
    const codeSelect = document.getElementById('currencyCode');
    const symbolInput = document.getElementById('currencySymbol');
    const decimalsSelect = document.getElementById('currencyDecimals');
    const previewAmount = document.getElementById('previewAmount');
    const previewMeta = document.getElementById('previewMeta');
    const saveBtn = document.getElementById('saveCurrencyBtn');
    const sampleValue = 1250;

    function getPosition() {
        const checked = form.querySelector('input[name="currency_position"]:checked');
        return checked ? checked.value : 'before';
    }

    function formatPreview() {
        const decimals = parseInt(decimalsSelect.value, 10) || 2;
        const symbol = symbolInput.value.trim() || codeSelect.selectedOptions[0]?.dataset.symbol || '$';
        const formatted = sampleValue.toLocaleString(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
        const position = getPosition();
        previewAmount.textContent = position === 'after'
            ? formatted + ' ' + symbol
            : symbol + ' ' + formatted;

        const opt = codeSelect.selectedOptions[0];
        if (opt) {
            previewMeta.textContent = opt.value + ' — ' + (opt.dataset.name || '');
        }
    }

    codeSelect.addEventListener('change', function () {
        const opt = this.selectedOptions[0];
        if (opt) {
            symbolInput.value = opt.dataset.symbol || '';
        }
        formatPreview();
    });

    symbolInput.addEventListener('input', formatPreview);
    decimalsSelect.addEventListener('change', formatPreview);
    form.querySelectorAll('input[name="currency_position"]').forEach(el => {
        el.addEventListener('change', formatPreview);
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        saveBtn.disabled = true;

        fetch(@json(route('currency.update')), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: new FormData(form),
        })
        .then(r => r.json())
        .then(data => {
            if (typeof toastr !== 'undefined') {
                toastr.success(data.message || @json(__('currency.saved')));
            }
        })
        .catch(() => {
            if (typeof toastr !== 'undefined') {
                toastr.error(@json(__('currency.save_failed')));
            }
        })
        .finally(() => { saveBtn.disabled = false; });
    });

    formatPreview();
});
</script>
@endsection
