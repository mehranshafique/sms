# Payment Gateways Overview (Democratic Republic of Congo)

This guide explains how **online fee collection** works in Digitex SMS and which **payment gateway** to choose for schools in the **DRC**.

---

## Why online payments?

Parents can pay school fees without visiting the office:

- **Instant payment** via Mobile Money (Orange, Airtel, M-Pesa/Vodacom)
- **Manual proof upload** if they already paid at an agent or bank
- **Shareable invoice link** — send by WhatsApp or SMS

---

## Supported gateways (DRC)

| Gateway | Best for | Mobile operators (DRC) | Currencies |
|---------|----------|--------------------------|------------|
| **PawaPay** (recommended) | Direct API — payment prompt on parent's phone | Orange, Airtel, Vodacom M-Pesa | CDF, USD |
| **CinetPay** | Redirect checkout page (very popular in Francophone Africa) | Orange CD, M-Pesa CD, Airtel CD | CDF, USD |
| **Flutterwave** | Pan-African platform + cards | DRC mobile money, cards | CDF, USD |

You enable **one gateway at a time** per school in **Finance → Payment Methods**.

---

## How the flow works

### Option A — Pay instantly (gateway)

1. Parent opens invoice pay link (`/pay/...`)
2. Chooses **Pay instantly**
3. Enters phone number and amount
4. **PawaPay:** PIN prompt on phone  
   **CinetPay / Flutterwave:** Redirect to secure checkout
5. Gateway confirms payment → invoice marked **Paid** automatically

### Option B — Upload proof (manual)

1. Parent pays at Mobile Money agent or bank **outside** the system
2. Opens pay link → **Upload proof**
3. Enters date/time, transaction ID, uploads receipt photo
4. Status: **Pending** until accountant approves
5. Accountant approves → invoice updated → parent notified

---

## What you must configure (admin checklist)

1. **Payment Methods** — Enable Orange Money, Airtel, bank transfer, etc.
2. **Gateway provider** — Choose PawaPay, CinetPay, or Flutterwave
3. **API credentials** — From gateway dashboard (see dedicated guides)
4. **Webhooks** — Copy URLs from Payment Methods page into gateway dashboard
5. **Online payments** — Toggle ON
6. **Manual proof** — Toggle ON (recommended as backup)

**Menu path:** Finance → Fees & Collection → **Payment Methods**

---

## Webhook URLs (copy from your school)

After saving Payment Methods, your system shows three webhook URLs. Register them in your gateway dashboard:

```
https://YOUR-DOMAIN.com/webhooks/payments/pawapay
https://YOUR-DOMAIN.com/webhooks/payments/cinetpay
https://YOUR-DOMAIN.com/webhooks/payments/flutterwave
```

Replace `YOUR-DOMAIN.com` with your live domain (e.g. `e-digitex.com`).

**Why webhooks matter:** When a parent completes payment on their phone, the gateway sends a secure notification to your server so the invoice is marked paid **automatically** — even if the parent closes the browser.

---

## Sandbox vs Production

| Environment | Purpose |
|-------------|---------|
| **Sandbox** | Testing with fake money — use during setup |
| **Production** | Real payments — switch only when API keys are live |

Always test in **Sandbox** first, then change environment to **Production** in Payment Methods settings.

---

## Related guides

- [Configure payment methods](/help/payment-methods-setup)
- [PawaPay setup](/help/payment-gateway-pawapay)
- [CinetPay setup](/help/payment-gateway-cinetpay)
- [Flutterwave setup](/help/payment-gateway-flutterwave)
- [Online payment links](/help/online-payment-links)
- [Review payment proofs](/help/payment-proof-admin)

---

## Common questions

**Q: Can I use more than one gateway?**  
A: Only one active provider per school at a time. You can switch in Payment Methods settings.

**Q: What if the gateway is down?**  
A: Keep **Manual proof upload** enabled so parents can still submit receipts.

**Q: Who pays gateway fees?**  
A: Transaction fees are defined by PawaPay/CinetPay/Flutterwave — check their pricing pages.

**Q: Do parents need an account?**  
A: No. Pay links are public. Parents only need invoice number + admission number for lookup.
