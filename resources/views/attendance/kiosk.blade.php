<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#071529">
    <title>{{ __('attendance.kiosk_title') }} — {{ $institutionName }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --bg: #071529;
            --panel: #0d213d;
            --line: rgba(112, 168, 255, 0.22);
            --text: #eaf2ff;
            --muted: #8aa4c8;
            --accent: #2f80ed;
            --ok: #22c55e;
            --bad: #ef4444;
            --warn: #f59e0b;
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            min-height: 100%;
            background: var(--bg);
            color: var(--text);
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
            overflow: hidden;
        }
        .kiosk {
            min-height: 100vh;
            min-height: 100dvh;
            padding: 18px;
            display: flex;
            flex-direction: column;
            background:
                radial-gradient(circle at 15% 20%, rgba(47,128,237,.18), transparent 35%),
                radial-gradient(circle at 85% 75%, rgba(34,197,94,.08), transparent 30%),
                linear-gradient(160deg, #071529, #0a1a33 60%, #061224);
            border: 4px solid transparent;
            transition: border-color .25s ease, box-shadow .25s ease;
        }
        .kiosk.state-idle { border-color: rgba(47,128,237,.45); box-shadow: inset 0 0 40px rgba(47,128,237,.12); }
        .kiosk.state-ok { border-color: var(--ok); box-shadow: 0 0 0 3px rgba(34,197,94,.35), inset 0 0 50px rgba(34,197,94,.16); }
        .kiosk.state-bad { border-color: var(--bad); box-shadow: 0 0 0 3px rgba(239,68,68,.35), inset 0 0 50px rgba(239,68,68,.14); }
        .kiosk.state-warn { border-color: var(--warn); box-shadow: 0 0 0 3px rgba(245,158,11,.35), inset 0 0 50px rgba(245,158,11,.14); }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }
        .brand { display: flex; align-items: center; gap: 12px; }
        .logo-dot {
            width: 42px; height: 42px; border-radius: 50%;
            background: linear-gradient(135deg, #2563eb, #38bdf8);
            display: grid; place-items: center; font-weight: 800; color: #fff;
            box-shadow: 0 0 20px rgba(37,99,235,.45);
        }
        .brand h1 { margin: 0; font-size: 1.15rem; }
        .brand p { margin: 2px 0 0; color: var(--muted); font-size: .82rem; }
        .clock { text-align: right; color: var(--muted); font-variant-numeric: tabular-nums; }
        .clock strong { display: block; color: #fff; font-size: 1.35rem; }

        .stage {
            flex: 1;
            display: grid;
            grid-template-columns: 1.05fr 1fr;
            gap: 18px;
            min-height: 0;
        }
        .panel {
            background: rgba(13, 33, 61, .88);
            border: 1px solid var(--line);
            border-radius: 22px;
            padding: 18px;
            min-height: 0;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }
        .panel::before {
            content: "";
            position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(80,140,230,.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(80,140,230,.05) 1px, transparent 1px);
            background-size: 28px 28px;
            pointer-events: none;
        }
        .panel > * { position: relative; z-index: 1; }

        .idle-copy h2 {
            margin: 0 0 10px;
            font-size: clamp(1.4rem, 2.4vw, 2rem);
            line-height: 1.2;
        }
        .idle-copy p { margin: 0; color: var(--muted); font-size: 1rem; }
        .nfc-hero {
            flex: 1;
            display: grid;
            place-items: center;
            text-align: center;
            color: #9ec1ff;
        }
        .nfc-ring {
            width: min(220px, 46vw);
            height: min(220px, 46vw);
            border-radius: 50%;
            border: 2px dashed rgba(110,160,240,.45);
            display: grid;
            place-items: center;
            animation: pulse 2.2s infinite;
        }
        .nfc-ring i { font-size: 4.2rem; opacity: .9; }
        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(47,128,237,.35); }
            50% { transform: scale(1.03); box-shadow: 0 0 0 18px rgba(47,128,237,0); }
        }

        .result {
            flex: 1;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 10px;
        }
        .result.show { display: flex; }
        .photo-frame {
            width: min(240px, 55vw);
            aspect-ratio: 1;
            border-radius: 18px;
            border: 3px solid rgba(120,170,255,.45);
            overflow: hidden;
            background: #091828;
            box-shadow: 0 10px 40px rgba(0,0,0,.35);
        }
        .photo-frame img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .photo-fallback {
            width: 100%; height: 100%;
            display: grid; place-items: center;
            font-size: 3rem; font-weight: 800; color: #9ec1ff;
            background: linear-gradient(160deg, #123054, #0a1c34);
        }
        .scan-badge {
            width: 64px; height: 64px; border-radius: 50%;
            display: grid; place-items: center;
            margin-top: -28px;
            border: 3px solid #0b1d36;
            background: var(--ok);
            color: #fff;
            font-size: 1.4rem;
            box-shadow: 0 0 24px rgba(34,197,94,.55);
        }
        .scan-badge.is-bad { background: var(--bad); box-shadow: 0 0 24px rgba(239,68,68,.55); }
        .scan-badge.is-warn { background: var(--warn); box-shadow: 0 0 24px rgba(245,158,11,.55); }
        .person-name { margin: 8px 0 0; font-size: clamp(1.5rem, 3vw, 2.2rem); font-weight: 800; }
        .person-meta { color: var(--muted); font-size: .95rem; }
        .action-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: .45rem .9rem;
            border-radius: 999px;
            background: rgba(34,197,94,.15);
            border: 1px solid rgba(34,197,94,.45);
            color: #86efac;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            font-size: .78rem;
        }
        .msg { margin-top: 4px; font-size: 1.05rem; }

        .cam-wrap {
            flex: 1;
            min-height: 220px;
            border-radius: 16px;
            overflow: hidden;
            border: 1px dashed rgba(140,170,220,.35);
            background: #081526;
        }
        #reader { width: 100%; height: 100%; }
        #reader video { object-fit: cover !important; }
        .manual {
            margin-top: 12px;
            display: flex;
            gap: 8px;
        }
        .manual input {
            flex: 1;
            border-radius: 12px;
            border: 1px solid rgba(140,170,220,.3);
            background: rgba(255,255,255,.05);
            color: #fff;
            padding: .85rem 1rem;
            font-size: 1rem;
        }
        .manual input:focus { outline: 2px solid rgba(47,128,237,.55); border-color: transparent; }
        .manual button, .ghost-btn {
            border: 0;
            border-radius: 12px;
            padding: .85rem 1.1rem;
            font-weight: 700;
            cursor: pointer;
        }
        .manual button {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
        }
        .foot {
            margin-top: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .ghost-btn {
            background: rgba(255,255,255,.06);
            color: #cfe0ff;
            border: 1px solid rgba(140,170,220,.25);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .status-line {
            color: var(--muted);
            font-size: .85rem;
            min-height: 1.2em;
        }
        .hint { color: var(--muted); font-size: .8rem; margin-top: 8px; }

        @media (max-width: 900px) {
            body { overflow: auto; }
            .stage { grid-template-columns: 1fr; }
            .kiosk { overflow: auto; }
            .nfc-ring { width: 150px; height: 150px; }
            .nfc-ring i { font-size: 2.8rem; }
        }
    </style>
</head>
<body>
<div class="kiosk state-idle" id="kioskShell">
    <div class="topbar">
        <div class="brand">
            <div class="logo-dot">DT</div>
            <div>
                <h1>{{ __('attendance.kiosk_title') }}</h1>
                <p>{{ $institutionName }}</p>
            </div>
        </div>
        <div class="clock">
            <strong id="clockTime">--:--</strong>
            <span id="clockDate"></span>
        </div>
    </div>

    <div class="stage">
        <section class="panel" id="identityPanel">
            <div class="idle-copy" id="idleCopy">
                <h2>{{ __('attendance.kiosk_headline') }}</h2>
                <p>{{ __('attendance.kiosk_sub') }}</p>
            </div>

            <div class="nfc-hero" id="idleHero">
                <div>
                    <div class="nfc-ring"><i class="fa-solid fa-wifi"></i></div>
                    <div class="hint">NFC / QR</div>
                </div>
            </div>

            <div class="result" id="resultBox">
                <div class="photo-frame">
                    <img id="personPhoto" alt="" class="d-none" style="display:none;">
                    <div class="photo-fallback" id="personFallback">?</div>
                </div>
                <div class="scan-badge" id="scanBadge"><i class="fa-solid fa-check"></i></div>
                <h2 class="person-name" id="personName">—</h2>
                <div class="person-meta" id="personMeta">—</div>
                <div class="action-pill" id="actionPill">ARRIVAL</div>
                <div class="msg" id="personMsg"></div>
            </div>
        </section>

        <section class="panel">
            <div class="idle-copy" style="margin-bottom:10px;">
                <h2 style="font-size:1.25rem;">{{ __('attendance.kiosk_scan_panel') }}</h2>
                <p>{{ __('attendance.kiosk_scan_help') }}</p>
            </div>
            <div class="cam-wrap">
                <div id="reader"></div>
            </div>
            <div class="manual">
                <input type="text" id="manualCode" autocomplete="off" autocapitalize="off" spellcheck="false"
                       placeholder="{{ __('attendance.kiosk_manual_ph') }}">
                <button type="button" id="manualBtn">{{ __('attendance.kiosk_submit') }}</button>
            </div>
            <div class="status-line" id="statusLine">{{ __('attendance.kiosk_ready') }}</div>
            <div class="foot">
                <a class="ghost-btn" href="{{ route('dashboard') }}"><i class="fa-solid fa-arrow-left"></i> {{ __('attendance.kiosk_exit') }}</a>
                <button type="button" class="ghost-btn" id="resetBtn"><i class="fa-solid fa-rotate"></i> {{ __('attendance.kiosk_reset') }}</button>
            </div>
        </section>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(() => {
    const scanUrl = @json($scanUrl);
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const shell = document.getElementById('kioskShell');
    const statusLine = document.getElementById('statusLine');
    const idleCopy = document.getElementById('idleCopy');
    const idleHero = document.getElementById('idleHero');
    const resultBox = document.getElementById('resultBox');
    const personPhoto = document.getElementById('personPhoto');
    const personFallback = document.getElementById('personFallback');
    const personName = document.getElementById('personName');
    const personMeta = document.getElementById('personMeta');
    const personMsg = document.getElementById('personMsg');
    const actionPill = document.getElementById('actionPill');
    const scanBadge = document.getElementById('scanBadge');
    const manualCode = document.getElementById('manualCode');
    const labels = {
        arrival: @json(__('attendance.kiosk_arrival')),
        departure: @json(__('attendance.kiosk_departure')),
        student: @json(__('attendance.kiosk_student')),
        staff: @json(__('attendance.kiosk_staff')),
        processing: @json(__('attendance.kiosk_processing')),
        ready: @json(__('attendance.kiosk_ready')),
        unknown: @json(__('attendance.kiosk_unknown')),
    };

    let busy = false;
    let resetTimer = null;
    let html5QrCode = null;

    function tickClock() {
        const now = new Date();
        document.getElementById('clockTime').textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        document.getElementById('clockDate').textContent = now.toLocaleDateString([], { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
    }
    tickClock();
    setInterval(tickClock, 1000);

    function setShell(state) {
        shell.classList.remove('state-idle', 'state-ok', 'state-bad', 'state-warn');
        shell.classList.add('state-' + state);
    }

    function resetUi() {
        clearTimeout(resetTimer);
        busy = false;
        setShell('idle');
        idleCopy.style.display = '';
        idleHero.style.display = '';
        resultBox.classList.remove('show');
        statusLine.textContent = labels.ready;
        manualCode.value = '';
        manualCode.focus();
        startScanner();
    }

    function showResult(payload, httpOk) {
        idleCopy.style.display = 'none';
        idleHero.style.display = 'none';
        resultBox.classList.add('show');

        const success = httpOk && (payload.success === true || payload.status === 'success');
        const ignored = payload.status === 'ignored';
        const name = payload.display_name || payload.name || '—';
        const type = payload.type || '';
        const code = payload.code || '';
        const action = payload.action || '';
        const photo = payload.photo_url || null;

        personName.textContent = name;
        personMeta.textContent = [type ? (labels[type] || type) : '', code].filter(Boolean).join(' · ') || '—';
        personMsg.textContent = payload.message || '';
        actionPill.textContent = action === 'departure' ? labels.departure : (action === 'arrival' ? labels.arrival : (payload.punctuality || '—'));
        actionPill.style.borderColor = payload.ui_color || '';
        actionPill.style.color = payload.ui_color || '';

        if (photo) {
            personPhoto.src = photo;
            personPhoto.style.display = 'block';
            personFallback.style.display = 'none';
        } else {
            personPhoto.style.display = 'none';
            personPhoto.removeAttribute('src');
            personFallback.style.display = 'grid';
            personFallback.textContent = (name || '?').trim().charAt(0).toUpperCase();
        }

        scanBadge.classList.remove('is-bad', 'is-warn');
        if (success) {
            setShell(payload.punctuality === 'late' ? 'warn' : 'ok');
            scanBadge.innerHTML = '<i class="fa-solid fa-check"></i>';
            if (payload.punctuality === 'late') scanBadge.classList.add('is-warn');
        } else if (ignored) {
            setShell('warn');
            scanBadge.classList.add('is-warn');
            scanBadge.innerHTML = '<i class="fa-solid fa-clock"></i>';
        } else {
            setShell('bad');
            scanBadge.classList.add('is-bad');
            scanBadge.innerHTML = '<i class="fa-solid fa-xmark"></i>';
            personName.textContent = name !== '—' ? name : labels.unknown;
        }

        statusLine.textContent = payload.message || labels.ready;
        clearTimeout(resetTimer);
        resetTimer = setTimeout(resetUi, success || ignored ? 4500 : 3500);
    }

    async function processUid(uid, method) {
        const code = String(uid || '').trim();
        if (!code || busy) return;
        busy = true;
        statusLine.textContent = labels.processing;
        try { await stopScanner(); } catch (e) {}

        try {
            const res = await fetch(scanUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ uid: code, method: method || 'qr' }),
            });
            const data = await res.json().catch(() => ({ message: labels.unknown }));
            showResult(data, res.ok);
        } catch (err) {
            showResult({ success: false, message: labels.unknown }, false);
        }
    }

    async function startScanner() {
        if (!window.Html5Qrcode) return;
        if (!html5QrCode) html5QrCode = new Html5Qrcode('reader');
        try {
            if (html5QrCode.isScanning) return;
            await html5QrCode.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 240, height: 240 } },
                (decoded) => processUid(decoded, 'qr'),
                () => {}
            );
        } catch (e) {
            statusLine.textContent = @json(__('attendance.kiosk_camera_fail'));
        }
    }

    async function stopScanner() {
        if (html5QrCode && html5QrCode.isScanning) {
            await html5QrCode.stop();
        }
    }

    document.getElementById('manualBtn').addEventListener('click', () => processUid(manualCode.value, 'manual'));
    manualCode.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            processUid(manualCode.value, 'manual');
        }
    });
    document.getElementById('resetBtn').addEventListener('click', resetUi);

    // Keyboard wedge NFC readers type then press Enter into the focused field.
    manualCode.focus();
    startScanner();
})();
</script>
</body>
</html>
