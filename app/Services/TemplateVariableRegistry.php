<?php

namespace App\Services;

use App\Models\SmsTemplate;

class TemplateVariableRegistry
{
    /** @var array<string, string>|null */
    private static ?array $definitions = null;

    /** @return array<string, string> event_key => comma-separated tag names (no $) */
    private static function definitions(): array
    {
        if (self::$definitions !== null) {
            return self::$definitions;
        }

        self::$definitions = [
            'payment_received' => 'StudentName, ParentName, Amount, Balance, RemainingBalance, SchoolName, Date, TransactionID, Class, Grade, Section, Session, PaymentReason, InstallmentName, DueDate',
            'student_welcome' => 'Name, SchoolName, Email, Url, Shortcode, LoginId, Username, Password',
            'staff_welcome' => 'Name, SchoolName, LoginId, Username, Shortcode, Email, Password, Url',
            'teacher_welcome' => 'Name, SchoolName, LoginId, Username, Shortcode, Email, Password',
            'head_officer_welcome' => 'Name, SchoolName, LoginId, Username, Shortcode, Email, Password, Url',
            'institution_created' => 'Name, SchoolName, LoginId, Username, Shortcode, Email, Password',
            'user_welcome' => 'Name, SchoolName, LoginId, Username, Shortcode, Email, Password',
            'guardian_welcome' => 'SchoolName, Password, Url',
            'invoice_created' => 'StudentName, ParentName, Amount, AmountDue, OutstandingAmount, InvoiceNumber, DueDate, Class, Grade, Section, Session, InstallmentName, SchoolName',
            'low_balance' => 'SchoolName, Credits',
            'candidate_added' => 'StudentName, Position',
            'fee_reminder' => 'ParentName, StudentName, Class, Grade, Section, Session, InstallmentName, OutstandingAmount, AmountDue, DueDate, TotalDebt, Currency, SchoolName, InvoiceNumber',
            'exam_reminder' => 'ParentName, StudentName, ClassName, ExamDetails, SchoolName',
            'payment_proof_submitted' => 'StudentName, Amount, InvoiceNumber, SchoolName, PayerName',
            'payment_proof_rejected' => 'StudentName, Amount, InvoiceNumber, SchoolName, Reason',
            'student_arrival' => 'ParentName, StudentName, Time, Date, SchoolName',
            'student_departure' => 'ParentName, StudentName, Time, Date, SchoolName',
            'student_absent' => 'StudentName, Date, SchoolName, ParentName',
            'attendance_weekly_summary' => 'StudentName, SchoolName, PeriodLabel, TotalDays, Present, Absent, Late, Percentage, PrevPercentage',
            'attendance_monthly_summary' => 'StudentName, SchoolName, PeriodLabel, TotalDays, Present, Absent, Late, Percentage, PrevPercentage',
            'request_submitted' => 'StudentName, RequestType, TicketNumber, SchoolName, SchoolYear, Class',
            'request_submitted_parent' => 'StudentName, TicketNumber, RequestType, ResponseTime, SchoolName, SchoolYear, Class, Days',
            'request_updated' => 'TicketNumber, StudentName, RequestType, Status, ApprovedDays, AdminNote, SchoolName',
            'derogation_reminder' => 'StudentName, TicketNumber, Deadline, SchoolName',
            'derogation_expired' => 'StudentName, TicketNumber, SchoolName',
            'derogation_honored' => 'StudentName, TicketNumber, SchoolName',
            'notice_published' => 'Title, SchoolName',
            'exam_published' => 'ExamName, StudentName, SchoolName',
            'pickup_scan' => 'StudentName, Time, SchoolName',
            'pickup_status_updated' => 'StudentName, Status, SchoolName',
            'staff_leave_submitted' => 'StaffName, SchoolName',
            'staff_leave_updated' => 'StaffName, Status, SchoolName',
            'fund_request_submitted' => 'Requester, Title, Amount, TicketNumber, SchoolName',
            'fund_request_processed' => 'Requester, Title, Status, SchoolName, Amount, Remaining, TicketNumber, Reason',
            'budget_consumed' => 'BudgetLine, ExpenseTitle, Amount, Remaining, SchoolName, Requester',
            'disciplinary_incident' => 'StudentName, IncidentType, Title, Severity, Date, Reference, ActionTaken, SchoolName',
            'otp_login' => 'OTP, Name, SchoolName',
            'event_invitation' => 'ParentName, StudentName, ClassName, EventName, EventDate, EventTime, Venue, TicketNumber, SchoolName',
            'agent_payment_processed' => 'AgentName, Amount, Period, SchoolName',
        ];

        return self::$definitions;
    }

    /** @return list<string> tag names without $ prefix */
    public static function tagNamesFor(string $eventKey): array
    {
        $tags = isset(self::definitions()[$eventKey])
            ? self::parseTagNames(self::definitions()[$eventKey])
            : [];

        if (class_exists(SmsTemplate::class)) {
            $stored = SmsTemplate::whereNull('institution_id')->where('event_key', $eventKey)->value('available_tags');
            if ($stored) {
                $tags = array_values(array_unique(array_merge($tags, self::parseTagNames($stored))));
            }
        }

        return $tags;
    }

    /** @return array<string, list<string>> */
    public static function all(): array
    {
        $keys = array_unique(array_merge(
            array_keys(self::definitions()),
            SmsTemplate::whereNull('institution_id')->pluck('event_key')->all()
        ));

        $out = [];
        foreach ($keys as $key) {
            $out[$key] = self::tagNamesFor($key);
        }

        ksort($out);

        return $out;
    }

    public static function forEvent(string $eventKey): array
    {
        return array_map(fn ($t) => '$' . $t, self::tagNamesFor($eventKey));
    }

    public static function displayForEvent(string $eventKey): string
    {
        return implode(', ', self::forEvent($eventKey));
    }

    public static function validateBody(string $eventKey, string $body): array
    {
        $allowed = self::forEvent($eventKey);
        preg_match_all('/\$([A-Za-z0-9_]+)/', $body, $matches);
        $unknown = array_diff(array_map(fn ($t) => '$' . $t, $matches[1] ?? []), $allowed);

        return array_values($unknown);
    }

    /** @return list<string> */
    private static function parseTagNames(string $csv): array
    {
        return array_values(array_filter(array_map(
            fn ($t) => trim(ltrim(trim($t), '$')),
            preg_split('/\s*,\s*/', $csv) ?: []
        )));
    }
}
