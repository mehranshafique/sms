# DigitexVx Voice — Design (WhatsApp Calling + IVR)

> **Implementation status (all phases built).** See [§16 Delivered implementation](#16-delivered-implementation)
> for the shipped menu map, settings, endpoints and code layout. Phase 1 (keypad IVR),
> Phase 2 (PIN + pickup/requests/fee details), Phase 3 (AI voice agent) and Phase 4
> (transfer to a person) are all in the codebase. Only the Infobip account enablement in
> §3 remains an external dependency.

**Status:** Implemented  
**Date:** 2026-08-13  
**Owner:** Digitex platform  
**Channel:** Infobip WhatsApp Business Calling + Calls Markup Language (CML) / IVR  
**Goal:** Bring DigitexVx back as a modern **parent-initiated WhatsApp voice IVR**, powered by the same Digitex domain logic as the text chatbot.

---

## 1. Product summary

A parent opens the school WhatsApp chat, taps **Call**, and hears:

> “Welcome to {SchoolName}. Press 1 for fees, 2 for attendance, 3 for notices, 4 for PTM, 0 to repeat, 9 for secretary.”

Digitex identifies the caller by WhatsApp MSISDN (same as chatbot), speaks a short answer via TTS, then returns to the menu or hangs up.

**In scope (Phase 1)**
- User-initiated WhatsApp calls only
- DTMF IVR menus (guest + authenticated parent)
- EN / FR TTS prompts
- Read-only answers from Digitex (fees, attendance, notices, contact, PTM status)
- Webhook logging

**Out of scope (Phase 1)**
- Business-initiated WhatsApp calls
- Bridging to PSTN / school landline (forbidden by Meta/Infobip)
- Full AI conversational agent
- Staff / director voice menus
- Video calls

---

## 2. Why this fits Digitex today

| Existing Digitex piece | Reuse in Voice Phase 1 |
|------------------------|------------------------|
| `ChatbotLogicService` menus & intents | Same option map (fees, attendance, notices, PTM) |
| WhatsApp MSISDN → parent/student identity | Same phone matching as chatbot login |
| `ChatSession` | New `voice_sessions` (or extend session with `channel=voice`) |
| Infobip outbound WhatsApp text (`InfobipService`) | Keep for SMS/WA text; voice uses Calls/CML APIs |
| `/api/v1/chatbot/webhook/infobip` | Pattern for new `/api/v1/voice/infobip/*` webhooks |
| Institution settings / notification prefs | Add `voice_ivr_enabled`, menu toggles, locale |

DigitexVx conceptually returns as: **voice keypad instead of typed digits**, same school WhatsApp number, same backend truth.

---

## 3. Infobip enablement checklist (do first)

Give this list to the Infobip account manager / support before coding.

### Account & number
- [ ] Confirm WhatsApp Business Platform sender(s) used by Digitex schools
- [ ] Enable **WhatsApp Voice / Business Calling** on those senders (portal: Channels & Numbers → sender → WhatsApp Voice)
- [ ] Confirm country coverage for the sender MSISDN(s)
- [ ] Confirm voice billing / minutes plan (separate from WA text)

### Routing
- [ ] Choose Phase-1 routing mode (recommended: **Forward to Subscription / Calls API + CML**)
- [ ] If using classic IVR scenario ID: Infobip Support associates `scenarioId` with WhatsApp sender
- [ ] Confirm inbound WhatsApp calls can reach CML webhooks on Digitex public HTTPS URL
- [ ] Confirm DTMF on WhatsApp calling keypad is supported for our account

### Credentials & security
- [ ] Infobip API key with Voice / Calls permissions (not only SMS/WA text)
- [ ] Base URL / subdomain already used by Digitex (`infobip_subdomain`)
- [ ] Webhook signing / shared secret strategy for voice callbacks
- [ ] Whitelist Digitex callback host in Infobip if required

### Acceptance ping
- [ ] Place a test WhatsApp call to the sender
- [ ] Hear TTS “Digitex voice test”
- [ ] Press `1`, Digitex webhook receives DTMF, responds with next CML actions

**Do not start full implementation until the test call reaches Digitex.**

---

## 4. Target architecture

```text
Parent WhatsApp
    │  (tap Call)
    ▼
Infobip WhatsApp Voice
    │  inbound call event
    ▼
Digitex Voice Webhooks (Laravel)
    │  identify phone → institution → parent/children
    │  build CML: say + captureDtmf
    ▼
Infobip TTS / DTMF collection
    │  parent presses digit
    ▼
Digitex DTMF callback
    │  resolve intent (fees/attendance/…)
    │  query Digitex DB/services
    ▼
Infobip say(answer) → menu again / hangup
```

### Recommended Infobip mode for Digitex
Prefer **Calls Markup Language (CML)** over Moments-only IVR:

- Digitex owns menu logic (multi-tenant, EN/FR, school-specific)
- `captureDtmf` callbacks return next actions dynamically
- Same pattern as chatbot: Digitex is the brain, Infobip is the channel

Moments IVR is fine for a static demo, but not for per-school Digitex data.

---

## 5. Digitex API surface (Phase 1)

Mirror chatbot webhook style.

| Method | Endpoint | Purpose |
|--------|----------|---------|
| `POST` | `/api/v1/voice/infobip/inbound` | Call started / answered → return welcome CML |
| `POST` | `/api/v1/voice/infobip/dtmf` | DTMF captured → return next CML |
| `POST` | `/api/v1/voice/infobip/status` | Hangup / failed / completed (async log only) |
| `GET`  | `/api/v1/voice/infobip/health` | Ops ping |

Config examples (Chatbot settings UI sibling):

```text
Inbound CML URL : https://{app}/api/v1/voice/infobip/inbound?secret=...
DTMF callback   : https://{app}/api/v1/voice/infobip/dtmf?secret=...
Status webhook  : https://{app}/api/v1/voice/infobip/status?secret=...
```

### Security
Reuse `ChatbotWebhookVerifier` patterns:
- `INFOBIP_API_KEY` / `CHATBOT_WEBHOOK_SECRET` / skip-verify for staging only
- Always validate Infobip source where possible
- Never speak sensitive balances until caller is matched to a parent record (and optional PIN later)

---

## 6. Session model

### Option A (recommended): `voice_sessions` table
Keep voice separate from chat sessions to avoid colliding mid-text flows.

Suggested columns:
- `id`, `call_id` (Infobip), `phone_number`, `institution_id`
- `locale` (`en`/`fr`)
- `state` (`WELCOME`, `GUEST_MENU`, `PARENT_MENU`, `SELECT_CHILD`, `ANSWER`, `ENDED`)
- `menu_profile` (`guest` / `parent`)
- `student_id` (selected child)
- `user_id` / `parent_id` when matched
- `last_digit`, `turns`, `started_at`, `ended_at`

### Option B: extend `chat_sessions`
Add `channel` (`whatsapp_text` | `whatsapp_voice`) and `external_call_id`.  
Possible, but riskier if parent texts and calls at once.

**Phase 1 recommendation:** Option A.

---

## 7. DTMF menu map (reuse chatbot intents)

### 7.1 Guest menu (unknown / unmatched phone)

Aligned with `ChatbotLogicService::sendGuestMenu()`:

| Key | Intent | Voice answer source |
|-----|--------|---------------------|
| `1` | Admission info | `chatbot_admission_info` setting |
| `2` | Fees info (generic) | `chatbot_fees_info` setting |
| `3` | Contact school | `chatbot_contact_info` / school phone |
| `4` | How to open parent portal | Short spoken instructions |
| `9` | Language toggle EN↔FR | Flip `locale`, re-speak menu |
| `0` | Repeat menu | Re-speak guest menu |
| `*` | Hang up | `hangup` |

Guest pre-registration (`text option 1`) is **not** in Phase-1 voice (too long for IVR). Keep it WhatsApp text.

### 7.2 Parent menu (phone matched to guardian)

Aligned with parent WhatsApp menu concepts (fees / attendance / results / PTM):

| Key | Intent | Digitex source (examples) |
|-----|--------|---------------------------|
| `1` | Fee balance (selected child) | Invoice remaining balance API/service |
| `2` | Today’s attendance | Latest attendance for child |
| `3` | Latest notice | Latest published notice title/date |
| `4` | PTM status | Latest `parent_meetings` for child |
| `5` | Switch child (if multiple) | List children, capture next digit |
| `8` | School contact | Institution contact |
| `9` | Language EN↔FR | Flip locale |
| `0` | Repeat menu | Re-speak parent menu |
| `*` | Hang up | `hangup` |

Sensitive options (full report card, pickup QR, leave requests) stay on **text chatbot / mobile app** for Phase 1.

### 7.3 Identity resolution order
1. Normalize caller MSISDN (same helper as chatbot)
2. Find `StudentParent` by father/mother/guardian phone
3. If found → parent menu + default first child
4. Else try student mobile → limited student prompts (optional Phase 1.1)
5. Else guest menu

---

## 8. CML action examples

### Welcome (inbound)

```json
{
  "actions": [
    {
      "action": "say",
      "text": "Welcome to Green Valley School. Press 1 for fees, 2 for attendance, 3 for notices, 4 for parent teacher meetings, 0 to repeat, star to hang up.",
      "language": "en",
      "voicePreferences": { "voiceGender": "FEMALE" }
    },
    {
      "action": "captureDtmf",
      "maxLength": 1,
      "timeout": 8,
      "callback": {
        "url": "https://app.digitex.example/api/v1/voice/infobip/dtmf",
        "method": "POST"
      }
    }
  ]
}
```

### After fees answer

```json
{
  "actions": [
    {
      "action": "say",
      "text": "For Room Masuaku, remaining balance is 45 dollars. Press 1 for fees again, 2 for attendance, 0 for the main menu, star to hang up.",
      "language": "en"
    },
    {
      "action": "captureDtmf",
      "maxLength": 1,
      "timeout": 8,
      "callback": {
        "url": "https://app.digitex.example/api/v1/voice/infobip/dtmf",
        "method": "POST"
      }
    }
  ]
}
```

Keep spoken amounts short. Prefer “45 dollars” over long invoice lists.

---

## 9. Digitex code layout (proposed)

```text
app/Http/Controllers/Api/V1/Voice/
  InfobipVoiceWebhookController.php

app/Services/Voice/
  VoiceSessionService.php          # create/find voice session
  VoiceIdentityService.php         # phone → parent/student/institution
  VoiceMenuService.php             # DTMF → intent (mirrors chatbot menus)
  VoiceAnswerService.php           # fees/attendance/notice/ptm text for TTS
  InfobipCmlBuilder.php            # say / captureDtmf / hangup helpers
  InfobipVoiceClient.php           # optional Calls API helpers

routes/api.php
  POST /v1/voice/infobip/inbound
  POST /v1/voice/infobip/dtmf
  POST /v1/voice/infobip/status

database/migrations/
  create_voice_sessions_table.php

resources/lang/en/voice.php
resources/lang/fr/voice.php
```

### Critical design rule
`VoiceMenuService` / `VoiceAnswerService` should call **shared domain services** (fees, attendance, notices, PTM), not scrape chatbot reply strings.  
Chatbot stays text UX; Voice stays spoken UX; both share data services.

---

## 10. Configuration (per institution)

New institution settings (suggested):

| Key | Default | Meaning |
|-----|---------|---------|
| `voice_ivr_enabled` | `0` | Master switch |
| `voice_ivr_locale_default` | `fr` | Default TTS language |
| `voice_ivr_parent_pin_required` | `0` | Phase 1.1 optional |
| `voice_ivr_max_turns` | `8` | Anti-loop hangup |
| `voice_ivr_secretary_message` | text | Spoken “contact office…” for key reserved later |

Chatbot settings page can show Voice webhook URLs next to existing Infobip text webhook.

---

## 11. Delivery plan

### Sprint A — Enablement & skeleton (3–5 days)
1. Infobip AM checklist completed
2. Webhook controller + verifier + health endpoint
3. Hard-coded CML welcome + echo DTMF
4. Call log table / voice_sessions

### Sprint B — Guest + Parent IVR (1–2 weeks)
1. Identity resolution by phone
2. Guest menu answers from institution settings
3. Parent menu: fees + attendance + notice + PTM
4. Multi-child select
5. EN/FR prompts

### Sprint C — Hardening (3–5 days)
1. Turn limits, timeouts, empty DTMF handling
2. Privacy redaction rules
3. Metrics: calls started, answered intents, failures
4. Ops runbook in Infobip + Digitex

### Later phases (not Phase 1)
- Phase 2: PIN, pickup status, request status, deeper fees breakdown
- Phase 3: AI voice agent (speech-to-text → Digitex tools → TTS)
- Phase 4: Agent transfer via Infobip Conversations / WebRTC (still no PSTN)

---

## 12. Acceptance criteria (Phase 1 done when)

1. Parent calls school WhatsApp number and hears Digitex welcome in FR/EN  
2. Unmatched number gets guest menu and hears contact/fees info  
3. Matched parent hears personalized fee balance for default child  
4. Press `2` speaks today’s attendance status  
5. Press `4` speaks latest PTM status/date/topic  
6. Press `5` switches child when multiple children exist  
7. Press `*` hangs up cleanly; status webhook marks session ended  
8. No PSTN bridging attempted  
9. Voice remains disabled until `voice_ivr_enabled=1` for that school  

---

## 13. Risks & mitigations

| Risk | Mitigation |
|------|------------|
| WhatsApp Voice not enabled on Infobip account | Complete checklist before coding menus |
| TTS quality / FR accents | Use Infobip female FR/EN voices; keep sentences short |
| Privacy leak on shared phones | Optional PIN; speak only first name + high-level status in Phase 1 |
| Multi-tenant sender routing | Start with one pilot school sender |
| Cost surprise | Separate voice credits; show minutes in message logs later |
| Duplicate logic with chatbot | Extract shared answer services early |

---

## 14. Immediate next actions

1. Send **Section 3 checklist** to Infobip AM  
2. Pick one pilot school WhatsApp sender  
3. Implement Sprint A skeleton webhooks in Digitex  
4. Prove one end-to-end DTMF round-trip  
5. Only then build parent fee/attendance answers  

---

## 15. One-line decision

**Yes — DigitexVx via WhatsApp Calling IVR is the right Phase-1 product.**  
Use Infobip for call transport + TTS/DTMF; use Digitex as the multi-tenant brain, reusing chatbot identity and domain data — not the text reply strings.

---

## 16. Delivered implementation

### 16.1 Super Admin gating (per school)

Voice is a normal Digitex module: slug `voice_ivr`, permissions `voice_ivr.view` / `voice_ivr.manage`.
Super Admin enables it per school in **Configuration → Modules**, exactly like Invoices or Voting.
Every voice webhook re-checks the module on each turn, so revoking it mid-call ends the call politely.
A school-level switch (`voice_ivr_enabled`) lets the school pause voice without losing the module.

### 16.2 Shipped menu map

Top-level menus keep the Phase-1 keys; everything added later lives under **6 — more options**,
which only lists what the school has enabled.

| Key | Guest | Parent |
|-----|-------|--------|
| `1` | Admission info | Fee balance |
| `2` | Fees info | Today's attendance |
| `3` | Contact school | Latest notice |
| `4` | Parent portal help | PTM status |
| `5` | — | Switch child |
| `6` | More options | More options |
| `8` | — | School contact |
| `9` | Language EN↔FR | Language EN↔FR |
| `0` | Repeat | Repeat |
| `*` | Hang up | Hang up |

More options (numbered dynamically): pickup status, request status, payment details,
ask a question in your own words (AI), speak to someone at the school (transfer).

### 16.3 Phase 2 — privacy & deeper self-service

- `voice_ivr_parent_pin_required` gates every child-specific answer behind a 4-digit PIN.
- PINs live in `voice_parent_pins`, hashed, 3 attempts then a 30-minute lock; staff set/remove
  them from the Voice settings page by parent phone number.
- Guests are never asked for a PIN — their menu is public information only.
- New spoken answers: pickup (QR) status, latest student request status, payment details
  (next due invoice + last payment).

### 16.4 Phase 3 — AI voice agent

- `voice_ivr_ai_enabled` (+ `voice_ivr_ai_guest_enabled`, `voice_ivr_ai_max_questions`).
- Flow: `say` prompt → CML `record` → `/voice/infobip/recording` → download recording from the
  Calls API → transcribe through the OpenAI-compatible `/audio/transcriptions` endpoint →
  answer → `say` + back to the keypad menu.
- The model is given **only** the facts the caller could already hear from the keypad menu and is
  instructed to refuse anything else, so AI cannot widen data access. It runs through `AiManager`,
  so plan access, quota and usage logging are shared with the rest of the AI layer.
- Every failure (no AI plan, no key, bad audio, empty transcript) degrades to the keypad menu.

### 16.5 Phase 4 — transfer to a person

- `voice_ivr_transfer_enabled`, `voice_ivr_transfer_endpoint_type` (`whatsapp` / `webrtc` / `viber`),
  `voice_ivr_transfer_identity`, plus optional office-hours window.
- Uses the CML `dial` action; outside office hours the caller hears the secretary message instead.
- **PSTN/landline bridging is intentionally not implemented** — the endpoint list excludes `PHONE`.

### 16.6 Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| `POST` | `/api/v1/voice/infobip/inbound` | Welcome CML |
| `POST` | `/api/v1/voice/infobip/dtmf` | Keypad + PIN input |
| `POST` | `/api/v1/voice/infobip/recording` | AI question (record callback) |
| `POST` | `/api/v1/voice/infobip/transfer` | Dial callback |
| `POST` | `/api/v1/voice/infobip/status` | Hangup / failure log |
| `GET`  | `/api/v1/voice/infobip/health` | Ops ping |

All are verified with `ChatbotWebhookVerifier` (Infobip API key, shared secret, or explicit
staging skip flag) and return `200` with CML actions.

### 16.7 Code layout

```text
app/Http/Controllers/Api/V1/Voice/InfobipVoiceWebhookController.php
app/Http/Controllers/VoiceSettingController.php
app/Models/VoiceSession.php, VoiceParentPin.php
app/Services/Voice/
  InfobipCmlBuilder.php      # say / captureDtmf / record / dial / hangup
  InfobipVoiceClient.php     # Calls API recording download
  VoiceSessionService.php    # session lifecycle, turn limits
  VoiceIdentityService.php   # MSISDN -> school + parent, module/enable checks
  VoiceAnswerService.php     # spoken answers from domain data
  VoiceMenuService.php       # states, menus, PIN gate funnel
  VoicePinService.php        # Phase 2 PIN
  VoiceSpeechService.php     # Phase 3 speech-to-text
  VoiceAiAgentService.php    # Phase 3 grounded answering
  VoiceTransferService.php   # Phase 4 transfer rules
resources/views/voice/settings.blade.php
resources/lang/{en,fr}/voice.php
```

### 16.8 Still external

Infobip must enable WhatsApp Business Calling on the school sender and point the CML webhooks at
the URLs shown on the Voice settings page (§3 checklist). Until then the code degrades safely:
unmapped sender numbers and disabled schools hear a short "not enabled" message and the call ends.
