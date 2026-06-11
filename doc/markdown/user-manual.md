# Digitex School Management System
# Complete User Manual — Module by Module

**Audience:** School administrators, teachers, accountants, parents, and staff who are **not technical experts**.  
**Goal:** Explain every part of the system in plain language, with real-world examples, so you know *what to click*, *why it exists*, and *what must be done first*.

> **Mobile app users:** For the Digitex Portal Android app (NFC gate, pickup, student portal, teacher attendance), see **`mobile-app-user-manual.md`** in this folder — a separate complete guide for all app roles with example login values and step-by-step workflows.

---

## How to Read This Manual

Each chapter describes **one module** (one area of the software). Every chapter includes:

- **Purpose** — Why this module exists
- **Who uses it** — Which job roles need it
- **Depends on** — What you must set up before using it
- **Step-by-step** — How to use it, with example values
- **Example story** — A short scenario like a real school day
- **Common questions** — Answers a normal user would ask

**Example school used throughout this manual:**

- **Platform URL (all schools):** https://e-digitex.com/
- **School name:** Green Valley International School  
- **Short code:** GVIS  
- **Current session:** 2025-2026  
- **Sample class:** Grade 5 Section A  
- **Sample student:** Marie Kouassi, Admission No. GVIS-2025-0142  
- **Sample teacher:** Mr. Jean Dupont  
- **Sample parent phone:** +225 07 12 34 56 78  

---

# PART A — BEFORE YOU START

---

## Module A1: Logging In and Understanding Your Screen

### Purpose

The login screen is the front door. After login, you see the **dashboard** (summary page), the **sidebar menu** on the left, and the **top bar** with search, notifications, and school name.

### Who uses it

Everyone: Super Admin, School Admin, Teacher, Student, Parent, Accountant, Staff.

### Step-by-step — First login

**Important:** All schools use the **same website address**. There is no separate link per school (no subdomains).

1. Open your browser and go to: **https://e-digitex.com/login**  
   (You can also start at **https://e-digitex.com/** — it redirects to login.)
2. Enter your **email** or **username**. Example: `admin@gvis.edu.ci`
3. Enter your **password**. Example: (provided by your IT officer)
4. Click **Login**.
5. The system opens the school linked to your account. If you manage **more than one school** (Head Officer / Super Admin), click the **building icon** top-right and choose **Green Valley International School** (or another assigned school).

### Common questions (login URL)

**Q: My colleague gave me a different link — is it wrong?**  
A: The official address for every school is **https://e-digitex.com/**. Bookmark that page only.

**Q: How does the system know my school if the URL is the same for everyone?**  
A: Your **user account** is tied to your institution when it was created. Multi-school users pick the active school from the **institution switcher** in the header after login.

### Top bar — what each button means

| Element | What it does (simple words) |
|---------|----------------------------|
| Menu (☰) | Opens/closes the left menu |
| Search (🔍) | Click the small circle, then type a name — finds students, staff, invoices, pages |
| Year badge (2025-2026) | Shows which academic year you are working in |
| Bell (🔔) | Alerts inside the app — new invoice, request approved, notice published, etc. |
| Building icon | Switch to another school (Head Officers / Super Admin) |
| Profile | Change password, photo, personal details |

### Example story

Mrs. Aya is the school secretary. She logs in Monday morning, switches to Green Valley, and sees on the dashboard: **420 active students**, **18 teachers**, and **12 unpaid invoices this week**. She clicks the bell and sees: *"Marie Kouassi's parent submitted a leave request."*

### Common questions

**Q: I forgot my password.**  
A: Use **Forgot password** on the login page, or ask your School Admin to reset it.

**Q: I don't see a menu item my colleague has.**  
A: Your **role** or **permissions** may be different, or your school's **subscription package** may not include that module.

**Q: Why does the dashboard show different numbers from last year?**  
A: Make sure the **active academic session** is set to 2025-2026 under Academic Sessions.

---

## Module A2: Dashboard

### Purpose

The dashboard is your **control room**. It shows the most important numbers and shortcuts for **your role** — not everyone sees the same dashboard.

### Who uses it

All logged-in users (content varies by role).

### Depends on

- Institution must exist and be selected
- Active academic session should be configured
- Data in other modules (students, invoices, etc.) fills the statistics

### What you typically see

| Role | Example widgets |
|------|-----------------|
| School Admin | Total students, staff, enrollment, financial summary, timetables count |
| Teacher | Class-related shortcuts, pending pickups, own timetable |
| Accountant | Fees collected, outstanding balances, recent payments |
| Student / Parent | Notices, marks link, own fees (where enabled) |
| Super Admin | All schools overview, subscriptions, platform stats |

### Example story

The School Admin opens the dashboard on the first day of term and sees **Enrollment: 420** but expects 450. She goes to **Student Enrollments** to find 30 students not yet placed in a class.

### Common questions

**Q: Numbers look wrong.**  
A: Check you selected the correct school (building icon) and the session **2025-2026** is marked as **current**.

---

## Module A3: Global Search and In-App Notifications

### Purpose

- **Search:** Find anything quickly without clicking through menus.
- **Notifications (bell):** See what happened recently — payment recorded, exam published, pickup scanned, etc.

### Who uses it

All staff roles with login access. Students/parents see limited search results.

### How to search

1. Click the **small magnifier circle** in the top bar (it expands).
2. Type at least **2 letters**, e.g. `Marie` or `INV-2025`.
3. Click a result to open that student, invoice, or page.

### Notifications

- Red badge = unread count.
- Click an item to open the related page; it marks as read.
- **Mark all read** clears the badge.

Your School Admin can turn off certain alert types under **Configuration → Notification Settings → In-App (Bell)** column.

### Common questions

**Q: I paid fees but parent says no SMS.**  
A: SMS is separate from the bell. Check **Configuration → Notification preferences → Payment Received → SMS** is ON, and the school has SMS credits.

---

# PART B — PLATFORM ADMINISTRATION (Usually Super Admin / Head Officer)

---

## Module B1: Institutions (Schools)

### Purpose

An **institution** is one school or university in the system. Super Admin creates each tenant (e.g. Green Valley, Blue Coast Academy).

### Who uses it

**Super Admin** primarily. Head Officers see assigned institutions only.

### Depends on

Nothing — this is usually step one for a new deployment.

### Step-by-step — Create a school

1. Menu → **Institutions** → **Add New**
2. Fill example values:
   - **Name:** Green Valley International School
   - **Code:** GVIS
   - **Type:** Secondary
   - **Email:** contact@gvis.edu.ci
   - **Phone:** +225 27 00 00 00 00
   - **Address:** Cocody, Abidjan
3. Upload **logo** (optional) — appears on reports and mobile app login.
4. Save.
5. Assign a **subscription/package** so modules are enabled (see Module B7).

### Example story

Digitex platform owner adds a new client school "Lycée Moderne de Yamoussoukro" with code `LMY` and assigns the "Standard Package" subscription.

### Depends on for other modules

Almost **everything** else requires an institution to exist first.

### Common questions

**Q: Can one person manage two schools?**  
A: Yes — **Head Officer** role with multiple institutions assigned, or Super Admin global view.

---

## Module B2: Campuses

### Purpose

If one school has **multiple physical sites** (Main Campus, Annex Branch), campuses organize students and staff by location.

### Who uses it

School Admin, Super Admin.

### Depends on

Institution created.

### Example

- Institution: Green Valley International School  
- Campus 1: **Main Campus — Cocody**  
- Campus 2: **Annex — Bingerville**  

When registering Marie Kouassi, assign her to **Main Campus — Cocody**.

### Common questions

**Q: We have only one building. Do we need campuses?**  
A: Optional. Many schools create one default campus and use it for all records.

---

## Module B3: Head Officers

### Purpose

A **Head Officer** oversees **several schools** (e.g. a chain of 5 academies). They switch between schools using the header switcher or view a **Global Dashboard**.

### Who uses it

**Super Admin** creates Head Officer accounts.

### Depends on

Institutions must exist.

### Step-by-step

1. Menu → **Head Officers** → **Create**
2. Example:
   - **Name:** Mr. Ibrahim Koné
   - **Email:** ibrahim.kone@digitex-group.com
   - **Password:** (temporary — user should change)
   - **Assign institutions:** Green Valley, Blue Coast, Sunrise Primary
3. Save. Mr. Koné logs in and uses the building icon to switch schools.

### Common questions

**Q: Difference between Head Officer and School Admin?**  
A: **School Admin** manages **one** school deeply. **Head Officer** monitors **many** schools at summary level.

---

## Module B4: Roles and Permissions

### Purpose

Controls **who can do what**. Example: allow teachers to mark attendance but **not** delete invoices.

### Who uses it

Super Admin, School Admin (for their institution's custom roles).

### Depends on

Modules seeded in system (done during installation).

### Plain language

- **Role** = job title bucket (Teacher, Accountant)
- **Permission** = single action (e.g. `invoice.create` = can create invoices)

### Step-by-step — Create custom role "Senior Accountant"

1. Menu → **Roles** → **Add Role**
2. Name: **Senior Accountant**, Institution: Green Valley
3. Open **Assign Permissions**
4. Enable: Invoices (view, create), Payments (view, create), Fee Structures (view)
5. Disable: Student delete, Settings manage
6. Save.
7. Assign this role to user `finance@gvis.edu.ci` in Staff module.

### Common questions

**Q: Teacher sees "403 Forbidden".**  
A: Their role lacks permission for that page. School Admin adds the permission.

---

## Module B5: Configuration Hub (Critical — SMS, WhatsApp, Email, Notifications)

### Purpose

The **Configuration** area is where the school connects the system to the **outside world**: email server, SMS gateway, WhatsApp Business API, notification toggles, school hours, and academic calendar dates.

### Who uses it

Super Admin, Head Officer, School Admin.

**Super Admin only (system-wide SMS/WhatsApp defaults):** must switch to **Global View** in the top header first — see **B5.0** below.

### Depends on

Institution selected in header (or **Global View** for platform-wide communication defaults).

---

### B5.0 System Default SMS & WhatsApp (Super Admin — Global View only)

**Purpose:** Set the **platform-wide** SMS and WhatsApp providers and API credentials. These replace static `.env` values (`SMS_DRIVER`, `MOBISHASTRA_*`, etc.) for every school that chooses **System Default (Digitex Credits)**.

**Who uses it:** **Super Admin only.**

**Important — you must use Global View:**

1. Log in at **https://e-digitex.com/login**
2. Click the **building icon** (institution switcher) in the top-right header
3. Select **Global View** — the header should show "Global View", not a school name
4. Menu → **Configuration** → **ID Sender SMS** tab
5. Confirm the blue **Global Mode** badge appears on the Communication Setup card

If you open Configuration while a school (e.g. Green Valley) is selected, you will only edit **that school's** settings — not the system defaults. A yellow warning appears reminding you to switch to Global View.

**Step-by-step — configure system defaults:**

1. Under **Provider Availability Control**, tick which providers schools may use individually (e.g. Mobishastra, Infobip, Twilio, SignalWire for SMS; Meta, Infobip, Twilio for WhatsApp)
2. Set **System Default SMS Provider** — e.g. **Mobishastra**
3. Set **System Default WhatsApp Provider** — e.g. **Meta**
4. Open the **API Credentials** tabs and enter platform credentials (example Mobishastra):

| Field | Example value |
|-------|---------------|
| Username (Profile ID) | INTEGRALE |
| Password | (your Mobishastra password) |
| Sender ID | ARCHIDIOKIN |

5. Click **Save Changes**
6. Use **Configuration → Test Notifications** to send a test SMS/WhatsApp

**How schools use this:**

- Each school can set **Active SMS Provider** = **System Default (Digitex Credits)** — messages use your Global View credentials and deduct **SMS credits** from that school
- Or a school can pick its **own** provider (e.g. Twilio) and enter its own API keys — no credit deduction for that channel

**Common questions:**

**Q: I saved Mobishastra in `.env` but the UI still shows another provider.**  
A: Global View settings in the database **override** `.env`. Change them in Global View, or remove the global DB rows to fall back to `.env`.

**Q: Can I configure this without Global View?**  
A: No. System defaults are stored with no institution (`Global View` context). Always switch via the header building icon first.

---

### B5.1 SMTP (Email) — Example Setup

**Purpose:** Send invoice emails, password resets, and email notifications.

**Steps:**

1. Configuration → **SMTP** tab
2. Example values (using Gmail-style SMTP — your IT team provides real ones):

| Field | Example value |
|-------|---------------|
| Mail Host | smtp.gmail.com |
| Port | 587 |
| Encryption | TLS |
| Username | notifications@gvis.edu.ci |
| Password | (app password from Google) |
| From Address | notifications@gvis.edu.ci |
| From Name | Green Valley School |

3. Click **Save**, then **Send Test Email** to `secretary@gvis.edu.ci`.
4. If test succeeds, enable **Email** column for events like *Invoice Created* under Notification Settings.

**Common question:** *Emails go to spam.* — Ask IT to set SPF/DKIM records for your domain.

---

### B5.2 SMS Provider — Example Setup (School level)

**Purpose:** Send fee reminders, OTP codes, arrival SMS to parents.

**Note for Super Admin:** Platform-wide defaults are configured in **B5.0 (Global View)**. This section is for a **single school's** own provider or for choosing **System Default (Digitex Credits)**.

**Supported providers (examples):** Mobishastra, Infobip, Twilio, SignalWire.

**Steps:**

1. Select the school in the header (or stay in Global View only for B5.0)
2. Configuration → **SMS / WhatsApp** tab
3. Choose **Active SMS Provider**:
   - **System Default (Digitex Credits)** — uses Super Admin global config (B5.0); credits deducted
   - **Twilio** (or other) — school uses its own API keys; no credit deduction
4. If using your own provider, fill credentials (example for Twilio):

| Field | Example value |
|-------|---------------|
| Account SID | ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx |
| Auth Token | (secret — paste once, stored encrypted) |
| From Number | +15551234567 |

4. **Sender ID** (if provider uses alphanumeric): `GVIS`
5. Save → **Send Test SMS** to `+2250712345678`
6. Message should arrive: *"Test message from E-Digitex (14:32:05)"*

**Mobishastra example:**

| Field | Example |
|-------|---------|
| User | gvis_sms_user |
| Password | (secret) |
| Sender ID | GVIS |

**Credits:** SMS costs credits. Super Admin recharges under **Configuration → Recharging**.

---

### B5.3 WhatsApp Provider — Example Setup

**Purpose:** Parents receive homework alerts, payment confirmations, pickup messages on WhatsApp.

**Common providers:** Meta Cloud API, Infobip, Twilio.

**Meta (Facebook) WhatsApp example:**

| Field | Example value |
|-------|---------------|
| Phone Number ID | 123456789012345 |
| Business Account ID | 987654321098765 |
| Access Token | EAAxx... (long token from Meta Developer Console) |

**Twilio WhatsApp example:**

| Field | Example |
|-------|---------|
| WhatsApp From | whatsapp:+14155238886 |

**Steps:**

1. Select **WhatsApp Provider** = Meta (or Twilio)
2. Paste credentials → Save
3. Test tab → send test WhatsApp to parent phone
4. Under **Notification Settings**, enable WhatsApp for **Payment Received**, **Student Arrival**, etc.

**Webhook for chatbot:** Point Meta/Twilio webhook to:  
`https://e-digitex.com/api/v1/chatbot/webhook/meta` (or `twilio`)

---

### B5.4 Notification Preferences (SMS / WhatsApp / Email / In-App Bell)

**Purpose:** Decide **which events** send **which type** of message.

**Steps:**

1. Configuration → **Notification Settings**
2. You see a table of events, e.g.:

| Event | SMS | WhatsApp | Email | In-App (Bell) |
|-------|-----|----------|-------|---------------|
| Payment Received | ☑ | ☑ | ☐ | ☑ |
| Invoice Created | ☐ | ☑ | ☑ | ☑ |
| Student Arrival (RFID) | ☑ | ☑ | ☐ | ☐ |
| Exam Results Published | ☐ | ☑ | ☐ | ☑ |

3. Check boxes → **Save Changes**

**Example policy at Green Valley:**

- All fee events → WhatsApp ON (parents prefer WhatsApp)
- Staff leave requests → In-App bell ON for admins only
- Marketing bulk SMS → OFF (use Reminders module manually instead)

**Default behavior:** If you never saved preferences, **in-app bell** is ON by default for most events; SMS/WhatsApp are OFF until you enable them (to avoid accidental charges).

---

### B5.5 School Year and Gate Timings

**Purpose:** Tell the system when school starts, when a student is "late", and how long between check-in and check-out at RFID gates.

| Setting | Example | Meaning |
|---------|---------|---------|
| Academic Start Date | 2025-09-01 | First day of year |
| Academic End Date | 2026-07-15 | Last day |
| School Start Time | 08:00 | Classes begin |
| School End Time | 15:00 | Classes end |
| Late Margin | 15 minutes | Arrival before 08:15 = on time |
| Double Tap Wait | 15 minutes | Must wait 15 min between IN and OUT scan |

**Depends on:** Used by **Student Attendance** (hardware and manual) and reports.

---

### B5.6 SMS Templates

**Purpose:** Edit the **wording** of automatic messages. Templates use tags like `$StudentName`, `$Amount`, `$SchoolName`.

**Example template — Payment Received:**

```
Dear Parent, payment of $Amount for $StudentName has been received.
Remaining Balance: $Balance. Thank you, $SchoolName.
```

**Steps:** Configuration → SMS Templates → select event → edit body → Save.

---

### B5.7 Enabled Modules (Super Admin)

**Purpose:** Turn entire feature areas on/off per school subscription.

**Example:** Sunrise Primary package includes Students + Invoices but NOT Elections. The Elections menu will not appear for that school.

---

## Module B6: Settings (School Operational Rules)

### Purpose

Different from Configuration: **Settings** controls **academic rules** inside daily operations — lock attendance after 7 days, lock exam marks, block report cards if fees unpaid.

### Who uses it

School Admin (with `setting.manage` permission).

### Depends on

Institution selected.

### Example values at Green Valley

| Setting | Value | Effect |
|---------|-------|--------|
| Lock attendance after | 7 days | Teachers cannot edit last week's attendance |
| Lock exams after | 30 days | Marks frozen after a month |
| Block reports on debt | ON | Marie cannot download report card if she owes 50,000 XOF |
| LMD validation threshold | 50% | University pass rule |
| Active marking periods | Term 1, Term 2 | Teachers only enter marks for open terms |

### Common questions

**Q: Teacher cannot edit yesterday's attendance.**  
A: Grace period may have expired — Admin increases **attendance grace period** in Settings.

---

## Module B7: Packages and Subscriptions

### Purpose

Digitex is sold as **packages** (bundles of modules). Each school needs an **active subscription** to use the system.

### Who uses it

Super Admin.

### Example

- **Package name:** Standard Secondary  
- **Modules included:** Students, Invoices, Attendance, Exams, Notices  
- **Subscription:** Green Valley, Start 2025-01-01, End 2026-01-01, Status Active  

If subscription expires, users see a warning; after grace period access may be limited.

---

## Module B8: Audit Logs

### Purpose

Security trail — who changed what and when (Super Admin investigation).

### Who uses it

Super Admin only.

### Example entry

*"User admin@gvis.edu.ci updated Invoice INV-2025-0089 at 2025-11-03 14:22"*

---

# PART C — ACADEMICS MODULES

---

## Module C1: Academic Sessions

### Purpose

An **academic session** is one school year, e.g. **2025-2026**. Almost all enrollments, fees, and exams belong to a session.

### Who uses it

School Admin, Registrar.

### Depends on

Institution.

### Step-by-step

1. Menu → **Academic Sessions** → **Add**
2. **Name:** 2025-2026  
3. **Start date:** 2025-09-01  
4. **End date:** 2026-07-15  
5. Check **Set as current session** ☑  
6. Save.

Only **one** session should be "current" at a time.

### Example story

Green Valley runs two sessions in database (2024-2025 archived, 2025-2026 current). All new enrollments automatically tie to 2025-2026.

### Modules that depend on this

Student Enrollments, Fee Structures, Invoices, Exams, Reports.

### Common questions

**Q: We started a new year but old fees show.**  
A: Filter invoices by session 2025-2026, or generate new fee structures for the new session.

---

## Module C2: Departments (Universities)

### Purpose

Groups university subjects and staff — e.g. **Department of Computer Science**, **Department of Economics**.

### Who uses it

University / LMD institutions.

### Depends on

Institution, Academic session (for enrollments).

### Example

Department: **Sciences** → Programs: **Licence Informatique**, **Licence Mathématiques**

### School (K-12) note

Primary/secondary schools may skip Departments and use Grade Levels directly.

---

## Module C3: Grade Levels

### Purpose

The **grade/year** label: Grade 1, Grade 2, … Form 6, CP, CE1 (French system), etc.

### Who uses it

School Admin.

### Depends on

Institution.

### Example at Green Valley

| Grade name | Order |
|------------|-------|
| CP | 1 |
| CE1 | 2 |
| … | … |
| Terminale | 12 |

### Depends on for

Class Sections, Fee Structures (fees often differ per grade).

---

## Module C4: Class Sections

### Purpose

A **class section** is a group of students taught together: **Grade 5 Section A**, **Grade 5 Section B**.

### Who uses it

School Admin, Teachers (view own classes).

### Depends on

Grade Levels, Academic Session, optionally Staff (homeroom teacher).

### Step-by-step

1. **Class Sections** → **Add**
2. **Name:** Section A  
3. **Grade Level:** Grade 5  
4. **Homeroom Teacher:** Mr. Jean Dupont  
5. **Capacity:** 35  
6. Save.

### Example story

Green Valley has Grade 5 split into A (35 students) and B (32 students). Marie Kouassi is in **Grade 5 Section A**.

### Modules that depend on this

Enrollments, Timetables, Attendance (by class), Exam marks, Assignments.

---

## Module C5: Subjects

### Purpose

Courses taught: **Mathematics**, **French**, **Physical Education**, **Physics**.

### Who uses it

School Admin, University Admin.

### Depends on

Institution; for universities also **Department** or **Academic Unit**.

### Example

| Subject | Code | Type |
|---------|------|------|
| Mathematics | MATH-G5 | Core |
| English | ENG-G5 | Core |
| Art | ART-G5 | Optional |

---

## Module C6: Class Subjects (Subject Allocation)

### Purpose

Links **which subjects** are taught in **which class**, and **which teacher** teaches each.

### Who uses it

School Admin, Academic coordinator.

### Depends on

Class Sections + Subjects + Staff (teachers).

### Example

**Grade 5 Section A:**

| Subject | Teacher |
|---------|---------|
| Mathematics | Mr. Jean Dupont |
| French | Mrs. Aminata Traoré |
| Science | Mr. Paul N'Guessan |

### Depends on for

Timetables, subject-wise attendance, exam marks per subject.

---

## Module C7: Timetables

### Purpose

Weekly schedule — which subject happens **Monday 08:00–09:00** in which room.

### Who uses it

School Admin creates; Teachers and Students view.

### Depends on

Class Subjects allocated, Teachers assigned.

### Example (Grade 5A — excerpt)

| Day | Time | Subject | Teacher | Room |
|-----|------|---------|---------|------|
| Monday | 08:00-09:00 | Mathematics | Mr. Dupont | Room 12 |
| Monday | 09:00-10:00 | French | Mrs. Traoré | Room 12 |

**Print / Download PDF** available for posting on classroom door.

### Common questions

**Q: Teacher clash — same time two classes.**  
A: System may warn on availability check; adjust slot or assign different teacher.

---

## Module C8: Assignments (Homework)

### Purpose

Teachers publish homework with **title**, **description**, **deadline**. Students and parents see it in portal and mobile app.

### Who uses it

Teachers create; Students/Parents view.

### Depends on

Class Sections, Subjects, Teacher permissions.

### Example

- **Title:** Exercise pages 45–48  
- **Subject:** Mathematics  
- **Class:** Grade 5A  
- **Due:** 2025-10-15 23:59  
- **Created by:** Mr. Dupont  

Marie's parent sees this in the mobile app under **Homework**.

---

## Module C9: Programs and Academic Units (LMD / University)

### Purpose

**LMD** (Licence-Master-Doctorat) structure:

- **Program:** e.g. Licence Informatique (6 semesters)  
- **Academic Unit (UE):** e.g. UE101 — Algorithmics (groups subjects)

### Who uses it

University administrators.

### Depends on

Departments, Academic Sessions.

### Example flow

1. Create Program **Licence Informatique**, 6 semesters, 3 years  
2. Create UE **UE101** Semester 1  
3. Assign subjects **Algorithmics**, **Programming 1** to UE101  
4. Enroll student into Program via **University Enrollments**

---

# PART D — PEOPLE MODULES (Students & Parents)

---

## Module D1: Student Parents (Guardians)

### Purpose

Store **mother, father, guardian** contact details and link to portal login for fee viewing and pickup.

### Who uses it

School Admin, Registrar.

### Depends on

Institution.

### Example record

| Field | Value |
|-------|-------|
| Father name | Kouassi Emmanuel |
| Father phone | +225 07 12 34 56 78 |
| Mother name | Kouassi Aya |
| Primary contact | Father |
| User account | parent.kouassi@gvis.edu.ci |

### Depends on for

Student registration (link child), SMS notifications, Pickup QR, mobile app.

---

## Module D2: Students

### Purpose

The **heart of the system** — every learner's profile: identity, photo, class, RFID card, login.

### Who uses it

School Admin, Registrar; Teachers view limited info.

### Depends on

Parents (optional at create), Grade/Class (can assign later), Campus.

### Step-by-step — Register Marie Kouassi

1. **Students** → **Add Student**
2. Personal:
   - **First name:** Marie  
   - **Last name:** Kouassi  
   - **Admission No.:** GVIS-2025-0142 (auto or manual)  
   - **Date of birth:** 2014-03-12  
   - **Gender:** Female  
3. Link **Parent:** Kouassi family record  
4. **Campus:** Main Campus — Cocody  
5. Optional hardware:
   - **RFID UID:** A1B2C3D4 (printed on card)  
   - **QR token:** (auto-generated for gate pass)  
6. Upload **photo**  
7. Save → system can create **student login** (shortcode + password)

### Example story

Marie receives login shortcode **GVIS0142** and password. She uses the mobile app to see homework and results.

### Modules that depend on Students

Enrollments, Invoices, Attendance, Exams, Pickup, Requests, Elections.

### Common questions

**Q: Gate doesn't recognize card.**  
A: Verify RFID UID in student profile matches physical card; check for typos (0 vs O).

---

## Module D3: Student Enrollments (Schools K-12)

### Purpose

Places a student in a **class section** for the **current session**.

### Who uses it

Registrar, School Admin.

### Depends on

Students, Class Sections, Academic Session (current).

### Step-by-step

1. **Enrollments** → **New Enrollment**
2. **Student:** Marie Kouassi  
3. **Class:** Grade 5 Section A  
4. **Session:** 2025-2026  
5. **Status:** Active  
6. Save.

Without enrollment, Marie won't appear in class attendance lists or class-based invoices.

---

## Module D4: University Enrollments

### Purpose

Enrolls student in **Program / Semester / UE** path (universities).

### Depends on

Programs, Academic Units, Students.

### Example

Marie (university student) → Program **Licence Informatique** → Semester 2 → UE **UE202**

---

## Module D5: Student Promotion

### Purpose

Move a **whole cohort** to the next grade at year end (e.g. all Grade 5 → Grade 6).

### Who uses it

School Admin.

### Depends on

Grade Levels, Enrollments, new Class Sections for next grade.

### Example

Select **Grade 5 Section A** → Promote to **Grade 6 Section A** for session 2026-2027.

---

## Module D6: Student Transfers

### Purpose

Move one student **out** to another school or **in** from elsewhere, with printable transfer certificate.

### Example

Marie transfers from Green Valley to Blue Coast — admin marks transfer, prints document for new school.

---

# PART E — ATTENDANCE & REQUESTS

---

## Module E1: Student Attendance (Manual)

### Purpose

Record who is **present**, **absent**, **late** each day or per subject.

### Who uses it

Teachers, School Admin.

### Depends on

Enrollments, Class Sections, optionally Timetables (subject mode).

### Step-by-step — Daily attendance Grade 5A

1. **Attendance** → **Mark Attendance**
2. Select **Class:** Grade 5 Section A  
3. **Date:** 2025-10-06  
4. Mark each student: Marie = Present, Paul = Late, Aisha = Absent  
5. Save.

### Example story

Mr. Dupont marks morning attendance on his phone browser. At 08:20 Marie is marked **Late** (school starts 08:00, margin 15 min).

---

## Module E2: Student Attendance (Hardware / RFID Gate)

### Purpose

Automatic check-in when student taps card at gate — no manual typing.

### Who uses it

Gate devices (configured by IT); results viewed by Admin/Teachers.

### Depends on

Student RFID/NFC UID in profile, **Configuration** school timings, **HARDWARE_SECRET** set by IT, SMS preferences for arrival messages.

### Flow (simple)

1. Marie taps card at gate 07:55  
2. System records **Check-In**  
3. Parent receives WhatsApp: *"Marie arrived at Green Valley at 07:55"* (if enabled)  
4. Marie taps again at 15:10 → **Check-Out** / Departure message  

### Common questions

**Q: Double tap too fast shows error.**  
A: Increase **Double Tap Wait Time** in Configuration (e.g. 15 minutes between IN and OUT).

---

## Module E3: Attendance Analytics

### Purpose

Charts and reports — absence trends, class comparison, monthly summaries.

### Depends on

Attendance data from Module E1/E2.

---

## Module E4: Student Requests (Tickets)

### Purpose

Students or parents **ask permission** for absence, lateness, sick leave, early exit — tracked with ticket number.

### Who uses it

Students/Parents submit; Admin approves/rejects.

### Depends on

Student enrolled, notification preferences for updates.

### Example

1. Marie's mother submits **Sick leave** Oct 6–7, reason: fever  
2. Ticket: **REQ-K7M2P9QX**  
3. Secretary sees bell notification: *New student request*  
4. Admin approves → parent gets WhatsApp: *Request REQ-K7M2P9QX approved*

### Common questions

**Q: Teacher cannot see requests.**  
A: By design teachers may not view all requests — only Admin roles process them.

---

# PART F — STAFF & HR

---

## Module F1: Staff

### Purpose

Employee records: teachers, accountants, guards, cleaners — linked to login account.

### Who uses it

School Admin, HR.

### Depends on

Institution, Roles.

### Example — Create Mr. Jean Dupont

| Field | Value |
|-------|-------|
| Name | Jean Dupont |
| Email | j.dupont@gvis.edu.ci |
| Role | Teacher |
| Employee ID | EMP-T-014 |
| RFID (staff gate) | STAFF-9F3A2B |
| Department | Sciences |
| Phone | +225 05 11 22 33 44 |

---

## Module F2: Staff Attendance

### Purpose

Same concept as student attendance but for employees — manual or RFID.

---

## Module F3: Staff Leave

### Purpose

Staff request days off; Admin approves.

### Example

Mr. Dupont requests leave Dec 20–22, type **Annual leave**, reason: family travel. Status **Pending** → Admin approves → Jean gets bell notification.

---

## Module F4: Salary Structures

### Purpose

Define pay components: basic salary, allowances, deductions per staff grade.

### Example

Teacher Grade A: Base 250,000 XOF + Transport 25,000 XOF.

---

## Module F5: Payroll

### Purpose

Generate **monthly payslips** from salary structures.

### Flow

1. Ensure salary structures assigned  
2. **Payroll** → Generate for **November 2025**  
3. Review list → Confirm  
4. Staff view/download payslip PDF  

---

# PART G — EXAMINATIONS & RESULTS

---

## Module G1: Exams

### Purpose

Define an examination event: **First Term Exam 2025**, **Mock BAC**, etc.

### Depends on

Academic Session.

### Example

- **Name:** First Term Examination 2025  
- **Session:** 2025-2026  
- **Category:** Term Exam  
- **Status:** Draft → Published (when ready)

**Publish** triggers student notifications and makes results visible (if marks entered).

---

## Module G2: Exam Schedules

### Purpose

Timetable of exam dates per subject/class; generate **admit cards**.

### Example

| Date | Subject | Class | Room | Time |
|------|---------|-------|------|------|
| 2025-11-10 | Mathematics | Grade 5A | Hall B | 08:00 |

---

## Module G3: Exam Marks (Marks Entry)

### Purpose

Teachers enter scores per student per subject.

### Depends on

Exams, Class Subjects, Enrollments, Settings (active periods, exam lock).

### Example

Marie Kouassi — Mathematics — First Term: **16/20**

---

## Module G4: Result Cards and Academic Reports

### Purpose

Print **report cards**, **bulletins**, **transcripts** (including full LMD transcript).

### Depends on

Published exams, marks entered, optionally fee clearance (Settings block on debt).

### Example story

Marie owes 75,000 XOF. **Block reports on debt** is ON — report card PDF shows message to visit finance office. After payment, report unlocks.

---

# PART H — FINANCE MODULES

---

## Module H1: Fee Types

### Purpose

Categories of charges: **Tuition**, **Registration**, **Transport**, **Canteen**, **Exam Fee**.

### Example

| Fee Type | Code |
|----------|------|
| Tuition | TUITION |
| Transport | TRANSPORT |

---

## Module H2: Fee Structures

### Purpose

**How much** each grade pays, in **how many installments (tranches)**.

### Depends on

Fee Types, Grade Levels, Academic Session.

### Example — Grade 5 Tuition 2025-2026

| Tranche | Due date | Amount (XOF) |
|---------|----------|--------------|
| Tranche 1 | 2025-09-15 | 150,000 |
| Tranche 2 | 2025-12-15 | 150,000 |
| Tranche 3 | 2026-03-15 | 150,000 |

Total annual tuition: **450,000 XOF**

---

## Module H3: Invoices

### Purpose

Bill sent to student/parent for fees owed.

### Depends on

Fee Structures, Students.

### Step-by-step — Generate class invoices

1. **Invoices** → **Bulk Generate**
2. **Class:** Grade 5 Section A  
3. **Fee structure:** Grade 5 Tuition Tranche 1  
4. Generate → creates INV-2025-0142 for Marie, etc.

### Example invoice

- **Number:** INV-2025-0089  
- **Student:** Marie Kouassi  
- **Amount:** 150,000 XOF  
- **Status:** Unpaid  

Parent receives WhatsApp + bell notification if enabled.

---

## Module H4: Payments

### Purpose

Record money received — cash, bank transfer, mobile money.

### Example

1. Parent pays 150,000 XOF at office  
2. Accountant opens **Payments** → **Record Payment**  
3. **Invoice:** INV-2025-0089  
4. **Amount:** 150,000  
5. **Method:** Cash  
6. **Date:** 2025-09-14  
7. Save → Invoice status **Paid**, parent notified.

---

## Module H5: Student Balances and Statements

### Purpose

See total owed across all invoices; download **PDF statement** for parent meetings.

### Example statement line

| Date | Description | Debit | Credit | Balance |
|------|-------------|-------|--------|---------|
| 2025-09-01 | Invoice INV-2025-0089 | 150,000 | — | 150,000 |
| 2025-09-14 | Payment TRX-9912 | — | 150,000 | 0 |

---

## Module H6: Budgets and Fund Requests

### Purpose

Internal school spending control — departments get **budget envelopes**; staff **request funds** for activities.

### Example

- **Budget:** Science Department 2025-2026 — Allocated **2,000,000 XOF**, Spent **800,000 XOF**  
- **Fund request:** Mr. N'Guessan requests **150,000 XOF** for lab equipment → Finance approves → spent amount increases  

---

## Module H7: Payment Methods Configuration

### Purpose

Configure which payment options your school accepts — **Cash**, **Bank transfer**, **Orange Money**, **Airtel Money**, **M-Pesa/Vodacom**, and **Card/Online** — instead of hardcoded choices. These settings control both **office payment recording** and **parent online pay pages**.

### Menu path

**Finance → Fees & Collection → Payment Methods**

### Step-by-step — Enable methods

1. Log in as **School Admin** or **Accountant**.
2. Open **Payment Methods**.
3. Toggle **Online payments** ON to allow public pay links.
4. For each payment method row:
   - Check **Enabled**
   - Fill **Merchant code** (Mobile Money) or **Bank details** (transfer)
   - Add **Instructions** parents will see on the pay page
5. Toggle **Manual proof upload** ON (recommended backup when gateway is unavailable).
6. Click **Save settings**.

### Example — Orange Money (DRC)

| Field | Example value |
|-------|----------------|
| Merchant code | `123456` |
| Instructions | Dial *144# → Pay Merchant → enter 123456 → amount → confirm PIN |

### Depends on

Finance module enabled, active institution selected (building icon, top-right).

---

## Module H8: Online Invoice Payment Links

### Purpose

Share a **secure pay link** with parents so they can pay fees **without logging in**. Each invoice can have a unique token URL.

### How it works

1. Accountant generates or opens an invoice (Module H3).
2. On the invoice detail page, copy the **Online Payment Link** (or regenerate if needed).
3. Send link via **WhatsApp**, **SMS**, or email.
4. Parent opens link → sees amount due and payment options.

### Public invoice lookup (no link)

If the parent lost the link:

1. Open **https://e-digitex.com/pay** (or your school domain + `/pay`).
2. Enter **Invoice number** (e.g. INV-2025-0089) and **Student admission number**.
3. System finds the invoice and opens the pay page.

### Pay page tabs

| Tab | What happens |
|-----|----------------|
| **Pay instantly** | Mobile Money via PawaPay, CinetPay, or Flutterwave — invoice marked **Paid** automatically |
| **Upload proof** | Parent submits receipt + transaction ID — status **Pending** until accountant approves (Module H10) |

### Example workflow

1. Invoice INV-2025-0089 — Marie Kouassi — 150,000 CDF unpaid  
2. Accountant copies link: `https://e-digitex.com/pay/abc123...`  
3. Sends WhatsApp to parent  
4. Parent pays via Orange Money → invoice **Paid** within seconds  

---

## Module H9: Payment Gateways (PawaPay, CinetPay, Flutterwave)

### Purpose

Collect **Mobile Money** payments online in the **Democratic Republic of Congo (DRC)**. One gateway is active per school at a time.

### Supported gateways

| Gateway | Best for | DRC operators | Currencies |
|---------|----------|---------------|------------|
| **PawaPay** (recommended) | Direct API — PIN prompt on parent's phone | Orange, Airtel, Vodacom M-Pesa | CDF, USD |
| **CinetPay** | Redirect checkout (popular in Francophone Africa) | Orange CD, Airtel CD, M-Pesa CD | CDF, USD |
| **Flutterwave** | Pan-African + cards | DRC mobile money, cards | CDF, USD |

### Configuration checklist

1. **Payment Methods** → scroll to **Payment Gateway (DRC)**.
2. Choose **Provider** (PawaPay / CinetPay / Flutterwave).
3. Set **Environment**: **Sandbox** (testing) or **Production** (live).
4. Paste **API credentials** from gateway dashboard (see below).
5. Copy **Webhook URLs** from the page into your gateway dashboard.
6. Enable **Online payments** and required Mobile Money methods.
7. **Save settings** → test with a small invoice → switch to Production when ready.

### Webhook URLs (register in gateway dashboard)

Replace `YOUR-DOMAIN.com` with your live domain (e.g. `e-digitex.com`):

```
https://YOUR-DOMAIN.com/webhooks/payments/pawapay
https://YOUR-DOMAIN.com/webhooks/payments/cinetpay
https://YOUR-DOMAIN.com/webhooks/payments/flutterwave
```

**Why webhooks matter:** When payment completes on the parent's phone, the gateway notifies your server so the invoice is marked **Paid** automatically — even if the browser is closed.

**Return URL (after checkout):** `https://YOUR-DOMAIN.com/pay/callback/{gateway}/{reference}` — configured automatically; CinetPay/Flutterwave redirect parents here.

---

### H9A: PawaPay Setup

#### Get API keys

1. Register at **https://dashboard.pawapay.io** (production) or **https://dashboard.sandbox.pawapay.io** (testing).
2. Complete merchant verification (school name, contact, bank details).
3. Dashboard → **Settings → API** → copy **Bearer API Token**.
4. **Never share the token publicly.**

#### Configure in Digitex SMS

| Setting | Value |
|---------|-------|
| Provider | PawaPay |
| Environment | Sandbox (then Production) |
| PawaPay API Token | Paste token |
| Online payments | ON |

#### Register webhook

1. Payment Methods page → copy PawaPay webhook URL.
2. PawaPay dashboard → **Webhooks** → add URL.
3. Events: **Deposit completed**, **Deposit failed**.

#### Operator mapping (automatic)

| Digitex method | PawaPay provider |
|----------------|------------------|
| Orange Money | ORANGE_COD |
| Airtel Money | AIRTEL_COD |
| M-Pesa / Vodacom | VODACOM_MPESA_COD |

#### Go live

1. Complete PawaPay production KYC.
2. Get production API token.
3. Payment Methods → Environment: **Production** → save.
4. Update webhook on production PawaPay dashboard.
5. Test with small real payment (e.g. 100 CDF).

**Docs:** https://docs.pawapay.io

---

### H9B: CinetPay Setup

#### Get API keys

1. Register at **https://cinetpay.com** → merchant dashboard.
2. Complete account verification.
3. Copy **Site ID** and **API Key** from integration settings.

#### Configure in Digitex SMS

| Setting | Value |
|---------|-------|
| Provider | CinetPay |
| Environment | Sandbox / Production |
| CinetPay Site ID | From dashboard |
| CinetPay API Key | From dashboard |

#### Register webhook

1. Copy CinetPay webhook URL from Payment Methods page.
2. CinetPay dashboard → **Notifications / Webhook** → paste URL.
3. Save.

#### Test

1. Create test invoice → copy pay link.
2. **Pay instantly** → CinetPay checkout → use sandbox test credentials.
3. Confirm invoice status **Paid**.

**Docs:** https://docs.cinetpay.com

---

### H9C: Flutterwave Setup

#### Get API keys

1. Register at **https://dashboard.flutterwave.com**.
2. Complete business verification.
3. Copy **Public Key**, **Secret Key**, and **Encryption Key** (if shown).

#### Configure in Digitex SMS

| Setting | Value |
|---------|-------|
| Provider | Flutterwave |
| Environment | Test / Live |
| Flutterwave Secret Key | From dashboard |
| Flutterwave Public Key | From dashboard |

#### Register webhook

1. Copy Flutterwave webhook URL from Payment Methods page.
2. Flutterwave dashboard → **Settings → Webhooks** → add URL.
3. Enable **charge.completed** (or equivalent payment success event).

**Docs:** https://developer.flutterwave.com/docs

---

### Gateway troubleshooting

| Problem | Solution |
|---------|----------|
| Payment stays pending | Verify webhook URL is HTTPS and reachable from internet |
| Invalid API key | Regenerate key in gateway dashboard; re-save in Payment Methods |
| Wrong operator | Parent phone must match operator (243…) |
| Gateway down | Parents can use **Upload proof** tab (Module H10) |
| Sandbox works, production fails | Switch Environment to Production and use live API keys |

### Public help articles

Detailed step-by-step guides (no login required): **https://e-digitex.com/help**

---

## Module H10: Manual Payment Proof (Upload & Review)

### Purpose

When parents pay **offline** (Mobile Money agent, bank deposit, cash at office without immediate recording), they can **upload proof** on the pay page. Accountants **approve or reject** submissions before the invoice is marked paid.

---

### H10A: Parent — Upload proof

1. Parent opens invoice pay link (`/pay/...`) or finds invoice at `/pay`.
2. Select tab **Upload proof**.
3. Fill in:
   - **Payment date and time**
   - **Transaction / reference ID**
   - **Payment method** (Orange, Airtel, bank, etc.)
   - **Amount paid**
   - **Receipt photo** (screenshot or photo of slip)
4. Submit → status **Pending review**.

**Tip:** Encourage parents to include the full transaction SMS or agent receipt for faster approval.

---

### H10B: Accountant — Review proofs

**Menu:** Finance → Fees & Collection → **Payment Proofs**

| Action | Result |
|--------|--------|
| **Approve** | Payment recorded; invoice balance updated; parent notified |
| **Reject** | Parent can re-submit with correct information |

#### Step-by-step — Approve

1. Open **Payment Proofs**.
2. Review pending list — click submission to see receipt image and details.
3. Verify amount and transaction ID match bank/Mobile Money statement.
4. Click **Approve** → invoice marked paid (or partial payment applied).
5. If invalid duplicate or wrong amount → **Reject** with reason.

### Example

1. Parent pays 150,000 CDF at Orange agent — TRX: `OM-20250914-8821`.
2. Uploads proof on pay page with receipt photo.
3. Accountant sees pending proof next morning.
4. Matches statement → **Approve** → INV-2025-0089 **Paid**.

### Depends on

**Manual proof upload** enabled in Payment Methods; `storage:link` run on server for receipt file uploads.

---

## Help Center & Community Forum

### Public Documentation (no login)

| Page | URL | Content |
|------|-----|---------|
| **Documentation home** | `/help` | Search, links to full manuals, quick guides, forum |
| **Web user manual** | `/manual/web` | Complete browser admin guide — all modules A–M |
| **Mobile app manual** | `/manual/mobile` | Digitex Portal app — 21 chapters |
| **Quick guides** | `/help/payment-gateway-overview` (etc.) | Focused setup articles |
| **Community forum** | `/community` | Ask questions (post requires login) |
| **Pay fees** | `/pay` | Public invoice payment |

**Live example:** https://e-digitex.com/help

---

# PART I — COMMUNICATION

---

## Module I1: Notices (Announcements)

### Purpose

Official messages on notice board — holidays, meetings, exam rules.

### Example

- **Title:** Parent-Teacher Meeting — October 20  
- **Audience:** Parents only  
- **Published:** Yes → all parents see in **My Notices** + bell alert  

---

## Module I2: Reminders (Fee & Exam SMS)

### Purpose

Manually trigger bulk **fee reminder** or **exam tomorrow** SMS/WhatsApp to selected classes.

### Depends on

SMS/WhatsApp configured, templates, credits.

### Example

Send fee reminder to **Grade 5** parents: *"Outstanding balance for Marie Kouassi: 150,000 XOF. Please pay by Dec 15."*

---

## Module I3: Chatbot (WhatsApp / SMS)

### Purpose

Parents text school WhatsApp number, get menu: fees, homework, results, pickup QR — without opening app.

### Depends on

WhatsApp provider, keywords configured in **Chatbot Settings**, SMS credits.

### Example conversation

Parent: `GVIS` (school keyword)  
Bot: *Welcome to Green Valley. Reply 1 for fees, 2 for homework…*  
Parent: `1`  
Bot: *Marie Kouassi balance: 0 XOF. All clear!*

Configure keywords under **Chatbot → Settings**.

---

# PART J — PICKUP (Child Collection Security)

---

## Module J1: Student Pickup — Full Flow

### Purpose

Ensure only authorized persons collect children — QR scan at gate, teacher approval.

### Who uses it

Parents (generate QR), Guards (scan), Teachers (approve).

### Depends on

Students, Enrollments, optional SMS on approval.

### Step-by-step (example)

1. **08:00** — Mother opens mobile app → **Gate Pass** for Marie → QR code `PKUP-8X7K2M`  
2. **14:45** — Guard at gate scans QR → status **Scanned**  
3. Teacher Mr. Dupont sees pending pickup on **Pickup Management** page  
4. **14:50** — Teacher clicks **Approve**  
5. Mother receives: *"Marie released safely at 14:50"*  

### Common questions

**Q: Parent lost phone — no QR?**  
A: Use **OTP pickup** (staff generates SMS code) via mobile app or office.

---

# PART K — ELECTIONS & VOTING

---

## Module K1: Elections

### Purpose

Student council elections — positions, candidates, voting, results.

### Flow

1. Admin creates **Student Council Election 2025**  
2. Adds positions: President, Vice President  
3. Registers candidates  
4. **Publish** election → students vote at **My Elections**  
5. Close → publish results  

### Example

Marie votes for candidate **Yao Christian** as President — one vote per student enforced.

---

# PART L — QUICK REFERENCE — MODULE DEPENDENCY CHART

---

## Module L1: Module Dependency Chart

### Purpose

See **what must be created first** before using each area of the system. If something is missing from a menu, work through this chart from top to bottom.

### Who uses it

School Admin, Head Officer, Super Admin — especially during **initial school setup**.

### Setup order (recommended)

| Step | Create first | Then you can use |
|------|----------------|------------------|
| 1 | **Institution** (school) | Everything else |
| 2 | **Campus** (optional) | Multi-site reports |
| 3 | **Academic Session** (mark one as *current*) | Enrollments, fees, exams |
| 4 | **Configuration** (SMS, email, WhatsApp) | Notifications, reminders |
| 5 | **Grade Levels** | Class sections, fee structures |
| 6 | **Class Sections** | Enrollments, attendance, timetables |
| 7 | **Subjects** | Class subjects, exam marks |
| 8 | **Parents / Guardians** | Link to students |
| 9 | **Students** | Enrollments, invoices, attendance |
| 10 | **Fee Types** → **Fee Structures** | Invoices, payments |
| 11 | **Staff** | Timetables, payroll, gate pickup |
| 12 | **Exams** → **Schedules** → **Marks** | Result cards, reports |

### Visual dependency chart

```
Institution → Academic Session → Grade Levels → Class Sections
     ↓              ↓                                    ↓
  Campus      Fee Structures ← Fee Types          Enrollments ← Students ← Parents
     ↓              ↓                                    ↓
Configuration   Invoices → Payments              Attendance / Exams / Pickup
(SMS/Email)
```

### Example story

Green Valley opens in September. The admin creates **Session 2025-2026**, then **Grade 5**, then **Grade 5 Section A**, then imports **students**, then **enrolls** them — only then **Bulk Generate Invoices** works.

### Common questions

**Q: Invoices menu is empty / generate fails.**  
A: Check fee structures exist for the student's grade and session, and the student is **enrolled** in a class.

**Q: Teacher cannot mark attendance.**  
A: Student must be **enrolled** in that class; teacher must be assigned to class/subject.

**Q: Parent cannot pay online.**  
A: Invoice must exist; **Payment Methods** → Online payments must be ON; gateway configured (Module H9).

---

# PART M — GLOSSARY FOR NON-TECHNICAL USERS

---

## Module M1: Glossary for Non-Technical Users

### Purpose

Plain-language definitions of words you will see in Digitex SMS menus, reports, and mobile app.

### Who uses it

Everyone — especially new secretaries, accountants, teachers, and parents.

| Term | Simple meaning |
|------|----------------|
| **Institution** | Your school in the system |
| **Session** | School year (e.g. 2025-2026) |
| **Enrollment** | Student placed in a class for a year |
| **Tranche** | Fee installment (1st payment, 2nd payment…) |
| **Invoice** | Bill for fees owed |
| **Payment proof** | Photo/receipt parent uploads after paying offline |
| **Payment gateway** | Online service (PawaPay, CinetPay, Flutterwave) that collects Mobile Money |
| **Webhook** | Automatic message from gateway to your server when payment succeeds |
| **RFID / NFC** | Electronic ID card for gate scanning |
| **Gate pass / Pickup QR** | QR code for authorizing child collection |
| **OTP** | One-time SMS code for verification |
| **Module** | A feature area (menu section) |
| **Permission** | Allow or deny one action for a role |
| **Role** | Job type: Teacher, Accountant, School Admin, etc. |
| **In-app notification** | Alert inside the bell icon (not SMS) |
| **Head Officer** | Manages multiple schools on the platform |
| **Super Admin** | Platform operator (Digitex) |
| **LMD** | University credit system (Licence-Master-Doctorat) |
| **Deliberation** | University exam board decision on pass/fail |
| **Merchant code** | Number parents dial for Mobile Money payments |
| **Sandbox** | Test mode — no real money |
| **Production** | Live mode — real payments |

### Common questions

**Q: What is the difference between invoice and payment?**  
A: **Invoice** = bill (money owed). **Payment** = money received against that bill.

**Q: What is a payment token / pay link?**  
A: A secret URL on an invoice that lets parents pay without logging in.

---

*End of User Manual — For technical setup and API details, see Developer Manual and REST API Manual.*
