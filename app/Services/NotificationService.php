<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\Invoice; 
use App\Mail\PaymentReceived;
use App\Mail\InvoiceCreatedMail;
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
        'system_alert',
        'user_welcome',
        'otp_verification' 
    ];

    protected function isChannelEnabled($institutionId, $eventKey, $channel)
    {
        if (in_array($eventKey, $this->unlimitedGlobalEvents)) {
            return true;
        }

        $settings = InstitutionSetting::where('institution_id', $institutionId)
            ->where('key', 'notification_preferences')
            ->value('value');
        
        if (!$settings) {
            return true; 
        }

        $preferences = is_string($settings) ? json_decode($settings, true) : $settings;

        if (isset($preferences[$eventKey]) && isset($preferences[$eventKey][$channel])) {
            return (bool) $preferences[$eventKey][$channel];
        }

        return true;
    }

    public function sendInvoiceNotification(Invoice $invoice)
    {
        $student = $invoice->student;
        $institution = $invoice->institution;
        $eventKey = 'invoice_created';

        if ($this->isChannelEnabled($institution->id, $eventKey, 'email') && $student->email) {
            try {
                if (class_exists(InvoiceCreatedMail::class)) {
                    Mail::to($student->email)->queue(new InvoiceCreatedMail($invoice));
                }
            } catch (\Exception $e) {
                Log::error("Invoice Email Error: " . $e->getMessage());
            }
        }

        $phone = $student->mobile_number ?? $student->parent->father_phone ?? $student->parent->guardian_phone ?? null;

        if ($phone) {
            $data = [
                'StudentName' => $student->full_name,
                'Amount' => number_format($invoice->total_amount, 2),
                'InvoiceNumber' => $invoice->invoice_number,
                'DueDate' => $invoice->due_date->format('d/m/Y'),
                'SchoolName' => $institution->name,
                'Currency' => config('app.currency_symbol', '$'),
                'student_name' => $student->full_name,
                'school_name' => $institution->name,
                'invoice_number' => $invoice->invoice_number,
                'due_date' => $invoice->due_date->format('d/m/Y'),
            ];

            if ($this->isChannelEnabled($institution->id, $eventKey, 'sms')) {
                $this->sendNotificationEvent($eventKey, $phone, $data, $institution->id, 'sms');
            }
            
            if ($this->isChannelEnabled($institution->id, $eventKey, 'whatsapp')) {
                $this->sendNotificationEvent($eventKey, $phone, $data, $institution->id, 'whatsapp');
            }
        }
    }

    public function sendPaymentNotification(Payment $payment)
    {
        $invoice = $payment->invoice;
        $student = $invoice->student;
        $institution = $payment->institution ?? $invoice->institution;
        $eventKey = 'payment_received';
        
        if ($this->isChannelEnabled($institution->id, $eventKey, 'email') && $student->email) {
            try {
                if (class_exists(PaymentReceived::class)) {
                    Mail::to($student->email)->queue(new PaymentReceived($payment));
                }
            } catch (\Exception $e) {
                Log::error("Payment Email Error: " . $e->getMessage());
            }
        }

        $phone = $student->mobile_number ?? $student->parent->father_phone ?? $student->parent->guardian_phone ?? null;

        if ($phone) {
            $data = [
                'StudentName' => $student->full_name,
                'Amount' => number_format($payment->amount, 2),
                'SchoolName' => $institution->name,
                'Balance' => number_format($invoice->total_amount - $invoice->paid_amount, 2),
                'Currency' => config('app.currency_symbol', '$'),
                'Date' => $payment->payment_date->format('d/m/Y'),
                'TransactionID' => $payment->transaction_id ?? 'N/A',
                'student_name' => $student->full_name,
                'school_name' => $institution->name,
                'transaction_id' => $payment->transaction_id ?? 'N/A'
            ];

            if ($this->isChannelEnabled($institution->id, $eventKey, 'sms')) {
                $this->sendNotificationEvent($eventKey, $phone, $data, $institution->id, 'sms');
            }
            
            if ($this->isChannelEnabled($institution->id, $eventKey, 'whatsapp')) {
                $this->sendNotificationEvent($eventKey, $phone, $data, $institution->id, 'whatsapp');
            }
        }
    }

    public function sendUserCredentials(User $user, $plainPassword, $roleEnumVal)
    {
        $schoolName = $user->institute ? $user->institute->name : config('app.name');
        $roleLabel = $roleEnumVal; 
        $institutionId = $user->institute_id;
        
        $eventKey = 'user_welcome';
        if (stripos($roleEnumVal, 'Student') !== false) $eventKey = 'student_created';
        elseif (stripos($roleEnumVal, 'Staff') !== false || stripos($roleEnumVal, 'Teacher') !== false) $eventKey = 'staff_created';

        $data = [
            'Name' => $user->name,
            'Email' => $user->email,
            'Shortcode' => $user->shortcode ?? 'N/A',
            'Username' => $user->username ?? $user->shortcode ?? 'N/A',
            'Password' => $plainPassword ?? 'Unchanged',
            'Role' => $roleLabel, 
            'SchoolName' => $schoolName,
            'Url' => route('login'),
            'LoginLink' => route('login'),
            'name' => $user->name,
            'email' => $user->email,
            'password' => $plainPassword ?? 'Unchanged',
            'role' => $roleLabel,
            'school_name' => $schoolName
        ];

        if ($this->isChannelEnabled($institutionId, $eventKey, 'email') && $user->email) {
            try {
                if (class_exists(UserCredentialsMail::class)) {
                    Mail::to($user->email)->send(new UserCredentialsMail($data));
                }
            } catch (\Exception $e) {
                Log::error("Email Error: " . $e->getMessage());
            }
        }

        if ($user->phone) {
            $templateKey = strtolower(str_replace(' ', '_', $roleEnumVal)) . '_welcome'; 
            
            if ($this->isChannelEnabled($institutionId, $eventKey, 'sms')) {
                $res = $this->sendNotificationEvent($templateKey, $user->phone, $data, $institutionId, 'sms');
                if (!$res['success']) {
                    $this->sendNotificationEvent('user_welcome', $user->phone, $data, $institutionId, 'sms');
                }
            }

            if ($this->isChannelEnabled($institutionId, $eventKey, 'whatsapp')) {
                $res = $this->sendNotificationEvent($templateKey, $user->phone, $data, $institutionId, 'whatsapp');
                if (!$res['success']) {
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
            'Role' => 'Head Officer', 
            'SchoolName' => $institution->name,
            'Url' => route('login'),
            'LoginLink' => route('login'),
            'name' => $adminUser->name,
            'email' => $adminUser->email,
            'password' => $plainPassword,
            'school_name' => $institution->name
        ];

        if ($this->isChannelEnabled($institution->id, $eventKey, 'email') && $adminUser->email) {
            try {
                if (class_exists(UserCredentialsMail::class)) {
                    Mail::to($adminUser->email)->send(new UserCredentialsMail($data));
                }
            } catch (\Exception $e) {
                Log::error("Email Error: " . $e->getMessage());
            }
        }

        if ($adminUser->phone) {
            if ($this->isChannelEnabled($institution->id, $eventKey, 'sms')) {
                $this->sendNotificationEvent($eventKey, $adminUser->phone, $data, $institution->id, 'sms');
            }
            if ($this->isChannelEnabled($institution->id, $eventKey, 'whatsapp')) {
                $this->sendNotificationEvent($eventKey, $adminUser->phone, $data, $institution->id, 'whatsapp');
            }
        }
    }

    public function sendNotificationEvent($eventKey, $to, $data = [], $institutionId = null, $channel = 'sms')
    {
        $template = null;
        if (class_exists(\App\Models\SmsTemplate::class)) {
            $template = \App\Models\SmsTemplate::where('event_key', $eventKey)
                ->where(function($q) use ($institutionId) {
                    $q->where('institution_id', $institutionId)
                      ->orWhereNull('institution_id');
                })
                ->orderByDesc('institution_id') 
                ->first();
        }

        if (!$template || !$template->is_active) {
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

    public function performSend($to, $message, $institutionId, $isUnlimited = false, $channel = 'sms') 
    {
        $institution = Institution::find($institutionId);
        
        $providerKey = ($channel === 'whatsapp') ? 'whatsapp_provider' : 'sms_provider';
        
        $selectedProvider = InstitutionSetting::where('institution_id', $institutionId)
            ->where('key', $providerKey)
            ->value('value') ?? 'system';

        $finalProviderName = $selectedProvider;
        $credentialsContextId = $institutionId; 
        $shouldDeductCredits = false;

        if ($selectedProvider === 'system') {
            $globalKey = ($channel === 'whatsapp') ? 'whatsapp_provider' : 'sms_provider';
            $finalProviderName = InstitutionSetting::whereNull('institution_id')
                ->where('key', $globalKey)
                ->value('value') ?? 'mobishastra';
            
            $credentialsContextId = null; 
            $shouldDeductCredits = true;
        } else {
            $finalProviderName = $selectedProvider;
            $credentialsContextId = $institutionId;
            $shouldDeductCredits = false;
        }

        $creditCol = ($channel === 'whatsapp') ? 'whatsapp_credits' : 'sms_credits';
        
        if ($shouldDeductCredits && !$isUnlimited && $institution) {
            if ($institution->$creditCol <= 0) {
                return ['success' => false, 'message' => __('configuration.insufficient_credits')];
            }
        }

        try {
            $gateway = GatewayFactory::create($finalProviderName, $credentialsContextId);

            if ($channel === 'whatsapp') {
                $result = $gateway->sendWhatsApp($to, $message);
            } else {
                $result = $gateway->sendSms($to, $message);
            }

            if ($result['success'] && $shouldDeductCredits && !$isUnlimited && $institution) {
                if($institution->$creditCol > 0) {
                    $institution->decrement($creditCol);
                }
            }

            return $result;

        } catch (\Exception $e) {
            Log::error("Notification Error [Inst: $institutionId]: " . $e->getMessage());
            return ['success' => false, 'message' => __('configuration.gateway_connection_error') . ': ' . $e->getMessage()];
        }
    }
    
    // --- UPDATED OTP METHOD (FORCE SMS) ---
    public function sendOtpNotification(Student $student, $otp)
    {
        // Try Parent Phone first
        $phone = $student->parent->father_phone 
              ?? $student->parent->mother_phone 
              ?? $student->parent->guardian_phone 
              ?? $student->mobile_number; 

        if (!$phone) {
            Log::error("OTP Failed: No phone number found for Student ID {$student->id}");
            return;
        }

        $message = __('chatbot.otp_sms_message', ['otp' => $otp]); // Ensure translation uses :otp
        
        // FORCE SMS CHANNEL regardless of settings to ensure it goes to SIM
        $this->performSend($phone, $message, $student->institution_id, true, 'sms');
    }

    /**
     * Send File (PDF/Image) via WhatsApp
     */
    public function performSendFile($to, $fileUrl, $caption, $filename, $institutionId)
    {
        $institution = Institution::find($institutionId);
        if (!$institution) return ['success' => false];

        $providerName = InstitutionSetting::get($institutionId, 'whatsapp_provider', 'infobip');
        
        try {
            $gateway = GatewayFactory::create($providerName, $institutionId);
            
            if (method_exists($gateway, 'sendWhatsAppFile')) {
                return $gateway->sendWhatsAppFile($to, $fileUrl, $caption, $filename);
            }

            // Fallback if provider doesn't support files
            return $gateway->sendWhatsApp($to, $caption . " " . $fileUrl);

        } catch (\Exception $e) {
            Log::error("File Send Error: " . $e->getMessage());
            return ['success' => false];
        }
    }
}