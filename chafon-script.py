import os
import socket
import requests
import binascii
import time
from datetime import datetime, timezone

# --- CONFIGURATION (set via environment — never commit secrets) ---
LISTEN_PORT = int(os.environ.get('CHAFON_LISTEN_PORT', '5000'))
LISTEN_HOST = os.environ.get('CHAFON_LISTEN_HOST', '0.0.0.0')
API_URL = os.environ.get(
    'HARDWARE_API_URL',
    'https://account.digitexvx.com/api/v1/hardware/attendance/scan',
)
HARDWARE_SECRET = os.environ.get('HARDWARE_SECRET', '')
INSTITUTION_ID = os.environ.get('HARDWARE_INSTITUTION_ID', '')
DEVICE_ID = os.environ.get('HARDWARE_DEVICE_ID', 'CHAFON_MAIN_GATE_01')
HARDWARE_PURPOSE = os.environ.get('HARDWARE_PURPOSE', 'attendance')  # attendance | fee_check | pickup | report_card | identity_check
REQUEST_TIMEOUT = int(os.environ.get('HARDWARE_REQUEST_TIMEOUT', '10'))
MAX_RETRIES = int(os.environ.get('HARDWARE_MAX_RETRIES', '2'))
# ---------------------


def build_headers():
    headers = {
        'X-Hardware-Secret': HARDWARE_SECRET,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    }
    if INSTITUTION_ID:
        headers['X-Institution-Id'] = INSTITUTION_ID
    return headers


def post_scan(payload):
    last_error = None
    for attempt in range(1, MAX_RETRIES + 2):
        try:
            response = requests.post(
                API_URL,
                json=payload,
                headers=build_headers(),
                timeout=REQUEST_TIMEOUT,
            )
            print(f"API Response ({response.status_code}): {response.text}")
            return response
        except requests.RequestException as exc:
            last_error = exc
            print(f"Attempt {attempt} failed: {exc}")
            if attempt <= MAX_RETRIES:
                time.sleep(0.5 * attempt)
    print(f"All attempts failed: {last_error}")
    return None


def process_rfid_data(hex_data):
    try:
        uid = hex_data.replace(' ', '').upper()
        print(f"Extracted UID: {uid}")

        if not HARDWARE_SECRET:
            print('ERROR: HARDWARE_SECRET environment variable is not set.')
            return

        payload = {
            'uid': uid,
            'method': 'rfid',
            'device_id': DEVICE_ID,
            'purpose': HARDWARE_PURPOSE,
            'timestamp': datetime.now(timezone.utc).isoformat(),
        }

        post_scan(payload)

    except Exception as e:
        print(f"Error processing RFID: {e}")


def start_server():
    if not HARDWARE_SECRET:
        print('WARNING: Set HARDWARE_SECRET before running in production.')

    print(f"Bridge target: {API_URL}")
    print(f"Purpose: {HARDWARE_PURPOSE} | Device: {DEVICE_ID}")
    if INSTITUTION_ID:
        print(f"Institution: {INSTITUTION_ID}")

    server = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    server.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    server.bind((LISTEN_HOST, LISTEN_PORT))
    server.listen(5)
    print(f"Chafon bridge listening on {LISTEN_HOST}:{LISTEN_PORT}")

    while True:
        client, addr = server.accept()
        try:
            data = client.recv(1024)
            if data:
                hex_data = binascii.hexlify(data).decode('utf-8')
                print(f"Received from {addr}: {hex_data}")
                process_rfid_data(hex_data)
        finally:
            client.close()


if __name__ == '__main__':
    start_server()
