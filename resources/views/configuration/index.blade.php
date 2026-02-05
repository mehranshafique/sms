@extends('layout.layout')

@section('styles')
<!-- Date Picker & Select Picker CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.14.0-beta2/css/bootstrap-select.min.css">
<!-- Time Picker CSS -->
<link rel="stylesheet" href="{{ asset('vendor/clockpicker/css/bootstrap-clockpicker.min.css') }}">

<style>
    .form-switch .form-check-input {
        width: 3.5em !important;
        height: 1.75em !important;
        cursor: pointer;
    }
    .modal-dialog { max-width: 90%; margin: 1.75rem auto; }
    @media (min-width: 576px) { .modal-dialog { max-width: 600px; margin: 1.75rem auto; } }
    @media (min-width: 992px) { .modal-dialog.modal-lg { max-width: 800px; } }
</style>
@endsection

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        <div class="row page-titles mx-0 mb-4 p-4 bg-white rounded shadow-sm">
            <div class="col-sm-6 p-0">
                <div class="welcome-text">
                    <h4>{{ __('configuration.page_title') }}</h4>
                    <p class="mb-0 text-muted">{{ __('configuration.subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-0 text-end">
                <span class="badge badge-primary">{{ $institution->name }}</span>
            </div>
        </div>

        <div class="row">
            {{-- Sidebar --}}
            <div class="col-xl-3 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="nav flex-column nav-pills mb-3">
                            <a href="#smtp" data-bs-toggle="pill" class="nav-link active show"><i class="fa fa-envelope me-2"></i> {{ __('configuration.smtp') }}</a>
                            <a href="#sms" data-bs-toggle="pill" class="nav-link"><i class="fa fa-mobile me-2"></i> {{ __('configuration.sms_sender') }}</a>
                            <a href="#notifications" data-bs-toggle="pill" class="nav-link"><i class="fa fa-bell me-2"></i> {{ __('configuration.notification_settings') }}</a>
                            <a href="#test_msg" data-bs-toggle="pill" class="nav-link"><i class="fa fa-paper-plane me-2"></i> {{ __('configuration.test_notifications') }}</a>
                            <a href="#school_year" data-bs-toggle="pill" class="nav-link"><i class="fa fa-calendar me-2"></i> {{ __('configuration.school_year') }}</a>
                            
                            @if(auth()->user()->hasRole('Super Admin'))
                                <a href="#modules" data-bs-toggle="pill" class="nav-link"><i class="fa fa-cubes me-2"></i> {{ __('configuration.modules') }}</a>
                                <a href="#recharge" data-bs-toggle="pill" class="nav-link"><i class="fa fa-credit-card me-2"></i> {{ __('configuration.sms_recharge') }}</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-9 col-lg-8">
                <div class="tab-content">
                    
                    {{-- 1. SMTP --}}
                    <div id="smtp" class="tab-pane fade active show">
                        {{-- (Content same as previous) --}}
                         <div class="card">
                            <div class="card-header"><h4 class="card-title">{{ __('configuration.smtp') }}</h4></div>
                            <div class="card-body">
                                <form action="{{ route('configuration.smtp.update') }}" method="POST" id="smtpForm">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-6 mb-3"><label class="form-label">{{ __('configuration.mail_host') }}</label><input type="text" name="mail_host" class="form-control" value="{{ $smtp['host'] }}" required></div>
                                        <div class="col-md-6 mb-3"><label class="form-label">{{ __('configuration.mail_port') }}</label><input type="text" name="mail_port" class="form-control" value="{{ $smtp['port'] }}" required></div>
                                        <div class="col-md-6 mb-3"><label class="form-label">{{ __('configuration.mail_username') }}</label><input type="text" name="mail_username" class="form-control" value="{{ $smtp['username'] }}"></div>
                                        <div class="col-md-6 mb-3"><label class="form-label">{{ __('configuration.mail_password') }}</label><input type="password" name="mail_password" class="form-control" value="{{ $smtp['password'] }}"></div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">{{ __('configuration.mail_encryption') }}</label>
                                            <select name="mail_encryption" class="form-control default-select">
                                                <option value="tls" {{ $smtp['encryption'] == 'tls' ? 'selected' : '' }}>TLS</option>
                                                <option value="ssl" {{ $smtp['encryption'] == 'ssl' ? 'selected' : '' }}>SSL</option>
                                                <option value="null" {{ $smtp['encryption'] == 'null' ? 'selected' : '' }}>None</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3"><label class="form-label">{{ __('configuration.mail_driver') }}</label><input type="text" name="mail_driver" class="form-control" value="{{ $smtp['driver'] }}" readonly></div>
                                        <div class="col-md-6 mb-3"><label class="form-label">{{ __('configuration.mail_from_address') }}</label><input type="email" name="mail_from_address" class="form-control" value="{{ $smtp['from_address'] }}" required></div>
                                        <div class="col-md-6 mb-3"><label class="form-label">{{ __('configuration.mail_from_name') }}</label><input type="text" name="mail_from_name" class="form-control" value="{{ $smtp['from_name'] }}" required></div>
                                    </div>
                                    <button type="submit" class="btn btn-primary submit-btn">{{ __('configuration.save_changes') }}</button>
                                </form>
                                <hr class="my-4">
                                <h5 class="text-primary mb-3">{{ __('configuration.test_email_connection') }}</h5>
                                <form action="{{ route('configuration.smtp.test') }}" method="POST" id="testEmailForm">
                                    @csrf
                                    <div class="input-group">
                                        <input type="email" name="test_email" class="form-control" placeholder="{{ __('configuration.enter_test_email') }}" required>
                                        <button type="submit" class="btn btn-outline-primary" id="testEmailBtn"><i class="fa fa-paper-plane me-2"></i> {{ __('configuration.send_test_email') }}</button>
                                    </div>
                                    <small class="text-muted">{{ __('configuration.test_email_help') }}</small>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- 2. SMS --}}
                    <div id="sms" class="tab-pane fade">
                        {{-- (Content same as previous) --}}
                         <div class="card">
                            <div class="card-header"><h4 class="card-title">{{ __('configuration.sms_sender') }}</h4></div>
                            <div class="card-body">
                                <form action="{{ route('configuration.sms.update') }}" method="POST" id="smsForm">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">{{ __('configuration.provider') }}</label>
                                            <select name="sms_provider" class="form-control default-select">
                                                <option value="mobishastra" {{ $sms['provider'] == 'mobishastra' ? 'selected' : '' }}>Mobishastra</option>
                                                <option value="infobip" {{ $sms['provider'] == 'infobip' ? 'selected' : '' }}>Infobip</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">{{ __('configuration.sender_id') }}</label>
                                            <input type="text" name="sms_sender_id" class="form-control" value="{{ $sms['sender_id'] }}" placeholder="{{ __('configuration.sender_id_placeholder') }}" required maxlength="11">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary submit-btn">{{ __('configuration.save_changes') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- NEW: Notification Settings --}}
                    <div id="notifications" class="tab-pane fade">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">{{ __('configuration.notification_preferences') }}</h4>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('configuration.notifications.update') }}" method="POST" id="notificationsForm">
                                    @csrf
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>{{ __('configuration.event_name') }}</th>
                                                    <th class="text-center">{{ __('configuration.email_channel') }}</th>
                                                    <th class="text-center">{{ __('configuration.sms_channel') }}</th>
                                                    <th class="text-center">{{ __('configuration.whatsapp_channel') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $events = [
                                                        'student_created' => __('configuration.student_created'),
                                                        'staff_created' => __('configuration.staff_created'),
                                                        'payment_received' => __('configuration.payment_received'),
                                                        'institution_created' => __('configuration.institution_created'),
                                                    ];
                                                @endphp
                                                @foreach($events as $key => $label)
                                                <tr>
                                                    <td><strong>{{ $label }}</strong></td>
                                                    <td class="text-center">
                                                        <div class="form-check form-switch d-inline-block">
                                                            {{-- Hidden input sends '0' if checkbox is unchecked --}}
                                                            <input type="hidden" name="preferences[{{ $key }}][email]" value="0">
                                                            <input class="form-check-input" type="checkbox" name="preferences[{{ $key }}][email]" value="1" 
                                                                {{ ($notificationPrefs[$key]['email'] ?? false) ? 'checked' : '' }}>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="form-check form-switch d-inline-block">
                                                            <input type="hidden" name="preferences[{{ $key }}][sms]" value="0">
                                                            <input class="form-check-input" type="checkbox" name="preferences[{{ $key }}][sms]" value="1" 
                                                                {{ ($notificationPrefs[$key]['sms'] ?? false) ? 'checked' : '' }}>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="form-check form-switch d-inline-block">
                                                            <input type="hidden" name="preferences[{{ $key }}][whatsapp]" value="0">
                                                            <input class="form-check-input" type="checkbox" name="preferences[{{ $key }}][whatsapp]" value="1" 
                                                                {{ ($notificationPrefs[$key]['whatsapp'] ?? false) ? 'checked' : '' }}>
                                                        </div>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <button type="submit" class="btn btn-primary mt-3 submit-btn">{{ __('configuration.save_changes') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- Test Notifications --}}
                    <div id="test_msg" class="tab-pane fade">
                        <div class="card">
                            <div class="card-header"><h4 class="card-title">{{ __('configuration.test_notifications') }}</h4></div>
                            <div class="card-body">
                                <div class="alert alert-info"><i class="fa fa-info-circle me-2"></i> {{ __('configuration.api_credentials_warning') }}</div>
                                <div class="row">
                                    <div class="col-md-6 border-end">
                                        <h5 class="text-primary mb-3"><i class="fa fa-mobile me-2"></i> {{ __('configuration.test_sms_title') }}</h5>
                                        <form class="testMsgForm">
                                            @csrf
                                            <input type="hidden" name="channel" value="sms">
                                            <div class="mb-3">
                                                <label class="form-label">{{ __('configuration.phone_number') }}</label>
                                                <input type="text" name="phone" class="form-control" placeholder="{{ __('configuration.phone_placeholder') }}" required>
                                            </div>
                                            <p class="text-muted small">{{ __('configuration.current_provider') }}: <strong>{{ ucfirst($sms['provider']) }}</strong></p>
                                            <button type="submit" class="btn btn-outline-primary btn-sm test-btn"><i class="fa fa-paper-plane me-1"></i> {{ __('configuration.send_test_sms') }}</button>
                                        </form>
                                    </div>
                                    <div class="col-md-6">
                                        <h5 class="text-success mb-3"><i class="fa fa-whatsapp me-2"></i> {{ __('configuration.test_whatsapp_title') }}</h5>
                                        <form class="testMsgForm">
                                            @csrf
                                            <input type="hidden" name="channel" value="whatsapp">
                                            <div class="mb-3">
                                                <label class="form-label">{{ __('configuration.phone_number') }}</label>
                                                <input type="text" name="phone" class="form-control" placeholder="{{ __('configuration.phone_placeholder') }}" required>
                                            </div>
                                            <p class="text-muted small">{{ __('configuration.whatsapp_provider') }}</p>
                                            <button type="submit" class="btn btn-outline-success btn-sm test-btn"><i class="fa fa-paper-plane me-1"></i> {{ __('configuration.send_test_whatsapp') }}</button>
                                        </form>
                                    </div>
                                </div>
                                <div class="mt-4 pt-3 border-top" id="testResultArea" style="display:none;"><h6 class="fw-bold">{{ __('configuration.status') }}:</h6><div id="testResultContent"></div></div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- 3. School Year --}}
                    <div id="school_year" class="tab-pane fade">
                        <div class="card">
                             <div class="card-header"><h4 class="card-title">{{ __('configuration.school_year') }} & Timings</h4></div>
                            <div class="card-body">
                                <form action="{{ route('configuration.year.update') }}" method="POST" id="yearForm">
                                    @csrf
                                    <h5 class="text-primary mb-3">{{ __('configuration.academic_session') }}</h5>
                                    <div class="row mb-4">
                                        <div class="col-md-6 mb-3"><label class="form-label">{{ __('configuration.academic_start_date') }}</label><input type="text" name="academic_start_date" class="form-control datepicker" value="{{ $schoolYear['start_date'] }}" required></div>
                                        <div class="col-md-6 mb-3"><label class="form-label">{{ __('configuration.academic_end_date') }}</label><input type="text" name="academic_end_date" class="form-control datepicker" value="{{ $schoolYear['end_date'] }}" required></div>
                                    </div>
                                    <h5 class="text-primary mb-3">{{ __('configuration.school_hours') }}</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3"><label class="form-label">{{ __('configuration.school_start_time') }}</label><div class="input-group clockpicker"><input type="text" name="school_start_time" class="form-control" value="{{ $schoolYear['start_time'] }}" required><span class="input-group-text"><i class="far fa-clock"></i></span></div></div>
                                        <div class="col-md-6 mb-3"><label class="form-label">{{ __('configuration.school_end_time') }}</label><div class="input-group clockpicker"><input type="text" name="school_end_time" class="form-control" value="{{ $schoolYear['end_time'] }}" required><span class="input-group-text"><i class="far fa-clock"></i></span></div></div>
                                    </div>
                                    <button type="submit" class="btn btn-primary submit-btn">{{ __('configuration.save_changes') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- 4. Modules --}}
                    @if(auth()->user()->hasRole('Super Admin'))
                    <div id="modules" class="tab-pane fade">
                        {{-- (Content same as previous) --}}
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title">{{ __('configuration.module_management') }}</h4>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-2" id="selectAllModules"><i class="fa fa-check-square-o me-1"></i> {{ __('invoice.select_all') ?? 'Select All' }}</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllModules"><i class="fa fa-square-o me-1"></i> {{ __('invoice.deselect_all') ?? 'Deselect All' }}</button>
                                </div>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('configuration.modules.update') }}" method="POST" id="modulesForm">
                                    @csrf
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead class="bg-light"><tr><th>{{ __('configuration.module_name') }}</th><th class="text-end">{{ __('configuration.status') }}</th></tr></thead>
                                            <tbody>
                                                @foreach($allModules as $mod)
                                                <tr><td class="align-middle fw-bold">{{ $mod->name }}</td><td class="text-end"><div class="form-check form-switch d-inline-block"><input class="form-check-input module-switch" type="checkbox" name="modules[]" value="{{ $mod->slug }}" {{ in_array($mod->slug, $enabledModules) ? 'checked' : '' }}></div></td></tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-end mt-4"><button type="submit" class="btn btn-primary px-5 submit-btn">{{ __('configuration.save_changes') }}</button></div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    {{-- 5. Recharging --}}
                    <div id="recharge" class="tab-pane fade">
                        {{-- (Content same as previous) --}}
                        <div class="card">
                            <div class="card-header"><h4 class="card-title">{{ __('configuration.sms_recharge') }} / WhatsApp</h4></div>
                            <div class="card-body">
                                <div class="row mb-4">
                                    <div class="col-md-6"><div class="widget-stat card bg-primary text-white mb-0"><div class="card-body p-3"><div class="media"><span class="me-3"><i class="fa fa-envelope"></i></span><div class="media-body text-white"><p class="mb-1">{{ __('configuration.sms_purchased') }}</p><h3 class="text-white" id="smsBalance">{{ number_format($institution->sms_credits) }}</h3></div></div></div></div></div>
                                    <div class="col-md-6"><div class="widget-stat card bg-success text-white mb-0"><div class="card-body p-3"><div class="media"><span class="me-3"><i class="fa fa-whatsapp"></i></span><div class="media-body text-white"><p class="mb-1">{{ __('configuration.whatsapp_purchased') }}</p><h3 class="text-white" id="waBalance">{{ number_format($institution->whatsapp_credits) }}</h3></div></div></div></div></div>
                                </div>
                                <h4 class="mb-3">{{ __('configuration.add_credits') }}</h4>
                                <form action="{{ route('configuration.recharge') }}" method="POST" id="rechargeForm">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-4 mb-3"><label class="form-label">{{ __('configuration.type') }}</label><select name="type" id="rechargeType" class="form-control default-select"><option value="sms">SMS</option><option value="whatsapp">WhatsApp</option></select><small class="text-muted mt-2 d-block">{{ __('configuration.balance') }}: <span id="currentBalanceDisplay" class="fw-bold text-primary">0</span></small></div>
                                        <div class="col-md-4 mb-3"><label class="form-label">{{ __('configuration.enter_amount') }}</label><input type="number" name="amount" class="form-control" min="1" required></div>
                                        <div class="col-md-4 mb-3"><label class="form-label d-block">&nbsp;</label><button type="submit" class="btn btn-success w-100 submit-btn">{{ __('configuration.recharge') }}</button></div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.14.0-beta2/js/bootstrap-select.min.js"></script>
<script src="{{ asset('vendor/clockpicker/js/bootstrap-clockpicker.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        if($.fn.selectpicker) { $('.default-select').selectpicker('refresh'); }
        if ($.fn.datepicker) { $('.datepicker').datepicker({ autoclose: true, format: 'yyyy-mm-dd', todayHighlight: true }); }
        if ($.fn.clockpicker) { $('.clockpicker').clockpicker({ donetext: 'Done', placement: 'bottom', align: 'left', autoclose: true }); }

        // Module Select/Deselect All
        $('#selectAllModules').click(function() { $('.module-switch').prop('checked', true); });
        $('#deselectAllModules').click(function() { $('.module-switch').prop('checked', false); });

        // Recharge Balance Logic
        function updateRechargeBalance() {
            let type = $('#rechargeType').val();
            let balance = (type === 'sms') ? $('#smsBalance').text() : $('#waBalance').text();
            $('#currentBalanceDisplay').text(balance);
        }
        $('#rechargeType').change(updateRechargeBalance);
        updateRechargeBalance();

        // Generic AJAX Form
        function handleAjaxForm(formSelector) {
            $(formSelector).submit(function(e) {
                e.preventDefault(); 
                let form = $(this);
                let btn = form.find('.submit-btn');
                let originalText = btn.html();
                
                btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-2"></i> {{ __('configuration.saving') }}');

                $.ajax({
                    url: form.attr('action'),
                    type: "POST",
                    data: form.serialize(),
                    success: function(response) {
                        btn.prop('disabled', false).html(originalText);
                        Swal.fire({
                            icon: 'success',
                            title: '{{ __('configuration.success') }}',
                            text: response.message,
                            showConfirmButton: true,
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#3085d6'
                        }).then((result) => { location.reload(); });
                        
                        if(formSelector === '#rechargeForm' && response.new_balance !== undefined) {
                            // Logic to update UI balance without reload if desired
                        }
                    },
                    error: function(xhr) {
                        btn.prop('disabled', false).html(originalText);
                        let msg = '{{ __('configuration.something_went_wrong') }}';
                        if(xhr.responseJSON) {
                            if(xhr.responseJSON.message) msg = xhr.responseJSON.message;
                            else if (xhr.responseJSON.errors) msg = Object.values(xhr.responseJSON.errors)[0][0];
                        }
                        Swal.fire({ icon: 'error', title: '{{ __('configuration.error') }}', text: msg, confirmButtonText: 'OK', confirmButtonColor: '#d33' });
                    }
                });
            });
        }

        handleAjaxForm('#smtpForm');
        handleAjaxForm('#smsForm');
        handleAjaxForm('#notificationsForm'); // New handler
        handleAjaxForm('#yearForm');
        handleAjaxForm('#modulesForm');
        handleAjaxForm('#rechargeForm');

        // Test Email
        $('#testEmailForm').submit(function(e) {
            e.preventDefault(); 
            let btn = $('#testEmailBtn');
            let originalText = btn.html();
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-2"></i> {{ __('configuration.sending') }}');
            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                data: $(this).serialize(),
                success: function(response) {
                    btn.prop('disabled', false).html(originalText);
                    Swal.fire({ icon: 'success', title: '{{ __('configuration.success') }}', text: response.message, confirmButtonText: 'OK', confirmButtonColor: '#3085d6' });
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html(originalText);
                    let msg = xhr.responseJSON ? (xhr.responseJSON.message || xhr.responseJSON.error) : '{{ __('configuration.failed_to_send') }}';
                    Swal.fire({ icon: 'error', title: '{{ __('configuration.error') }}', text: msg, confirmButtonText: 'OK', confirmButtonColor: '#d33' });
                }
            });
        });

        // Test SMS/WhatsApp
        $('.testMsgForm').submit(function(e) {
            e.preventDefault();
            let form = $(this);
            let btn = form.find('.test-btn');
            let originalText = btn.html();
            let resultArea = $('#testResultArea');
            let resultContent = $('#testResultContent');
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> {{ __('configuration.sending') }}');
            resultArea.hide();
            $.ajax({
                url: "{{ route('configuration.sms.test') }}",
                type: "POST",
                data: form.serialize(),
                success: function(response) {
                    btn.prop('disabled', false).html(originalText);
                    resultArea.show();
                    resultContent.html(`<div class="alert alert-success">${response.message}</div>`);
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html(originalText);
                    resultArea.show();
                    let msg = xhr.responseJSON ? xhr.responseJSON.message : '{{ __('configuration.unknown_error') }}';
                    resultContent.html(`<div class="alert alert-danger"><strong>{{ __('configuration.failed') }}:</strong> ${msg}<br><small>{{ __('configuration.check_logs') }}</small></div>`);
                }
            });
        });
    });
</script>
@endsection