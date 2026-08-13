<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SmsTemplate;

class SmsTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $templates = [
            // Payment
            [
                'event_key' => 'payment_received',
                'name' => 'Payment Received',
                'body' => 'Dear Parent, payment of $Amount for $StudentName ($Class, $Session) for $PaymentReason has been received. Remaining Balance: $Balance. Thank you, $SchoolName.',
                'available_tags' => '$StudentName, $ParentName, $Amount, $Balance, $RemainingBalance, $SchoolName, $Date, $TransactionID, $Class, $Grade, $Section, $Session, $PaymentReason, $InstallmentName, $DueDate',
                'is_active' => true,
            ],
            
            // Student Welcome
            [
                'event_key' => 'student_welcome',
                'name' => 'Student Welcome',
                'body' => 'Welcome $Name to $SchoolName! Class: $Class. Your Admission No: $Shortcode. Login ID: $LoginId | Password: $Password. URL: $Url',
                'available_tags' => '$Name, $Class, $SchoolName, $LoginId, $Username, $Shortcode, $Email, $Url, $Password',
                'is_active' => true,
            ],
            
            // Staff / Teacher Welcome (New)
            [
                'event_key' => 'staff_welcome', // Used by NotificationService logic
                'name' => 'Staff Welcome',
                'body' => 'Hello $Name, welcome to the team at $SchoolName! Login ID: $LoginId | Email: $Email | Password: $Password | URL: $Url',
                'available_tags' => '$Name, $SchoolName, $LoginId, $Username, $Shortcode, $Email, $Password, $Url',
                'is_active' => true,
            ],
            [
                'event_key' => 'teacher_welcome', // Specific for teachers if needed
                'name' => 'Teacher Welcome',
                'body' => 'Hello $Name, welcome to $SchoolName as a Teacher. Login ID: $LoginId | Email: $Email | Password: $Password',
                'available_tags' => '$Name, $SchoolName, $LoginId, $Username, $Shortcode, $Email, $Password',
                'is_active' => true,
            ],

            // Head Officer / Admin
            [
                'event_key' => 'head_officer_welcome',
                'name' => 'Head Officer Welcome',
                'body' => 'Hello $Name, you are appointed as Head Officer at $SchoolName. Login ID: $LoginId | Email: $Email | Password: $Password | URL: $Url',
                'available_tags' => '$Name, $SchoolName, $LoginId, $Username, $Shortcode, $Email, $Password, $Url',
                'is_active' => true,
            ],
            
            // Institution Creation (Super Admin Trigger)
            [
                'event_key' => 'institution_created',
                'name' => 'Institution Created',
                'body' => 'New Institute $SchoolName Registered. Admin: $Name. Login ID: $LoginId | Email: $Email | Password: $Password',
                'available_tags' => '$Name, $SchoolName, $LoginId, $Username, $Shortcode, $Email, $Password',
                'is_active' => true,
            ],

            // General Fallback User Welcome
            [
                'event_key' => 'user_welcome',
                'name' => 'General User Welcome',
                'body' => 'Welcome to $SchoolName. Login ID: $LoginId | Email: $Email | Password: $Password',
                'available_tags' => '$Name, $SchoolName, $LoginId, $Username, $Shortcode, $Email, $Password',
                'is_active' => true,
            ],

            // Guardian / Parent Welcome
            [
                'event_key' => 'guardian_welcome',
                'name' => 'Guardian Welcome',
                'body' => 'Welcome to the Parent Portal of $SchoolName. Login using your Phone/Email. Password: $Password.',
                'available_tags' => '$SchoolName, $Password, $Url',
                'is_active' => true,
            ],
            [
                'event_key' => 'invoice_created',
                'name' => 'Invoice Generated',
                'body' => 'Dear Parent, invoice #$InvoiceNumber of $Amount for $StudentName ($Class, $Session) — $InstallmentName is due on $DueDate. Outstanding: $OutstandingAmount. $SchoolName.',
                'available_tags' => '$StudentName, $ParentName, $Amount, $AmountDue, $OutstandingAmount, $InvoiceNumber, $DueDate, $Class, $Grade, $Section, $Session, $InstallmentName, $SchoolName',
                'is_active' => true,
            ],
            // System Alerts
            [
                'event_key' => 'low_balance',
                'name' => 'Low SMS Balance Warning',
                'body' => 'Alert: Your school $SchoolName is running low on SMS credits. Please recharge immediately.',
                'available_tags' => '$SchoolName, $Credits',
                'is_active' => true,
            ],
            [
                'event_key' => 'candidate_added',
                'name' => 'Candidate Registration',
                'body' => 'Your child $StudentName has submitted a candidacy for the position of $Position.',
                'available_tags' => '$StudentName, $Position',
                'is_active' => true,
            ],
            // Smart Reminders (NEW)
            [
                'event_key' => 'fee_reminder',
                'name' => 'Smart Fee Reminder',
                'body' => 'Dear Parent, your child $StudentName ($Class, $Session) still owes $OutstandingAmount for $InstallmentName. Please pay before $DueDate. Total outstanding: $TotalDebt. — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $Class, $Grade, $Section, $Session, $InstallmentName, $OutstandingAmount, $AmountDue, $DueDate, $TotalDebt, $Currency, $SchoolName, $InvoiceNumber',
                'is_active' => true,
            ],
            [
                'event_key' => 'exam_reminder',
                'name' => 'Next-Day Exam Reminder',
                'body' => 'Dear $ParentName, The following exams are scheduled for tomorrow for your child $StudentName ($ClassName): $ExamDetails. Thank you for your attention, $SchoolName.',
                'available_tags' => '$ParentName, $StudentName, $ClassName, $ExamDetails, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'payment_proof_submitted',
                'name' => 'Payment Proof Submitted',
                'body' => 'Dear Parent, we received your payment proof of $Amount for $StudentName (Invoice $InvoiceNumber). Our accounts team will review it shortly. — $SchoolName',
                'available_tags' => '$StudentName, $Amount, $InvoiceNumber, $SchoolName, $PayerName',
                'is_active' => true,
            ],
            [
                'event_key' => 'payment_proof_rejected',
                'name' => 'Payment Proof Rejected',
                'body' => 'Dear Parent, your payment proof for $StudentName (Invoice $InvoiceNumber, $Amount) could not be verified. Please resubmit or contact the school. — $SchoolName',
                'available_tags' => '$StudentName, $Amount, $InvoiceNumber, $SchoolName, $Reason',
                'is_active' => true,
            ],

             // --- RFID Access Control Templates ---
            [
                'event_key' => 'student_arrival',
                'name' => 'RFID Gate: Student Arrival',
                'body' => 'Dear $ParentName, your child $StudentName has securely arrived at $SchoolName at $Time on $Date.',
                'available_tags' => '$ParentName, $StudentName, $Time, $Date, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'student_departure',
                'name' => 'RFID Gate: Student Departure',
                'body' => 'Dear $ParentName, your child $StudentName has safely exited $SchoolName at $Time on $Date.',
                'available_tags' => '$ParentName, $StudentName, $Time, $Date, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'student_absent',
                'name' => 'Student Absence Alert',
                'body' => 'Dear Parent, your child $StudentName was marked absent from $SchoolName on $Date. Please contact the school if you have any questions.',
                'available_tags' => '$StudentName, $Date, $SchoolName, $ParentName',
                'is_active' => true,
            ],
            [
                'event_key' => 'attendance_weekly_summary',
                'name' => 'Weekly Attendance Summary',
                'body' => '$SchoolName: $StudentName — $PeriodLabel. Days: $TotalDays, Present: $Present, Absent: $Absent, Late: $Late. Attendance: $Percentage (prev: $PrevPercentage).',
                'available_tags' => '$StudentName, $SchoolName, $PeriodLabel, $TotalDays, $Present, $Absent, $Late, $Percentage, $PrevPercentage',
                'is_active' => true,
            ],
            [
                'event_key' => 'attendance_monthly_summary',
                'name' => 'Monthly Attendance Summary',
                'body' => '$SchoolName — Monthly report for $StudentName ($PeriodLabel). Present: $Present/$TotalDays days. Attendance: $Percentage (last month: $PrevPercentage). Absent: $Absent, Late: $Late.',
                'available_tags' => '$StudentName, $SchoolName, $PeriodLabel, $TotalDays, $Present, $Absent, $Late, $Percentage, $PrevPercentage',
                'is_active' => true,
            ],
            // --- Automated Request / Ticket Processing ---
            [
                'event_key' => 'request_submitted',
                'name' => 'New Student Request Submitted',
                'body' => 'New request from $StudentName ($Class, $SchoolYear). Type: $RequestType. Ticket: $TicketNumber. Please review in the admin panel.',
                'available_tags' => '$StudentName, $RequestType, $TicketNumber, $SchoolName, $SchoolYear, $Class',
                'is_active' => true,
            ],
            [
                'event_key' => 'request_updated',
                'name' => 'Student Ticket/Request Processed',
                'body' => '🎫 TICKET UPDATE
Ticket: $TicketNumber
Student: $StudentName
Type: $RequestType
Status: $Status 
$ApprovedDays
$AdminNote
Thank you, $SchoolName.',
                'available_tags' => '$TicketNumber, $StudentName, $RequestType, $Status, $ApprovedDays, $AdminNote, $SchoolName',
                'is_active' => true,
            ],

            // --- In-App (System) Events ---
            [
                'event_key' => 'notice_published',
                'name' => 'Announcement Published',
                'body' => 'New announcement: $Title. Log in to read the full notice.',
                'available_tags' => '$Title, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'exam_published',
                'name' => 'Exam Results Published',
                'body' => 'Results for $ExamName are now available. Log in to view your marks.',
                'available_tags' => '$ExamName, $StudentName, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'resit_notification',
                'name' => 'End-of-Year Resit Subjects',
                'body' => 'Dear $ParentName, $StudentName ($ClassName) must take a resit/remedial exam in: $ResitSubjects. — $SchoolName ($AcademicYear)',
                'available_tags' => '$ParentName, $StudentName, $ClassName, $ResitSubjects, $SchoolName, $AcademicYear',
                'is_active' => true,
            ],
            [
                'event_key' => 'pickup_scan',
                'name' => 'Student Pickup Scan',
                'body' => '$StudentName was scanned at the gate for pickup at $Time.',
                'available_tags' => '$StudentName, $Time, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'pickup_status_updated',
                'name' => 'Pickup Status Updated',
                'body' => 'Pickup for $StudentName has been updated to $Status.',
                'available_tags' => '$StudentName, $Status, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'staff_leave_submitted',
                'name' => 'Staff Leave Request Submitted',
                'body' => '$StaffName submitted a leave request. Please review and approve.',
                'available_tags' => '$StaffName, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'staff_leave_updated',
                'name' => 'Staff Leave Request Updated',
                'body' => 'Your leave request status is now $Status.',
                'available_tags' => '$StaffName, $Status, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'fund_request_submitted',
                'name' => 'Fund Request Submitted',
                'body' => 'Hi $Requester, fund request $TicketNumber ($Amount) submitted. Pending approval. — $SchoolName',
                'available_tags' => '$Requester, $Title, $Amount, $TicketNumber, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'fund_request_processed',
                'name' => 'Fund Request Processed',
                'body' => 'Hi $Requester, fund request $TicketNumber ($Amount) is $Status. — $SchoolName',
                'available_tags' => '$Requester, $Title, $Status, $Amount, $Remaining, $TicketNumber, $Reason, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'budget_consumed',
                'name' => 'Budget Consumption Alert',
                'body' => 'Budget line: $BudgetLine. Expense "$ExpenseTitle" recorded: $Amount. Remaining budget: $Remaining. — $SchoolName',
                'available_tags' => '$BudgetLine, $ExpenseTitle, $Amount, $Remaining, $SchoolName, $Requester',
                'is_active' => true,
            ],
            [
                'event_key' => 'disciplinary_incident',
                'name' => 'Disciplinary Incident',
                'body' => 'Dear Parent, $StudentName has a disciplinary record ($IncidentType): $Title on $Date. Severity: $Severity. Ref: $Reference. Action: $ActionTaken. — $SchoolName',
                'available_tags' => '$StudentName, $IncidentType, $Title, $Severity, $Date, $Reference, $ActionTaken, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'ptm_created',
                'name' => 'Parent–Teacher Meeting Scheduled',
                'body' => 'Dear $ParentName, a parent–teacher meeting ($Scope) for $StudentName is scheduled on $Date. Topic: $Topic. Class: $ClassName. Status: $Status. — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $Topic, $Date, $ClassName, $Scope, $Status, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'reenrollment_invitation',
                'name' => 'Re-enrollment Invitation',
                'body' => 'Dear $ParentName, re-enrollment for $Session is now open for $StudentName ($Class). Reply to our WhatsApp menu (option 10) or visit the school to confirm. Deposit required: $AmountRequired. Deadline: $Deadline. — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $Class, $Session, $Campaign, $AmountRequired, $AmountPaid, $Remaining, $Deadline, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'reenrollment_reminder',
                'name' => 'Re-enrollment Reminder',
                'body' => 'Reminder: re-enrollment for $StudentName ($Class) for $Session is not confirmed yet. Deadline: $Deadline. Remaining to pay: $Remaining. — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $Class, $Session, $Campaign, $AmountRequired, $AmountPaid, $Remaining, $Deadline, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'reenrollment_confirmation_received',
                'name' => 'Re-enrollment Confirmation Received',
                'body' => 'Dear $ParentName, we received your re-enrollment confirmation for $StudentName ($Class). Status: $Status. — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $Class, $Status, $Campaign, $Session, $AmountRequired, $AmountPaid, $Remaining, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'reenrollment_partial_confirmation',
                'name' => 'Re-enrollment Partial Confirmation',
                'body' => 'Dear $ParentName, confirmation for $StudentName is partial. Please pay remaining $Remaining (required $AmountRequired). — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $Class, $Status, $AmountRequired, $AmountPaid, $Remaining, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'reenrollment_confirmed',
                'name' => 'Re-enrollment Approved',
                'body' => 'Dear $ParentName, re-enrollment for $StudentName has been approved for $Session. — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $Class, $Session, $Campaign, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'reenrollment_declined',
                'name' => 'Re-enrollment Declined',
                'body' => 'Dear $ParentName, re-enrollment for $StudentName was recorded as declined. — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $Class, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'reenrollment_rejected',
                'name' => 'Re-enrollment Rejected by School',
                'body' => 'Dear $ParentName, the school could not approve re-enrollment for $StudentName. Please contact the office. — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $Class, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'pre_enrollment_received',
                'name' => 'Pre-Enrollment Confirmation',
                'body' => 'Dear $ParentName, pre-enrollment for $StudentName is registered. Temporary ID: $TemporaryId. — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $TemporaryId, $Class, $Option, $Status, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'pre_enrollment_test_invite',
                'name' => 'Admission Test Invitation',
                'body' => 'Dear $ParentName, $StudentName ($TemporaryId) is invited for the admission test on $TestDate at $TestLocation. — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $TemporaryId, $TestDate, $TestLocation, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'pre_enrollment_test_reminder',
                'name' => 'Admission Test Reminder',
                'body' => 'Reminder: admission test for $StudentName ($TemporaryId) on $TestDate at $TestLocation. — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $TemporaryId, $TestDate, $TestLocation, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'pre_enrollment_admitted',
                'name' => 'Admission Test Passed',
                'body' => 'Congratulations $ParentName: $StudentName ($TemporaryId) is admitted. Please contact the school to finalize enrollment. — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $TemporaryId, $TestScore, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'pre_enrollment_not_admitted',
                'name' => 'Admission Test Not Passed',
                'body' => 'Dear $ParentName, $StudentName ($TemporaryId) was not admitted after the admission test. — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $TemporaryId, $TestScore, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'pre_enrollment_finalize_invite',
                'name' => 'Finalize Enrollment Invitation',
                'body' => 'Dear $ParentName, please visit the school to finalize enrollment for $StudentName ($TemporaryId). — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $TemporaryId, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'pre_enrollment_finalized',
                'name' => 'Enrollment Finalized',
                'body' => 'Dear $ParentName, enrollment is finalized for $StudentName. Student ID: $AdmissionNumber (ref $TemporaryId). — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $TemporaryId, $AdmissionNumber, $Class, $SchoolName',
                'is_active' => true,
            ],
        ];

        
        foreach ($templates as $tmpl) {
            SmsTemplate::updateOrCreate(
                ['event_key' => $tmpl['event_key'], 'institution_id' => null], // Null = Global Template
                $tmpl
            );
        }
        
        // Output confirmation to console
        $this->command->info('Updated SMS Templates seeded successfully.');
    }
}