# Release Notes — Dashboard, PWA, Subjects & Academic Fixes

**Digitex / Integrale Plus** — School Admin, Head Officer, Super Admin, QA.

This document covers the **September 2026 follow-up update** (commit `e47f2db` and related work): livelier dashboards, PWA back navigation, attendance schedule grade/section filter, academic sessions DataTables fix, class-course list fix, and **multi-grade central subjects**.

Use it as a release note and a test checklist on staging (**account.digitecvx.com**) before live (**e-digitex.com**).

---

## 1. Summary of what changed

| Area | What changed |
| --- | --- |
| Dashboard cards | KPI cards animate on load, show accent edges, soft icon pulse, and share meters. Attendance block uses metric chips and colour rate badges. |
| Attendance overview | Same visual system; each KPI shows count plus percent of expected; rate bar under student/staff panels. |
| PWA back arrow | Header back button on mobile and standalone PWA (not on dashboard). Goes to previous screen or dashboard. |
| Attendance schedules | Section dropdown only lists sections of the **selected grades** (e.g. grades 1 and 4 will not show 3A). |
| Academic sessions | Fixed DataTables Ajax error on Sessions list (`sessionTable`). |
| Class courses list | Class dropdown shows all sections (ordered by grade), including inactive ones marked Inactive. |
| Subjects / Courses | Create a subject once and assign it to **many grade levels**. Class Courses, timetables, and exams pick subjects for that grade from the shared catalogue. |

---

## 2. Deploy steps (staging or live)

After pulling `main`:

```bash
php artisan migrate --force
php artisan optimize:clear
```

Optional if permissions were not seeded recently:

```bash
php artisan db:seed --class=RolePermissionSeeder --force
```

Clear browser cache or reopen the PWA so header/CSS updates appear.

---

## 3. Feature details

### 3.1 Dashboard and attendance overview

- Shared `dash-*` styles: fade-up entrance, left colour accent, shimmer meters, live rate badges (green / amber / red by threshold).
- School dashboard: enrollment / paid / unpaid / personnel meters; today’s attendance chips for students and staff.
- Attendance overview: clickable KPIs still open person lists; meters show share of expected headcount.

### 3.2 PWA / mobile back arrow

- Appears left of the page title on **mobile** and when the app is installed as **standalone PWA**.
- Hidden on the dashboard home.
- Behaviour: browser history back; if none, go to Dashboard.
- Labels: EN **Back** / FR **Retour**.

### 3.3 Attendance schedule — section filter

- Select grades first (e.g. 1e and 4e).
- Section overrides only list sections belonging to those grades.
- Server validation rejects sections outside the selected grades.

### 3.4 Academic sessions list

- Fixed Ajax DataTables warning (invalid order/search on related columns).
- Status labels localized (Active / Planned / Closed and French equivalents).
- Dates shown as `Y-m-d` for stability.

### 3.5 Class courses — missing classes

- Previously only **active** sections appeared, unordered.
- Now: all institution sections, sorted by grade order then section name.
- Inactive sections still appear with “(Inactive)” / “(Inactif)”.

### 3.6 Multi-grade subjects (central catalogue)

**Problem:** Mathematics / French were recreated for every grade.

**New workflow:**

1. Open **Subjects / Cours**.
2. Create **Mathematics** once.
3. In **Grade levels**, multi-select every grade that teaches it (1e, 2e, 3e…).
4. Save.
5. Open **Cours par classe**, pick a class (e.g. 1e - A). Mathematics appears if that grade was assigned.
6. Enable the subject, set teacher, weekly periods, exam weight as before.

Existing subjects were migrated into a grade pivot automatically. Timetable, exam schedule, assignments, and marks use the same multi-grade lookup.

**Note:** Each class allocation still keeps its own teacher and periods. The shared subject is only the catalogue entry.

---

## 4. Test plan (QA checklist)

### A. Dashboard and overview UI

| # | Steps | Expected |
| --- | --- | --- |
| A1 | Log in as School Admin. Open Dashboard. | KPI cards animate in; meters visible under key stats. |
| A2 | Check Today’s attendance panel. | Student/staff chips and coloured % badges; progress bars fill. |
| A3 | Open Attendance overview for today. | Hero + KPI meters; rate badge colour matches threshold. |
| A4 | Click Present / Absent / Late KPI. | Detail modal still loads the correct people. |
| A5 | Switch dark mode (optional). | Cards remain readable. |

### B. PWA / mobile back

| # | Steps | Expected |
| --- | --- | --- |
| B1 | On phone browser or installed PWA, open Students then a student profile. | Back arrow visible in header. |
| B2 | Tap back. | Returns to previous page. |
| B3 | Open Dashboard. | Back arrow hidden. |
| B4 | Desktop wide screen (optional). | Back arrow hidden unless PWA standalone. |

### C. Attendance schedules filter

| # | Steps | Expected |
| --- | --- | --- |
| C1 | Attendance → Attendance schedules → Create/Edit. | Grade multi-select and section multi-select present. |
| C2 | Select only grades 1e and 4e. | Section list shows only 1e-* and 4e-* sections, not 3e-A. |
| C3 | Change grades; remove a grade that had a section selected. | That section disappears from selection. |
| C4 | Save a valid schedule. | Success; list shows assignments. |

### D. Academic sessions DataTables

| # | Steps | Expected |
| --- | --- | --- |
| D1 | Open Academics → Sessions. | No DataTables Ajax alert. Table loads rows. |
| D2 | Use Search. | Filters without error. |
| D3 | French locale. | Status shows Active / Planifiée / Clôturée style labels, not raw English crash. |

### E. Class courses dropdown

| # | Steps | Expected |
| --- | --- | --- |
| E1 | Create or confirm sections for several grades (e.g. 1e–6e). | Sections exist under Class Sections. |
| E2 | Open Cours par classe dropdown. | All those classes appear (searchable), ordered by grade. |
| E3 | If a section is inactive. | Still listed with Inactive marker. |

### F. Multi-grade subjects

| # | Steps | Expected |
| --- | --- | --- |
| F1 | Subjects → Add subject “Test Math Shared”. Assign grades 5e and 6e. Save. | Created once; list grade column shows both grades. |
| F2 | Cours par classe → select 5e - A. | Test Math Shared appears in the allocation table. |
| F3 | Select 6e - B. | Same subject appears. |
| F4 | Assign teacher + periods on 5e - A and save. | Allocation saved for that class/session only. |
| F5 | Timetable or exam schedule subject picker for 5e. | Shared subject is available. |
| F6 | Edit subject; add grade 4e. | 4e classes can now allocate it without recreating the subject. |

### G. Regression smoke

| # | Steps | Expected |
| --- | --- | --- |
| G1 | Manual student attendance marking. | Still works. |
| G2 | Fee Structures French labels (from prior release). | Annuel / Trimestriel / Tranche still correct. |
| G3 | Attendance device/kiosk punch (if hardware available). | Present/Late still follows attendance schedules. |

---

## 5. Out of scope

- Automatic merge of old duplicate subjects with the same name across grades (admins can reassign grades on one subject and retire duplicates manually).
- Staff attendance schedules.
- Changing teacher assignment globally when editing the subject catalogue (teachers stay on class-course allocations).

---

## 6. Support tips for schools

1. Prefer **one subject name per school** (Mathematics, French) with multiple grades selected.  
2. Use **Cours par classe** for teacher and weekly periods per section.  
3. If a class is missing from Cours par classe, check Class Sections exists for that school; inactive classes still appear but are labelled.  
4. After deploy, always run **migrate** so the subject–grade pivot table exists.

---

*End of release notes.*
