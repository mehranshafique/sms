# DigiteX Voting Version 2 — Operations & Test Guide

**Integrale Plus / DigiteX**  
**Audience:** School and university partners testing Voting Priority 1  
**Date:** September 2026

---

## 1. What is operational now (Priority 1)

| Feature | Status |
| --- | --- |
| Electoral cycles containing multiple elections | **Operational** |
| Independent open / close / publish results per election | **Operational** |
| Eligibility: all students, grades, class sections, departments, manual/import | **Operational** |
| Candidates from DigiteX **or** independent (external) | **Operational** |
| Voters from DigiteX sync, manual add, or CSV import | **Operational** |
| Student ballot (one submission per election) | **Operational** |
| One participation per voter per election | **Operational** |
| **Ballot secrecy** (who voted ≠ whom they chose) | **Operational** |
| Turnout monitoring + CSV | **Operational** |
| Results after close + publish + CSV | **Operational** |
| QR/NFC identify + cast API (status aligned to `open`) | **Partial** (API only; Priority 2 kiosk UI) |
| SMS/WhatsApp notifications | **Under development** (Priority 2) |
| Standalone Voting-only school product pack | **Under development** (Priority 2) |

---

## 2. Step-by-step (admin)

1. Enable module **Voting** or **Elections** for the school.  
2. Open **Elections** (sidebar) → lands on **Electoral Cycles**.  
3. **Create Cycle** (e.g. Student Elections 2026).  
4. Inside the cycle → **Add Election** (e.g. Student President). Repeat for Faculty Representative and Class Delegate with different eligibility.  
5. Open each election → **Ballot** tab: add positions, then DigiteX or independent candidates.  
6. **Settings** tab: set eligibility; **Voters** tab → Sync from DigiteX (or import CSV).  
7. **Open voting** when ready.  
8. **Turnout** tab: see registered / voted / not voted (names only).  
9. **Close** → review **Results** → **Publish results**. Export CSV if needed.

---

## 3. Step-by-step (student)

1. Student logs in → **My Elections**.  
2. Only elections where they are on the voter roll and status is **Open** appear.  
3. Select one candidate per position → **Submit ballot** → confirm.  
4. Receipt confirms **participation only** (choices are anonymous).  
5. If results were published and visibility is enabled, results appear under Published results.

---

## 4. Spec test cases

| Test | How to verify |
| --- | --- |
| DigiteX reuses students | Sync voters; add candidates by admission number |
| Independent candidate/voter | Ballot → independent candidate; Voters → external or CSV |
| Multi-election eligibility | Same cycle: President=all, Delegate=one section; student in that section sees both |
| No double vote | Submit twice → second attempt rejected |
| Close one, leave another open | Close President; leave Delegate open |
| Secrecy | Turnout lists names; Results show counts only — no voter→candidate link |

Optional demo seed:

```bash
php artisan db:seed --class=VotingV2DemoSeeder
```

---

## 5. Deploy notes

```bash
php artisan migrate --force
php artisan optimize:clear
```

Existing Version 1 elections are wrapped in a migrated cycle; old linked votes are converted to participation + anonymous ballots (`votes_legacy_v1`).

---

*Priority 2 (kiosk UI, notifications, richer exports, standalone packaging) remains planned separately.*
