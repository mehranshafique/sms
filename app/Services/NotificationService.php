<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Mail\PaymentReceived;
use App\Interfaces\SmsGatewayInterface;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected $smsGateway;

    public function __construct(SmsGatewayInterface $smsGateway)
    {
        $this->smsGateway = $smsGateway;
    }

    /**
     * Trigger Payment Notifications (Email & SMS)
     */
    public function sendPaymentNotification(Payment $payment)
    {
        $invoice = $payment->invoice;
        $student = $invoice->student;
        
        // 1. Send Email (Async Queue)
        if ($student->email) {
            try {
                Mail::to($student->email)->queue(new PaymentReceived($payment));
            } catch (\Exception $e) {
                Log::error("Email Notification Failed: " . $e->getMessage());
            }
        }

        // 2. Send SMS
        // Check both student mobile and father/guardian phone
        $phone = $student->mobile_number ?? $student->father_phone;

        if ($phone) {
            $message = $this->formatSmsMessage($payment, $student);
            // Fallback to direct send if no template found or use specific payment template logic
            // Ideally should use sendSmsEvent here too if payment_received template exists
            $this->smsGateway->send($phone, $message);
        }
    }

    /**
     * Format Localized SMS Message (Legacy Support)
     */
    private function formatSmsMessage(Payment $payment, Student $student)
    {
        $institution = $payment->invoice->institution;
        $balance = $payment->invoice->total_amount - $payment->invoice->paid_amount;

        return __('payment.sms_template', [
            'name' => $student->first_name,
            'amount' => number_format($payment->amount, 2),
            'school' => $institution->name,
            'balance' => number_format($balance, 2),
        ]);
    }

    /**
     * Generic Sender using Templates (From previous context)
     */
    public function sendSmsEvent($eventKey, $to, $data = [], $institutionId = null)
    {
        // 1. Fetch Template
        $template = \App\Models\SmsTemplate::forEvent($eventKey, $institutionId)->first();

        if (!$template || !$template->is_active) {
            Log::info("SMS Skipped: No active template for [$eventKey]");
            return false;
        }

        // 2. Replace Tags
        $message = $template->body;
        foreach ($data as $key => $value) {
            $message = str_replace('$' . $key, $value, $message);
        }

        // 3. Send
        return $this->smsGateway->send($to, $message);
    }

    /**
     * Send Institution Creation Notification (Admin)
     */
    public function sendInstitutionCreation($institution, $adminUser, $plainPassword)
    {
        $data = [
            'Acronym' => $institution->code ?? '',
            'InstitutionName' => $institution->name,
            'ID' => $adminUser->email,
            'Pw' => $plainPassword
        ];

        if ($adminUser->mobile_number) {
            $this->sendSmsEvent('institution_creation', $adminUser->mobile_number, $data, null);
        }
    }

    /**
     * Send Head Officer Credentials & Assignments
     */
    public function sendHeadOfficerCredentials(User $user, $plainPassword, array $assignedInstituteIds)
    {
        // 1. Get Institution Names
        $institutes = \App\Models\Institution::whereIn('id', $assignedInstituteIds)->pluck('name')->toArray();
        $instituteList = implode(', ', $institutes);

        $data = [
            'Name' => $user->name,
            'Email' => $user->email,
            'Password' => $plainPassword ?? __('auth.unchanged'),
            'Institutions' => $instituteList,
            'Url' => url('/login'),
        ];

        // 2. Send SMS
        // Event Key: 'head_officer_welcome' (Needs to be seeded/created in DB)
        if ($user->phone) {
            $this->sendSmsEvent('head_officer_welcome', $user->phone, $data, null);
        }

        // 3. Send Email (Placeholder for logic)
        // if ($user->email) {
        //    Mail::to($user->email)->send(new HeadOfficerWelcomeMail($data));
        // }
    }
}