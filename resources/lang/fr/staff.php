<?php

return [
    'page_title' => 'Gestion du Personnel',
    'messages' => [
        'success_create' => 'Personnel créé avec succès.',
        'success_update' => 'Personnel mis à jour avec succès.',
        'success_delete' => 'Personnel supprimé avec succès.',
        'error_create' => 'Une erreur est survenue lors de la création du personnel. Veuillez réessayer ou contacter le support.',
        'error_update' => 'Une erreur est survenue lors de la mise à jour du personnel. Veuillez réessayer ou contacter le support.',
    ],
    'index' => [
        'title' => 'Personnel',
        'add' => 'Ajouter du Personnel',

        'table' => [
            'serial' => '#',
            'employee_no' => 'Numéro d’employé',
            'user' => 'Utilisateur',
            'campus' => 'Campus',
            'designation' => 'Désignation',
            'department' => 'Département',
            'hire_date' => 'Date d\'embauche',
            'status' => 'Statut',
            'action' => 'Action',
        ],

        'confirm_delete_title' => 'Êtes-vous sûr ?',
        'confirm_delete_button' => 'Oui, supprimer !',
    ],

    'create' => [
        'title' => 'Ajouter du Personnel',
        'subtitle' => 'Remplissez les détails pour enregistrer un nouveau membre du personnel',

        'sections' => [
            'user_details' => 'Détails de l’utilisateur',
            'staff_details' => 'Détails du personnel',
        ],

        'fields' => [
            'name' => 'Nom',
            'email' => 'Email',
            'phone' => 'Téléphone',
            'password' => 'Mot de passe',
            'role' => 'Rôle',
            'address' => 'Adresse',
            'designation' => 'Désignation',
            'department' => 'Département',
            'hire_date' => 'Date d\'embauche',
            'status' => 'Statut',
        ],

        'placeholders' => [
            'name' => 'Nom complet',
            'email' => 'Email',
            'phone' => 'Téléphone (optionnel)',
            'password' => 'Mot de passe',
            'address' => 'Adresse',
            'designation' => 'Désignation',
            'department' => 'Département',
            'hire_date' => 'AAAA-MM-JJ',
        ],

        'status_options' => [
            'active' => 'Actif',
            'on_leave' => 'En congé',
            'terminated' => 'Terminé',
        ],

        'buttons' => [
            'save' => 'Enregistrer',
        ],
    ],

    'edit' => [
        'title' => 'Modifier le personnel',
        'subtitle' => 'Mettre à jour les informations du membre du personnel',

        'section_user' => 'Détails de l’utilisateur',
        'section_staff' => 'Détails du personnel',

        'name' => 'Nom',
        'email' => 'E-mail',
        'phone' => 'Téléphone',
        'password' => 'Mot de passe',
        'password_placeholder' => 'Laisser vide pour conserver le mot de passe actuel',
        'role' => 'Rôle',
        'address' => 'Adresse',

        'designation' => 'Poste',
        'department' => 'Département',
        'hire_date' => 'Date d’embauche',
        'hire_date_placeholder' => 'AAAA-MM-JJ',
        'status' => 'Statut',

        'update_btn' => 'Mettre à jour le personnel',

        'status_active' => 'Actif',
        'status_on_leave' => 'En congé',
        'status_terminated' => 'Licencié',
    ],

];
