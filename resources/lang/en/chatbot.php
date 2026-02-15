<?php

return [
    'page_title' => 'Chatbot Settings',
    'subtitle' => 'Configure automated responses and behaviors',
    
    // Config & Settings
    'general_config' => 'General Configuration',
    'channels' => 'Active Channels',
    'session_settings' => 'Session Settings',
    'session_timeout' => 'Session Timeout (Minutes)',
    'session_timeout_help' => 'Time before a user must re-authenticate (OTP). Default: 15.',
    'save_config' => 'Save Configuration',
    'enable_whatsapp' => 'Enable WhatsApp Chatbot',
    'enable_sms' => 'Enable SMS Chatbot',
    'enable_telegram' => 'Enable Telegram Chatbot',
    'channel_help' => 'Toggle to enable/disable automated responses for this channel.',
    
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
    'actions' => 'Actions',
    'no_keywords' => 'No keywords defined yet.',
    
    // Messages
    'config_updated' => 'Chatbot settings updated successfully.',
    'keyword_created' => 'Keyword created successfully.',
    'keyword_updated' => 'Keyword updated successfully.',
    'keyword_deleted' => 'Keyword deleted.',
    
    // --- CHATBOT INTERACTION RESPONSES (DYNAMIC) ---
    
    // Greetings & Errors
    'welcome_message' => "🎓 *Welcome to E-Digitex!*\n\n📌 Please enter your *Student ID* or *Staff ID* to login.",
    'default_keyword_response' => "👋 Hello! Please type a valid keyword to start (e.g. 'Hello', 'Menu').",
    'unknown_state_error' => "⚠️ Error: Unknown state. Type 'Reset' to start over.",
    'too_many_attempts' => "🚫 Too many failed attempts. Session ended.",
    'id_not_found' => "❌ ID not found. Please try again (Attempt :attempt/3).",
    'no_registered_phone' => "⚠️ Error: No registered phone number found for this ID. Please contact administration.",
    'system_error' => "⚠️ An internal error occurred. Please try again later.",
    
    // OTP
    'otp_sms_message' => "🔢 Your E-Digitex OTP code is: :otp (Valid for 5 min). Do not share it.",
    'otp_sent_notification' => "🔒 *Verification Required*\n\nAn OTP has been sent via SMS to the number ending in *:phone*.\n\n👉 *Please enter the code here to continue.*",
    'otp_expired' => "⏳ OTP code expired. Session ended.",
    'invalid_otp' => "❌ Invalid OTP. Please try again.",
    
    // Menus
    'login_success' => "✅ *Login Successful!*\n\n👤 Welcome, *:name*.\n\n" .
                       "📜 *Main Menu:*\n" .
                       "1️⃣ Balance & Finance 💰\n" .
                       "2️⃣ Homework & Assignments 📚\n" .
                       "3️⃣ Results & Reports 📊\n" .
                       "4️⃣ Canteen Menu 🍽️\n\n" .
                       "Type 'Menu' to see this list again or 'Logout' to exit.",
                       
    'main_menu' => "🎓 *Welcome to :school (Digitex)*\n\n📚 *:student, :class, :year*\n\n1️⃣ Homework (TP/TD)\n2️⃣ Payment\n3️⃣ Balance\n4️⃣ Report Card\n5️⃣ Misc Fees\n6️⃣ Activities & Calendar\n7️⃣ Derogation\n8️⃣ My Requests\n9️⃣ Generate Pickup QR",
    'homework_list' => "📚 *Homework*\n:content",
    'no_homework' => "⚠️ No homework found.",
    'balance_info' => "📊 *Balance Summary:*\n💰 Total Fees: :total\n✅ Paid: :paid\n❌ Outstanding: :due",
    'payment_method_menu' => "💰 Amount Due: :due\n💳 Total to Pay: :total\n📌 Choose Payment Method:\n\n1️⃣ Visa Card\n2️⃣ Mobile Money\n0️⃣ Cancel",
    'payment_link' => "✅ *💳 Visa Selected.*\n👉 Click here to pay:\n🔗 :link",
    'mobile_money_instruction' => "💳 Mobile Money selected.\n📌 Please enter your phone number.",
    'result_found' => "📄 Here is your result card.",
    'report_generated_local' => "📄 Report generated (Localhost): :url", // Added
    'no_result_found' => "⚠️ No result found.",
    'misc_fees_list' => "💰 *School Fees:*\n:content",
    'no_fees_found' => "✅ No miscellaneous fees found.",
    'activities_list' => "📅 *Activities & Calendar:*\n:content",
    'no_events_found' => "📅 No upcoming events found.",
    
    // Derogation & Requests
    'derogation_menu' => "📝 *Derogation Request*\n\nChoose duration:\n1️⃣ 7 days\n2️⃣ 15 days\n3️⃣ 20 days\n4️⃣ 30 days\n0️⃣ Cancel",
    'derogation_submitted' => "✅ Derogation request for :days days submitted.\nTicket: *:ticket*",
    
    'request_menu' => "📝 *Special Request Type:*\n\n1️⃣ Absence\n2️⃣ Late Arrival\n3️⃣ Sickness\n4️⃣ Early Exit\n5️⃣ Other\n0️⃣ Cancel",
    'request_search_prompt' => "🔍 *Student Search*\n\nPlease enter the Student Name or Admission Number to create a request for:",
    'no_student_found_retry' => "⚠️ Student not found. Please try again or type *0* to cancel.",
    'multiple_students_found' => "🔍 Multiple students found. Please type the exact *Admission Number* from the list below:\n",
    'student_selected' => "✅ Selected: *:name*",
    
    'request_reason_1' => "📝 *Reason for Early Exit:*\n1️⃣ Medical\n2️⃣ Family Emergency\n3️⃣ Other\n0️⃣ Cancel", // Legacy? Kept for safety
    'request_submitted' => "✅ Request submitted successfully.\nTicket: *:ticket*",
    
    // QR
    'qr_verification' => "📲 *QR Generation*\n\nIdentity verification required.\n➡️ Type *1* to receive OTP.\n➡️ Type *0* to cancel.",
    'otp_sent' => "OTP sent to registered number. Enter code to continue.",
    'qr_success_menu' => "✅ Verified. QR Code has been sent.",
    'qr_caption' => "Pickup QR for :student.\nValid for 2 hours.",
    
    // Legacy / API
    'student_not_found' => 'Student not found.',
    'not_enrolled' => 'Student is not enrolled in any active class.',
    'no_homework_found' => 'No recent homework found.',
    'latest_homework_retrieved' => 'Latest homework retrieved successfully.',
    'validation_error' => 'Validation Error',
    'student_verified' => 'Student verified successfully.',
    'staff_verified' => 'Staff verified successfully.',
    'staff_not_found' => 'Staff record not found.',
    'no_active_session' => 'No active academic session found.',
    'summary_retrieved' => 'Institution summary retrieved successfully.',
    'balance_retrieved' => 'Balance retrieved successfully.',
    'result_generated' => 'Result generated successfully.',
    'no_results_found' => 'No exam results found for this academic year.',
    'no_session' => 'No active session found.',
    'otp_message' => 'Your verification code is: :code',
    'qr_generated' => 'QR Code generated.',
    'qr_expired' => 'QR Code expired.',
    'qr_already_used' => 'QR Code already used.',
    'invalid_qr' => 'Invalid QR Code.',
    'scan_success' => 'Scan successful.',
    'teacher_pickup_alert' => 'PICKUP ALERT: Parent is at the gate to pick up :student. Validated by :gate.',
    'fees_retrieved' => 'Fees retrieved.',
    'events_retrieved' => 'Events retrieved.',
    
    // HEAD OFFICER MENU
    'admin_welcome_prompt' => "👤 *Admin Login*\n\nPlease enter your Username or Shortcode:",
    'admin_id_invalid' => "❌ Invalid Admin ID.",
    'admin_welcome' => "👤 *Welcome, :name.*\n\nPlease choose an option:\n\n1️⃣ Global Dashboard\n3️⃣ Financial Ranking\n5️⃣ Export Report\n6️⃣ Create Student Request\n0️⃣ Quit",
    'admin_dashboard' => "📊 *Global Dashboard*\n\n🏫 Schools: *:schools*\n👨‍🎓 Total Students: *:students*\n💵 Paid: *:paid_students* (*:paid_percentage*%)\n💰 Amount Paid: *:amount_paid*\n📈 Outstanding: *:outstanding*\n🔮 Forecast: *:total_balance*",
    'admin_ranking_menu' => "🏆 *Ranking Type*\n\nChoose ranking type:\n\n3️⃣1️⃣ Payment Rate\n3️⃣2️⃣ Enrollment (Student Count)\n\nType *00* for Main Menu.",
    'admin_export_menu' => "📁 *Export Reports*\n\nChoose report to export:\n\n1️⃣ Global (All Schools)\n2️⃣ By School\n\nType *00* for Main Menu.",
    'export_ready' => "✅ Export ready. Sending file...",
    'export_failed' => "Sorry, could not generate the file.",

    // Errors & Status
    'invalid_option' => "⚠️ Invalid option. Please try again.",
    'session_ended' => "Your session has ended. Type 'Digitex' or 'Admin' to start again.",
    'unauthorized' => "⛔ Unauthorized access.",
    'attempt_count' => "(Attempt :count/3)",
    'error_occurred' => "⚠️ An error occurred. Please try again.",
];