@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-8 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('voice.page_title') }}</h4>
                    <p class="mb-0">{{ __('voice.subtitle') }}</p>
                </div>
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

        <div class="row">
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form action="{{ route('voice.settings.store') }}" method="POST">
                            @csrf
                            <div class="form-check form-switch mb-4">
                                <input class="form-check-input" type="checkbox" name="enabled" id="voiceEnabled" value="1"
                                    {{ ($config['enabled'] ?? false) ? 'checked' : '' }}
                                    {{ $moduleEnabled ? '' : 'disabled' }}>
                                <label class="form-check-label fw-bold ms-2" for="voiceEnabled">{{ __('voice.settings.enable') }}</label>
                                <small class="text-muted d-block ms-4 mt-1">{{ __('voice.settings.enable_help') }}</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">{{ __('voice.settings.locale') }}</label>
                                    <select name="locale" class="form-select" {{ $moduleEnabled ? '' : 'disabled' }}>
                                        <option value="fr" {{ ($config['locale'] ?? 'fr') === 'fr' ? 'selected' : '' }}>Français</option>
                                        <option value="en" {{ ($config['locale'] ?? '') === 'en' ? 'selected' : '' }}>English</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">{{ __('voice.settings.max_turns') }}</label>
                                    <input type="number" name="max_turns" class="form-control" min="3" max="30"
                                        value="{{ $config['max_turns'] ?? 8 }}" {{ $moduleEnabled ? '' : 'disabled' }}>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">{{ __('voice.settings.secretary') }}</label>
                                <textarea name="secretary" class="form-control" rows="2" {{ $moduleEnabled ? '' : 'disabled' }}>{{ $config['secretary'] ?? '' }}</textarea>
                                <small class="text-muted">{{ __('voice.settings.secretary_help') }}</small>
                            </div>

                            {{-- Phase 2 --}}
                            <h5 class="text-primary border-bottom pb-2">{{ __('voice.settings.privacy_title') }}</h5>
                            <div class="form-check form-switch mb-4 mt-3">
                                <input class="form-check-input" type="checkbox" name="pin_required" id="pinRequired" value="1"
                                    {{ ($config['pin_required'] ?? false) ? 'checked' : '' }}
                                    {{ $moduleEnabled ? '' : 'disabled' }}>
                                <label class="form-check-label fw-bold ms-2" for="pinRequired">{{ __('voice.settings.pin_required') }}</label>
                                <small class="text-muted d-block ms-4 mt-1">{{ __('voice.settings.pin_required_help') }}</small>
                            </div>

                            {{-- Phase 3 --}}
                            <h5 class="text-primary border-bottom pb-2">{{ __('voice.settings.ai_title') }}</h5>
                            @unless($aiReady)
                                <div class="alert alert-light border mt-3 mb-3 small">{{ __('voice.settings.ai_not_ready') }}</div>
                            @endunless
                            <div class="form-check form-switch mb-3 mt-3">
                                <input class="form-check-input" type="checkbox" name="ai_enabled" id="aiEnabled" value="1"
                                    {{ ($config['ai_enabled'] ?? false) ? 'checked' : '' }}
                                    {{ $moduleEnabled ? '' : 'disabled' }}>
                                <label class="form-check-label fw-bold ms-2" for="aiEnabled">{{ __('voice.settings.ai_enabled') }}</label>
                                <small class="text-muted d-block ms-4 mt-1">{{ __('voice.settings.ai_enabled_help') }}</small>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="ai_guest_enabled" id="aiGuestEnabled" value="1"
                                    {{ ($config['ai_guest_enabled'] ?? false) ? 'checked' : '' }}
                                    {{ $moduleEnabled ? '' : 'disabled' }}>
                                <label class="form-check-label fw-bold ms-2" for="aiGuestEnabled">{{ __('voice.settings.ai_guest_enabled') }}</label>
                            </div>
                            <div class="mb-4 col-md-6">
                                <label class="form-label fw-bold">{{ __('voice.settings.ai_max_questions') }}</label>
                                <input type="number" name="ai_max_questions" class="form-control" min="1" max="10"
                                    value="{{ $config['ai_max_questions'] ?? 3 }}" {{ $moduleEnabled ? '' : 'disabled' }}>
                            </div>

                            {{-- Phase 4 --}}
                            <h5 class="text-primary border-bottom pb-2">{{ __('voice.settings.transfer_title') }}</h5>
                            <div class="form-check form-switch mb-3 mt-3">
                                <input class="form-check-input" type="checkbox" name="transfer_enabled" id="transferEnabled" value="1"
                                    {{ ($config['transfer_enabled'] ?? false) ? 'checked' : '' }}
                                    {{ $moduleEnabled ? '' : 'disabled' }}>
                                <label class="form-check-label fw-bold ms-2" for="transferEnabled">{{ __('voice.settings.transfer_enabled') }}</label>
                                <small class="text-muted d-block ms-4 mt-1">{{ __('voice.settings.transfer_enabled_help') }}</small>
                            </div>
                            <div class="row">
                                <div class="col-md-5 mb-3">
                                    <label class="form-label fw-bold">{{ __('voice.settings.transfer_endpoint_type') }}</label>
                                    <select name="transfer_endpoint_type" class="form-select" {{ $moduleEnabled ? '' : 'disabled' }}>
                                        @foreach($endpointTypes as $type)
                                            <option value="{{ $type }}" {{ ($config['transfer_endpoint_type'] ?? 'whatsapp') === $type ? 'selected' : '' }}>{{ strtoupper($type) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-7 mb-3">
                                    <label class="form-label fw-bold">{{ __('voice.settings.transfer_identity') }}</label>
                                    <input type="text" name="transfer_identity" class="form-control"
                                        value="{{ $config['transfer_identity'] ?? '' }}" {{ $moduleEnabled ? '' : 'disabled' }}>
                                    <small class="text-muted">{{ __('voice.settings.transfer_identity_help') }}</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">{{ __('voice.settings.transfer_hours_start') }}</label>
                                    <input type="time" name="transfer_hours_start" class="form-control"
                                        value="{{ $config['transfer_hours_start'] ?? '' }}" {{ $moduleEnabled ? '' : 'disabled' }}>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">{{ __('voice.settings.transfer_hours_end') }}</label>
                                    <input type="time" name="transfer_hours_end" class="form-control"
                                        value="{{ $config['transfer_hours_end'] ?? '' }}" {{ $moduleEnabled ? '' : 'disabled' }}>
                                    <small class="text-muted">{{ __('voice.settings.transfer_hours_help') }}</small>
                                </div>
                            </div>

                            @if($moduleEnabled)
                                <button type="submit" class="btn btn-primary mt-2">{{ __('configuration.save') ?? 'Save' }}</button>
                            @endif
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="text-primary border-bottom pb-2">{{ __('voice.settings.webhooks') }}</h5>
                        <p class="text-muted small">{{ __('voice.settings.webhooks_help') }}</p>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <tbody>
                                    @foreach([
                                        'inbound' => __('voice.settings.inbound'),
                                        'dtmf' => __('voice.settings.dtmf'),
                                        'recording' => __('voice.settings.recording'),
                                        'transfer' => __('voice.settings.transfer'),
                                        'status' => __('voice.settings.status'),
                                        'health' => __('voice.settings.health'),
                                    ] as $key => $label)
                                        <tr>
                                            <td class="fw-bold" style="width:120px">{{ $label }}</td>
                                            <td><code class="small user-select-all">{{ $webhookUrls[$key] ?? '' }}</code></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="text-primary border-bottom pb-2">{{ __('voice.settings.pins_title') }}</h5>
                        <p class="text-muted small">{{ __('voice.settings.pins_help') }}</p>

                        @if($moduleEnabled)
                            <form action="{{ route('voice.settings.pin.store') }}" method="POST" class="row g-2 align-items-end mb-3">
                                @csrf
                                <div class="col-7">
                                    <label class="form-label fw-bold small">{{ __('voice.settings.pin_search') }}</label>
                                    <input type="text" name="phone" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-3">
                                    <label class="form-label fw-bold small">{{ __('voice.settings.pin_value') }}</label>
                                    <input type="text" name="pin" class="form-control form-control-sm" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" required>
                                </div>
                                <div class="col-2">
                                    <button type="submit" class="btn btn-sm btn-primary w-100">
                                        <i class="fa fa-key"></i>
                                    </button>
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
                                            <td class="small">{{ $pin->updated_at?->format('Y-m-d H:i') }}</td>
                                            <td class="small">{{ $pin->isLocked() ? $pin->locked_until->format('Y-m-d H:i') : '—' }}</td>
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

        <div class="row mt-3">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('voice.settings.sessions') }}</h5>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm table-striped mb-0">
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
                                        <td>{{ $s->phone_number }}</td>
                                        <td>{{ $s->menu_profile }}</td>
                                        <td>{{ $s->state }}</td>
                                        <td>{{ $s->pin_verified ? '✓' : '—' }}</td>
                                        <td>{{ $s->locale }}</td>
                                        <td>{{ $s->turns }}</td>
                                        <td>{{ $s->ai_turns }}</td>
                                        <td>{{ $s->started_at?->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="10" class="text-muted text-center">—</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
