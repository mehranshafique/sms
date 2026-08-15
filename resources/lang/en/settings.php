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
    'auto_notify_absent' => 'Notify parents when student is absent',
    'auto_notify_on' => 'Enabled',
    'auto_notify_off' => 'Disabled',
    'auto_notify_absent_help' => 'When enabled, parents receive SMS/WhatsApp (per Notification Preferences for “Student Absent”) as soon as a student is marked Absent. A daily catch-up also runs at 16:00.',
    
    // Exam Settings
    'exam_settings' => 'Exam Configuration',
    'lock_exams' => 'Block Exam Creation/Updates',
    'lock_exams_help' => 'If enabled, regular users (teachers) cannot create or update exams.',
    'exam_grace_period' => 'Modification Grace Period (Days)',
    'exam_grace_help' => 'Number of past days regular users are allowed to edit exam details after the start date.',

    // Academic Tab
    'tab_academic' => 'Academic',
    'lmd_config' => 'LMD / University Configuration',
    'validation_threshold' => 'Validation Threshold',
    'threshold_hint' => 'Minimum percentage required to validate a credit (e.g., 50).',
    
    // NEW KEYS
    'active_periods_title' => 'Active Periods Management',
    'active_periods_help' => 'Select the periods currently open for teachers to enter marks. Unchecked periods will be hidden from the teacher interface.',
    
    'grading_scale' => 'Grading Scale',
    'grade_label' => 'Grade Label',
    'min_percentage' => 'Min Percentage',
    'remark' => 'Remark',
    'save_settings' => 'Save Settings',

    'enabled' => 'Enabled (Blocked)',
    'disabled' => 'Disabled (Open)',
    
    'financial_restrictions' => 'Financial Restrictions',
    'block_reports_on_debt' => 'Block Report Cards & Transcripts when payment rules are not met',
    'block_reports_on_debt_help' => 'If enabled, report cards are blocked when payment rules fail (Chatbot, SMS/WhatsApp, and web downloads). Configure a minimum paid amount per period below; if a period has no minimum, any outstanding balance blocks access.',
    'report_min_paid_title' => 'Minimum paid amount per report period',
    'report_min_paid_help' => 'For each period, set how much the student must have paid (current session) to unlock that report card. Leave blank to use the legacy rule (any unpaid balance blocks). The parent sees how much remains to reach this amount.',

    'report_seal_title' => 'Report Card Seal / Stamp',
    'report_seal_position' => 'Seal position',
    'report_seal_position_help' => 'Where the school seal appears in the report card footer.',
    'report_seal_image' => 'Seal / stamp image',
    'report_seal_image_help' => 'Upload your official school stamp (PNG/JPG, max 2MB). If empty, the default circular seal is used.',
    'remove_report_seal' => 'Remove uploaded seal',
    'seal_left' => 'Left',
    'seal_center' => 'Center',
    'seal_right' => 'Right',
    'seal_none' => 'Hidden',
    'application_scale' => 'Application Scale (Report Card)',
    'application_scale_help' => 'Maps overall percentage to Application grade on bulletins. Editable per school.',
    'resit_pass_percentage' => 'Resit pass percentage (secondary)',
    'resit_pass_percentage_help' => 'Used for the secondary year-end deliberation list. Parents are notified after the jury confirms decisions — not when S2 is finalized.',
    'assessment_periods_title' => 'Official assessment stages',
    'assessment_periods_help' => 'Close a period or exam to publish official report cards and rankings. Reopening requires a reason and marks results as under revision (parents cannot download until you close again). A trimester/semester report exists only when both periods and the exam are closed.',
    'period_label' => 'Stage',
    'period_status' => 'Status',
    'closes_at' => 'Auto-close at',
    'closed_at' => 'Closed',
    'close_period' => 'Close',
    'reopen_period' => 'Reopen',
    'save_schedule' => 'Save',
    'period_status_open' => 'Open',
    'period_status_closed' => 'Closed',
    'period_status_reopened' => 'Under revision',
    'period_closed' => ':period is now closed. Report cards and ranking are official.',
    'period_reopened' => ':period reopened for revision.',
    'period_schedule_saved' => 'Auto-close time saved for :period.',
    'reopen_reason_required' => 'A reason is required to reopen a closed stage.',
    'reopen_reason_prompt' => 'Why are you reopening this stage?',
    'no_current_session' => 'No current academic session is set for this school.',
    
    // Errors
    'admin_blocked' => 'Action blocked by administrator.',
    'admin_blocked_error' => 'This action has been disabled by the administrator.',
    'grace_period_exceeded' => 'Modification period expired (Limit: :days days).',
    'grace_period_error' => 'Cannot modify records older than :days days.',
];