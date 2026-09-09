# Staff Timetable Help Manual

**Digitex / Integrale Plus** — School Admin, Head Officer, and Teachers.

This guide explains how to configure a **staff (teacher) weekly timetable** in Digitex: prerequisites, creating slots, viewing a teacher’s schedule, printing, and common errors.

> In Digitex, a **staff timetable** means the **teacher’s teaching schedule** (class, subject, day, and time). There is **no separate duty/shift roster** for non-teaching staff (guards, office, drivers).

---

## 1. What this module does

The Timetables module builds the weekly school routine:

| Field | Example |
| --- | --- |
| Day | Monday |
| Time | 08:00–09:00 |
| Class | Grade 5 — Section A |
| Subject | Mathematics |
| Teacher | Mr. Dupont (auto-filled) |
| Room | Room 12 |

Teachers and students use the same timetable data. Teachers see only their own slots; students see their class routine.

Menu path: **Academics → Class Courses → Timetables**  
(also **Timetables** under Class Courses in the sidebar).

---

## 2. Who does what

| Role | What they do |
| --- | --- |
| School Admin / Head Officer | Create, edit, delete, print, and filter routines |
| Teacher | View own timetable (web and mobile “Today’s Timetable”) |
| Student / Guardian | View class routine (own class only) |
| Super Admin | Enable the **Timetables** and **Class Courses** modules in the school package |

Required permissions (typical School Admin):

- `timetable.view`
- `timetable.create`
- `timetable.update`
- `timetable.delete` (optional)

The school subscription must include the **timetables** module.

---

## 3. Prerequisites (do these first)

Select the school with the building icon in the header, then complete:

1. **Academic Session** — create the year and mark it as **current**.
2. **Grade Levels** — e.g. Grade 5, Grade 6.
3. **Class Sections** — e.g. Grade 5 — A.
4. **Subjects** — e.g. Mathematics, French.
5. **Staff** — create teaching staff with the Teacher role and linked user accounts.
6. **Class Courses** (Class Subjects) — assign each subject to a class **and** assign a teacher.

### Why Class Courses matters

On the timetable form, the **Teacher** field is **read-only**. Digitex fills it from the Class Courses assignment for that class + subject.

If you see:

> No teacher assigned to this subject. Please assign a teacher in "Class Courses" first.

Open **Class Courses**, assign the teacher, then return to Timetables.

---

## 4. Create a staff timetable slot

1. Open **Timetables**.
2. Click **Add Routine** (Create).
3. Fill the form:

| Field | Action |
| --- | --- |
| Grade | Select the grade |
| Class | Select the class section |
| Day | Choose Mon–Sun |
| Subject | Select a subject already allocated to that class |
| Teacher | Auto-filled — do not invent a different teacher |
| Room | Optional room number |
| Start time | e.g. 08:00 |
| End time | e.g. 09:00 |

4. Use the right-hand panel (**Reserved Slots** / **Available Slots**) to avoid clashes.
5. Click **Save Routine**.

Repeat for every period of the week.

### Example week excerpt (Grade 5A)

| Day | Time | Subject | Teacher | Room |
| --- | --- | --- | --- | --- |
| Monday | 08:00–09:00 | Mathematics | Mr. Dupont | Room 12 |
| Monday | 09:00–10:00 | French | Mrs. Traoré | Room 12 |
| Tuesday | 08:00–09:00 | Mathematics | Mr. Dupont | Room 12 |

---

## 5. Edit, delete, and bulk actions

- **Edit:** Timetable list → pencil icon → change day/time/room (or subject if still allocated) → **Update Routine**.
- **Delete:** Trash icon, or select several rows → **Bulk Delete**.
- After any change, ask teachers to refresh their timetable view.

---

## 6. View a staff member’s schedule

### School Admin

1. Open **Timetables**.
2. Filter by grade and/or class.
3. Use the **Teacher** column to find a staff member’s slots.
4. Click **View Routine** for a weekly calendar-style view of the filtered class.

There is no separate “staff profile timetable” page. Filter the list by class, or ask the teacher to log in and open Timetables (they only see their own rows).

### Teacher login

- Sidebar **Timetables** shows only slots where `teacher_id` is that staff record.
- Dashboard / mobile: **Today’s Timetable**.

### Print / PDF

Use **Print Routine** or **Download PDF** from the timetable screens to post the schedule on the classroom door or staff notice board.

---

## 7. Clash rules Digitex enforces

When saving a slot, the system can reject conflicts:

| Message | Meaning | What to do |
| --- | --- | --- |
| Teacher is already booked | Same teacher overlaps another class at that time | Change time or assign another teacher in Class Courses |
| Class already has a lecture | Class overlaps another subject at that time | Change time or day |
| Room is occupied | Same room used by another class | Pick another room |
| End time must be after start | Invalid times | Fix start/end |
| No active academic session | No current session | Mark a session as current |

Use **Available Slots** suggestions on the create form when possible.

---

## 8. School hours used by suggestions

Timetable availability suggestions use school hours from **Configuration → School Year**:

- School start time (e.g. 08:00)
- School end time (e.g. 15:00)

Set these before building a full week so suggested free slots stay inside school hours.

Room count (same Configuration tab) drives the room number list used in the form.

---

## 9. Recommended build order for a new school

1. Current academic session  
2. Grades and classes  
3. Subjects  
4. Teaching staff  
5. **Class Courses** — class + subject + teacher  
6. **Timetables** — one slot at a time (or class by class)  
7. Print each class routine and share with teachers  
8. Teacher login check: Today’s Timetable looks correct  

Do not import students or start attendance/marks testing until Class Courses and Timetables are consistent.

---

## 10. FAQ

**Q: Can I pick any teacher on the timetable form?**  
A: No. Assign the teacher in **Class Courses** first. The timetable form only displays that assignment.

**Q: Can I schedule non-teaching staff (accountant, guard)?**  
A: No. Timetables are for teaching periods only. Duty/shift rosters are not part of this module.

**Q: Why does a teacher see nothing?**  
A: They have no `teacher_id` slots, or their staff profile is not linked to their user. Check Class Courses and Timetables rows for that staff ID.

**Q: Do university/LMD schools use the same screen?**  
A: Yes for weekly slots. Subjects may come from programs/academic units, but the Timetables create flow is the same once Class Courses (allocations) exist.

**Q: Does changing Class Courses update existing timetable rows?**  
A: Existing timetable rows keep their saved `teacher_id` until you edit or recreate them. After reassigning a teacher in Class Courses, review and update affected timetable slots.

---

## 11. Quick checklist

- [ ] School selected in header  
- [ ] Current academic session active  
- [ ] Grades, classes, subjects created  
- [ ] Teachers created and active  
- [ ] Class Courses: each subject has a teacher  
- [ ] Timetables module enabled  
- [ ] At least one full class week entered without clash errors  
- [ ] Teacher login shows correct Today’s Timetable  
- [ ] Class routine printed for notice board  

---

## Related manuals

- User Manual — Module C7: Timetables  
- Mobile App User Manual — Today’s Timetable  
- Go-live checklist — Academics section  
