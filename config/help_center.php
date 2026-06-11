<?php

return [
    'categories' => [
        'getting-started' => [
            'icon' => 'fa-book-open',
            'title' => ['en' => 'Getting Started', 'fr' => 'Premiers pas'],
        ],
        'finance-payments' => [
            'icon' => 'fa-money-bill-wave',
            'title' => ['en' => 'Fees & Online Payments', 'fr' => 'Frais & paiements en ligne'],
        ],
        'parents-guardians' => [
            'icon' => 'fa-users',
            'title' => ['en' => 'Parents & Guardians', 'fr' => 'Parents & tuteurs'],
        ],
        'mobile-app' => [
            'icon' => 'fa-mobile-alt',
            'title' => ['en' => 'Mobile App', 'fr' => 'Application mobile'],
        ],
    ],

    'articles' => [
        'welcome' => [
            'category' => 'getting-started',
            'title' => ['en' => 'Welcome to Digitex SMS Help', 'fr' => 'Bienvenue — Aide Digitex SMS'],
            'summary' => ['en' => 'How to use this help center and community forum.', 'fr' => 'Utiliser le centre d\'aide et le forum.'],
        ],
        'payment-gateway-overview' => [
            'category' => 'finance-payments',
            'title' => ['en' => 'Payment Gateways Overview (DRC)', 'fr' => 'Vue d\'ensemble des passerelles (RDC)'],
            'summary' => ['en' => 'PawaPay, CinetPay, Flutterwave — which to choose and how they work.', 'fr' => 'PawaPay, CinetPay, Flutterwave — choix et fonctionnement.'],
        ],
        'payment-methods-setup' => [
            'category' => 'finance-payments',
            'title' => ['en' => 'Configure Payment Methods', 'fr' => 'Configurer les modes de paiement'],
            'summary' => ['en' => 'Enable Cash, Mobile Money, bank transfer, and merchant codes.', 'fr' => 'Activer Cash, Mobile Money, virement et codes marchands.'],
        ],
        'payment-gateway-pawapay' => [
            'category' => 'finance-payments',
            'title' => ['en' => 'PawaPay Setup (API Keys & Webhooks)', 'fr' => 'Configuration PawaPay (clés API & webhooks)'],
            'summary' => ['en' => 'Step-by-step PawaPay registration for Orange, Airtel, M-Pesa in DRC.', 'fr' => 'Inscription PawaPay pour Orange, Airtel, M-Pesa en RDC.'],
        ],
        'payment-gateway-cinetpay' => [
            'category' => 'finance-payments',
            'title' => ['en' => 'CinetPay Setup (API Keys & Webhooks)', 'fr' => 'Configuration CinetPay (clés API & webhooks)'],
            'summary' => ['en' => 'CinetPay checkout for DRC mobile money (CDF & USD).', 'fr' => 'CinetPay pour Mobile Money RDC (CDF & USD).'],
        ],
        'payment-gateway-flutterwave' => [
            'category' => 'finance-payments',
            'title' => ['en' => 'Flutterwave Setup (API Keys & Webhooks)', 'fr' => 'Configuration Flutterwave (clés API & webhooks)'],
            'summary' => ['en' => 'Flutterwave for DRC mobile money and cards.', 'fr' => 'Flutterwave pour Mobile Money et cartes en RDC.'],
        ],
        'online-payment-links' => [
            'category' => 'finance-payments',
            'title' => ['en' => 'Online Invoice Payment Links', 'fr' => 'Liens de paiement en ligne'],
            'summary' => ['en' => 'Share pay links with parents; invoice lookup page.', 'fr' => 'Partager les liens de paiement avec les parents.'],
        ],
        'payment-proof-upload' => [
            'category' => 'finance-payments',
            'title' => ['en' => 'Manual Payment Proof (Parents)', 'fr' => 'Preuve de paiement manuelle (parents)'],
            'summary' => ['en' => 'Upload receipt, transaction ID, and date after paying offline.', 'fr' => 'Téléverser reçu, référence et date après paiement hors ligne.'],
        ],
        'payment-proof-admin' => [
            'category' => 'finance-payments',
            'title' => ['en' => 'Review Payment Proofs (Accountant)', 'fr' => 'Vérifier les preuves de paiement (comptable)'],
            'summary' => ['en' => 'Approve or reject parent-submitted payment receipts.', 'fr' => 'Approuver ou rejeter les preuves soumises.'],
        ],
        'parent-pay-online' => [
            'category' => 'parents-guardians',
            'title' => ['en' => 'How Parents Pay School Fees Online', 'fr' => 'Comment payer les frais scolaires en ligne'],
            'summary' => ['en' => 'Guide for parents: instant pay vs upload proof.', 'fr' => 'Guide parents : paiement instantané ou preuve manuelle.'],
        ],
    ],
];
