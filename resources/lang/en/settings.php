<?php

return [
    'page_title' => 'General Settings',
    'settings_management' => 'Settings Management',
    'subtitle' => 'Configure system rules and preferences',
    
    'messages' => [
        'update_success' => 'Settings updated successfully.',
    ],

    'select_institution_first' => 'Please select an institution context first.',
    'save_changes' => 'Save Changes',
    
    // Tabs
    'tab_attendance' => 'Attendance',
    'tab_exams' => 'Exams',
    'tab_general' => 'General',

    // Attendance Settings
    'attendance_settings' => 'Attendance Configuration',
    'lock_attendance' => 'Block Attendance Marking',
    'lock_help' => 'If enabled, no new attendance can be marked by teachers for any date.',
    'grace_period' => 'Modification Grace Period (Days)',
    'grace_help' => 'Number of past days teachers are allowed to add or edit attendance. Set to 0 to allow only today.',
    
    // Exam Settings
    'exam_settings' => 'Exam Configuration',
    'lock_exams' => 'Block Exam Creation/Updates',
    'lock_exams_help' => 'If enabled, regular users (teachers) cannot create or update exams.',
    'exam_grace_period' => 'Modification Grace Period (Days)',
    'exam_grace_help' => 'Number of past days regular users are allowed to edit exam details after the start date.',

    'enabled' => 'Enabled (Blocked)',
    'disabled' => 'Disabled (Open)',
    
    // Errors
    'admin_blocked' => 'Action blocked by administrator.',
    'admin_blocked_error' => 'This action has been disabled by the administrator.',
    'grace_period_exceeded' => 'Modification period expired (Limit: :days days).',
    'grace_period_error' => 'Cannot modify records older than :days days.',
];