<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\Invoice; // Added
use App\Mail\PaymentReceived;
use App\Mail\InvoiceCreatedMail; // Will create this
use App\Mail\UserCredentialsMail; 
use App\Services\Sms\GatewayFactory;
use App\Services\Sms\InfobipService; 
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected $unlimitedGlobalEvents = [
        'institution_created',
        'low_balance',
        'subscription_expiry',
        'subscription_expired',
        'system_alert'
    ];

    /**
     * Check if a specific channel is enabled for an event in the institution settings.
     * Default to true if settings are not found (for backward compatibility).
     */
    protected function isChannelEnabled($institutionId, $eventKey, $channel)
    {
        // For unlimited global events (system level), usually always enabled or handled separately.
        // But if you want granular control even for these, remove this check.
        if (in_array($eventKey, $this->unlimitedGlobalEvents)) {
            return true;
        }

        $settings = InstitutionSetting::get($institutionId, 'notification_preferences');
        
        if (!$settings) {
            // Default: All enabled if no config exists yet
            return true; 
        }

        $preferences = is_string($settings) ? json_decode($settings, true) : $settings;

        // Structure: ['student_created' => ['email' => true, 'sms' => false, ...]]
        if (isset($preferences[$eventKey]) && isset($preferences[$eventKey][$channel])) {
            return (bool) $preferences[$eventKey][$channel];
        }

        return true; // Default to enabled
    }

    /**
     * Send Invoice Notification (New Method)
     */
    public function sendInvoiceNotification(Invoice $invoice)
    {
        $student = $invoice->student;
        $institution = $invoice->institution;
        $eventKey = 'invoice_created';

        // 1. Email
        if ($this->isChannelEnabled($institution->id, $eventKey, 'email') && $student->email) {
            try {
                // Check if Mailable exists before sending to avoid errors if file missing
                if (class_exists(InvoiceCreatedMail::class)) {
                    Mail::to($student->email)->queue(new InvoiceCreatedMail($invoice));
                    Log::info("Invoice Email queued for {$student->email}");
                } else {
                     Log::warning("InvoiceCreatedMail class not found. Email skipped.");
                }
            } catch (\Exception $e) {
                Log::error("Invoice Email Error: " . $e->getMessage());
            }
        }

        $phone = $student->mobile_number ?? $student->father_phone;

        if ($phone) {
            $data = [
                'StudentName' => $student->first_name,
                'Amount' => number_format($invoice->total_amount, 2),
                'InvoiceNumber' => $invoice->invoice_number,
                'DueDate' => $invoice->due_date->format('d/m/Y'),
                'SchoolName' => $institution->name,
                'Currency' => config('app.currency_symbol', '$'),
                // Snake Case Aliases
                'student_name' => $student->first_name,
                'school_name' => $institution->name,
                'invoice_number' => $invoice->invoice_number,
                'due_date' => $invoice->due_date->format('d/m/Y'),
            ];

            // 2. SMS
            if ($this->isChannelEnabled($institution->id, $eventKey, 'sms')) {
                $this->sendNotificationEvent($eventKey, $phone, $data, $institution->id, 'sms');
            }
            
            // 3. WhatsApp
            if ($this->isChannelEnabled($institution->id, $eventKey, 'whatsapp')) {
                $this->sendNotificationEvent($eventKey, $phone, $data, $institution->id, 'whatsapp');
            }
        }
    }

    public function sendPaymentNotification(Payment $payment)
    {
        $invoice = $payment->invoice;
        $student = $invoice->student;
        $institution = $invoice->institution;
        $eventKey = 'payment_received';
        
        // 1. Email
        if ($this->isChannelEnabled($institution->id, $eventKey, 'email') && $student->email) {
            try {
                Mail::to($student->email)->queue(new PaymentReceived($payment));
            } catch (\Exception $e) {
                Log::error("Email Error: " . $e->getMessage());
            }
        }

        $phone = $student->mobile_number ?? $student->father_phone;

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

            // 2. SMS
            if ($this->isChannelEnabled($institution->id, $eventKey, 'sms')) {
                $this->sendNotificationEvent($eventKey, $phone, $data, $institution->id, 'sms');
            }
            
            // 3. WhatsApp
            if ($this->isChannelEnabled($institution->id, $eventKey, 'whatsapp')) {
                $this->sendNotificationEvent($eventKey, $phone, $data, $institution->id, 'whatsapp');
            }
        }
    }

    public function sendUserCredentials(User $user, $plainPassword, $roleEnumVal)
    {
        $schoolName = $user->institute ? $user->institute->name : config('app.name');
        $roleLabel = __('roles.' . strtolower($roleEnumVal));
        $institutionId = $user->institute_id;
        
        // Determine event key based on role
        $eventKey = 'user_welcome'; // Default
        if (stripos($roleEnumVal, 'Student') !== false) $eventKey = 'student_created';
        elseif (stripos($roleEnumVal, 'Staff') !== false || stripos($roleEnumVal, 'Teacher') !== false) $eventKey = 'staff_created';
        elseif (stripos($roleEnumVal, 'Guardian') !== false) $eventKey = 'user_welcome'; // Or parent_created if added

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
            // Aliases
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

        // 1. Email
        if ($this->isChannelEnabled($institutionId, $eventKey, 'email') && $user->email) {
            try {
                Mail::to($user->email)->send(new UserCredentialsMail($data));
            } catch (\Exception $e) {
                Log::error("Email Error: " . $e->getMessage());
            }
        }

        // 2. SMS & WhatsApp
        if ($user->phone) {
            $templateKey = strtolower(str_replace(' ', '_', $roleEnumVal)) . '_welcome'; 
            
            // --- SMS ---
            if ($this->isChannelEnabled($institutionId, $eventKey, 'sms')) {
                // Try specific then generic
                $smsRes = $this->sendNotificationEvent($templateKey, $user->phone, $data, $institutionId, 'sms');
                if (!$smsRes['success']) {
                    $this->sendNotificationEvent('user_welcome', $user->phone, $data, $institutionId, 'sms');
                }
            }

            // --- WhatsApp ---
            if ($this->isChannelEnabled($institutionId, $eventKey, 'whatsapp')) {
                $waRes = $this->sendNotificationEvent($templateKey, $user->phone, $data, $institutionId, 'whatsapp');
                if (!$waRes['success']) {
                    $this->sendNotificationEvent('user_welcome', $user->phone, $data, $institutionId, 'whatsapp');
                }
            }
        }
    }

    public function sendInstitutionCreation($institution, $adminUser, $plainPassword)
    {
        $eventKey = 'institution_created';
        
        $data = [
            'Name' => $adminUser->name,
            'Email' => $adminUser->email,
            'Shortcode' => $adminUser->shortcode ?? 'N/A',
            'Password' => $plainPassword,
            'Role' => __('roles.head_officer'), 
            'SchoolName' => $institution->name,
            'Url' => route('login'),
            'LoginLink' => route('login'),
            // Aliases
            'name' => $adminUser->name,
            'email' => $adminUser->email,
            'shortcode' => $adminUser->shortcode ?? 'N/A',
            'password' => $plainPassword,
            'role' => __('roles.head_officer'),
            'school_name' => $institution->name,
            'url' => route('login'),
            'login_link' => route('login'),
        ];

        // Email
        if ($this->isChannelEnabled($institution->id, $eventKey, 'email') && $adminUser->email) {
            try {
                Mail::to($adminUser->email)->send(new UserCredentialsMail($data));
            } catch (\Exception $e) {
                Log::error("Email Error: " . $e->getMessage());
            }
        }

        // SMS & WhatsApp
        if ($adminUser->phone) {
            if ($this->isChannelEnabled($institution->id, $eventKey, 'sms')) {
                $this->sendNotificationEvent($eventKey, $adminUser->phone, $data, $institution->id, 'sms');
            }
            if ($this->isChannelEnabled($institution->id, $eventKey, 'whatsapp')) {
                $this->sendNotificationEvent($eventKey, $adminUser->phone, $data, $institution->id, 'whatsapp');
            }
        }
    }

    /**
     * Unified method to dispatch notifications.
     */
    public function sendNotificationEvent($eventKey, $to, $data = [], $institutionId = null, $channel = 'sms')
    {
        // Fetch Template from Database
        $template = \App\Models\SmsTemplate::forEvent($eventKey, $institutionId)->first();

        if (!$template || !$template->is_active) {
            // Only log if not a test event or expected missing template scenario
            // Log::warning("Template '$eventKey' missing or inactive for Institution ID $institutionId");
            return ['success' => false, 'message' => "Template '$eventKey' missing or inactive."];
        }

        $message = $template->body;
        foreach ($data as $key => $value) {
            $message = str_replace('$' . $key, $value, $message);
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        
        $isUnlimited = in_array($eventKey, $this->unlimitedGlobalEvents);

        return $this->performSend($to, $message, $institutionId, $isUnlimited, $channel);
    }

    /**
     * Core sending logic with credit check and factory instantiation.
     */
    public function performSend($to, $message, $institutionId, $isUnlimited = false, $channel = 'sms') 
    {
        $institution = Institution::find($institutionId);
        
        if (!$institution) {
             return ['success' => false, 'message' => __('configuration.institution_not_found')];
        }

        // Credit Check
        $creditColumn = ($channel === 'whatsapp') ? 'whatsapp_credits' : 'sms_credits';
        
        if (!$isUnlimited && $institution->$creditColumn <= 0) {
            $msg = strtoupper($channel) . " " . __('configuration.insufficient_credits');
            Log::warning($msg . " Inst ID: $institutionId");
            return ['success' => false, 'message' => $msg];
        }
        
        // Resolve Provider using Factory
        $result = ['success' => false, 'message' => __('configuration.unknown_error')];
        
        try {
            if ($channel === 'whatsapp') {
                $provider = app(InfobipService::class);
                $result = $provider->sendWhatsApp($to, $message);
            } else {
                $providerName = \App\Models\InstitutionSetting::get($institutionId, 'sms_provider', 'mobishastra');
                $gateway = GatewayFactory::create($providerName);
                $result = $gateway->send($to, $message);
            }

            // Deduct Credit on Success
            if ($result['success'] && !$isUnlimited) {
                $institution->decrement($creditColumn);
            }

        } catch (\Exception $e) {
            Log::error("Notification Service General Error", ['error' => $e->getMessage()]);
            $result = ['success' => false, 'message' => __('configuration.gateway_connection_error')];
        }
        
        return $result;
    }
}