<?php

return [
    'page_title' => 'System Configuration',
    'subtitle' => 'Manage system-wide settings and integrations',
    
    // Menu
    'smtp' => 'SMTP Configuration',
    'sms_sender' => 'ID Sender SMS',
    'school_year' => 'School Year Config',
    'modules' => 'Module Purchased',
    'sms_recharge' => 'SMS Recharging',
    'whatsapp_recharge' => 'Whatsapp Recharging',
    
    // SMTP Form
    'mail_host' => 'Mail Server Host',
    'mail_port' => 'Mail Server Port',
    'mail_username' => 'Mail Server Username',
    'mail_password' => 'Mail Server Password',
    'mail_encryption' => 'Email Encryption',
    'mail_driver' => 'Mail Driver',
    'mail_from_address' => 'Sender Email Address',
    'mail_from_name' => 'Sender Name',
    'smtp_help' => 'Configure the email server settings for this institution.',
    
    // Test Email
    'test_email_connection' => 'Test Email Connection',
    'enter_test_email' => 'Enter recipient email',
    'send_test_email' => 'Send Test Email',
    'test_email_help' => 'Ensure you save any changes above before testing.',

    // SMS Sender
    'sender_id' => 'SMS Sender ID',
    'sender_id_placeholder' => 'e.g. DIGITEX',
    'provider' => 'SMS Provider',
    'provider_help' => 'Select the gateway provider.',
    
    // Test Notifications
    'test_notifications' => 'Test Notifications',
    'test_sms_title' => 'Test SMS',
    'test_whatsapp_title' => 'Test WhatsApp',
    'phone_number' => 'Phone Number (with Country Code)',
    'phone_placeholder' => '+1234567890',
    'send_test_sms' => 'Send Test SMS',
    'send_test_whatsapp' => 'Send Test WhatsApp',
    'current_provider' => 'Uses currently selected provider',
    'whatsapp_provider' => 'Uses Provider: Infobip',
    'check_credentials' => 'Ensure your API Credentials are correct in .env before testing.',
    'api_credentials_warning' => 'Ensure your API Credentials are correctly configured.',
    'sending' => 'Sending...',
    'failed_to_send' => 'Failed to send message.',
    'check_logs' => 'Check your .env settings and provider logs.',
    'unknown_error' => 'Unknown Error',
    // NEW SAFE ERROR MESSAGES
    'gateway_connection_error' => 'Connection to SMS/WhatsApp Gateway failed. Please contact support or check server logs.',
    'gateway_response_error'   => 'The Gateway provider returned an error.',
    'sms_sent_success'         => 'SMS Sent Successfully.',
    'whatsapp_sent_success'    => 'WhatsApp Message Sent Successfully.',
    'institution_not_found'    => 'Institution Context Not Found.',
    'insufficient_credits'     => 'Failed: Insufficient Credits.',

    // Notification Settings
    'notification_settings' => 'Notification Settings',
    'notification_preferences' => 'Notification Preferences',
    'event_name' => 'Event Name',
    'email_channel' => 'Email',
    'sms_channel' => 'SMS',
    'whatsapp_channel' => 'WhatsApp',
    'student_created' => 'Student Created',
    'staff_created' => 'Staff Created',
    'payment_received' => 'Payment Received',
    'invoice_created' => 'Invoice Created', // Assuming this event exists or will be added
    'institution_created' => 'Institution Created', // Admin only usually
    'user_welcome' => 'User Welcome',
    
    // School Year
    'academic_session' => 'Academic Session',
    'academic_start_date' => 'Academic Start Date',
    'academic_end_date' => 'Academic End Date',
    'school_hours' => 'School Operational Hours (For Auto-SMS)',
    'school_start_time' => 'School Start Time',
    'school_end_time' => 'School End Time',
    'academic_start_date' => 'Academic Start Date',
    'academic_end_date' => 'Academic End Date',
    // Modules
    'module_management' => 'Module Management',
    'enable_disable_modules' => 'Enable or disable features for this institution',
    'module_name' => 'Module Name',
    'status' => 'Status',
    'enabled' => 'Enabled',
    'disabled' => 'Disabled',

    // Recharging
    'current_balance' => 'Current Balance',
    'add_credits' => 'Add Credits',
    'transaction_history' => 'Transaction History',
    'enter_amount' => 'Enter Amount',
    'credits' => 'Credits',
    'recharge' => 'Recharge',
    'sms_purchased' => 'SMS Purchased',
    'whatsapp_purchased' => 'WhatsApp Purchased',
    'balance' => 'Balance',
    'type' => 'Type',

    // Messages
    'update_success' => 'Configuration updated successfully.',
    'success' => 'Success',
    'error' => 'Error',
    'saving' => 'Saving...',
    'recharge_success' => 'Credits added successfully.',
    'save_changes' => 'Save Changes',
    'select_institution_context' => 'Please select an institution context first.', // Fix key name mismatch
    'something_went_wrong' => 'Something went wrong. Please try again.',
    'failed' => 'Failed',
    
    // Validation Messages
    'mail_host_error' => 'The Mail Host must be a server address (e.g. smtp.gmail.com), not an email address.',
    'only_super_admin_modules' => 'Only Main Admin can configure purchased modules.',
    'only_super_admin_recharge' => 'Only Main Admin can recharge credits.',
    'test_email_subject' => 'SMTP Configuration Test',
    'test_email_body' => 'This is a test email from the System Configuration check.',
    'test_email_success' => 'Test email sent successfully to :email',
    'test_email_failed' => 'Test failed: :error',
    'connection_failed' => 'Connection failed: Could not connect to the Mail Host. Please check your Host and Port settings.',
    'test_msg_content' => 'Digitex Test Message from :school. System configuration check successful.',
    'test_msg_success' => 'Test message sent successfully via :provider to :phone',
    'test_msg_failed' => 'Failed to send message via :provider. Please check API credentials and logs.',
    'exception_error' => 'Exception: :message',
];