<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Spatie roles per menu profile (builtin keywords / empty pivot)
    |--------------------------------------------------------------------------
    */
    'menu_profile_default_roles' => [
        'teacher' => ['Teacher', 'Staff'],
        'school_admin' => ['School Admin'],
        'head_officer' => ['Head Officer', 'Super Admin'],
        'finance' => [],
        'super_admin' => ['Super Admin'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission fallback when pivot and default roles are empty
    |--------------------------------------------------------------------------
    */
    'menu_profile_permission_fallback' => [
        'finance' => ['payment.view', 'invoice.view'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global builtin keyword → menu_profile map
    |--------------------------------------------------------------------------
    */
    'builtin_keyword_menu_profiles' => [
        'digitex' => 'head_officer',
        'headoffice' => 'head_officer',
        'direction' => 'head_officer',
        'admin' => 'school_admin',
        'director' => 'school_admin',
        'directeur' => 'school_admin',
        'agent' => 'teacher',
        'teacher' => 'teacher',
        'enseignant' => 'teacher',
        'prof' => 'teacher',
        'staff' => 'teacher',
        'finance' => 'finance',
        'compta' => 'finance',
        'bonjour' => 'parent',
        'parent' => 'parent',
        'parents' => 'parent',
        'portail' => 'student',
        'student' => 'student',
        'eleve' => 'student',
        'etudiant' => 'student',
        'hello' => 'student',
        'hi' => 'student',
        'start' => 'student',
        'salut' => 'student',
        'menu' => 'student',
    ],
];
