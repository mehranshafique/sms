# Chafon CF661 RFID Bridge — Setup, Configure, Test & Run

**Digitex School Management System — Hardware Attendance**

This guide explains how to connect a **Chafon CF661** UHF RFID reader to Digitex using the Python bridge script (`chafon-script.py`) on a local PC or Raspberry Pi.

---

## 1. How authentication works (important)

Digitex hardware scans do **not** use per-school API keys such as `dgtx_live_…`.

| Layer | What you configure | Who sets it |
|-------|-------------------|-------------|
| **Server** | `HARDWARE_SECRET` in Laravel `.env` | Platform / Super Admin (once per server) |
| **Each gate PC** | Same `HARDWARE_SECRET` + that school's `HARDWARE_INSTITUTION_ID` | School IT or installer |

- **One shared secret is enough for all schools** on the same Digitex installation.
- **Each school** must set a **different** `HARDWARE_INSTITUTION_ID` on its own bridge so scans are routed to the correct school.
- Schools do **not** enter an API key in the web UI for hardware; they only configure the bridge environment on the gate computer.

Optional server setting:

```env
HARDWARE_ALLOWED_INSTITUTION_IDS=3,5,12
```

When set, only those institution IDs are accepted via the `X-Institution-Id` header.

---

## 2. Prerequisites

### On the Digitex server

1. PHP/Laravel app deployed and reachable over HTTPS.
2. In `.env`:

```env
HARDWARE_SECRET=generate-a-long-random-string-here
# Optional — comma-separated school IDs this deployment may serve:
# HARDWARE_ALLOWED_INSTITUTION_IDS=3,5
```

3. Run `php artisan config:clear` after changing `.env`.

### On the gate PC / Raspberry Pi

- Python 3.8+
- Package: `requests` (`pip install requests`)
- Same LAN as the Chafon reader
- Copy from the SMS repo:
  - `chafon-script.py`
  - `chafon.env.example` → rename/copy to `chafon.env`

### Find your school Institution ID

In Digitex web admin: note the school context, or ask Super Admin. It is the numeric `institution_id` (e.g. `3` for Complexe Scolaire Integrale).

---

## 3. Configure the Chafon CF661 reader

1. Connect the reader to power and Ethernet.
2. On a Windows PC, open **Chafon UHF RFID Demo Software**.
3. **TCP/IP** tab → connect (default reader IP often `192.168.1.190`, port `6000` or `27011`).
4. **Work Mode** → set to **Active Mode** (auto push on tag read).
5. **Destination IP** → IP of the PC running the bridge (e.g. `192.168.1.100`).
6. **Destination Port** → `5000` (must match `CHAFON_LISTEN_PORT`).
7. **Save** settings to the reader.

---

## 4. Configure the bridge (`chafon.env`)

Edit `chafon.env` on the gate machine:

```env
HARDWARE_SECRET=same-value-as-server-HARDWARE_SECRET
HARDWARE_API_URL=https://your-domain.com/api/v1/hardware/attendance/scan
HARDWARE_INSTITUTION_ID=3
HARDWARE_DEVICE_ID=CHAFON_MAIN_GATE_01
HARDWARE_PURPOSE=attendance
CHAFON_LISTEN_HOST=0.0.0.0
CHAFON_LISTEN_PORT=5000
```

| Variable | Required | Description |
|----------|----------|-------------|
| `HARDWARE_SECRET` | Yes | Must match server `.env` |
| `HARDWARE_API_URL` | Yes | Full URL to scan endpoint |
| `HARDWARE_INSTITUTION_ID` | Yes* | School ID (*optional only if server sets `HARDWARE_ALLOWED_INSTITUTION_IDS` with a single ID) |
| `HARDWARE_DEVICE_ID` | No | Label in logs (default `CHAFON_MAIN_GATE_01`) |
| `HARDWARE_PURPOSE` | No | `attendance`, `fee_check`, `pickup`, `report_card`, `identity_check` |
| `CHAFON_LISTEN_PORT` | No | TCP listen port (default `5000`) |

**Multi-school example**

| School | Gate PC | `HARDWARE_INSTITUTION_ID` | `HARDWARE_SECRET` |
|--------|---------|---------------------------|-------------------|
| School A | PC at gate A | `3` | same platform secret |
| School B | PC at gate B | `5` | same platform secret |

---

## 5. Run the bridge

### Windows (PowerShell)

```powershell
cd D:\path\to\sms
pip install requests
Get-Content chafon.env | ForEach-Object {
  if ($_ -match '^([^#=]+)=(.*)$') {
    [Environment]::SetEnvironmentVariable($matches[1].Trim(), $matches[2].Trim(), 'Process')
  }
}
python chafon-script.py
```

### Linux / Raspberry Pi

```bash
cd /opt/digitex-bridge
pip3 install requests
set -a && source chafon.env && set +a
python3 chafon-script.py
```

### Run as a background service (Linux, systemd example)

Create `/etc/systemd/system/digitex-chafon.service`:

```ini
[Unit]
Description=Digitex Chafon RFID Bridge
After=network.target

[Service]
Type=simple
WorkingDirectory=/opt/digitex-bridge
EnvironmentFile=/opt/digitex-bridge/chafon.env
ExecStart=/usr/bin/python3 /opt/digitex-bridge/chafon-script.py
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable digitex-chafon
sudo systemctl start digitex-chafon
sudo systemctl status digitex-chafon
```

Expected console output:

```
Bridge target: https://your-domain.com/api/v1/hardware/attendance/scan
Purpose: attendance | Device: CHAFON_MAIN_GATE_01
Institution: 3
Chafon bridge listening on 0.0.0.0:5000
```

---

## 6. Test the connection

### Step A — Test server secret (curl)

From any machine with network access to the server:

```bash
curl -X POST "https://your-domain.com/api/v1/hardware/attendance/scan" \
  -H "Content-Type: application/json" \
  -H "X-Hardware-Secret: YOUR_HARDWARE_SECRET" \
  -H "X-Institution-Id: 3" \
  -d "{\"uid\":\"TEST123\",\"method\":\"rfid\",\"device_id\":\"TEST\",\"purpose\":\"attendance\"}"
```

| Response | Meaning |
|----------|---------|
| `503 Hardware API is disabled` | `HARDWARE_SECRET` not set on server |
| `401 Unauthorized Hardware Device` | Secret mismatch |
| `400` / student not found | Secret OK — use a real student RFID/admission UID |
| `200` with check-in message | Full success |

### Step B — Test from Digitex web

1. **Configuration → Test Notifications** (optional SMS path — separate from hardware).
2. Register a student's **RFID UID** on the student profile (`rfid_uid` or `nfc_tag_uid`).
3. Wave the tag at the reader; watch the bridge console for `API Response (200)`.

### Step C — Bridge logs

- `ERROR: HARDWARE_SECRET environment variable is not set` → fix `chafon.env`.
- `Attempt N failed: Connection refused` → wrong `HARDWARE_API_URL` or firewall.
- `401` in API response → secret does not match server.

Server-side logs: `storage/logs/laravel.log` (search for `Unknown UID` or hardware errors).

---

## 7. Student / staff UID setup

The scan API matches (case-normalized):

- Students: `rfid_uid`, `nfc_tag_uid`, `qr_code_token`, `admission_number`
- Staff: `nfc_uid`, `rfid_uid`, `employee_id`

Enter the tag's EPC/UID on the student or staff record before expecting attendance to record.

---

## 8. Troubleshooting

| Problem | Fix |
|---------|-----|
| No data in bridge console | Check reader Dest IP/port; firewall on gate PC for port 5000 |
| 503 from API | Set `HARDWARE_SECRET` on server |
| 401 from API | Align `HARDWARE_SECRET` on bridge and server |
| 403 Institution not authorized | Add school ID to `HARDWARE_ALLOWED_INSTITUTION_IDS` or fix `HARDWARE_INSTITUTION_ID` |
| 400 X-Institution-Id required | Set `HARDWARE_INSTITUTION_ID` in bridge env |
| Unknown student | Register UID on student profile |

---

## 9. Security notes

- Never commit `chafon.env` or real secrets to Git.
- Use HTTPS for `HARDWARE_API_URL` in production.
- Rotate `HARDWARE_SECRET` if compromised; update all gate PCs the same day.
- One platform secret is normal; isolation between schools is by **Institution ID** and database scoping.

---

## 10. Quick reference

| Item | Value |
|------|-------|
| Script | `chafon-script.py` |
| Env template | `chafon.env.example` |
| API endpoint | `POST /api/v1/hardware/attendance/scan` |
| Auth header | `X-Hardware-Secret` |
| School header | `X-Institution-Id` |
| Default listen | `0.0.0.0:5000` |

*Regenerate this PDF: `php artisan docs:generate-pdf` (includes Chafon Hardware Bridge Manual).*
