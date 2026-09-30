# DigiteX Voting — Client Briefing

**Operational status, step-by-step testing guide, and gap analysis vs the September 2026 Functional Specification**

**Integrale Plus / DigiteX**  
**Audience:** School and university partners evaluating or testing the Elections and Voting module  
**Date:** September 2026  
**Related document:** DigiteX Voting Functional Specification (19 September 2026)

---

## 1. Purpose of this document

You asked for:

1. A **detailed, step-by-step** explanation of the voting process (candidates, voters, voting, authentication, statistics, results).  
2. Clarity on **what is already operational** and **what is still under development**.  
3. Enough detail to **test the module confidently** before school or university deployment.

This document answers those points honestly.

**Important summary**

| Item | Reality today |
| --- | --- |
| DigiteX Voting **Version 1** | Available and testable for **simple school elections** inside DigiteX |
| September 2026 Functional Spec (cycles, multi-electorate, standalone voters, ballot secrecy, turnout dashboards) | Describes **Version 2** — largely **not yet built** |
| User manual section on Elections | Intentionally short because Version 1 is a **basic** module |

Version 1 is useful for student council–style elections. It is **not yet** the full university multi-election cycle product described in the specification.

---

## 2. How Version 1 is structured (what exists in DigiteX today)

```
One Election (e.g. Student Council 2026)
   ├── Position 1 (e.g. President)
   │      └── Candidates (must be DigiteX students)
   ├── Position 2 (e.g. Vice-President)
   │      └── Candidates (must be DigiteX students)
   └── Votes (one vote per student per position)
```

There is **no Electoral Cycle** layer yet.  
There is **no separate voter register**. Eligible voters are DigiteX students of the same school who can log in.

---

## 3. Feature status matrix (Operational vs Under development)

### 3.1 Administration

| Feature | Status | Comment |
| --- | --- | --- |
| List elections | **Operational** | Elections menu (module must be enabled) |
| Create election (title, dates, academic session) | **Operational** | Starts as draft |
| Add positions (President, Delegate, etc.) | **Operational** | Several positions per election |
| Add candidates from DigiteX students | **Operational** | By admission number |
| Remove candidate | **Operational** | |
| Publish election (open for student voting) | **Operational** | Status becomes published |
| Close election | **Operational** | Status becomes completed |
| Electoral Cycle (campaign containing several elections) | **Under development** | Spec Priority 1 — not in Version 1 |
| Independent open/close/publish per election inside a cycle | **Under development** | |
| Eligibility rules (class, faculty, department, year group) | **Under development** | Today: all school students can see a published election |
| Independent candidates (not DigiteX students) | **Under development** | Spec dual registration mode |
| Independent voters / CSV import | **Under development** | |
| Voting-only school (no full DigiteX student DB) | **Under development** | Standalone mode |
| SMS / WhatsApp to candidates or parents | **Under development** | Spec Priority 2 |
| Dedicated admin roles (registrar vs observer) | **Partial** | Uses DigiteX permissions; not election-specific roles |

### 3.2 Voting and authentication

| Feature | Status | Comment |
| --- | --- | --- |
| Student login → My Elections | **Operational** | Main voting path in Version 1 |
| Ballot with candidate name and photo | **Operational** | Photo from student profile |
| Confirm before casting vote | **Operational** | |
| One vote per student per position | **Operational** | Database unique constraint + lock |
| Block double vote after refresh / double-click | **Operational** | |
| Participation receipt without naming the choice to third parties | **Partial** | Student sees own confirmation; no parent SMS receipt yet |
| QR / NFC identify API | **Partial** | Backend endpoints exist; no complete kiosk screen for schools |
| Voter ID / PIN / second factor after scan | **Under development** | Spec requirement |
| Separate authentication for standalone voters | **Under development** | |

### 3.3 Statistics, secrecy, publication

| Feature | Status | Comment |
| --- | --- | --- |
| Count of candidates on election page | **Operational** | Basic |
| Live turnout (registered / voted / not voted / %) | **Under development** | Spec Priority 1 |
| Results by candidate (votes and %) | **Under development** | No proper results publication screen yet |
| Publish results only after close, by role | **Under development** | |
| Exportable results report | **Under development** | Spec Priority 2 |
| Ballot secrecy (know who voted, not for whom) | **Not met in Version 1** | Votes currently link voter and candidate — must be redesigned in Version 2 |
| Admin audit log (open / close / publish) | **Under development** | |
| Notifications after publication | **Under development** | |

---

## 4. Mapping to your September 2026 specification

| Spec section | DigiteX today |
| --- | --- |
| 2. Cycle → Elections → Positions | Only Elections → Positions |
| 3.1 Create cycle and elections | Create election only |
| 3.2 Candidate registration (DigiteX or independent) | DigiteX students only |
| 3.3 Voter registration / import / filters | Not available (all school students) |
| 3.4 Voting interface | Available for logged-in students |
| 3.5 Authentication (ID, QR, NFC + extra check) | Login yes; QR/NFC partial; extra check no |
| 3.6 Statistics and publication | Not ready for production reporting |
| 5. Confidentiality / secrecy | Version 1 does not meet full secrecy design |
| Priority 1 | Partially covered (basic multi-position election + one vote) |
| Priority 2 | Mostly not started |

---

## 5. Step-by-step: operate and test Version 1

Use this checklist on staging (for example account.digitexvx.com) or a pilot school before live deployment.

### 5.1 Prerequisites

1. Institution (school) exists in DigiteX.  
2. Module **Elections** is activated for that school.  
3. Current **Academic Session** exists.  
4. Students exist and are enrolled.  
5. Each test voter has a **student user account** (can log in).  
6. Candidate students ideally have a **profile photo**.

### 5.2 Admin — create the election

1. Log in as **School Admin** (or a role with election permissions).  
2. Open **Elections**.  
3. Click create / add election.  
4. Enter:
   - Title (example: Student Council Elections 2026)
   - Start date and time
   - End date and time (must be after start)
   - Academic session
   - Optional description  
5. Save. Status is **draft**.

### 5.3 Admin — add positions

1. Open the election detail page.  
2. Click **Add position**.  
3. Create at least two positions for a realistic test, for example:
   - President  
   - Vice-President  
4. Positions appear as sections of the ballot.

### 5.4 Admin — register candidates

1. On a position, click **Add candidate**.  
2. Enter the student’s **admission number** (matricule).  
3. Confirm the student is found and appears in the list.  
4. Repeat for each candidate on each position.  
5. Remove a candidate with the delete action if you made a mistake.

**Limitation:** you cannot add a candidate who is not already a DigiteX student.

### 5.5 Admin — publish (open voting)

1. Check dates: “now” must be between start and end.  
2. Click **Publish**.  
3. Status becomes **published**.  
4. Students can now see the election under **My Elections**.

### 5.6 Student — vote

1. Log in as a student user.  
2. Open **My Elections**.  
3. Select the published election.  
4. For each position:
   - Review candidate names and photos  
   - Click vote  
   - Confirm in the dialog  
5. After voting on a position, that choice is locked (cannot vote again for the same position).  
6. Complete all positions.

### 5.7 Admin — close

1. When the voting period ends (or you want to stop voting), open the election.  
2. Click **Close**.  
3. Status becomes **completed**.  
4. Students should no longer cast new votes for that election.

### 5.8 What Version 1 does **not** include in this flow

- Selecting voters by class or faculty  
- Importing an external voter list  
- A live turnout dashboard  
- An official results screen with percentages and export  
- Proof that administrators cannot see who voted for whom (secrecy redesign)  
- A finished NFC / QR voting booth experience for the school hall  

---

## 6. Essential test cases from your specification

| Your test case | Possible on Version 1? | How / why |
| --- | --- | --- |
| School using only Voting, no DigiteX student DB | **No** | Standalone mode not built |
| School already on DigiteX reuses students | **Yes** | Candidates by admission number; voters = student logins |
| University student votes President + Faculty + Year Delegate with different eligibility | **No** | No cycle and no per-election electorate filters |
| Cannot vote twice (refresh, double-click, network retry) | **Yes** | Test with one student on one position |
| Close one election while another in the same cycle stays open | **No** | No cycle; create two separate elections as a weak workaround only |
| Admin sees participation without linking voter to choice | **No** | Secrecy model not implemented |

---

## 7. Recommended demonstration with Version 1 (what we can show now)

Until Version 2 is delivered, a fair demonstration is:

1. Create **Student Council Elections 2026**.  
2. Add positions: **President** and **Vice-President**.  
3. Register 2–3 DigiteX students as candidates per position.  
4. Publish within an active date window.  
5. Have 3–5 student accounts vote.  
6. Prove a second vote on the same position is rejected.  
7. Close the election.

This proves Version 1 for **simple school elections**.  
It does **not** yet prove the university multi-election cycle demo described in the specification (three elections with different eligible groups). That requires Version 2 development.

---

## 8. Proposed Version 2 roadmap (aligned with your Priority 1 and 2)

### Priority 1 — required for confident school / university deployment

1. **Electoral Cycle** containing multiple elections.  
2. **Eligibility** per election (all students, class/year, faculty/department, or imported list).  
3. **Dual registration** (DigiteX database or independent candidates/voters).  
4. **Ballot secrecy** (participation record separate from ballot).  
5. **Turnout monitoring** and **results publication** with role rules.  
6. Independent open / close / publish per election.

### Priority 2 — after Priority 1

1. Finished QR / NFC booth flow with anti-impersonation check.  
2. Notifications (SMS / WhatsApp) where configured.  
3. Advanced exports and dispute / lost-card procedures.  
4. Standalone Voting subscription packaging.

Security and ballot secrecy must be treated as **mandatory** in the first production-ready Version 2 release, as stated in your specification.

---

## 9. Practical recommendation for schools today

| Situation | Recommendation |
| --- | --- |
| Primary / secondary student council, all students eligible, DigiteX already used | You may **pilot Version 1** with training using Section 5 of this document |
| University faculty / department / year-group elections | **Wait for Version 2** (eligibility + cycle) |
| Institution that wants Voting only, no full DigiteX SIS | **Wait for Version 2** (standalone registration) |
| Need official published results and secrecy guarantees | **Wait for Version 2** |

---

## 10. Next steps and your feedback

Please reply with your preference:

- [ ] Proceed with a **Version 1 pilot** in one school (simple council election), using this guide  
- [ ] Prioritise **Version 2 Priority 1** development before any live election  
- [ ] Both: short Version 1 pilot for training **and** Version 2 build plan with dates  
- [ ] Need a **French** version of this briefing for local teams  

**School / organisation:** ________________________________  
**Contact name:** ________________________________  
**Date:** ________________________________  

**Comments:**

________________________________________________________________

________________________________________________________________

________________________________________________________________

---

## 11. Contact

Integrale Plus / DigiteX project team — please return this document with Section 10 completed so we can plan the demonstration and development sequence with you.

---

*This briefing describes the DigiteX codebase as of September 2026. It does not promise Version 2 delivery dates until a written scope and schedule are agreed.*
