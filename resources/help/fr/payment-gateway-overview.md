# Vue d'ensemble des passerelles de paiement (RDC)

Guide pour les écoles en **République Démocratique du Congo**.

## Passerelles supportées

| Passerelle | Usage | Opérateurs RDC |
|------------|-------|----------------|
| **PawaPay** (recommandé) | API directe — confirmation sur le téléphone | Orange, Airtel, M-Pesa/Vodacom |
| **CinetPay** | Page de paiement sécurisée | Orange CD, M-Pesa CD, Airtel CD |
| **Flutterwave** | Mobile Money + cartes | DRC |

**Menu :** Finance → Frais & recouvrement → **Modes de paiement**

## Deux options pour les parents

1. **Payer instantanément** — via passerelle (PIN Mobile Money ou checkout)
2. **Téléverser une preuve** — après paiement chez un agent ou à la banque

## URLs webhook (à copier dans le tableau de bord gateway)

```
https://VOTRE-DOMAINE.com/webhooks/payments/pawapay
https://VOTRE-DOMAINE.com/webhooks/payments/cinetpay
https://VOTRE-DOMAINE.com/webhooks/payments/flutterwave
```

## Sandbox vs Production

- **Sandbox** : tests sans argent réel
- **Production** : paiements réels — après validation KYC

Guides détaillés : [PawaPay](/help/payment-gateway-pawapay) | [CinetPay](/help/payment-gateway-cinetpay) | [Flutterwave](/help/payment-gateway-flutterwave)
