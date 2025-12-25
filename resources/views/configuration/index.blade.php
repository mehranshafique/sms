@extends('layout.layout')

@section('styles')
<style>
    /* Custom Toggle Switch Width */
    .form-switch .form-check-input {
        width: 3.5em !important; /* Increased width */
        height: 1.75em !important;
        cursor: pointer;
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
                                <form action="{{ route('configuration.smtp.update') }}" method="POST">
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
                                    <button type="submit" class="btn btn-primary">{{ __('configuration.save_changes') }}</button>
                                </form>

                                <hr class="my-4">

                                {{-- Test Email Section --}}
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
                                <form action="{{ route('configuration.sms.update') }}" method="POST">
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
                                    <button type="submit" class="btn btn-primary">{{ __('configuration.save_changes') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    {{-- 3. School Year --}}
                    <div id="school_year" class="tab-pane fade">
                        <div class="card">
                             <div class="card-header">
                                <h4 class="card-title">{{ __('configuration.school_year') }}</h4>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('configuration.year.update') }}" method="POST">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Academic Start Month</label>
                                            <select name="academic_start_month" class="form-control default-select">
                                                @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $m)
                                                    <option value="{{ $m }}" {{ $schoolYear['start_month'] == $m ? 'selected' : '' }}>{{ $m }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Academic End Month</label>
                                            <select name="academic_end_month" class="form-control default-select">
                                                @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $m)
                                                    <option value="{{ $m }}" {{ $schoolYear['end_month'] == $m ? 'selected' : '' }}>{{ $m }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">{{ __('configuration.save_changes') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- 4. Modules --}}
                    @if(auth()->user()->hasRole('Super Admin'))
                    <div id="modules" class="tab-pane fade">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">{{ __('configuration.module_management') }}</h4>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('configuration.modules.update') }}" method="POST">
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
                                                {{-- Dynamic Modules from Database (using $allModules as per new Controller logic) --}}
                                                @foreach($allModules as $mod)
                                                <tr>
                                                    {{-- Display correct DB Name (e.g. "Academic Sessions") --}}
                                                    <td class="align-middle fw-bold">{{ $mod->name }}</td>
                                                    <td class="text-end">
                                                        <div class="form-check form-switch d-inline-block">
                                                            {{-- Use slug for value matching with enabled_modules array --}}
                                                            <input class="form-check-input" type="checkbox" 
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
                                        <button type="submit" class="btn btn-primary px-5">{{ __('configuration.save_changes') }}</button>
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
                                                        <h3 class="text-white">{{ number_format($institution->sms_credits) }}</h3>
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
                                                        <h3 class="text-white">{{ number_format($institution->whatsapp_credits) }}</h3>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <h4 class="mb-3">{{ __('configuration.add_credits') }}</h4>
                                <form action="{{ route('configuration.recharge') }}" method="POST">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Type</label>
                                            <select name="type" class="form-control default-select">
                                                <option value="sms">SMS</option>
                                                <option value="whatsapp">WhatsApp</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">{{ __('configuration.enter_amount') }}</label>
                                            <input type="number" name="amount" class="form-control" min="1" required>
                                        </div>
                                        <div class="col-md-4 mb-3 d-flex align-items-end">
                                            <button type="submit" class="btn btn-success w-100">{{ __('configuration.recharge') }}</button>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        if($.fn.selectpicker) {
            $('.default-select').selectpicker('refresh');
        }

        // AJAX Handler for Test Email
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
                        text: response.message || 'Email sent successfully!'
                    });
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html(originalText);
                    let msg = 'Failed to send email.';
                    if(xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.error) {
                        msg = xhr.responseJSON.error;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: msg
                    });
                }
            });
        });
    });
</script>
@endsection