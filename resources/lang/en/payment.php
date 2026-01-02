<?php

return [
    'page_title' => 'Record Payment',
    'record_payment' => 'Record Payment',
    'invoice_no' => 'Invoice',
    'payment_details' => 'Payment Details',
    
    // Form Fields
    'student_name' => 'Student Name',
    'total_amount' => 'Total Amount',
    'remaining_balance' => 'Remaining Balance',
    'payment_amount' => 'Payment Amount',
    'payment_date' => 'Payment Date',
    'method' => 'Method',
    'notes' => 'Notes',
    
    // Methods
    'cash' => 'Cash',
    'bank_transfer' => 'Bank Transfer',
    'card' => 'Card',
    'online' => 'Online',
    
    // Buttons
    'confirm_payment' => 'Confirm Payment',
    
    // Messages
    'success' => 'Success!',
    'error' => 'Error',
    'error_occurred' => 'An error occurred while processing the payment.',
    'success_recorded' => 'Payment recorded successfully.',
    'exceeds_balance' => 'Amount exceeds remaining balance.',
    //
    
    'previous_installment_pending_error' => 'Cannot accept payment. A previous installment for this student is still pending. Please clear previous dues first.',
    // SMS Template
    'sms_template' => 'Hello :name, payment of :amount received for :school. Remaining Balance: :balance. Thank you.',
];