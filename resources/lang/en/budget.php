<?php

return [
    'page_title' => 'Budget Management',
    'categories_title' => 'Budget Categories',
    'requests_title' => 'Fund Requests',
    'allocation_title' => 'Budget Allocation',
    'finance_overview' => 'Global Finance Overview',
    
    // Actions
    'add_category' => 'Add Category',
    'allocate_budget' => 'Allocate Budget',
    'request_fund' => 'Request Funds',
    'approve' => 'Approve',
    'reject' => 'Reject',
    'edit' => 'Edit',
    'delete' => 'Delete',
    'save' => 'Save',
    'cancel' => 'Cancel',
    
    // Table Headers & Fields
    'ticket_number' => 'Ticket Ref',
    'branch' => 'Branch / School',
    'category' => 'Category',
    'description' => 'Description',
    'period' => 'Period / Dates', 
    'allocated' => 'Allocated',
    'spent' => 'Spent',
    'remaining' => 'Remaining',
    'status' => 'Status',
    'requested_by' => 'Requested By',
    'amount' => 'Amount',
    'date' => 'Date',
    'actions' => 'Actions',
    
    // Global Stats
    'total_allocated' => 'Total Allocated (Session)',
    'total_spent' => 'Total Spent (Session)',
    'total_remaining' => 'Total Remaining (Session)',
    'budget_periods' => 'Budget Periods & Allocations',
    'total_pending_req' => 'Pending Requests',
    'total_processed_req' => 'Processed Requests',
    'total_requested_amt' => 'Total Amount Requested',
    'total_approved_amt' => 'Total Amount Approved',

    // Statuses
    'pending' => 'Pending',
    'approved' => 'Approved',
    'rejected' => 'Rejected',
    
    // Forms
    'category_name' => 'Category Name',
    'period_name' => 'Period Name', 
    'start_date' => 'Start Date', 
    'end_date' => 'End Date', 
    'enter_period_name' => 'e.g. Q1, Jan 2025', 
    'enter_name' => 'Enter category name',
    'enter_description' => 'Enter description (optional)',
    'select_category' => 'Select Category',
    'enter_amount' => 'Enter Amount',
    'request_title' => 'Request Title',
    'request_description' => 'Reason / Details',
    'rejection_reason' => 'Rejection Reason',
    'allocation_warning' => 'Note: You cannot reduce the amount below what has already been spent.',
    'confirm_approve' => 'Confirm Decision',
    
    // Messages
    'success_category_created' => 'Budget category created successfully.',
    'success_allocated' => 'Budget allocated successfully.',
    'success_update' => 'Budget updated successfully.', 
    'success_request_submitted' => 'Fund request submitted successfully. A ticket reference has been generated.',
    'success_approved' => 'Fund request approved.',
    'success_rejected' => 'Fund request rejected.',
    'insufficient_funds' => 'Insufficient budget funds for this request.',
    'error_allocation_less_than_spent' => 'Cannot update. Allocated amount is less than spent amount.'
];