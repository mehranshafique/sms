import socket
import requests
import binascii
import time

# --- CONFIGURATION ---
# The port you told the CHAFON reader to send data to
LISTEN_PORT = 5000 
LISTEN_HOST = '0.0.0.0' # Listens on all available network interfaces

# Your Laravel Application API endpoint
API_URL = "https://your-domain.com/api/v1/hardware/attendance/scan"
HARDWARE_SECRET = "digitex_secure_hardware_key"
DEVICE_ID = "CHAFON_MAIN_GATE_01"
# ---------------------

def process_rfid_data(hex_data):
    """
    Chafon Active Mode usually sends a data frame where the EPC (Tag ID) 
    is embedded in a hex string.
    Example Frame: 04 01 12 34 56 78
    (This is a simplified extractor; adjust slicing based on your exact tag byte length)
    """
    try:
        # Assuming standard Chafon active mode hex output. 
        # Usually, the actual UID (EPC) is the payload minus headers.
        # We will strip out standard hex characters to get the raw UID.
        uid = hex_data.replace(" ", "").upper()
        
        # If the UID is incredibly long (UHF tags are 24 chars), 
        # you might want to slice it to just the unique identifier.
        if len(uid) > 16:
            uid = uid[-16:] # Keep the last 16 characters

        return uid
    except Exception as e:
        print(f"Error parsing hex: {e}")
        return None

def send_to_laravel(uid):
    payload = {
        "uid": uid,
        "device_id": DEVICE_ID,
        "method": "rfid"
    }
    
    headers = {
        "Content-Type": "application/json",
        "X-Hardware-Secret": HARDWARE_SECRET
    }

    try:
        response = requests.post(API_URL, json=payload, headers=headers, timeout=5)
        if response.status_code == 200:
            data = response.json()
            print(f"✅ Success: {data.get('name', 'User')} marked {data.get('action', 'present')}!")
        else:
            print(f"⚠️ API Rejected ({response.status_code}): {response.text}")
    except requests.exceptions.RequestException as e:
        print(f"❌ Network Error connecting to Laravel: {e}")

def start_bridge():
    # Create a TCP Socket Server
    server_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    server_socket.bind((LISTEN_HOST, LISTEN_PORT))
    server_socket.listen(1)
    
    print(f"🚀 CHAFON Bridge Started. Listening for reader on Port {LISTEN_PORT}...")
    print(f"Forwarding to: {API_URL}")

    while True:
        try:
            conn, addr = server_socket.accept()
            print(f"\n📡 Reader Connected from {addr}")
            
            while True:
                # Receive raw bytes from the Chafon Reader
                data = conn.recv(1024)
                if not data:
                    break # Reader disconnected
                
                # Convert raw bytes to Hexadecimal string
                hex_data = binascii.hexlify(data).decode('utf-8')
                print(f"📥 Raw Tag Scanned: {hex_data}")
                
                # Extract the UID
                uid = process_rfid_data(hex_data)
                
                if uid:
                    send_to_laravel(uid)
                    
                # Small delay to prevent API spamming if the reader reads the same tag 50 times a second
                time.sleep(1) 
                
        except Exception as e:
            print(f"Connection error: {e}")
        finally:
            if 'conn' in locals():
                conn.close()

if __name__ == "__main__":
    start_bridge()