# Digitex SMS — REST API Manual (Hardware & Mobile App)

---

## 1. Overview

### 1.1 Base URL

All schools use one platform address:

```
https://e-digitex.com
```

API base:

```
https://e-digitex.com/api
```

There are **no per-school subdomains**. Login, mobile apps, hardware devices, and webhooks all use **e-digitex.com**.

Example login page: **https://e-digitex.com/login**

All versioned endpoints use prefix **`/v1`**.

### 1.2 Response Format

JSON responses unless noted. Standard shapes:

**Success:**
```json
{ "success": true, "data": { ... } }
```

**Error:**
```json
{ "success": false, "message": "Human-readable error" }
```

HTTP status codes: `200` OK, `400` Bad Request, `401` Unauthorized, `403` Forbidden, `404` Not Found, `422` Validation Error, `503` Service Unavailable.

### 1.3 Common Headers

| Header | Value | When |
|--------|-------|------|
| Accept | application/json | All requests |
| Content-Type | application/json | POST/PUT bodies |
| Authorization | Bearer {token} | Sanctum-authenticated routes |
| X-Hardware-Secret | {HARDWARE_SECRET} | Hardware scanner endpoints |
| X-Institution-Id | {institution_id} | Hardware GET attendance today (required) |

---

## 2. Authentication

### 2.1 Mobile Login

**POST** `/v1/login`

**Auth:** None (public)

**Body:**
```json
{
  "email": "user@school.com",
  "password": "secret"
}
```

The `email` field accepts **email**, **username**, or student **shortcode**.

**Success (200):**
```json
{
  "success": true,
  "token": "1|plainTextSanctumToken...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@school.com",
    "role": "Teacher",
    "institution_id": 3,
    "school_name": "Example High School",
    "school_logo": "https://..."
  }
}
```

**Errors:** `401` invalid credentials, `403` inactive account.

Store `token` securely on device. Send on all subsequent requests:

```
Authorization: Bearer 1|plainTextSanctumToken...
```

### 2.2 Update FCM Token (Push Notifications)

**POST** `/v1/update-fcm-token`

**Auth:** Sanctum

**Body:**
```json
{ "fcm_token": "firebase-device-token" }
```

---

## 3. User Profile API

**Prefix:** `/v1/profile` — **Auth:** Sanctum

### GET `/v1/profile/`

Returns authenticated user profile (name, email, phone, address, photo URL, role).

### POST `/v1/profile/update`

**Body (partial allowed):**
```json
{
  "phone": "+1234567890",
  "address": "123 Street",
  "password": "newpassword",
  "password_confirmation": "newpassword"
}
```

Profile picture: multipart form field `profile_picture` (where supported by client).

---

## 4. Hardware API (NFC / RFID / QR Gate)

**Prefix:** `/v1/hardware`

### 4.1 Security

1. Set `HARDWARE_SECRET` in server `.env` (long random string).
2. Every hardware request must include header:

```
X-Hardware-Secret: {same value as HARDWARE_SECRET}
```

3. If secret is **not configured**, API returns **503** (disabled).
4. Secret comparison uses timing-safe `hash_equals`.
5. For read endpoints, also send:

```
X-Institution-Id: 3
```

### 4.2 Universal Scan

**POST** `/v1/hardware/attendance/scan`

**Auth:** `X-Hardware-Secret`

**Body:**
```json
{
  "uid": "A1B2C3D4",
  "device_id": "GATE-01",
  "timestamp": "2026-06-09T08:15:00",
  "method": "rfid",
  "purpose": "attendance"
}
```

| Field | Required | Description |
|-------|----------|-------------|
| uid | Yes | NFC/RFID/QR token or admission number |
| device_id | No | Scanner identifier for audit |
| timestamp | No | ISO datetime of scan (default: now) |
| method | No | `rfid`, `nfc`, `qr` (default: rfid) |
| purpose | No | See purposes below |

**Purposes (`purpose`):**

| Value | Behavior |
|-------|----------|
| `attendance` (default) | Student/staff check-in or check-out |
| `fee_check` | Returns fee balance / payment threshold |
| `pickup` | Process pickup QR or NFC gate pickup |
| `report_card` | Returns published exam marks summary |

**UID matching:** System searches student fields `nfc_tag_uid`, `rfid_uid`, `qr_code_token`, `admission_number` and staff equivalents. UIDs are normalized (case/colon variants).

**Attendance response (example):**
```json
{
  "success": true,
  "status": "success",
  "message": "Check-In Recorded",
  "action": "check_in",
  "student_name": "Jane Doe",
  "time": "08:15 AM"
}
```

**Fee check:** Returns balance breakdown; `success: false` when payment threshold not met (with data payload).

**Pickup:** Accepts pickup tokens (`PKUP-*`, `QR-*`) or student NFC tap; sets status to `scanned` and notifies class teacher.

**Report card:** Only **published** exam results returned; may block if institution has debt blocking enabled.

### 4.3 Today's Attendance List

**GET** `/v1/hardware/attendance/today`

**Auth:** `X-Hardware-Secret` + `X-Institution-Id` **OR** Sanctum user token

**Hardware headers:**
```
X-Hardware-Secret: ...
X-Institution-Id: 3
```

**Sanctum:** Results scoped by role (student → self, parent → children, teacher → classes, admin → institution).

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 101,
      "student_name": "Jane Doe (ADM-001)",
      "admission_no": "ADM-001",
      "photo": "https://.../storage/students/...",
      "time": "08:15 AM",
      "time_in": "08:15 AM",
      "time_out": "--:--",
      "action": "Arrival",
      "status": "Present",
      "status_color": "#10B981",
      "action_color": "#3B82F6"
    }
  ]
}
```

### 4.4 Teacher Class Absentees

**GET** `/v1/hardware/attendance/absentees`

**Auth:** Sanctum (Teacher)

Returns absentee report for teacher's assigned classes (multi-day structure). Used by teacher mobile dashboard.

---

## 5. Staff / Teacher Pickup API

**Prefix:** `/v1/pickup` — **Auth:** Sanctum

**Blocked roles:** Student, Guardian (receive empty or 403).

### 5.1 Pending Count

**GET** `/v1/pickup/count`

```json
{ "success": true, "count": 3 }
```

Scoped by institution and teacher's classes.

### 5.2 Pending Pickup List

**GET** `/v1/pickup/pending`

```json
{
  "success": true,
  "data": [
    {
      "pickup_id": 55,
      "student_name": "Jane Doe (ADM-001)",
      "admission_number": "ADM-001",
      "parent_phone": "+1234567890",
      "class_name": "Grade 5 A",
      "requested_by": "Parent App",
      "status": "scanned",
      "scanned_by_device": "GATE-01",
      "time": "14:30"
    }
  ]
}
```

### 5.3 Approve Pickup

**POST** `/v1/pickup/approve`

**Body:**
```json
{ "pickup_id": 55 }
```

**Authorization:** Teacher (student in assigned class), School Admin, Head Officer (same institution), Super Admin.

**Success:**
```json
{ "success": true, "message": "pickup_approved_success" }
```

Triggers parent SMS/WhatsApp if preferences enabled.

### 5.4 Generate OTP (Gate fallback)

**POST** `/v1/pickup/generate-otp`

**Auth:** Sanctum — Guardian, Staff, Admin roles (not Student)

**Body:**
```json
{ "student_id": 42 }
```

`student_id` may be numeric ID or admission number. Guardian must be linked to student; staff/admin must belong to same institution.

**Success:**
```json
{
  "success": true,
  "message": "pickup_otp_sent",
  "student_id": 42
}
```

OTP sent via SMS to registered parent phone; cached 15 minutes.

### 5.5 Verify OTP

**POST** `/v1/pickup/verify-otp`

**Body:**
```json
{
  "student_id": 42,
  "otp": "123456"
}
```

Creates pickup record with status `scanned`. Returns wait-for-teacher message.

---

## 6. Student Portal API

**Prefix:** `/v1/student` — **Auth:** Sanctum

**Allowed roles:** Student (own record) or Guardian (first linked child).

### 6.1 Attendance History

**GET** `/v1/student/attendance`

Last 30 records with date, subject, status, check-in/out times.

### 6.2 Fees & Invoices

**GET** `/v1/student/fees`

```json
{
  "success": true,
  "data": {
    "total_fees": 5000.00,
    "paid_fees": 3000.00,
    "outstanding": 2000.00,
    "invoices": [
      {
        "id": 10,
        "invoice_number": "INV-2025-001",
        "total_amount": 2500.00,
        "paid_amount": 1000.00,
        "status": "partial",
        "due_date": "2025-09-01"
      }
    ]
  }
}
```

### 6.3 Homework

**GET** `/v1/student/homework`

Assignments for enrolled class (typically 7-day window) with subject, title, deadline.

### 6.4 Results

**GET** `/v1/student/results`

Published exam records only. Returns `403`/empty if `block_reports_on_debt` enabled and student has outstanding fees.

### 6.4a LMD Transcript (University)

**GET** `/v1/student/lmd-transcript`

Optional query: `student_id` (guardian with multiple children).

Returns semester-by-semester LMD results (UE averages, credits, mention, decision) for university/LMD students. Returns `422` if student is not on a university cycle.

**Example response:**
```json
{
  "success": true,
  "data": {
    "student_name": "Jean Kabila",
    "admission_number": "120001",
    "institution": "Université Example",
    "sessions": {
      "2025-2026": {
        "semester_1": { "average": "14.50", "mention": "Bien", "decision": "Admis", "units": [] }
      }
    }
  }
}
```

### 6.5 Gate Pass (Pickup QR)

**POST** `/v1/student/gate-pass`

Generates pickup token/QR for authenticated student (or guardian's child). Returns token string and expiry for QR encoding.

### 6.6 Submit Request

**POST** `/v1/student/requests`

**Body:**
```json
{
  "type": "absence",
  "reason": "Medical appointment",
  "start_date": "2026-06-10",
  "end_date": "2026-06-10"
}
```

Types: `absence`, `late`, `sick`, `early_exit`, `leave`, `other`. Creates pending student request ticket.

---

## 7. Chatbot API

**Prefix:** `/v1/chatbot`

### 7.1 Webhook (Inbound Messages)

**POST** `/v1/chatbot/webhook/{provider}`

**Auth:** None (public endpoint — secure via provider signatures in production)

**Providers:** `infobip`, `twilio`, `meta`, `mobishastra`

Provider POSTs message payload; system normalizes to `{ from, to, body, provider }` and passes to `ChatbotLogicService`.

**Configure in provider dashboard:**
```
https://e-digitex.com/api/v1/chatbot/webhook/twilio
```

### 7.2 Generate Pickup QR (Authenticated)

**POST** `/v1/chatbot/generate-qr`

**Auth:** Sanctum

**Body:**
```json
{
  "student_id": 42,
  "otp": "123456",
  "requester_name": "Parent Name"
}
```

Verifies OTP then returns pickup QR/token data for WhatsApp flow integration.

---

## 8. Error Codes Reference

| HTTP | Meaning |
|------|---------|
| 401 | Missing/invalid token or hardware secret |
| 403 | Role not allowed or institution mismatch |
| 404 | Student/pickup/record not found |
| 422 | Validation failed |
| 503 | Hardware API disabled (no HARDWARE_SECRET) |

---

## 9. Hardware Integration Guide

### 9.1 Recommended Setup

1. Generate strong `HARDWARE_SECRET` (32+ chars).
2. Configure each gate device with secret and institution ID.
3. Register student/staff UIDs in admin panel before go-live.
4. Set school timings in Configuration → School year (`school_start_time`, `late_margin_time`, `double_tap_wait_time`).
5. Enable notification events: `student_arrival`, `student_departure` in Configuration.

### 9.2 Sample cURL — Attendance Scan

```bash
curl -X POST "https://e-digitex.com/api/v1/hardware/attendance/scan" \
  -H "Content-Type: application/json" \
  -H "X-Hardware-Secret: YOUR_SECRET" \
  -d '{"uid":"ADM-001","device_id":"GATE-1","purpose":"attendance","method":"rfid"}'
```

### 9.3 Sample cURL — Today's List

```bash
curl "https://e-digitex.com/api/v1/hardware/attendance/today" \
  -H "X-Hardware-Secret: YOUR_SECRET" \
  -H "X-Institution-Id: 3" \
  -H "Accept: application/json"
```

### 9.4 Sample cURL — Mobile Login

```bash
curl -X POST "https://e-digitex.com/api/v1/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"teacher@school.com","password":"secret"}'
```

### 9.5 Sample cURL — Authenticated Request

```bash
curl "https://e-digitex.com/api/v1/pickup/pending" \
  -H "Authorization: Bearer 1|token..." \
  -H "Accept: application/json"
```

---

## 10. Mobile App Integration Notes

### 10.1 Token Lifecycle

- Token issued at login; no refresh endpoint documented — re-login on 401.
- Store token in secure storage (Keychain/Keystore).
- Call `/v1/update-fcm-token` after login for push notifications.

### 10.2 Role-Based UI

Use `user.role` from login response to show:

| Role | Primary API surfaces |
|------|---------------------|
| Teacher | `/v1/pickup/*`, `/v1/hardware/attendance/absentees` |
| Student | `/v1/student/*` |
| Guardian | `/v1/student/*` (child context) |
| Admin | Broader web app; limited mobile pickup approval |

### 10.3 Pickup State Machine

```
pending → scanned → approved | rejected
```

Mobile teacher app lists `pending` and `scanned`; approve moves to `approved` and notifies parent.

### 10.4 Offline / Retry

Hardware scanners should retry POST on network failure with same `uid` + `timestamp`; server deduplicates check-in/out using double-tap wait setting.

---

## 11. Rate Limiting & Production

- Configure Laravel rate limiter for `/v1/login` to prevent brute force.
- Use HTTPS only in production.
- Rotate `HARDWARE_SECRET` if compromised; update all devices.
- Monitor `storage/logs/laravel.log` for hardware `Unknown UID` warnings.

---

## 12. Endpoint Summary Table

| Method | Endpoint | Auth |
|--------|----------|------|
| POST | /v1/login | Public |
| POST | /v1/update-fcm-token | Sanctum |
| GET | /v1/profile/ | Sanctum |
| POST | /v1/profile/update | Sanctum |
| POST | /v1/hardware/attendance/scan | Hardware secret |
| POST | /v1/hardware/attendance/bulk | Hardware secret |
| GET | /v1/hardware/attendance/today | Hardware secret + Institution ID OR Sanctum |
| GET | /v1/hardware/attendance/absentees | Sanctum |
| GET | /v1/hardware/attendance/absentees/today | Sanctum |
| POST | /attendance/terminal | Hardware secret (legacy) |
| POST | /attendance/bulk | Hardware secret (legacy) |
| GET | /student/{id}/profile | Sanctum + tenant |
| GET | /student/{id}/balance | Sanctum + tenant |
| GET | /student/{id}/attendance | Sanctum + tenant |
| GET | /student/{id}/results | Sanctum + tenant |
| GET | /v1/pickup/count | Sanctum |
| GET | /v1/pickup/pending | Sanctum |
| POST | /v1/pickup/approve | Sanctum |
| POST | /v1/pickup/generate-otp | Sanctum |
| POST | /v1/pickup/verify-otp | Sanctum |
| POST | /v1/pickup/scan | Sanctum + tenant |
| GET | /v1/student/attendance | Sanctum |
| GET | /v1/student/fees | Sanctum |
| GET | /v1/student/homework | Sanctum |
| GET | /v1/student/results | Sanctum |
| POST | /v1/student/gate-pass | Sanctum |
| POST | /v1/student/requests | Sanctum |
| POST | /v1/chatbot/webhook/{provider} | Public |
| GET | /v1/chatbot/webhook/{provider} | Public (Mobishastra) |
| POST | /v1/chatbot/webhook | Public (legacy) |
| POST | /v1/chatbot/generate-qr | Sanctum |
| POST | /v1/chatbot/pickup/generate-qr | Sanctum + tenant |
| POST | /v1/chatbot/verify-student | Sanctum + tenant |
| POST | /v1/chatbot/verify-staff | Sanctum + tenant |
| POST | /v1/chatbot/auth/request-otp | Sanctum + tenant |
| GET | /v1/chatbot/institution/summary | Sanctum + tenant |
| GET | /v1/chatbot/student/balance | Sanctum + tenant |
| GET | /v1/chatbot/student/result | Sanctum + tenant |
| GET | /v1/chatbot/student/homework | Sanctum + tenant |
| GET | /v1/chatbot/student/fees | Sanctum + tenant |
| GET | /v1/chatbot/institution/events | Sanctum + tenant |
| POST | /v1/chatbot/student/derogation | Sanctum + tenant |
| POST | /v1/chatbot/student/request | Sanctum + tenant |

---

*End of REST API Manual*
