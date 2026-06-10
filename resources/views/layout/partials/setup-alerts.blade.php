@if(!empty($setupAlerts) && count($setupAlerts) > 0)
<div class="container-fluid px-3 pt-3" id="setup-alerts-wrap">
    @foreach($setupAlerts as $alert)
        <div class="alert alert-warning alert-dismissible fade show setup-config-alert shadow-sm border-0 mb-2"
             role="alert"
             data-alert-key="{{ $alert['key'] }}">
            <div class="d-flex align-items-start gap-3">
                <div class="flex-shrink-0 mt-1">
                    <i class="fa fa-exclamation-triangle fa-lg text-warning"></i>
                </div>
                <div class="flex-grow-1">
                    <strong class="d-block mb-1">{{ __('configuration.setup_alert_title') }}</strong>
                    <span>{{ $alert['message'] }}</span>
                    @if(!empty($alert['url']))
                        <a href="{{ $alert['url'] }}" class="alert-link ms-1 fw-bold">{{ __('configuration.setup_alert_fix_now') }}</a>
                    @endif
                </div>
                <button type="button"
                        class="btn-close setup-alert-dismiss"
                        data-alert-key="{{ $alert['key'] }}"
                        aria-label="{{ __('configuration.dismiss') }}"></button>
            </div>
        </div>
    @endforeach
</div>
@endif
