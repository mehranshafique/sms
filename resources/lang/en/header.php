<?php

return [
    // Navigation & Menus
    'select_institute' => 'Select Institute',
    'my_institute' => 'My Institute',
    'global_view' => 'Global View',
    'global_dashboard' => 'Global Dashboard',
    'mobile_menu' => 'Menu',
    
    // Search
    'search_school' => 'Search school...',
    'no_school_found' => 'No school found',
    'global_search_placeholder' => 'Search students, staff, pages...',
    'global_search_no_results' => 'No results found',
    'global_search_hint' => 'Type at least 2 characters',
    'global_search_page' => 'Page',
    'search_page' => 'Navigation page',
    'search_unknown_staff' => 'Staff member',
    'search_unknown_parent' => 'Parent / Guardian',
    'search_types' => [
        'page' => 'Page',
        'student' => 'Student',
        'staff' => 'Staff',
        'parent' => 'Parent',
        'subject' => 'Subject',
        'class_section' => 'Class',
        'invoice' => 'Invoice',
        'exam' => 'Exam',
        'notice' => 'Notice',
        'institution' => 'Institution',
    ],

    // Profile Dropdown
    'my_profile' => 'My Profile',
    'inbox' => 'Inbox',
    'settings' => 'Settings',
    'logout' => 'Logout',

    // Notification Bell (General)
    'notifications' => 'Notifications',
    'new' => 'New',
    'no_new_notifications' => 'No new notifications',
    'new_notification_toast' => 'New notification',

    // Admin Notifications
    'pending_fund_requests' => 'Pending Fund Requests',
    'pending_fund_requests_desc' => ':count pending fund requests to review.',
    'pending_requests_leaves' => 'Pending Requests/Leaves',
    'pending_requests_desc' => ':count new requests require your approval.',

    // Student Notifications
    'unpaid_fees' => 'Unpaid Fees',
    'unpaid_fees_desc' => 'You have :count pending fee invoices.',
    'active_elections' => 'Active Elections',
    'active_elections_desc' => ':count elections are open for voting.',
    'new_announcements' => 'New Announcements',
    'new_announcements_desc' => ':count new notices posted recently.',

    // Staff/Teacher Notifications
    'new_staff_announcements' => 'New Staff Announcements',
    'new_staff_announcements_desc' => ':count new notices posted.',
    'request_updated' => 'Request Updated',
    'request_updated_desc' => ':count of your requests have been reviewed.',

    // Dynamic Financial/Budget Notifications
    'fund_request_status' => 'Fund Request :status',
    'fund_request_desc' => 'Your request :ticket was :status.',

    'mark_all_read' => 'Mark all read',
    'notif_mark_failed' => 'Could not update notifications. Please refresh the page.',

    // In-app notification messages
    'notif_unknown_student' => 'Student',
    'notif_unknown_staff' => 'Staff member',
    'notif_request_new_title' => 'New Student Request',
    'notif_request_new_message' => ':student submitted :type request (:ticket).',
    'notif_request_updated_title' => 'Request Updated',
    'notif_request_updated_message' => 'Request :ticket is now :status.',
    'notif_payment_title' => 'Payment Received',
    'notif_payment_student_message' => 'Payment of :amount recorded for invoice :invoice.',
    'notif_payment_parent_message' => 'Payment of :amount received for :student.',
    'notif_payment_admin_message' => ':student paid :amount on invoice :invoice.',
    'notif_invoice_title' => 'New Invoice',
    'notif_invoice_student_message' => 'New invoice :invoice for :amount.',
    'notif_invoice_parent_message' => 'New invoice for :student — :amount.',
    'notif_invoice_admin_message' => 'Invoice :invoice created for :student.',
    'notif_notice_title' => 'New Announcement',
    'notif_notice_message' => ':title',
    'notif_exam_title' => 'Exam Results Published',
    'notif_exam_student_message' => 'Results for :name are now available.',
    'notif_exam_teacher_message' => 'Exam :name has been published.',
    'notif_pickup_scan_title' => 'Pickup Scan',
    'notif_pickup_scan_message' => ':student was scanned at the gate for pickup.',
    'notif_pickup_status_title' => 'Pickup Update',
    'notif_pickup_status_message' => 'Pickup for :student was :status.',
    'notif_leave_new_title' => 'Leave Request',
    'notif_leave_new_message' => ':staff submitted a leave request.',
    'notif_leave_updated_title' => 'Leave Request Updated',
    'notif_leave_updated_message' => 'Your leave request was :status.',
    'notif_fund_new_title' => 'Fund Request',
    'notif_fund_new_message' => ':requester submitted fund request: :title.',
    'notif_fund_status_title' => 'Fund Request :status',
    'notif_fund_status_message' => 'Your fund request ":title" was :status.',
    'notif_budget_consumed_title' => 'Budget Expense Recorded',
    'notif_budget_consumed_message' => ':line — ":title" spent :amount. Remaining: :remaining.',
    'notif_discipline_title' => 'Disciplinary Notice',
    'notif_discipline_message' => ':student — :type: :title on :date.',
    'notif_proof_submitted_title' => 'Payment Proof Submitted',
    'notif_proof_submitted_admin_message' => ':student submitted payment proof of :amount for invoice :invoice. Review required.',
    'notif_proof_submitted_parent_message' => 'We received your payment proof of :amount for :student (invoice :invoice). Accounts will review it soon.',
    'notif_proof_rejected_title' => 'Payment Proof Rejected',
    'notif_proof_rejected_message' => 'Payment proof for :student (invoice :invoice, :amount) was not accepted. :reason',
    'notif_proof_no_reason' => 'Please contact the school office.',
];