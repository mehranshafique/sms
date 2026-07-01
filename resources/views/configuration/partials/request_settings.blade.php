@php
    $requestNotify = ($settings['request_notify_parent_on_submit'] ?? '1') === '1';
    $whatsappOnly = ($settings['request_submit_whatsapp_only'] ?? '0') === '1';
    $responseHours = (int) ($settings['request_response_hours'] ?? 24);
    $blockAttendance = ($settings['block_attendance_on_expired_derogation'] ?? '0') === '1';
    $blockResults = ($settings['block_results_on_expired_derogation'] ?? '0') === '1';
@endphp
<style>
    .req-settings-card .req-setting-row {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        min-height: 2.25rem;
    }
    .req-settings-card .req-setting-switch {
        padding-left: 0;
        margin-bottom: 0;
        flex-shrink: 0;
    }
    .req-settings-card .req-setting-switch .form-check-input {
        margin-left: 0 !important;
        float: none;
    }
    .req-settings-card .req-setting-label {
        font-weight: 600;
        line-height: 1.45;
        margin-bottom: 0;
        padding-top: 0.1rem;
        cursor: pointer;
    }
</style>
<div class="card mb-4 border-0 shadow-sm req-settings-card">
    <div class="card-header d-flex justify-content-between align-items-center bg-white border-0 pt-4 px-4">
        <div>
            <h4 class="card-title text-primary fw-bold mb-0">{{ __('configuration.request_settings_title') }}</h4>
            <p class="text-muted small mb-0 mt-1">{{ __('configuration.request_settings_help') }}</p>
        </div>
    </div>
    <div class="card-body px-4 pb-4">
        <form action="{{ route('configuration.requests.update') }}" method="POST" id="requestSettingsForm">
            @csrf
            <div class="row g-3 align-items-start">
                <div class="col-lg-6">
                    <div class="req-setting-row">
                        <div class="form-check form-switch req-setting-switch">
                            <input class="form-check-input" type="checkbox" name="request_notify_parent_on_submit" value="1" id="reqNotifyParent" {{ $requestNotify ? 'checked' : '' }}>
                        </div>
                        <label class="req-setting-label" for="reqNotifyParent">{{ __('configuration.request_notify_parent_on_submit') }}</label>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="req-setting-row">
                        <div class="form-check form-switch req-setting-switch">
                            <input class="form-check-input" type="checkbox" name="request_submit_whatsapp_only" value="1" id="reqWaOnly" {{ $whatsappOnly ? 'checked' : '' }}>
                        </div>
                        <label class="req-setting-label" for="reqWaOnly">{{ __('configuration.request_submit_whatsapp_only') }}</label>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <label class="form-label fw-bold mb-2" for="requestResponseHours">{{ __('configuration.request_response_hours') }}</label>
                    <input type="number" name="request_response_hours" id="requestResponseHours" class="form-control" min="1" value="{{ $responseHours }}">
                </div>

                <div class="col-lg-4 col-md-6">
                    <label class="form-label fw-bold mb-2 d-block opacity-0 user-select-none" aria-hidden="true">&nbsp;</label>
                    <div class="req-setting-row">
                        <div class="form-check form-switch req-setting-switch">
                            <input class="form-check-input" type="checkbox" name="block_attendance_on_expired_derogation" value="1" id="blockAttendance" {{ $blockAttendance ? 'checked' : '' }}>
                        </div>
                        <label class="req-setting-label" for="blockAttendance">{{ __('configuration.block_attendance_expired') }}</label>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <label class="form-label fw-bold mb-2 d-block opacity-0 user-select-none" aria-hidden="true">&nbsp;</label>
                    <div class="req-setting-row">
                        <div class="form-check form-switch req-setting-switch">
                            <input class="form-check-input" type="checkbox" name="block_results_on_expired_derogation" value="1" id="blockResults" {{ $blockResults ? 'checked' : '' }}>
                        </div>
                        <label class="req-setting-label" for="blockResults">{{ __('configuration.block_results_expired') }}</label>
                    </div>
                </div>
            </div>
            <div class="text-end border-top pt-3 mt-4">
                <button type="submit" class="btn btn-primary shadow-sm submit-btn"><i class="fa fa-save me-1"></i> {{ __('configuration.save_changes') }}</button>
            </div>
        </form>
    </div>
</div>
