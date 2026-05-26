<?php

return [
    'page_title' => 'Requests & Applications',
    'subtitle' => 'Manage leave, late arrival, and special permissions',
    'create_new' => 'New Request',
    'my_requests' => 'My Requests',
    'request_list' => 'Request List',
    'request_details' => 'Request Details',
    
    // Form Fields
    'request_type' => 'Request Type',
    'reason' => 'Reason / Description',
    'start_date' => 'Start Date',
    'end_date' => 'End Date',
    'attachment' => 'Attachment (Optional)',
    'status' => 'Status',
    'applicant' => 'Applicant',
    'role' => 'Role',
    'date_submitted' => 'Date Submitted',
    'ticket_number' => 'Ticket #',
    'action' => 'Action',
    
    // Types
    'type_absence' => 'Absence',
    'type_late' => 'Late Arrival',
    'type_sick' => 'Sick Leave',
    'type_early_exit' => 'Early Exit',
    'type_leave' => 'Staff Leave',
    'type_other' => 'Other',
    
    // Statuses
    'status_pending' => 'Pending',
    'status_pending_only' => 'Pending Only',
    'status_approved' => 'Approved',
    'status_partially_approved' => 'Partially Approved',
    'status_rejected' => 'Rejected',
    'status_all' => 'All Tickets',
    
    // Actions
    'approve' => 'Approve',
    'reject' => 'Reject',
    'cancel' => 'Cancel',
    'save' => 'Submit Request',
    'back' => 'Back to List',
    'download_attachment' => 'Download Attachment',
    'process_ticket' => 'Process Ticket',
    'save_notify' => 'Save & Notify Parent',
    
    // JS Alerts & Messages
    'success_create' => 'Request submitted successfully.',
    'success_update' => 'Request status updated.',
    'success_delete' => 'Request deleted.',
    'confirm_approve' => 'Approve this request?',
    'confirm_reject' => 'Reject this request?',
    'no_records_found' => 'No requests found.',
    'unauthorized_action' => 'You are not authorized to perform this action.',
    'unauthorized_teacher' => 'Teachers cannot create generic requests.',
    'processing' => 'Processing...',
    'processed' => 'Processed!',
    'error' => 'Error',
    'error_occurred' => 'An error occurred',
    'are_you_sure' => 'Are you sure?',
    'cannot_revert' => 'You won\'t be able to revert this!',
    'yes_delete' => 'Yes, delete it!',
    'deleted' => 'Deleted!',
    'success' => 'Success!',
    'no_reason_provided' => 'No reason provided.',
    
    // Admin Processing Modal
    'student' => 'Student',
    'parent_reason' => 'Parent\'s Reason / Explanation',
    'admin_decision' => 'Admin Decision',
    'select_decision' => '-- Select Decision --',
    'approve_fully' => 'Approve Request Fully',
    'partially_approve' => 'Partially Approve (Reduce Days)',
    'reject_request' => 'Reject Request',
    'approved_duration' => 'Approved Duration (Days)',
    'approved_duration_help' => 'How many days are you actually granting?',
    'admin_note' => 'Admin Note (Sent to Parent)',
    'admin_note_placeholder' => 'Explain your decision to the parent... (Required)',
    
    // Create view
    'request_for' => 'Request For (Student)',
    'myself_staff_leave' => '-- Myself (Staff Leave) --',
    'request_for_help' => 'Select a student to create a request on their behalf, or leave empty for your own leave request.',
    
    // Chatbot Specific
    'chatbot_submitted' => '✅ Request submitted successfully. Ticket: :ticket',
    'chatbot_ask_type' => '📝 Select Request Type:',
    'chatbot_ask_reason' => '📝 Please enter the reason for your request:',
];