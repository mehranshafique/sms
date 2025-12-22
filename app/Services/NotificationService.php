<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Student;
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
            $this->smsGateway->send($phone, $message);
        }
    }

    /**
     * Format Localized SMS Message
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
}