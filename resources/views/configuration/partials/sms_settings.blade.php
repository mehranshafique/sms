@php
    if (!isset($isSuperAdmin)) {
        $isSuperAdmin = auth()->user()->hasRole(\App\Enums\RoleEnum::SUPER_ADMIN->value) && is_null($institutionId);
    }

    $isSuperAdminUser = auth()->user()->hasRole(\App\Enums\RoleEnum::SUPER_ADMIN->value);
    
    if ($isSuperAdmin) {
        $smsProvider = $settings['sms_provider'] ?? config('sms.default', 'mobishastra');
        $waProvider = $settings['whatsapp_provider'] ?? config('sms.whatsapp_default', 'meta');
    } else {
        $smsProvider = $settings['sms_provider'] ?? 'system';
        $waProvider = $settings['whatsapp_provider'] ?? 'system';
    }
    
    $allowedSms = $allowedSms ?? [];
    $allowedWa = $allowedWa ?? [];
@endphp

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title">{{ __('configuration.sms_whatsapp_setup') }}</h4>
        @if($isSuperAdmin)
            <span class="badge badge-primary">{{ __('configuration.global_mode') }}</span>
        @endif
    </div>
    <div class="card-body">
        @if($isSuperAdminUser && !$isSuperAdmin)
            <div class="alert alert-warning border-warning mb-4">
                <i class="fa fa-globe me-2"></i>
                <strong>{{ __('configuration.global_view_required_title') }}</strong>
                {{ __('configuration.global_view_required_body') }}
            </div>
        @endif

        <form action="{{ route('configuration.sms.update') }}" method="POST" id="smsSettingsForm">
            @csrf
            
            {{-- SECTION 1: SUPER ADMIN GLOBAL CONTROLS --}}
            @if($isSuperAdmin)
                <div class="alert alert-soft-primary border-primary border-opacity-25 mb-5 p-4 rounded-3">
                    <h5 class="text-primary fw-bold mb-3"><i class="fa fa-shield me-2"></i> {{ __('configuration.provider_control_title') }}</h5>
                    <p class="text-muted small mb-3">{{ __('configuration.system_default_env_override_help') }}</p>
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark border-bottom pb-1 d-block mb-2">{{ __('configuration.allowed_sms') }}</label>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach(['mobishastra', 'infobip', 'twilio', 'signalwire'] as $p)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input cursor-pointer" type="checkbox" name="allowed_sms[]" id="allow_sms_{{ $p }}" value="{{ $p }}" 
                                            {{ in_array($p, $allowedSms) ? 'checked' : '' }}>
                                        <label class="form-check-label text-capitalize cursor-pointer" for="allow_sms_{{ $p }}">{{ ucfirst($p) }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark border-bottom pb-1 d-block mb-2">{{ __('configuration.allowed_whatsapp') }}</label>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach(['meta', 'infobip', 'twilio'] as $p)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input cursor-pointer" type="checkbox" name="allowed_whatsapp[]" id="allow_wa_{{ $p }}" value="{{ $p }}" 
                                            {{ in_array($p, $allowedWa) ? 'checked' : '' }}>
                                        <label class="form-check-label text-capitalize cursor-pointer" for="allow_wa_{{ $p }}">{{ ucfirst($p) }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    
                </div>
            @endif
            @if(auth()->user()->hasRole('Super Admin'))
                    <div class="row">
                        <div class="col-md-12 mb-3 border-bottom pb-3">
                            <div class="form-check form-switch custom-switch">
                                <input type="hidden" name="chatbot_free_interactions" value="0">
                                <input class="form-check-input" type="checkbox" name="chatbot_free_interactions" id="chatbot_free_interactions" value="1" {{ (isset($billingSettings['chatbot_free_interactions']) && $billingSettings['chatbot_free_interactions'] == '1') ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold ms-2 mt-1 text-dark" style="cursor: pointer;" for="chatbot_free_interactions">{{ __('configuration.chatbot_free_interactions') ?? 'Enable Unlimited Free Chatbot/Messaging (Bypass Billing)' }}</label>
                            </div>
                            <small class="text-muted d-block mt-2 ms-5">{{ __('configuration.chatbot_free_help') ?? 'If enabled, this institution will not be charged SMS/WhatsApp credits for automated chatbot interactions, report deliveries, or hardware notifications.' }}</small>
                        </div>
                    </div>
                    @endif
            {{-- SECTION 2: ACTIVE PROVIDER SELECTION --}}
            <div class="row mb-4 p-3 bg-light rounded mx-0 border">
                <div class="col-md-6">
                    <label class="form-label fw-bold">
                        {{ $isSuperAdmin ? __('configuration.system_default_sms') : __('configuration.active_sms_provider') }}
                    </label>
                    <select name="sms_provider" id="smsProviderSelect" class="form-control default-select">
                        @if(!$isSuperAdmin)
                            <option value="system" {{ $smsProvider == 'system' ? 'selected' : '' }}>{{ __('configuration.system_default_option') }}</option>
                        @endif
                        @foreach(['mobishastra', 'infobip', 'twilio', 'signalwire'] as $p)
                            @if($isSuperAdmin || in_array($p, $allowedSms))
                                <option value="{{ $p }}" {{ $smsProvider == $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">
                        {{ $isSuperAdmin ? __('configuration.system_default_whatsapp') : __('configuration.active_whatsapp_provider') }}
                    </label>
                    <select name="whatsapp_provider" id="waProviderSelect" class="form-control default-select">
                        @if(!$isSuperAdmin)
                            <option value="system" {{ $waProvider == 'system' ? 'selected' : '' }}>{{ __('configuration.system_default_option') }}</option>
                        @endif
                        @foreach(['meta', 'infobip', 'twilio'] as $p)
                            @if($isSuperAdmin || in_array($p, $allowedWa))
                                <option value="{{ $p }}" {{ $waProvider == $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- SECTION 3: CREDENTIALS INPUT --}}
            <div id="credentialsSection">
                <h6 class="text-muted text-uppercase fs-12 font-w600 mb-3 border-bottom pb-2">{{ __('configuration.api_credentials') }}</h6>
                
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#mobishastra">Mobishastra</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#infobip">Infobip</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#meta">Meta</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#twilio">Twilio</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#signalwire">SignalWire</a></li>
                </ul>

                <div class="tab-content border p-3 rounded-bottom border-top-0">
                    
                    {{-- 1. MOBISHASTRA --}}
                    <div class="tab-pane fade show active" id="mobishastra">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Username (Profile ID)</label>
                                <input type="text" name="mobishastra_user" class="form-control" value="{{ $settings['mobishastra_user'] ?? '' }}" placeholder="e.g. 12345">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="mobishastra_password" class="form-control" placeholder="*************">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Sender ID</label>
                                <input type="text" name="mobishastra_sender_id" class="form-control" value="{{ $settings['mobishastra_sender_id'] ?? '' }}" placeholder="e.g. SCHOOL-A">
                            </div>
                        </div>
                    </div>

                    {{-- 2. INFOBIP --}}
                    <div class="tab-pane fade" id="infobip">
                        <div class="alert alert-info py-2 small">
                            <i class="fa fa-info-circle me-1"></i> Enter <strong>ONLY</strong> the subdomain part of your Infobip URL. Example: if your URL is <code>https://xkglel.api.infobip.com</code>, enter <strong>xkglel</strong>.
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Base URL Subdomain</label>
                                <div class="input-group">
                                    <span class="input-group-text">https://</span>
                                    <input type="text" name="infobip_subdomain" id="infobipSubdomain" class="form-control text-center font-weight-bold" 
                                        value="{{ $settings['infobip_subdomain'] ?? '' }}" 
                                        placeholder="XXXXXX" pattern="[a-zA-Z0-9]+" title="Only alphanumeric subdomain characters allowed">
                                    <span class="input-group-text">.api.infobip.com</span>
                                </div>
                                <div id="subdomainError" class="text-danger small mt-1" style="display:none;">Invalid Format: Please enter ONLY the subdomain code (e.g., xkglel).</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">API Key</label>
                                <input type="password" name="infobip_api_key" class="form-control" placeholder="*************">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">WhatsApp From Number</label>
                                <input type="text" name="infobip_whatsapp_from" class="form-control" value="{{ $settings['infobip_whatsapp_from'] ?? '' }}" placeholder="e.g. 243899xxxxxx">
                                <small class="text-muted">Registered Infobip WhatsApp sender (digits only, with country code). Do not leave empty — empty falls back to a UK demo number.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">WhatsApp Template Name <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" name="infobip_whatsapp_template_name" class="form-control" value="{{ $settings['infobip_whatsapp_template_name'] ?? '' }}" placeholder="e.g. school_notification">
                                <small class="text-muted">Approved Infobip template with one body placeholder <code>@{{1}}</code>. Digitex puts the school message in that placeholder only. Any extra English/French sentences you typed around <code>@{{1}}</code> in Infobip (for Meta approval) are shown on every template send. Edit the template body in Infobip to change that wrapper. Session replies (parent already wrote in the last 24h) go as free text without the wrapper.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Template Language</label>
                                <input type="text" name="infobip_whatsapp_template_language" class="form-control" value="{{ $settings['infobip_whatsapp_template_language'] ?? 'en' }}" placeholder="en / fr">
                                <small class="text-muted">Must match the language code used when the template was registered.</small>
                            </div>
                        </div>
                    </div>

                    {{-- 3. META (Unchanged) --}}
                    <div class="tab-pane fade" id="meta">
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Phone ID</label><input type="text" name="meta_phone_number_id" class="form-control" value="{{ $settings['meta_phone_number_id'] ?? '' }}"></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Business Account ID</label><input type="text" name="meta_business_account_id" class="form-control" value="{{ $settings['meta_business_account_id'] ?? '' }}"></div>
                            <div class="col-md-12 mb-3"><label class="form-label">Access Token</label><input type="password" name="meta_access_token" class="form-control" placeholder="*************"></div>
                        </div>
                    </div>

                    {{-- 4. TWILIO (Unchanged) --}}
                    <div class="tab-pane fade" id="twilio">
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">SID</label><input type="text" name="twilio_sid" class="form-control" value="{{ $settings['twilio_sid'] ?? '' }}"></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Token</label><input type="password" name="twilio_token" class="form-control" placeholder="*************"></div>
                            <div class="col-md-6 mb-3"><label class="form-label">From (SMS)</label><input type="text" name="twilio_from" class="form-control" value="{{ $settings['twilio_from'] ?? '' }}"></div>
                            <div class="col-md-6 mb-3"><label class="form-label">WhatsApp From</label><input type="text" name="twilio_whatsapp_from" class="form-control" value="{{ $settings['twilio_whatsapp_from'] ?? '' }}"></div>
                        </div>
                    </div>

                    {{-- 5. SIGNALWIRE (Unchanged) --}}
                    <div class="tab-pane fade" id="signalwire">
                        <div class="row">
                            <div class="col-md-4 mb-3"><label class="form-label">Project ID</label><input type="text" name="sw_project_id" class="form-control" value="{{ $settings['sw_project_id'] ?? '' }}"></div>
                            <div class="col-md-4 mb-3"><label class="form-label">Space URL</label><input type="text" name="sw_space_url" class="form-control" value="{{ $settings['sw_space_url'] ?? '' }}"></div>
                            <div class="col-md-4 mb-3"><label class="form-label">Token</label><input type="password" name="sw_token" class="form-control" placeholder="*************"></div>
                            <div class="col-md-6 mb-3"><label class="form-label">From</label><input type="text" name="sw_from" class="form-control" value="{{ $settings['sw_from'] ?? '' }}"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Parent WhatsApp contact QR --}}
            @unless($isSuperAdmin)
            <div class="mt-4 mb-2 p-4 border rounded-3 bg-light">
                <h5 class="text-primary fw-bold mb-2">
                    <i class="fab fa-whatsapp me-2"></i>{{ __('configuration.school_whatsapp_qr_title') }}
                </h5>
                <p class="text-muted small mb-3">{{ __('configuration.school_whatsapp_qr_help') }}</p>
                <div class="row align-items-end g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-bold">{{ __('configuration.school_whatsapp_number') }}</label>
                        <input type="text"
                               name="school_whatsapp_number"
                               id="schoolWhatsappNumber"
                               class="form-control"
                               value="{{ $settings['school_whatsapp_number'] ?? ($settings['infobip_whatsapp_from'] ?? ($settings['twilio_whatsapp_from'] ?? '')) }}"
                               placeholder="e.g. 243899xxxxxx"
                               inputmode="tel">
                        <small class="text-muted">{{ __('configuration.school_whatsapp_number_hint') }}</small>
                    </div>
                    <div class="col-md-7">
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <div id="schoolWaQrPreview" class="bg-white border rounded p-2 text-center" style="min-width:140px;min-height:140px;">
                                <img id="schoolWaQrImg" src="" alt="WhatsApp QR" width="128" height="128" class="d-none">
                                <div id="schoolWaQrEmpty" class="text-muted small py-5 px-2">{{ __('configuration.school_whatsapp_qr_empty') }}</div>
                            </div>
                            <div>
                                <a id="schoolWaLink" href="#" target="_blank" rel="noopener" class="btn btn-success btn-sm mb-2 d-none">
                                    <i class="fab fa-whatsapp me-1"></i>{{ __('configuration.open_whatsapp') }}
                                </a>
                                <a id="schoolWaQrDownload" href="#" download="school-whatsapp-qr.png" class="btn btn-outline-primary btn-sm mb-2 d-none">
                                    <i class="fa fa-download me-1"></i>{{ __('configuration.download_qr') }}
                                </a>
                                <button type="button" id="schoolWaQrPrint" class="btn btn-outline-secondary btn-sm mb-2 d-none">
                                    <i class="fa fa-print me-1"></i>{{ __('configuration.print_qr') }}
                                </button>
                                <div class="small text-muted">{{ __('configuration.school_whatsapp_qr_print_hint') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endunless

            <div class="text-end mt-4">
                <button type="submit" class="btn btn-primary px-4">{{ __('configuration.save_changes') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle Creds Logic
        const smsSelect = document.getElementById('smsProviderSelect');
        const waSelect = document.getElementById('waProviderSelect');
        const credSection = document.getElementById('credentialsSection');
        
        function toggleCreds() {
            const isSystem = (smsSelect.value === 'system' && waSelect.value === 'system');
            const isSuperAdmin = {{ $isSuperAdmin ? 'true' : 'false' }};
            
            if (!isSuperAdmin && isSystem) {
                credSection.style.display = 'none';
            } else {
                credSection.style.display = 'block';
            }
        }

        if(smsSelect && waSelect) {
            smsSelect.addEventListener('change', toggleCreds);
            waSelect.addEventListener('change', toggleCreds);
            toggleCreds(); 
        }

        // Infobip Subdomain Validation
        const subInput = document.getElementById('infobipSubdomain');
        const subError = document.getElementById('subdomainError');
        const form = document.getElementById('smsSettingsForm');

        if(subInput) {
            subInput.addEventListener('input', function() {
                // Remove protocol or domain parts if user pastes full URL
                let val = this.value;
                val = val.replace('https://', '').replace('http://', '');
                val = val.replace('.api.infobip.com', '').replace('/', '');
                
                if (val !== this.value) {
                    this.value = val; // Auto-fix
                }

                // Check for special chars
                const regex = /^[a-zA-Z0-9]+$/;
                if (!regex.test(val) && val.length > 0) {
                    subError.style.display = 'block';
                    this.classList.add('is-invalid');
                } else {
                    subError.style.display = 'none';
                    this.classList.remove('is-invalid');
                }
            });
        }

        // School WhatsApp QR live preview
        const waInput = document.getElementById('schoolWhatsappNumber');
        const waImg = document.getElementById('schoolWaQrImg');
        const waEmpty = document.getElementById('schoolWaQrEmpty');
        const waLink = document.getElementById('schoolWaLink');
        const waDl = document.getElementById('schoolWaQrDownload');
        const waPrint = document.getElementById('schoolWaQrPrint');

        function digitsOnly(v) {
            return (v || '').replace(/\D+/g, '');
        }

        function refreshWaQr() {
            if (!waInput || !waImg) return;
            const digits = digitsOnly(waInput.value);
            if (digits.length < 8) {
                waImg.classList.add('d-none');
                waEmpty?.classList.remove('d-none');
                waLink?.classList.add('d-none');
                waDl?.classList.add('d-none');
                waPrint?.classList.add('d-none');
                return;
            }
            const waUrl = 'https://wa.me/' + digits;
            const qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=256x256&data=' + encodeURIComponent(waUrl);
            waImg.src = qrUrl;
            waImg.classList.remove('d-none');
            waEmpty?.classList.add('d-none');
            if (waLink) {
                waLink.href = waUrl;
                waLink.classList.remove('d-none');
            }
            if (waDl) {
                waDl.href = qrUrl;
                waDl.classList.remove('d-none');
            }
            waPrint?.classList.remove('d-none');
        }

        if (waInput) {
            waInput.addEventListener('input', refreshWaQr);
            refreshWaQr();
        }

        if (waPrint) {
            waPrint.addEventListener('click', function() {
                const digits = digitsOnly(waInput?.value || '');
                if (digits.length < 8) return;
                const waUrl = 'https://wa.me/' + digits;
                const qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=' + encodeURIComponent(waUrl);
                const w = window.open('', '_blank');
                if (!w) return;
                w.document.write('<html><head><title>WhatsApp QR</title></head><body style="text-align:center;font-family:sans-serif;padding:24px;">'
                    + '<h2>{{ __('configuration.school_whatsapp_qr_title') }}</h2>'
                    + '<img src="' + qrUrl + '" width="280" height="280" alt="QR"/>'
                    + '<p style="margin-top:16px;">' + waUrl + '</p>'
                    + '<script>window.onload=function(){window.print();}<\\/script>'
                    + '</body></html>');
                w.document.close();
            });
        }
    });
</script>