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
            --panel: rgba(13, 33, 61, .92);
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
        }
        .kiosk {
            min-height: 100vh;
            min-height: 100dvh;
            padding: clamp(12px, 2vw, 20px);
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
            margin-bottom: clamp(10px, 1.5vh, 16px);
            flex-shrink: 0;
        }
        .brand { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .logo-dot {
            width: 42px; height: 42px; border-radius: 50%; flex-shrink: 0;
            background: linear-gradient(135deg, #2563eb, #38bdf8);
            display: grid; place-items: center; font-weight: 800; color: #fff;
            box-shadow: 0 0 20px rgba(37,99,235,.45);
        }
        .brand h1 { margin: 0; font-size: clamp(.95rem, 2.5vw, 1.15rem); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .brand p { margin: 2px 0 0; color: var(--muted); font-size: .82rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .clock { text-align: right; color: var(--muted); font-variant-numeric: tabular-nums; flex-shrink: 0; }
        .clock strong { display: block; color: #fff; font-size: clamp(1.1rem, 3vw, 1.35rem); }

        .stage {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 0;
            width: 100%;
            max-width: 720px;
            margin: 0 auto;
        }

        .panel {
            width: 100%;
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 22px;
            padding: clamp(14px, 2.5vw, 22px);
            display: flex;
            flex-direction: column;
            align-items: center;
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
        .panel > * { position: relative; z-index: 1; width: 100%; }

        .idle-copy { text-align: center; margin-bottom: 12px; }
        .idle-copy h2 {
            margin: 0 0 8px;
            font-size: clamp(1.2rem, 3.5vw, 1.75rem);
            line-height: 1.25;
        }
        .idle-copy p { margin: 0; color: var(--muted); font-size: clamp(.88rem, 2.2vw, 1rem); }

        .modes {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 14px;
        }
        .mode-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: .35rem .75rem;
            border-radius: 999px;
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .03em;
            border: 1px solid rgba(140,170,220,.3);
            background: rgba(255,255,255,.04);
            color: var(--muted);
        }
        .mode-chip.on { color: #9ec1ff; border-color: rgba(47,128,237,.55); background: rgba(47,128,237,.12); }
        .mode-chip.on .dot { background: var(--ok); box-shadow: 0 0 8px rgba(34,197,94,.7); }
        .mode-chip .dot {
            width: 7px; height: 7px; border-radius: 50%;
            background: #64748b;
        }

        /* Centered square viewfinder — never clipped */
        .viewport {
            width: min(100%, 520px);
            margin: 0 auto;
            aspect-ratio: 1;
            max-height: min(58vh, 520px);
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid rgba(140,170,220,.35);
            background: #081526;
            position: relative;
            flex-shrink: 0;
        }
        #reader {
            width: 100% !important;
            height: 100% !important;
            position: absolute;
            inset: 0;
        }
        #reader > div { border: none !important; }
        #reader video {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            border-radius: 0 !important;
        }
        #reader img { display: none !important; }
        .scan-frame {
            pointer-events: none;
            position: absolute;
            inset: 12%;
            z-index: 2;
            border: 2px solid rgba(158, 193, 255, .35);
            border-radius: 12px;
            box-shadow: 0 0 0 9999px rgba(8, 21, 38, .35);
        }
        .scan-frame::before,
        .scan-frame::after {
            content: "";
            position: absolute;
            width: 28px; height: 28px;
            border: 3px solid #9ec1ff;
        }
        .scan-frame::before { top: -2px; left: -2px; border-right: 0; border-bottom: 0; border-radius: 8px 0 0 0; }
        .scan-frame::after { top: -2px; right: -2px; border-left: 0; border-bottom: 0; border-radius: 0 8px 0 0; }
        .scan-corners {
            pointer-events: none;
            position: absolute;
            inset: 12%;
            z-index: 3;
        }
        .scan-corners::before,
        .scan-corners::after {
            content: "";
            position: absolute;
            width: 28px; height: 28px;
            border: 3px solid #9ec1ff;
        }
        .scan-corners::before { bottom: -2px; left: -2px; border-right: 0; border-top: 0; border-radius: 0 0 0 8px; }
        .scan-corners::after { bottom: -2px; right: -2px; border-left: 0; border-top: 0; border-radius: 0 0 8px 0; }

        .cam-overlay-msg {
            position: absolute;
            inset: 0;
            z-index: 4;
            display: none;
            place-items: center;
            text-align: center;
            padding: 24px;
            background: rgba(8, 21, 38, .82);
            color: var(--muted);
            font-size: .95rem;
        }
        .cam-overlay-msg.show { display: grid; }

        .result {
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 10px;
            padding: 8px 0 4px;
        }
        .result.show { display: flex; }
        .photo-frame {
            width: min(200px, 48vw);
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
            width: 56px; height: 56px; border-radius: 50%;
            display: grid; place-items: center;
            margin-top: -24px;
            border: 3px solid #0b1d36;
            background: var(--ok);
            color: #fff;
            font-size: 1.25rem;
            box-shadow: 0 0 24px rgba(34,197,94,.55);
        }
        .scan-badge.is-bad { background: var(--bad); box-shadow: 0 0 24px rgba(239,68,68,.55); }
        .scan-badge.is-warn { background: var(--warn); box-shadow: 0 0 24px rgba(245,158,11,.55); }
        .person-name { margin: 8px 0 0; font-size: clamp(1.35rem, 3.5vw, 2rem); font-weight: 800; }
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

        .manual {
            margin-top: 14px;
            display: flex;
            gap: 8px;
            width: 100%;
            max-width: 520px;
            margin-left: auto;
            margin-right: auto;
        }
        .manual input {
            flex: 1;
            min-width: 0;
            border-radius: 12px;
            border: 1px solid rgba(140,170,220,.3);
            background: rgba(255,255,255,.05);
            color: #fff;
            padding: .8rem 1rem;
            font-size: 1rem;
        }
        .manual input:focus { outline: 2px solid rgba(47,128,237,.55); border-color: transparent; }
        .manual button, .ghost-btn {
            border: 0;
            border-radius: 12px;
            padding: .8rem 1.05rem;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
        }
        .manual button {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
        }
        .foot {
            margin-top: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            width: 100%;
            max-width: 520px;
            margin-left: auto;
            margin-right: auto;
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
            text-align: center;
            margin-top: 10px;
        }

        /* Captures HID / USB NFC wedges without stealing the UI */
        #nfcCapture {
            position: absolute;
            left: -9999px;
            top: 0;
            width: 1px;
            height: 1px;
            opacity: 0;
            pointer-events: none;
        }

        @media (max-height: 700px) {
            .viewport { max-height: min(48vh, 420px); }
            .idle-copy h2 { font-size: 1.15rem; }
        }
        @media (max-width: 480px) {
            .manual { flex-direction: column; }
            .manual button { width: 100%; }
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
        <section class="panel" id="mainPanel">
            <div id="scanArea">
                <div class="idle-copy">
                    <h2>{{ __('attendance.kiosk_headline') }}</h2>
                    <p>{{ __('attendance.kiosk_sub') }}</p>
                </div>

                <div class="modes">
                    <span class="mode-chip" id="chipNfc"><span class="dot"></span> {{ __('attendance.kiosk_mode_nfc') }}</span>
                    <span class="mode-chip" id="chipQr"><span class="dot"></span> {{ __('attendance.kiosk_mode_qr') }}</span>
                </div>

                <div class="viewport" id="viewport">
                    <div id="reader"></div>
                    <div class="scan-frame"></div>
                    <div class="scan-corners"></div>
                    <div class="cam-overlay-msg" id="camOverlay">{{ __('attendance.kiosk_camera_fail') }}</div>
                </div>

                <div class="manual">
                    <input type="text" id="manualCode" autocomplete="off" autocapitalize="off" spellcheck="false"
                           placeholder="{{ __('attendance.kiosk_manual_ph') }}" enterkeyhint="go">
                    <button type="button" id="manualBtn">{{ __('attendance.kiosk_submit') }}</button>
                </div>
                <div class="status-line" id="statusLine">{{ __('attendance.kiosk_ready') }}</div>
            </div>

            <div class="result" id="resultBox">
                <div class="photo-frame">
                    <img id="personPhoto" alt="" style="display:none;">
                    <div class="photo-fallback" id="personFallback">?</div>
                </div>
                <div class="scan-badge" id="scanBadge"><i class="fa-solid fa-check"></i></div>
                <h2 class="person-name" id="personName">—</h2>
                <div class="person-meta" id="personMeta">—</div>
                <div class="action-pill" id="actionPill">ARRIVAL</div>
                <div class="msg" id="personMsg"></div>
            </div>

            <div class="foot">
                <a class="ghost-btn" href="{{ route('dashboard') }}"><i class="fa-solid fa-arrow-left"></i> {{ __('attendance.kiosk_exit') }}</a>
                <button type="button" class="ghost-btn" id="resetBtn"><i class="fa-solid fa-rotate"></i> {{ __('attendance.kiosk_reset') }}</button>
            </div>
        </section>
    </div>

    {{-- Always-available HID capture for USB/NFC keyboard wedges --}}
    <input type="text" id="nfcCapture" autocomplete="off" autocapitalize="off" spellcheck="false" aria-hidden="true" tabindex="-1">
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(() => {
    const scanUrl = @json($scanUrl);
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const shell = document.getElementById('kioskShell');
    const statusLine = document.getElementById('statusLine');
    const scanArea = document.getElementById('scanArea');
    const resultBox = document.getElementById('resultBox');
    const personPhoto = document.getElementById('personPhoto');
    const personFallback = document.getElementById('personFallback');
    const personName = document.getElementById('personName');
    const personMeta = document.getElementById('personMeta');
    const personMsg = document.getElementById('personMsg');
    const actionPill = document.getElementById('actionPill');
    const scanBadge = document.getElementById('scanBadge');
    const manualCode = document.getElementById('manualCode');
    const nfcCapture = document.getElementById('nfcCapture');
    const camOverlay = document.getElementById('camOverlay');
    const chipNfc = document.getElementById('chipNfc');
    const chipQr = document.getElementById('chipQr');
    const viewport = document.getElementById('viewport');
    const labels = {
        arrival: @json(__('attendance.kiosk_arrival')),
        departure: @json(__('attendance.kiosk_departure')),
        student: @json(__('attendance.kiosk_student')),
        staff: @json(__('attendance.kiosk_staff')),
        processing: @json(__('attendance.kiosk_processing')),
        ready: @json(__('attendance.kiosk_ready')),
        unknown: @json(__('attendance.kiosk_unknown')),
        nfcReady: @json(__('attendance.kiosk_nfc_ready')),
        nfcUnsupported: @json(__('attendance.kiosk_nfc_unsupported')),
        cameraFail: @json(__('attendance.kiosk_camera_fail')),
    };

    let busy = false;
    let resetTimer = null;
    let html5QrCode = null;
    let webNfcActive = false;
    let wedgeBuffer = '';
    let wedgeTimer = null;
    const WEDGE_GAP_MS = 80;

    function tickClock() {
        const now = new Date();
        document.getElementById('clockTime').textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        document.getElementById('clockDate').textContent = now.toLocaleDateString([], { weekday: 'short', month: 'short', day: 'numeric' });
    }
    tickClock();
    setInterval(tickClock, 1000);

    function setShell(state) {
        shell.classList.remove('state-idle', 'state-ok', 'state-bad', 'state-warn');
        shell.classList.add('state-' + state);
    }

    function setChip(el, on) {
        el.classList.toggle('on', !!on);
    }

    function focusCapture() {
        // Keep HID wedge dedicated field ready when user is not typing manually.
        if (document.activeElement === manualCode) return;
        nfcCapture.focus({ preventScroll: true });
    }

    function resetUi() {
        clearTimeout(resetTimer);
        busy = false;
        setShell('idle');
        scanArea.style.display = '';
        resultBox.classList.remove('show');
        statusLine.textContent = labels.ready;
        manualCode.value = '';
        nfcCapture.value = '';
        wedgeBuffer = '';
        startScanner();
        focusCapture();
    }

    function showResult(payload, httpOk) {
        scanArea.style.display = 'none';
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

    function qrboxSize() {
        const side = Math.min(viewport.clientWidth, viewport.clientHeight);
        const box = Math.max(160, Math.floor(side * 0.72));
        return { width: box, height: box };
    }

    async function startWithCamera(cameraConfig) {
        await html5QrCode.start(
            cameraConfig,
            { fps: 12, qrbox: qrboxSize(), aspectRatio: 1 },
            (decoded) => processUid(decoded, 'qr'),
            () => {}
        );
    }

    async function startScanner() {
        if (!window.Html5Qrcode) {
            camOverlay.classList.add('show');
            camOverlay.textContent = labels.cameraFail;
            setChip(chipQr, false);
            return;
        }
        if (!html5QrCode) html5QrCode = new Html5Qrcode('reader');
        try {
            if (html5QrCode.isScanning) return;
            camOverlay.classList.remove('show');

            // Prefer rear camera; fall back to front (user), then any camera.
            const attempts = [
                { facingMode: 'environment' },
                { facingMode: 'user' },
            ];
            let started = false;
            let lastErr = null;
            for (const cfg of attempts) {
                try {
                    await startWithCamera(cfg);
                    started = true;
                    break;
                } catch (e) {
                    lastErr = e;
                    try { if (html5QrCode.isScanning) await html5QrCode.stop(); } catch (_) {}
                }
            }
            if (!started) {
                try {
                    const cams = await Html5Qrcode.getCameras();
                    if (cams && cams.length) {
                        await startWithCamera(cams[0].id);
                        started = true;
                    }
                } catch (e) {
                    lastErr = e;
                }
            }
            if (!started) throw lastErr || new Error('no camera');
            setChip(chipQr, true);
            statusLine.textContent = labels.ready;
        } catch (e) {
            setChip(chipQr, false);
            camOverlay.classList.add('show');
            camOverlay.textContent = labels.cameraFail;
            statusLine.textContent = labels.cameraFail;
        }
    }

    async function stopScanner() {
        if (html5QrCode && html5QrCode.isScanning) {
            await html5QrCode.stop();
        }
    }

    // --- HID / USB NFC keyboard wedge (document-level when not typing in manual) ---
    function flushWedge(method) {
        const code = wedgeBuffer.trim();
        wedgeBuffer = '';
        if (code) processUid(code, method || 'nfc');
    }

    document.addEventListener('keydown', (e) => {
        if (busy) return;
        const target = e.target;
        const inManual = target === manualCode;
        const inCapture = target === nfcCapture;

        if (inManual) return; // handled separately

        // Ignore shortcuts / navigation keys when not capture-focused from a scanner.
        if (e.ctrlKey || e.metaKey || e.altKey) return;
        if (e.key === 'Tab' || e.key === 'Escape') return;

        if (e.key === 'Enter') {
            if (wedgeBuffer.length || (inCapture && nfcCapture.value.trim())) {
                e.preventDefault();
                if (inCapture && nfcCapture.value.trim()) {
                    const v = nfcCapture.value.trim();
                    nfcCapture.value = '';
                    processUid(v, 'nfc');
                } else {
                    flushWedge('nfc');
                }
            }
            return;
        }

        if (e.key.length === 1) {
            // Don't steal typed characters from other focusable controls.
            if (target && target !== document.body && target !== nfcCapture && target.tagName !== 'HTML'
                && target.isContentEditable) {
                return;
            }
            if (target && ['INPUT', 'TEXTAREA', 'SELECT', 'BUTTON', 'A'].includes(target.tagName)
                && target !== nfcCapture) {
                return;
            }
            wedgeBuffer += e.key;
            clearTimeout(wedgeTimer);
            wedgeTimer = setTimeout(() => {
                // Scanners type fast; slow human typing into nowhere is ignored unless Enter.
                if (wedgeBuffer.length >= 4) flushWedge('nfc');
                else wedgeBuffer = '';
            }, WEDGE_GAP_MS * 6);
        }
    });

    nfcCapture.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const v = nfcCapture.value.trim();
            nfcCapture.value = '';
            if (v) processUid(v, 'nfc');
        }
    });

    // --- Web NFC (Android Chrome / supported browsers) ---
    async function startWebNfc() {
        if (!('NDEFReader' in window)) {
            setChip(chipNfc, false);
            return;
        }
        try {
            const reader = new NDEFReader();
            await reader.scan();
            webNfcActive = true;
            setChip(chipNfc, true);
            statusLine.textContent = labels.nfcReady;
            reader.addEventListener('reading', ({ serialNumber, message }) => {
                if (busy) return;
                let uid = serialNumber || '';
                if (!uid && message && message.records) {
                    for (const record of message.records) {
                        if (record.recordType === 'text') {
                            try {
                                const decoder = new TextDecoder(record.encoding || 'utf-8');
                                uid = decoder.decode(record.data);
                                break;
                            } catch (_) {}
                        } else if (record.recordType === 'url' || record.recordType === 'absolute-url') {
                            try {
                                uid = new TextDecoder().decode(record.data);
                                break;
                            } catch (_) {}
                        }
                    }
                }
                if (uid) processUid(uid, 'nfc');
            });
            reader.addEventListener('readingerror', () => {
                // Keep listening; chip stays on.
            });
        } catch (err) {
            // Permission denied or NFC off — USB wedges still work.
            webNfcActive = false;
            setChip(chipNfc, true); // HID wedge still available
            statusLine.textContent = labels.nfcUnsupported;
        }
    }

    // Mark NFC chip ready for HID wedges even without Web NFC.
    setChip(chipNfc, true);

    document.getElementById('manualBtn').addEventListener('click', () => processUid(manualCode.value, 'manual'));
    manualCode.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            processUid(manualCode.value, 'manual');
        }
    });
    manualCode.addEventListener('blur', () => setTimeout(focusCapture, 50));
    document.getElementById('resetBtn').addEventListener('click', resetUi);

    window.addEventListener('resize', () => {
        // Restart camera with resized qrbox when viewport changes (rotation / split screen).
        if (busy || !html5QrCode || !html5QrCode.isScanning) return;
        clearTimeout(window.__kioskResizeTimer);
        window.__kioskResizeTimer = setTimeout(async () => {
            try {
                await stopScanner();
                await startScanner();
            } catch (_) {}
        }, 350);
    });

    // Keep capture focused after taps on non-input areas (tablets).
    document.addEventListener('click', (e) => {
        if (e.target === manualCode || e.target.closest('.manual') || e.target.closest('a,button')) return;
        focusCapture();
    });

    startScanner();
    startWebNfc();
    focusCapture();
})();
</script>
</body>
</html>
