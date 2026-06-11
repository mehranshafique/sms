<?php

return [
    'page_title' => 'Smart Reminders',
    'page_subtitle' => 'Send automated and personalized fee & exam reminders to parents',
    
    'fee_reminders_title' => 'Fee Reminders (Debt Logic)',
    'fee_reminders_desc' => 'Sends a personalized message to parents indicating their child\'s total real outstanding balance. Automatically filters out students with zero debt.',
    
    'target_class' => 'Target Class & Section (Optional)',
    'all_classes' => 'All Classes & Sections',
    
    'target_tranche' => 'Target Tranche (Optional)',
    'all_unpaid_tranches' => 'All Unpaid Tranches (Global Debt)',
    'tranche_info' => 'If a specific tranche is selected, the system targets students who owe for that specific tranche, but still calculates their FULL outstanding debt globally to present in the message.',
    
    'delivery_channel' => 'Delivery Channel',
    'send_fee_reminders' => 'Send Fee Reminders',
    
    'exam_reminders_title' => 'Next-Day Exam Reminders',
    'exam_reminders_desc' => 'Automatically scans the Date Sheets for exams scheduled for Tomorrow. Sends highly personalized alerts to parents combining their child\'s name, subjects, times, and examination rooms.',
    'exam_info' => 'Only parents of students who have exams specifically scheduled for tomorrow will receive this automated message.',
    'trigger_exam_reminders' => 'Trigger Exam Reminders',

    'attendance_reports_title' => 'Attendance Summary Reports',
    'attendance_reports_desc' => 'Send weekly or monthly attendance summaries to parents (days present, absent, late, and attendance percentage with comparison to the previous period).',
    'attendance_period' => 'Report period',
    'send_attendance_reports' => 'Send Attendance Reports Now',
    'attendance_auto_schedule' => 'Automatic schedule: weekly reports every Friday 18:00, monthly reports on the 1st at 08:00.',
    
    'standard_sms' => 'Standard SMS',
    'whatsapp' => 'WhatsApp',
    'email' => 'Email',
    
    // JS / SweetAlert Prompts
    'initiate_broadcast' => 'Initiate Broadcast?',
    'broadcast_warning' => 'This will bulk-send personalized messages to parents based on the selected criteria.',
    'yes_dispatch' => 'Yes, Dispatch Reminders',
    'dispatching' => 'Dispatching...',
    'finished' => 'Finished',
    'error' => 'Error',
    'gateway_error' => 'Gateway connection error.',
    
    // Controller Messages
    'messages' => [
        'no_exams' => 'No exams are scheduled for tomorrow.',
        'success_sent' => 'Reminders sent successfully to :count parents. :failedMsg',
        'failed_count' => '(:count failed)',
        'template_not_found' => 'The message template for this event is missing or disabled.',
        'notifications_disabled' => 'Notifications are disabled for this channel in your institution\'s notification preferences.',
        'gateway_config_error' => 'Gateway configuration error: :error',
        'no_institution' => 'No active institution context. Please select a school first.',
        'attendance_sent' => 'Attendance reports sent to :sent parents. Skipped: :skipped.',
    ],
];