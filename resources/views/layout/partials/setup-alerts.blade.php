@if(!empty($setupAlerts) && count($setupAlerts) > 0)
<div class="setup-alerts-inner" id="setup-alerts-wrap">
    <div class="container-fluid px-3 pt-2 pb-0">
        <div class="alert alert-warning border-0 shadow-sm mb-2 setup-config-alert-summary" role="alert">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa fa-exclamation-triangle text-warning"></i>
                    <strong>{{ __('configuration.setup_alerts_summary', ['count' => count($setupAlerts)]) }}</strong>
                </div>
                <button type="button"
                        class="btn btn-sm btn-outline-warning"
                        data-bs-toggle="collapse"
                        data-bs-target="#setup-alerts-list"
                        aria-expanded="false"
                        aria-controls="setup-alerts-list">
                    {{ __('configuration.setup_alerts_show') }}
                </button>
            </div>
            <div class="collapse mt-2" id="setup-alerts-list">
                @foreach($setupAlerts as $alert)
                    <div class="setup-config-alert d-flex align-items-start gap-2 py-2 border-top border-warning border-opacity-25"
                         data-alert-key="{{ $alert['key'] }}">
                        <div class="flex-grow-1">
                            <span>{{ $alert['message'] }}</span>
                            @if(!empty($alert['url']))
                                <a href="{{ $alert['url'] }}" class="alert-link ms-1 fw-bold">{{ __('configuration.setup_alert_fix_now') }}</a>
                            @endif
                        </div>
                        <button type="button"
                                class="btn-close btn-close-sm setup-alert-dismiss flex-shrink-0"
                                data-alert-key="{{ $alert['key'] }}"
                                aria-label="{{ __('configuration.dismiss') }}"></button>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif
