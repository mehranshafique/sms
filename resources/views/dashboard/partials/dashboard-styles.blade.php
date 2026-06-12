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

    .dash-hero {
        background: linear-gradient(110deg, #2b2f6b 0%, #5b53e8 100%);
        border-radius: 16px;
        color: #fff;
        overflow: hidden;
        position: relative;
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
    }

    /* Clean stat card */
    .dash-stat {
        background: #fff;
        border: 1px solid var(--dash-border);
        border-radius: 14px;
        padding: 20px;
        height: 100%;
        transition: transform .18s ease, box-shadow .18s ease;
    }
    .dash-stat:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 24px rgba(31, 37, 51, 0.08);
    }
    .dash-stat__icon {
        width: 46px; height: 46px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    .dash-stat__label {
        color: var(--dash-muted);
        font-size: 13px;
        margin-bottom: 2px;
        font-weight: 500;
    }
    .dash-stat__value {
        color: var(--dash-ink);
        font-size: 24px;
        font-weight: 700;
        line-height: 1.1;
        margin: 0;
    }
    .dash-stat__hint { font-size: 12px; }

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

    /* Panel cards */
    .dash-panel {
        background: #fff;
        border: 1px solid var(--dash-border);
        border-radius: 14px;
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
    .dash-progress > span { display: block; height: 100%; border-radius: 6px; }

    .dash-mini-label { color: var(--dash-muted); font-size: 12px; font-weight: 500; }
    .dash-divider { border-color: var(--dash-border) !important; }

    [data-theme-version="dark"] .dash-stat,
    [data-theme-version="dark"] .dash-panel,
    [data-theme-version="dark"] .dash-link {
        background: var(--dz-card-bg, #1e2746);
        border-color: rgba(255,255,255,.06);
    }
    [data-theme-version="dark"] .dash-stat__value,
    [data-theme-version="dark"] .dash-panel__title,
    [data-theme-version="dark"] .dash-link__value { color: #fff; }
</style>
@endonce
