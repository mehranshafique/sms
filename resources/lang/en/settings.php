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
    'save_settings' => 'Save Settings', // Added
    
    // Tabs
    'tab_attendance' => 'Attendance',
    'tab_exams' => 'Exams',
    'tab_general' => 'General',
    'tab_academic' => 'Academic', // Added for Academic Settings

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

    // Academic / LMD Settings (New)
    'lmd_config' => 'LMD / University Configuration',
    'validation_threshold' => 'Validation Threshold',
    'threshold_hint' => 'Minimum percentage required to validate a credit (e.g., 50).',
    'grading_scale' => 'Grading Scale',
    'grade_label' => 'Grade Label',
    'min_percentage' => 'Min Percentage',
    'remark' => 'Remark',

    'enabled' => 'Enabled (Blocked)',
    'disabled' => 'Disabled (Open)',
    
    // Errors
    'admin_blocked' => 'Action blocked by administrator.',
    'admin_blocked_error' => 'This action has been disabled by the administrator.',
    'grace_period_exceeded' => 'Modification period expired (Limit: :days days).',
    'grace_period_error' => 'Cannot modify records older than :days days.',
];