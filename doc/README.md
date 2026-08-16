# Digitex SMS Documentation

This folder contains user, developer, and API documentation for the Digitex School Management System.

## Source files

| File | Description |
|------|-------------|
| `markdown/user-manual.md` | **Web admin** module-by-module guide with examples (Green Valley school), SMS/WhatsApp setup, flows, FAQs |
| `markdown/deployment-roadmap.md` | **4-phase deployment plan** (DRC focus, no inventory module) — track go-live tasks |
| `go-live-checklist.md` | **Production go-live checklist** per school |
| `markdown/mobile-app-user-manual.md` | **Mobile app (Digitex Portal)** complete guide for all roles |
| `markdown/developer-manual.md` | **Module-by-module** technical reference: routes, models, permissions, scoping, integrations |
| `markdown/api-manual.md` | REST API for hardware scanners and mobile apps |
| `markdown/chafon-hardware-bridge-manual.md` | **Chafon CF661** bridge: configure, test, run `chafon-script.py` |
| `markdown/whatsapp-voice-ivr-phase1-design.md` | **DigitexVx Voice Phase 1** — WhatsApp Calling + IVR design (Infobip) |
| `markdown/whatsapp-voice-ivr-help-manual.md` | **WhatsApp Voice IVR** — school go-live guide (readiness, Infobip, settings, test call) |
| `markdown/reenrollment-confirmation-help-manual.md` | **Re-enrollment confirmation** — campaign, parent WhatsApp option 10, review and approve |
| `markdown/homework-workflow-and-medical-records.md` | **Homework approval, WhatsApp homework alerts, infirmary records** — per-school settings, schema, access control |

## PDF output

Generated PDFs are written to `pdf/`:

- `User-Manual.pdf`
- `Mobile-App-User-Manual.pdf`
- `Developer-Manual.pdf`
- `REST-API-Manual-(Hardware-&-Mobile-App).pdf`
- `Chafon-Hardware-Bridge-Manual.pdf`
- `WhatsApp-Voice-IVR-Help-Manual.pdf`
- `Re-enrollment-Confirmation-Help-Manual.pdf`

## Regenerate PDFs

From the project root:

```bash
php artisan docs:generate-pdf
```

## Production URL

All schools log in at the same address: **https://e-digitex.com/** (no per-school subdomains).

Requires `barryvdh/laravel-dompdf` (already in composer.json).

## Notes

- Edit the Markdown sources, then re-run the command to refresh PDFs.
- PDF layout template: `resources/views/doc/pdf-layout.blade.php`
- Markdown converter: `app/Support/MarkdownToHtml.php`
