@once
@include('ai.partials.ai-styles')
<style>
    .ai-fab {
        position: fixed;
        right: 22px;
        left: auto;
        bottom: 22px;
        z-index: 1055;
        width: 54px;
        height: 54px;
        border-radius: 50%;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #7c3aed, #5b21b6);
        color: #fff;
        font-size: 1.35rem;
        cursor: pointer;
        box-shadow: 0 8px 28px rgba(124, 58, 237, .45);
        transition: transform .15s, box-shadow .15s;
    }
    .ai-fab:hover { transform: scale(1.06); box-shadow: 0 10px 32px rgba(124, 58, 237, .55); }
    .ai-fab-panel {
        position: fixed;
        right: 22px;
        left: auto;
        bottom: 86px;
        z-index: 1055;
        width: min(380px, calc(100vw - 44px));
        max-height: 70vh;
        background: #fff;
        border: 1px solid var(--ai-border, #eef0f4);
        border-radius: 16px;
        box-shadow: 0 16px 48px rgba(15, 23, 42, .18);
        display: none;
        flex-direction: column;
        overflow: hidden;
    }
    .ai-fab-panel.is-open { display: flex; }
    .ai-fab-panel__head {
        padding: 12px 16px; border-bottom: 1px solid var(--ai-border, #eef0f4);
        display: flex; align-items: center; justify-content: space-between;
        background: linear-gradient(120deg, #5b21b6, #7c3aed); color: #fff;
    }
    .ai-fab-panel__body { flex: 1; overflow-y: auto; padding: 14px; background: #f7f8fb; min-height: 200px; max-height: 45vh; }
    .ai-fab-panel__foot { padding: 10px 12px; border-top: 1px solid var(--ai-border, #eef0f4); display: flex; gap: 8px; }
    .ai-fab-panel__foot input { flex: 1; border-radius: 10px; border: 1px solid #e5e7eb; padding: 8px 12px; font-size: .88rem; }
    .ai-fab-msg { font-size: .88rem; line-height: 1.5; margin-bottom: 10px; padding: 10px 12px; border-radius: 12px; }
    .ai-fab-msg.user { background: #eff6ff; margin-left: 24px; }
    .ai-fab-msg.bot { background: #fff; border: 1px solid #eef0f4; margin-right: 24px; }
    .ai-embed-btn {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: .82rem; font-weight: 600; padding: 5px 12px;
        border-radius: 999px; border: 1px solid #ddd6fe;
        background: #f5f3ff; color: #6d28d9; cursor: pointer;
        transition: background .12s, border-color .12s;
    }
    .ai-embed-btn:hover { background: #ede9fe; border-color: #c4b5fd; }
    .ai-embed-btn.is-loading { opacity: .65; pointer-events: none; }
    .ai-embed-btn i { font-size: 1rem; }
    .ai-embed-panel {
        background: transparent;
        border: none;
        border-radius: 12px;
        padding: 0;
        margin: 12px 0 0;
        font-size: .9rem;
        line-height: 1.55;
        display: none;
    }
    .ai-embed-panel.is-visible { display: block; }
    .ai-embed-panel:not(:empty) { display: block; }
    .ai-copilot-card {
        border-radius: 14px; border: 1px solid #ddd6fe;
        background: linear-gradient(135deg, #faf5ff 0%, #eff6ff 100%);
        padding: 16px 20px; margin-bottom: 18px;
    }
    .ai-copilot-card__head { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
    .ai-copilot-card__body { margin-top: 12px; white-space: pre-wrap; font-size: .92rem; line-height: 1.55; display: none; }
    .ai-copilot-card__body.is-visible { display: block; }
    [data-theme="dark"] .ai-fab-panel { background: #1e2746; border-color: #2b365c; }
    [data-theme="dark"] .ai-fab-panel__body { background: #172036; }
    [data-theme="dark"] .ai-fab-msg.bot { background: #243054; border-color: #2b365c; color: #e8ebf5; }
    [data-theme="dark"] .ai-embed-panel { background: #243054; border-color: #4c1d95; color: #e8ebf5; }
    [data-theme="dark"] .ai-copilot-card { background: #1e2746; border-color: #4c1d95; color: #e8ebf5; }
    @media (max-width: 575px) {
        .ai-fab { right: 16px; bottom: 16px; width: 48px; height: 48px; font-size: 1.2rem; }
        .ai-fab-panel { right: 16px; bottom: 76px; width: calc(100vw - 32px); }
    }
</style>
@endonce

<button type="button" class="ai-fab" id="ai-fab-toggle" title="{{ __('ai.widget_title') }}" aria-label="{{ __('ai.widget_title') }}">
    <i class="la la-magic"></i>
</button>

<div class="ai-fab-panel" id="ai-fab-panel" aria-hidden="true">
    <div class="ai-fab-panel__head">
        <span class="fw-bold"><i class="la la-magic me-1"></i> {{ __('ai.widget_title') }}</span>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('ai.assistant') }}" class="text-white small opacity-75" title="{{ __('ai.assistant_title') }}"><i class="la la-external-link"></i></a>
            <button type="button" class="btn btn-sm btn-link text-white p-0" id="ai-fab-close" aria-label="Close"><i class="la la-times fs-18"></i></button>
        </div>
    </div>
    <div class="ai-fab-panel__body" id="ai-fab-thread">
        <div class="ai-fab-msg bot">{{ __('ai.widget_greeting') }}</div>
    </div>
    <div class="ai-fab-panel__foot">
        <input type="text" id="ai-fab-input" placeholder="{{ __('ai.widget_placeholder') }}" autocomplete="off">
        <button type="button" class="btn btn-primary btn-sm" id="ai-fab-send"><i class="la la-paper-plane"></i></button>
    </div>
</div>

