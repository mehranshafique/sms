# Online Invoice Payment Links

---

## For accountants / admins

### Automatic link on each invoice

1. Open **Finance → Invoices →** click an unpaid invoice.
2. Scroll to **Online Payment Link** section.
3. Copy the URL — example:
   ```
   https://e-digitex.com/pay/a1b2c3d4e5...
   ```
4. Send to parent via WhatsApp, SMS, or email.

### Regenerate link

If a link was shared wrongly, click **Regenerate link**. Old link stops working.

### Invoice lookup (no link needed)

Parents can go to:

```
https://e-digitex.com/pay
```

Enter:
- **Invoice number** (e.g. INV-2026-...)
- **Student admission number**

---

## Invoice ID vs payment link

| Item | Example | Use |
|------|---------|-----|
| Invoice number | INV-2026-GVIS0142-... | Lookup on /pay |
| Payment token link | /pay/{long-token} | Direct pay — no typing |

Both work for the same invoice.

---

## Requirements

- **Online payments** enabled in Payment Methods
- At least one payment method enabled
- Invoice status not fully **Paid**
