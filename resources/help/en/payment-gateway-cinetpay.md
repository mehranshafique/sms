# CinetPay Setup — API Keys & Webhooks (DRC)

**CinetPay** is widely used in Francophone Africa including **RD Congo** (Orange CD, M-Pesa CD, Airtel CD).

---

## Step 1 — Register

1. Visit **https://cinetpay.com**
2. Create merchant account (school / business).
3. Complete verification.

---

## Step 2 — Get API Key and Site ID

1. Log in to **CinetPay merchant dashboard**.
2. Go to **Integration** / **API**.
3. Copy:
   - **API Key** (apikey)
   - **Site ID** (site_id)

---

## Step 3 — Configure Digitex SMS

1. **Finance → Payment Methods → Payment Gateway (DRC)**
2. Provider: **CinetPay**
3. Environment: Sandbox or Production
4. Paste **API Key** and **Site ID**
5. Save.

---

## Step 4 — Notify URL (webhook)

Register in CinetPay dashboard:

```
https://YOUR-DOMAIN.com/webhooks/payments/cinetpay
```

CinetPay sends payment confirmation to this URL.

**Return URL** is handled automatically by Digitex when parent completes checkout.

---

## DRC channels supported

| Digitex method | CinetPay channel |
|----------------|------------------|
| Orange Money | OMCD (CDF) / OMCDUSD |
| M-Pesa | MPESACD |
| Airtel Money | AIRTELCD |

---

## Testing

Use CinetPay **test mode** with test phone numbers from their documentation.

Docs: **https://docs.cinetpay.com**
