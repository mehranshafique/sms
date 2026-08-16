@extends('layout.layout')

@section('styles')
<style>
    .voice-settings-page { overflow-x: hidden; }
    .voice-settings-page .row > [class*="col-"] { min-width: 0; }
    .voice-settings-page .card { overflow: hidden; }
    .voice-settings-page .form-control,
    .voice-settings-page select.form-control {
        max-width: 100%;
    }
    .voice-webhook-url {
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 0.75rem;
        word-break: break-all;
    }
    .voice-check-item {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        margin-bottom: 0.45rem;
    }
    .voice-check-item i { margin-top: 0.2rem; flex-shrink: 0; }
    .voice-sessions-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .voice-sessions-table { min-width: 760px; margin-bottom: 0; }
    @media (max-width: 767.98px) {
        .voice-settings-page .welcome-text h4 { font-size: 1.15rem; }
        .voice-pin-actions { width: 100%; }
    }
</style>
@endsection

@section('content')
<div class="content-body voice-settings-page">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-12 col-md-8 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('voice.page_title') }}</h4>
                    <p class="mb-0">{{ __('voice.subtitle') }}</p>
                </div>
            </div>
            <div class="col-12 col-md-4 p-md-0 mt-2 mt-md-0 text-md-end">
                <a href="{{ route('voice.settings.manual') }}" class="btn btn-outline-primary btn-sm">
                    <i class="fa fa-file-pdf-o me-1"></i>{{ __('voice.settings.manual_download') }}
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-warning">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @unless($moduleEnabled)
            <div class="alert alert-warning">
                <i class="fa fa-lock me-2"></i>{{ __('voice.settings.module_off') }}
            </div>
        @endunless

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h5 class="text-primary mb-2">{{ __('voice.settings.readiness_title') }}</h5>
                <p class="mb-3">{{ __('voice.settings.readiness_code') }} {{ __('voice.settings.readiness_infobip') }}</p>
                <div class="row g-2">
                    <div class="col-12 col-lg-6">
                        <div class="voice-check-item">
                            <i class="fa {{ $moduleEnabled ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }}"></i>
                            <span>{{ __('voice.settings.checklist_module') }}</span>
                        </div>
                        <div class="voice-check-item">
                            <i class="fa {{ ($config['enabled'] ?? false) ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }}"></i>
                            <span>{{ __('voice.settings.checklist_switch') }}</span>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="voice-check-item">
                            <i class="fa {{ $whatsappFromConfigured ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }}"></i>
                            <span>{{ __('voice.settings.checklist_whatsapp') }}</span>
                        </div>
                        <div class="voice-check-item">
                            <i class="fa {{ $sessions->isNotEmpty() ? 'fa-check-circle text-success' : 'fa-exclamation-circle text-warning' }}"></i>
                            <span>{{ __('voice.settings.checklist_calls') }}</span>
                        </div>
                    </div>
                </div>
                @if($sessions->isEmpty())
                    <p class="text-muted small mb-0 mt-2">{{ __('voice.settings.readiness_no_calls') }}</p>
                @endif
            </div>
        </div>

        <div class="row g-3 align-items-start">
            <div class="col-12 col-xl-7">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form action="{{ route('voice.settings.store') }}" method="POST">
                            @csrf
                            <div class="form-check form-switch mb-4">
                                <input class="form-check-input" type="checkbox" name="enabled" id="voiceEnabled" value="1"
                                    {{ ($config['enabled'] ?? false) ? 'checked' : '' }}
                                    {{ $moduleEnabled ? '' : 'disabled' }}>
                                <label class="form-check-label fw-bold" for="voiceEnabled">{{ __('voice.settings.enable') }}</label>
                                <small class="text-muted d-block mt-1">{{ __('voice.settings.enable_help') }}</small>
                            </div>

                            <div class="row g-3">
                                <div class="col-12 col-sm-6">
                                    <label class="form-label fw-bold">{{ __('voice.settings.locale') }}</label>
                                    <select name="locale" class="form-control" {{ $moduleEnabled ? '' : 'disabled' }}>
                                        <option value="fr" {{ ($config['locale'] ?? 'fr') === 'fr' ? 'selected' : '' }}>Français</option>
                                        <option value="en" {{ ($config['locale'] ?? '') === 'en' ? 'selected' : '' }}>English</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label class="form-label fw-bold">{{ __('voice.settings.max_turns') }}</label>
                                    <input type="number" name="max_turns" class="form-control" min="3" max="30"
                                        value="{{ $config['max_turns'] ?? 8 }}" {{ $moduleEnabled ? '' : 'disabled' }}>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">{{ __('voice.settings.secretary') }}</label>
                                    <textarea name="secretary" class="form-control" rows="2" {{ $moduleEnabled ? '' : 'disabled' }}>{{ $config['secretary'] ?? '' }}</textarea>
                                    <small class="text-muted">{{ __('voice.settings.secretary_help') }}</small>
                                </div>
                            </div>

                            <h5 class="text-primary border-bottom pb-2 mt-4">{{ __('voice.settings.privacy_title') }}</h5>
                            <div class="form-check form-switch mb-3 mt-3">
                                <input class="form-check-input" type="checkbox" name="pin_required" id="pinRequired" value="1"
                                    {{ ($config['pin_required'] ?? false) ? 'checked' : '' }}
                                    {{ $moduleEnabled ? '' : 'disabled' }}>
                                <label class="form-check-label fw-bold" for="pinRequired">{{ __('voice.settings.pin_required') }}</label>
                                <small class="text-muted d-block mt-1">{{ __('voice.settings.pin_required_help') }}</small>
                            </div>

                            <h5 class="text-primary border-bottom pb-2 mt-4">{{ __('voice.settings.ai_title') }}</h5>
                            @unless($aiReady)
                                <div class="alert alert-light border mt-3 mb-3 small">{{ __('voice.settings.ai_not_ready') }}</div>
                            @endunless
                            <div class="form-check form-switch mb-3 mt-3">
                                <input class="form-check-input" type="checkbox" name="ai_enabled" id="aiEnabled" value="1"
                                    {{ ($config['ai_enabled'] ?? false) ? 'checked' : '' }}
                                    {{ $moduleEnabled ? '' : 'disabled' }}>
                                <label class="form-check-label fw-bold" for="aiEnabled">{{ __('voice.settings.ai_enabled') }}</label>
                                <small class="text-muted d-block mt-1">{{ __('voice.settings.ai_enabled_help') }}</small>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="ai_guest_enabled" id="aiGuestEnabled" value="1"
                                    {{ ($config['ai_guest_enabled'] ?? false) ? 'checked' : '' }}
                                    {{ $moduleEnabled ? '' : 'disabled' }}>
                                <label class="form-check-label fw-bold" for="aiGuestEnabled">{{ __('voice.settings.ai_guest_enabled') }}</label>
                            </div>
                            <div class="row g-3 mb-2">
                                <div class="col-12 col-sm-6">
                                    <label class="form-label fw-bold">{{ __('voice.settings.ai_max_questions') }}</label>
                                    <input type="number" name="ai_max_questions" class="form-control" min="1" max="10"
                                        value="{{ $config['ai_max_questions'] ?? 3 }}" {{ $moduleEnabled ? '' : 'disabled' }}>
                                </div>
                            </div>

                            <h5 class="text-primary border-bottom pb-2 mt-4">{{ __('voice.settings.transfer_title') }}</h5>
                            <div class="form-check form-switch mb-3 mt-3">
                                <input class="form-check-input" type="checkbox" name="transfer_enabled" id="transferEnabled" value="1"
                                    {{ ($config['transfer_enabled'] ?? false) ? 'checked' : '' }}
                                    {{ $moduleEnabled ? '' : 'disabled' }}>
                                <label class="form-check-label fw-bold" for="transferEnabled">{{ __('voice.settings.transfer_enabled') }}</label>
                                <small class="text-muted d-block mt-1">{{ __('voice.settings.transfer_enabled_help') }}</small>
                            </div>
                            <div class="row g-3">
                                <div class="col-12 col-sm-4">
                                    <label class="form-label fw-bold">{{ __('voice.settings.transfer_endpoint_type') }}</label>
                                    <select name="transfer_endpoint_type" class="form-control" {{ $moduleEnabled ? '' : 'disabled' }}>
                                        @foreach($endpointTypes as $type)
                                            <option value="{{ $type }}" {{ ($config['transfer_endpoint_type'] ?? 'whatsapp') === $type ? 'selected' : '' }}>{{ strtoupper($type) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-sm-8">
                                    <label class="form-label fw-bold">{{ __('voice.settings.transfer_identity') }}</label>
                                    <input type="text" name="transfer_identity" class="form-control"
                                        value="{{ $config['transfer_identity'] ?? '' }}" {{ $moduleEnabled ? '' : 'disabled' }}>
                                    <small class="text-muted d-block">{{ __('voice.settings.transfer_identity_help') }}</small>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label class="form-label fw-bold">{{ __('voice.settings.transfer_hours_start') }}</label>
                                    <input type="time" name="transfer_hours_start" class="form-control"
                                        value="{{ $config['transfer_hours_start'] ?? '' }}" {{ $moduleEnabled ? '' : 'disabled' }}>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label class="form-label fw-bold">{{ __('voice.settings.transfer_hours_end') }}</label>
                                    <input type="time" name="transfer_hours_end" class="form-control"
                                        value="{{ $config['transfer_hours_end'] ?? '' }}" {{ $moduleEnabled ? '' : 'disabled' }}>
                                    <small class="text-muted d-block">{{ __('voice.settings.transfer_hours_help') }}</small>
                                </div>
                            </div>

                            @if($moduleEnabled)
                                <button type="submit" class="btn btn-primary mt-3">{{ __('configuration.save') ?? 'Save' }}</button>
                            @endif
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-5">
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <h5 class="text-primary border-bottom pb-2">{{ __('voice.settings.webhooks') }}</h5>
                        <p class="text-muted small">{{ __('voice.settings.webhooks_help') }}</p>
                        @foreach([
                            'inbound' => __('voice.settings.inbound'),
                            'dtmf' => __('voice.settings.dtmf'),
                            'recording' => __('voice.settings.recording'),
                            'transfer' => __('voice.settings.transfer'),
                            'status' => __('voice.settings.status'),
                            'health' => __('voice.settings.health'),
                        ] as $key => $label)
                            <div class="mb-3">
                                <label class="form-label fw-bold small mb-1">{{ $label }}</label>
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-sm voice-webhook-url" readonly
                                        value="{{ $webhookUrls[$key] ?? '' }}" aria-label="{{ $label }}">
                                    <button type="button" class="btn btn-outline-secondary btn-sm js-copy-webhook">
                                        {{ __('voice.settings.copy') }}
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="text-primary border-bottom pb-2">{{ __('voice.settings.pins_title') }}</h5>
                        <p class="text-muted small">{{ __('voice.settings.pins_help') }}</p>

                        @if($moduleEnabled)
                            <form action="{{ route('voice.settings.pin.store') }}" method="POST" class="mb-3">
                                @csrf
                                <div class="row g-2 align-items-end">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-bold small">{{ __('voice.settings.pin_search') }}</label>
                                        <input type="text" name="phone" class="form-control" required
                                            placeholder="{{ __('voice.settings.pin_phone_placeholder') }}">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label fw-bold small">{{ __('voice.settings.pin_value') }}</label>
                                        <input type="text" name="pin" class="form-control" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" required>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <button type="submit" class="btn btn-primary w-100 voice-pin-actions">
                                            {{ __('voice.settings.pin_set') }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ __('voice.settings.pin_list_parent') }}</th>
                                        <th>{{ __('voice.settings.pin_list_updated') }}</th>
                                        <th>{{ __('voice.settings.pin_list_locked') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($parentPins as $pin)
                                        <tr>
                                            <td>{{ $pin->parent?->guardian_name ?: ($pin->parent?->father_name ?: ($pin->parent?->mother_name ?: '#' . $pin->parent_id)) }}</td>
                                            <td class="small text-nowrap">{{ $pin->updated_at?->format('Y-m-d H:i') }}</td>
                                            <td class="small text-nowrap">{{ $pin->isLocked() ? $pin->locked_until->format('Y-m-d H:i') : '—' }}</td>
                                            <td class="text-end">
                                                @if($moduleEnabled)
                                                    <form action="{{ route('voice.settings.pin.destroy', $pin->parent_id) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('voice.settings.pin_remove') }}</button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-muted text-center">—</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-header">
                <h5 class="mb-0">{{ __('voice.settings.sessions') }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="voice-sessions-wrap p-3">
                    <table class="table table-sm table-striped voice-sessions-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Call</th>
                                <th>Phone</th>
                                <th>Profile</th>
                                <th>State</th>
                                <th>PIN</th>
                                <th>Locale</th>
                                <th>Turns</th>
                                <th>AI</th>
                                <th>Started</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sessions as $s)
                                <tr>
                                    <td>{{ $s->id }}</td>
                                    <td><code class="small">{{ \Illuminate\Support\Str::limit($s->call_id, 18) }}</code></td>
                                    <td class="text-nowrap">{{ $s->phone_number }}</td>
                                    <td>{{ $s->menu_profile }}</td>
                                    <td>{{ $s->state }}</td>
                                    <td>{{ $s->pin_verified ? '✓' : '—' }}</td>
                                    <td>{{ $s->locale }}</td>
                                    <td>{{ $s->turns }}</td>
                                    <td>{{ $s->ai_turns }}</td>
                                    <td class="text-nowrap">{{ $s->started_at?->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-muted text-center py-4">{{ __('voice.settings.sessions_empty') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    document.querySelectorAll('.js-copy-webhook').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = btn.parentElement.querySelector('input');
            if (!input) return;
            navigator.clipboard.writeText(input.value).then(function () {
                var original = btn.textContent;
                btn.textContent = @json(__('voice.settings.copied'));
                setTimeout(function () { btn.textContent = original; }, 1500);
            });
        });
    });
</script>
@endsection
