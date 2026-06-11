# Configure Payment Methods

**Menu:** Finance → Fees & Collection → **Payment Methods**

---

## Purpose

Control which payment options appear when recording fees or when parents pay online.

---

## Step-by-step

1. Log in as **School Admin** or **Accountant**.
2. Open **Finance → Fees & Collection → Payment Methods**.
3. **Online payments** — Enable to allow public pay links.
4. For each method row, check **Enabled** and fill details:

| Method | What to fill |
|--------|----------------|
| Cash | Instructions (optional) |
| Bank transfer | Bank name, account name, account number |
| Orange / Airtel / M-Pesa / Vodacom | Merchant / USSD code, instructions |
| Card / Online | Instructions |

5. **Merchant code example (Orange DRC):** `*144*1*12345#` or merchant number from your contract.
6. **Instructions example:** "Dial *144#, select Pay Merchant, enter code 12345, amount, confirm PIN."
7. Click **Save settings**.

---

## Gateway section

Below the methods table, configure **Payment Gateway (DRC)** — see [PawaPay](/help/payment-gateway-pawapay), [CinetPay](/help/payment-gateway-cinetpay), or [Flutterwave](/help/payment-gateway-flutterwave) guides.

**Manual proof upload** — Keep enabled so parents can upload receipts when not using instant pay.

---

## Example — Green Valley School

- Enabled: Cash, Bank transfer, Orange Money, Airtel Money
- Orange merchant code: `123456`
- Gateway: PawaPay (Sandbox during setup)
- Online payments: ON
- Manual proof: ON
