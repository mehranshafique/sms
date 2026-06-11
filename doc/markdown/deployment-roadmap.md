# Digitex SMS — Deployment Roadmap (4 Phases)

**Last updated:** June 2026  
**Scope:** Multi-type schools (primary, secondary, mixed, university, vocational) for DRC and regional deployment.  
**Excluded:** Inventory module (not required).

All user-facing strings must use `resources/lang/{en,fr}/` translation keys.

---

## Phase 1 — Deploy now (operational readiness)

| # | Task | Status | Notes |
|---|------|--------|-------|
| 1.1 | Head/responsible person on institution registration | Done | `head_person_name`, `head_person_phone` on `institutions` |
| 1.2 | Personalized dashboard welcome (user + school + session) | Done | `dashboard.welcome_personalized` lang key |
| 1.3 | Fix LMD validation threshold (use institution setting, not hardcoded 10) | Done | `LmdCalculationService` |
| 1.4 | Compute LMD **mention** (Passable, Assez Bien, Bien, Très Bien) | Done | Transcript + service |
| 1.5 | Re-enable **mixed** institution type (primary + secondary campus) | Done | `InstitutionType` enum + migration |
| 1.6 | Fix vocational type label (not "Mixed Level") | Done | `InstitutionType::label()` |
| 1.7 | Production migrations: `student_absent` SMS template, head person fields | Pending | Run on server |
| 1.8 | Remove or hide Inventory menu stub | Done | Sidebar only |
| 1.9 | Add Staff Leave to sidebar | Done | Route exists at `/staff-leaves` |
| 1.10 | Per-school setup checklist doc for go-live | Done | `doc/go-live-checklist.md` |

---

## Phase 2 — DRC differentiation

| # | Task | Status | Notes |
|---|------|--------|-------|
| 2.1 | Complete DRC geography (26 provinces + major communes) | Done | `LocationSeeder` + `drc_provinces.php` |
| 2.2 | EPST-style bulletin PDF templates (configurable header) | Done | `epst_header` partial + institution code |
| 2.3 | EXETAT / Examen d'État module (6e primaire, 8e secondaire) | Done | `StateExamController`, routes, views |
| 2.4 | Student fields: province d'origine, national ID (optional) | Done | Migration + student form |
| 2.5 | Congolese grading mentions on primary/secondary bulletins | Done | `GradeMentionService` on bulletins |
| 2.6 | Mobile Money payment method (Orange, Airtel, M-Pesa) — record + reference | Done | Payment UI + model columns |
| 2.7 | Dual currency display (CDF + USD equivalent) | Done | Institution `secondary_currency` + `CurrencyDisplayService` |
| 2.8 | French-first validation messages for all new fields | Done | EN/FR lang keys |

---

## Phase 3 — University / LMD completeness

| # | Task | Status | Notes |
|---|------|--------|-------|
| 3.1 | Deliberation / jury workflow (validate UEs, semester decision) | Done | `LmdDeliberationController` + views |
| 3.2 | Session de rattrapage (resit exams) | Done | Exam categories + LMD recalc |
| 3.3 | Advanced compensation rules (inter-UE, semester level) | Done | Existing `LmdCalculationService` compensation |
| 3.4 | ESU-aligned transcript PDF option | Done | `transcript_lmd_esu.blade.php`, `?format=esu` |
| 3.5 | Mobile app LMD transcript for students | Done | `GET /api/v1/student/lmd-transcript` |
| 3.6 | Preset program templates (Licence 6 sem., Master 4 sem.) | Done | `LmdProgramTemplateSeeder` |
| 3.7 | Split inscription académique vs administrative | Done | `enrollment_type` on enrollments |

---

## Phase 4 — Product polish & hardening

| # | Task | Status | Notes |
|---|------|--------|-------|
| 4.1 | Library module (optional) OR keep disabled | Done | Kept disabled (no stub in sidebar) |
| 4.2 | Transport module | Done | Vehicles, routes, student assignments |
| 4.3 | Domain tests: enrollment, LMD calc, bulletins, API auth | Done | `GradeMentionServiceTest`, `InstitutionHeadPersonTest` |
| 4.4 | Mobile offline queue for NFC scans | Pending | Flutter + API (future sprint) |
| 4.5 | Guardian web portal expansion | Done | Fees, results, requests at `/guardian` |
| 4.6 | API manual sync with `/v1/me/context`, timetable, teacher attendance | Done | Updated `api-manual.md` |
| 4.7 | Regenerate all PDF manuals after doc changes | Pending | Run `php artisan docs:generate-pdf` on server |

**Note:** Inventory module is explicitly **out of scope** per product decision.

---

## Institution registration — responsible person (Phase 1.1)

When Super Admin or Head Officer creates a school/university:

| Field | DB column | Required |
|-------|-----------|----------|
| Head / Responsible Person Name | `head_person_name` | Yes (create) |
| Head / Responsible Person Phone | `head_person_phone` | Yes (create) |

The responsible person's name is used as the **School Admin** account display name. Phone is stored on both `institutions` and the admin `users` record.

---

## Dashboard welcome format (Phase 1.2)

**English:** `Welcome, :name! :school Academic Year: :session`  
**French:** `Bienvenue, :name! :school Année scolaire/académique : :session`

Example: *Welcome, Moutard! Reka School Academic Year: 2025-2026*

---

## Translation key conventions

- Module keys: `institute.*`, `dashboard.*`, `lmd.*`, `reports.*`
- Always add **both** `resources/lang/en/` and `resources/lang/fr/`
- Use `:name`, `:school`, `:session` placeholders — never hardcode user-facing text in Blade

---

## Go-live

See **[go-live-checklist.md](../go-live-checklist.md)** before production deployment.

**Migrations to run:** `2026_06_10_*`, `2026_06_11_000001_phase234_platform_features.php`
