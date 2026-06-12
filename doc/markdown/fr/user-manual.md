# Digitex — Système de Gestion Scolaire
# Manuel Utilisateur Complet — Module par Module

**Public :** Administrateurs scolaires, enseignants, comptables, parents et personnel **non techniques**.  
**Objectif :** Expliquer chaque partie du système en langage simple, avec des exemples concrets.

> **Mobile app users:** For the Digitex Portal Android app (NFC gate, pickup, student portal, teacher attendance), see **`mobile-app-user-manual.md`** in this folder — a separate complete guide for all app roles with example login values and step-by-step workflows.

---

## Comment lire ce manuel

Each chapter describes **one module** (one area of the software). Every chapter includes:

- **Objectif** — Pourquoi ce module existe
- **Qui l'utilise** — Quels rôles en ont besoin
- **Prérequis** — Ce qu'il faut configurer avant
- **Étapes** — Comment l'utiliser
- **Exemple concret** — Scénario réaliste
- **Questions fréquentes** — FAQ

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

# PARTIE A — AVANT DE COMMENCER

---

## Module A1 : Connexion et écran

### Objectif

The login screen is the front door. After login, you see the **dashboard** (summary page), the **sidebar menu** on the left, and the **top bar** with search, notifications, and school name.

### Qui l'utilise

Everyone: Super Admin, School Admin, Teacher, Student, Parent, Accountant, Staff.

### Étapes — First login

**Important:** All schools use the **same website address**. There is no separate link per school (no subdomains).

1. Open your browser and go to: **https://e-digitex.com/login**  
   (You can also start at **https://e-digitex.com/** — it redirects to login.)
2. Enter your **email** or **username**. Example: `admin@gvis.edu.ci`
3. Enter your **password**. Example: (provided by your IT officer)
4. Click **Login**.
5. The system opens the school linked to your account. If you manage **more than one school** (Head Officer / Super Admin), click the **building icon** top-right and choose **Green Valley International School** (or another assigned school).

### Questions fréquentes (login URL)

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

### Exemple concret

Mrs. Aya is the school secretary. She logs in Monday morning, switches to Green Valley, and sees on the dashboard: **420 active students**, **18 teachers**, and **12 unpaid invoices this week**. She clicks the bell and sees: *"Marie Kouassi's parent submitted a leave request."*

### Questions fréquentes

**Q: I forgot my password.**  
A: Use **Forgot password** on the login page, or ask your School Admin to reset it.

**Q: I don't see a menu item my colleague has.**  
A: Your **role** or **permissions** may be different, or your school's **subscription package** may not include that module.

**Q: Why does the dashboard show different numbers from last year?**  
A: Make sure the **active academic session** is set to 2025-2026 under Academic Sessions.

---

## Module A2 : Tableau de bord

### Objectif

The dashboard is your **control room**. It shows the most important numbers and shortcuts for **your role** — not everyone sees the same dashboard.

### Qui l'utilise

All logged-in users (content varies by role).

### Prérequis

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

### Exemple concret

The School Admin opens the dashboard on the first day of term and sees **Enrollment: 420** but expects 450. She goes to **Student Enrollments** to find 30 students not yet placed in a class.

### Questions fréquentes

**Q: Numbers look wrong.**  
A: Check you selected the correct school (building icon) and the session **2025-2026** is marked as **current**.

---

## Module A3 : Recherche et notifications

### Objectif

- **Search:** Find anything quickly without clicking through menus.
- **Notifications (bell):** See what happened recently — payment recorded, exam published, pickup scanned, etc.

### Qui l'utilise

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

### Questions fréquentes

**Q: I paid fees but parent says no SMS.**  
A: SMS is separate from the bell. Check **Configuration → Notification preferences → Payment Received → SMS** is ON, and the school has SMS credits.

---

# PARTIE B — ADMINISTRATION PLATEFORME

---

## Module B1 : Établissements

### Objectif

An **institution** is one school or university in the system. Super Admin creates each tenant (e.g. Green Valley, Blue Coast Academy).

### Qui l'utilise

**Super Admin** primarily. Head Officers see assigned institutions only.

### Prérequis

Nothing — this is usually step one for a new deployment.

### Étapes — Create a school

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

### Exemple concret

Digitex platform owner adds a new client school "Lycée Moderne de Yamoussoukro" with code `LMY` and assigns the "Standard Package" subscription.

### Prérequis for other modules

Almost **everything** else requires an institution to exist first.

### Questions fréquentes

**Q: Can one person manage two schools?**  
A: Yes — **Head Officer** role with multiple institutions assigned, or Super Admin global view.

---

## Module B2 : Campus

### Objectif

If one school has **multiple physical sites** (Main Campus, Annex Branch), campuses organize students and staff by location.

### Qui l'utilise

School Admin, Super Admin.

### Prérequis

Institution created.

### Exemple

- Institution: Green Valley International School  
- Campus 1: **Main Campus — Cocody**  
- Campus 2: **Annex — Bingerville**  

When registering Marie Kouassi, assign her to **Main Campus — Cocody**.

### Questions fréquentes

**Q: We have only one building. Do we need campuses?**  
A: Optional. Many schools create one default campus and use it for all records.

---

## Module B3 : Head Officers

### Objectif

A **Head Officer** oversees **several schools** (e.g. a chain of 5 academies). They switch between schools using the header switcher or view a **Global Dashboard**.

### Qui l'utilise

**Super Admin** creates Head Officer accounts.

### Prérequis

Institutions must exist.

### Étapes

1. Menu → **Head Officers** → **Create**
2. Example:
   - **Name:** Mr. Ibrahim Koné
   - **Email:** ibrahim.kone@digitex-group.com
   - **Password:** (temporary — user should change)
   - **Assign institutions:** Green Valley, Blue Coast, Sunrise Primary
3. Save. Mr. Koné logs in and uses the building icon to switch schools.

### Questions fréquentes

**Q: Difference between Head Officer and School Admin?**  
A: **School Admin** manages **one** school deeply. **Head Officer** monitors **many** schools at summary level.

---

## Module B4 : Rôles et permissions

### Objectif

Controls **who can do what**. Example: allow teachers to mark attendance but **not** delete invoices.

### Qui l'utilise

Super Admin, School Admin (for their institution's custom roles).

### Prérequis

Modules seeded in system (done during installation).

### Plain language

- **Role** = job title bucket (Teacher, Accountant)
- **Permission** = single action (e.g. `invoice.create` = can create invoices)

### Étapes — Create custom role "Senior Accountant"

1. Menu → **Roles** → **Add Role**
2. Name: **Senior Accountant**, Institution: Green Valley
3. Open **Assign Permissions**
4. Enable: Invoices (view, create), Payments (view, create), Fee Structures (view)
5. Disable: Student delete, Settings manage
6. Save.
7. Assign this role to user `finance@gvis.edu.ci` in Staff module.

### Questions fréquentes

**Q: Teacher sees "403 Forbidden".**  
A: Their role lacks permission for that page. School Admin adds the permission.

---

## Module B5 : Configuration (SMS, WhatsApp, e-mail)

### Objectif

The **Configuration** area is where the school connects the system to the **outside world**: email server, SMS gateway, WhatsApp Business API, notification toggles, school hours, and academic calendar dates.

### Qui l'utilise

Super Admin, Head Officer, School Admin.

**Super Admin only (system-wide SMS/WhatsApp defaults):** must switch to **Global View** in the top header first — see **B5.0** below.

### Prérequis

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

## Module B6 : Paramètres école

### Objectif

Different from Configuration: **Settings** controls **academic rules** inside daily operations — lock attendance after 7 days, lock exam marks, block report cards if fees unpaid.

### Qui l'utilise

School Admin (with `setting.manage` permission).

### Prérequis

Institution selected.

### Exemple values at Green Valley

| Setting | Value | Effect |
|---------|-------|--------|
| Lock attendance after | 7 days | Teachers cannot edit last week's attendance |
| Lock exams after | 30 days | Marks frozen after a month |
| Block reports on debt | ON | Marie cannot download report card if she owes 50,000 XOF |
| LMD validation threshold | 50% | University pass rule |
| Active marking periods | Term 1, Term 2 | Teachers only enter marks for open terms |

### Questions fréquentes

**Q: Teacher cannot edit yesterday's attendance.**  
A: Grace period may have expired — Admin increases **attendance grace period** in Settings.

---

## Module B7 : Forfaits et abonnements

### Objectif

Digitex is sold as **packages** (bundles of modules). Each school needs an **active subscription** to use the system.

### Qui l'utilise

Super Admin.

### Exemple

- **Package name:** Standard Secondary  
- **Modules included:** Students, Invoices, Attendance, Exams, Notices  
- **Subscription:** Green Valley, Start 2025-01-01, End 2026-01-01, Status Active  

If subscription expires, users see a warning; after grace period access may be limited.

---

## Module B8 : Journaux d'audit

### Objectif

Security trail — who changed what and when (Super Admin investigation).

### Qui l'utilise

Super Admin only.

### Exemple entry

*"User admin@gvis.edu.ci updated Invoice INV-2025-0089 at 2025-11-03 14:22"*

---

## Module B9 : Paramètres de devise

### Objectif

Définir **comment les montants sont affichés** pour votre école — code devise (USD, CDF, XOF, etc.), symbole, position du symbole et décimales. Cela affecte les factures, paiements, tableaux de bord, rapports de frais et la page de paiement en ligne.

### Qui l'utilise

Admin école, Responsable régional, Super Admin (permission `currency.view`).

### Où le trouver

**Menu → Configuration → Devise**

### Étapes

1. Sélectionnez votre école dans l'en-tête (ex. **Green Valley**).
2. Ouvrez **Configuration → Devise**.
3. **Devise principale** — choisissez le code ISO (ex. **USD** ou **CDF**).
4. **Symbole d'affichage** — personnalisez si nécessaire (ex. `FC`, `CFA`).
5. **Position du symbole** — avant (`$ 1 250,00`) ou après (`1 250,00 $`) le montant.
6. **Décimales** — généralement **2** ; **0** si vous n'affichez pas de centimes.
7. Vérifiez l'**Aperçu en direct** à droite.
8. Cliquez **Enregistrer**.

### Où cela s'applique

Factures, paiements, soldes élèves, tableaux de bord, paiement en ligne Mobile Money et rapports financiers. Chaque école peut avoir sa propre devise.

---

# PARTIE C — MODULES ACADÉMIQUES

---

## Module C1 : Sessions académiques

### Objectif

An **academic session** is one school year, e.g. **2025-2026**. Almost all enrollments, fees, and exams belong to a session.

### Qui l'utilise

School Admin, Registrar.

### Prérequis

Institution.

### Étapes

1. Menu → **Academic Sessions** → **Add**
2. **Name:** 2025-2026  
3. **Start date:** 2025-09-01  
4. **End date:** 2026-07-15  
5. Check **Set as current session** ☑  
6. Save.

Only **one** session should be "current" at a time.

### Exemple concret

Green Valley runs two sessions in database (2024-2025 archived, 2025-2026 current). All new enrollments automatically tie to 2025-2026.

### Modules that depend on this

Student Enrollments, Fee Structures, Invoices, Exams, Reports.

### Questions fréquentes

**Q: We started a new year but old fees show.**  
A: Filter invoices by session 2025-2026, or generate new fee structures for the new session.

---

## Module C2 : Départements

### Objectif

Groups university subjects and staff — e.g. **Department of Computer Science**, **Department of Economics**.

### Qui l'utilise

University / LMD institutions.

### Prérequis

Institution, Academic session (for enrollments).

### Exemple

Department: **Sciences** → Programs: **Licence Informatique**, **Licence Mathématiques**

### School (K-12) note

Primary/secondary schools may skip Departments and use Grade Levels directly.

---

## Module C3 : Niveaux scolaires

### Objectif

The **grade/year** label: Grade 1, Grade 2, … Form 6, CP, CE1 (French system), etc.

### Qui l'utilise

School Admin.

### Prérequis

Institution.

### Exemple at Green Valley

| Grade name | Order |
|------------|-------|
| CP | 1 |
| CE1 | 2 |
| … | … |
| Terminale | 12 |

### Prérequis for

Class Sections, Fee Structures (fees often differ per grade).

---

## Module C4 : Classes

### Objectif

A **class section** is a group of students taught together: **Grade 5 Section A**, **Grade 5 Section B**.

### Qui l'utilise

School Admin, Teachers (view own classes).

### Prérequis

Grade Levels, Academic Session, optionally Staff (homeroom teacher).

### Étapes

1. **Class Sections** → **Add**
2. **Name:** Section A  
3. **Grade Level:** Grade 5  
4. **Homeroom Teacher:** Mr. Jean Dupont  
5. **Capacity:** 35  
6. Save.

### Exemple concret

Green Valley has Grade 5 split into A (35 students) and B (32 students). Marie Kouassi is in **Grade 5 Section A**.

### Modules that depend on this

Enrollments, Timetables, Attendance (by class), Exam marks, Assignments.

---

## Module C5 : Matières

### Objectif

Courses taught: **Mathematics**, **French**, **Physical Education**, **Physics**.

### Qui l'utilise

School Admin, University Admin.

### Prérequis

Institution; for universities also **Department** or **Academic Unit**.

### Exemple

| Subject | Code | Type |
|---------|------|------|
| Mathematics | MATH-G5 | Core |
| English | ENG-G5 | Core |
| Art | ART-G5 | Optional |

---

## Module C6 : Affectation matières

### Objectif

Links **which subjects** are taught in **which class**, and **which teacher** teaches each.

### Qui l'utilise

School Admin, Academic coordinator.

### Prérequis

Class Sections + Subjects + Staff (teachers).

### Exemple

**Grade 5 Section A:**

| Subject | Teacher |
|---------|---------|
| Mathematics | Mr. Jean Dupont |
| French | Mrs. Aminata Traoré |
| Science | Mr. Paul N'Guessan |

### Prérequis for

Timetables, subject-wise attendance, exam marks per subject.

---

## Module C7 : Emplois du temps

### Objectif

Weekly schedule — which subject happens **Monday 08:00–09:00** in which room.

### Qui l'utilise

School Admin creates; Teachers and Students view.

### Prérequis

Class Subjects allocated, Teachers assigned.

### Exemple (Grade 5A — excerpt)

| Day | Time | Subject | Teacher | Room |
|-----|------|---------|---------|------|
| Monday | 08:00-09:00 | Mathematics | Mr. Dupont | Room 12 |
| Monday | 09:00-10:00 | French | Mrs. Traoré | Room 12 |

**Print / Download PDF** available for posting on classroom door.

### Questions fréquentes

**Q: Teacher clash — same time two classes.**  
A: System may warn on availability check; adjust slot or assign different teacher.

---

## Module C8 : Devoirs

### Objectif

Teachers publish homework with **title**, **description**, **deadline**. Students and parents see it in portal and mobile app.

### Qui l'utilise

Teachers create; Students/Parents view.

### Prérequis

Class Sections, Subjects, Teacher permissions.

### Exemple

- **Title:** Exercise pages 45–48  
- **Subject:** Mathematics  
- **Class:** Grade 5A  
- **Due:** 2025-10-15 23:59  
- **Created by:** Mr. Dupont  

Marie's parent sees this in the mobile app under **Homework**.

---

## Module C9 : Programmes LMD

### Objectif

**LMD** (Licence-Master-Doctorat) structure:

- **Program:** e.g. Licence Informatique (6 semesters)  
- **Academic Unit (UE):** e.g. UE101 — Algorithmics (groups subjects)

### Qui l'utilise

University administrators.

### Prérequis

Departments, Academic Sessions.

### Exemple flow

1. Create Program **Licence Informatique**, 6 semesters, 3 years  
2. Create UE **UE101** Semester 1  
3. Assign subjects **Algorithmics**, **Programming 1** to UE101  
4. Enroll student into Program via **University Enrollments**

---

# PARTIE D — ÉLÈVES & PARENTS

---

## Module D1 : Parents / tuteurs

### Objectif

Store **mother, father, guardian** contact details and link to portal login for fee viewing and pickup.

### Qui l'utilise

School Admin, Registrar.

### Prérequis

Institution.

### Exemple record

| Field | Value |
|-------|-------|
| Father name | Kouassi Emmanuel |
| Father phone | +225 07 12 34 56 78 |
| Mother name | Kouassi Aya |
| Primary contact | Father |
| User account | parent.kouassi@gvis.edu.ci |

### Prérequis for

Student registration (link child), SMS notifications, Pickup QR, mobile app.

---

## Module D2 : Élèves

### Objectif

The **heart of the system** — every learner's profile: identity, photo, class, RFID card, login.

### Qui l'utilise

School Admin, Registrar; Teachers view limited info.

### Prérequis

Parents (optional at create), Grade/Class (can assign later), Campus.

### Étapes — Register Marie Kouassi

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

### Exemple concret

Marie receives login shortcode **GVIS0142** and password. She uses the mobile app to see homework and results.

### Modules that depend on Students

Enrollments, Invoices, Attendance, Exams, Pickup, Requests, Elections.

### Questions fréquentes

**Q: Gate doesn't recognize card.**  
A: Verify RFID UID in student profile matches physical card; check for typos (0 vs O).

---

## Module D3 : Inscriptions

### Objectif

Places a student in a **class section** for the **current session**.

### Qui l'utilise

Registrar, School Admin.

### Prérequis

Students, Class Sections, Academic Session (current).

### Étapes

1. **Enrollments** → **New Enrollment**
2. **Student:** Marie Kouassi  
3. **Class:** Grade 5 Section A  
4. **Session:** 2025-2026  
5. **Status:** Active  
6. Save.

Without enrollment, Marie won't appear in class attendance lists or class-based invoices.

---

## Module D4 : Inscriptions universitaires

### Objectif

Enrolls student in **Program / Semester / UE** path (universities).

### Prérequis

Programs, Academic Units, Students.

### Exemple

Marie (university student) → Program **Licence Informatique** → Semester 2 → UE **UE202**

---

## Module D5 : Promotion

### Objectif

Move a **whole cohort** to the next grade at year end (e.g. all Grade 5 → Grade 6).

### Qui l'utilise

School Admin.

### Prérequis

Grade Levels, Enrollments, new Class Sections for next grade.

### Exemple

Select **Grade 5 Section A** → Promote to **Grade 6 Section A** for session 2026-2027.

---

## Module D6 : Transferts

### Objectif

Move one student **out** to another school or **in** from elsewhere, with printable transfer certificate.

### Exemple

Marie transfers from Green Valley to Blue Coast — admin marks transfer, prints document for new school.

---

# PARTIE E — PRÉSENCE & DEMANDES

---

## Module E1 : Présence (manuelle)

### Objectif

Record who is **present**, **absent**, **late** each day or per subject.

### Qui l'utilise

Teachers, School Admin.

### Prérequis

Enrollments, Class Sections, optionally Timetables (subject mode).

### Étapes — Daily attendance Grade 5A

1. **Attendance** → **Mark Attendance**
2. Select **Class:** Grade 5 Section A  
3. **Date:** 2025-10-06  
4. Mark each student: Marie = Present, Paul = Late, Aisha = Absent  
5. Save.

### Exemple concret

Mr. Dupont marks morning attendance on his phone browser. At 08:20 Marie is marked **Late** (school starts 08:00, margin 15 min).

---

## Module E2 : Présence RFID / NFC

### Objectif

Automatic check-in when student taps card at gate — no manual typing.

### Qui l'utilise

Gate devices (configured by IT); results viewed by Admin/Teachers.

### Prérequis

Student RFID/NFC UID in profile, **Configuration** school timings, **HARDWARE_SECRET** set by IT, SMS preferences for arrival messages.

### Déroulement (simple)

1. Marie taps card at gate 07:55  
2. System records **Check-In**  
3. Parent receives WhatsApp: *"Marie arrived at Green Valley at 07:55"* (if enabled)  
4. Marie taps again at 15:10 → **Check-Out** / Departure message  

### Questions fréquentes

**Q: Double tap too fast shows error.**  
A: Increase **Double Tap Wait Time** in Configuration (e.g. 15 minutes between IN and OUT).

---

## Module E3 : Analyses présence

### Objectif

Charts and reports — absence trends, class comparison, monthly summaries.

### Prérequis

Attendance data from Module E1/E2.

---

## Module E4 : Demandes élèves

### Objectif

Students or parents **ask permission** for absence, lateness, sick leave, early exit — tracked with ticket number.

### Qui l'utilise

Students/Parents submit; Admin approves/rejects.

### Prérequis

Student enrolled, notification preferences for updates.

### Exemple

1. Marie's mother submits **Sick leave** Oct 6–7, reason: fever  
2. Ticket: **REQ-K7M2P9QX**  
3. Secretary sees bell notification: *New student request*  
4. Admin approves → parent gets WhatsApp: *Request REQ-K7M2P9QX approved*

### Questions fréquentes

**Q: Teacher cannot see requests.**  
A: By design teachers may not view all requests — only Admin roles process them.

---

# PARTIE F — PERSONNEL & RH

---

## Module F1 : Personnel

### Objectif

Employee records: teachers, accountants, guards, cleaners — linked to login account.

### Qui l'utilise

School Admin, HR.

### Prérequis

Institution, Roles.

### Exemple — Create Mr. Jean Dupont

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

## Module F2 : Présence personnel

### Objectif

Same concept as student attendance but for employees — manual or RFID.

---

## Module F3 : Congés

### Objectif

Staff request days off; Admin approves.

### Exemple

Mr. Dupont requests leave Dec 20–22, type **Annual leave**, reason: family travel. Status **Pending** → Admin approves → Jean gets bell notification.

---

## Module F4 : Grilles salariales

### Objectif

Define pay components: basic salary, allowances, deductions per staff grade.

### Exemple

Teacher Grade A: Base 250,000 XOF + Transport 25,000 XOF.

---

## Module F5 : Paie

### Objectif

Generate **monthly payslips** from salary structures.

### Déroulement

1. Ensure salary structures assigned  
2. **Payroll** → Generate for **November 2025**  
3. Review list → Confirm  
4. Staff view/download payslip PDF  

---

# PARTIE G — EXAMENS & RÉSULTATS

---

## Module G1 : Examens

### Objectif

Define an examination event: **First Term Exam 2025**, **Mock BAC**, etc.

### Prérequis

Academic Session.

### Exemple

- **Name:** First Term Examination 2025  
- **Session:** 2025-2026  
- **Category:** Term Exam  
- **Status:** Draft → Published (when ready)

**Publish** triggers student notifications and makes results visible (if marks entered).

---

## Module G2 : Calendriers examens

### Objectif

Timetable of exam dates per subject/class; generate **admit cards**.

### Exemple

| Date | Subject | Class | Room | Time |
|------|---------|-------|------|------|
| 2025-11-10 | Mathematics | Grade 5A | Hall B | 08:00 |

---

## Module G3 : Saisie des notes

### Objectif

Teachers enter scores per student per subject.

### Prérequis

Exams, Class Subjects, Enrollments, Settings (active periods, exam lock).

### Exemple

Marie Kouassi — Mathematics — First Term: **16/20**

---

## Module G4 : Bulletins

### Objectif

Print **report cards**, **bulletins**, **transcripts** (including full LMD transcript).

### Prérequis

Published exams, marks entered, optionally fee clearance (Settings block on debt).

### Exemple concret

Marie owes 75,000 XOF. **Block reports on debt** is ON — report card PDF shows message to visit finance office. After payment, report unlocks.

---

# PARTIE H — MODULES FINANCE

---

## Module H1 : Types de frais

### Objectif

Categories of charges: **Tuition**, **Registration**, **Transport**, **Canteen**, **Exam Fee**.

### Exemple

| Fee Type | Code |
|----------|------|
| Tuition | TUITION |
| Transport | TRANSPORT |

---

## Module H2 : Structures de frais

### Objectif

**How much** each grade pays, in **how many installments (tranches)**.

### Prérequis

Fee Types, Grade Levels, Academic Session.

### Exemple — Grade 5 Tuition 2025-2026

| Tranche | Due date | Amount (XOF) |
|---------|----------|--------------|
| Tranche 1 | 2025-09-15 | 150,000 |
| Tranche 2 | 2025-12-15 | 150,000 |
| Tranche 3 | 2026-03-15 | 150,000 |

Total annual tuition: **450,000 XOF**

---

## Module H3 : Factures

### Objectif

Bill sent to student/parent for fees owed.

### Prérequis

Fee Structures, Students.

### Étapes — Generate class invoices

1. **Invoices** → **Bulk Generate**
2. **Class:** Grade 5 Section A  
3. **Fee structure:** Grade 5 Tuition Tranche 1  
4. Generate → creates INV-2025-0142 for Marie, etc.

### Exemple invoice

- **Number:** INV-2025-0089  
- **Student:** Marie Kouassi  
- **Amount:** 150,000 XOF  
- **Status:** Unpaid  

Parent receives WhatsApp + bell notification if enabled.

---

## Module H4 : Paiements

### Objectif

Record money received — cash, bank transfer, mobile money.

### Exemple

1. Parent pays 150,000 XOF at office  
2. Accountant opens **Payments** → **Record Payment**  
3. **Invoice:** INV-2025-0089  
4. **Amount:** 150,000  
5. **Method:** Cash  
6. **Date:** 2025-09-14  
7. Save → Invoice status **Paid**, parent notified.

---

## Module H5 : Soldes élèves

### Objectif

See total owed across all invoices; download **PDF statement** for parent meetings.

### Exemple statement line

| Date | Description | Debit | Credit | Balance |
|------|-------------|-------|--------|---------|
| 2025-09-01 | Invoice INV-2025-0089 | 150,000 | — | 150,000 |
| 2025-09-14 | Payment TRX-9912 | — | 150,000 | 0 |

---

## Module H6 : Budgets

### Objectif

Internal school spending control — departments get **budget envelopes**; staff **request funds** for activities.

### Exemple

- **Budget:** Science Department 2025-2026 — Allocated **2,000,000 XOF**, Spent **800,000 XOF**  
- **Fund request:** Mr. N'Guessan requests **150,000 XOF** for lab equipment → Finance approves → spent amount increases  

---

## Module H7 : Modes de paiement

### Objectif

Configure which payment options your school accepts — **Cash**, **Bank transfer**, **Orange Money**, **Airtel Money**, **M-Pesa/Vodacom**, and **Card/Online** — instead of hardcoded choices. These settings control both **office payment recording** and **parent online pay pages**.

### Menu path

**Finance → Fees & Collection → Payment Methods**

### Étapes — Enable methods

1. Log in as **School Admin** or **Accountant**.
2. Open **Payment Methods**.
3. Toggle **Online payments** ON to allow public pay links.
4. For each payment method row:
   - Check **Enabled**
   - Fill **Merchant code** (Mobile Money) or **Bank details** (transfer)
   - Add **Instructions** parents will see on the pay page
5. Toggle **Manual proof upload** ON (recommended backup when gateway is unavailable).
6. Click **Save settings**.

### Exemple — Orange Money (DRC)

| Field | Example value |
|-------|----------------|
| Merchant code | `123456` |
| Instructions | Dial *144# → Pay Merchant → enter 123456 → amount → confirm PIN |

### Prérequis

Finance module enabled, active institution selected (building icon, top-right).

---

## Module H8 : Liens paiement en ligne

### Objectif

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

### Exemple workflow

1. Invoice INV-2025-0089 — Marie Kouassi — 150,000 CDF unpaid  
2. Accountant copies link: `https://e-digitex.com/pay/abc123...`  
3. Sends WhatsApp to parent  
4. Parent pays via Orange Money → invoice **Paid** within seconds  

---

## Module H9 : Paiements en ligne (connecter le Mobile Money)

> **À lire d'abord — en mots simples.**
> Cette page permet aux parents de payer les frais scolaires depuis leur téléphone avec le **Mobile Money** (Orange Money, Airtel Money, M-Pesa). Pour que cela fonctionne, votre école ouvre un compte chez une **société de paiement** (nous prenons en charge **PawaPay**, **CinetPay** ou **Flutterwave**), copie quelques codes secrets chez elle, puis les colle dans Digitex. Une fois fait, chaque fois qu'un parent paie, la facture passe **automatiquement à « Payé »** — aucun travail manuel pour votre comptable. Vous ne configurez ceci qu'**une seule fois**.

### Comment ça marche (vue d'ensemble)

Imaginez la société de paiement comme un **caissier placé entre le parent et votre école** :

1. Le parent clique sur le lien de paiement et choisit le Mobile Money.
2. La société de paiement demande au parent de confirmer sur son téléphone.
3. Le parent saisit son code PIN et l'argent est collecté.
4. La société de paiement **dit à Digitex « c'est payé »**, et la facture devient verte automatiquement.

Pas besoin de comprendre la technique — vous devez seulement **relier les deux** en copiant des codes. Suivez les étapes ci-dessous pour la société que vous choisissez.

### Quelle société choisir ?

Vous n'en avez besoin que d'**une seule**. Choisissez selon ce qui convient à votre école :

| Société | Pourquoi la choisir | Compatible (RDC) |
|---------|---------------------|------------------|
| **PawaPay** (la plus simple, recommandée) | Le parent reçoit juste une demande de PIN sur son téléphone — sans page supplémentaire | Orange, Airtel, Vodacom M-Pesa |
| **CinetPay** | Très répandue en Afrique francophone ; ouvre une page de paiement familière | Orange, Airtel, M-Pesa |
| **Flutterwave** | Accepte aussi les cartes bancaires, pas seulement le Mobile Money | Mobile Money + cartes |

> **Conseil :** En cas de doute, commencez par **PawaPay**. C'est le plus simple pour les parents.

### Ce qu'il vous faut avant de commencer

- Un compte chez **une** société de paiement (l'inscription est gratuite — liens plus bas).
- Les **codes secrets** qu'elle vous donne (appelés « clés API » — voyez-les comme le mot de passe de votre boutique).
- Environ **15 minutes**, une seule fois.

### Toujours tester d'abord (« Sandbox » ou « Production »)

Chaque société propose **deux modes** :

- **Sandbox / Test** = un mode d'entraînement avec de la fausse monnaie. Utilisez-le d'abord pour vérifier que tout fonctionne.
- **Production / En direct** = le mode réel qui encaisse de l'argent réel. Passez-y **seulement après** un test réussi.

Ce choix s'appelle **Environnement** sur la page Méthodes de paiement. Commencez sur **Sandbox**, testez avec une petite facture, puis passez en **Production**.

---

### Étape 1 — Configurer dans Digitex (identique pour toutes les sociétés)

1. Allez dans **Configuration → Méthodes de paiement**.
2. Descendez jusqu'à la section **Passerelle de paiement (RDC)**.
3. Choisissez votre **Fournisseur** (PawaPay, CinetPay ou Flutterwave).
4. Réglez l'**Environnement** sur **Sandbox** pour l'instant.
5. Collez les **codes secrets** de la société choisie (voir l'Étape 2 pour les trouver).
6. Activez **Paiements en ligne** et cochez les options Mobile Money voulues (Orange, Airtel, etc.).
7. Cliquez sur **Enregistrer**.

Cette page affiche aussi un encadré **URL de webhook**. Un « webhook » est simplement le **message de retour** que la société de paiement envoie pour prévenir Digitex qu'un paiement a réussi. Vous copierez ces URL dans le site de la société à l'Étape 2 — c'est ce qui met les factures à jour automatiquement.

Vos URL de webhook (remplacez `VOTRE-DOMAINE.com` par l'adresse réelle de votre site, ex. `e-digitex.com`) :

```
https://VOTRE-DOMAINE.com/webhooks/payments/pawapay
https://VOTRE-DOMAINE.com/webhooks/payments/cinetpay
https://VOTRE-DOMAINE.com/webhooks/payments/flutterwave
```

> **En bref :** Digitex a besoin des codes secrets de la société, et la société a besoin de l'URL de webhook de Digitex. Vous ne faites que les présenter l'un à l'autre.

---

### Étape 2 — Récupérer vos codes chez la société de paiement

Choisissez la section de la société que vous avez retenue.

#### Option A — PawaPay (recommandée)

1. Créez un compte gratuit sur **https://dashboard.pawapay.io** (en direct) ou **https://dashboard.sandbox.pawapay.io** (pour tester).
2. Renseignez les informations de l'école (nom, contact, coordonnées bancaires) pour être approuvé.
3. Dans leur menu, ouvrez **Settings → API** et **copiez le jeton API** (un long code secret). Gardez-le privé — ne le partagez jamais.
4. De retour dans Digitex (Méthodes de paiement), collez-le dans **Jeton API PawaPay** et enregistrez.
5. Dans PawaPay, ouvrez **Webhooks**, cliquez sur **Ajouter**, et collez l'URL de webhook PawaPay copiée depuis Digitex. Choisissez les événements **Deposit completed** et **Deposit failed**.

C'est tout. PawaPay reconnaît automatiquement le réseau (Orange, Airtel ou M-Pesa) d'après le numéro du parent.

**Documentation :** https://docs.pawapay.io

#### Option B — CinetPay

1. Créez un compte sur **https://cinetpay.com** et vérifiez-le.
2. Trouvez les réglages d'intégration et **copiez deux codes : le Site ID et la clé API (API Key)**.
3. Dans Digitex, collez-les dans **Site ID CinetPay** et **Clé API CinetPay**, puis enregistrez.
4. Dans CinetPay, allez dans **Notifications / Webhook** et collez l'URL de webhook CinetPay copiée depuis Digitex.

**Documentation :** https://docs.cinetpay.com

#### Option C — Flutterwave

1. Créez un compte sur **https://dashboard.flutterwave.com** et vérifiez votre entreprise.
2. **Copiez la clé publique (Public Key) et la clé secrète (Secret Key)** (et la clé de chiffrement si elle s'affiche).
3. Dans Digitex, collez la **clé secrète** et la **clé publique**, puis enregistrez.
4. Dans Flutterwave, allez dans **Settings → Webhooks**, ajoutez l'URL de webhook Flutterwave copiée depuis Digitex, et activez l'événement de **succès de paiement** (souvent appelé `charge.completed`).

**Documentation :** https://developer.flutterwave.com/docs

---

### Étape 3 — Tester, puis passer en direct

1. Créez une **petite facture de test** (ex. 100 CDF).
2. Ouvrez son lien de paiement et choisissez **Payer maintenant**.
3. Effectuez le paiement avec les **instructions de test** de la société (mode Sandbox).
4. Vérifiez que la facture passe à **Payé** toute seule en quelques secondes.
5. Si le test réussit : revenez sur **Méthodes de paiement**, passez l'**Environnement** sur **Production**, collez vos codes secrets **réels** et mettez à jour l'URL de webhook sur le tableau de bord en direct de la société.
6. Faites un dernier test avec un **petit paiement réel**.

C'est terminé — les parents peuvent désormais payer depuis leur téléphone.

---

### En cas de problème

| Ce que vous voyez | Que faire |
|-------------------|-----------|
| Le parent a payé mais la facture reste impayée | L'URL de webhook est absente ou erronée. Recopiez-la depuis Digitex vers le tableau de bord de la société. Votre site doit utiliser **https://** et être accessible en ligne. |
| « Clé API invalide » ou erreurs de connexion | Le code secret est erroné ou expiré. Générez-en un nouveau sur le site de la société et recollez-le dans Méthodes de paiement. |
| Le paiement est refusé sur le téléphone | Le numéro du parent doit correspondre à son réseau Mobile Money et commencer par l'indicatif RDC (243…). |
| La société de paiement est momentanément indisponible | Les parents peuvent quand même payer via l'onglet **Téléverser une preuve** (voir Module H10). |
| Cela marchait en test mais échoue en réel | Vous avez oublié de passer l'**Environnement** en **Production** ou vous utilisez encore les codes secrets de **test**. Utilisez les codes **réels**. |

### Besoin d'aide ?

Des guides simples, image par image (sans connexion), sont en ligne sur : **https://e-digitex.com/help**

---

## Module H10 : Preuve paiement manuelle

### Objectif

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

#### Étapes — Approve

1. Open **Payment Proofs**.
2. Review pending list — click submission to see receipt image and details.
3. Verify amount and transaction ID match bank/Mobile Money statement.
4. Click **Approve** → invoice marked paid (or partial payment applied).
5. If invalid duplicate or wrong amount → **Reject** with reason.

### Exemple

1. Parent pays 150,000 CDF at Orange agent — TRX: `OM-20250914-8821`.
2. Uploads proof on pay page with receipt photo.
3. Accountant sees pending proof next morning.
4. Matches statement → **Approve** → INV-2025-0089 **Paid**.

### Prérequis

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

# PARTIE I — COMMUNICATION

---

## Module I1 : Annonces

### Objectif

Official messages on notice board — holidays, meetings, exam rules.

### Exemple

- **Title:** Parent-Teacher Meeting — October 20  
- **Audience:** Parents only  
- **Published:** Yes → all parents see in **My Notices** + bell alert  

---

## Module I2 : Rappels SMS

### Objectif

Manually trigger bulk **fee reminder** or **exam tomorrow** SMS/WhatsApp to selected classes.

### Prérequis

SMS/WhatsApp configured, templates, credits.

### Exemple

Send fee reminder to **Grade 5** parents: *"Outstanding balance for Marie Kouassi: 150,000 XOF. Please pay by Dec 15."*

---

## Module I3 : Chatbot

### Objectif

Parents text school WhatsApp number, get menu: fees, homework, results, pickup QR — without opening app.

### Prérequis

WhatsApp provider, keywords configured in **Chatbot Settings**, SMS credits.

### Exemple conversation

Parent: `GVIS` (school keyword)  
Bot: *Welcome to Green Valley. Reply 1 for fees, 2 for homework…*  
Parent: `1`  
Bot: *Marie Kouassi balance: 0 XOF. All clear!*

Configure keywords under **Chatbot → Settings**.

---

# PARTIE J — PICKUP

---

## Module J1 : Pickup complet

### Objectif

Ensure only authorized persons collect children — QR scan at gate, teacher approval.

### Qui l'utilise

Parents (generate QR), Guards (scan), Teachers (approve).

### Prérequis

Students, Enrollments, optional SMS on approval.

### Étapes (example)

1. **08:00** — Mother opens mobile app → **Gate Pass** for Marie → QR code `PKUP-8X7K2M`  
2. **14:45** — Guard at gate scans QR → status **Scanned**  
3. Teacher Mr. Dupont sees pending pickup on **Pickup Management** page  
4. **14:50** — Teacher clicks **Approve**  
5. Mother receives: *"Marie released safely at 14:50"*  

### Questions fréquentes

**Q: Parent lost phone — no QR?**  
A: Use **OTP pickup** (staff generates SMS code) via mobile app or office.

---

# PARTIE K — ÉLECTIONS

---

## Module K1 : Élections

### Objectif

Student council elections — positions, candidates, voting, results.

### Déroulement

1. Admin creates **Student Council Election 2025**  
2. Adds positions: President, Vice President  
3. Registers candidates  
4. **Publish** election → students vote at **My Elections**  
5. Close → publish results  

### Exemple

Marie votes for candidate **Yao Christian** as President — one vote per student enforced.

---

# PARTIE L — RÉFÉRENCE RAPIDE

---

## Module L1 : Schéma des dépendances

### Objectif

See **what must be created first** before using each area of the system. If something is missing from a menu, work through this chart from top to bottom.

### Qui l'utilise

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

### Exemple concret

Green Valley opens in September. The admin creates **Session 2025-2026**, then **Grade 5**, then **Grade 5 Section A**, then imports **students**, then **enrolls** them — only then **Bulk Generate Invoices** works.

### Questions fréquentes

**Q: Invoices menu is empty / generate fails.**  
A: Check fee structures exist for the student's grade and session, and the student is **enrolled** in a class.

**Q: Teacher cannot mark attendance.**  
A: Student must be **enrolled** in that class; teacher must be assigned to class/subject.

**Q: Parent cannot pay online.**  
A: Invoice must exist; **Payment Methods** → Online payments must be ON; gateway configured (Module H9).

---

---

# PARTIE L — DIGITEX IA (OPTION FACULTATIVE)

---

## Module L1 : Accès IA et où la trouver

### Objectif

**Digitex IA** aide le personnel à rédiger des avis, rappels, commentaires de bulletin, réponses support et synthèses — **directement dans le module en cours**, sans copier les données vers un chatbot externe.

L’IA est une **option d’abonnement** (Basic / Premium / PRO / Enterprise). Si votre forfait inclut l’IA, vous verrez :

- **Menu latéral :** **Assistant IA** (chat complet) et **Studio IA** (outils de rédaction)
- **Bouton violet en bas à gauche :** aide rapide sur **la page actuelle**
- **Boutons « IA » violets** sur avis, rappels, résultats, factures, élèves, notes, support et tableau de bord

### Qui l'utilise

Administration, enseignants, comptables, Head Officers (si le forfait inclut l’IA).

### Étapes — Ouvrir l’Assistant IA

1. Connectez-vous sur **https://e-digitex.com/**
2. Menu **Digitex IA → Assistant IA**
3. Posez une question, ex. *« Comment générer les factures en masse pour la 5e A ? »*
4. Cliquez **Nouvelle conversation** pour recommencer

### Étapes — Widget flottant

1. Cliquez le bouton **baguette magique** violet en bas à gauche
2. Posez une question sur **cet écran**
3. Ouvrez l’assistant complet via l’icône lien externe

---

## Module L2 : IA sur le tableau de bord (Copilote)

### Objectif

La carte **Copilote école** résume ce qui requiert votre attention (frais en retard, brouillons d’avis, examens à venir).

### Étapes

1. **Tableau de bord**
2. Carte **Copilote école**
3. Cliquez **Quoi de neuf ?**
4. Agissez selon les points listés

---

## Module L3 : IA dans les avis

### Étapes

1. **Communication → Avis → Créer**
2. Renseignez titre, type, public
3. **Brouillon IA** → confirmez **Appliquer**
4. **Traduire IA** si besoin
5. **Enregistrer** / publier

---

## Module L4 : IA dans les résultats et bulletins

### Étapes

1. **Examens → Résultats**
2. Section **Commentaires en masse**
3. Choisissez **Examen** et **Classe**
4. **Générer commentaires**

---

## Module L5 : IA dans les rappels (frais et examens)

### Étapes

1. **Communication → Rappels**
2. Frais ou examens : canal, classe, tranche
3. **Brouillon IA** → modifiez l’**Aperçu du message**
4. Envoyez et confirmez (l’aperçu s’affiche dans la confirmation)

---

## Module L6 : IA dans la finance (factures)

1. Ouvrez une facture **impayée** / **partielle** / **en retard**
2. **Expliquer avec l’IA**

---

## Module L7 : IA fiche élève (synthèse 360°)

1. **Élèves →** ouvrir un profil
2. **Synthèse 360° IA**

---

## Module L8 : IA saisie des notes (analyse à risque)

1. **Examens → Saisir les notes**
2. Sélectionnez examen et classe
3. **Scan à risque IA**

---

## Module L9 : IA tickets support

1. Ouvrez un ticket
2. **Suggestion IA** → modifiez → envoyer

---

## Module L10 : Studio IA

Menu **Digitex IA → Studio IA** — traduction, résumé, amélioration de texte, etc.

---

## Module L11 : Paramètres IA (Super Admin)

**Digitex IA → Paramètres IA** — clé API, modèle, usage. Activez l’IA par forfait dans **Finance → Forfaits**.

---

# PARTIE M — GLOSSAIRE

---

## Module M1 : Glossaire

### Objectif

Plain-language definitions of words you will see in Digitex SMS menus, reports, and mobile app.

### Qui l'utilise

Everyone — especially new secretaries, accountants, teachers, and parents.

| Terme | Signification |
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
| **Production** | Mode réel — vrais paiements |
| **Digitex IA** | Assistant et outils de rédaction intégrés (option de forfait) |
| **Assistant IA** | Chat complet pour questions et brouillons |
| **Studio IA** | Outils autonomes (traduction, résumé, etc.) |
| **Copilote école** | Briefing IA sur le tableau de bord |
| **Bouton IA** | Action IA violette sur une page métier |

### Questions fréquentes

**Q: What is the difference between invoice and payment?**  
A: **Invoice** = bill (money owed). **Payment** = money received against that bill.

**Q: What is a payment token / pay link?**  
A: A secret URL on an invoice that lets parents pay without logging in.

---

*End of User Manual — For technical setup and API details, see Developer Manual and REST API Manual.*
