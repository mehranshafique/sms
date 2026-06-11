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
    'orange_money' => 'Orange Money',
    'airtel_money' => 'Airtel Money',
    'mpesa' => 'M-Pesa',
    'vodacom' => 'Vodacom',
    'mobile_reference' => 'Mobile Money Reference',
    'mobile_reference_placeholder' => 'Transaction ID from SMS/receipt',
    'method_not_enabled' => 'This payment method is not enabled for your school.',
    'mobile_reference_required' => 'Mobile Money transaction reference is required.',
    'global_cap_exceeded' => 'Payment exceeds annual fee limit of :limit. You can pay up to :remaining more.',
    'no_methods_enabled' => 'No payment methods are enabled. Ask an administrator to configure Payment Methods under Finance.',
    
    // Buttons
    'confirm_payment' => 'Confirm Payment',
    
    // Messages
    'success' => 'Success!',
    'error' => 'Error',
    'error_occurred' => 'An error occurred while processing the payment.',
    'success_recorded' => 'Payment recorded successfully.',
    'exceeds_balance' => 'Amount exceeds remaining balance.',
    'previous_installment_pending_error' => 'Cannot accept payment. A previous installment for this student is still pending. Please clear previous dues first.',
    
    // Confirmation Popup
    'confirm_title' => 'Confirm Payment',
    'confirm_message' => 'Do you want to confirm the payment for <strong>:name</strong>?',
    'amount_to_pay' => 'Amount to Pay',
    'password_label' => 'Enter Admin Password to Validate',
    'password_placeholder' => 'Password',
    'validate_pay_btn' => 'Validate & Pay',
    'password_required' => 'Password is required',

    // SMS Template
    'sms_template' => 'Hello :name, payment of :amount received for :school. Remaining Balance: :balance. Thank you.',
];