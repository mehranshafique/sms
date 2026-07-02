<?php

return [
    // Core & Auth
    'session_ended' => "Votre session a expiré. Veuillez envoyer un mot-clé pour recommencer.",
    'unknown_state_error' => "⚠️ État inconnu. Retour au menu principal.",
    'system_error' => "⚠️ Une erreur interne s'est produite. Veuillez réessayer plus tard.",
    'admin_welcome_prompt' => "🏢 *Portail Direction Générale*\n\nVeuillez entrer votre identifiant personnel :",
    'director_welcome_prompt' => "🏫 *Portail Directeur*\n\nVeuillez entrer votre identifiant personnel :",
    'finance_welcome_prompt' => "💰 *Portail Finance*\n\nVeuillez entrer votre identifiant personnel :",
    'teacher_welcome_prompt' => "👨‍🏫 *Portail Enseignant*\n\nSaisissez votre code ID pour commencer.",
    'student_welcome_prompt' => "🎓 *Portail Étudiant*\n\nVeuillez entrer votre Matricule :",
    'parent_welcome_prompt' => "👋 *Portail Parent*\n\nVeuillez entrer le Matricule de votre enfant :",
    'keywords_not_found' => "👋 Bonjour ! Veuillez commencer par taper votre mot-clé :\n\n👉 *Portail* (Étudiants)\n👉 *Bonjour* (Parents)\n👉 *Agent* (Enseignants)\n👉 *Digitex* (Direction)",
    'invalid_id' => "❌ ID invalide. Veuillez réessayer.",
    'no_registered_phone' => "⚠️ Erreur : Aucun numéro de téléphone enregistré pour cet ID.",
    'otp_sms_message' => "🔢 Votre code OTP E-Digitex est : :otp. Ne le partagez pas.",
    'otp_sent_notification' => "🔒 *Vérification Requise*\n\nUn OTP a été envoyé par SMS au numéro se terminant par *:phone*.\n\n👉 *Veuillez entrer le code ici pour continuer.*",
    'invalid_otp' => "❌ OTP invalide. Veuillez réessayer.",
    'logout_success' => "✅ Vous avez été déconnecté avec succès.",
    'invalid_option' => "⚠️ Option invalide. Veuillez réessayer.",
    'too_many_attempts' => "🚫 Trop de tentatives échouées. Session terminée.",
    
    // Global Elements
    'not_enrolled' => "⚠️ L'étudiant n'est inscrit à aucune session académique active.",
    'no_data_found' => "⚠️ Aucune donnée trouvée.",
    'action_cancelled' => "🚫 Action annulée.",

    // --- STUDENT MENU (Portail) ---
    'menu_student' => "🎓 *Menu Principal - Université*\n\n1️⃣ Frais académiques\n2️⃣ Horaires\n3️⃣ Résultats académiques\n4️⃣ Travaux académiques\n5️⃣ Notifications académiques\n0️⃣ Quitter",
    'menu_student_fees' => "💰 *Frais académiques*\n\n11️⃣ Minerval\n12️⃣ Enrôlement\n13️⃣ Autres frais\n14️⃣ Mes paiements\n00️⃣ Retour",
    'menu_student_schedules' => "📅 *Horaires*\n\n21️⃣ Examens / Épreuves\n22️⃣ Cours\n23️⃣ Crédits par cours\n00️⃣ Retour",
    'menu_student_results' => "📊 *Résultats académiques*\n\n31️⃣ Semestre I\n32️⃣ Semestre II\n33️⃣ Moyenne générale\n34️⃣ Cours validés / non validés\n00️⃣ Retour",
    'menu_student_work' => "📚 *Travaux académiques*\n\n41️⃣ TP\n42️⃣ Devoirs\n00️⃣ Retour",

    // --- PARENT MENU (Bonjour) ---
    'menu_parent' => "👨‍👩‍👧 *Menu Principal - Parent*\n\n1️⃣ e-TP / e-Devoir / e-Travail\n2️⃣ Frais de l'année\n3️⃣ Mes Paiements\n4️⃣ Dérogation\n5️⃣ Mes requêtes\n6️⃣ Horaires\n7️⃣ e-Bulletin\n8️⃣ QR Code Retrait enfant\n9️⃣ Mes enfants\n0️⃣ Quitter",
    'menu_parent_derogation' => "📝 *Demande de Dérogation*\n\nChoisissez la durée :\n1️⃣ 3 jours\n2️⃣ 7 jours\n3️⃣ 10 jours\n4️⃣ 14 jours\n00️⃣ Annuler",
    'menu_parent_requests' => "📝 *Mes requêtes*\n\n51️⃣ Sortie anticipée\n52️⃣ Hôpital\n53️⃣ Cas d'urgence\n54️⃣ Retard\n55️⃣ Absence\n00️⃣ Annuler",
    'menu_parent_schedule' => "📅 *Horaires*\n\n61️⃣ Cours\n62️⃣ Épreuves/Examens\n00️⃣ Retour",
    
    'derogation_sms_receipt' => "Votre demande de dérogation a été reçue. Réf Ticket : #:ticket. Étudiant : :student. Jours demandés : :days. Traitement sous 48 heures.",
    'request_submitted' => "✅ Requête soumise avec succès.\nRéf Ticket : #:ticket",

    // --- TEACHER MENU (Agent) ---
    'menu_teacher' => "👨‍🏫 *Menu Principal - Agent*\n\n1️⃣ Pointer présence QR Code {OTP}\n2️⃣ Mes horaires\n3️⃣ Mes épreuves\n4️⃣ Mes requêtes\n0️⃣ Quitter",
    'menu_teacher_requests' => "📝 *Mes requêtes*\n\n41️⃣ Avance sur salaire {OTP}\n42️⃣ Demande de congé\n43️⃣ Signaler la maladie\n44️⃣ Empêchement\n45️⃣ Retard\n00️⃣ Retour",
    'menu_teacher_advance' => "💰 *Avance sur salaire*\n\nSélectionnez le niveau :\n1️⃣ 50%\n2️⃣ 30%\n3️⃣ 20%\n4️⃣ 10%\n00️⃣ Annuler",
    
    'teacher_clockin_success' => "✅ Présence pointée avec succès pour aujourd'hui à :time.",
    'advance_sms_receipt' => "Votre demande d'avance sur salaire a été reçue. Réf Ticket : #:ticket. Une réponse sera fournie dans les 48 heures.",

    // --- HEADOFF MENU (Digitex) ---
    'menu_headoff' => "🏢 *Menu Principal - Direction*\n\n1️⃣ Effectifs (Élèves & Personnel)\n2️⃣ Paiement Frais\n3️⃣ Budget & Finance\n4️⃣ Classement & Effectif\n0️⃣ Quitter",
    'menu_headoff_headcount' => "👥 *Effectifs*\n\n11️⃣ Global\n12️⃣ Par Écoles / Par classe\n00️⃣ Retour",
    'menu_headoff_fees' => "💰 *Paiement Frais*\n\n21️⃣ Global | Prévision\n22️⃣ État caisses du jour\n23️⃣ Élève en ordre de frais\n24️⃣ Élève débiteur\n00️⃣ Retour",
    'menu_headoff_budget' => "📉 *Budget & Finance*\n\n31️⃣ Budget global toutes écoles\n32️⃣ Budget par école\n33️⃣ Dépense globale\n34️⃣ Dépense par école\n35️⃣ Demande de fonds encours\n00️⃣ Retour",
    'menu_headoff_rankings' => "🏆 *Classement des écoles*\n\n41️⃣ Effectif\n42️⃣ Paiements\n43️⃣ Budget\n44️⃣ Dépenses\n00️⃣ Retour",
    
    // Outputs
    'fees_output' => "💰 *Détails des frais :*\nTotal : :total\nPayé : :paid\nReste : :balance",
    'schedule_output' => "📅 *Horaire :*\n:content",
    'qr_caption' => "QR Code de retrait pour :student.\nValide pour 2 heures.",
];