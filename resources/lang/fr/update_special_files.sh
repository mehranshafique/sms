#!/bin/bash

# Script to generate the 4 advanced/updated French language files
echo "Generating Finance, LMD, SMS Template, and Configuration files..."

# 1. finance.php (Updated with Statements & Misc Fees)
cat << 'EOF' > finance.php
<?php

return [
    'page_title' => 'Gestion Financière',
    'fee_structure_title' => 'Structures des frais',
    'fee_type_title' => 'Types de frais',
    'manage_subtitle' => 'Gérer les frais, les factures et les paiements',
    'manage_types_subtitle' => 'Définir les types de frais (Scolarité, Bus, Labo)',
    
    // Fee Structures
    'fee_list' => 'Liste des structures de frais',
    'add_fee' => 'Ajouter une structure',
    'fee_name' => 'Nom du frais',
    'fee_type' => 'Type de frais',
    'amount' => 'Montant',
    'frequency' => 'Fréquence',
    'grade_level' => 'Niveau scolaire (Facultatif)',
    'select_type' => 'Sélectionner le type',
    'select_grade' => 'Sélectionner le niveau',
    'mode' => 'Mode de paiement',
    'parent_fee_name' => 'Nom du frais parent',
    
    // Fee Types
    'fee_type_list' => 'Liste des types de frais',
    'add_type' => 'Ajouter un type',
    'edit_type' => 'Modifier le type',
    'type_name' => 'Nom du type',
    'description' => 'Description',
    'status' => 'Statut',
    
    // Frequencies
    'one_time' => 'Unique',
    'monthly' => 'Mensuel',
    'termly' => 'Trimestriel',
    'yearly' => 'Annuel',

    // Messages
    'success_create' => 'Structure de frais créée avec succès.',
    'success_create_type' => 'Type de frais créé avec succès.',
    'success_update_type' => 'Type de frais mis à jour avec succès.',
    'success_delete_type' => 'Type de frais supprimé avec succès.',
    'success_update' => 'Structure de frais mise à jour avec succès.',
    'success_delete' => 'Structure de frais supprimée avec succès.',
    'no_active_session' => 'Aucune session académique active trouvée.',
    'global_fee_missing_error' => 'Un Frais Annuel Global doit être créé pour ce niveau avant d\'ajouter des tranches.',
    'installment_cap_error' => 'Le total des tranches (:total) ne peut pas dépasser le Frais Annuel Global (:limit).',
    'error_occurred' => 'Une erreur est survenue',
    'unexpected_error' => 'Une erreur inattendue est survenue.',
    
    // Safety Messages
    'global_amount_too_low' => 'Impossible de réduire le Frais Global en dessous du total des tranches existantes (:total).',
    'cannot_delete_global_with_installments' => 'Impossible de supprimer le Frais Global car des tranches actives existent.',
    'duplicate_global_config_error' => 'Ce niveau possède déjà un Frais Global pour ce type de frais.',

    // Buttons
    'save' => 'Enregistrer',
    'cancel' => 'Annuler',
    'action' => 'Action',
    'edit_fee' => 'Modifier',
    'view_details' => 'Voir détails',
    'close' => 'Fermer',
    'yes_delete' => 'Oui, supprimer !',
    
    'are_you_sure' => 'Êtes-vous sûr ?',
    'delete_warning' => 'Cette action est irréversible !',
    'error' => 'Erreur',
    'success' => 'Succès',
    'deleted' => 'Supprimé !',
    
    // Class Report
    'class_financial_report' => 'Rapport financier de la classe',
    'report_subtitle' => 'Aperçu financier détaillé par classe',
    'select_class_filter' => 'Sélectionner une classe pour le rapport',
    'select_class' => 'Sélectionner la classe',
    'choose_class' => 'Choisir une classe...',
    'generate_report' => 'Générer le rapport',
    'financial_overview' => 'Aperçu financier',
    'totals' => 'TOTAUX',
    'student_identity' => 'Identité de l\'élève',
    'parent_guardian' => 'Parent / Tuteur',
    'today_payment' => 'Paiement du jour',
    'cumulative_paid' => 'Cumul payé',
    'remaining_fees' => 'Frais restants',
    'annual_fee' => 'Frais annuels',
    'previous_debt' => 'Dette précédente',
    'no_data_found' => 'Aucune donnée trouvée.',
    'payment_mode' => 'Mode de paiement',
    'global' => 'Global',
    'installment' => 'Tranche',
    'class_section' => 'Section',
    'optional' => 'Facultatif',
    'all_sections' => 'Toutes les sections',
    'installment_order' => 'Ordre de tranche',
    'sequence_order_hint' => 'Numéro de séquence (1 pour la première, etc.)',
    'no_financial_records_found' => 'Aucun dossier financier trouvé.',

    // Dashboard
    'student_finance_dashboard' => 'Tableau de bord financier',
    'back_to_profile' => 'Retour au profil',
    'fee_management' => 'Gestion des frais',
    'global_overview' => 'Aperçu global',
    'annual_fee_contract' => 'Frais annuels (Contrat)',
    'annual_fee_gross' => 'Frais annuels (Brut)',
    'discount_applied' => 'Remise appliquée',
    'total_paid_global' => 'Total payé (Global)',
    'total_remaining_year' => 'Total restant (Année)',
    'installment_label' => 'Tranche',
    'paid' => 'Payé',
    'remaining' => 'Restant',
    'locked_msg' => 'VERROUILLÉ ! La tranche précédente doit être réglée d\'abord.',
    'payment_for' => 'Paiement pour : :label',
    'already_paid' => 'Déjà payé',
    'remaining_due' => 'Reste à payer',
    'pay_now' => 'Payer maintenant',
    'fully_settled' => 'Cette tranche est réglée.',
    'context_history' => 'Historique',
    'current_installment' => 'Tranche actuelle',
    'reduce_global_msg' => 'Ce paiement réduira votre solde global à :',
    'student_not_enrolled' => 'Élève non inscrit.',
    'installment_prefix' => 'Tranche',

    // Payment History
    'payment_history' => 'Historique des paiements',
    'date' => 'Date',
    'transaction_id' => 'ID Transaction',
    'method' => 'Méthode',
    'recorded_by' => 'Enregistré par',
    'no_payments_found' => 'Aucun paiement enregistré.',
    'fixed' => 'Fixe',

    // Balance Overview
    'student_balances' => 'Soldes des élèves',
    'balance_overview' => 'Aperçu des soldes',
    'class_wise_breakdown' => 'Répartition par classe',
    'all_classes' => 'Toutes les classes',
    'class_name' => 'Classe',
    'students_count' => 'Élèves',
    'paid_students' => 'Ayant payé',
    'total_invoiced' => 'Total facturé',
    'total_collected' => 'Total perçu',
    'total_outstanding' => 'Total dû',
    'class_details' => 'Détails de la classe',
    'loading_details' => 'Chargement...',
    'no_fee_structures_class' => 'Aucune structure de frais définie.',
    'view_dashboard' => 'Voir tableau de bord',
    'error_loading' => 'Erreur de chargement.',
    'paid_amount' => 'Montant payé',
    'due_amount' => 'Montant dû',
    'status_partial' => 'Partiel',
    'status_unpaid' => 'Non payé',
    'status_overdue' => 'En retard',
    'outstanding' => 'À recouvrer',
    
    // Statements & Misc
    'student_statement' => 'Relevé financier de l\'élève',
    'transaction_history' => 'Historique des transactions',
    'misc_fees' => 'Frais divers',
    'tab_info_misc' => 'Frais uniques comme Uniformes, Cartes ID, Livres, etc.',
    'total_paid' => 'Total Payé',
    'outstanding_balance' => 'Solde à payer',
    'debit' => 'Débit',
    'credit' => 'Crédit',
    'reference' => 'Référence',
    'export_pdf' => 'Exporter PDF',
    'type' => 'Type',
    'description' => 'Description',
    
    'tab_info_global' => 'Élèves en mode "Global" (Paiement unique).',
    'tab_info_installment' => 'Élèves en mode "Tranches".',
    'summary' => 'Résumé Total',
    'tab_info_summary' => 'Résumé financier cumulé de la classe.',

    'fee_collection_analysis' => 'Analyse du recouvrement',
    'pending_vs_collected' => 'À recouvrer vs Perçu',
];
EOF

# 2. lmd.php (New University Module)
cat << 'EOF' > lmd.php
<?php

return [
    'units_page_title' => 'Unités d\'Enseignement (UE)',
    'programs_page_title' => 'Programmes & Filières',
    'create_program' => 'Créer un Programme',
    'edit_program' => 'Modifier le Programme',
    'program_name' => 'Nom du Programme (ex: Licence Info)',
    'program_code' => 'Code du Programme',
    'total_semesters' => 'Total Semestres',
    'duration_years' => 'Durée (Années)',
    
    'create_unit' => 'Créer une Unité (UE)',
    'edit_unit' => 'Modifier l\'Unité',
    'unit_name' => 'Nom de l\'Unité',
    'code' => 'Code',
    'type' => 'Type',
    'semester' => 'Semestre',
    'credits' => 'Crédits',
    'fundamental' => 'Fondamentale',
    'transversal' => 'Transversale',
    'optional' => 'Optionnelle',
    
    'unit_saved' => 'Unité d\'enseignement enregistrée avec succès.',
    'unit_deleted' => 'Unité supprimée avec succès.',
    'program_saved' => 'Programme enregistré avec succès.',
    'program_deleted' => 'Programme supprimé avec succès.',
    'subjects_assigned' => 'Matières assignées à l\'UE avec succès.',
    
    // Transcript Terms
    'report_header' => 'Relevé de Notes Académique',
    'ue_title' => 'Unité d\'Enseignement (UE)',
    'ec_title' => 'Élément Constitutif (EC)',
    'grade' => 'Grade',
    'decision' => 'Décision',
    'admitted' => 'Admis',
    'adjourned' => 'Ajourné',
    'validated' => 'Validé (V)',
    'compensated' => 'Compensé (Cmp)',
    'failed' => 'Non Validé (NV)',
    'average' => 'Moyenne',
    'mention' => 'Mention',
    'credits_earned' => 'Crédits Acquis',
    'credits_attempted' => 'Crédits Tentés',
];
EOF

# 3. sms_template.php (New Module)
cat << 'EOF' > sms_template.php
<?php

return [
    'page_title' => 'Modèles SMS',
    'subtitle' => 'Personnaliser les messages de notification',
    
    // Table Headers
    'template_list' => 'Liste des modèles',
    'event_name' => 'Nom de l\'événement',
    'message_body' => 'Corps du message',
    'tags' => 'Balises disponibles',
    'status' => 'Statut',
    'action' => 'Action',
    'active' => 'Actif',
    'inactive' => 'Inactif',
    
    // Form & Modal
    'edit_template' => 'Modifier le modèle',
    'customize_help' => 'Vous pouvez personnaliser le message pour votre établissement.',
    'available_tags_label' => 'Balises disponibles',
    'click_to_copy' => 'Cliquez pour copier (optionnel)',
    'body_label' => 'Corps du message',
    'active_label' => 'Actif (Envoyer pour cet événement)',
    
    // Character Counter
    'characters' => 'caractères',
    'segments' => 'segment(s) SMS',
    
    // Messages
    'success_update' => 'Modèle mis à jour avec succès.',
    'success_override' => 'Modèle personnalisé et enregistré.',
    'success_saved' => 'Enregistré !',
    'error_update' => 'Impossible de mettre à jour le modèle.',
    'error_occurred' => 'Une erreur est survenue.',
    
    // Buttons
    'save_changes' => 'Enregistrer',
    'close' => 'Fermer',
    'edit' => 'Modifier',
];
EOF

# 4. configuration.php (Updated with Providers)
cat << 'EOF' > configuration.php
<?php

return [
    'page_title' => 'Configuration du Système',
    'subtitle' => 'Gérer les paramètres globaux et les intégrations',
    'sms_whatsapp_setup' => 'Configuration Communication',
    'active_sms_provider' => 'Fournisseur SMS Actif',
    'active_whatsapp_provider' => 'Fournisseur WhatsApp Actif',
    'sender_id' => 'ID Expéditeur',
    'api_key' => 'Clé API / Token',
    'base_url' => 'URL de base',
    'global_mode' => 'Mode Global',
    
    // Super Admin Controls
    'provider_control_title' => 'Contrôle de disponibilité des fournisseurs',
    'provider_control_desc' => 'Activer les fournisseurs disponibles pour la configuration individuelle des écoles.',
    'allowed_sms' => 'Fournisseurs SMS autorisés',
    'allowed_whatsapp' => 'Fournisseurs WhatsApp autorisés',
    'system_default_config' => 'Configuration système par défaut',
    'active_provider_config' => 'Configuration du fournisseur actif',
    'system_default_sms' => 'Fournisseur SMS par défaut du système',
    'system_default_whatsapp' => 'Fournisseur WhatsApp par défaut du système',
    'system_default_option' => 'Système par défaut (Crédits Digitex)',
    'my_sms_provider' => 'Mon fournisseur SMS actif',
    'api_credentials' => 'Identifiants API',

    // Provider Specifics
    'phone_number_id' => 'ID Numéro de téléphone',
    'business_account_id' => 'ID Compte Business',
    'access_token' => 'Jeton d\'accès (Token)',
    'account_sid' => 'Account SID',
    'auth_token' => 'Auth Token',
    'from_number' => 'Numéro d\'envoi (From)',
    'whatsapp_from' => 'Numéro WhatsApp (From)',
    'project_id' => 'ID Projet',
    'space_url' => 'URL de l\'espace',

    // Feedback
    'sms_settings_updated' => 'Paramètres de communication mis à jour.',
    'settings_saved' => 'Paramètres enregistrés avec succès.',
    'sms_sent_success' => 'SMS envoyé avec succès.',
    'whatsapp_sent_success' => 'Message WhatsApp envoyé avec succès.',
    'gateway_connection_error' => 'Impossible de se connecter à la passerelle.',
    'gateway_response_error' => 'La passerelle a renvoyé une erreur.',
    'credentials_missing' => 'Identifiants API manquants pour le fournisseur sélectionné.',
    'institution_not_found' => 'Contexte institutionnel manquant.',
    'insufficient_credits' => 'Crédits de message insuffisants.',
    
    'meta_credentials_missing' => 'Identifiants Meta Cloud API manquants.',
    'twilio_credentials_missing' => 'SID ou Token Twilio manquant.',
    'sw_credentials_missing' => 'Identifiants SignalWire manquants.',

    'smtp' => 'Configuration SMTP',
    'sms_sender' => 'ID Expéditeur SMS',
    'school_year' => 'Config Année Scolaire',
    'modules' => 'Modules Achetés',
    'sms_recharge' => 'Recharge SMS',
    'whatsapp_recharge' => 'Recharge Whatsapp',
    
    'mail_host' => 'Hôte Serveur Mail',
    'mail_port' => 'Port Serveur Mail',
    'mail_username' => 'Utilisateur Serveur Mail',
    'mail_password' => 'Mot de passe Serveur Mail',
    'mail_encryption' => 'Chiffrement',
    'mail_driver' => 'Pilote Mail',
    'mail_from_address' => 'Adresse E-mail Expéditeur',
    'mail_from_name' => 'Nom Expéditeur',
    'smtp_help' => 'Configurez les paramètres du serveur e-mail pour cette institution.',
    
    'test_email_connection' => 'Tester la connexion E-mail',
    'enter_test_email' => 'Entrez l\'e-mail du destinataire',
    'send_test_email' => 'Envoyer E-mail de test',
    'test_email_help' => 'Assurez-vous d\'enregistrer les modifications avant de tester.',

    'sender_id_placeholder' => 'ex: DIGITEX',
    'provider' => 'Fournisseur SMS',
    'provider_help' => 'Sélectionnez la passerelle.',
    
    'test_notifications' => 'Tester les notifications',
    'test_sms_title' => 'Test SMS',
    'test_whatsapp_title' => 'Test WhatsApp',
    'phone_number' => 'Numéro de téléphone',
    'phone_placeholder' => '+1234567890',
    'send_test_sms' => 'Envoyer SMS Test',
    'send_test_whatsapp' => 'Envoyer WhatsApp Test',
    'current_provider' => 'Utilise le fournisseur actuel',
    'whatsapp_provider' => 'Fournisseur WhatsApp',
    'api_credentials_warning' => 'Vérifiez que vos identifiants API sont corrects.',
    'sending' => 'Envoi en cours...',
    'failed_to_send' => 'Échec de l\'envoi du message.',
    'check_logs' => 'Vérifiez vos paramètres .env et les journaux.',
    'unknown_error' => 'Erreur inconnue',
    'failed' => 'Échec',

    'notification_settings' => 'Paramètres de notification',
    'notification_preferences' => 'Préférences de notification',
    'event_name' => 'Nom de l\'événement',
    'email_channel' => 'E-mail',
    'sms_channel' => 'SMS',
    'whatsapp_channel' => 'WhatsApp',
    'student_created' => 'Création Élève',
    'staff_created' => 'Création Personnel',
    'payment_received' => 'Paiement Reçu',
    'invoice_created' => 'Facture Créée',
    'institution_created' => 'Institution Créée',

    'academic_session' => 'Session Académique',
    'academic_start_date' => 'Date de début',
    'academic_end_date' => 'Date de fin',
    'school_hours' => 'Heures de cours',
    'school_start_time' => 'Heure début',
    'school_end_time' => 'Heure fin',
    
    'module_management' => 'Gestion des Modules',
    'module_name' => 'Nom du module',
    'status' => 'Statut',

    'recharge' => 'Recharger',
    'sms_purchased' => 'SMS Achetés',
    'whatsapp_purchased' => 'WhatsApp Achetés',
    'add_credits' => 'Ajouter des crédits',
    'balance' => 'Solde',
    'type' => 'Type',
    'enter_amount' => 'Entrez le montant',
    'recharge_success' => 'Crédits ajoutés avec succès.',
    'save_changes' => 'Enregistrer',
    'success' => 'Succès',
    'error' => 'Erreur',
    'saving' => 'Enregistrement...',
];
EOF

echo "Done! The 4 advanced French language files have been generated."