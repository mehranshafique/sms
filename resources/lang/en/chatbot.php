<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Backend UI Settings & Configuration
    |--------------------------------------------------------------------------
    */
    'page_title' => 'Chatbot Settings',
    'subtitle' => 'Configure automated responses, behaviors, and active sessions',
    
    // General Settings
    'general_config' => 'General Configuration',
    'channels' => 'Active Channels',
    'session_settings' => 'Session Settings',
    'session_timeout' => 'Session Timeout (Minutes)',
    'session_timeout_help' => 'Time before a user must re-authenticate (OTP). Default: 15.',
    'save_config' => 'Save Configuration',
    'config_saved' => 'Configuration saved successfully.',
    'enable_whatsapp' => 'Enable WhatsApp Chatbot',
    'enable_sms' => 'Enable SMS Chatbot',
    'enable_telegram' => 'Enable Telegram Chatbot',
    'channel_help' => 'Toggle to enable/disable automated responses for this channel.',
    'webhook_urls' => 'Webhook URLs',
    'webhook_urls_help' => 'Paste these URLs in Infobip, Twilio, Meta, or Telegram. The ?secret= parameter authenticates inbound webhooks when the provider does not send Authorization headers.',
    'webhook_secret_missing' => 'Set CHATBOT_WEBHOOK_SECRET in your server .env file, then refresh this page to copy secure webhook URLs.',
    
    // Keyword Management
    'keyword_management' => 'Keyword Management',
    'add_keyword' => 'Add Keyword',
    'edit_keyword' => 'Edit Keyword',
    'keyword_list' => 'Keyword List',
    'keyword' => 'Trigger Keyword',
    'language' => 'Language',
    'response_message' => 'Response Message',
    'response_placeholder' => 'Enter the automated reply here...',
    'keyword_help' => 'The word that starts the conversation (e.g. "Hi", "Menu", "Balance").',
    'portal_role' => 'Portal / User Role',
    'portal_role_help' => 'Only IDs matching this role will be accepted after the keyword is sent.',
    'actions' => 'Actions',
    'no_keywords' => 'No keywords defined yet.',
    'keyword_created' => 'Keyword successfully created.',
    'keyword_updated' => 'Keyword successfully updated.',
    'keyword_deleted' => 'Keyword successfully deleted.',

    // Active Chat Sessions Table
    'chat_sessions' => 'Chat Sessions',
    'active_sessions' => 'Active Sessions',
    'phone_user' => 'Phone / User Info',
    'phone' => 'Phone Number',
    'user_type' => 'User Role',
    'institute' => 'Institute',
    'otp' => 'OTP',
    'locale' => 'Lang',
    'attempts' => 'Attempts',
    'created_at' => 'Created At',
    'updated_at' => 'Updated At',
    'expires_at' => 'Expires At',
    'status' => 'Status',
    'last_interaction' => 'Last Interaction',
    'end_session' => 'End Session',
    'confirm_end_session' => 'Are you sure you want to forcibly end this session?',
    'session_ended_success' => 'Chat session(s) successfully ended.',
    'delete_selected' => 'Delete Selected',
    'are_you_sure' => 'Are you sure?',
    'bulk_delete_warning' => 'You want to delete these sessions? Users will be forced to restart their chat.',
    'yes_delete' => 'Yes, delete!',
    'success' => 'Success!',
    'error' => 'Error!',
    'something_went_wrong' => 'Something went wrong!',
    'select_least_one' => 'Select at least one record.',

    /*
    |--------------------------------------------------------------------------
    | Automated Chatbot Messages (WhatsApp/SMS)
    |--------------------------------------------------------------------------
    */
    // Authentication & Identity
    'admin_welcome_prompt' => "Welcome, please enter your identifier (Shortcode).",
    'welcome_message' => "Welcome! Please enter your Admission Number (Student) or Phone Number (Parent).",
    'keywords_not_found' => "Keyword not found. Send 'Hello' or your school keyword to begin.",
    'admin_id_invalid' => "Invalid ID. We could not find a record matching that shortcode.",
    'student_id_invalid' => "ID or Phone Number not found. Please verify and try again.",
    'no_registered_phone' => "No registered phone number found for OTP delivery.",
    'otp_sms_message' => "Your Digitex OTP verification code is: :otp",
    'otp_sent_notification' => "A 4-digit verification code has been sent to :phone. Please reply with the code.",
    'invalid_otp' => "Invalid OTP code. Please try again.",
    'too_many_attempts' => "Too many failed attempts. Session closed.",
    'attempt_count' => "(Attempt :count/3)",
    
    // Core Navigation
    'logout_success' => "👋 Session closed. Thank you for using our service.",
    'session_ended' => "Your session has ended. Send 'Menu' to start again.",
    'invalid_option' => "⚠️ Invalid option. Please reply with a valid number from the menu.",
    'unknown_command' => "Unknown command. Please reply with a valid number from the menu.",
    'system_error' => "⚠️ System error. Please try again later.",
    'unauthorized' => "⛔ Unauthorized access.",

    // Academic & Financial Outputs
    'financial_restriction_msg' => 'Access denied. You have an outstanding fee balance of :amount. Please settle your account to access academic reports.',
    'no_results_found' => 'No exam results or marks are available for this session yet.',
    'result_found' => 'Here is your official academic report card.',
    'not_enrolled' => 'No active enrollment found for the current academic session.',
    'no_session' => 'No active academic session found.',
    'no_homework' => 'No recent homework or assignments found.',
    'no_fees_found' => 'No miscellaneous or one-time fees found.',
    
    // Quick Actions
    'otp_sent' => 'An OTP has been sent to your registered phone number.',
    'qr_caption' => 'Secure Pickup QR for :student',
    'derogation_submitted' => '✅ Request for :days days submitted successfully. Ticket: #:ticket',
];