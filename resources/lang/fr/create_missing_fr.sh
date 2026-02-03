#!/bin/bash

# Script to generate the 10 missing French language files
echo "Generating the 10 missing French language files..."

# 1. invoice.php
cat << 'EOF' > invoice.php
<?php

return [
    'page_title' => 'Factures & Paiements',
    'generate_invoices' => 'Générer des factures',
    'generate_subtitle' => 'Assigner des frais à tous les élèves d\'une section spécifique',
    'invoice_details' => 'Détails de la facture',
    
    'no_active_session' => 'Aucune session académique active trouvée.',
    'no_students_found' => 'Aucun élève actif trouvé dans cette classe.',
    'no_students_found_for_mode' => 'Aucun élève trouvé correspondant au mode de paiement : :mode',
    'success_generated' => 'Factures générées avec succès.',
    'success_generated_count' => ':count factures générées avec succès.',
    
    'invoice_list' => 'Liste des factures',
    'invoice_number' => 'Facture #',
    'student' => 'Élève',
    'issue_date' => 'Date d\'émission',
    'due_date' => 'Date d\'échéance',
    'total' => 'Total',
    'paid' => 'Payé',
    'status' => 'Statut',
    'action' => 'Action',
    
    'configuration' => 'Configuration',
    'target_grade' => 'Niveau scolaire cible', 
    'target_section' => 'Section cible',
    'select_grade' => 'Sélectionner le niveau',
    'select_section' => 'Sélectionner la section',
    'select_grade_first' => 'Sélectionner le niveau d\'abord',
    
    'select_students' => 'Sélectionner les élèves',
    'select_all' => 'Tout sélectionner',
    'search_student' => 'Rechercher le nom de l\'élève...',
    'select_class_first_msg' => 'Veuillez sélectionner une section de classe ci-dessus.',
    'students_selected_count' => ':count élèves sélectionnés',
    'students_selected_suffix' => 'élèves sélectionnés',
    
    'select_fees' => 'Sélectionner les frais',
    'search_fees' => 'Rechercher des frais...',
    'fees_will_load' => 'Les frais s\'afficheront ici.',
    'fee_bundle_help' => 'Cochez plusieurs éléments pour regrouper les frais.',
    'fee_help' => 'Les factures seront générées pour tous les élèves actifs de la section sélectionnée.',
    
    'class_fee_overview' => 'Aperçu des frais de classe (Référence)',
    'fee_name' => 'Nom du frais',
    'fee_type' => 'Type',
    'amount' => 'Montant',
    'mode' => 'Mode',
    'order' => 'Ordre',
    'frequency' => 'Fréquence',

    'status_unpaid' => 'Non payé',
    'status_partial' => 'Partiel',
    'status_paid' => 'Payé',
    'status_overdue' => 'En retard',
    
    'generate_btn' => 'Générer les factures',
    'processing' => 'Traitement...',
    'checking' => 'Vérification...',
    'pay' => 'Payer',
    'view' => 'Voir',
    'delete' => 'Supprimer',
    'print' => 'Imprimer',
    'download_pdf' => 'Télécharger PDF',
    'pay_now' => 'Payer maintenant',
    'yes_generate' => 'Oui, générer quand même',
    
    'no_fees_found' => 'Aucune structure de frais disponible. Veuillez d\'abord créer des frais.',
    'no_sections_found' => 'Aucune section trouvée',
    'no_active_students' => 'Aucun élève actif trouvé dans cette classe.',
    'success_deleted' => 'Facture supprimée avec succès.',
    'error_delete_paid' => 'Impossible de supprimer une facture ayant des paiements associés. Veuillez d\'abord supprimer les paiements.',
    'success' => 'Succès',
    'error' => 'Erreur',
    'warning' => 'Avertissement',
    'error_occurred' => 'Une erreur est survenue.',
    'unexpected_error' => 'Une erreur inattendue est survenue.',
    'loading' => 'Chargement...',
    'error_loading' => 'Erreur lors du chargement des données',
    'error_loading_students' => 'Erreur lors du chargement des élèves',
    'error_loading_fees' => 'Erreur lors du chargement des frais',
    'select_student_warning' => 'Veuillez sélectionner au moins un élève.',
    'select_fee_warning' => 'Veuillez sélectionner au moins une structure de frais.',
    'duplicate_warning' => 'Attention : :count factures en double détectées.',
    'duplicate_warning_title' => 'Avertissement de doublon',
    'no_invoices_generated_error' => 'Aucune facture n\'a été générée. :count élèves ont été ignorés.',
    'skipped_count_msg' => '(:count ignorés)',
    'deselect_all' => 'Tout désélectionner',
    'discount_scholarship' => 'Remise / Bourse',
    'fixed' => 'Fixe',
    
    'bill_to' => 'Facturé à',
    'from' => 'De',
    'to' => 'À',
    'session' => 'Session',
    'date' => 'Date',
    'status_label' => 'Statut',
    'description' => 'Description',
    'subtotal' => 'Sous-total',
    'paid_to_date' => 'Payé à ce jour',
    'balance_due' => 'Solde dû',
    'thank_you' => 'Merci pour votre confiance.',
    'authorized_signature' => 'Signature autorisée',
    'item_description' => 'Description de l\'article',
    'cost' => 'Coût',
    'paid_amount' => 'Montant payé',
    'payment_history' => 'Historique des paiements',
    'transaction_id' => 'ID Transaction',
    'method' => 'Méthode',
    'recorded_by' => 'Enregistré par',
    'no_payments_found' => 'Aucun paiement enregistré pour le moment.',
];
EOF

# 2. locations.php
cat << 'EOF' > locations.php
<?php

return [
    'country' => 'Pays',
    'select_country' => 'Sélectionner le pays',
    'state' => 'État / Province',
    'select_state' => 'Sélectionner l\'état',
    'city' => 'Ville / Commune',
    'select_city' => 'Sélectionner la ville/commune',
    'commune' => 'Commune / Lieu',
    'select_commune' => 'Sélectionner la commune',
    'address' => 'Adresse',
    
    'country_required' => 'Le champ pays est requis.',
    'state_required' => 'Le champ état/province est requis.',
    'city_required' => 'Le champ ville est requis.',
    'commune_required' => 'Le champ commune est requis.',
];
EOF

# 3. login.php
cat << 'EOF' > login.php
<?php

return [
    'page_title' => 'Connexion | Système E-Digitex',
    'welcome_back' => 'Connectez-vous à votre compte',
    'email_label' => 'E-mail, Nom d\'utilisateur ou ID',
    'password_label' => 'Mot de passe',
    'email_placeholder' => 'bonjour@exemple.com',
    'password_placeholder' => 'Entrez votre mot de passe',
    'remember_me' => 'Se souvenir de moi',
    'forgot_password' => 'Mot de passe oublié ?',
    'submit_btn' => 'Se connecter',
    
    'forgot_password_title' => 'Mot de passe oublié | E-Digitex',
    'reset_password_header' => 'Réinitialiser le mot de passe',
    'forgot_password_desc' => 'Entrez votre adresse e-mail et nous vous enverrons un lien de réinitialisation.',
    'send_reset_link' => 'Envoyer le lien de réinitialisation',
    'back_to_login' => 'Retour à la connexion',

    'error_title' => 'Erreur d\'authentification',
    'success_title' => 'Succès',
    'session_expired' => 'Votre session a expiré. Veuillez vous reconnecter.',
];
EOF

# 4. marks.php
cat << 'EOF' > marks.php
<?php

return [
    'page_title' => 'Notes d\'examen',
    'messages' => [
        'success_save' => 'Notes enregistrées avec succès.',
        'unauthorized' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
        'missing_fields' => 'Des champs obligatoires sont manquants.',
        'exam_not_found' => 'Examen non trouvé.',
        'exceeds_max' => 'La note pour l\'ID élève :id dépasse la limite maximale de :max.',
        'not_enrolled' => 'Vous n\'êtes inscrit dans aucune classe active.',
    ],
    'enter_marks' => 'Saisir les notes',
    'manage_subtitle' => 'Saisir les notes des élèves par matière',
    
    'select_criteria' => 'Sélectionner les critères',
    'select_exam' => 'Sélectionner l\'examen',
    'select_grade' => 'Sélectionner le niveau',
    'select_section' => 'Sélectionner la section',
    'select_subject' => 'Sélectionner la matière',
    
    'grade' => 'Niveau',
    'section' => 'Section',
    'section_option' => 'Section / Option',
    'subject' => 'Matière',
    'teacher' => 'Enseignant',
    'exam' => 'Examen',
    'date' => 'Date',
    'total_marks' => 'Note Totale',
    'pass_marks' => 'Note de Passage',
    'total_students' => 'Total élèves',
    'award_list' => 'Palmarès',
    'summary' => 'Résumé',
    'present' => 'Présent',
    'absent' => 'Absent',
    'teacher_sign' => 'Signature de l\'enseignant',
    
    'student_list' => 'Liste des élèves',
    'student_name' => 'Nom de l\'élève',
    'admission_no' => 'N° d\'admission',
    'marks_obtained' => 'Notes obtenues',
    'status' => 'Statut',
    'is_absent' => 'Absent ?',
    'search_student' => 'Rechercher un élève...',
    'auto_save_info' => 'N\'oubliez pas d\'enregistrer vos modifications.',
    
    'save_marks' => 'Enregistrer les notes',
    'load_students' => 'Chargement des élèves...',
    'validation_error' => 'Erreur de validation',
    
    'pass' => 'Réussite',
    'fail' => 'Échec',
];
EOF

# 5. modules.php
cat << 'EOF' > modules.php
<?php

return [
    'page_title' => 'Modules',
    'messages' => [
        'success_create' => 'Module créé avec succès.',
        'success_update' => 'Module mis à jour avec succès.',
        'success_delete' => 'Module supprimé avec succès.',
    ],
    'add_module' => 'Ajouter un module',
    'edit_module' => 'Modifier le module',
    'module_name' => 'Nom du module',
    'actions' => 'Actions',
    'name' => 'Nom',
    'slug' => 'Slug',
    'add_module_button' => 'Ajouter le module',
    'update_module_button' => 'Mettre à jour le module',
    'delete_confirmation_title' => 'Êtes-vous sûr ?',
    'delete_confirmation_text' => 'Ce module sera supprimé !',
    'delete_confirm_button' => 'Oui, supprimer !',
    'delete_cancel_button' => 'Annuler',
    'success_message' => 'Module enregistré avec succès !',
    'edit_button' => 'Modifier',
    'delete_button' => 'Supprimer',

    'add_permission' => 'Ajouter une permission',
    'edit_permission' => 'Modifier la permission',
    'permission_name' => 'Nom de la permission',
    'add_permission_button' => 'Ajouter la permission',
    'update_permission_button' => 'Mettre à jour la permission',
    'delete_permission_text' => 'Cette permission sera supprimée !',
    'permissions_page_title' => 'Permissions',
    'permission_messages' => [
        'success_create' => 'Permission créée avec succès.',
        'success_update' => 'Permission mise à jour avec succès.',
        'success_delete' => 'Permission supprimée avec succès.',
    ],
    'role_permissions_page_title' => 'Permissions des rôles',
    'permission_assigned_successfully' => 'Permissions assignées avec succès.',
];
EOF

# 6. notice.php
cat << 'EOF' > notice.php
<?php

return [
    'page_title' => 'Tableau d\'affichage',
    'notice_list' => 'Liste des annonces',
    'add_notice' => 'Ajouter une annonce',
    'edit_notice' => 'Modifier l\'annonce',
    'manage_subtitle' => 'Gérer les annonces du système et de l\'école',
    
    'title' => 'Titre',
    'content' => 'Contenu',
    'type' => 'Type',
    'audience' => 'Public cible',
    'published_at' => 'Date de publication',
    'status' => 'Statut',
    'created_by' => 'Créé par',
    
    'info' => 'Information',
    'warning' => 'Avertissement',
    'urgent' => 'Urgent',
    'all' => 'Tout le monde',
    'staff' => 'Personnel uniquement',
    'student' => 'Élèves uniquement',
    'parent' => 'Parents uniquement',
    'published' => 'Publié',
    'draft' => 'Brouillon',

    'success_create' => 'Annonce créée avec succès.',
    'success_update' => 'Annonce mise à jour avec succès.',
    'success_delete' => 'Annonce supprimée avec succès.',
    'delete_warning' => 'Êtes-vous sûr de vouloir supprimer cette annonce ?',
    'yes_delete' => 'Oui, supprimer',
    'cancel' => 'Annuler',
    'notice_board' => 'Tableau d\'affichage',
    'latest_announcements' => 'Dernières annonces et mises à jour',
    'read_more' => 'Lire la suite',
    'no_notices' => 'Aucune annonce disponible pour le moment.',
    'details' => 'Détails',
    'back' => 'Retour',
    'posted_by' => 'Publié par',
    'student_profile_not_found' => 'Profil élève non trouvé lié à votre compte.',
    'unauthorized' => 'Vous n\'êtes pas autorisé à voir cette annonce.',
];
EOF

# 7. payment.php
cat << 'EOF' > payment.php
<?php

return [
    'page_title' => 'Enregistrer un paiement',
    'record_payment' => 'Enregistrer le paiement',
    'invoice_no' => 'Facture',
    'payment_details' => 'Détails du paiement',
    
    'student_name' => 'Nom de l\'élève',
    'total_amount' => 'Montant total',
    'remaining_balance' => 'Solde restant',
    'payment_amount' => 'Montant du paiement',
    'payment_date' => 'Date du paiement',
    'method' => 'Méthode',
    'notes' => 'Notes',
    
    'cash' => 'Espèces',
    'bank_transfer' => 'Virement bancaire',
    'card' => 'Carte',
    'online' => 'En ligne',
    
    'confirm_payment' => 'Confirmer le paiement',
    
    'success' => 'Succès !',
    'error' => 'Erreur',
    'error_occurred' => 'Une erreur est survenue lors du traitement du paiement.',
    'success_recorded' => 'Paiement enregistré avec succès.',
    'exceeds_balance' => 'Le montant dépasse le solde restant.',
    'previous_installment_pending_error' => 'Paiement refusé. Une tranche précédente pour cet élève est toujours en attente.',
    
    'confirm_title' => 'Confirmer le paiement',
    'confirm_message' => 'Voulez-vous confirmer le paiement pour <strong>:name</strong> ?',
    'amount_to_pay' => 'Montant à payer',
    'password_label' => 'Entrez le mot de passe Admin pour valider',
    'password_placeholder' => 'Mot de passe',
    'validate_pay_btn' => 'Valider & Payer',
    'password_required' => 'Le mot de passe est requis',

    'sms_template' => 'Bonjour :name, paiement de :amount reçu pour :school. Solde restant : :balance. Merci.',
];
EOF

# 8. payroll.php
cat << 'EOF' > payroll.php
<?php

return [
    'page_title' => 'Gestion de la paie',
    'salary_structure' => 'Structure salariale',
    'setup_salary' => 'Configurer le salaire',
    'manage_salaries' => 'Gérer les salaires du personnel',
    'payroll_history' => 'Historique de la paie',
    'generate_payroll' => 'Générer la paie',
    
    'staff_name' => 'Nom du personnel',
    'staff' => 'Personnel',
    'designation' => 'Désignation',
    'department' => 'Département',
    'base_salary' => 'Salaire de base',
    'allowances' => 'Allocations',
    'deductions' => 'Déductions',
    'net_salary' => 'Salaire net',
    'net_pay' => 'Paie nette',
    'actions' => 'Actions',
    'action' => 'Action',
    'period' => 'Période',
    'work_units' => 'Unités de travail',
    'earnings' => 'Gains',
    'amount' => 'Montant',
    'staff_id' => 'ID Personnel',
    'name' => 'Nom',
    'join_date' => 'Date d\'embauche',
    'authorized_sign' => 'Signature autorisée',
    
    'select_staff' => 'Sélectionner le personnel',
    'base_configuration' => 'Configuration de base',
    'payment_basis' => 'Base de paiement',
    'monthly' => 'Mensuel',
    'hourly' => 'Horaire',
    'monthly_desc' => 'Mensuel (Salaire fixe)',
    'hourly_desc' => 'Horaire (Paie par heure)',
    'hourly_rate' => 'Taux horaire',
    'hourly_rate_label' => 'Taux horaire (Par heure)',
    'base_salary_label' => 'Salaire de base (Fixe mensuel)',
    'select_month' => 'Sélectionner le mois',
    'select_year' => 'Sélectionner l\'année',
    'note_title' => 'Note :',
    
    'help_hourly' => 'Personnel payé selon les heures de présence enregistrées.',
    'help_monthly' => 'Personnel payé un montant fixe. Les absences sont déduites.',
    'allowance_help' => 'Montants fixes ajoutés à chaque cycle (ex: Logement, Transport).',
    'deduction_help' => 'Montants fixes déduits (ex: Taxes, Assurance).',
    'deduction_note' => 'Note : Les jours d\'absence sont déduits automatiquement.',
    'configure_rules' => 'Configurer les règles pour',
    'generate_note' => 'La génération calculera les salaires basés sur les <strong>Présences</strong> et les <strong>Structures salariales</strong>.',
    'back_to_list' => 'Retour à la liste',
    
    'allowance_label' => 'Libellé de l\'allocation',
    'allowance_amount' => 'Montant',
    'deduction_label' => 'Libellé de la déduction',
    'deduction_amount' => 'Montant',
    'label_placeholder' => 'Libellé',
    'amount_placeholder' => '0.00',
    'add_row' => 'Ajouter une ligne',
    'remove' => 'Retirer',
    'total_allowance' => 'Total Allocations',
    'total_deduction' => 'Total Déductions',
    'gross_salary' => 'Salaire brut',
    'total_earnings' => 'Gains totaux',
    'lop' => 'Perte de salaire (Absences)',
    
    'month' => 'Mois',
    'year' => 'Année',
    'generate_btn' => 'Traiter la paie',
    'total_days' => 'Total jours',
    'present' => 'Présent',
    'absent' => 'Absent',
    'status' => 'Statut',
    'paid' => 'Payé',
    'generated' => 'Généré',
    'payslip' => 'Fiche de paie',
    'download_payslip' => 'Télécharger fiche de paie',
    'hourly_short' => 'Hrs',
    'days_short' => 'Jrs',
    'no_records' => 'Aucun enregistrement trouvé. Générez-en un ci-dessus.',
    
    'save_structure' => 'Enregistrer la structure',
    'save_changes' => 'Enregistrer les modifications',

    'success_created' => 'Structure salariale créée avec succès.',
    'success_updated' => 'Structure salariale mise à jour.',
    'success_generated' => 'Paie générée avec succès.',
    'success_generated_count' => 'Paie générée avec succès pour :count membres du personnel.',
    'no_staff_found' => 'Aucun personnel actif trouvé.',
];
EOF

# 9. profile.php
cat << 'EOF' > profile.php
<?php

return [
    'page_title' => 'Profil',
    'my_profile' => 'Mon profil',
    'subtitle' => 'Gérez les détails de votre compte personnel',
    'dashboard' => 'Tableau de bord',
    'profile' => 'Profil',
    'user' => 'Utilisateur',
    'email' => 'E-mail',
    'joined' => 'Inscrit le',
    'status' => 'Statut',
    'active' => 'Actif',
    'profile_settings' => 'Paramètres du profil',
    'tab_overview' => 'Aperçu',
    'tab_edit_profile' => 'Modifier le profil',
    'tab_security' => 'Sécurité',
    'account_information' => 'Informations du compte',
    'full_name' => 'Nom complet',
    'phone' => 'Numéro de téléphone',
    'address' => 'Adresse',
    'not_set' => 'Non défini',
    'academic_details' => 'Détails académiques',
    'admission_no' => 'Numéro d\'admission',
    'update_profile' => 'Mettre à jour le profil',
    'upload_hint' => 'Cliquez sur l\'icône caméra pour télécharger. Taille max 2Mo.',
    'save_changes' => 'Enregistrer les modifications',
    'change_password' => 'Changer le mot de passe',
    'current_password' => 'Mot de passe actuel',
    'new_password' => 'Nouveau mot de passe',
    'confirm_password' => 'Confirmer le nouveau mot de passe',
    'update_password' => 'Mettre à jour le mot de passe',
    
    'update_success' => 'Profil mis à jour avec succès.',
    'password_update_success' => 'Mot de passe modifié avec succès.',
];
EOF

# 10. promotion.php
cat << 'EOF' > promotion.php
<?php

return [
    'page_title' => 'Promotion des élèves',
    'manage_subtitle' => 'Promouvoir les élèves vers la session académique suivante',
    'messages' => [
        'success_promote' => 'Élèves promus avec succès.',
    ],
    
    'select_criteria' => 'Sélectionner les critères de promotion',
    'promote_from' => 'Promouvoir de (Actuel)',
    'promote_to' => 'Promouvoir vers (Cible)',
    'current_session' => 'Session actuelle',
    'current_class' => 'Classe actuelle',
    'target_session' => 'Session cible',
    'target_class' => 'Classe cible',
    
    'select_session' => 'Sélectionner la session',
    'select_class' => 'Sélectionner la classe',
    
    'student_list' => 'Liste des élèves éligibles',
    'student_name' => 'Nom de l\'élève',
    'admission_no' => 'N° d\'admission',
    'current_result' => 'Résultat actuel',
    'action' => 'Promouvoir',
    'select_all' => 'Tout sélectionner',
    
    'promote_students' => 'Promouvoir les élèves sélectionnés',
    'no_students_found' => 'Aucun élève actif trouvé dans la classe sélectionnée.',
    
    'pending' => 'En attente',
    'eligible' => 'Éligible',

    'missing_info' => 'Informations manquantes',
    'select_target_warning' => 'Veuillez sélectionner la session et la classe cibles.',
    'no_selection' => 'Aucune sélection',
    'select_student_warning' => 'Veuillez sélectionner au moins un élève à promouvoir.',
    'processing' => 'Traitement...',
    'error_occurred' => 'Une erreur est survenue',
    'success' => 'Succès',
];
EOF

echo "Done! The 10 missing French language files have been generated."