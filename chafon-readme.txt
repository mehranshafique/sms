The CHAFON CF661 is a heavy-duty, long-range UHF RFID reader (typically used for parking gates or hands-free student attendance as they walk through a doorway).

Because this is industrial hardware, it does not make HTTP POST (Webhook) requests natively. It does not understand web URLs. Instead, it communicates via raw TCP/IP Sockets or Wiegand.

To connect the CF661 to your Laravel API, you need to set up a "Middleware Bridge"—a simple script running on a computer or Raspberry Pi on the school's local network that catches the raw data from the CHAFON reader and translates it into an HTTP POST request for your Laravel backend.

Here is the exact step-by-step guide to configuring the CHAFON CF661 and the bridge code you need.

Step 1: Configure the CHAFON CF661 Hardware
You will need a Windows PC and the Chafon UHF RFID Demo Software (which comes on a CD/USB with the reader or can be downloaded from their site).

Plug the CF661 into power and connect it to your computer (or network switch) via the Ethernet cable.

Open the Chafon Demo Software.

Go to the TCP/IP connection tab. The default IP of the reader is usually 192.168.1.190 (Port 6000 or 27011). Click Connect.

Go to the Work Mode or Working Parameter settings.

Change the Work Mode to "Active Mode" (sometimes called Auto Mode). This tells the reader to automatically push data whenever a card is near, rather than waiting to be asked.

Set the Dest IP (Destination IP) to the local IP address of the computer that will run your Python Bridge (e.g., 192.168.1.100).

Set the Dest Port to 5000.

Click Set / Save to apply to the reader.

Step 2: Run the Python bridge

Use `chafon_bridge.py` in this repository on a PC or Raspberry Pi on the same LAN as the reader.

Step 3: Environment variables for the bridge

Set these on the PC or Raspberry Pi running chafon_bridge.py:

| Variable | Example | Description |
|----------|---------|-------------|
| HARDWARE_SECRET | (from Laravel .env) | Must match `HARDWARE_SECRET` on server |
| HARDWARE_API_URL | https://your-domain.com/api/v1/hardware/attendance/scan | V1 scan endpoint |
| HARDWARE_INSTITUTION_ID | 3 | School ID (required if server uses `HARDWARE_ALLOWED_INSTITUTION_IDS`) |
| HARDWARE_DEVICE_ID | CHAFON_MAIN_GATE_01 | Device label in logs |
| HARDWARE_PURPOSE | attendance | `attendance`, `fee_check`, `pickup`, `report_card`, `identity_check` |
| CHAFON_LISTEN_PORT | 5000 | TCP port the reader pushes to |

Run: `python chafon_bridge.py`

The bridge sends JSON with `uid`, `method`, `device_id`, `purpose`, and ISO `timestamp` to Laravel.
You will run this lightweight Python script on a computer connected to the same local network (this could be the reception PC or a dedicated $30 Raspberry Pi).

This script listens on port 5000 for the raw hex data from the CHAFON reader, extracts the Card ID (EPC), and instantly fires your Laravel Webhook.