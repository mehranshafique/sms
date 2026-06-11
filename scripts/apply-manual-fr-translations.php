<?php

$path = __DIR__ . '/../doc/markdown/fr/user-manual.md';
if (!file_exists($path)) {
    fwrite(STDERR, "File not found: {$path}\n");
    exit(1);
}

$content = file_get_contents($path);

$replacements = [
    '# Digitex School Management System' => '# Digitex — Système de Gestion Scolaire',
    '# Complete User Manual — Module by Module' => '# Manuel Utilisateur Complet — Module par Module',
    '**Audience:** School administrators, teachers, accountants, parents, and staff who are **not technical experts**.' =>
    '**Public :** Administrateurs scolaires, enseignants, comptables, parents et personnel **non techniques**.',
    '**Goal:** Explain every part of the system in plain language, with real-world examples, so you know *what to click*, *why it exists*, and *what must be done first*.' =>
    '**Objectif :** Expliquer chaque partie du système en langage simple, avec des exemples concrets.',
    '## How to Read This Manual' => '## Comment lire ce manuel',
    '- **Purpose** — Why this module exists' => '- **Objectif** — Pourquoi ce module existe',
    '- **Who uses it** — Which job roles need it' => '- **Qui l\'utilise** — Quels rôles en ont besoin',
    '- **Depends on** — What you must set up before using it' => '- **Prérequis** — Ce qu\'il faut configurer avant',
    '- **Step-by-step** — How to use it, with example values' => '- **Étapes** — Comment l\'utiliser',
    '- **Example story** — A short scenario like a real school day' => '- **Exemple concret** — Scénario réaliste',
    '- **Common questions** — Answers a normal user would ask' => '- **Questions fréquentes** — FAQ',
    '### Purpose' => '### Objectif',
    '### Who uses it' => '### Qui l\'utilise',
    '### Depends on' => '### Prérequis',
    '### Step-by-step' => '### Étapes',
    '### Example story' => '### Exemple concret',
    '### Example' => '### Exemple',
    '### Common questions' => '### Questions fréquentes',
    '### Flow' => '### Déroulement',
    '# PART A — BEFORE YOU START' => '# PARTIE A — AVANT DE COMMENCER',
    '# PART B — PLATFORM ADMINISTRATION (Usually Super Admin / Head Officer)' => '# PARTIE B — ADMINISTRATION PLATEFORME',
    '# PART C — ACADEMICS MODULES' => '# PARTIE C — MODULES ACADÉMIQUES',
    '# PART D — PEOPLE MODULES (Students & Parents)' => '# PARTIE D — ÉLÈVES & PARENTS',
    '# PART E — ATTENDANCE & REQUESTS' => '# PARTIE E — PRÉSENCE & DEMANDES',
    '# PART F — STAFF & HR' => '# PARTIE F — PERSONNEL & RH',
    '# PART G — EXAMINATIONS & RESULTS' => '# PARTIE G — EXAMENS & RÉSULTATS',
    '# PART H — FINANCE MODULES' => '# PARTIE H — MODULES FINANCE',
    '# PART I — COMMUNICATION' => '# PARTIE I — COMMUNICATION',
    '# PART J — PICKUP (Child Collection Security)' => '# PARTIE J — PICKUP',
    '# PART K — ELECTIONS & VOTING' => '# PARTIE K — ÉLECTIONS',
    '# PART L — QUICK REFERENCE — MODULE DEPENDENCY CHART' => '# PARTIE L — RÉFÉRENCE RAPIDE',
    '# PART M — GLOSSARY FOR NON-TECHNICAL USERS' => '# PARTIE M — GLOSSAIRE',
    '## Module A1: Logging In and Understanding Your Screen' => '## Module A1 : Connexion et écran',
    '## Module A2: Dashboard' => '## Module A2 : Tableau de bord',
    '## Module A3: Global Search and In-App Notifications' => '## Module A3 : Recherche et notifications',
    '## Module B1: Institutions (Schools)' => '## Module B1 : Établissements',
    '## Module B2: Campuses' => '## Module B2 : Campus',
    '## Module B3: Head Officers' => '## Module B3 : Head Officers',
    '## Module B4: Roles and Permissions' => '## Module B4 : Rôles et permissions',
    '## Module B5: Configuration Hub (Critical — SMS, WhatsApp, Email, Notifications)' => '## Module B5 : Configuration (SMS, WhatsApp, e-mail)',
    '## Module B6: Settings (School Operational Rules)' => '## Module B6 : Paramètres école',
    '## Module B7: Packages and Subscriptions' => '## Module B7 : Forfaits et abonnements',
    '## Module B8: Audit Logs' => '## Module B8 : Journaux d\'audit',
    '## Module C1: Academic Sessions' => '## Module C1 : Sessions académiques',
    '## Module C2: Departments (Universities)' => '## Module C2 : Départements',
    '## Module C3: Grade Levels' => '## Module C3 : Niveaux scolaires',
    '## Module C4: Class Sections' => '## Module C4 : Classes',
    '## Module C5: Subjects' => '## Module C5 : Matières',
    '## Module C6: Class Subjects (Subject Allocation)' => '## Module C6 : Affectation matières',
    '## Module C7: Timetables' => '## Module C7 : Emplois du temps',
    '## Module C8: Assignments (Homework)' => '## Module C8 : Devoirs',
    '## Module C9: Programs and Academic Units (LMD / University)' => '## Module C9 : Programmes LMD',
    '## Module D1: Student Parents (Guardians)' => '## Module D1 : Parents / tuteurs',
    '## Module D2: Students' => '## Module D2 : Élèves',
    '## Module D3: Student Enrollments (Schools K-12)' => '## Module D3 : Inscriptions',
    '## Module D4: University Enrollments' => '## Module D4 : Inscriptions universitaires',
    '## Module D5: Student Promotion' => '## Module D5 : Promotion',
    '## Module D6: Student Transfers' => '## Module D6 : Transferts',
    '## Module E1: Student Attendance (Manual)' => '## Module E1 : Présence (manuelle)',
    '## Module E2: Student Attendance (Hardware / RFID Gate)' => '## Module E2 : Présence RFID / NFC',
    '## Module E3: Attendance Analytics' => '## Module E3 : Analyses présence',
    '## Module E4: Student Requests (Tickets)' => '## Module E4 : Demandes élèves',
    '## Module F1: Staff' => '## Module F1 : Personnel',
    '## Module F2: Staff Attendance' => '## Module F2 : Présence personnel',
    '## Module F3: Staff Leave' => '## Module F3 : Congés',
    '## Module F4: Salary Structures' => '## Module F4 : Grilles salariales',
    '## Module F5: Payroll' => '## Module F5 : Paie',
    '## Module G1: Exams' => '## Module G1 : Examens',
    '## Module G2: Exam Schedules' => '## Module G2 : Calendriers examens',
    '## Module G3: Exam Marks (Marks Entry)' => '## Module G3 : Saisie des notes',
    '## Module G4: Result Cards and Academic Reports' => '## Module G4 : Bulletins',
    '## Module H1: Fee Types' => '## Module H1 : Types de frais',
    '## Module H2: Fee Structures' => '## Module H2 : Structures de frais',
    '## Module H3: Invoices' => '## Module H3 : Factures',
    '## Module H4: Payments' => '## Module H4 : Paiements',
    '## Module H5: Student Balances and Statements' => '## Module H5 : Soldes élèves',
    '## Module H6: Budgets and Fund Requests' => '## Module H6 : Budgets',
    '## Module H7: Payment Methods Configuration' => '## Module H7 : Modes de paiement',
    '## Module H8: Online Invoice Payment Links' => '## Module H8 : Liens paiement en ligne',
    '## Module H9: Payment Gateways (PawaPay, CinetPay, Flutterwave)' => '## Module H9 : Passerelles paiement',
    '## Module H10: Manual Payment Proof (Upload & Review)' => '## Module H10 : Preuve paiement manuelle',
    '## Module I1: Notices (Announcements)' => '## Module I1 : Annonces',
    '## Module I2: Reminders (Fee & Exam SMS)' => '## Module I2 : Rappels SMS',
    '## Module I3: Chatbot (WhatsApp / SMS)' => '## Module I3 : Chatbot',
    '## Module J1: Student Pickup — Full Flow' => '## Module J1 : Pickup complet',
    '## Module K1: Elections' => '## Module K1 : Élections',
    '| Term | Simple meaning |' => '| Terme | Signification |',
];

foreach ($replacements as $search => $replace) {
    $content = str_replace($search, $replace, $content);
}

file_put_contents($path, $content);
echo "Web manual FR applied\n";

$mobilePath = __DIR__ . '/../doc/markdown/fr/mobile-app-user-manual.md';
if (file_exists($mobilePath)) {
    $mobile = file_get_contents($mobilePath);
    $mobile = str_replace('# Digitex Portal — Mobile App User Manual', '# Digitex Portal — Manuel Mobile', $mobile);
    $mobile = str_replace('# Complete Guide for All Users', '# Guide complet', $mobile);
    $mobile = str_replace('## How to Read This Manual', '## Comment lire ce manuel', $mobile);
    $mobile = str_replace('### Purpose', '### Objectif', $mobile);
    $mobile = str_replace('### Who uses it', '### Qui l\'utilise', $mobile);
    $mobile = str_replace('### Common questions', '### Questions fréquentes', $mobile);
    $mobile = preg_replace('/^# PART (\d+) — /m', '# PARTIE $1 — ', $mobile);
    file_put_contents($mobilePath, $mobile);
    echo "Mobile manual FR applied\n";
}
