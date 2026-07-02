<?php

return [
    // Core & Auth
    'session_ended' => "Your session has ended. Please send a keyword to start again.",
    'unknown_state_error' => "⚠️ Unknown state. Returning to main menu.",
    'system_error' => "⚠️ An internal error occurred. Please try again later.",
    'admin_welcome_prompt' => "🏢 *Head Office Portal*\n\nPlease enter your Staff ID or Username:",
    'director_welcome_prompt' => "🏫 *Director Portal*\n\nPlease enter your Staff ID or Username:",
    'finance_welcome_prompt' => "💰 *Finance Portal*\n\nPlease enter your Staff ID or Username:",
    'teacher_welcome_prompt' => "👨‍🏫 *Teacher Portal*\n\nPlease enter your Staff ID:",
    'student_welcome_prompt' => "🎓 *University Portal*\n\nPlease enter your Student ID:",
    'parent_welcome_prompt' => "👋 *Parent Portal*\n\nPlease enter your Child's Student ID:",
    'keywords_not_found' => "👋 Hello! Please start by typing your designated keyword:\n\n👉 *Portail* (Students)\n👉 *Bonjour* (Parents)\n👉 *Agent* (Teachers)\n👉 *Digitex* (Head Office)",
    'invalid_id' => "❌ Invalid ID. Please try again.",
    'no_registered_phone' => "⚠️ Error: No registered phone number found for this ID.",
    'otp_sms_message' => "🔢 Your E-Digitex OTP code is: :otp. Do not share it.",
    'otp_sent_notification' => "🔒 *Verification Required*\n\nAn OTP has been sent via SMS to the number ending in *:phone*.\n\n👉 *Please enter the code here to continue.*",
    'invalid_otp' => "❌ Invalid OTP. Please try again.",
    'logout_success' => "✅ You have successfully logged out.",
    'invalid_option' => "⚠️ Invalid option. Please try again.",
    'too_many_attempts' => "🚫 Too many failed attempts. Session ended.",
    
    // Global Elements
    'not_enrolled' => "⚠️ Student is not enrolled in an active academic session.",
    'no_data_found' => "⚠️ No records found.",
    'action_cancelled' => "🚫 Action cancelled.",

    // --- STUDENT MENU (Portail) ---
    'menu_student' => "🎓 *Main Menu - University*\n\n1️⃣ Academic Fees\n2️⃣ Schedules\n3️⃣ Academic Results\n4️⃣ Academic Work\n5️⃣ Academic Notifications\n0️⃣ Logout",
    'menu_student_fees' => "💰 *Academic Fees*\n\n11️⃣ Tuition Fees\n12️⃣ Enrollment\n13️⃣ Other Fees\n14️⃣ My Payments\n00️⃣ Back",
    'menu_student_schedules' => "📅 *Schedules*\n\n21️⃣ Exams / Tests\n22️⃣ Courses\n23️⃣ Credits per Course\n00️⃣ Back",
    'menu_student_results' => "📊 *Academic Results*\n\n31️⃣ Semester I\n32️⃣ Semester II\n33️⃣ General Average\n34️⃣ Validated / Non-validated Courses\n00️⃣ Back",
    'menu_student_work' => "📚 *Academic Work*\n\n41️⃣ Practical Work (TP)\n42️⃣ Homework\n00️⃣ Back",

    // --- PARENT MENU (Bonjour) ---
    'menu_parent' => "👨‍👩‍👧 *Main Menu - Parent*\n\n1️⃣ e-TP / e-Homework / e-Work\n2️⃣ View Yearly Fees\n3️⃣ My Payments\n4️⃣ Derogation (Leave Requests)\n5️⃣ My Requests\n6️⃣ Schedules\n7️⃣ e-Report Card\n8️⃣ QR Code for Child Pickup\n9️⃣ My Children\n0️⃣ Logout",
    'menu_parent_derogation' => "📝 *Derogation Request*\n\nSelect Duration:\n1️⃣ 3 days\n2️⃣ 7 days\n3️⃣ 10 days\n4️⃣ 14 days\n00️⃣ Cancel",
    'menu_parent_requests' => "📝 *My Requests*\n\n51️⃣ Early Departure\n52️⃣ Hospital\n53️⃣ Emergency\n54️⃣ Delay\n55️⃣ Absence\n00️⃣ Cancel",
    'menu_parent_schedule' => "📅 *Schedules*\n\n61️⃣ Courses\n62️⃣ Tests / Exams\n00️⃣ Back",
    
    'derogation_sms_receipt' => "Your derogation request has been received. Ticket Ref: #:ticket. Student: :student. Requested Days: :days. Will be processed within 48 hours.",
    'request_submitted' => "✅ Request successfully submitted.\nTicket Ref: #:ticket",

    // --- TEACHER MENU (Agent) ---
    'menu_teacher' => "👨‍🏫 *Main Menu - Teacher*\n\n1️⃣ Clock-in via QR Code {OTP}\n2️⃣ My Schedule\n3️⃣ My Tests\n4️⃣ My Requests\n0️⃣ Logout",
    'menu_teacher_requests' => "📝 *My Requests*\n\n41️⃣ Salary Advance {OTP}\n42️⃣ Leave Request\n43️⃣ Report Illness\n44️⃣ Impediment\n45️⃣ Delay\n00️⃣ Back",
    'menu_teacher_advance' => "💰 *Salary Advance*\n\nSelect level:\n1️⃣ 50%\n2️⃣ 30%\n3️⃣ 20%\n4️⃣ 10%\n00️⃣ Cancel",
    
    'teacher_clockin_success' => "✅ Successfully clocked in for today at :time.",
    'advance_sms_receipt' => "Your salary advance request has been received. Ticket Ref: #:ticket. Response will be provided within 48 hours.",

    // --- HEADOFF MENU (Digitex) ---
    'menu_headoff' => "🏢 *Main Menu - Head Office*\n\n1️⃣ Headcount (Students & Staff)\n2️⃣ Fee Payments\n3️⃣ Budget & Finance\n4️⃣ Rankings\n0️⃣ Logout",
    'menu_headoff_headcount' => "👥 *Headcount*\n\n11️⃣ Global\n12️⃣ By School / By Class\n00️⃣ Back",
    'menu_headoff_fees' => "💰 *Fee Payments*\n\n21️⃣ Global | Forecast\n22️⃣ Daily Cash Status\n23️⃣ Students in Good Standing\n24️⃣ Debtor Students\n00️⃣ Back",
    'menu_headoff_budget' => "📉 *Budget & Finance*\n\n31️⃣ Global Budget (All Schools)\n32️⃣ Budget per School\n33️⃣ Global Expenses\n34️⃣ Expenses per School\n35️⃣ Pending Fund Requests\n00️⃣ Back",
    'menu_headoff_rankings' => "🏆 *Rankings*\n\n41️⃣ By Headcount\n42️⃣ By Payments\n43️⃣ By Budget\n44️⃣ By Expenses\n00️⃣ Back",
    
    // Outputs
    'fees_output' => "💰 *Fee Detail:*\nTotal: :total\nPaid: :paid\nBalance: :balance",
    'schedule_output' => "📅 *Schedule:*\n:content",
    'qr_caption' => "Pickup QR for :student.\nValid for 2 hours.",
];