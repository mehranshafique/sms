# Digitex SMS — Go-Live Checklist

Use this checklist before opening a school or university on production.

## Server & database

- [ ] Merge new env keys (keeps existing secrets): `php artisan env:merge`
- [ ] Or full post-deploy: `php artisan app:deploy --env-merge`
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Seed DRC locations (only on fresh DB): `php artisan db:seed --class=LocationSeeder`
- [ ] Seed SMS templates if needed: `php artisan db:seed --class=SmsTemplateSeeder`
- [ ] Configure `.env`: `APP_URL`, database, mail, SMS provider
- [ ] Set `APP_DEBUG=false` and secure `APP_KEY`

## Institution setup

- [ ] Create institution with **head person name** and **phone**
- [ ] Set **EPST school code** (primary/secondary) if applicable
- [ ] Configure **secondary currency** and **exchange rate** (CDF/USD) if needed
- [ ] Upload logo and verify bulletin header
- [ ] Activate academic session and mark as current
- [ ] Enable required modules in Configuration

## Users & roles

- [ ] School Admin can log in and see personalized dashboard welcome
- [ ] Teachers, accountants, students, guardians assigned correct roles
- [ ] Guardian accounts linked to children (parent phone/email match)

## Academics

- [ ] Grade levels, classes, subjects, timetables configured
- [ ] Exam categories created (periods, trimester/semester exams, LMD sessions)
- [ ] Marks entry tested; bulletins print with **mention**
- [ ] LMD deliberation generated and validated (universities)
- [ ] EXETAT / state exam candidates registered (6e primaire, 8e secondaire)

## Finance

- [ ] Fee structures and invoices generated
- [ ] Mobile Money payments recorded with reference (Orange, Airtel, M-Pesa, Vodacom)
- [ ] Financial clearance blocks reports when configured

## Mobile app

- [ ] API base URL points to production `/api/v1`
- [ ] Student/guardian login, attendance, fees, results tested
- [ ] LMD transcript endpoint: `GET /api/v1/student/lmd-transcript`
- [ ] NFC pickup and teacher attendance tested on device

## Communication

- [ ] SMS credits loaded; `student_absent` template present
- [ ] Test absent notification to guardian phone

## Documentation

- [ ] Staff trained using `doc/markdown/user-manual.md`
- [ ] Mobile users have `doc/markdown/mobile-app-user-manual.md`
- [ ] Regenerate PDFs: `php artisan docs:generate-pdf`

## Post go-live

- [ ] Monitor `storage/logs/laravel.log` for 48 hours
- [ ] Backup schedule confirmed (database + `storage/app`)

---

See also: [deployment-roadmap.md](markdown/deployment-roadmap.md)
