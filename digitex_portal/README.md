# Digitex Portal (Flutter)

Mobile app for Digitex SMS — synced with Laravel `/api/v1` as of 2026.

## Setup

```bash
cd digitex_portal
flutter pub get
```

Edit `lib/config/env.dart` with your API base URL (e.g. `https://account.digitexvx.com/api`).

## Run

```bash
flutter run
```

## Architecture

- **API-driven menu** — tiles come from `GET /v1/me/context` → `menu` (respects subscription modules + capabilities).
- **Role switching** — `POST /v1/me/switch-role` for multi-role accounts (admin + guardian, etc.).
- **Notifications** — `GET /v1/notifications/feed` (poll every 30s on dashboard).
- **Gate Terminal** — when `capabilities.gate_mode` is true, simplified 2×4 grid layout.

See `../doc/markdown/flutter-api-sync.md` for full API reference.
