{{-- Shared attendance terminal theme: bright by default, dark under [data-theme-version="dark"] --}}
<style>
    .att-terminal {
        --att-bg: #f4f8ff;
        --att-panel: #ffffff;
        --att-line: rgba(37, 99, 235, 0.14);
        --att-text: #0f172a;
        --att-muted: #64748b;
        --att-accent: #2563eb;
        --att-ok: #16a34a;
        --att-warn: #d97706;
        --att-danger: #dc2626;
        --att-info: #0284c7;
        --att-row: #ffffff;
        --att-input-bg: #ffffff;
        --att-chip-bg: #f8fafc;
        border-radius: 18px;
        overflow: hidden;
        background:
            radial-gradient(circle at top right, rgba(37,99,235,.10), transparent 42%),
            linear-gradient(160deg, #f8fbff, #eef4ff 70%);
        color: var(--att-text);
        box-shadow: 0 10px 28px rgba(15, 23, 42, .08);
        border: 1px solid rgba(37, 99, 235, .14);
    }
    .att-terminal .att-edge {
        height: 4px;
        background: linear-gradient(90deg, var(--att-ok), #4ade80, var(--att-ok));
        box-shadow: 0 0 12px rgba(34,197,94,.35);
    }
    .att-terminal .att-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--att-line);
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        align-items: center;
    }
    .att-terminal .att-kicker {
        text-transform: uppercase;
        letter-spacing: .12em;
        font-size: .72rem;
        color: var(--att-muted);
        margin-bottom: .25rem;
    }
    .att-terminal h4,
    .att-terminal .card-title { color: var(--att-text); }
    .att-terminal .att-body { padding: 1.25rem 1.5rem 1.5rem; }
    .att-terminal .form-label { color: var(--att-muted); font-size: .85rem; }
    .att-terminal .form-control,
    .att-terminal .bootstrap-select .dropdown-toggle {
        background: var(--att-input-bg) !important;
        border-color: rgba(100, 116, 139, .28) !important;
        color: var(--att-text) !important;
    }
    .att-terminal .input-group-text {
        background: #f1f5f9;
        border-color: rgba(100, 116, 139, .28);
        color: #475569;
    }
    .att-terminal .btn-scan {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        border: 0;
        color: #fff;
        font-weight: 600;
        box-shadow: 0 8px 20px rgba(37,99,235,.25);
    }
    .att-terminal .btn-ghost {
        background: #eff6ff;
        border: 1px solid rgba(37,99,235,.2);
        color: #1e40af;
    }
    .att-terminal .btn-ghost:hover { background: #dbeafe; color: #1e3a8a; }
    .att-roster-row {
        display: grid;
        gap: .85rem;
        align-items: center;
        padding: .9rem 1rem;
        border: 1px solid var(--att-line);
        border-radius: 14px;
        background: var(--att-row);
        margin-bottom: .65rem;
        transition: border-color .15s ease, transform .15s ease;
    }
    .att-roster-row:hover { border-color: rgba(37,99,235,.35); transform: translateY(-1px); }
    .att-roster-row.is-present { box-shadow: inset 3px 0 0 var(--att-ok); }
    .att-roster-row.is-absent { box-shadow: inset 3px 0 0 var(--att-danger); }
    .att-roster-row.is-late { box-shadow: inset 3px 0 0 var(--att-warn); }
    .att-roster-row.is-excused,
    .att-roster-row.is-half_day { box-shadow: inset 3px 0 0 var(--att-info); }
    .att-name { font-weight: 700; color: var(--att-text); }
    .att-meta { color: var(--att-muted); font-size: .8rem; }
    .att-status-group { display: flex; flex-wrap: wrap; gap: .35rem; }
    .att-chip { position: relative; margin: 0; }
    .att-chip input { position: absolute; opacity: 0; pointer-events: none; }
    .att-chip label {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 42px; padding: .4rem .55rem; border-radius: 999px;
        border: 1px solid rgba(100,116,139,.25); color: var(--att-muted);
        font-weight: 700; font-size: .72rem; cursor: pointer; margin: 0;
        background: var(--att-chip-bg);
    }
    .att-chip input:checked + label[data-s="present"],
    .att-chip input:checked + label.att-p { background: rgba(22,163,74,.12); border-color: var(--att-ok); color: #15803d; }
    .att-chip input:checked + label[data-s="absent"],
    .att-chip input:checked + label.att-a { background: rgba(220,38,38,.12); border-color: var(--att-danger); color: #b91c1c; }
    .att-chip input:checked + label[data-s="late"],
    .att-chip input:checked + label.att-l { background: rgba(217,119,6,.12); border-color: var(--att-warn); color: #b45309; }
    .att-chip input:checked + label[data-s="excused"],
    .att-chip input:checked + label[data-s="half_day"],
    .att-chip input:checked + label.att-e { background: rgba(2,132,199,.12); border-color: var(--att-info); color: #0369a1; }
    .att-live {
        display: inline-flex; align-items: center; gap: .4rem;
        color: #15803d; font-size: .78rem; font-weight: 600;
        text-transform: uppercase; letter-spacing: .08em;
    }
    .att-live::before {
        content: ''; width: 8px; height: 8px; border-radius: 50%;
        background: var(--att-ok); box-shadow: 0 0 10px rgba(22,163,74,.55);
        animation: attPulse 1.4s infinite;
    }
    .att-count { color: var(--att-muted); font-size: .85rem; }
    .att-count strong { color: var(--att-text); }
    .att-footer {
        display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;
        align-items: center; margin-top: 1rem; padding-top: 1rem;
        border-top: 1px solid var(--att-line);
    }
    .att-avatar {
        width: 48px; height: 48px; border-radius: 12px; object-fit: cover;
        border: 2px solid rgba(37,99,235,.25); background: #e2e8f0;
    }
    .att-avatar-fallback {
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; color: #1d4ed8; font-size: .9rem;
    }
    @keyframes attPulse { 0%,100%{opacity:1} 50%{opacity:.35} }

    /* Dark mode — keep the previous terminal look */
    [data-theme-version="dark"] .att-terminal {
        --att-bg: #0b1b33;
        --att-panel: #122744;
        --att-line: rgba(120, 170, 255, 0.18);
        --att-text: #e8f0ff;
        --att-muted: #8aa0c0;
        --att-accent: #2f80ed;
        --att-ok: #22c55e;
        --att-warn: #f59e0b;
        --att-danger: #ef4444;
        --att-info: #38bdf8;
        --att-row: rgba(18,39,68,.72);
        --att-input-bg: rgba(255,255,255,.06);
        --att-chip-bg: rgba(255,255,255,.03);
        background:
            radial-gradient(circle at top right, rgba(47,128,237,.22), transparent 42%),
            linear-gradient(160deg, var(--att-bg), #081528 70%);
        box-shadow: 0 16px 40px rgba(8, 21, 40, .28);
        border-color: rgba(90,140,230,.25);
    }
    [data-theme-version="dark"] .att-terminal h4,
    [data-theme-version="dark"] .att-terminal .card-title { color: #fff; }
    [data-theme-version="dark"] .att-terminal .input-group-text {
        background: rgba(255,255,255,.08);
        border-color: rgba(140,170,220,.28);
        color: #9ec1ff;
    }
    [data-theme-version="dark"] .att-terminal .btn-ghost {
        background: rgba(255,255,255,.06);
        border: 1px solid rgba(140,170,220,.25);
        color: #cfe0ff;
    }
    [data-theme-version="dark"] .att-terminal .btn-ghost:hover { background: rgba(255,255,255,.12); color: #fff; }
    [data-theme-version="dark"] .att-chip input:checked + label[data-s="present"],
    [data-theme-version="dark"] .att-chip input:checked + label.att-p { background: rgba(34,197,94,.18); color: #86efac; }
    [data-theme-version="dark"] .att-chip input:checked + label[data-s="absent"],
    [data-theme-version="dark"] .att-chip input:checked + label.att-a { background: rgba(239,68,68,.18); color: #fca5a5; }
    [data-theme-version="dark"] .att-chip input:checked + label[data-s="late"],
    [data-theme-version="dark"] .att-chip input:checked + label.att-l { background: rgba(245,158,11,.18); color: #fcd34d; }
    [data-theme-version="dark"] .att-chip input:checked + label[data-s="excused"],
    [data-theme-version="dark"] .att-chip input:checked + label[data-s="half_day"],
    [data-theme-version="dark"] .att-chip input:checked + label.att-e { background: rgba(56,189,248,.18); color: #7dd3fc; }
    [data-theme-version="dark"] .att-live { color: #86efac; }
    [data-theme-version="dark"] .att-name { color: #fff; }
    [data-theme-version="dark"] .att-count strong { color: #fff; }
    [data-theme-version="dark"] .att-avatar-fallback { color: #9ec1ff; background: #0a1730; }
</style>
