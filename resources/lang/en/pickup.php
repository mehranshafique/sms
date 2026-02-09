<?php

return [
    // Headers
    'page_title' => 'Student Pickup',
    'scanner_title' => 'Gatekeeper Scanner',
    'manager_title' => 'Pickup Requests',
    'scanner_subtitle' => 'Scan Parent QR to validate pickup',
    'manager_subtitle' => 'Manage student release approvals',
    'parent_subtitle' => 'Generate QR codes for student pickup', // Added
    
    // UI Elements
    'manual_entry' => 'Manual Entry',
    'enter_code' => 'Enter Code (e.g. PKUP-...)',
    'validate_btn' => 'Validate',
    'refresh_btn' => 'Refresh',
    'scan_next' => 'Scan Next',
    'waiting_approval' => 'Waiting for Teacher Approval',
    'verification_success' => 'Verification Successful',
    'teacher_notified' => 'Class Teacher has been notified.',
    'expires_at' => 'Expires at', // Added
    'generate_qr' => 'Generate QR Code', // Added
    'generating' => 'Generating', // Added
    'no_students_linked' => 'No students linked to your account.', // Added
    
    // Table Columns
    'student' => 'Student',
    'pickup_by' => 'Pickup By',
    'scanned_by' => 'Scanned By',
    'status' => 'Status',
    'action' => 'Action',
    
    // Status Labels
    'status_pending' => 'Pending',
    'status_scanned' => 'Waiting Approval',
    'status_approved' => 'Released',
    'status_rejected' => 'Rejected',
    'status_expired' => 'Expired',
    
    // Actions
    'btn_release' => 'Release',
    'btn_reject' => 'Reject',
    
    // Alerts / Messages
    'confirm_title' => 'Are you sure?',
    'confirm_release' => 'Release student to guardian?',
    'confirm_reject' => 'Reject this pickup request?',
    'yes_release' => 'Yes, Release',
    'yes_reject' => 'Yes, Reject',
    'updated_success' => 'Request updated successfully.',
    'qr_generated_title' => 'QR Code Generated', // Added
    'qr_generated_text' => 'Please show this code to the school guard.', // Added
    
    // Errors
    'error' => 'Error', // Added
    'invalid_qr' => 'Invalid QR Code',
    'qr_used' => 'QR already used (Status: :status)',
    'qr_expired' => 'QR Code has expired',
    'valid_scan' => 'Valid QR! Waiting for teacher approval.',
    'not_waiting' => 'Request is not in waiting state.',
    'qr_generation_failed' => 'Could not generate QR code.', // Added
    'unauthorized' => 'You are not authorized to generate a QR code for this student.', // Added
];