@extends('layout.layout')

@section('styles')
<!-- Date Picker & Select Picker CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.14.0-beta2/css/bootstrap-select.min.css">
<!-- Time Picker CSS -->
<link rel="stylesheet" href="{{ asset('vendor/clockpicker/css/bootstrap-clockpicker.min.css') }}">

<style>
    /* Custom Toggle Switch Width */
    .form-switch .form-check-input {
        width: 3.5em !important;
        height: 1.75em !important;
        cursor: pointer;
    }
    
    /* Fix for "Half Modal" / Modal Width Issues */
    .modal-dialog {
        max-width: 90%; 
        margin: 1.75rem auto;
    }
    @media (min-width: 576px) {
        .modal-dialog {
            max-width: 600px; 
            margin: 1.75rem auto;
        }
    }
    @media (min-width: 992px) {
        .modal-dialog.modal-lg {
            max-width: 800px;
        }
    }
</style>
@endsection

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        {{-- Title --}}
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
            {{-- Sidebar Menu --}}
            <div class="col-xl-3 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="nav flex-column nav-pills mb-3">
                            <a href="#smtp" data-bs-toggle="pill" class="nav-link active show">
                                <i class="fa fa-envelope me-2"></i> {{ __('configuration.smtp') }}
                            </a>
                            <a href="#sms" data-bs-toggle="pill" class="nav-link">
                                <i class="fa fa-mobile me-2"></i> {{ __('configuration.sms_sender') }}
                            </a>
                            <a href="#school_year" data-bs-toggle="pill" class="nav-link">
                                <i class="fa fa-calendar me-2"></i> {{ __('configuration.school_year') }}
                            </a>
                            
                            @if(auth()->user()->hasRole('Super Admin'))
                                <a href="#modules" data-bs-toggle="pill" class="nav-link">
                                    <i class="fa fa-cubes me-2"></i> {{ __('configuration.modules') }}
                                </a>
                                <a href="#recharge" data-bs-toggle="pill" class="nav-link">
                                    <i class="fa fa-credit-card me-2"></i> {{ __('configuration.sms_recharge') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Content Area --}}
            <div class="col-xl-9 col-lg-8">
                <div class="tab-content">
                    
                    {{-- 1. SMTP --}}
                    <div id="smtp" class="tab-pane fade active show">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">{{ __('configuration.smtp') }}</h4>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('configuration.smtp.update') }}" method="POST" id="smtpForm">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">{{ __('configuration.mail_host') }}</label>
                                            <input type="text" name="mail_host" class="form-control" value="{{ $smtp['host'] }}" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">{{ __('configuration.mail_port') }}</label>
                                            <input type="text" name="mail_port" class="form-control" value="{{ $smtp['port'] }}" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">{{ __('configuration.mail_username') }}</label>
                                            <input type="text" name="mail_username" class="form-control" value="{{ $smtp['username'] }}">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">{{ __('configuration.mail_password') }}</label>
                                            <input type="password" name="mail_password" class="form-control" value="{{ $smtp['password'] }}">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">{{ __('configuration.mail_encryption') }}</label>
                                            <select name="mail_encryption" class="form-control default-select">
                                                <option value="tls" {{ $smtp['encryption'] == 'tls' ? 'selected' : '' }}>TLS</option>
                                                <option value="ssl" {{ $smtp['encryption'] == 'ssl' ? 'selected' : '' }}>SSL</option>
                                                <option value="null" {{ $smtp['encryption'] == 'null' ? 'selected' : '' }}>None</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">{{ __('configuration.mail_driver') }}</label>
                                            <input type="text" name="mail_driver" class="form-control" value="{{ $smtp['driver'] }}" readonly>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">{{ __('configuration.mail_from_address') }}</label>
                                            <input type="email" name="mail_from_address" class="form-control" value="{{ $smtp['from_address'] }}" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">{{ __('configuration.mail_from_name') }}</label>
                                            <input type="text" name="mail_from_name" class="form-control" value="{{ $smtp['from_name'] }}" required>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary submit-btn">{{ __('configuration.save_changes') }}</button>
                                </form>

                                <hr class="my-4">

                                {{-- Test Email --}}
                                <h5 class="text-primary mb-3">{{ __('configuration.test_email_connection') }}</h5>
                                <form action="{{ route('configuration.smtp.test') }}" method="POST" id="testEmailForm">
                                    @csrf
                                    <div class="input-group">
                                        <input type="email" name="test_email" class="form-control" placeholder="{{ __('configuration.enter_test_email') }}" required>
                                        <button type="submit" class="btn btn-outline-primary" id="testEmailBtn">
                                            <i class="fa fa-paper-plane me-2"></i> {{ __('configuration.send_test_email') }}
                                        </button>
                                    </div>
                                    <small class="text-muted">{{ __('configuration.test_email_help') }}</small>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- 2. SMS Sender --}}
                    <div id="sms" class="tab-pane fade">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">{{ __('configuration.sms_sender') }}</h4>
                            </div>
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
                                            <small class="text-muted">Max 11 characters.</small>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary submit-btn">{{ __('configuration.save_changes') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    {{-- 3. School Year & Timings --}}
                    <div id="school_year" class="tab-pane fade">
                        <div class="card">
                             <div class="card-header">
                                <h4 class="card-title">{{ __('configuration.school_year') }} & Timings</h4>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('configuration.year.update') }}" method="POST" id="yearForm">
                                    @csrf
                                    <h5 class="text-primary mb-3">Academic Session</h5>
                                    <div class="row mb-4">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">{{ __('configuration.academic_start_date') }}</label>
                                            <input type="text" name="academic_start_date" class="form-control datepicker" value="{{ $schoolYear['start_date'] }}" placeholder="YYYY-MM-DD" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">{{ __('configuration.academic_end_date') }}</label>
                                            <input type="text" name="academic_end_date" class="form-control datepicker" value="{{ $schoolYear['end_date'] }}" placeholder="YYYY-MM-DD" required>
                                        </div>
                                    </div>

                                    <h5 class="text-primary mb-3">School Operational Hours (For Auto-SMS)</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">School Start Time</label>
                                            <div class="input-group clockpicker">
                                                <input type="text" name="school_start_time" class="form-control" value="{{ $schoolYear['start_time'] }}" required>
                                                <span class="input-group-text"><i class="far fa-clock"></i></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">School End Time</label>
                                            <div class="input-group clockpicker">
                                                <input type="text" name="school_end_time" class="form-control" value="{{ $schoolYear['end_time'] }}" required>
                                                <span class="input-group-text"><i class="far fa-clock"></i></span>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary submit-btn">{{ __('configuration.save_changes') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- 4. Modules (UPDATED WITH BUTTONS) --}}
                    @if(auth()->user()->hasRole('Super Admin'))
                    <div id="modules" class="tab-pane fade">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title">{{ __('configuration.module_management') }}</h4>
                                {{-- Select/Deselect All Buttons --}}
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-2" id="selectAllModules">
                                        <i class="fa fa-check-square-o me-1"></i> {{ __('invoice.select_all') ?? 'Select All' }}
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllModules">
                                        <i class="fa fa-square-o me-1"></i> {{ __('invoice.deselect_all') ?? 'Deselect All' }}
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('configuration.modules.update') }}" method="POST" id="modulesForm">
                                    @csrf
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>{{ __('configuration.module_name') }}</th>
                                                    <th class="text-end">{{ __('configuration.status') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($allModules as $mod)
                                                <tr>
                                                    <td class="align-middle fw-bold">{{ $mod->name }}</td>
                                                    <td class="text-end">
                                                        <div class="form-check form-switch d-inline-block">
                                                            <input class="form-check-input module-switch" type="checkbox" 
                                                                   name="modules[]" 
                                                                   value="{{ $mod->slug }}" 
                                                                   {{ in_array($mod->slug, $enabledModules) ? 'checked' : '' }}>
                                                        </div>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-end mt-4">
                                        <button type="submit" class="btn btn-primary px-5 submit-btn">{{ __('configuration.save_changes') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    {{-- 5. Recharging --}}
                    <div id="recharge" class="tab-pane fade">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">{{ __('configuration.sms_recharge') }} / WhatsApp</h4>
                            </div>
                            <div class="card-body">
                                {{-- Stats Widgets --}}
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="widget-stat card bg-primary text-white mb-0">
                                            <div class="card-body">
                                                <div class="media">
                                                    <span class="me-3"><i class="fa fa-envelope"></i></span>
                                                    <div class="media-body text-white">
                                                        <p class="mb-1">{{ __('configuration.sms_purchased') }}</p>
                                                        <h3 class="text-white" id="smsBalance">{{ number_format($institution->sms_credits) }}</h3>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="widget-stat card bg-success text-white mb-0">
                                            <div class="card-body">
                                                <div class="media">
                                                    <span class="me-3"><i class="fa fa-whatsapp"></i></span>
                                                    <div class="media-body text-white">
                                                        <p class="mb-1">{{ __('configuration.whatsapp_purchased') }}</p>
                                                        <h3 class="text-white" id="waBalance">{{ number_format($institution->whatsapp_credits) }}</h3>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <h4 class="mb-3">{{ __('configuration.add_credits') }}</h4>
                                <form action="{{ route('configuration.recharge') }}" method="POST" id="rechargeForm">
                                    @csrf
                                    <div class="row">
                                        {{-- 1. Type Select with Dynamic Balance Display --}}
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Type</label>
                                            <select name="type" id="rechargeType" class="form-control default-select">
                                                <option value="sms">SMS</option>
                                                <option value="whatsapp">WhatsApp</option>
                                            </select>
                                            {{-- Dynamic Balance Info --}}
                                            <small class="text-muted mt-2 d-block">
                                                {{ __('configuration.balance') ?? 'Balance' }}: 
                                                <span id="currentBalanceDisplay" class="fw-bold text-primary">0</span>
                                            </small>
                                        </div>
                                        
                                        {{-- 2. Amount Input --}}
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">{{ __('configuration.enter_amount') }}</label>
                                            <input type="number" name="amount" class="form-control" min="1" required>
                                        </div>
                                        
                                        {{-- 3. Action Button (Fixed Alignment) --}}
                                        <div class="col-md-4 mb-3">
                                            {{-- Spacer Label ensures button aligns with inputs on all devices --}}
                                            <label class="form-label d-block">&nbsp;</label>
                                            <button type="submit" class="btn btn-success w-100 submit-btn">{{ __('configuration.recharge') }}</button>
                                        </div>
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
<!-- Datepicker & Select Picker JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.14.0-beta2/js/bootstrap-select.min.js"></script>
<!-- Clock Picker -->
<script src="{{ asset('vendor/clockpicker/js/bootstrap-clockpicker.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

{{-- Fix Z-Index & Button Visibility for SweetAlert --}}
<style>
    .swal2-container {
        z-index: 100000 !important;
    }
    .swal2-popup {
        font-size: 14px !important;
    }
    .swal2-actions {
        z-index: 1 !important;
    }
    .swal2-actions button {
        margin: 0 5px !important;
    }
    .swal2-confirm {
        background-color: #3085d6 !important;
        color: #fff !important;
        padding: 10px 24px !important;
        border-radius: 4px !important;
    }
    .swal2-cancel {
        background-color: #d33 !important;
        color: #fff !important;
        padding: 10px 24px !important;
        border-radius: 4px !important;
    }
</style>

<script>
    $(document).ready(function() {
        // Initialize Components
        if($.fn.selectpicker) {
            $('.default-select').selectpicker('refresh');
        }
        
        if ($.fn.datepicker) {
            $('.datepicker').datepicker({
                autoclose: true,
                format: 'yyyy-mm-dd',
                todayHighlight: true
            });
        }

        if ($.fn.clockpicker) {
            $('.clockpicker').clockpicker({
                donetext: 'Done',
                placement: 'bottom',
                align: 'left',
                autoclose: true
            });
        }

        // --- NEW: Module Select/Deselect All ---
        $('#selectAllModules').click(function() {
            $('.module-switch').prop('checked', true);
        });

        $('#deselectAllModules').click(function() {
            $('.module-switch').prop('checked', false);
        });

        // --- Dynamic Balance Display Logic ---
        function updateRechargeBalance() {
            let type = $('#rechargeType').val();
            let balance = 0;
            
            if (type === 'sms') {
                balance = $('#smsBalance').text();
            } else {
                balance = $('#waBalance').text();
            }
            
            $('#currentBalanceDisplay').text(balance);
        }

        $('#rechargeType').change(updateRechargeBalance);
        updateRechargeBalance();


        // --- Generic AJAX Form Handler ---
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
                        }).then((result) => {
                            location.reload(); 
                        });

                        if(formSelector === '#rechargeForm' && response.new_balance !== undefined) {
                             if(response.type === 'sms') {
                                $('#smsBalance').text(new Intl.NumberFormat().format(response.new_balance));
                            } else {
                                $('#waBalance').text(new Intl.NumberFormat().format(response.new_balance));
                            }
                            updateRechargeBalance();
                            form[0].reset();
                            if($.fn.selectpicker) $('.default-select').selectpicker('refresh');
                        }
                    },
                    error: function(xhr) {
                        btn.prop('disabled', false).html(originalText);
                        let msg = '{{ __('configuration.something_went_wrong') }}';
                        
                        if(xhr.responseJSON) {
                            if(xhr.responseJSON.message) msg = xhr.responseJSON.message;
                            else if (xhr.responseJSON.errors) {
                                msg = Object.values(xhr.responseJSON.errors)[0][0];
                            }
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __('configuration.error') }}',
                            text: msg,
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#d33'
                        });
                    }
                });
            });
        }

        handleAjaxForm('#smtpForm');
        handleAjaxForm('#smsForm');
        handleAjaxForm('#yearForm');
        handleAjaxForm('#modulesForm');
        handleAjaxForm('#rechargeForm');

        // --- Specific Handler for Test Email ---
        $('#testEmailForm').submit(function(e) {
            e.preventDefault(); 
            let btn = $('#testEmailBtn');
            let originalText = btn.html();
            
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-2"></i> Sending...');

            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                data: $(this).serialize(),
                success: function(response) {
                    btn.prop('disabled', false).html(originalText);
                    Swal.fire({ 
                        icon: 'success', 
                        title: 'Success', 
                        text: response.message,
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#3085d6'
                    });
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html(originalText);
                    let msg = xhr.responseJSON ? (xhr.responseJSON.message || xhr.responseJSON.error) : 'Failed to send email.';
                    Swal.fire({ 
                        icon: 'error', 
                        title: 'Error', 
                        text: msg,
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#d33'
                    });
                }
            });
        });
    });
</script>
@endsection