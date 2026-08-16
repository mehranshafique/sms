# WhatsApp Voice IVR Help Manual

**Digitex / Integrale Plus** — school administrators, Super Admin, and Infobip operators.

This guide answers three questions:

1. Is the WhatsApp Voice IVR 100% ready to use?
2. What must you do before parents can call the school WhatsApp number?
3. How do you configure, test, and support the module day to day?

---

## 1. Is it 100% ready?

**The Digitex software is complete. The live calling service is not automatically on.**

Digitex already includes:

- Guest and parent keypad menus (French / English)
- Optional 4-digit parent PIN before child data
- Optional spoken AI questions (needs an AI plan)
- Optional transfer to a staff WhatsApp, WebRTC or Viber endpoint
- Settings page, webhook URLs, PIN management, and a recent-calls table

It is **not** ready for parents until all of the following are true:

- Infobip has enabled **WhatsApp Voice / Business Calling** on the school sender
- Infobip routes inbound WhatsApp calls to Digitex CML webhooks (HTTPS)
- Super Admin has enabled the **Voice Ivr** module for the school
- The school WhatsApp number is saved in Configuration (same number Infobip uses as the sender)
- The **Enable WhatsApp Voice IVR** switch is on
- A test WhatsApp call appears in **Recent voice sessions**

If **Recent voice sessions** is empty, Digitex has not received a call yet. Saving the settings page alone does not start voice calling.

**Honest go-live status**

| Piece | Status |
| --- | --- |
| Digitex menus, PIN, AI, transfer code | Built |
| School settings UI | Built |
| Infobip WhatsApp Voice on your sender | External — ask Infobip |
| Webhook routing in Infobip | You must paste Digitex URLs |
| Landline / mobile (PSTN) transfer | Not supported (Meta / Infobip rule) |
| School-initiated outbound WhatsApp calls | Not in this module |

---

## 2. What parents will hear

A parent opens the school WhatsApp chat and taps **Call** (not a normal phone call). Infobip connects the call to Digitex. Digitex identifies the caller by phone number.

**Known parent** (number matches a parent record in this school):

| Key | Action |
| --- | --- |
| 1 | Fee balance |
| 2 | Today's attendance |
| 3 | Latest notice |
| 4 | Parent-teacher meeting status |
| 5 | Switch child |
| 6 | More options (pickup, requests, payment details, AI, staff transfer) |
| 8 | School contact |
| 9 | Change language (EN / FR) |
| 0 | Repeat menu |
| * | Hang up |

**Unknown caller (guest):**

| Key | Action |
| --- | --- |
| 1 | Admission information |
| 2 | School fees information |
| 3 | Contact the school |
| 4 | Parent portal help |
| 6 | More options (AI and staff transfer if enabled) |
| 9 | Change language |
| 0 | Repeat |
| * | Hang up |

If PIN is required, Digitex asks for the 4-digit code (then hash) before any child-specific answer. Guests never hear a PIN prompt.

---

## 3. Setup checklist (do this in order)

### Step 1 — Infobip account (do this first)

Ask Infobip support / your account manager to:

- Enable **WhatsApp Voice / Business Calling** on the school WhatsApp sender
- Confirm DTMF (keypad) is available on WhatsApp Calling for your account
- Confirm a **voice minutes** plan (this is billed separately from WhatsApp text)
- Confirm inbound calls can be forwarded to **Calls API / CML** on a public HTTPS URL

Until Infobip enables Voice on the sender, parents will not reach Digitex, even if every Digitex switch is on.

### Step 2 — Digitex Super Admin

1. Open **Configuration → Modules** for the school.
2. Enable **Voice Ivr**.
3. Confirm **Configuration → Infobip** has the same **WhatsApp From** number as the Infobip sender (international digits, for example `243812415004`).
4. Confirm `APP_URL` in `.env` is the public HTTPS address of Digitex (webhooks will not work on localhost unless you use a tunnel).

Digitex matches the called number (`To`) against `infobip_whatsapp_from`, `school_whatsapp_number`, or `whatsapp_from`. If that number is blank or belongs to more than one school, the caller hears that voice is not enabled.

### Step 3 — School Voice settings

Open **Communication → WhatsApp Voice IVR**.

1. Turn **Enable WhatsApp Voice IVR** on.
2. Set **Default language** (French or English).
3. Optionally write a **Secretary message** (office hours / how to contact the school).
4. Click **Save**.

Optional (not required for a first test):

- **Require a voice PIN** — recommended if several families share a phone. Then set a 4-digit PIN per parent using the parent phone number.
- **AI voice agent** — only if the school has an AI plan and an AI provider configured. Otherwise leave it off.
- **Transfer to a person** — WhatsApp, WebRTC or Viber identity only. Do not enter a landline. Leave transfer hours empty to allow transfers at any time.

### Step 4 — Paste webhook URLs in Infobip

On the Voice settings page, copy these URLs into Infobip Calls / WhatsApp Voice routing (CML):

| Digitex label | When Infobip should call it |
| --- | --- |
| Inbound CML | Call starts / is answered |
| DTMF callback | Parent presses a key |
| Recording callback | Spoken AI question (only if AI is on) |
| Transfer callback | After Digitex dials a staff endpoint |
| Status / hangup | Call ends or fails |
| Health check | Optional ops ping (`GET`) |

Keep the `?secret=` query if it is shown. Digitex also accepts the chatbot webhook secret / Infobip API key verification used by WhatsApp text.

Open the **Health check** URL in a browser. You should see JSON similar to `"status":"ok"`. If that fails, Infobip cannot reach Digitex either.

### Step 5 — Place a test call

1. From a parent WhatsApp that is saved on a student in this school, open the school chat.
2. Tap **Call**.
3. You should hear the school name and the keypad menu.
4. Press `1` (fees) or `0` (repeat).
5. Refresh **Communication → WhatsApp Voice IVR**. A row must appear under **Recent voice sessions**.

If you hear “Voice assistance is not enabled for this school”, check the module, the enable switch, and that the Infobip sender matches the school WhatsApp From number.

---

## 4. How to use each setting

### Enable WhatsApp Voice IVR

Master switch for this school. The Voice Ivr module must also be on, or the switch cannot be saved.

### Default language

First language of the welcome prompt. Callers can still press `9` to switch between French and English.

### Max menu turns

Safety limit so a call cannot loop forever. Default 8 is enough for a normal parent call.

### Secretary message

Spoken when the caller needs office contact, or when staff transfer is closed (outside transfer hours).

### Privacy / PIN

Turn on if one phone is used by several families. Staff set PINs from the same page:

1. Enter the parent phone (same digits as in the student record).
2. Enter a 4-digit PIN.
3. Click **Set PIN**.

PINs are stored hashed. After 3 wrong attempts the PIN locks for 30 minutes. Removing the PIN from the list means the parent cannot pass the PIN gate until you set a new one.

### AI voice agent

The parent presses the AI option, speaks a question, Digitex transcribes it and answers **only** from facts that keypad menus could already give (fees, attendance, notices, and so on). It will not invent marks or look up another child.

Requirements:

- School AI plan is active
- AI provider is configured
- Switch **Enable spoken questions** is on

Leave **Also allow unknown callers** off unless you want guests to use AI on public information only.

### Transfer to a person

Connects the WhatsApp call to a **WhatsApp**, **WebRTC** or **Viber** endpoint configured in Infobip.

- **Endpoint identity** for WhatsApp/Viber: staff number in international format
- **WebRTC**: the agent identity from your Infobip WebRTC application
- **Transfers from / until**: office hours. Leave both empty for any time

WhatsApp Calling **cannot** bridge to a PSTN landline or ordinary mobile call. If you need a desk phone, use a WebRTC agent or a staff WhatsApp that can receive the transfer.

---

## 5. Day-to-day operations

### Confirm the module is working

- **Health check** URL returns `ok`
- **Recent voice sessions** shows new rows after a call
- Session **Profile** is `parent` for known numbers and `guest` otherwise
- **PIN** column shows a check when the parent passed the PIN gate
- **AI** column counts spoken questions on that call

### Typical problems

| Symptom | What to check |
| --- | --- |
| Call never starts / WhatsApp has no Call button | Infobip has not enabled WhatsApp Voice on the sender |
| Call starts then hangs up immediately | Module off, IVR switch off, or WhatsApp From does not match the called number |
| Empty Recent voice sessions | Infobip is not posting to Digitex inbound URL, or `APP_URL` is wrong |
| Health check fails | Server, HTTPS certificate, or firewall |
| Parent hears guest menu | Phone on the student/parent record does not match the WhatsApp calling number |
| PIN always fails | PIN was never set, or you used a different phone than the parent record |
| AI says it is unavailable | No AI plan / provider, or AI switch is off |
| Transfer says the office is closed | Transfer hours window, or transfer identity is empty |
| Transfer never rings | Infobip endpoint type/identity is wrong; PSTN numbers are rejected |

---

## 6. Security notes

- Treat webhook URLs with `?secret=` as credentials. Do not post them in public tickets.
- Do not require PIN for guests; they only hear public school information.
- AI answers are grounded in the same facts as the keypad. It is not a general chatbot and must not be treated as a counsellor or examiner.
- Voice minutes are billed by Infobip separately from WhatsApp text templates.

---

## 7. Quick go-live test (15 minutes)

1. Super Admin enables **Voice Ivr** for the school.
2. School saves WhatsApp From = Infobip sender.
3. School turns **Enable WhatsApp Voice IVR** on and saves.
4. Operator pastes inbound + DTMF + status URLs in Infobip.
5. Browser opens the health URL — JSON `ok`.
6. Parent WhatsApp calls the school number.
7. Hear welcome + press `1`.
8. Digitex shows a session row.

If step 8 fails, stop and fix Infobip routing before training parents. The Digitex page can be fully configured and still receive zero calls until Infobip Voice is live.
