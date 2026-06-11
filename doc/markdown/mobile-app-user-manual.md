# Digitex Portal — Mobile App User Manual
# Complete Guide for All Users

**App name:** Digitex Portal (Digitex Management)  
**Audience:** Gate attendants, teachers, school administrators, accountants, students, parents/guardians, and platform super administrators.  
**Goal:** Explain every screen, button, and daily workflow in plain language — with realistic example names, numbers, and messages — so any user can operate the app confidently without technical training.

**Related documentation:**

| Document | What it covers |
|----------|----------------|
| `user-manual.md` | Web admin panel (browser) at https://e-digitex.com/ |
| `api-manual.md` | Hardware scanners and API integration |
| `developer-manual.md` | Technical reference for IT staff |

---

## How to Read This Manual

Each chapter is organized by **user role** and **task**. Every section includes:

- **Purpose** — Why this feature exists
- **Who uses it** — Which accounts see it
- **Step-by-step** — Exact taps and expected results
- **Example story** — A realistic school-day scenario with sample values
- **Common questions** — Troubleshooting in everyday language

Icons and colours in the app follow the Digitex brand: dark blue headers, coloured module cards, green for success, red for errors.

---

## Example School Used Throughout

To keep examples consistent, this manual uses one fictional institution:

| Item | Example value |
|------|---------------|
| **School name** | Green Valley International School |
| **Short code** | GVIS |
| **Academic session** | 2025–2026 |
| **Sample class** | Grade 5 Section A |
| **Sample student** | Marie Kouassi — Admission No. `GVIS-2025-0142`, NFC Card ID `A1B2C3D4` |
| **Sample parent** | Mrs. Aya Kouassi — Phone `+225 07 12 34 56 78` |
| **Sample teacher** | Mr. Jean Dupont — Staff ID `EMP-1042`, Email `j.dupont@gvis.edu.ci` |
| **Sample gate attendant** | Mr. Koffi Mensah — Staff ID `EMP-0091` |
| **Sample school admin** | Mrs. Fatou Diallo — Email `admin@gvis.edu.ci` |
| **Sample accountant** | Mr. Ibrahim Traoré — Email `accounts@gvis.edu.ci` |
| **Sample super admin** | Digitex platform operator — Email `superadmin@digitex.com` |
| **API server** | `https://account.digitexvx.com/api/v1` |

Your real login details are issued by your school administrator. The values above are **illustrations only**.

---

## Table of Contents

1. [Installing and Opening the App](#part-1--installing-and-opening-the-app)
2. [Logging In (All Users)](#part-2--logging-in-all-users)
3. [Understanding Your Dashboard](#part-3--understanding-your-dashboard)
4. [Gate Attendant — Gate Terminal Mode](#part-4--gate-attendant--gate-terminal-mode)
5. [NFC Scanning Workflows](#part-5--nfc-scanning-workflows-shared)
6. [Kids Pickup — Complete Flow](#part-6--kids-pickup--complete-flow)
7. [Teacher — Manual Attendance](#part-7--teacher--manual-attendance)
8. [Teacher — Class Absentees & Parent Alerts](#part-8--teacher--class-absentees--parent-alerts)
9. [Today's Scans & Live Roster](#part-9--todays-scans--live-roster)
10. [Fee Status & Fee Search](#part-10--fee-status--fee-search)
11. [Report Cards & Student Identity](#part-11--report-cards--student-identity)
12. [Timetable & Notices](#part-12--timetable--notices)
13. [Student Portal — All Modules](#part-13--student-portal--all-modules)
14. [Guardian / Parent Portal](#part-14--guardian--parent-portal)
15. [School Admin & Accountant on Mobile](#part-15--school-admin--accountant-on-mobile)
16. [Super Admin on Mobile](#part-16--super-admin-on-mobile)
17. [Profile, Password & Language](#part-17--profile-password--language)
18. [Notifications & Sounds](#part-18--notifications--sounds)
19. [Troubleshooting](#part-19--troubleshooting)
20. [Quick Reference Tables](#part-20--quick-reference-tables)
21. [Glossary](#part-21--glossary)

---

# PART 1 — INSTALLING AND OPENING THE APP

## Purpose

The Digitex Portal mobile app connects staff and families to live school data: gate attendance, pickup approval, fees, results, homework, and announcements. It is the **field companion** to the web admin at https://e-digitex.com/.

## Who uses it

Everyone with a school account: gate attendants, teachers, admin staff, accountants, students, guardians, and super administrators.

## System requirements

| Requirement | Details |
|-------------|---------|
| **Android** | Android 8.0 or newer recommended |
| **NFC** | Required for card scanning modules (most mid-range phones support NFC) |
| **Camera** | Required for QR pickup scanning |
| **Internet** | Wi‑Fi or mobile data — the app does not work fully offline |
| **Permissions** | Camera (QR), NFC (when prompted), notifications (optional, for pickup alerts) |

## Step-by-step — First launch

1. Install **Digitex Portal** from the APK or app store link provided by your school IT team.
2. Open the app. You see the **Digitex logo**, app title **"Digitex Portal"**, and subtitle *"Sign in to access school hardware modules"*.
3. Top-right: language toggle shows **EN** or **FR** — tap to switch between English and French.
4. If you were logged in before, the app may **auto-login** using a saved token and skip directly to your dashboard or gate terminal.

## Example story

Mr. Koffi receives a new Android phone on Monday. IT installs Digitex Portal, enables NFC in phone settings, and gives him Staff ID `EMP-0091` with a temporary password. He opens the app, switches language to French, signs in, and lands on the **Gate Terminal** screen because his account has gate-mode enabled.

## Common questions

**Q: Do students and parents use the same app?**  
A: Yes. One app, one login screen. After login, the server decides which modules you see based on your role.

**Q: Can I use the app on a tablet?**  
A: Yes, if it runs Android and has NFC (for scanning modules).

---

# PART 2 — LOGGING IN (ALL USERS)

## Purpose

Authentication connects your device to your school account on the Digitex server. All modules load only after a successful login.

## Login screen elements

| Element | Description |
|---------|-------------|
| Language button | Toggles EN ↔ FR for all labels |
| Logo | Digitex branding |
| **Email, Username or Staff ID** | Single field — see formats below |
| **Password** | Hidden by default; eye icon toggles visibility |
| **Sign In** | Submits credentials; shows spinner while connecting |

## Accepted login identifiers

The username field accepts **any one** of these (sent to the server as your login ID):

| User type | Example login value | Notes |
|-----------|---------------------|-------|
| School admin | `admin@gvis.edu.ci` | Email address |
| Teacher | `j.dupont@gvis.edu.ci` | Email |
| Teacher / staff | `EMP-1042` | Employee / Staff ID |
| Gate attendant | `EMP-0091` | Staff ID |
| Accountant | `accounts@gvis.edu.ci` | Email |
| Student | `GVIS-2025-0142` | Admission number or shortcode |
| Guardian / parent | `aya.kouassi@gmail.com` | Email or phone-linked account |
| Super admin | `superadmin@digitex.com` | Platform email |

**Password:** Provided by your administrator at account creation. Example temporary password: `Welcome@2025` (you should change it in Profile after first login).

## Step-by-step — Successful login

1. Enter login ID, e.g. `EMP-1042`
2. Enter password
3. Tap **Sign In**
4. App calls the server and stores a secure token
5. App loads your context: school name, logo, role, academic session, available modules
6. Routing:
   - **Gate Attendant (`gate_mode`)** → **Gate Terminal** screen (simplified grid)
   - **All other roles** → **Dashboard** with module cards

## Step-by-step — Failed login

| Message / behaviour | Meaning | What to do |
|--------------------|---------|------------|
| Invalid credentials | Wrong ID or password | Re-check caps lock; ask admin to reset |
| Network error dialog | No internet or server unreachable | Check Wi‑Fi/data; tap **Retry** |
| Blank after login | Rare token issue | Force-close app and reopen |

## Example stories

**Teacher login:**  
Mr. Jean Dupont enters `j.dupont@gvis.edu.ci` and his password. Dashboard shows: *"Welcome, Jean Dupont at Green Valley International School (2025–2026)"* with role badge **Teacher** and **Staff Tools** grid.

**Student login:**  
Marie Kouassi enters admission number `GVIS-2025-0142`. Dashboard shows **Student Portal** with eight modules including **My Gate Pass** and **My Fees**.

**Gate attendant login:**  
Mr. Koffi enters `EMP-0091`. He never sees the full dashboard — only the **Gate Terminal** with eight large scan tiles and a **Logout** button.

## Common questions

**Q: I am both a teacher and a parent. Which portal do I see?**  
A: If your account has both staff and student/guardian access, the dashboard shows **two sections**: **Staff Tools** at the top and **Student Portal** below. Use the **Select child** dropdown when viewing student data for a linked child.

**Q: Does the app remember me?**  
A: Yes. Closing the app usually keeps you logged in until you tap **Logout** or the server invalidates your token.

---

# PART 3 — UNDERSTANDING YOUR DASHBOARD

## Purpose

The dashboard is your **home screen** — a grid of coloured tiles (modules). Each tile opens one feature. What you see depends on your role and school configuration.

## Dashboard header (standard mode)

After login, the top area shows:

```
Welcome, Jean Dupont
at Green Valley International School
(2025–2026)
Role: Teacher
```

Additional indicators may appear:

| Badge | Meaning |
|-------|---------|
| **Subject-wise attendance enabled** | Your school marks attendance per subject, not only per class |
| **Select child** dropdown | Guardian viewing a specific child's data |
| **Full access — staff & student modules** | Super Admin or dual-role account |

## App bar buttons (standard dashboard)

| Icon | Name | Action |
|------|------|--------|
| School logo | Branding | Left side; shows your institution logo |
| Bell (with red number) | Live Pickups | Opens pickup options; badge = pending pickup count |
| Person circle | My Profile | Opens profile and password settings |
| Globe | Language | Switches EN ↔ FR instantly |
| Logout | Sign out | Clears session and returns to login |

## Staff Tools modules (capability-gated)

| Module tile | Subtitle | Who typically sees it |
|-------------|----------|----------------------|
| **Gate Attendance** | NFC In / Out | Gate staff, admin, super admin |
| **Kids Pickup** | QR & NFC Release | Teachers, pickup managers, admin |
| **Fee Status** | Check Balances | Admin, accountant, gate staff |
| **Fee Search** | Lookup by name | Admin, accountant (not in gate mode) |
| **Report Cards** | Parent Signature | Admin, teachers with hardware access |
| **Student Identity** | Verify student card & photo | Gate staff, admin |
| **Take Attendance** | Class / subject manual | Teachers |
| **Class Absentees** | Today's absent list | Teachers, admin staff |
| **Today's Timetable** | Your schedule | Teachers, staff |
| **Today's Scans** | Live roster | Gate staff, teachers, admin |
| **Notices** | Announcements | All staff |

**Super Admin** sees **all** staff modules regardless of individual flags.

## Student Portal modules (eight tiles)

| Module | Subtitle | Opens |
|--------|----------|-------|
| **My Gate Pass** | Generate QR Code | Pickup QR for parents/guardians |
| **My Attendance** | View History | Last 30 attendance records |
| **My Fees** | View Invoices | Balance and invoice list |
| **My Results** | View Report Cards | Exam marks (blocked if fees owed) |
| **My Homework** | View Assignments | Assignments with deadlines |
| **My Requests** | Submit Leave/Request | Absence and leave tickets |
| **Today's Timetable** | Your schedule | Class periods for today |
| **Notices** | Announcements | School notices |

## Recent Gate Scans strip

Staff users with hardware access may see the **last three gate scans** on the dashboard, e.g.:

| Student | Time In | Status |
|---------|---------|--------|
| Marie Kouassi | 07:42 | Present |
| Yao Christian | 07:55 | Late |
| Aminata Diallo | 08:01 | Present |

Tap **Today's Scans** for the full live list.

## Example story

Monday 07:30 — Mr. Dupont opens the app. Bell icon shows **2** pending pickups from yesterday evening. He taps the bell, selects **Live Pickups**, and releases two students whose parents waited at the gate. He then opens **Take Attendance** for Grade 5 Section A before first period.

## Common questions

**Q: I don't see "Take Attendance".**  
A: Your account may lack the `mark_attendance` permission, or you are in Gate Terminal mode (gate attendants don't mark class attendance in the app).

**Q: Why are there two sections on my dashboard?**  
A: You have both staff and student/guardian access — e.g. a teacher who is also a parent.

---

# PART 4 — GATE ATTENDANT — GATE TERMINAL MODE

## Purpose

Gate Terminal is a **simplified, full-screen layout** for security staff at the school entrance. It removes profile, language, and student portal clutter so attendants can scan cards quickly.

## Who uses it

Users with **Gate Attendant** role or `gate_mode` capability from the server. After login they **never** see the standard dashboard.

## Gate Terminal layout

**App bar:** School logo (left) · **Logout** (right only)

**Welcome text (example):**
```
Welcome, Koffi Mensah at Green Valley International School (2025–2026)
Scan staff or student cards for attendance. Parents scan QR for pickup.
```

**Eight module tiles (2×4 grid):**

| # | Tile | Action |
|---|------|--------|
| 1 | Gate Attendance | NFC student/staff in-out |
| 2 | Staff Check-in | NFC staff attendance |
| 3 | Kids Pickup | NFC pickup release |
| 4 | Fee Status | NFC fee balance check |
| 5 | Report Cards | NFC report card lookup |
| 6 | Student Identity | NFC photo & ID verification |
| 7 | Live Pickups | Pending parent pickup list |
| 8 | Today's Scans | Full attendance log for today |

## Step-by-step — Morning student arrival

1. Mr. Koffi opens app → Gate Terminal appears automatically
2. Taps **Gate Attendance**
3. Screen shows: *"Ready. Tap an NFC/RFID card to the back of your phone."*
4. Student Marie Kouassi taps card `A1B2C3D4` on phone
5. Success sound plays; modal shows:
   - **Success!**
   - Student: Marie Kouassi
   - Class: Grade 5 Section A
   - Time In: 07:42
   - Status: Present
6. Taps **Continue Scanning** for next student

## Step-by-step — Manual ID entry (card not working)

1. On NFC screen, scroll to **Or enter ID manually**
2. Type admission number: `GVIS-2025-0142`
3. Tap **Submit Manual ID**
4. Same result modal as NFC scan

## Example story — Busy Friday dismissal

14:30 — Queue at gate. Mr. Koffi alternates between **Kids Pickup** (NFC taps) and **Live Pickups** (teacher approvals). By 15:00, **Today's Scans** shows 412 time-outs recorded. One parent forgot QR; teacher uses **Manual OTP** from the staff dashboard (see Part 6).

## Common questions

**Q: Can gate attendants change language?**  
A: Gate Terminal does not show the language button. Set language before login on the login screen, or ask IT to add standard dashboard access if needed.

**Q: NFC says "not supported or disabled".**  
A: Enable NFC in Android Settings → Connected devices → NFC.

---

# PART 5 — NFC SCANNING WORKFLOWS (SHARED)

## Purpose

The **Universal NFC Screen** handles all card-based operations. The same scanning UI is reused for attendance, fees, pickup, report cards, and identity checks — only the backend **purpose** changes.

## How to open

From Dashboard or Gate Terminal, tap the relevant module tile (e.g. **Gate Attendance**, **Fee Status**).

## Screen elements

| Element | Description |
|---------|-------------|
| Large NFC icon | Visual cue for tap position |
| Status line | "Ready. Tap an NFC/RFID card…" or "Processing" |
| Manual ID field | Fallback text entry |
| **Submit Manual ID** | Sends typed ID to server |
| Recent scans list | Shown for **attendance** purpose only (last entries) |

## Scan purposes explained

### 1. Gate Attendance (`attendance`)

**Use:** Record student or staff **time in** and **time out** at the gate.

**Example success result:**

| Field | Example value |
|-------|---------------|
| Student | Marie Kouassi |
| Type | student |
| Time In | 07:42 |
| Time Out | — (blank until exit scan) |
| Status | Present |
| Punctuality | On time |

**Second scan same day:** Records **Time Out**, e.g. 15:05.

**Staff scan:** Same screen; result shows employee name and designation instead of class.

---

### 2. Fee Status (`fee_check`)

**Use:** Instantly check whether a student has outstanding fees — useful at gate or office.

**Example result:**

| Field | Example value |
|-------|---------------|
| Student | Marie Kouassi |
| Total Balance | XOF 450,000 |
| Paid | XOF 300,000 |
| Remaining | XOF 150,000 |
| Last Payment | 12/09/2025 |

---

### 3. Kids Pickup — NFC (`pickup`)

**Use:** Release a student to an authorized collector when they tap the student's NFC card at pickup time.

**Example flow:**

1. Parent arrives; student card tapped
2. Modal: *"Scan successful! Please wait for teacher approval."*
3. Teacher sees request in **Live Pickups**
4. Teacher taps **RELEASE STUDENT**
5. Parent receives SMS/WhatsApp: pickup approved

---

### 4. Report Cards (`report_card`)

**Use:** Look up published exam results via card — often used when parents collect signed report cards at the office.

**Example result:**

| Field | Example value |
|-------|---------------|
| Exam | Term 1 Examination 2025 |
| Student | Marie Kouassi |
| Mathematics | 16 / 20 |
| French | 14 / 20 |
| Science | 18 / 20 |
| **Total** | **148 / 200** |

---

### 5. Student Identity (`identity_check`)

**Use:** Verify the person holding a card matches the student record — shows photo and biodata.

**Example modal fields:**

| Field | Example value |
|-------|---------------|
| Photo | Student portrait |
| Full name | Marie Kouassi |
| Admission No. | GVIS-2025-0142 |
| Class | Grade 5 Section A |
| Roll No. | 14 |
| Date of birth | 15/03/2014 |
| Blood group | O+ |
| Father | Mr. Kouassi — +225 07 12 34 56 78 |
| Address | Cocody, Abidjan |

## Sounds and feedback

| Event | Feedback |
|-------|----------|
| Successful scan | Success tone |
| Failed scan | Error tone |
| Modal | Green header = success; red = failure |

Tap **Continue Scanning** to reset for the next person.

## Example story

Accountant Mrs. Traoré uses **Fee Status** at the bursar window. Three parents in queue tap cards. Two show **Remaining: XOF 0** — cleared for exam registration. One shows **Remaining: XOF 75,000** — directed to payment desk.

---

# PART 6 — KIDS PICKUP — COMPLETE FLOW

## Purpose

Secure child collection: only authorized adults collect students. Combines **QR codes**, **NFC cards**, **teacher approval**, and **OTP fallback**.

## Roles in pickup

| Role | Responsibility |
|------|----------------|
| **Student / Guardian** | Generate gate-pass QR in app |
| **Gate attendant** | Scan QR or NFC at gate |
| **Teacher / Admin** | Approve release in **Live Pickups** |
| **System** | Sends status SMS/WhatsApp when configured |

## Method A — QR code pickup (most common)

### Parent / student side

1. Open app → **My Gate Pass** (Student Portal)
2. Tap to generate QR (calls server)
3. Screen shows:
   - QR image
   - **Expires at:** e.g. `10/06/2026 15:30`
   - Instruction: *"Present this QR code to the gate guard."*
4. Parent shows QR on phone at gate

### Gate side

1. Staff opens **Kids Pickup** from dashboard bell menu → **Scan QR**
2. Camera opens with viewfinder
3. Scan parent's QR
4. Processing overlay: *"Validating Gate Pass…"*
5. On success: pickup request created — status **Scanned**, waiting teacher

### Teacher approval

1. Open **Live Pickups** (from dashboard bell or tile)
2. See card example:

```
Marie Kouassi
Class: Grade 5 Section A
Parent Contact: +225 07 12 34 56 78
Requested By: Parent
Status: Scanned
[ RELEASE STUDENT ]
```

3. Tap **RELEASE STUDENT**
4. Snackbar: *"Student Released Successfully!"*
5. Parent may receive: *"Marie Kouassi released safely at 14:50"*

## Method B — NFC pickup

1. Gate staff opens **Kids Pickup** → NFC mode (or Gate Terminal **Kids Pickup** tile)
2. Student taps NFC card
3. Same teacher approval flow as QR

## Method C — Manual OTP (lost phone / no QR)

Available from staff dashboard **Kids Pickup** menu:

1. Select **Manual OTP Release**
2. Enter admission number: `GVIS-2025-0142`
3. Tap **Send OTP to Parent**
4. Parent receives 6-digit SMS, e.g. `847293`
5. Staff enters OTP in app
6. Tap **Verify OTP**
7. Pickup request created; teacher still approves in **Live Pickups** if policy requires

## Live Pickups screen details

| Feature | Behaviour |
|---------|-----------|
| Auto-refresh | Every 15 seconds |
| Refresh button | Manual reload top-right |
| Grouping | By date — **Today** vs **Past pending** |
| Empty state | *"All Clear! No parents are currently waiting at the gate."* |
| New arrival alert | Sound + snackbar when count increases |

## Dashboard bell menu (pickup)

Teachers and pickup managers see:

| Option | Action |
|--------|--------|
| Scan QR | Opens camera scanner |
| Live Pickups | Opens approval list |
| Manual OTP | Opens OTP dialog |

Badge number = count of pending/scanned pickups awaiting action.

## Example story — Full afternoon cycle

| Time | Event |
|------|-------|
| 14:35 | Mrs. Kouassi generates QR for Marie |
| 14:44 | Guard scans QR — status **Scanned** |
| 14:44 | Mr. Dupont's phone plays alert — bell shows **1** |
| 14:46 | Mr. Dupont taps **RELEASE STUDENT** |
| 14:46 | Marie exits gate; scan logged in **Today's Scans** time-out |

## Common questions

**Q: QR expired.**  
A: Regenerate **My Gate Pass** — QRs have a short expiry for security.

**Q: Teacher doesn't see pickup.**  
A: Confirm teacher has pickup permission; check internet; pull refresh on Live Pickups.

**Q: OTP invalid.**  
A: OTP expires quickly; request a new code. Verify correct admission number.

---

# PART 7 — TEACHER — MANUAL ATTENDANCE

## Purpose

Teachers record **Present**, **Absent**, **Late**, **Excused**, or **Half Day** for each student in assigned classes — either once per day (class-wise) or per subject (subject-wise).

## Who uses it

Teachers and staff with `mark_attendance` capability.

## Step-by-step — Class-wise attendance

1. Dashboard → **Take Attendance**
2. **Select Class** dropdown → choose `Grade 5 Section A`
3. Roster loads, e.g. 32 students
4. For each student, set status:

| Student | Default | Teacher sets |
|---------|---------|--------------|
| Marie Kouassi | Present | Present |
| Yao Christian | Present | Late |
| Aminata Diallo | Present | Absent |

5. Tap **Save Attendance**
6. Confirmation message; data sent to server

## Step-by-step — Subject-wise attendance

When school enables subject-wise mode (badge on dashboard):

1. Select class: `Licence 1 (BCOM) - Section A`
2. **Select Subject** dropdown → e.g. `Accounting 101`
3. Mark roster for that subject period only
4. **Save Attendance**

## Attendance status meanings

| Status | When to use |
|--------|-------------|
| **Present** | Student in class |
| **Absent** | Student not in class — triggers absentee lists |
| **Late** | Arrived after late margin (school configuration) |
| **Excused** | Approved absence |
| **Half Day** | Left early or arrived mid-day |

## Locked attendance

If the school locked past days, you see:

*"Attendance marking is locked."*

**Save** is disabled. Contact School Admin to adjust lock settings on the web admin.

## Example story

08:15 — Mr. Dupont marks Grade 5 Section A. Two absent: Marie Kouassi and Yao Christian (sick). At 09:00 he opens **Class Absentees** and sends SMS to both parents.

## Common questions

**Q: No classes in dropdown.**  
A: *"No active classes assigned to you."* — Admin must assign homeroom, timetable, or class subjects on web admin.

**Q: Subject dropdown empty.**  
A: *"No subjects assigned for this class."* — Check class subject allocation.

---

# PART 8 — TEACHER — CLASS ABSENTEES & PARENT ALERTS

## Purpose

View who was absent today (or recent days), contact parents by phone/WhatsApp, and send **SMS and/or WhatsApp absence alerts** directly from the app.

## Who uses it

Teachers, school admin staff, and super admin.

## Opening the screen

Dashboard → **Class Absentees**

## Screen layout

### Summary header (example)

```
Today (10 Jun)
Section: Licence 1 (BCOM) - Section A

   2          0           2
 Total    Present     Absent
```

### Absent student card (example)

```
☐ Madisyn Lebsack (32682588)
  👤 Caleb Schroeder (parent)
  📞 +243 991 214 567   [Call] [WhatsApp]

  [ Send SMS ]  [ Send WhatsApp ]  [ SMS + WhatsApp ]
```

## Step-by-step — Notify one parent

1. Find student in absent list
2. Tap **Send SMS** (or WhatsApp / both)
3. Confirm dialog: *"Send absence notification to parent of Madisyn Lebsack?"*
4. Choose channels if prompted: ☑ SMS ☑ WhatsApp
5. Tap **Send Notification**
6. Result snackbar, e.g.: *"Notifications sent for 1 student(s). (Sent: 1, Failed: 0)"*

**Sample SMS received by parent:**

> Dear Parent, your child Madisyn Lebsack was marked absent from Green Valley International School on 10/06/2026. Please contact the school if you have any questions.

## Step-by-step — Bulk notify

1. Tap **Select all absent students** (or tick individual checkboxes)
2. Bottom bar: **Notify Selected (2)**
3. Choose SMS / WhatsApp / both
4. Confirm and send

## Contact actions

| Button | Action |
|--------|--------|
| Green phone icon | Opens device dialer with parent number |
| WhatsApp icon | Opens WhatsApp chat with parent |
| Phone row tap | Bottom sheet with full number and actions |

## Toolbar actions

| Button | Action |
|--------|--------|
| Refresh | Reload absentee data from server |
| Select All | Tick all absent students in section |
| Clear selection | Untick all |

## When notifications fail

The app shows the **specific server reason**, for example:

| Message | Cause | Fix |
|---------|-------|-----|
| Template 'student_absent' missing | SMS template not configured | School admin: Configuration → SMS Templates |
| No parent phone number on file | Parent record incomplete | Update parent phone on web admin |
| Insufficient credits | SMS balance empty | Super admin: recharge credits |
| sms: Provider error | Gateway misconfigured | Check SMS provider in Configuration |

## Example story

Mr. Dupont selects two absent students, sends **SMS + WhatsApp** to both parents before 09:30. One succeeds; one fails with *"No parent phone number on file"* — he calls the office to update the parent record for Yao Christian.

## Common questions

**Q: Student not listed but was absent.**  
A: Attendance may not be saved yet, or student is in a section you don't access.

**Q: Can I notify for yesterday?**  
A: The report groups by date; select students from the relevant date section.

---

# PART 9 — TODAY'S SCANS — LIVE ROSTER

## Purpose

Real-time list of everyone who scanned at the gate today — time in, time out, photo, and status.

## Who uses it

Gate attendants, teachers, school admin.

## Step-by-step

1. Open **Today's Scans**
2. List auto-refreshes every **10 seconds**
3. Tap **Refresh** for immediate reload

## Example row

| Photo | Marie Kouassi |
|-------|---------------|
| Time In | 07:42 |
| Time Out | 15:05 |
| Status | Present (green badge) |

## Status badges

| Colour | Status |
|--------|--------|
| Green | Present |
| Red | Absent (manual attendance, not gate) |
| Orange | Late |

## Example story

School Admin Mrs. Diallo checks **Today's Scans** at 10:00. She counts 398 time-ins against 420 enrolled — identifies 22 not yet arrived and calls homeroom teachers.

---

# PART 10 — FEE STATUS & FEE SEARCH

## Fee Status (NFC)

**Path:** Dashboard → **Fee Status** → Universal NFC Screen

Scan student card → modal shows balance summary (see Part 5).

**Example — cleared student:**

- Total: XOF 450,000 · Paid: XOF 450,000 · **Remaining: XOF 0**

**Example — debtor:**

- Remaining: **XOF 150,000** — student may be blocked from viewing results in Student Portal until paid.

---

## Fee Search (text lookup)

**Path:** Dashboard → **Fee Search**  
**Who:** Admin, accountant (not shown in Gate Terminal mode)

### Step-by-step

1. Type at least **2 characters** — name or admission number
2. Example search: `Marie` or `GVIS-2025`
3. Results list:

```
Marie Kouassi — GVIS-2025-0142
Grade 5 Section A
Outstanding: XOF 150,000  [OVERDUE]
```

4. Tap a row for detail if available

### Example story

Mr. Traoré (accountant) searches `Kouassi` during fee collection week. Finds two siblings, records payment on web admin, then re-scans cards at window showing **Remaining: XOF 0**.

---

# PART 11 — REPORT CARDS & STUDENT IDENTITY

## Report Cards (NFC)

**Use cases:**

- Parent collects signed bulletin at office — staff verifies marks on device
- Gate event — quick academic status check

Scan card → exam breakdown with subject marks and totals (see Part 5).

**Note:** Students see full results in **My Results**; staff NFC view is for verification at counter/gate.

---

## Student Identity (NFC)

**Use cases:**

- Verify stranger picking up child matches registered photo
- Security check at exam hall entrance

Opens dedicated dialog with photo and full biodata (see Part 5).

### Example story

Unknown adult claims to be uncle at gate. Guard scans student's card in **Student Identity**, compares photo to adult — escalates to office when mismatch.

---

# PART 12 — TIMETABLE & NOTICES

## Today's Timetable

**Path:** Dashboard → **Today's Timetable** (Staff or Student Portal)

### Example entries — Teacher (Mr. Dupont)

| Time | Subject | Class | Room |
|------|---------|-------|------|
| 08:00 – 09:00 | Mathematics | Grade 5 A | Room 12 |
| 10:00 – 11:00 | Mathematics | Grade 6 B | Room 14 |
| 14:00 – 15:00 | Club Math | Grade 5 A | Lab 2 |

### Example entries — Student (Marie)

| Time | Subject | Teacher | Room |
|------|---------|---------|------|
| 08:00 | Mathematics | Mr. Dupont | Room 12 |
| 09:00 | French | Ms. Koné | Room 12 |
| 11:00 | Science | Dr. Bamba | Lab 1 |

Pull down to refresh.

---

## Notices

**Path:** Dashboard or Student Portal → **Notices**

### Example notice cards

| Type | Title | Body excerpt |
|------|-------|--------------|
| 🔴 Urgent | School Closed Thursday | Due to public holiday… |
| 🟡 Warning | Fee deadline 15 June | Last date for Term 2… |
| 🔵 Normal | Sports Day 20 June | All parents invited… |

Pull down to refresh. Notices are filtered by audience — students see student/parent notices; staff see staff notices.

---

# PART 13 — STUDENT PORTAL — ALL MODULES

## Purpose

Students (and guardians acting for children) access personal academic and administrative information.

## My Gate Pass

Generate time-limited QR for pickup. See Part 6.

**Example expiry:** `10/06/2026 15:30`

---

## My Attendance

Shows last **30** attendance records.

| Date | Status | Time In |
|------|--------|---------|
| 09/06/2026 | Present | 07:40 |
| 08/06/2026 | Late | 08:15 |
| 07/06/2026 | Present | 07:38 |

Colour coding: green = present, red = absent, orange = late.

---

## My Fees

### Summary card (example)

| Field | Value |
|-------|-------|
| Total Fees Due | XOF 450,000 |
| Amount Paid | XOF 300,000 |
| **Outstanding Balance** | **XOF 150,000** |

### Invoice list

```
Invoice #INV-2025-0842
Term 2 Tuition — Due: 15/06/2026
Amount: XOF 150,000  [OVERDUE]
```

---

## My Results

### When fees are clear

Shows **Official Transcript** with exams, subjects, marks, grades.

**Example:**

```
Term 1 Examination 2025
Mathematics    16/20   A
French         14/20   B
Science        18/20   A*
Total          148/200
```

### When fees are owed — blocked

Screen shows:

- **Results Locked**
- *"Please clear your outstanding balance to view your exam results."*
- Outstanding amount displayed

School policy (configured on web admin) controls this block.

---

## My Homework

Lists assignments (~7 days).

**Example card:**

```
Subject: Mathematics
Title: Exercise 4 — Fractions
Deadline: 12/06/2026
[ Download File ]  (if attachment exists)
```

---

## My Requests

Two tabs: **History** and **New Request**

### Submit new request

1. Tab **New Request**
2. **Request Type:** Absence / Late Arrival / Leave / Other
3. **Reason:** Free text, e.g. *"Doctor appointment 14 June"*
4. Tap **Submit Request**
5. Appears in **History** with status **Pending**

### History example

| Ticket | Type | Status |
|--------|------|--------|
| #REQ-1042 | Absence | Approved |
| #REQ-1038 | Late | Pending |

Admin processes requests on web admin; student sees status update on refresh.

---

# PART 14 — GUARDIAN / PARENT PORTAL

## Purpose

Parents and guardians monitor children and participate in pickup — usually through a **Guardian account** linked to one or more students.

## Login

Same app and login screen. Example: `aya.kouassi@gmail.com`

## Select child dropdown

When multiple children are linked:

```
Select child: [ Marie Kouassi ▼ ]
              [ Yao Kouassi   ]
```

All Student Portal modules apply to the **selected child**.

## Typical parent workflows

| Task | Module |
|------|--------|
| Generate pickup QR | My Gate Pass (for selected child) |
| Check attendance | My Attendance |
| Pay fees at school / check balance | My Fees |
| View results | My Results |
| Read homework | My Homework |
| Report absence in advance | My Requests |
| Read school news | Notices |

## Example story

Mrs. Kouassi logs in, selects **Marie Kouassi**, checks **My Fees** — sees XOF 150,000 overdue. She pays at school. Same evening she opens **My Gate Pass** for tomorrow's pickup QR.

## Common questions

**Q: I see no children in dropdown.**  
A: Admin must link your guardian account to student records on web admin.

**Q: Can both parents use one account?**  
A: Typically one guardian login is created; school may create separate accounts per parent if needed.

---

# PART 15 — SCHOOL ADMIN & ACCOUNTANT ON MOBILE

## Purpose

Administrators and finance staff use mobile for **field operations** — gate verification, fee checks, absentee alerts, pickup oversight — while full configuration remains on web admin.

## Typical admin mobile modules

| Module | Admin use case |
|--------|----------------|
| Gate Attendance | Spot-check gate operations |
| Class Absentees | Send bulk absence alerts |
| Fee Search / Fee Status | Verify payment at counter |
| Student Identity | Fraud prevention at gate |
| Live Pickups | Approve when teachers unavailable |
| Today's Scans | Monitor arrival statistics |
| Notices | Read urgent announcements on the move |

## Accountant example day

| Time | Action |
|------|--------|
| 08:00 | **Fee Search** — verify 5 walk-in payments |
| 10:00 | **Fee Status** NFC — confirm clearance before exam registration |
| 14:00 | Web admin — record payments (full finance on browser) |

## School admin example day

| Time | Action |
|------|--------|
| 07:45 | **Today's Scans** — 95% arrival by first bell |
| 09:30 | **Class Absentees** — verify teachers sent parent alerts |
| 15:00 | **Live Pickups** — assist during staff shortage |

**Important:** Creating students, configuring SMS, managing subscriptions, and full reports require the **web admin** at https://e-digitex.com/ — see `user-manual.md`.

---

# PART 16 — SUPER ADMIN ON MOBILE

## Purpose

Digitex platform operators (Super Admin) have **full mobile access** to all staff and student modules for testing and emergency support at any school.

## Behaviour

- Capability flag `super_admin: true` unlocks **every** staff module tile
- Dashboard subtitle may show: *"Full access — staff & student modules"*
- Both **Staff Tools** and **Student Portal** sections visible
- Can notify absentees, scan NFC, approve pickups at any institution context their token allows

## Web vs mobile split

| Task | Mobile | Web admin |
|------|--------|-----------|
| Platform statistics | Limited | Full platform dashboard |
| Create institutions | No | Yes |
| SMS credit recharge | No | Yes |
| Module toggles | No | Yes |
| Gate scan testing | Yes | N/A |
| Support parent pickup | Yes | Yes |

## Example story

Digitex support engineer logs in as Super Admin, switches school context on web, then uses mobile **Gate Attendance** at Green Valley to verify NFC integration after deployment.

---

# PART 17 — PROFILE, PASSWORD & LANGUAGE

## Opening profile

Dashboard app bar → **Account circle** icon → **My Profile**

## Profile fields

| Field | Editable | Example |
|-------|----------|---------|
| Photo | Yes (camera/gallery) | Portrait |
| Name | Usually read-only | Jean Dupont |
| Email | Read-only | j.dupont@gvis.edu.ci |
| Shortcode | Read-only | EMP-1042 |
| Phone | Yes | +225 07 00 00 00 00 |
| Address | Yes | Cocody, Abidjan |
| Class / Department | Read-only | Grade 5 A / Mathematics |

## Change password

1. Scroll to password section
2. Enter current password
3. Enter new password twice
4. Tap **Save Changes**

## Language

Toggle **EN** / **FR** from dashboard app bar at any time. All module titles and messages update immediately.

## Logout

Dashboard app bar → **Logout** — returns to login screen and clears saved token.

---

# PART 18 — NOTIFICATIONS & SOUNDS

## Push notifications (FCM)

On login, the app registers for push notifications if Firebase is configured. Used for platform-level alerts.

## In-app pickup alerts

| Trigger | Feedback |
|---------|----------|
| New pending pickup | Bell badge count increases |
| Count rises while app open | Success sound + snackbar *"New pickup at gate (N pending)"* |

Polling interval: **10 seconds** on dashboard.

## Absence alerts (SMS/WhatsApp)

Sent from **Class Absentees** — not push notifications. Delivered via school's SMS/WhatsApp gateway to parent phone numbers.

## Example pickup alert sequence

1. Guard scans QR — pending count 0 → 1
2. Teacher's phone plays tone
3. Snackbar: *"New pickup at gate (1 pending)"*
4. Bell shows red **1**

---

# PART 19 — TROUBLESHOOTING

## Login & connection

| Problem | Solution |
|---------|----------|
| Network error on login | Check internet; verify server URL with IT |
| Invalid credentials | Reset password via web admin |
| App stuck on loading | Force-close and reopen; check server status |

## NFC scanning

| Problem | Solution |
|---------|----------|
| NFC disabled | Android Settings → NFC → On |
| Card not detected | Remove phone case; tap centre-back; try manual ID |
| Unknown card | Student card may be unregistered — admin updates RFID on web |
| Wrong student returned | Verify card assignment in student record |

## QR pickup

| Problem | Solution |
|---------|----------|
| Camera black | Grant camera permission in app settings |
| Invalid QR | Regenerate gate pass; check expiry time |
| Scan OK but no release | Teacher must approve in Live Pickups |

## Attendance & absentees

| Problem | Solution |
|---------|----------|
| No classes assigned | Admin assigns timetable/homeroom |
| Attendance locked | Admin adjusts lock days in web Settings |
| Notify failed — template missing | Run SMS template migration/seeder on server |
| Notify failed — no phone | Add parent phone in web admin Parents module |

## Student portal

| Problem | Solution |
|---------|----------|
| Results locked | Pay outstanding fees |
| No homework | Teacher may not have published assignments |
| Gate pass won't generate | Check enrollment status and internet |

## General

| Problem | Solution |
|---------|----------|
| Wrong language | Tap globe icon — toggle EN/FR |
| Missing module | Role lacks permission — contact admin |
| Old data showing | Pull to refresh; check internet |

---

# PART 20 — QUICK REFERENCE TABLES

## Role → Primary mobile screens

| Role | First screen | Main daily modules |
|------|--------------|-------------------|
| Gate Attendant | Gate Terminal | Gate Attendance, Kids Pickup, Live Pickups, Today's Scans |
| Teacher | Dashboard | Take Attendance, Class Absentees, Live Pickups, Timetable |
| School Admin | Dashboard | All staff modules as permissions allow |
| Accountant | Dashboard | Fee Search, Fee Status, Notices |
| Student | Dashboard | Student Portal eight modules |
| Guardian | Dashboard | Student Portal + Select child |
| Super Admin | Dashboard | Full staff + student modules |

## Module → API area (for IT reference)

| App module | Server area |
|------------|-------------|
| Login / Profile | `/v1/login`, `/v1/me/context`, `/v1/profile` |
| NFC scans | `/v1/hardware/attendance/scan` |
| Today's Scans | `/v1/hardware/attendance/today` |
| Class Absentees | `/v1/hardware/attendance/absentees` |
| Notify parents | `/v1/hardware/attendance/absentees/notify` |
| Take Attendance | `/v1/teacher/attendance/*` |
| Live Pickups | `/v1/pickup/pending`, `/v1/pickup/approve` |
| Student fees | `/v1/student/fees` |
| Gate pass QR | `/v1/student/gate-pass` |
| Notices | `/v1/notices` |
| Timetable | `/v1/timetable/today` |

## Example credentials summary (fictional)

| Person | Login | Password (example) | Primary use |
|--------|-------|-------------------|-------------|
| Gate attendant Koffi | `EMP-0091` | (from admin) | Gate Terminal |
| Teacher Jean | `j.dupont@gvis.edu.ci` | (from admin) | Attendance, absentees |
| Admin Fatou | `admin@gvis.edu.ci` | (from admin) | Oversight, fee search |
| Accountant Ibrahim | `accounts@gvis.edu.ci` | (from admin) | Fee lookup |
| Student Marie | `GVIS-2025-0142` | (from admin) | Student portal |
| Parent Aya | `aya.kouassi@gmail.com` | (from admin) | Child pickup QR |
| Super Admin | `superadmin@digitex.com` | (platform) | Full access testing |

---

# PART 21 — GLOSSARY

| Term | Simple meaning |
|------|----------------|
| **Digitex Portal** | The mobile app described in this manual |
| **Gate Terminal** | Simplified full-screen layout for gate attendants |
| **NFC / RFID** | Tap-to-read student or staff ID card |
| **Gate Pass** | Time-limited QR code for child pickup |
| **Live Pickups** | Screen where teachers approve child release |
| **OTP** | One-time 6-digit SMS code for manual pickup verification |
| **Admission No.** | Student's unique school ID (e.g. GVIS-2025-0142) |
| **Staff ID** | Employee identifier for login (e.g. EMP-1042) |
| **Subject-wise attendance** | Marking attendance per lesson, not only once daily |
| **Outstanding balance** | Unpaid school fees remaining |
| **Capability** | Server flag that shows/hides app modules |
| **Session** | Academic year (e.g. 2025–2026) |
| **Snackbar** | Brief message bar at bottom of screen |
| **Module tile** | Coloured square button on dashboard |

---

## Document Information

| Item | Value |
|------|-------|
| **Manual version** | 1.0 |
| **App** | Digitex Portal (Flutter mobile) |
| **Web admin manual** | `doc/markdown/user-manual.md` |
| **PDF regeneration** | `php artisan docs:generate-pdf` (includes this manual when configured) |
| **Support** | Contact your school administrator or Digitex platform support |

---

*End of Digitex Portal Mobile App User Manual*
