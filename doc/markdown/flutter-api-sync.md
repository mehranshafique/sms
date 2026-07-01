# Flutter / Mobile App — API Sync Guide

The **Digitex Portal** Flutter app lives in `digitex_portal/` inside this repository.

## Base URL

```
{APP_URL}/api/v1/
```

Example: `https://account.digitexvx.com/api/v1/`

Configure in `digitex_portal/lib/config/env.dart` or build with:

```bash
flutter run --dart-define=API_BASE_URL=https://your-domain.com/api
```

---

## What changed (2026 sync with web SMS)

| Feature | API | Flutter |
|---------|-----|---------|
| **Subscription / module gating** | `enabled_modules`, `subscription` in context | Menu tiles hidden when module not in plan |
| **Multi-role switching** | `POST /v1/me/switch-role` | Header role switcher |
| **Server-driven menu** | `menu.staff_tools`, `menu.student_portal`, `menu.gate_terminal` | Dashboard reads tiles from API |
| **In-app notifications** | `GET /v1/notifications/feed` | Bell badge + notifications screen (30s poll) |
| **Plan on institution create** | Web only (Super Admin form) | Mobile reads `subscription.plan_name` |
| **Localized request reasons** | Student requests API | Show `localized_reason` when added to API |

---

## Auth & context

| Action | Endpoint |
|--------|----------|
| Login | `POST /v1/login` |
| Context refresh | `GET /v1/me/context` |
| Switch role | `POST /v1/me/switch-role` `{ "role": "Guardian" }` |
| Logout | `POST /v1/logout` |
| FCM token | `POST /v1/update-fcm-token` |

**Login / context response** includes:

- `active_role`, `switchable_roles`
- `capabilities` (gate_mode, student_portal, teacher_tools, …)
- `enabled_modules` (from subscription + super-admin override)
- `subscription` (`plan_name`, `active`, `expires_at`, `days_left`)
- `menu` (layout + tile list with `id`, `title`, `subtitle`, `icon`, `route`)
- `features` (`notifications`, `role_switching`, `support_tickets`, `ai_copilot`)
- `currency`, `children` (guardians)

---

## Notifications (mobile)

| Action | Endpoint |
|--------|----------|
| Feed | `GET /v1/notifications/feed?limit=15` |
| Mark read | `POST /v1/notifications/{id}/read` |
| Mark all read | `POST /v1/notifications/read-all` |

Poll every **30 seconds** while the dashboard is open (matches web bell behaviour).

---

## Student portal

| Endpoint | Notes |
|----------|-------|
| `GET /v1/student/fees` | Use `currency_settings` |
| `GET /v1/student/requests` | Fee extension, absence, etc. |
| `POST /v1/student/requests` | Submit new request |
| `GET /v1/student/attendance-summary` | Parent summary |

Pass `?student_id=` for guardians with multiple children.

---

## Flutter project structure

```
digitex_portal/
  lib/
    main.dart                 # App entry + routing
    config/theme.dart         # Digitex purple theme (matches web)
    core/api/api_client.dart
    core/services/session_service.dart
    core/models/mobile_context.dart
    features/
      auth/login_screen.dart
      dashboard/              # Standard + gate terminal layouts
      notifications/
      profile/
```

Run:

```bash
cd digitex_portal && flutter pub get && flutter run
```

---

## Testing checklist

- [ ] Login loads menu from API (not hardcoded tiles)
- [ ] Premium school without `staff` module — Staff tiles hidden
- [ ] Multi-role user can switch Guardian ↔ Teacher
- [ ] Gate attendant sees Gate Terminal grid only
- [ ] Notification bell updates within 30s after admin event
- [ ] Subscription expired — warning banner on dashboard

See also: [api-manual.md](./api-manual.md), [mobile-app-user-manual.md](./mobile-app-user-manual.md).
