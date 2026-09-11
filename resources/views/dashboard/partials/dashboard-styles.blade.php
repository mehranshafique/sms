{{-- Shared refined dashboard styling — clean, low-noise palette --}}
@once
<style>
    :root {
        --dash-ink: #1f2533;
        --dash-muted: #8a93a6;
        --dash-border: #eef1f6;
        --dash-primary: #5b53e8;
        --dash-success: #2bb673;
        --dash-warning: #f5a623;
        --dash-danger: #ef5675;
        --dash-info: #2aa9e0;
    }

    @keyframes dash-fade-up {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes dash-pulse-soft {
        0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(91, 83, 232, 0.25); }
        50% { transform: scale(1.04); box-shadow: 0 0 0 8px rgba(91, 83, 232, 0); }
    }
    @keyframes dash-shimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    @keyframes dash-rate-glow {
        0%, 100% { opacity: 1; }
        50% { opacity: .72; }
    }

    .dash-hero {
        background: linear-gradient(110deg, #2b2f6b 0%, #5b53e8 55%, #7b74f0 100%);
        border-radius: 16px;
        color: #fff;
        overflow: hidden;
        position: relative;
        animation: dash-fade-up .45s ease both;
    }
    .dash-hero::after {
        content: "";
        position: absolute;
        right: -40px; top: -60px;
        width: 220px; height: 220px;
        background: rgba(255, 255, 255, 0.08);
        border-radius: 50%;
    }
    .dash-hero::before {
        content: "";
        position: absolute;
        right: 70px; bottom: -90px;
        width: 160px; height: 160px;
        background: rgba(255, 255, 255, 0.06);
        border-radius: 50%;
    }
    .dash-hero .dash-hero__chip {
        background: rgba(255, 255, 255, 0.16);
        border-radius: 30px;
        padding: 4px 14px;
        font-size: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        backdrop-filter: blur(4px);
    }

    /* Clean stat card */
    .dash-stat {
        background: #fff;
        border: 1px solid var(--dash-border);
        border-radius: 14px;
        padding: 18px 18px 14px;
        height: 100%;
        position: relative;
        overflow: hidden;
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        animation: dash-fade-up .5s ease both;
    }
    .dash-stat::before {
        content: "";
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 4px;
        background: var(--dash-primary);
        opacity: .9;
    }
    .dash-stat.tint-edge-success::before { background: var(--dash-success); }
    .dash-stat.tint-edge-warning::before { background: var(--dash-warning); }
    .dash-stat.tint-edge-danger::before  { background: var(--dash-danger); }
    .dash-stat.tint-edge-info::before    { background: var(--dash-info); }
    .dash-stat.tint-edge-primary::before { background: var(--dash-primary); }
    .dash-stat.tint-edge-dark::before    { background: var(--dash-ink); }

    .dash-stat:hover {
        transform: translateY(-4px);
        box-shadow: 0 14px 28px rgba(31, 37, 51, 0.1);
        border-color: rgba(91, 83, 232, .22);
    }
    .dash-stat__icon {
        width: 46px; height: 46px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
        animation: dash-pulse-soft 2.8s ease-in-out infinite;
    }
    .dash-stat__label {
        color: var(--dash-muted);
        font-size: 13px;
        margin-bottom: 2px;
        font-weight: 500;
    }
    .dash-stat__value {
        color: var(--dash-ink);
        font-size: 26px;
        font-weight: 700;
        line-height: 1.1;
        margin: 0;
        letter-spacing: -0.02em;
    }
    .dash-stat__hint { font-size: 12px; }
    .dash-stat__meter {
        margin-top: 12px;
        height: 6px;
        border-radius: 6px;
        background: var(--dash-border);
        overflow: hidden;
    }
    .dash-stat__meter > span {
        display: block;
        height: 100%;
        border-radius: 6px;
        min-width: 0;
        transition: width .6s ease;
        background: linear-gradient(90deg, rgba(255,255,255,.15), rgba(255,255,255,0) 40%, rgba(255,255,255,.15)), var(--dash-primary);
        background-size: 200% 100%;
        animation: dash-shimmer 2.4s linear infinite;
    }
    .dash-stat.tint-edge-success .dash-stat__meter > span { background-color: var(--dash-success); }
    .dash-stat.tint-edge-warning .dash-stat__meter > span { background-color: var(--dash-warning); }
    .dash-stat.tint-edge-danger .dash-stat__meter > span  { background-color: var(--dash-danger); }
    .dash-stat.tint-edge-info .dash-stat__meter > span    { background-color: var(--dash-info); }
    .dash-stat.tint-edge-primary .dash-stat__meter > span { background-color: var(--dash-primary); }

    .dash-stat-grid > [class*="col-"]:nth-child(1) .dash-stat { animation-delay: .05s; }
    .dash-stat-grid > [class*="col-"]:nth-child(2) .dash-stat { animation-delay: .1s; }
    .dash-stat-grid > [class*="col-"]:nth-child(3) .dash-stat { animation-delay: .15s; }
    .dash-stat-grid > [class*="col-"]:nth-child(4) .dash-stat { animation-delay: .2s; }
    .dash-stat-grid > [class*="col-"]:nth-child(5) .dash-stat { animation-delay: .25s; }
    .dash-stat-grid > [class*="col-"]:nth-child(6) .dash-stat { animation-delay: .3s; }

    /* Soft icon tints */
    .tint-primary { background: rgba(91, 83, 232, .12); color: var(--dash-primary); }
    .tint-success { background: rgba(43, 182, 115, .12); color: var(--dash-success); }
    .tint-warning { background: rgba(245, 166, 35, .14); color: var(--dash-warning); }
    .tint-danger  { background: rgba(239, 86, 117, .12); color: var(--dash-danger); }
    .tint-info    { background: rgba(42, 169, 224, .12); color: var(--dash-info); }
    .tint-dark    { background: rgba(31, 37, 51, .08);  color: var(--dash-ink); }

    .text-tint-primary { color: var(--dash-primary) !important; }
    .text-tint-success { color: var(--dash-success) !important; }
    .text-tint-warning { color: var(--dash-warning) !important; }
    .text-tint-danger  { color: var(--dash-danger) !important; }
    .text-tint-info    { color: var(--dash-info) !important; }

    .dash-live-badge {
        background: rgba(91,83,232,.12);
        color: var(--dash-primary);
        animation: dash-rate-glow 2.2s ease-in-out infinite;
    }
    .dash-live-badge.is-good {
        background: rgba(43,182,115,.14);
        color: var(--dash-success);
    }
    .dash-live-badge.is-warn {
        background: rgba(245,166,35,.16);
        color: #c47d00;
    }
    .dash-live-badge.is-bad {
        background: rgba(239,86,117,.14);
        color: var(--dash-danger);
    }

    .dash-metric-chip {
        background: linear-gradient(180deg, #fff 0%, #f8f9fc 100%);
        border: 1px solid var(--dash-border);
        border-radius: 12px;
        padding: 10px 8px;
        text-align: center;
        height: 100%;
        transition: transform .18s ease, box-shadow .18s ease;
    }
    .dash-metric-chip:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(31, 37, 51, 0.07);
    }
    .dash-metric-chip__value {
        font-size: 20px;
        font-weight: 700;
        line-height: 1.1;
        color: var(--dash-ink);
    }
    .dash-metric-chip__label {
        display: block;
        margin-top: 4px;
        font-size: 11px;
        color: var(--dash-muted);
        font-weight: 500;
    }

    /* Panel cards */
    .dash-panel {
        background: #fff;
        border: 1px solid var(--dash-border);
        border-radius: 14px;
        animation: dash-fade-up .55s ease both;
    }
    .dash-panel__head {
        padding: 18px 20px 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .dash-panel__title {
        font-size: 15px;
        font-weight: 700;
        color: var(--dash-ink);
        margin: 0;
    }
    .dash-panel__body { padding: 16px 20px 20px; }

    /* Quick-link tile (replaces saturated cards) */
    .dash-link {
        display: flex;
        align-items: center;
        gap: 14px;
        background: #fff;
        border: 1px solid var(--dash-border);
        border-radius: 14px;
        padding: 16px 18px;
        height: 100%;
        text-decoration: none;
        transition: border-color .18s ease, transform .18s ease, box-shadow .18s ease;
        animation: dash-fade-up .5s ease both;
    }
    .dash-link:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 24px rgba(31, 37, 51, 0.08);
        border-color: rgba(91, 83, 232, .35);
    }
    .dash-link__value { color: var(--dash-ink); font-weight: 700; font-size: 18px; line-height: 1; }
    .dash-link__label { color: var(--dash-muted); font-size: 12.5px; font-weight: 500; }

    .dash-progress {
        height: 8px;
        border-radius: 6px;
        background: var(--dash-border);
        overflow: hidden;
    }
    .dash-progress > span { display: block; height: 100%; border-radius: 6px; transition: width .7s ease; }

    .dash-mini-label { color: var(--dash-muted); font-size: 12px; font-weight: 500; }
    .dash-divider { border-color: var(--dash-border) !important; }

    [data-theme-version="dark"] .dash-stat,
    [data-theme-version="dark"] .dash-panel,
    [data-theme-version="dark"] .dash-link,
    [data-theme-version="dark"] .dash-metric-chip {
        background: var(--dz-card-bg, #1e2746);
        border-color: rgba(255,255,255,.06);
    }
    [data-theme-version="dark"] .dash-stat__value,
    [data-theme-version="dark"] .dash-panel__title,
    [data-theme-version="dark"] .dash-link__value,
    [data-theme-version="dark"] .dash-metric-chip__value { color: #fff; }
    [data-theme-version="dark"] .dash-stat__meter,
    [data-theme-version="dark"] .dash-progress { background: rgba(255,255,255,.08); }

    /* Dashboard: AI copilot sits directly above welcome banner */
    .dashboard-ai-slot {
        padding: 30px 30px 0;
    }
    @media only screen and (max-width: 575px) {
        .dashboard-ai-slot { padding: 15px 15px 0; }
    }
    .content-body > .content-body > .container-fluid {
        padding-top: 0;
    }
    #ai-dashboard-copilot.ai-copilot-card {
        margin-bottom: 0.75rem;
    }
    .dashboard-welcome-row {
        margin-top: 0;
        margin-bottom: 1.25rem;
    }

    @media (prefers-reduced-motion: reduce) {
        .dash-stat, .dash-panel, .dash-link, .dash-hero,
        .dash-stat__icon, .dash-live-badge, .dash-stat__meter > span {
            animation: none !important;
        }
    }
</style>
@endonce
