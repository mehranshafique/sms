@if(!empty($planCtx['has_ai']))
<div class="ai-copilot-card" id="ai-dashboard-copilot">
    <div class="ai-copilot-card__head">
        <div>
            <strong><i class="la la-magic text-primary me-1"></i> {{ __('ai.copilot_title') }}</strong>
            <div class="text-muted small">{{ __('ai.copilot_subtitle') }}</div>
        </div>
        <button type="button" class="ai-embed-btn" data-ai-tool="dashboard_briefing" data-ai-params="{}" data-ai-panel="#ai-copilot-output">
            <i class="la la-bolt"></i> {{ __('ai.copilot_refresh') }}
        </button>
    </div>
    <div class="ai-embed-panel is-visible" id="ai-copilot-output" style="display:block;background:transparent;border:none;padding:0;margin:0;">
        <span class="text-muted small">{{ __('ai.copilot_hint') }}</span>
    </div>
</div>
@endif
