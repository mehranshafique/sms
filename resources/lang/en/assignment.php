<?php

return [
    'page_title' => 'Assignments',
    'subtitle' => 'Manage class assignments and homework',
    'create_new' => 'Create Assignment',
    'edit' => 'Edit Assignment',
    'details' => 'Assignment Details',
    
    // Table Headers
    'title' => 'Title',
    'class' => 'Class',
    'subject' => 'Subject',
    'deadline' => 'Deadline',
    'teacher' => 'Teacher',
    'action' => 'Action',
    'no_assignments' => 'No assignments found.',
    
    // Form Labels
    'select_class' => 'Select Class',
    'select_subject' => 'Select Subject',
    'enter_title' => 'e.g. Algebra Homework 1',
    'description' => 'Description',
    'attachment' => 'Attachment (Optional)',
    'attachment_view' => 'View Attachment',
    'save' => 'Save Assignment',
    'update' => 'Update Assignment',
    
    // JS States
    'loading' => 'Loading...',
    'select_subject_placeholder' => '-- Select Subject --',
    'error_loading' => 'Error loading subjects',
    
    // Messages
    'success_create' => 'Assignment created successfully.',
    'success_update' => 'Assignment updated successfully.',
    'success_delete' => 'Assignment deleted.',
    'delete_confirm' => 'Delete this assignment?',
    'delete_warning' => 'This action cannot be undone.',
    'yes_delete' => 'Yes, delete it!',
    'cancel' => 'Cancel',
    'no_active_session' => 'No active academic session found.',
    'validation_error' => 'Please check the form for errors.',

    // Approval workflow
    'status' => 'Status',
    'all_statuses' => 'All statuses',
    'status_approved' => 'Published',
    'status_pending' => 'Awaiting approval',
    'status_rejected' => 'Rejected',
    'approve' => 'Approve',
    'reject' => 'Reject',
    'approve_confirm' => 'Publish this homework?',
    'approve_warning' => 'Parents and students will be notified once it is published.',
    'reject_confirm' => 'Reject this homework?',
    'rejection_reason' => 'Reason (optional)',
    'rejection_reason_placeholder' => 'Tell the teacher what needs to change',
    'no_reason_given' => 'No reason given',
    'success_create_pending' => 'Homework submitted for approval. Parents will be notified once it is approved.',
    'success_approved' => 'Homework approved and published.',
    'success_rejected' => 'Homework rejected. The teacher has been notified.',
    'approval_required_notice' => 'This school requires homework to be approved before parents and students can see it. Your homework will be sent for review when you save it.',
    'pending_notice' => ':count homework item(s) are waiting for your approval.',
    'show_pending' => 'Review now',
    'parent' => 'Parent',

    // Notification copy
    'notif_published_title' => 'New homework',
    'notif_published_message' => 'New :subject homework for :class, due :deadline.',
    'notif_pending_title' => 'Homework awaiting approval',
    'notif_pending_message' => ':teacher submitted ":title" for :class and is waiting for approval.',
    'notif_approved_title' => 'Homework published',
    'notif_approved_message' => 'Your homework ":title" was approved and parents have been notified.',
    'notif_rejected_title' => 'Homework rejected',
    'notif_rejected_message' => 'Your homework ":title" was rejected. Reason: :reason',
];