<?php

return [
    'page_title' => 'Invoices & Payments',
    'generate_invoices' => 'Generate Invoices',
    'generate_subtitle' => 'Assign fees to all students in a specific section',
    'invoice_details' => 'Invoice Details',
    
    'no_active_session' => 'No active academic session found.',
    'no_students_found' => 'No active students found in this class.',
    'no_students_found_for_mode' => 'No students found matching the payment mode: :mode',
    'success_generated' => 'Invoices generated successfully.',
    'success_generated_count' => ':count Invoices generated successfully.',
    
    // Table Headers
    'invoice_list' => 'Invoice List',
    'invoice_number' => 'Invoice #',
    'student' => 'Student',
    'issue_date' => 'Issue Date',
    'due_date' => 'Due Date',
    'total' => 'Total',
    'paid' => 'Paid',
    'status' => 'Status',
    'action' => 'Action',
    
    // Form Labels
    'configuration' => 'Configuration', // Added
    'target_grade' => 'Target Grade Level', 
    'target_section' => 'Target Section',
    'select_grade' => 'Select Grade',
    'select_section' => 'Select Section',
    'select_grade_first' => 'Select Grade First',
    
    'select_students' => 'Select Students', // Added
    'select_all' => 'Select All', // Added
    'search_student' => 'Search student name...', // Added
    'select_class_first_msg' => 'Please select a class section above.', // Added
    'students_selected_count' => ':count students selected', // Added
    'students_selected_suffix' => 'students selected', // Added for dynamic JS update
    
    'select_fees' => 'Select Fees',
    'search_fees' => 'Search fees...', // Added
    'fees_will_load' => 'Fees will load here.', // Added
    'fee_bundle_help' => 'Check multiple items to bundle fees.', // Added
    'fee_help' => 'Invoices will be generated for all active students in the selected section.',
    
    // Fee Reference Table
    'class_fee_overview' => 'Class Fee Overview (Reference)', // Added
    'fee_name' => 'Fee Name', // Added
    'fee_type' => 'Type', // Added
    'amount' => 'Amount',
    'mode' => 'Mode', // Added
    'order' => 'Order', // Added
    'frequency' => 'Frequency', // Added

    // Statuses
    'status_unpaid' => 'Unpaid',
    'status_partial' => 'Partial',
    'status_paid' => 'Paid',
    'status_overdue' => 'Overdue',
    
    // Buttons
    'generate_btn' => 'Generate Invoices',
    'processing' => 'Processing...',
    'checking' => 'Checking...', // Added
    'pay' => 'Pay',
    'view' => 'View',
    'delete' => 'Delete',
    'print' => 'Print',
    'download_pdf' => 'Download PDF',
    'pay_now' => 'Pay Now',
    'yes_generate' => 'Yes, generate anyway', // Added
    
    // Messages & Alerts
    'no_fees_found' => 'No fee structures available. Please create fees first.',
    'no_sections_found' => 'No sections found', // Added
    'no_active_students' => 'No active students found in this class.', // Added
    'success_deleted' => 'Invoice deleted successfully.',
    'error_delete_paid' => 'Cannot delete an invoice that has payments attached. Please delete payments first.',
    'success' => 'Success',
    'error' => 'Error',
    'warning' => 'Warning', // Added
    'error_occurred' => 'An error occurred.',
    'unexpected_error' => 'An unexpected error occurred.', // Added
    'loading' => 'Loading...',
    'error_loading' => 'Error loading data',
    'error_loading_students' => 'Error loading students', // Added
    'error_loading_fees' => 'Error loading fees', // Added
    'select_student_warning' => 'Please select at least one student.', // Added
    'select_fee_warning' => 'Please select at least one fee structure.', // Added
    'duplicate_warning' => 'Warning: :count duplicate invoices detected.',
    'duplicate_warning_title' => 'Duplicate Warning', // Added
    'no_invoices_generated_error' => 'No invoices were generated. :count students were skipped.',
    'skipped_count_msg' => '(:count skipped)',
    'deselect_all' => 'Deselect All',
    // Discount
    'discount_scholarship' => 'Discount / Scholarship',
    'fixed' => 'Fixed',
    
    // PDF Labels
    'bill_to' => 'Bill To',
    'from' => 'From',
    'to' => 'To',
    'session' => 'Session',
    'date' => 'Date',
    'status_label' => 'Status',
    'description' => 'Description',
    'subtotal' => 'Subtotal',
    'paid_to_date' => 'Paid to Date',
    'balance_due' => 'Balance Due',
    'thank_you' => 'Thank you for your business.',
    'authorized_signature' => 'Authorized Signature',
    'item_description' => 'Item Description',
    'cost' => 'Cost',
    'paid_amount' => 'Paid Amount',
    'payment_history' => 'Payment History',
    'transaction_id' => 'Transaction ID',
    'method' => 'Method',
    'recorded_by' => 'Recorded By',
    'no_payments_found' => 'No payments recorded yet.',
];