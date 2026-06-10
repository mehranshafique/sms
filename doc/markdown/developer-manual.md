# Digitex SMS — Developer Manual
# Module-by-Module Technical Reference

**Audience:** Developers, integrators, and technical school IT staff.  
**Companion:** User Manual (layman flows), REST API Manual (hardware/mobile endpoints).

**Example tenant used in examples:**

- Institution ID: `3`  
- Name: Green Valley International School (`GVIS`)  
- Session ID: `12` (2025-2026)  
- Class Section ID: `45` (Grade 5A)  
- Student ID: `1001` (Marie Kouassi)

---

## How This Manual Is Organized

Each module section includes:

| Section | Content |
|---------|---------|
| **Purpose** | Business reason the module exists |
| **User-facing summary** | What layman sees (link to User Manual) |
| **Depends on** | Upstream modules / data |
| **Used by** | Downstream modules |
| **Routes** | Web route names / prefixes |
| **Controller** | Primary PHP class |
| **Models** | Eloquent models & key columns |
| **Permissions** | Spatie permission slugs |
| **Module gate** | `CheckModuleAccess` slug if any |
| **Institution scoping** | How to query safely |
| **Key flows** | Request lifecycle |
| **Integration points** | Notifications, jobs, API |
| **Example** | Concrete IDs / payloads |
| **Pitfalls** | Common bugs for developers |

---

# FOUNDATION

---

## F1: Architecture & Multi-Tenancy

### Purpose

Single Laravel application serves many schools. Data isolation uses `institution_id` on tenant tables and session context.

### Core classes

| Class | Role |
|-------|------|
| `BaseController` | `getInstitutionId()`, `getAllowedInstitutionIds()`, `assertInstitutionMatch()`, `checkInstitution(Role)` |
| `LoadInstitutionSettings` | Middleware — loads settings into runtime |
| `CheckSubscription` | Blocks expired subscriptions (3-day grace) |
| `CheckModuleAccess` | Route middleware `CheckModuleAccess:module_slug` |
| `SetLocale` | EN/FR from session |

### getInstitutionId() resolution order

1. Session `active_institution_id` if user allowed (or `'global'` → `null` for Super Admin)
2. Fallback `users.institute_id`
3. First ID from allowed list

### Example

```php
$institutionId = $this->getInstitutionId(); // 3 or null (global)

Student::where('institution_id', $institutionId)
    ->findOrFail($studentId);
```

### Pitfalls

- **IDOR:** Never `Student::findOrFail($id)` without institution scope for school-scoped roles.
- **Global context:** Super Admin with `null` institution must still validate when writing data.

---

## F2: Authorization Stack

### Layers

1. **Route middleware:** `auth`, `verified`, `RoleMiddleware`, `PermissionMiddleware`
2. **Spatie permissions:** `{singular_module}.{action}` e.g. `student.create`
3. **Policies:** `ResourcePolicy` on models
4. **Module access:** Package/subscription enabled modules

### RolePermissionSeeder modules

See `database/seeders/RolePermissionSeeder.php` — `$modulesData` array defines all modules and actions.

### Example permission check

```php
if (!Auth::user()->can('invoice.view')) {
    abort(403);
}
```

---

## F3: Notification Architecture

### Services

| Service | Responsibility |
|---------|----------------|
| `NotificationPreferenceService` | `isChannelEnabled($institutionId, $eventKey, $channel)` |
| `NotificationService` | SMS, WhatsApp, email via gateways |
| `InAppNotificationService` | Rows in `in_app_notifications`, respects `system` channel |

### Settings key pattern

`notify_{event_key}` → JSON: `{"sms":true,"whatsapp":false,"email":true,"system":true}`

Stored in `institution_settings`.

### Default policy

- No row saved → **system ON**, SMS/WhatsApp/email **OFF**
- Always-on events: `institution_created`, `low_balance`, `user_welcome`, etc.

### Hook pattern in controllers

```php
app(InAppNotificationService::class)->notifyPaymentReceived($payment);
$this->notificationService->sendInvoiceNotification($invoice);
```

---

## F4: Development Setup

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
# Set DB_*, HARDWARE_SECRET, APP_URL=https://e-digitex.com
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=SmsTemplateSeeder
php artisan storage:link
php artisan docs:generate-pdf   # regenerate manuals
```

Default admin: `admin@digitex.com` / `password`

---

# MODULE REFERENCE (Alphabetical BY DOMAIN)

---

## M01: Academic Sessions

| Item | Value |
|------|-------|
| **Purpose** | Define school years; one `is_current` drives defaults |
| **Depends on** | Institution |
| **Used by** | Enrollments, Fee structures, Exams, Invoices |
| **Routes** | `academic-sessions.*` resource |
| **Controller** | `AcademicSessionController` |
| **Model** | `AcademicSession` — `institution_id`, `name`, `start_date`, `end_date`, `is_current` |
| **Module slug** | `academic_sessions` |
| **Permissions** | `academic_session.viewAny`, `.create`, `.update`, `.delete` |

### Example

```php
AcademicSession::where('institution_id', 3)
    ->where('is_current', true)
    ->first(); // 2025-2026
```

---

## M02: Assignments

| Item | Value |
|------|-------|
| **Purpose** | Homework with deadlines |
| **Depends on** | Class sections, subjects, staff |
| **Used by** | Student portal API `GET /v1/student/homework`, chatbot |
| **Routes** | `assignments.*`, `assignments.get-subjects` |
| **Controller** | `AssignmentController` |
| **Model** | `Assignment` |
| **Module slug** | `assignments` |

### API exposure

`StudentPortalApiController::getHomework()` — 7-day window, enrolled class.

---

## M03: Audit Logs

| Item | Value |
|------|-------|
| **Purpose** | Change history |
| **Controller** | `AuditLogController` |
| **Model** | `AuditLog` |
| **Route middleware** | Super Admin only |
| **Trait** | `LogsActivity` on key models |

---

## M04: Budgets & Fund Requests

| Item | Value |
|------|-------|
| **Purpose** | Department budgets and approval workflow |
| **Depends on** | Institution, finance permissions |
| **Routes** | `budgets.*`, `budgets.requests`, approve endpoints |
| **Controller** | `Finance\BudgetController` |
| **Models** | `Budget`, `BudgetCategory`, `FundRequest` |
| **Permissions** | `budget.approve_funds` for approval |
| **Notifications** | `notifyFundRequestSubmitted`, `notifyFundRequestProcessed` |

### Institution scoping (required)

```php
FundRequest::where('institution_id', $institutionId)->findOrFail($id);
Budget::where('institution_id', $institutionId)->findOrFail($budgetId);
```

---

## M05: Campuses

| Item | Value |
|------|-------|
| **Model** | `Campus` — `institution_id`, `name`, `address` |
| **Controller** | `CampusController` |
| **Routes** | `campuses.*` |

---

## M06: Chatbot

| Item | Value |
|------|-------|
| **Purpose** | WhatsApp/SMS conversational UI |
| **Service** | `ChatbotLogicService` (large state machine) |
| **Web routes** | `/chatbot/settings`, keywords, sessions |
| **Controller** | `ChatbotSettingController` |
| **Models** | `ChatbotKeyword`, `ChatSession` |
| **API** | `POST /api/v1/chatbot/webhook/{provider}` |
| **Middleware** | Role: Super Admin, Head Officer, School Admin on web routes |

### Webhook providers

`infobip`, `twilio`, `meta`, `mobishastra`

### Credits

Messages debit institution `sms_credits` unless `chatbot_free_interactions` enabled.

---

## M07: Class Sections

| Item | Value |
|------|-------|
| **Model** | `ClassSection` — `grade_level_id`, `staff_id` (homeroom), `institution_id` |
| **Controller** | `ClassSectionController` |
| **Module slug** | `class_sections` |

### Example

Grade 5 Section A → `id=45`, `grade_level_id=8`, `staff_id=22` (Mr. Dupont)

---

## M08: Class Subjects

| Item | Value |
|------|-------|
| **Purpose** | Allocate subject + teacher to class |
| **Controller** | `ClassSubjectController` |
| **Model** | `ClassSubject` |
| **Module slug** | `class_subjects` |

---

## M09: Configuration

| Item | Value |
|------|-------|
| **Controller** | `ConfigurationController`, `SmsTemplateController` |
| **Routes** | `/configuration/*` under role middleware |
| **Storage** | `institution_settings` key/value groups: `smtp`, `sms`, `notifications`, `academics`, `system` |

### SMS credential keys (examples)

| Key | Encrypted? |
|-----|------------|
| `sms_provider` | No — e.g. `twilio` |
| `whatsapp_provider` | No — e.g. `meta` |
| `twilio_sid`, `twilio_from` | No |
| `twilio_token` | Yes |
| `meta_access_token` | Yes |
| `infobip_api_key` | Yes |
| `mobishastra_password` | Yes |

### updateSms flow

1. Validate providers selected  
2. Save public keys loop  
3. Encrypt secrets via `Crypt::encryptString`  
4. Super Admin in **Global View** (`getInstitutionId()` = null) saves:
   - `sms_provider` / `whatsapp_provider` globally → overrides `config/sms.php` / `.env`
   - Allowed provider lists (`allowed_sms_providers`, `allowed_whatsapp_providers`)
   - Global API credentials (`institution_id` = null)
5. `SystemCommunicationConfigService::applyGlobalOverrides()` runs on boot and after global save

### Provider resolution (school sends message)

| School setting | Provider used | Credentials from |
|----------------|---------------|------------------|
| `sms_provider` = `system` | Global `sms_provider` or `config('sms.default')` | Global settings (null institution) |
| `sms_provider` = `twilio` | Twilio | That school's settings |

Helper: `InstitutionSetting::resolveSystemProvider('sms'|'whatsapp')`

### Global View requirement

Super Admin must set `session('active_institution_id')` to `'global'` (via header **Global View**) before opening Configuration → ID Sender SMS. Otherwise settings apply to the currently selected school only.

### Test endpoints

- `configuration.smtp.test`  
- `configuration.sms.test` — uses `NotificationService::performSend` with unlimited flag  

---

## M10: Dashboard

| Item | Value |
|------|-------|
| **Controller** | `DashboardController` |
| **Views** | `dashboard/super_admin`, `main_admin`, `teacher`, `student`, `accountant`, etc. |
| **Route** | `dashboard` |

Role detected via `Auth::user()->hasRole()` and redirects to appropriate view with real stats queries scoped by `$institutionId`.

---

## M11: Departments

| Item | Value |
|------|-------|
| **Model** | `Department` |
| **Controller** | `DepartmentController` |
| **Module slug** | `departments` |
| **Used by** | Subjects, Programs (university) |

---

## M12: Elections & Voting

| Item | Value |
|------|-------|
| **Controllers** | `ElectionController`, `StudentVotingController`, `VotingController` |
| **Models** | `Election`, `ElectionPosition`, `Candidate`, `Vote` |
| **Module slug** | `elections` |
| **Policy** | `ElectionPolicy` via `authorizeResource` |

### Security note

`ElectionController` DataTables `orderColumn` must whitelist sort direction (ASC/DESC only).

---

## M13: Exams, Schedules, Marks, Results

| Component | Controller | Model |
|-----------|------------|-------|
| Exams | `ExamController` | `Exam` — `status`: draft → published |
| Schedules | `ExamScheduleController` | `ExamSchedule` |
| Marks | `ExamMarkController` | `ExamRecord` |
| Result cards | `ResultCardController` | — |
| Reports | `ReportController` | bulletin, transcript views |

### Publish flow

`ExamController::finalize` → sets published → `InAppNotificationService::notifyExamPublished`

### Student API

`StudentPortalApiController::getResults()` — only `exam.status = published`, debt block check.

### Hardware API

`AttendanceApiController::handleReportCard()` — same published filter.

---

## M14: Fee Types & Fee Structures

| Item | Value |
|------|-------|
| **Controllers** | `FeeTypeController`, `FeeStructureController` |
| **Models** | `FeeType`, `FeeStructure`, `FeeStructureItem` |
| **Module slugs** | `fee_types`, `fee_structures` |

### Example structure

Grade 5 Tuition 2025-2026 — 3 tranches × 150,000 XOF linked to `grade_level_id=8`.

---

## M15: Global Search

| Item | Value |
|------|-------|
| **Controller** | `GlobalSearchController` |
| **Service** | `GlobalSearchService` |
| **Route** | `global-search.suggest?q=` |
| **Auth** | Permission-scoped results per entity type |

---

## M16: Grade Levels

| Item | Value |
|------|-------|
| **Model** | `GradeLevel` — `name`, `order`, `institution_id` |
| **Controller** | `GradeLevelController` |
| **Module slug** | `grade_levels` |

---

## M17: Head Officers

| Item | Value |
|------|-------|
| **Controller** | `HeadOfficersController` |
| **Middleware** | Super Admin only |
| **Pivot** | `institution_head_officers` |
| **User type** | `UserType::HEAD_OFFICER` |

---

## M18: In-App Notifications

| Item | Value |
|------|-------|
| **Model** | `InAppNotification` |
| **Service** | `InAppNotificationService` |
| **Controller** | `InAppNotificationController` |
| **Composer** | `AppServiceProvider` shares `$inAppNotifications` to header |
| **Routes** | `notifications.read`, `notifications.read_all` |

### Event key map

See `InAppNotificationService::EVENT_KEYS` — maps internal type to `notify_*` preference key.

---

## M19: Institutions

| Item | Value |
|------|-------|
| **Model** | `Institution` |
| **Controller** | `InstituteController` |
| **Relations** | campuses, subscriptions, settings |

---

## M20: Invoices & Payments

| Component | Controller | Key logic |
|-----------|------------|-----------|
| Invoices | `Finance\InvoiceController` | Bulk generate, PDF print |
| Payments | `Finance\PaymentController` | Links to invoice, triggers notifications |

### Scoping pattern

```php
Invoice::when($institutionId, fn($q) => $q->where('institution_id', $institutionId))
    ->findOrFail($id);
```

### Notifications

- `notifyInvoiceCreated`  
- `notifyPaymentReceived`  
- `NotificationService::sendInvoiceNotification`  

---

## M21: Modules & Permissions (System)

| Item | Value |
|------|-------|
| **Models** | `Module`, Spatie `Permission`, `Role` |
| **Controllers** | `ModuleController`, `PermissionController`, `RolesController`, `RolePermissionController` |
| **PermissionController** | Super Admin only for mutations |
| **RolePermissionController** | Requires `checkInstitution($role)` + `role.update` |

---

## M22: Notices & Reminders

| Item | Value |
|------|-------|
| **Notices** | `NoticeController`, `StudentNoticeController` |
| **Model** | `Notice` — `audience`, `is_published` |
| **Reminders** | `ReminderController` — fee/exam bulk SMS |
| **Module slug** | `notices` |

### Notice notification

`notifyNoticePublished` — audience maps to role lists.

---

## M23: Packages & Subscriptions

| Item | Value |
|------|-------|
| **Controller** | `SubscriptionController` |
| **Models** | `Package`, `Subscription`, `PlatformInvoice` |
| **Middleware** | Super Admin / Head Officer for platform billing |

`Package.modules` JSON → drives `CheckModuleAccess`.

---

## M24: Parents

| Item | Value |
|------|-------|
| **Model** | `StudentParent` |
| **Controller** | `ParentController` |
| **Relation** | `Student.parent_id`, optional `user_id` for portal |

---

## M25: Payroll & Salary Structures

| Item | Value |
|------|-------|
| **Controllers** | `PayrollController`, `SalaryStructureController` |
| **Models** | `Payroll`, `SalaryStructure`, `PayrollItem` |
| **Module slugs** | `payrolls`, `salary_structures` |

---

## M26: Pickup (Web + API)

| Surface | Controller |
|---------|------------|
| Web guard/teacher/parent | `PickupWebController` |
| Mobile staff | `Api\V1\AppPickupController` |
| Hardware scan | `Api\V1\AttendanceApiController::handlePickup` |
| Chatbot QR | `Api\V1\Chatbot\PickupController` |

| Model | `StudentPickup` — statuses: pending → scanned → approved/rejected |

### Authorization

- Web `updateStatus` — teacher/admin roles + institution scope  
- API OTP — guardian/staff + `userCanAccessStudent`  
- API approve — institution membership for admins  

---

## M27: Programs & Academic Units (LMD)

| Item | Value |
|------|-------|
| **Controllers** | `ProgramController`, `AcademicUnitController` |
| **Models** | `Program`, `AcademicUnit` |
| **Routes** | `programs.*`, `units.*` |

### Program store fix pattern

Update only within institution:

```php
Program::where('institution_id', $institutionId)->findOrFail($id);
```

---

## M28: Settings

| Item | Value |
|------|-------|
| **Controller** | `SettingsController` |
| **Storage** | `institution_settings` keys: `attendance_locked`, `exams_locked`, `grading_scale`, `block_reports_on_debt`, `active_periods` |
| **Permission** | `setting.manage` or admin roles |

---

## M29: SMS Templates

| Item | Value |
|------|-------|
| **Model** | `SmsTemplate` — `event_key`, `body`, `available_tags` |
| **Seeder** | `SmsTemplateSeeder` — global templates |
| **Controller** | `SmsTemplateController` via configuration |

Institution can override template per `event_key`.

---

## M30: Staff, Staff Attendance, Staff Leave

| Module | Controller | Model |
|--------|------------|-------|
| Staff | `StaffController` | `Staff` → `User` |
| Staff attendance | `StaffAttendanceController` | `StaffAttendance` |
| Staff leave | `StaffLeaveController` | `StaffLeave` |

### Staff leave scoping

All find/update operations must use `where('institution_id', $institutionId)`.

---

## M31: Student Attendance

| Item | Value |
|------|-------|
| **Web** | `StudentAttendanceController`, `AttendanceReportController` |
| **Hardware** | `AttendanceApiController` |
| **Model** | `StudentAttendance` — `check_in`, `check_out`, `status`, `institution_id` |

### Hardware UID lookup (correct pattern)

```php
Student::where(function ($q) use ($uids) {
    $q->whereIn('nfc_tag_uid', $uids)
      ->orWhereIn('rfid_uid', $uids)
      // ...
})->first();
```

Never ungrouped `whereIn()->orWhereIn()` on tenant tables.

### Hardware auth

`HARDWARE_SECRET` required; `hash_equals`; `getTodayScans` requires `X-Institution-Id` for hardware mode.

---

## M32: Student Enrollments & Promotion & Transfers

| Module | Controller |
|--------|------------|
| Enrollments | `StudentEnrollmentController` |
| University | `UniversityEnrollmentController` |
| Promotion | `StudentPromotionController` |
| Transfers | `TransferController` |

---

## M33: Student Requests

| Item | Value |
|------|-------|
| **Controller** | `StudentRequestController` |
| **Model** | `StudentRequest` |
| **Notifications** | submit + status update |
| **Destroy** | Institution scope + owner/admin check |

Teachers blocked from index (query `whereRaw('1=0')`).

---

## M34: Students

| Item | Value |
|------|-------|
| **Controller** | `StudentController` |
| **Model** | `Student` — hardware fields, `user_id`, `parent_id` |
| **Module slug** | `students` |

Central hub model — most modules reference `student_id`.

---

## M35: Subjects

| Item | Value |
|------|-------|
| **Controller** | `SubjectController` |
| **Model** | `Subject` |
| **University** | May link to department / academic unit |

---

## M36: Timetables

| Item | Value |
|------|-------|
| **Controller** | `TimetableController` |
| **Model** | `Timetable` — `day_of_week`, `start_time`, `class_section_id`, `teacher_id` |
| **Pitfall** | Do not mass-assign `institution_id` from `$request->all()` on update |

---

## M37: User Profile

| Item | Value |
|------|-------|
| **Web** | `UserProfileController` |
| **API** | `UserProfileApiController` |
| **Model** | `User` — `institute_id`, `fcm_token`, `language` |

---

# API MODULE MAP (Summary)

| Prefix | Controller | Auth |
|--------|------------|------|
| `/v1/login` | `AuthApiController` | Public |
| `/v1/profile/*` | `UserProfileApiController` | Sanctum |
| `/v1/hardware/*` | `AttendanceApiController` | Hardware secret |
| `/v1/pickup/*` | `AppPickupController` | Sanctum |
| `/v1/student/*` | `StudentPortalApiController` | Sanctum |
| `/v1/chatbot/*` | Chatbot controllers | Webhook public / QR Sanctum |

Full request/response examples: **REST API Manual** PDF.

---

# ADDING A NEW MODULE — CHECKLIST

1. Migration with `institution_id` FK indexed  
2. Model + `$fillable` without unsafe fields  
3. `ResourcePolicy` + register  
4. Entry in `RolePermissionSeeder` `$modulesData`  
5. `Module` record slug matches route middleware  
6. Controller extends `BaseController`, scopes queries  
7. Routes inside `CheckModuleAccess:slug` group  
8. Lang files `resources/lang/en/{domain}.php` + `fr`  
9. Sidebar entry (if not auto-discovered)  
10. Optional: `SmsTemplateSeeder` event + `InAppNotificationService` method  
11. Feature tests for institution isolation  

---

# ENVIRONMENT & DEPLOYMENT

| Variable | Module impact |
|----------|---------------|
| `HARDWARE_SECRET` | M31 hardware attendance |
| `APP_URL` | Must be `https://e-digitex.com` in production — webhooks, QR links, asset URLs (single URL for all schools) |
| `SANCTUM_STATEFUL_DOMAINS` | SPA auth |
| SMS env fallbacks | M09 when DB provider = `system` |
| `storage/app/firebase-credentials.json` | FCM push on pickup approve |

### Production commands

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan queue:work
```

### Production URL (multi-tenant)

- Set `APP_URL=https://e-digitex.com` (no trailing path).
- **All institutions share one login URL:** `https://e-digitex.com/login`
- Tenant context comes from the user account (`institute_id`, head-officer pivot) and session `active_institution_id` — not from DNS subdomains.
- Chatbot webhooks: `https://e-digitex.com/api/v1/chatbot/webhook/{provider}`
- Mobile app and hardware API base: `https://e-digitex.com/api/v1/...`

---

# TESTING RECOMMENDATIONS

| Area | Test |
|------|------|
| Finance | User from institution A cannot view invoice from B |
| Pickup | Guardian cannot OTP unrelated student |
| Hardware | Missing secret returns 503 |
| Roles | Non-admin cannot assign permissions |
| Notifications | Disabled system channel skips `InAppNotification` row |

---

# MODULE DEPENDENCY GRAPH (Developer View)

```
Institution
 ├── InstitutionSetting (config, modules, notify_*)
 ├── Subscription → Package.modules
 ├── AcademicSession (is_current)
 │    ├── FeeStructure → Invoice → Payment
 │    ├── Exam → ExamRecord
 │    └── StudentEnrollment → ClassSection → GradeLevel
 ├── Student (+ StudentParent)
 │    ├── StudentAttendance ← hardware UID
 │    ├── StudentPickup
 │    └── StudentRequest
 └── Staff (+ User + Role)
      ├── Timetable.teacher_id
      ├── StaffLeave
      └── Payroll
```

---

*End of Developer Manual — Regenerate PDF: `php artisan docs:generate-pdf`*
