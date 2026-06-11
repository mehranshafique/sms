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

## PDF output

Generated PDFs are written to `pdf/`:

- `User-Manual.pdf`
- `Mobile-App-User-Manual.pdf`
- `Developer-Manual.pdf`
- `REST-API-Manual-(Hardware-&-Mobile-App).pdf`

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
