<?php

return [
    'page_title' => 'Finance Management',
    'fee_structure_title' => 'Fee Structures',
    'fee_type_title' => 'Fee Types',
    'manage_subtitle' => 'Manage fees, invoices and payments',
    'manage_types_subtitle' => 'Define types of fees (Tuition, Bus, Lab)',
    
    // Fee Structures
    'fee_list' => 'Fee Structure List',
    'add_fee' => 'Add Fee Structure',
    'fee_name' => 'Fee Name',
    'fee_type' => 'Fee Type',
    'amount' => 'Amount',
    'frequency' => 'Frequency',
    'grade_level' => 'Grade Level (Optional)',
    'select_type' => 'Select Type',
    'select_grade' => 'Select Grade',
    'mode' => 'Payment Mode', // Added
    'parent_fee_name' => 'Parent Fee Name', // Added
    // Fee Types
    'fee_type_list' => 'Fee Type List',
    'add_type' => 'Add Fee Type',
    'edit_type' => 'Edit Fee Type',
    'type_name' => 'Type Name',
    'description' => 'Description',
    'status' => 'Status',
    
    // Frequencies
    'one_time' => 'One Time',
    'monthly' => 'Monthly',
    'termly' => 'Termly',
    'yearly' => 'Yearly',

    // Messages
    'success_create' => 'Fee structure created successfully.',
    'success_create_type' => 'Fee type created successfully.',
    'success_update_type' => 'Fee type updated successfully.',
    'success_delete_type' => 'Fee type deleted successfully.',
    'success_update' => 'Fee structure updated successfully.',
    'success_delete' => 'Fee structure deleted successfully.',
    'no_active_session' => 'No active academic session found.',
    'global_fee_missing_error' => 'A Global Annual Fee must be created for this grade before adding installments.',
    'installment_cap_error' => 'Total installments (:total) cannot exceed the Global Annual Fee (:limit).',
    'error_occurred' => 'Error occurred',
    'unexpected_error' => 'An unexpected error occurred.',
    
    // NEW SAFETY MESSAGES
    'global_amount_too_low' => 'Cannot reduce Global Fee below the sum of existing installments (:total).',
    'cannot_delete_global_with_installments' => 'Cannot delete Global Fee because active installments exist. Please delete installments first.',
    'duplicate_global_config_error' => 'This class/grade already has a Global Fee configuration for this Fee Type. Please edit the existing structure instead of creating a duplicate.', // Added
    // Buttons & Actions
    'save' => 'Save',
    'cancel' => 'Cancel', // Added
    'action' => 'Action',
    'edit_fee' => 'Edit Fee',
    'view_details' => 'View Details',
    'close' => 'Close',
    'yes_delete' => 'Yes, delete it!', // Added
    
    // Alerts & Confirmations
    'are_you_sure' => 'Are you sure?', // Added
    'delete_warning' => 'This action cannot be undone!', // Added
    'error' => 'Error', // Added
    'success' => 'Success', // Added
    'deleted' => 'Deleted!', // Added
    
    // Class Financial Report
    'class_financial_report' => 'Class Financial Report',
    'report_subtitle' => 'Detailed financial overview per class',
    'select_class_filter' => 'Select Class to Generate Report',
    'select_class' => 'Select Class',
    'choose_class' => 'Choose a Class...',
    'generate_report' => 'Generate Report',
    'financial_overview' => 'Financial Overview',
    'totals' => 'TOTALS',
    'student_identity' => 'Student Identity',
    'parent_guardian' => 'Parent / Guardian',
    'today_payment' => 'Today Payment',
    'cumulative_paid' => 'Cumulative Paid',
    'remaining_fees' => 'Remaining Fees',
    'annual_fee' => 'Annual Fee',
    'previous_debt' => 'Previous Debt',
    'no_data_found' => 'No data found for the selected class.',
    'payment_mode' => 'Payment Mode',
    'global' => 'Global',
    'installment' => 'Installment',
    'class_section' => 'Class Section',
    'optional' => 'Optional',
    'all_sections' => 'All Sections',
    'installment_order' => 'Installment Order',
    'sequence_order_hint' => 'Sequence number (1 for first installment, etc.)',

    // Student Finance Dashboard & General Keys
    'student_finance_dashboard' => 'Student Finance Dashboard',
    'back_to_profile' => 'Back to Profile',
    'fee_management' => 'Fee Management',
    'global_overview' => 'Global Overview',
    'annual_fee_contract' => 'Annual Fee (Contract)',
    'annual_fee_gross' => 'Annual Fee (Gross)',
    'discount_applied' => 'Discount Applied',
    'total_paid_global' => 'Total Paid (Global)',
    'total_remaining_year' => 'Total Remaining (Year)',
    'installment_label' => 'Installment',
    'paid' => 'Paid',
    'remaining' => 'Remaining',
    'locked_msg' => 'LOCKED! Previous installment must be fully paid before accessing :label.',
    'payment_for' => 'Payment for: :label',
    'already_paid' => 'Already Paid',
    'remaining_due' => 'Remaining Due',
    'pay_now' => 'Pay Now',
    'fully_settled' => 'This installment is fully settled.',
    'context_history' => 'Context & History',
    'current_installment' => 'Current Installment',
    'reduce_global_msg' => 'Paying this will reduce your Global Balance to:',
    'student_not_enrolled' => 'Student not enrolled in any class.',
    'installment_prefix' => 'Installment',
    
    // Payment History Table
    'payment_history' => 'Payment History',
    'date' => 'Date',
    'transaction_id' => 'Transaction ID',
    'method' => 'Method',
    'recorded_by' => 'Recorded By',
    'no_payments_found' => 'No payments recorded yet.',
    'fixed' => 'Fixed',

    // New Balance Overview Keys
    'student_balances' => 'Student Balances',
    'balance_overview' => 'Balance Overview',
    'class_wise_breakdown' => 'Class-wise financial breakdown',
    'all_classes' => 'All Classes',
    'class_name' => 'Class Name',
    'students_count' => 'Students',
    'paid_students' => 'Paid Students',
    'total_invoiced' => 'Total Invoiced',
    'total_collected' => 'Collected',
    'total_outstanding' => 'Outstanding',
    'class_details' => 'Class Details',
    'loading_details' => 'Loading details...',
    'no_fee_structures_class' => 'No fee structures or installments defined for this class yet.',
    'view_dashboard' => 'View Dashboard',
    'error_loading' => 'Error loading data. Please try again.',
    'paid_amount' => 'Paid Amount',
    'due_amount' => 'Due Amount',
    'status_partial' => 'Partial',
    'status_unpaid' => 'Unpaid',
    'status_overdue' => 'Overdue',
     
    // NEW KEYS FOR TAB DESCRIPTIONS
    'tab_info_global' => 'Students with "Global" payment mode (Lump-sum payers).',
    'tab_info_installment' => 'Students with "Installment" payment mode.',
    'summary' => 'Total Summary',
    'tab_info_summary' => 'Cumulative financial summary for all students in this class.',
];