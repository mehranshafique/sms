# Re-enrollment Confirmation Help Manual

**Digitex / Integrale Plus** — school administrators, secretaries, and parents.

This guide explains how a school collects **parent confirmation** before placing a student in the next academic session. It is **not** the same as bulk Student Promotion.

---

## 1. What this module does

At year end the school must know which families will return. Digitex opens a **re-enrollment campaign**. Every active student in the current session is listed. The parent answers **yes** or **no**. The school then reviews the file (results, attendance, fees, discipline) and **approves** one student at a time into a class for the next session.

**This is not bulk promotion.** Promotion (People → Student Promotion) still exists for moving a whole class. When a re-enrollment campaign is open, promotion should only place students who are already **confirmed**.

**This is not pre-enrollment.** Pre-enrollment is for **new** candidates. Re-enrollment is for students who already study at the school.

---

## 2. Who does what

| Role | What they do |
| --- | --- |
| School Admin / secretary | Open the campaign, invite parents, record office visits, review files, approve or reject |
| Parent | Confirm or decline on WhatsApp (menu option **10**) or at the school office |
| Super Admin | Enable SMS/WhatsApp events under Configuration → Notifications if invitations do not send |

Menu path in Digitex: **People → Re-enrollment Confirmations** (wording may follow your language).

---

## 3. Statuses (what each word means)

| Status | Meaning |
| --- | --- |
| Pending (awaiting parent) | Campaign is open; the family has not answered |
| Partial confirmation | Parent said yes, but the minimum confirmation fee is not fully paid |
| Pending review | Parent said yes and the fee rule is met — waiting for school decision |
| Re-enrollment confirmed | School approved; student is enrolled in the next session/class |
| Declined by parent | Parent said they will not return |
| Rejected | School refused the file |
| Expired | Campaign closed while the parent never answered |

The **Pending review** list is the secretary's daily queue.

---

## 4. School workflow (step by step)

### Step 1 — Create the next academic session first

Create the **next** year (for example 2026-2027) under Academic Sessions **before** opening a campaign. Create the class sections you will assign into (or create them before you approve students).

### Step 2 — Open a campaign

1. Open **Re-enrollment Confirmations**.
2. Click **Open Re-enrollment Campaign**.
3. Fill:
   - **Name** — example: Re-enrollment 2026-2027
   - **From session** — current year
   - **To session** — next year
   - **Minimum confirmation fee** — 0 if you only need a yes/no. Otherwise the amount the family must have paid (from invoices/payments Digitex already counts) before the file enters full review
   - **Opens on / Closes on** — optional dates
4. Click **Create & Open Campaign**.

Digitex immediately creates one row per active student enrolled in the **from** session.

You can have only **one campaign per target session**.

### Step 3 — Invite parents

Click **Invite parents**. Digitex sends SMS and/or WhatsApp using the `reenrollment_invitation` template (Configuration → Notifications must have SMS or WhatsApp on for that event).

Parents who already answered are not contacted again. Use **Send reminders** later for families still on **Pending**. Reminders skip anyone reminded in the last 24 hours.

If you see a warning that messaging is switched off, enable the re-enrollment events under Configuration → Notifications.

### Step 4 — Parent answers (WhatsApp or office)

**WhatsApp (logged-in parent menu):**

1. Parent messages the school WhatsApp number and opens the parent portal menu.
2. Send **10** — Confirm Re-Enrollment / Confirmer la réinscription.
3. Digitex shows current class, next session, fee required, amount counted as paid.
4. Send **1** to confirm or **2** to decline.

**At the office:**

1. Open the student row → **Review**.
2. Use **Record physical confirmation** or **Record physical decline**.
3. Optionally type a parent note.

If several children share one parent WhatsApp, Digitex treats each child as a separate confirmation.

### Step 5 — Review the file

Open **Review** on a student in **Pending review** or **Partial confirmation**. You will see:

- Identity, years in school, current class, proposed next class
- Exam average / annual status
- Fees paid and outstanding (current session and older debts)
- Re-enrollment minimum vs amount counted as paid
- Attendance (present / absent / late / excused)
- Discipline / conduct

You may save a **proposed next class** before the final decision.

### Step 6 — Decide

| Button | Result |
| --- | --- |
| Approve Re-enrollment | Creates (or updates) the student's enrollment in the **to** session in the class you select. Previous enrollment is marked promoted. Parent is notified (`reenrollment_confirmed`). |
| Keep Pending | Stay in the queue (for example waiting for a missing document). |
| Reject | Parent is notified (`reenrollment_rejected`). Student is not placed in the next session. |
| Reopen for parent | Use after a decline/reject if the family changed their mind. Status returns to awaiting parent. You cannot reopen a fully confirmed student from this button. |

If the minimum fee is not met, Approve is blocked unless you tick **Approve even if minimum fee is not met**.

### Step 7 — Students who enroll after the campaign opened

Click **Sync students**. New active enrollments in the **from** session are added as pending rows. Then invite those families.

### Step 8 — Close the campaign

Click **Close campaign** when the window is over. Parents can no longer confirm on WhatsApp. Remaining **Pending** rows become **Expired**. You can **Reopen campaign** if needed; expired rows return to pending.

---

## 5. Payments and the minimum fee

Digitex does not create a special "re-enrollment invoice" by itself. It **counts payments already recorded** for the student against the campaign minimum.

- Minimum **0** — a parent yes goes straight to **Pending review**.
- Minimum greater than 0 — a parent yes with unpaid remainder stays **Partial confirmation** until enough is paid; then it moves to **Pending review** automatically when you open the review screen (payment is refreshed).

Outstanding tuition for the current year is shown for judgement. It is **not** the same figure as the re-enrollment minimum unless you chose it that way.

---

## 6. Messages parents may receive

These events must be enabled per school (Configuration → Notifications):

| Event | When it is sent |
| --- | --- |
| `reenrollment_invitation` | Invite parents |
| `reenrollment_reminder` | Send reminders / single reminder on a row |
| `reenrollment_confirmation_received` | Parent confirmed and fee is met |
| `reenrollment_partial_confirmation` | Parent confirmed but fee still short |
| `reenrollment_confirmed` | School approved |
| `reenrollment_declined` | Parent declined |
| `reenrollment_rejected` | School rejected |

Edit the wording under Configuration → SMS / WhatsApp templates if you want French-only school language.

WhatsApp may add a short official wrapper around the school text when Meta requires an approved template (outside the 24-hour chat window). That wrapper is configured in Infobip, not in the Digitex re-enrollment template body.

---

## 7. Typical problems

| Problem | What to check |
| --- | --- |
| Invite button does nothing useful / nobody to notify | Everyone already invited or already answered; or messaging events are off |
| Parent WhatsApp has no option 10 | Parent is not logged in to the parent portal on WhatsApp; send the usual chatbot login first |
| Parent hears / sees "no open campaign" | Campaign is closed, wrong school WhatsApp number, or student has no row (run Sync students) |
| Stuck on Partial confirmation | Collect the remaining minimum fee, then open Review so payment is refreshed |
| Cannot approve | File is not in the review queue, or fee not met and override not ticked, or next class not selected |
| Student missing from the list | Not enrolled as active in the **from** session, or student status is not active — use Sync after fixing enrollment |
| Bulk promotion listed the wrong children | Finish re-enrollment approvals first; promotion is a separate bulk tool |

---

## 8. Suggested calendar (example)

1. March — create next academic session and next-year classes.
2. April — open campaign, set a confirmation fee if the board requires a deposit, invite parents.
3. April–June — office confirmations + WhatsApp; weekly reminders.
4. Review files as they enter Pending review; approve into next-year classes.
5. After deadline — close campaign; handle remaining Expired / Declined cases by hand.
6. Only then run bulk promotion for any leftover policy the school still uses.

---

## 9. Quick test (one family)

1. Open a campaign from current session → next session, minimum fee 0.
2. Confirm the test student appears as Pending.
3. From that parent's WhatsApp, login, send **10**, then **1**.
4. Digitex row becomes Pending review.
5. Open Review → assign next class → Approve.
6. Check Students / Enrollments: the child is active in the **to** session.

If step 3 fails, record a physical confirmation on the Review screen — the rest of the workflow is the same.
