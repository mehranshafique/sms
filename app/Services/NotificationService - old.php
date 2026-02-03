<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Models\Institution;
use App\Mail\PaymentReceived;
use App\Mail\UserCredentialsMail; 
use App\Interfaces\SmsGatewayInterface;
use App\Enums\RoleEnum; 
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected $smsGateway;
    
    // Define event keys that should ignore credit limits
    protected $unlimitedGlobalEvents = [
        'institution_created',
        'low_balance',
        'subscription_expiry',
        'subscription_expired',
        'system_alert'
    ];

    public function __construct(SmsGatewayInterface $smsGateway)
    {
        $this->smsGateway = $smsGateway;
    }

    public function sendPaymentNotification(Payment $payment)
    {
        $invoice = $payment->invoice;
        $student = $invoice->student;
        $institution = $invoice->institution;
        
        if ($student->email) {
            try {
                Mail::to($student->email)->queue(new PaymentReceived($payment));
            } catch (\Exception $e) {
                Log::error("Email Notification Failed: " . $e->getMessage());
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
                'TransactionID' => $payment->transaction_id ?? 'N/A'
            ];

            // Provide snake_case keys for legacy compatibility
            $data['student_name'] = $data['StudentName'];
            $data['school_name'] = $data['SchoolName'];
            $data['transaction_id'] = $data['TransactionID'];

            $sent = $this->sendSmsEvent('payment_received', $phone, $data, $institution->id);

            if (!$sent) {
                $message = __('notifications.payment_received_sms', [
                    'name' => $data['StudentName'],
                    'amount' => $data['Amount'],
                    'school' => $data['SchoolName'],
                    'balance' => $data['Balance']
                ]);
                
                $this->performSmsSend($phone, $message, $institution->id, false); 
            }
        }
    }

    public function sendUserCredentials(User $user, $plainPassword, $roleEnumVal)
    {
        $schoolName = $user->institute ? $user->institute->name : config('app.name');
        
        $roleLabel = __('roles.' . strtolower($roleEnumVal));

        // Data array now includes BOTH PascalCase (for SMS templates) and snake_case (for views)
        $data = [
            'Name' => $user->name,
            'Email' => $user->email,
            'Password' => $plainPassword ?? __('auth.unchanged'),
            'Role' => $roleLabel, 
            'SchoolName' => $schoolName,
            'Url' => route('login'),
            'LoginLink' => route('login'),

            // Add snake_case keys to fix "Undefined array key" in views
            'name' => $user->name,
            'email' => $user->email,
            'password' => $plainPassword ?? __('auth.unchanged'),
            'role' => $roleLabel,
            'school_name' => $schoolName,
            'url' => route('login'),
            'login_link' => route('login'),
        ];

        // 2. Send SMS
        if ($user->phone) {
            $templateKey = strtolower(str_replace(' ', '_', $roleEnumVal)) . '_welcome'; 
            
            $sent = $this->sendSmsEvent($templateKey, $user->phone, $data, $user->institute_id);
            
            if (!$sent) {
                $sent = $this->sendSmsEvent('user_welcome', $user->phone, $data, $user->institute_id);
            }

            if (!$sent) {
                $msg = __('notifications.credential_sms_fallback', [
                    'name' => $data['Name'],
                    'school' => $data['SchoolName'],
                    'email' => $data['Email'],
                    'password' => $data['Password']
                ]);
                $this->performSmsSend($user->phone, $msg, $user->institute_id, false);
            }
        }

        // 3. Send Email
        if ($user->email) {
            try {
                Mail::to($user->email)->send(new UserCredentialsMail($data));
            } catch (\Exception $e) {
                Log::error("Failed to send credential email: " . $e->getMessage());
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
            'Password' => $plainPassword,
            'Role' => $roleLabel, 
            'SchoolName' => $schoolName,
            'Url' => route('login'),
            'LoginLink' => route('login'),

            // Add snake_case keys
            'name' => $adminUser->name,
            'email' => $adminUser->email,
            'password' => $plainPassword,
            'role' => $roleLabel,
            'school_name' => $schoolName,
            'url' => route('login'),
            'login_link' => route('login'),
        ];

        if ($adminUser->phone) {
            $this->sendSmsEvent('institution_created', $adminUser->phone, $data, $institution->id);
        }

         if ($adminUser->email) {
            try {
                Mail::to($adminUser->email)->send(new UserCredentialsMail($data));
            } catch (\Exception $e) {
                Log::error("Failed to send creation email: " . $e->getMessage());
            }
        }
    }

    public function sendHeadOfficerCredentials(User $user, $plainPassword, array $assignedInstituteIds)
    {
        $institutes = \App\Models\Institution::whereIn('id', $assignedInstituteIds)->pluck('name')->toArray();
        $schoolName = implode(', ', $institutes);
        
        $user->setRelation('institute', new Institution(['name' => $schoolName]));

        $this->sendUserCredentials($user, $plainPassword, RoleEnum::HEAD_OFFICER->value);
    }

    public function sendSmsEvent($eventKey, $to, $data = [], $institutionId = null)
    {
        $template = \App\Models\SmsTemplate::forEvent($eventKey, $institutionId)->first();

        if (!$template || !$template->is_active) {
            return false;
        }

        $message = $template->body;
        foreach ($data as $key => $value) {
            $message = str_replace('$' . $key, $value, $message);
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        
        $isUnlimited = in_array($eventKey, $this->unlimitedGlobalEvents);

        return $this->performSmsSend($to, $message, $institutionId, $isUnlimited);
    }

    protected function performSmsSend($to, $message, $institutionId, $isUnlimited = false) 
    {
        $institution = Institution::find($institutionId);
        
        if (!$institution) {
             return false;
        }

        if (!$isUnlimited && $institution->sms_credits <= 0) {
            Log::warning("SMS Failed: Insufficient credits for Institution ID " . ($institutionId ?? 'Unknown'));
            return false;
        }
        
        $sent = $this->smsGateway->send($to, $message);
        
        if ($sent && !$isUnlimited) {
            $institution->decrement('sms_credits');
        }
        
        return $sent;
    }
}