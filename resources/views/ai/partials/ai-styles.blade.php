@once
<style>
    :root {
        --ai-ink: #1f2937;
        --ai-muted: #6b7280;
        --ai-border: #eef0f4;
        --ai-bg: #f7f8fb;
        --ai-primary: #7c3aed;
    }
    .ai-hero {
        border-radius: 18px;
        background: linear-gradient(120deg, #5b21b6 0%, #7c3aed 45%, #2563eb 100%);
        position: relative;
        overflow: hidden;
    }
    .ai-hero::after {
        content: "";
        position: absolute;
        right: -40px; top: -60px;
        width: 220px; height: 220px;
        background: rgba(255,255,255,.08);
        border-radius: 50%;
    }
    .ai-hero__chip {
        display: inline-flex; align-items: center; gap: 6px;
        background: rgba(255,255,255,.18);
        color: #fff; font-weight: 600; font-size: .8rem;
        padding: 4px 12px; border-radius: 999px;
    }
    .ai-hero__icon { font-size: 3.4rem; color: rgba(255,255,255,.35); }

    .ai-panel {
        background: #fff;
        border: 1px solid var(--ai-border);
        border-radius: 16px;
    }
    .ai-panel__head {
        padding: 16px 20px;
        border-bottom: 1px solid var(--ai-border);
        display: flex; align-items: center; justify-content: space-between;
    }
    .ai-panel__body { padding: 20px; }

    .ai-quota-pill {
        display:inline-flex; align-items:center; gap:6px;
        font-size:.78rem; font-weight:600;
        padding:5px 12px; border-radius:999px;
        background:#f3effe; color:#6d28d9;
    }
    .ai-quota-pill.is-unlimited { background:#ecfdf5; color:#059669; }
    .ai-quota-pill.is-low { background:#fef2f2; color:#dc2626; }

    /* ---- Chat assistant ---- */
    .ai-chat-wrap { display:grid; grid-template-columns: 280px 1fr; gap:18px; }
    @media (max-width: 991px){ .ai-chat-wrap{ grid-template-columns: 1fr; } .ai-sidebar{ display:none; } }

    .ai-sidebar { background:#fff; border:1px solid var(--ai-border); border-radius:16px; padding:14px; height: 70vh; display:flex; flex-direction:column; }
    .ai-conv-list { overflow-y:auto; flex:1; margin-top:10px; }
    .ai-conv {
        display:flex; align-items:center; gap:10px;
        padding:10px 12px; border-radius:10px; color:var(--ai-ink);
        text-decoration:none; font-size:.88rem; margin-bottom:4px;
    }
    .ai-conv:hover { background:#f5f3ff; }
    .ai-conv.active { background:#ede9fe; color:#6d28d9; font-weight:600; }
    .ai-conv__title { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

    .ai-chat { background:#fff; border:1px solid var(--ai-border); border-radius:16px; height:70vh; display:flex; flex-direction:column; }
    .ai-thread { flex:1; overflow-y:auto; padding:22px; background:var(--ai-bg); }
    .ai-msg { display:flex; gap:12px; margin-bottom:18px; max-width:90%; }
    .ai-msg__avatar {
        width:36px; height:36px; border-radius:50%; flex-shrink:0;
        display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.1rem;
    }
    .ai-msg__bubble { background:#fff; border:1px solid var(--ai-border); border-radius:14px; padding:12px 16px; font-size:.92rem; line-height:1.55; color:var(--ai-ink); }
    .ai-msg.user { margin-left:auto; flex-direction:row-reverse; }
    .ai-msg.user .ai-msg__avatar { background:#2563eb; }
    .ai-msg.user .ai-msg__bubble { background:#eff6ff; border-color:#dbeafe; }
    .ai-msg.assistant .ai-msg__avatar { background:linear-gradient(135deg,#7c3aed,#5b21b6); }

    .ai-typing { display:inline-flex; gap:4px; align-items:center; }
    .ai-typing span { width:7px; height:7px; border-radius:50%; background:#a78bfa; animation: aiBlink 1.2s infinite both; }
    .ai-typing span:nth-child(2){ animation-delay:.2s; } .ai-typing span:nth-child(3){ animation-delay:.4s; }
    @keyframes aiBlink { 0%,80%,100%{ opacity:.2 } 40%{ opacity:1 } }

    .ai-composer { border-top:1px solid var(--ai-border); padding:14px; display:flex; gap:10px; align-items:flex-end; }
    .ai-composer textarea { resize:none; border-radius:12px; }

    /* ---- Studio ---- */
    .ai-tool-card { background:#fff; border:1px solid var(--ai-border); border-radius:14px; padding:18px; cursor:pointer; transition:.15s; height:100%; }
    .ai-tool-card:hover { border-color:#c4b5fd; box-shadow:0 6px 20px rgba(124,58,237,.08); transform:translateY(-2px); }
    .ai-tool-card.active { border-color:#7c3aed; background:#faf5ff; }
    .ai-tool-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.4rem; color:#fff; margin-bottom:12px; background:linear-gradient(135deg,#7c3aed,#5b21b6); }
    .ai-output { background:var(--ai-bg); border:1px dashed #d1d5db; border-radius:12px; padding:16px; min-height:160px; white-space:pre-wrap; font-size:.92rem; line-height:1.6; }

    /* Formatted AI embed output */
    .ai-embed-panel.is-visible { display: block; }
    .ai-embed-panel:empty { display: none; }
    .ai-output-view {
        background: linear-gradient(135deg, #faf5ff 0%, #f0f9ff 100%);
        border: 1px solid #ddd6fe;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 4px 18px rgba(124, 58, 237, .08);
    }
    .ai-output-view__head {
        display: flex; align-items: center; gap: 8px;
        padding: 10px 16px;
        background: linear-gradient(120deg, #5b21b6, #7c3aed);
        color: #fff; font-size: .82rem; font-weight: 600;
    }
    .ai-output-view__head i { font-size: 1.1rem; opacity: .95; }
    .ai-output-view__body { padding: 16px 18px; font-size: .92rem; line-height: 1.6; color: #1f2937; }
    .ai-output-view__body p.ai-output-p { margin: 0 0 10px; }
    .ai-output-view__body p.ai-output-p:last-child { margin-bottom: 0; }
    .ai-output-list { list-style: none; padding: 0; margin: 0; }
    .ai-output-list li {
        position: relative; padding: 10px 12px 10px 36px; margin-bottom: 8px;
        background: #fff; border-radius: 10px; border: 1px solid #ede9fe;
    }
    .ai-output-list li:last-child { margin-bottom: 0; }
    .ai-output-list li::before {
        content: ""; position: absolute; left: 12px; top: 14px;
        width: 8px; height: 8px; border-radius: 50%;
        background: linear-gradient(135deg, #7c3aed, #2563eb);
    }
    .ai-output-list li strong { color: #5b21b6; font-weight: 600; }

    [data-theme="dark"] .ai-output-view { background: linear-gradient(135deg, #1e2746, #172036); border-color: #4c1d95; }
    [data-theme="dark"] .ai-output-view__body { color: #e8ebf5; }
    [data-theme="dark"] .ai-output-list li { background: #243054; border-color: #374151; }
    [data-theme="dark"] .ai-output-list li strong { color: #c4b5fd; }

    [data-theme="dark"] .ai-panel,
    [data-theme="dark"] .ai-sidebar,
    [data-theme="dark"] .ai-chat,
    [data-theme="dark"] .ai-tool-card { background:#1e2746; border-color:#2b365c; color:#e8ebf5; }
    [data-theme="dark"] .ai-thread { background:#172036; }
    [data-theme="dark"] .ai-msg__bubble { background:#243054; border-color:#2b365c; color:#e8ebf5; }
    [data-theme="dark"] .ai-msg.user .ai-msg__bubble { background:#1e3a5f; border-color:#27496d; }
    [data-theme="dark"] .ai-output { background:#172036; border-color:#2b365c; color:#e8ebf5; }
</style>
@endonce
