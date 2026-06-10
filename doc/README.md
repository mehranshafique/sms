# Digitex SMS Documentation

This folder contains user, developer, and API documentation for the Digitex School Management System.

## Source files

| File | Description |
|------|-------------|
| `markdown/user-manual.md` | **Module-by-module** end-user guide with examples (Green Valley school), SMS/WhatsApp setup, flows, FAQs |
| `markdown/developer-manual.md` | **Module-by-module** technical reference: routes, models, permissions, scoping, integrations |
| `markdown/api-manual.md` | REST API for hardware scanners and mobile apps |

## PDF output

Generated PDFs are written to `pdf/`:

- `User-Manual.pdf`
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
