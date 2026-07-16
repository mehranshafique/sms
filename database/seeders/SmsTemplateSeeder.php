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
                'body' => 'Welcome $Name to $SchoolName! Your Admission No: $Shortcode. Login ID: $LoginId | Email: $Email | Password: $Password. URL: $Url',
                'available_tags' => '$Name, $SchoolName, $LoginId, $Username, $Shortcode, $Email, $Url, $Password',
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
                'body' => 'New request from $StudentName. Type: $RequestType. Ticket: $TicketNumber. Please review in the admin panel.',
                'available_tags' => '$StudentName, $RequestType, $TicketNumber, $SchoolName',
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
                'body' => '$Requester submitted a fund request: $Title. Amount: $Amount.',
                'available_tags' => '$Requester, $Title, $Amount, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'fund_request_processed',
                'name' => 'Fund Request Processed',
                'body' => 'Your fund request "$Title" was $Status.',
                'available_tags' => '$Title, $Status, $SchoolName',
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