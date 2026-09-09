# Release Notes — French UI & Attendance Schedules

**Digitex / Integrale Plus** — School Admin, Head Officer, Super Admin, QA.

This document describes the **September 2026 update**: French localization fixes for finance and DataTables, fee grade section display, and **attendance schedules by level/section**. Use it as a release note and a test checklist before production.

---

## 1. Summary of what was built

| Area | What changed |
| --- | --- |
| French UI | DataTables Search / Show entries / pagination use locale (`pagination.*`). Fee frequency and payment mode no longer show raw English enums. |
| Fee list — Niveau scolaire | Grade-wide fees show a compact section range (e.g. `1e A-E`) instead of only `1e (Toutes les sections)`. |
| Attendance schedules | Named check-in / check-out / late-margin schedules assignable to **grade levels** and optionally **class sections**. Device/kiosk punches use the student’s class schedule for Present / Late. |
| Permissions | New module permissions: `attendance_schedule.view|create|update|delete|viewAny|deleteAny`. |
| Docs | This release note PDF. |

---

## 2. French UI localization (finance & tables)

### Problem
Francophone schools saw English in French locale: `Search`, `Show entries`, `yearly`, `termly`, `Installment (1)`, and fee grade labels that did not show which sections a fee covered.

### Fixes
- Global DataTables language defaults from `resources/views/layout/footer.blade.php` using `__('pagination.*')`.
- Helpers: `finance_frequency_label()`, `finance_payment_mode_label()`, `finance_fee_grade_label()`, `compact_section_name_range()`.
- Wired into Fee Structures list, invoices fee picker, student finance views, class summary report.
- New / updated EN+FR keys (`finance`, `parent`, `student`, `pagination` consumers).
- Cursor rule: `.cursor/rules/french-ui-localization.mdc`.

### Display examples (locale = `fr`)

| Stored value | Shown as |
| --- | --- |
| `yearly` | Annuel |
| `termly` | Trimestriel |
| `installment` + order 1 | Tranche 1 |
| Grade `1e`, sections A–E, no class_section_id | `1e A-E` |
| Grade `1e`, sections A,B,D | `1e A-B, D` |
| Single section A | `1e A` |
| Grade with no sections | `1e (Toutes les sections)` |

---

## 3. Attendance schedules by level / section

### Problem
One institution-wide `school_start_time` / `late_margin_time` cannot support:

- Kindergarten: 07:45–11:30  
- Primary: 07:15–12:15  
- Secondary: 12:30–17:30  

Afternoon secondary students were often judged against a morning school start.

### Solution
1. Create named **Attendance schedules** (check-in, optional check-out, late margin).
2. Assign to **grade levels** (e.g. all Primary grades) and/or **class sections** (e.g. PM shift override).
3. On device or kiosk punch, Digitex loads the student’s enrollment class, resolves the schedule, then marks Present or Late.

### Resolution order

1. Schedule assigned to the **class section**  
2. Else schedule assigned to the section’s **grade level**  
3. Else **Configuration** default (`school_start_time` + `late_margin_time`)

### Punch behaviour (students)

| Institution type | Status logic |
| --- | --- |
| Primary / secondary (day gate) | Always vs schedule check-in + late margin (timetable periods ignored for day Present/Late). |
| University / vocational (subject-wise) | Inside a timetable period: Present/Late vs period start + schedule late margin. Before any period: schedule check-in. |

- Check-out remains the second punch after `double_tap_wait_time`.  
- Staff attendance still uses institution school start (staff schedules are out of scope for this release).

### Menu & setup
- Sidebar: **Attendance → Attendance schedules** / **Horaires de présence**  
- Configuration → School Year timings remains the **fallback** and still controls staff / hardware defaults.  
- After deploy: run migrations and ensure `RolePermissionSeeder` (or equivalent) has created `attendance_schedule.*` permissions for School Admin.

### Database
- `attendance_schedules`  
- `attendance_schedule_assignments` (exactly one of `grade_level_id` or `class_section_id` per row)

### Key code paths
- `app/Services/Attendance/AttendanceScheduleResolver.php`  
- `app/Services/Attendance/AttendanceGateStatusService.php`  
- `app/Http/Controllers/Api/V1/AttendanceApiController.php` (kiosk reuses this API)  
- `app/Http/Controllers/AttendanceScheduleController.php`

---

## 4. Deploy steps

1. Pull this release on the server.  
2. Run:

```bash
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force
php artisan optimize:clear
```

3. Confirm School Admin can open **Attendance → Attendance schedules**.  
4. Configure schedules for Kindergarten / Primary / Secondary (or your grade names), then assign grades (and section overrides if needed).  
5. Switch UI locale to French and spot-check Fee Structures + Attendance.

---

## 5. Test plan (QA checklist)

### A. French UI — Fee Structures

| # | Steps | Expected |
| --- | --- | --- |
| A1 | Set locale to French. Open Fee Structures list. | DataTables: Rechercher, Afficher _ entrées, pagination in French (not Search / Show entries). |
| A2 | Fee with frequency `yearly`. | Cell shows **Annuel**, not `yearly`. |
| A3 | Fee with frequency `termly`. | Cell shows **Trimestriel**, not `termly`. |
| A4 | Installment fee order 1. | **Tranche 1**, not `Installment (1)`. |
| A5 | Grade-wide fee for `1e` with sections A–E active. | Niveau scolaire ≈ **`1e A-E`**, not only Toutes les sections. |
| A6 | Fee for a single section. | Compact label such as **`1e A`**. |
| A7 | Switch locale to English. | Labels become Yearly / Termly / Installment 1 / `1e A-E`. |

### B. Attendance schedules — admin UI

| # | Steps | Expected |
| --- | --- | --- |
| B1 | Open Attendance → Attendance schedules. | Index loads; Create button available for School Admin. |
| B2 | Create “Primary morning”: check-in 07:15, check-out 12:15, late margin 10; assign Primary grade(s). | Saved; list shows times and assigned grades. |
| B3 | Create “Secondary afternoon”: check-in 12:30, check-out 17:30, late margin 5; assign Secondary grade(s). | Saved. |
| B4 | Create “Primary PM” with section override only (one PM section). | That section uses 13:00 (example); other Primary sections still use grade schedule. |
| B5 | Leave one grade unassigned. | Students in that grade use Configuration school start + late margin. |
| B6 | Deactivate a schedule. | Assigned classes fall through to next rule (grade or institution). |
| B7 | French locale on schedule screens. | Titles and labels in French (Horaires de présence, etc.). |

### C. Device / kiosk Present & Late

Use a test student enrolled in each class. Punch via kiosk or hardware API.

| # | Scenario | Expected status |
| --- | --- | --- |
| C1 | Primary schedule 07:15 + margin 10; punch 07:20. | **present** |
| C2 | Same student; punch 07:30. | **late** |
| C3 | Secondary schedule 12:30 + margin 5; punch 12:32. | **present** (must not be late vs 08:00 school default) |
| C4 | Same secondary student; punch 12:40. | **late** |
| C5 | Section override 13:00; punch 13:00. | **present** |
| C6 | Second punch after cooldown. | Check-out set; status unchanged. |
| C7 | Staff punch. | Still uses institution school start (unchanged). |

### D. Regression

| # | Steps | Expected |
| --- | --- | --- |
| D1 | Manual class attendance marking. | Still works; teacher chooses status. |
| D2 | Subject-wise (university) school: punch during a period. | Present/Late vs period start; subject may attach. |
| D3 | Invoice fee picker / student fees display in French. | No raw `yearly` / `installment` in UI. |

---

## 6. Out of scope (this release)

- Staff attendance schedules  
- Auto half-day / early leave from check-out time  
- Day-of-week variants on one schedule  
- Assigning by education cycle enum alone (use grade levels)

---

## 7. Support tips for schools

1. Name schedules clearly: e.g. **Maternelle matin**, **Primaire**, **Secondaire après-midi**.  
2. Prefer assigning **grades** first; use **section overrides** only for AM/PM splits inside the same grade.  
3. Keep Configuration school hours as a sensible default for unassigned classes and for staff.  
4. After changing schedules, test one student punch per level before opening the gate for the day.

---

*End of release notes.*
