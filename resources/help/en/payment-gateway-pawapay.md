# PawaPay Setup — API Keys & Webhooks (DRC)

**PawaPay** is the recommended gateway for **direct Mobile Money** in the DRC (Orange, Airtel, Vodacom M-Pesa).

---

## Step 1 — Create a PawaPay merchant account

1. Go to **https://dashboard.pawapay.io** (production) or **https://dashboard.sandbox.pawapay.io** (testing).
2. Click **Sign up** / **Register as merchant**.
3. Complete business verification (school name, contact, bank details).
4. Wait for account approval from PawaPay.

---

## Step 2 — Get your API token

1. Log in to the PawaPay dashboard.
2. Open **Settings** → **API** or **Developers**.
3. Copy your **Bearer API Token** (long secret string).
4. **Never share this token publicly** — treat it like a password.

---

## Step 3 — Configure in Digitex SMS

1. Log in to **https://e-digitex.com**
2. Switch to your school (building icon, top-right).
3. Go to **Finance → Fees & Collection → Payment Methods**.
4. Scroll to **Payment Gateway (DRC)**.
5. Set:
   - **Provider:** PawaPay
   - **Environment:** Sandbox (for testing)
   - **PawaPay API Token:** paste your token
6. Enable **Online payment links**.
7. Enable payment methods: Orange Money, Airtel Money, M-Pesa/Vodacom.
8. Click **Save settings**.

---

## Step 4 — Register webhook URL

1. On the Payment Methods page, copy:
   ```
   https://YOUR-DOMAIN.com/webhooks/payments/pawapay
   ```
2. In PawaPay dashboard → **Webhooks** / **Callbacks**.
3. Add URL above, events: **Deposit completed**, **Deposit failed**.
4. Save.

---

## Step 5 — Test a payment

1. Create a test invoice for a student.
2. Open invoice → copy **Online Payment Link**.
3. Open link in phone browser (use Sandbox test numbers from PawaPay docs).
4. Choose **Pay instantly** → Orange Money → enter test phone.
5. Confirm PIN on phone (sandbox).
6. Invoice should show **Paid** within seconds.

---

## DRC provider codes (automatic)

Digitex maps your payment methods to PawaPay:

| Method in Digitex | PawaPay provider |
|-------------------|------------------|
| Orange Money | ORANGE_COD |
| Airtel Money | AIRTEL_COD |
| M-Pesa / Vodacom | VODACOM_MPESA_COD |

---

## Go live (Production)

1. Complete PawaPay production KYC.
2. Get **production API token**.
3. In Digitex Payment Methods → Environment: **Production**.
4. Paste production token.
5. Update webhook URL on production PawaPay dashboard.
6. Test with a small real payment (e.g. 100 CDF).

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Payment stays pending | Check webhook URL is reachable (HTTPS required) |
| Invalid token error | Regenerate token in PawaPay; re-save in Digitex |
| Wrong operator | Parent phone must match operator (243...) |
| Amount rejected | CDF amounts for Vodacom must be whole numbers |

**Support:** PawaPay docs — https://docs.pawapay.io
