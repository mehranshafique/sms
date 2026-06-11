# Flutterwave Setup — API Keys & Webhooks (DRC)

**Flutterwave** supports DRC mobile money and card payments.

---

## Step 1 — Register

1. Go to **https://flutterwave.com**
2. Sign up as business / merchant.
3. Complete verification.

---

## Step 2 — API keys

1. Dashboard → **Settings → API**
2. Copy **Public Key** and **Secret Key**
3. Under **Webhooks**, set **Secret Hash** (for verifying callbacks)

---

## Step 3 — Configure Digitex SMS

1. **Finance → Payment Methods → Payment Gateway**
2. Provider: **Flutterwave**
3. Enter Public Key, Secret Key, Webhook Secret Hash
4. Save.

---

## Step 4 — Webhook URL

Register in Flutterwave dashboard:

```
https://YOUR-DOMAIN.com/webhooks/payments/flutterwave
```

Enable events: `charge.completed`

---

## Payment options

Flutterwave checkout offers **mobilemoneycdr** for DRC Mobile Money and cards for USD.

Docs: **https://developer.flutterwave.com**
