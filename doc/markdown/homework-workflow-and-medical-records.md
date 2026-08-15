# Homework workflow, WhatsApp alerts & infirmary records

Status: **Implemented** (August 2026)

Three school-requested features, all optional per school:

1. Homework approval before publishing.
2. WhatsApp-first parent alert when homework is published.
3. Student medical / infirmary record.

The proportional fee allocation model shipped alongside these; see
`doc/markdown/developer-manual.md` (Finance) for that feature.

---

## 1. Homework approval workflow

### Behaviour

- **Off (default):** a teacher saves homework and it is immediately visible to
  parents, students and the WhatsApp chatbot — unchanged from before.
- **On:** homework is saved with status `pending` and stays invisible until an
  approver publishes it. The teacher sees a notice on the create form and gets
  an in-app notification when the decision is made.
- A user who could approve anyway (e.g. the Director) publishes directly instead
  of queuing their own homework for review.

### Configuration

Configuration → **Homework** tab (`POST configuration/homework/settings`):

| Setting key | Meaning |
|---|---|
| `homework_approval_required` | `1` / `0` — hold homework for review |
| `homework_approver_roles` | JSON array of role names allowed to approve |

Stored in `institution_settings` under the `academics` group, so the choice is
per school. Selected roles must also hold the `assignment.approve` permission.
Super Admin and School Admin always keep the authority so a school cannot lock
itself out of its own approval queue; with no selection, School Admin and Head
Officer are the approvers.

### Schema (`assignments`)

| Column | Notes |
|---|---|
| `status` | `pending` / `approved` / `rejected`, defaults to `approved` |
| `submitted_at` | when the teacher saved it |
| `approved_by`, `approved_at` | decision trail |
| `rejection_reason` | shown to the teacher |
| `published_at` | set on approval; backfilled from `created_at` for old rows |
| `parents_notified_at` | guards against double notification |

Existing homework was backfilled as published, so enabling the feature never
hides content that parents could already see.

### Code

| Concern | Location |
|---|---|
| Policy + settings | `app/Services/Academic/HomeworkApprovalService.php` |
| Approve / reject | `AssignmentController@updateStatus` → `POST assignments/{assignment}/status` |
| Visibility scope | `Assignment::scopePublished()` |
| Read paths gated | `AssignmentController@index` (student view), `Api/V1/StudentPortalApiController@getHomework`, `Api/V1/Chatbot/HomeworkController@getLatestHomework`, `ChatbotLogicService@getHomework` |
| Queue UI | `resources/views/assignments/index.blade.php` (status column, filter, approve/reject) |

---

## 2. WhatsApp-first homework notification

When homework becomes published — on save, or on approval — every guardian of
the class section is messaged. WhatsApp is tried first; SMS is the fallback when
WhatsApp is disabled or the send fails; email goes out too when the school
enables that channel. Linked parent and student accounts also get an in-app
notification.

Message (event key `homework_published`, editable per school under SMS
Templates):

> New homework for $StudentName ($ClassName). Subject: $Subject. Title: $Title.
> Due $Deadline. To see the details or ask a question, use our WhatsApp chatbot.
> — $SchoolName

Available tags: `$ParentName, $StudentName, $Title, $Subject, $ClassName,
$Deadline, $SchoolName`.

Defaults ship as WhatsApp + in-app only (global `notify_homework_published`), so
schools are not charged for SMS unless they opt in under Configuration →
Notifications.

| Concern | Location |
|---|---|
| Fan-out | `app/Services/Academic/HomeworkNotificationService.php` |
| Queued dispatch | `app/Jobs/NotifyHomeworkPublishedJob.php` (dispatched after response) |
| Recipients | active enrollments of the class section, falling back to `students.class_section_id` |
| Dedupe | one message per phone, per email, per user account |

Sends are logged in `message_logs` against the assignment, so a failed delivery
is visible with its reason (e.g. insufficient credits).

---

## 3. Student medical / infirmary record

Scope is deliberately small: what the school nurse needs to help a student
safely, plus a visit history. Not a hospital system.

### Data

`student_medical_profiles` (one per student): blood group, allergies, chronic
conditions, current medication, important notes from the parent, family doctor,
insurance, emergency contact (name / relation / two phones), first-aid consent,
and the date the parent last confirmed the information.

`infirmary_visits` (many per student): date and time, reason, observation,
action taken, temperature, blood pressure, outcome (returned to class, rested,
sent home, referred to hospital, other), whether the family was informed, and
who recorded it.

Blood group stays in sync with `students.blood_group`, so the student profile and
the infirmary never disagree.

The emergency contact falls back to the guardian on file (respecting
`primary_guardian`) when the nurse has not entered a dedicated contact.

### Access control

- Module `medical_records` — Super Admin enables it per school in
  Configuration → Modules, like every other module.
- Permissions `medical_record.view|viewAny|create|update|delete`.
- A **Nurse** role is created per school with only the medical permissions plus
  `student.view`, so a nurse can find a child and nothing else.
- School Admin and Head Officer also receive the permissions.
- Parents and students have no access to this area.

### Audit

Every create, update **and read** is written to the audit log under module
`MedicalRecord`. Health details are never copied into the log: updates record
only the list of field names that changed, and reads record the student id and
context. This gives a school "who looked at this record?" without turning the
audit table into a second copy of the medical data.

### Screens

| Screen | Route |
|---|---|
| Infirmary log + student search + counters | `GET medical-records` |
| Student record (critical alert banner, essentials, visit history) | `GET medical-records/{student}` |
| Edit record | `GET/PUT medical-records/{student}` |
| Record a visit | `GET/POST medical-records/visits` |

Allergies, chronic conditions and current medication are surfaced as a red
banner at the top of the record and on the visit form, so the nurse sees them
before assisting. The student profile links to the record for users who hold the
permission.

Sidebar: Students → Student welfare → **Infirmary**.
Localisation: `resources/lang/{en,fr}/medical.php`.
