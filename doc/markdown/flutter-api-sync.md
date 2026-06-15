# Flutter / Mobile App — API Sync Guide

The Digitex Portal Flutter app lives in a **separate repository**. This document lists backend changes the mobile team must align with (as of the Laravel SMS codebase).

## Base URL

```
{APP_URL}/api/v1/
```

Example production: `https://account.digitexvx.com/api/v1/` or `https://e-digitex.com/api/v1/`

## Required client updates

### 1. Auth & context

| Action | Endpoint | Notes |
|--------|----------|-------|
| Login | `POST /v1/login` | Response `user.currency` object added |
| Refresh context | `GET /v1/me/context` | Use after login; includes `currency`, `capabilities`, `children` |
| Logout | `POST /v1/logout` | Revoke Sanctum token |
| FCM | `POST /v1/update-fcm-token` | Unchanged |

**Currency object shape** (login + context + fees):

```json
{
  "code": "CDF",
  "symbol": "FC",
  "name": "Congolese Franc",
  "position": "before",
  "decimals": 2
}
```

Flutter should format amounts with `CurrencyService` rules: symbol before/after based on `position`, `decimals` for fraction digits. Do **not** hardcode `$`.

### 2. Student portal (`/v1/student/*`)

| Endpoint | Change |
|----------|--------|
| `GET /v1/student/fees` | Adds `currency_code`, `currency_settings`; use for all fee labels |
| `GET /v1/student/payment-options` | Unchanged path; ensure app implements pay + proof flow |
| `POST /v1/student/payment-proof` | Upload offline payment proof |
| `GET /v1/student/lmd-transcript` | University/LMD schools |
| `GET /v1/student/requests` | Request history |

### 3. Teacher / gate / pickup

| Area | Endpoints |
|------|-----------|
| Teacher attendance | `GET/POST /v1/teacher/attendance/*` |
| Hardware NFC (app) | `POST /v1/hardware/attendance/scan` with Sanctum **or** gate device secret |
| Pickup | `/v1/pickup/count`, `/pending`, `/approve`, `/scan`, OTP routes |
| Today scans | `GET /v1/hardware/attendance/today` |
| Absentees + notify | `GET /v1/hardware/attendance/absentees`, `POST .../notify` |

**Capabilities** from context — gate attendants get `gate_mode: true`, hide teacher tools.

### 4. Timetable & notices

- `GET /v1/timetable/today`, `/week`
- `GET /v1/notices`

### 5. Deprecated — do not use

- `GET /api/student/{id}/balance` (legacy chatbot) — use `/v1/student/fees`
- `POST /attendance/terminal` without `/v1` prefix — prefer `/v1/hardware/attendance/scan`

## Suggested Flutter implementation

1. **ApiClient** — single base URL + `/v1` prefix, Bearer token interceptor.
2. **AuthRepository** — login → store token → call `/v1/me/context` → cache `currency` + `capabilities`.
3. **MoneyFormatter** — read from cached `currency_settings`; refresh on context reload.
4. **ModuleRouter** — show/hide tiles from `capabilities` (student_portal, pickup_management, gate_mode, etc.).
5. **Guardian flow** — pass `student_id` query param on student APIs when multiple `children`.

## Testing checklist

- [ ] Login shows welcome + loads context
- [ ] Fees screen uses CDF/FC (or school-configured currency), not hardcoded USD
- [ ] Gate mode user sees scan UI only
- [ ] Pickup bell polls `/v1/pickup/count`
- [ ] Logout clears token and calls `/v1/logout`

See also: [api-manual.md](./api-manual.md), [mobile-app-user-manual.md](./mobile-app-user-manual.md).
