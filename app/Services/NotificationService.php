<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Models\Institution;
use App\Mail\PaymentReceived;
use App\Mail\UserCredentialsMail; 
use App\Enums\RoleEnum; 
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    // Define event keys that should ignore credit limits
    protected $unlimitedGlobalEvents = [
        'institution_created',
        'low_balance',
        'subscription_expiry',
        'subscription_expired',
        'system_alert'
    ];

    public function __construct()
    {
        // We resolve SMS services dynamically to prevent autoloader errors during boot
    }

    public function sendPaymentNotification(Payment $payment)
    {
        $invoice = $payment->invoice;
        $student = $invoice->student;
        $institution = $invoice->institution;
        
        // 1. Email
        if ($student->email) {
            try {
                Mail::to($student->email)->queue(new PaymentReceived($payment));
            } catch (\Exception $e) {
                Log::error("Email Notification Failed: " . $e->getMessage());
            }
        }

        $phone = $student->mobile_number ?? $student->father_phone;

        // 2. SMS & WhatsApp
        if ($phone) {
            $data = [
                'StudentName' => $student->first_name,
                'Amount' => number_format($payment->amount, 2),
                'SchoolName' => $institution->name,
                'Balance' => number_format($invoice->total_amount - $invoice->paid_amount, 2),
                'Currency' => config('app.currency_symbol', '$'),
                'Date' => $payment->payment_date->format('d/m/Y'),
                'TransactionID' => $payment->transaction_id ?? 'N/A',
                // snake_case aliases
                'student_name' => $student->first_name,
                'school_name' => $institution->name,
                'transaction_id' => $payment->transaction_id ?? 'N/A'
            ];

            // Send via SMS (Mobishastra)
            $this->sendNotificationEvent('payment_received', $phone, $data, $institution->id, 'sms');
            
            // Send via WhatsApp (Infobip)
            $this->sendNotificationEvent('payment_received', $phone, $data, $institution->id, 'whatsapp');
        }
    }

    public function sendUserCredentials(User $user, $plainPassword, $roleEnumVal)
    {
        $schoolName = $user->institute ? $user->institute->name : config('app.name');
        $roleLabel = __('roles.' . strtolower($roleEnumVal));

        $data = [
            'Name' => $user->name,
            'Email' => $user->email,
            'Shortcode' => $user->shortcode ?? 'N/A',
            'Username' => $user->username ?? $user->shortcode ?? 'N/A',
            'Password' => $plainPassword ?? __('auth.unchanged'),
            'Role' => $roleLabel, 
            'SchoolName' => $schoolName,
            'Url' => route('login'),
            'LoginLink' => route('login'),

            // snake_case aliases
            'name' => $user->name,
            'email' => $user->email,
            'shortcode' => $user->shortcode ?? 'N/A',
            'username' => $user->username ?? $user->shortcode ?? 'N/A',
            'password' => $plainPassword ?? __('auth.unchanged'),
            'role' => $roleLabel,
            'school_name' => $schoolName,
            'url' => route('login'),
            'login_link' => route('login'),
        ];

        // 1. Send Email
        if ($user->email) {
            try {
                Mail::to($user->email)->send(new UserCredentialsMail($data));
            } catch (\Exception $e) {
                Log::error("Failed to send credential email: " . $e->getMessage());
            }
        }

        // 2. Send SMS & WhatsApp
        if ($user->phone) {
            $templateKey = strtolower(str_replace(' ', '_', $roleEnumVal)) . '_welcome'; 
            
            // Try specific template first, then fallback to generic
            $smsSent = $this->sendNotificationEvent($templateKey, $user->phone, $data, $user->institute_id, 'sms');
            if (!$smsSent) {
                $this->sendNotificationEvent('user_welcome', $user->phone, $data, $user->institute_id, 'sms');
            }

            // Send WhatsApp
            $waSent = $this->sendNotificationEvent($templateKey, $user->phone, $data, $user->institute_id, 'whatsapp');
            if (!$waSent) {
                $this->sendNotificationEvent('user_welcome', $user->phone, $data, $user->institute_id, 'whatsapp');
            }
        }
    }

    public function sendInstitutionCreation($institution, $adminUser, $plainPassword)
    {
        $schoolName = $institution->name;
        $roleLabel = __('roles.head_officer');
        
        $data = [
            'Name' => $adminUser->name,
            'Email' => $adminUser->email,
            'Shortcode' => $adminUser->shortcode ?? 'N/A',
            'Password' => $plainPassword,
            'Role' => $roleLabel, 
            'SchoolName' => $schoolName,
            'Url' => route('login'),
            'LoginLink' => route('login'),
            // Aliases
            'name' => $adminUser->name,
            'email' => $adminUser->email,
            'shortcode' => $adminUser->shortcode ?? 'N/A',
            'password' => $plainPassword,
            'role' => $roleLabel,
            'school_name' => $schoolName,
            'url' => route('login'),
            'login_link' => route('login'),
        ];

        // Email
        if ($adminUser->email) {
            try {
                Mail::to($adminUser->email)->send(new UserCredentialsMail($data));
            } catch (\Exception $e) {
                Log::error("Failed to send creation email: " . $e->getMessage());
            }
        }

        // SMS & WhatsApp
        if ($adminUser->phone) {
            $this->sendNotificationEvent('institution_created', $adminUser->phone, $data, $institution->id, 'sms');
            $this->sendNotificationEvent('institution_created', $adminUser->phone, $data, $institution->id, 'whatsapp');
        }
    }

    public function sendHeadOfficerCredentials(User $user, $plainPassword, array $assignedInstituteIds)
    {
        $institutes = \App\Models\Institution::whereIn('id', $assignedInstituteIds)->pluck('name')->toArray();
        $schoolName = implode(', ', $institutes);
        
        $user->setRelation('institute', new Institution(['name' => $schoolName]));

        $this->sendUserCredentials($user, $plainPassword, RoleEnum::HEAD_OFFICER->value);
    }

    /**
     * Unified method to fetch template and send via specific channel
     */
    public function sendNotificationEvent($eventKey, $to, $data = [], $institutionId = null, $channel = 'sms')
    {
        // Fetch Template from Database
        $template = \App\Models\SmsTemplate::forEvent($eventKey, $institutionId)->first();

        if (!$template || !$template->is_active) {
            return false;
        }

        // Replace Placeholders
        $message = $template->body;
        foreach ($data as $key => $value) {
            $message = str_replace('$' . $key, $value, $message);
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        
        $isUnlimited = in_array($eventKey, $this->unlimitedGlobalEvents);

        return $this->performSend($to, $message, $institutionId, $isUnlimited, $channel);
    }

    protected function performSend($to, $message, $institutionId, $isUnlimited = false, $channel = 'sms') 
    {
        $institution = Institution::find($institutionId);
        
        if (!$institution) {
             return false;
        }

        // Credit Check
        $creditColumn = ($channel === 'whatsapp') ? 'whatsapp_credits' : 'sms_credits';
        
        if (!$isUnlimited && $institution->$creditColumn <= 0) {
            Log::warning(strtoupper($channel) . " Failed: Insufficient credits for Institution ID " . ($institutionId ?? 'Unknown'));
            return false;
        }
        
        // Select Provider & Send
        $sent = false;
        try {
            if ($channel === 'whatsapp') {
                // Resolve Infobip Service Dynamically
                $infobip = app(\App\Services\Sms\InfobipService::class);
                $sent = $infobip->sendWhatsApp($to, $message);
            } else {
                // Resolve Mobishastra Service Dynamically
                $mobishastra = app(\App\Services\Sms\MobishastraService::class);
                $sent = $mobishastra->send($to, $message);
            }
        } catch (\Exception $e) {
            Log::error("Notification Service Error ($channel): " . $e->getMessage());
            return false;
        }
        
        // Deduct Credit
        if ($sent && !$isUnlimited) {
            $institution->decrement($creditColumn);
        }
        
        return $sent;
    }
}