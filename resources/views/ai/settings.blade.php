@extends('layout.layout')

@section('content')
@include('ai.partials.ai-styles')
<div class="content-body">
    <div class="container-fluid">

        <div class="row mb-4">
            <div class="col-12">
                <div class="ai-hero shadow-sm">
                    <div class="d-flex flex-wrap justify-content-between align-items-center p-4" style="position:relative; z-index:1;">
                        <div>
                            <span class="ai-hero__chip mb-2"><i class="la la-cogs"></i> {{ __('ai.platform') }}</span>
                            <h3 class="text-white fw-bold mb-1">{{ __('ai.settings_title') }}</h3>
                            <p class="mb-0 text-white opacity-75">{{ __('ai.settings_subtitle') }}</p>
                        </div>
                        <i class="la la-robot ai-hero__icon d-none d-md-block"></i>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(!$enabled)
            <div class="alert alert-secondary d-flex align-items-center gap-2">
                <i class="la la-power-off fs-4"></i>
                <div>{{ __('ai.master_disabled_notice') }}</div>
            </div>
        @endif

        <div class="row g-3">
            {{-- Usage overview --}}
            <div class="col-12">
                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <div class="ai-panel"><div class="ai-panel__body">
                            <div class="text-muted small">{{ __('ai.usage_requests') }}</div>
                            <h3 class="fw-bold mb-0">{{ number_format($usage['requests']) }}</h3>
                            <small class="text-muted">{{ $period }}</small>
                        </div></div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="ai-panel"><div class="ai-panel__body">
                            <div class="text-muted small">{{ __('ai.usage_tokens') }}</div>
                            <h3 class="fw-bold mb-0">{{ number_format($usage['tokens']) }}</h3>
                            <small class="text-muted">{{ __('ai.usage_tokens_hint') }}</small>
                        </div></div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="ai-panel"><div class="ai-panel__body">
                            <div class="text-muted small">{{ __('ai.usage_blocked') }}</div>
                            <h3 class="fw-bold mb-0 text-warning">{{ number_format($usage['blocked']) }}</h3>
                            <small class="text-muted">{{ __('ai.usage_blocked_hint') }}</small>
                        </div></div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="ai-panel"><div class="ai-panel__body">
                            <div class="text-muted small">{{ __('ai.usage_errors') }}</div>
                            <h3 class="fw-bold mb-0 text-danger">{{ number_format($usage['errors']) }}</h3>
                            <small class="text-muted">{{ __('ai.usage_errors_hint') }}</small>
                        </div></div>
                    </div>
                </div>
            </div>

            {{-- Provider config --}}
            <div class="col-lg-7">
                <div class="ai-panel h-100">
                    <div class="ai-panel__head">
                        <h6 class="mb-0 fw-bold"><i class="la la-key text-primary"></i> {{ __('ai.provider_config') }}</h6>
                        @if($hasKey)
                            <span class="badge bg-success">{{ __('ai.key_set') }}</span>
                        @else
                            <span class="badge bg-secondary">{{ __('ai.key_not_set') }}</span>
                        @endif
                    </div>
                    <div class="ai-panel__body">
                        <form action="{{ route('ai.settings.update') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">{{ __('ai.api_key') }}</label>
                                <input type="password" name="api_key" class="form-control" placeholder="{{ $hasKey ? '••••••••••••  ('.__('ai.leave_blank_keep').')' : 'sk-...' }}">
                                <small class="text-muted">{{ __('ai.api_key_hint') }}</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('ai.model') }}</label>
                                <input type="text" name="model" class="form-control" value="{{ $model }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('ai.base_url') }}</label>
                                <input type="text" name="base_url" class="form-control" value="{{ $baseUrl }}">
                                <small class="text-muted">{{ __('ai.base_url_hint') }}</small>
                            </div>
                            <button class="btn btn-primary"><i class="la la-save"></i> {{ __('ai.save') }}</button>
                            @if($hasKey)
                                <button form="aiClearKey" class="btn btn-outline-danger"><i class="la la-trash"></i> {{ __('ai.clear_key') }}</button>
                            @endif
                        </form>
                        <form action="{{ route('ai.settings.clear') }}" method="POST" id="aiClearKey">@csrf</form>
                    </div>
                </div>
            </div>

            {{-- Top consumers --}}
            <div class="col-lg-5">
                <div class="ai-panel h-100">
                    <div class="ai-panel__head">
                        <h6 class="mb-0 fw-bold"><i class="la la-chart-bar text-info"></i> {{ __('ai.top_consumers') }}</h6>
                    </div>
                    <div class="ai-panel__body p-0">
                        <table class="table mb-0">
                            <tbody>
                                @forelse($topConsumers as $row)
                                    <tr>
                                        <td>{{ optional($row->institution)->name ?? '—' }}</td>
                                        <td class="text-end fw-bold">{{ number_format($row->total) }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="text-muted text-center py-4">{{ __('ai.no_usage_yet') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="ai-panel mt-3">
            <div class="ai-panel__body">
                <h6 class="fw-bold"><i class="la la-info-circle text-primary"></i> {{ __('ai.tiering_title') }}</h6>
                <p class="text-muted mb-0">{{ __('ai.tiering_help') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
