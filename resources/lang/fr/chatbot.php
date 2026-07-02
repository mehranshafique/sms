<?php

return [
    'page_title' => 'Paramètres du Chatbot',
    'subtitle' => 'Configurer les réponses et comportements automatisés',
    
    // Config & Settings
    'general_config' => 'Configuration Générale',
    'channels' => 'Canaux Actifs',
    'session_settings' => 'Paramètres de Session',
    'session_timeout' => 'Délai d\'expiration de la Session (Minutes)',
    'session_timeout_help' => 'Temps avant qu\'un utilisateur doive se ré-authentifier (OTP). Par défaut : 15.',
    'save_config' => 'Enregistrer la Configuration',
    'enable_whatsapp' => 'Activer le Chatbot WhatsApp',
    'enable_sms' => 'Activer le Chatbot SMS',
    'enable_telegram' => 'Activer le Chatbot Telegram',
    'webhook_urls' => 'URLs Webhook',
    'webhook_urls_help' => 'Collez ces URLs dans Infobip, Twilio, Meta ou Telegram. Le paramètre ?secret= authentifie les webhooks entrants.',
    'webhook_secret_missing' => 'Définissez CHATBOT_WEBHOOK_SECRET dans le fichier .env du serveur.',
    'channel_help' => 'Basculez pour activer/désactiver les réponses automatisées pour ce canal.',
    
    // Keyword Management
    'keyword_management' => 'Gestion des Mots-clés',
    'add_keyword' => 'Ajouter un Mot-clé',
    'edit_keyword' => 'Modifier un Mot-clé',
    'keyword_list' => 'Liste des Mots-clés',
    'keyword' => 'Mot-clé Déclencheur',
    'language' => 'Langue',
    'response_message' => 'Message de Réponse',
    'response_placeholder' => 'Entrez la réponse automatisée ici...',
    'keyword_help' => 'Le mot qui démarre la conversation (ex. "Bonjour", "Menu", "Solde").',
    'portal_role' => 'Portail / Rôle utilisateur',
    'portal_role_help' => 'Seuls les identifiants correspondant à ce rôle seront acceptés après l\'envoi du mot-clé.',
    'actions' => 'Actions',
    'no_keywords' => 'Aucun mot-clé défini pour le moment.',
    
    // Messages
    'config_updated' => 'Paramètres du Chatbot mis à jour avec succès.',
    'keyword_created' => 'Mot-clé créé avec succès.',
    'keyword_updated' => 'Mot-clé mis à jour avec succès.',
    'keyword_deleted' => 'Mot-clé supprimé.',
    
    // --- CHATBOT INTERACTION RESPONSES (DYNAMIC) ---
    
    // Greetings & Errors
    'welcome_message' => "🎓 *Bienvenue sur E-Digitex !*\n\n📌 Veuillez entrer votre *ID Étudiant* ou *ID Personnel* pour vous connecter.",
    'default_keyword_response' => "👋 Bonjour ! Veuillez taper un mot-clé valide pour commencer (ex. 'Bonjour', 'Menu').",
    'unknown_state_error' => "⚠️ Erreur : État inconnu. Tapez 'Reset' pour recommencer.",
    'too_many_attempts' => "🚫 Trop de tentatives échouées. Fin de la session.",
    'id_not_found' => "❌ ID introuvable. Veuillez réessayer (Tentative :attempt/3).",
    'no_registered_phone' => "⚠️ Erreur : Aucun numéro de téléphone enregistré trouvé pour cet ID. Veuillez contacter l'administration.",
    'system_error' => "⚠️ Une erreur interne s'est produite. Veuillez réessayer plus tard.",
    
    // OTP
    'otp_sms_message' => "🔢 Votre code OTP E-Digitex est : :otp (Valide 5 min). Ne le partagez pas.",
    'otp_sent_notification' => "🔒 *Vérification Requise*\n\nUn OTP a été envoyé par SMS au numéro se terminant par *:phone*.\n\n👉 *Veuillez entrer le code ici pour continuer.*",
    'otp_expired' => "⏳ Le code OTP a expiré. Fin de la session.",
    'invalid_otp' => "❌ OTP invalide. Veuillez réessayer.",
    
    // Menus
    'login_success' => "✅ *Connexion Réussie !*\n\n👤 Bienvenue, *:name*.\n\n" .
                       "📜 *Menu Principal :*\n" .
                       "1️⃣ Solde & Finances 💰\n" .
                       "2️⃣ Devoirs & Travaux 📚\n" .
                       "3️⃣ Résultats & Bulletins 📊\n" .
                       "4️⃣ Menu de la Cantine 🍽️\n\n" .
                       "Tapez 'Menu' pour revoir cette liste ou 'Logout' pour quitter.",
                       
    'main_menu' => "🎓 *Bienvenue à :school (Digitex)*\n\n📚 *:student, :class, :year*\n\n1️⃣ Devoirs (TP/TD)\n2️⃣ Paiement\n3️⃣ Solde\n4️⃣ Bulletin de notes\n5️⃣ Frais Divers\n6️⃣ Activités & Calendrier\n7️⃣ Dérogation\n8️⃣ Mes Requêtes\n9️⃣ Générer QR de Récupération",
    'homework_list' => "📚 *Devoirs*\n:content",
    'no_homework' => "⚠️ Aucun devoir trouvé.",
    'balance_info' => "📊 *Résumé du Solde :*\n💰 Total des Frais : :total\n✅ Payé : :paid\n❌ Reste à payer : :due",
    'payment_method_menu' => "💰 Montant dû : :due\n💳 Total à payer : :total\n📌 Choisissez un Mode de Paiement :\n\n1️⃣ Carte Visa\n2️⃣ Mobile Money\n0️⃣ Annuler",
    'payment_link' => "✅ *💳 Visa Sélectionnée.*\n👉 Cliquez ici pour payer :\n🔗 :link",
    'mobile_money_instruction' => "💳 Mobile Money sélectionné.\n📌 Veuillez entrer votre numéro de téléphone.",
    'result_found' => "📄 Voici votre bulletin de notes.",
    'report_generated_local' => "📄 Rapport généré (Localhost) : :url", // Added
    'no_result_found' => "⚠️ Aucun résultat trouvé.",
    'misc_fees_list' => "💰 *Frais Scolaires :*\n:content",
    'no_fees_found' => "✅ Aucun frais divers trouvé.",
    'activities_list' => "📅 *Activités & Calendrier :*\n:content",
    'no_events_found' => "📅 Aucun événement à venir trouvé.",
    
    // Derogation & Requests
    'derogation_menu' => "📝 *Demande de Dérogation*\n\nChoisissez la durée :\n1️⃣ 7 jours\n2️⃣ 15 jours\n3️⃣ 20 jours\n4️⃣ 30 jours\n0️⃣ Annuler",
    'derogation_submitted' => "✅ Demande de dérogation pour :days jours soumise.\nTicket : *:ticket*",
    
    'request_menu' => "📝 *Type de Requête Spéciale :*\n\n1️⃣ Absence\n2️⃣ Retard\n3️⃣ Maladie\n4️⃣ Sortie Anticipée\n5️⃣ Autre\n0️⃣ Annuler",
    'request_search_prompt' => "🔍 *Recherche d'Étudiant*\n\nVeuillez entrer le Nom de l'Étudiant ou le Numéro d'Admission pour créer une requête pour :",
    'no_student_found_retry' => "⚠️ Étudiant introuvable. Veuillez réessayer ou taper *0* pour annuler.",
    'multiple_students_found' => "🔍 Plusieurs étudiants trouvés. Veuillez taper le *Numéro d'Admission* exact dans la liste ci-dessous :\n",
    'student_selected' => "✅ Sélectionné : *:name*",
    
    'request_reason_1' => "📝 *Raison de la Sortie Anticipée :*\n1️⃣ Médicale\n2️⃣ Urgence Familiale\n3️⃣ Autre\n0️⃣ Annuler", // Legacy? Kept for safety
    'request_submitted' => "✅ Requête soumise avec succès.\nTicket : *:ticket*",
    
    // QR
    'qr_verification' => "📲 *Génération de QR*\n\nVérification d'identité requise.\n➡️ Tapez *1* pour recevoir un OTP.\n➡️ Tapez *0* pour annuler.",
    'otp_sent' => "OTP envoyé au numéro enregistré. Entrez le code pour continuer.",
    'qr_success_menu' => "✅ Vérifié. Le QR Code a été envoyé.",
    'qr_caption' => "QR de récupération pour :student.\nValide pour 2 heures.",
    
    // Legacy / API
    'student_not_found' => 'Étudiant introuvable.',
    'not_enrolled' => 'L\'étudiant n\'est inscrit dans aucune classe active.',
    'no_homework_found' => 'Aucun devoir récent trouvé.',
    'latest_homework_retrieved' => 'Dernier devoir récupéré avec succès.',
    'validation_error' => 'Erreur de validation',
    'student_verified' => 'Étudiant vérifié avec succès.',
    'staff_verified' => 'Personnel vérifié avec succès.',
    'staff_not_found' => 'Dossier du personnel introuvable.',
    'no_active_session' => 'Aucune session académique active trouvée.',
    'summary_retrieved' => 'Résumé de l\'établissement récupéré avec succès.',
    'balance_retrieved' => 'Solde récupéré avec succès.',
    'result_generated' => 'Résultat généré avec succès.',
    'no_results_found' => 'Aucun résultat d\'examen trouvé pour cette année académique.',
    'no_session' => 'Aucune session active trouvée.',
    'otp_message' => 'Votre code de vérification est : :code',
    'qr_generated' => 'QR Code généré.',
    'qr_expired' => 'QR Code expiré.',
    'qr_already_used' => 'QR Code déjà utilisé.',
    'invalid_qr' => 'QR Code invalide.',
    'scan_success' => 'Scan réussi.',
    'teacher_pickup_alert' => 'ALERTE RÉCUPÉRATION : Un parent est au portail pour récupérer :student. Validé par :gate.',
    'fees_retrieved' => 'Frais récupérés.',
    'events_retrieved' => 'Événements récupérés.',
    
    // HEAD OFFICER MENU
    'admin_welcome_prompt' => "👤 *Connexion Admin*\n\nVeuillez entrer votre Nom d'utilisateur ou Code court :",
    'admin_id_invalid' => "❌ ID Admin invalide.",
    'admin_welcome' => "👤 *Bienvenue, :name.*\n\nVeuillez choisir une option :\n\n1️⃣ Tableau de bord global\n3️⃣ Classement financier\n5️⃣ Exporter le rapport\n6️⃣ Créer une requête étudiant\n0️⃣ Quitter",
    'admin_dashboard' => "📊 *Tableau de bord global*\n\n🏫 Écoles : *:schools*\n👨‍🎓 Total Étudiants : *:students*\n💵 Payé : *:paid_students* (*:paid_percentage*%)\n💰 Montant Payé : *:amount_paid*\n📈 Reste à payer : *:outstanding*\n🔮 Prévision : *:total_balance*",
    'admin_ranking_menu' => "🏆 *Type de Classement*\n\nChoisissez le type de classement :\n\n3️⃣1️⃣ Taux de paiement\n3️⃣2️⃣ Inscriptions (Nombre d'étudiants)\n\nTapez *00* pour le Menu Principal.",
    'admin_export_menu' => "📁 *Exporter les Rapports*\n\nChoisissez le rapport à exporter :\n\n1️⃣ Global (Toutes les écoles)\n2️⃣ Par École\n\nTapez *00* pour le Menu Principal.",
    'export_ready' => "✅ Exportation prête. Envoi du fichier...",
    'export_failed' => "Désolé, impossible de générer le fichier.",

    // Errors & Status
    'invalid_option' => "⚠️ Option invalide. Veuillez réessayer.",
    'session_ended' => "Votre session est terminée. Tapez 'Digitex' ou 'Admin' pour recommencer.",
    'unauthorized' => "⛔ Accès non autorisé.",
    'attempt_count' => "(Tentative :count/3)",
    'error_occurred' => "⚠️ Une erreur s'est produite. Veuillez réessayer.",
    
    // In your resources/lang/fr/chatbot.php
    'financial_restriction_msg' => 'Accès refusé. Vous avez un solde impayé de :amount. Veuillez régler votre compte pour télécharger les résultats académiques.',
    'no_results_found' => 'Aucune note d\'examen n\'a encore été publiée pour vous dans la session académique en cours.',
    'keywords_not_found' => 'Aucun mot-clé configuré pour le moment. Veuillez contacter l\'administration.',
    'student_id_invalid ' => 'L\'ID Étudiant fourni est invalide. Veuillez réessayer.',
];