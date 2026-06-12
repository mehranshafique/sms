@once
<style>
    :root {
        --sp-ink: #1f2533;
        --sp-muted: #8a93a6;
        --sp-border: #eef1f6;
        --sp-bg: #f7f8fc;
        --sp-primary: #5b53e8;
        --sp-success: #2bb673;
        --sp-warning: #f5a623;
        --sp-danger: #ef5675;
        --sp-info: #2aa9e0;
    }

    /* Hero */
    .sp-hero {
        background: linear-gradient(110deg, #2b2f6b 0%, #5b53e8 100%);
        border-radius: 16px;
        color: #fff;
        overflow: hidden;
        position: relative;
    }
    .sp-hero::after {
        content: ""; position: absolute; right: -40px; top: -60px;
        width: 220px; height: 220px; background: rgba(255,255,255,.08); border-radius: 50%;
    }
    .sp-hero__icon { font-size: 3.4rem; opacity: .25; }

    /* Stat chips */
    .sp-stat {
        background: #fff; border: 1px solid var(--sp-border); border-radius: 14px;
        padding: 16px 18px; height: 100%;
        transition: transform .18s ease, box-shadow .18s ease;
    }
    .sp-stat:hover { transform: translateY(-3px); box-shadow: 0 12px 24px rgba(31,37,51,.08); }
    .sp-stat__icon {
        width: 44px; height: 44px; border-radius: 12px; display: inline-flex;
        align-items: center; justify-content: center; font-size: 19px;
    }
    .sp-stat__value { font-size: 22px; font-weight: 700; color: var(--sp-ink); line-height: 1; }
    .sp-stat__label { font-size: 12.5px; color: var(--sp-muted); }

    .tint-primary { background: rgba(91,83,232,.12); color: var(--sp-primary); }
    .tint-success { background: rgba(43,182,115,.12); color: var(--sp-success); }
    .tint-warning { background: rgba(245,166,35,.14); color: var(--sp-warning); }
    .tint-danger  { background: rgba(239,86,117,.12); color: var(--sp-danger); }
    .tint-info    { background: rgba(42,169,224,.12); color: var(--sp-info); }

    /* Panels */
    .sp-panel { background: #fff; border: 1px solid var(--sp-border); border-radius: 14px; }

    /* Ticket list rows */
    .sp-ticket {
        display: flex; align-items: center; gap: 14px;
        padding: 16px 18px; border: 1px solid var(--sp-border); border-radius: 14px;
        background: #fff; text-decoration: none; margin-bottom: 12px;
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
    }
    .sp-ticket:hover { transform: translateY(-2px); box-shadow: 0 10px 22px rgba(31,37,51,.07); border-color: rgba(91,83,232,.3); }
    .sp-ticket.is-unread { border-left: 4px solid var(--sp-primary); }
    .sp-ticket__avatar {
        width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 16px;
    }
    .sp-ticket__subject { font-weight: 700; color: var(--sp-ink); margin: 0; font-size: 14.5px; }
    .sp-ticket__meta { font-size: 12px; color: var(--sp-muted); }

    /* Status pill */
    .sp-pill {
        display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 600;
        padding: 4px 10px; border-radius: 30px; text-transform: capitalize; white-space: nowrap;
    }
    .sp-pill .dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
    .pill-open    { background: rgba(42,169,224,.12);  color: var(--sp-info); }
    .pill-pending { background: rgba(245,166,35,.14);  color: #c47d10; }
    .pill-answered{ background: rgba(91,83,232,.12);   color: var(--sp-primary); }
    .pill-resolved{ background: rgba(43,182,115,.12);  color: var(--sp-success); }
    .pill-closed  { background: rgba(138,147,166,.16); color: #5b6373; }

    /* Priority dot */
    .sp-prio { font-size: 11.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
    .sp-prio .dot { width: 8px; height: 8px; border-radius: 50%; }
    .prio-low .dot    { background: var(--sp-muted); }
    .prio-medium .dot { background: var(--sp-info); }
    .prio-high .dot   { background: var(--sp-warning); }
    .prio-urgent .dot { background: var(--sp-danger); }
    .prio-urgent { color: var(--sp-danger); }

    /* ===== Chat layout ===== */
    .sp-chat { display: grid; grid-template-columns: 1fr 320px; gap: 18px; }
    @media (max-width: 991px) { .sp-chat { grid-template-columns: 1fr; } }

    .sp-conversation { display: flex; flex-direction: column; height: 72vh; min-height: 480px; }
    .sp-conversation__head {
        padding: 16px 20px; border-bottom: 1px solid var(--sp-border);
        display: flex; align-items: center; justify-content: space-between; gap: 10px;
    }
    .sp-thread {
        flex: 1; overflow-y: auto; padding: 22px 20px; background: var(--sp-bg);
        background-image: radial-gradient(rgba(91,83,232,.05) 1px, transparent 1px);
        background-size: 22px 22px;
    }
    .sp-thread::-webkit-scrollbar { width: 8px; }
    .sp-thread::-webkit-scrollbar-thumb { background: #d7dbe7; border-radius: 8px; }

    .sp-msg { display: flex; gap: 10px; margin-bottom: 18px; max-width: 78%; }
    .sp-msg__avatar {
        width: 38px; height: 38px; border-radius: 50%; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;
        background: rgba(91,83,232,.12); color: var(--sp-primary);
    }
    .sp-msg__bubble {
        background: #fff; border: 1px solid var(--sp-border); border-radius: 4px 16px 16px 16px;
        padding: 11px 15px; box-shadow: 0 1px 2px rgba(31,37,51,.04);
    }
    .sp-msg__name { font-size: 12px; font-weight: 700; color: var(--sp-ink); margin-bottom: 2px; }
    .sp-msg__name .badge-agent {
        background: var(--sp-primary); color: #fff; font-size: 9.5px; font-weight: 700;
        padding: 1px 7px; border-radius: 20px; margin-left: 6px; text-transform: uppercase; letter-spacing: .03em;
    }
    .sp-msg__body { font-size: 14px; color: #2c3344; white-space: pre-wrap; word-break: break-word; line-height: 1.5; }
    .sp-msg__time { font-size: 10.5px; color: var(--sp-muted); margin-top: 4px; }
    .sp-msg__file {
        display: inline-flex; align-items: center; gap: 7px; margin-top: 8px; font-size: 12.5px;
        background: rgba(91,83,232,.07); border: 1px solid var(--sp-border); border-radius: 8px; padding: 6px 10px;
        color: var(--sp-primary); text-decoration: none;
    }
    .sp-msg__file img { max-width: 220px; border-radius: 8px; display: block; }

    /* Own (right-aligned) message */
    .sp-msg.is-own { margin-left: auto; flex-direction: row-reverse; }
    .sp-msg.is-own .sp-msg__avatar { background: rgba(43,182,115,.14); color: var(--sp-success); }
    .sp-msg.is-own .sp-msg__bubble {
        background: var(--sp-primary); border-color: var(--sp-primary); border-radius: 16px 4px 16px 16px;
    }
    .sp-msg.is-own .sp-msg__name,
    .sp-msg.is-own .sp-msg__body { color: #fff; }
    .sp-msg.is-own .sp-msg__time { color: rgba(255,255,255,.6); text-align: right; }
    .sp-msg.is-own .sp-msg__file { background: rgba(255,255,255,.16); border-color: rgba(255,255,255,.25); color: #fff; }

    /* System note */
    .sp-msg.is-system { max-width: 100%; justify-content: center; }
    .sp-sysnote {
        background: rgba(138,147,166,.12); color: #5b6373; font-size: 12px;
        padding: 6px 14px; border-radius: 30px; margin: 0 auto;
    }

    /* Composer */
    .sp-composer { padding: 14px 18px; border-top: 1px solid var(--sp-border); background: #fff; }
    .sp-composer textarea {
        border: 1px solid var(--sp-border); border-radius: 12px; resize: none; padding: 11px 14px;
        font-size: 14px; width: 100%; outline: none; transition: border-color .15s ease;
    }
    .sp-composer textarea:focus { border-color: var(--sp-primary); }
    .sp-send-btn {
        background: var(--sp-primary); color: #fff; border: none; border-radius: 12px;
        width: 46px; height: 46px; display: inline-flex; align-items: center; justify-content: center;
        font-size: 18px; transition: background .15s ease;
    }
    .sp-send-btn:hover { background: #4a43d6; color: #fff; }
    .sp-attach-btn {
        width: 46px; height: 46px; border-radius: 12px; border: 1px solid var(--sp-border);
        background: #fff; color: var(--sp-muted); display: inline-flex; align-items: center; justify-content: center; font-size: 18px;
    }
    .sp-attach-btn:hover { color: var(--sp-primary); border-color: var(--sp-primary); }
    .sp-attach-name { font-size: 12px; color: var(--sp-primary); }

    /* Info sidebar */
    .sp-info-row { display: flex; justify-content: space-between; padding: 11px 0; border-bottom: 1px solid var(--sp-border); font-size: 13px; }
    .sp-info-row:last-child { border-bottom: none; }
    .sp-info-row .label { color: var(--sp-muted); }
    .sp-info-row .value { color: var(--sp-ink); font-weight: 600; text-align: right; }

    .sp-empty { text-align: center; padding: 60px 20px; color: var(--sp-muted); }
    .sp-empty i { font-size: 56px; opacity: .4; }

    .sp-filter-btn {
        font-size: 12.5px; font-weight: 600; padding: 7px 14px; border-radius: 30px;
        border: 1px solid var(--sp-border); background: #fff; color: var(--sp-muted); text-decoration: none; white-space: nowrap;
    }
    .sp-filter-btn.active { background: var(--sp-primary); border-color: var(--sp-primary); color: #fff; }

    [data-theme-version="dark"] .sp-stat,
    [data-theme-version="dark"] .sp-panel,
    [data-theme-version="dark"] .sp-ticket,
    [data-theme-version="dark"] .sp-msg__bubble:not(.is-own .sp-msg__bubble),
    [data-theme-version="dark"] .sp-composer { background: var(--dz-card-bg, #1e2746); border-color: rgba(255,255,255,.07); }
    [data-theme-version="dark"] .sp-stat__value,
    [data-theme-version="dark"] .sp-ticket__subject { color: #fff; }
    [data-theme-version="dark"] .sp-thread { background: #141a33; }
</style>
@endonce
