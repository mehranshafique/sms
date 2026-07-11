<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Models\Budget;
use App\Models\FundRequest;
use App\Models\DisciplinaryRecord;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\Invoice; 
use App\Mail\PaymentReceived;
use App\Mail\InvoiceCreatedMail;
use App\Mail\UserCredentialsMail; 
use App\Services\Sms\GatewayFactory;
use App\Services\Sms\InfobipService; 
use App\Services\MailSettingsService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class NotificationService
{
    public function __construct(
        protected NotificationPreferenceService $preferences
    ) {}

    protected function isChannelEnabled($institutionId, $eventKey, $channel): bool
    {
        return $this->preferences->isChannelEnabled($institutionId, $eventKey, $channel);
    }

    public function channelEnabled($institutionId, $eventKey, $channel): bool
    {
        return $this->isChannelEnabled($institutionId, $eventKey, $channel);
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
            $data = array_merge(
                student_notification_context($student, $invoice),
                [
                    'Amount' => \App\Enums\CurrencySymbol::default() . ' ' . number_format($invoice->total_amount, 2),
                    'InvoiceNumber' => $invoice->invoice_number,
                    'DueDate' => $invoice->due_date->format('d/m/Y'),
                ]
            );

            if ($this->isChannelEnabled($institution->id, $eventKey, 'sms')) {
                $this->sendNotificationEvent($eventKey, $phone, $data, $institution->id, 'sms');
            }
            
            if ($this->isChannelEnabled($institution->id, $eventKey, 'whatsapp')) {
                $this->sendNotificationEvent($eventKey, $phone, $data, $institution->id, 'whatsapp');
            }
        }
    }

    public function sendPaymentNotification(\App\Models\Payment $payment)
    {
        $invoice = $payment->invoice;
        $student = $invoice->student;
        $institutionId = $payment->institution_id;
        $eventKey = 'payment_received';

        $sendSms = $this->isChannelEnabled($institutionId, $eventKey, 'sms');
        $sendWa = $this->isChannelEnabled($institutionId, $eventKey, 'whatsapp');

        if (!$sendSms && !$sendWa) {
            return;
        }

        $template = \App\Models\SmsTemplate::forEvent($eventKey, $institutionId)->first();
        if (!$template || !$template->is_active) {
            return;
        }

        $currency = \App\Enums\CurrencySymbol::default();
        $remaining = max(0, (float) $invoice->total_amount - (float) $invoice->paid_amount);

        $data = array_merge(
            student_notification_context($student, $invoice, $invoice->academic_session_id),
            [
                'Amount' => $currency . ' ' . number_format((float) $payment->amount, 2),
                'Balance' => $currency . ' ' . number_format($remaining, 2),
                'RemainingBalance' => $currency . ' ' . number_format($remaining, 2),
                'Date' => \Carbon\Carbon::parse($payment->payment_date)->format('d M Y'),
                'TransactionID' => $payment->transaction_id ?? (string) $payment->id,
                'PaymentReason' => invoice_installment_label($invoice),
            ]
        );

        $message = apply_sms_template_tags($template->body, $data);
        $phone = $this->resolveStudentContactPhone($student, $student->parent);

        if (empty($phone)) {
            return;
        }

        if ($sendSms) {
            $this->dispatchMessage($phone, $message, $institutionId, 'sms');
        }

        if ($sendWa) {
            $this->dispatchMessage($phone, $message, $institutionId, 'whatsapp');
        }
    }

    public function sendPaymentProofSubmittedNotification(\App\Models\PaymentProofSubmission $proof): void
    {
        $proof->loadMissing(['invoice.student.parent', 'institution']);
        $this->sendProofExternalNotification($proof, 'payment_proof_submitted', [
            '$StudentName' => $proof->invoice?->student?->full_name ?? '',
            '$Amount' => number_format((float) $proof->amount, 2),
            '$InvoiceNumber' => $proof->invoice?->invoice_number ?? '',
            '$SchoolName' => $proof->institution?->name ?? 'School',
            '$PayerName' => $proof->payer_name ?? '',
        ], $proof->payer_phone);
    }

    public function sendPaymentProofRejectedNotification(\App\Models\PaymentProofSubmission $proof): void
    {
        $proof->loadMissing(['invoice.student.parent', 'institution']);
        $this->sendProofExternalNotification($proof, 'payment_proof_rejected', [
            '$StudentName' => $proof->invoice?->student?->full_name ?? '',
            '$Amount' => number_format((float) $proof->amount, 2),
            '$InvoiceNumber' => $proof->invoice?->invoice_number ?? '',
            '$SchoolName' => $proof->institution?->name ?? 'School',
            '$Reason' => $proof->rejection_reason ?? '',
        ]);
    }

    private function sendProofExternalNotification(
        \App\Models\PaymentProofSubmission $proof,
        string $eventKey,
        array $tags,
        ?string $fallbackPhone = null
    ): void {
        $institutionId = $proof->institution_id;
        $sendSms = $this->isChannelEnabled($institutionId, $eventKey, 'sms');
        $sendWa = $this->isChannelEnabled($institutionId, $eventKey, 'whatsapp');

        if (!$sendSms && !$sendWa) {
            return;
        }

        $template = \App\Models\SmsTemplate::forEvent($eventKey, $institutionId)->first();
        if (!$template || !$template->is_active) {
            return;
        }

        $message = str_replace(array_keys($tags), array_values($tags), $template->body);
        $student = $proof->invoice?->student;
        $phone = $fallbackPhone ?: $this->resolveStudentContactPhone($student, $student?->parent);

        if (empty($phone)) {
            return;
        }

        if ($sendSms) {
            $this->dispatchMessage($phone, $message, $institutionId, 'sms');
        }
        if ($sendWa) {
            $this->dispatchMessage($phone, $message, $institutionId, 'whatsapp');
        }
    }

    private function resolveStudentContactPhone($student, $parent = null): ?string
    {
        if ($parent) {
            $phoneField = ($parent->primary_guardian ?? 'father') . '_phone';
            $phone = $parent->$phoneField ?? $parent->father_phone ?? $parent->mother_phone ?? $parent->guardian_phone;
            if (!empty($phone)) {
                return $phone;
            }
        }

        return $student?->mobile_number ?? $student?->phone ?? null;
    }

    private function dispatchMessage($phone, $message, $institutionId, $channel)
    {
        $providerKey = $channel . '_provider';
        $providerName = InstitutionSetting::get($institutionId, $providerKey, 'system');

        if ($providerName === 'system') {
            $providerName = InstitutionSetting::resolveSystemProvider($channel);
            $institutionId = null;
        }

        try {
            $gateway = \App\Services\Sms\GatewayFactory::create($providerName, $institutionId);
            if ($channel === 'whatsapp') {
                $gateway->sendWhatsApp($phone, $message);
            } else {
                $gateway->sendSms($phone, $message);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error(strtoupper($channel) . " Notification Error: " . $e->getMessage());
        }
    }

    public function sendRequestUpdatedNotification(\App\Models\StudentRequest $request)
    {
        $student = $request->student;
        if (!$student) return;

        $parent = $student->parent;
        $institutionId = $request->institution_id;

        // Retrieve Parent or Student Phone
        $phoneField = ($parent->primary_guardian ?? 'father') . '_phone';
        $phone = $parent?->$phoneField ?? $parent?->father_phone ?? $parent?->mother_phone ?? $parent?->guardian_phone ?? $student->mobile_number;
        $email = $parent?->email ?? $student->email;

        $eventKey = 'request_updated';

        $sendSms = $this->isChannelEnabled($institutionId, $eventKey, 'sms');
        $sendWa = $this->isChannelEnabled($institutionId, $eventKey, 'whatsapp');
        $sendEmail = $this->isChannelEnabled($institutionId, $eventKey, 'email');

        if (!$sendSms && !$sendWa && !$sendEmail) {
            return;
        }

        // Safely Translate
        $statusText = __('requests.status_' . $request->status);
        if ($statusText === 'requests.status_' . $request->status) {
            $statusText = ucfirst(str_replace('_', ' ', $request->status));
        }

        $typeText = $request->typeLabel();

        // Prepare Dynamic Fields for Template
        $schoolName = $student->institution->name ?? config('app.name');
        
        $approvedDaysText = '';
        if ($request->status === 'partially_approved' && $request->start_date && $request->end_date) {
            $days = \Carbon\Carbon::parse($request->start_date)->diffInDays(\Carbon\Carbon::parse($request->end_date));
            $approvedDaysText = "Approved for: {$days} days";
        }

        $adminNoteText = $request->admin_note ? "Admin Note: {$request->admin_note}" : "";

        $data = [
            'StudentName' => $student->first_name,
            'TicketNumber' => $request->ticket_number,
            'Type' => $typeText,                     // Fallback for older templates
            'RequestType' => $typeText,              // Matches $RequestType
            'Status' => $statusText,
            'Note' => $request->admin_note ?? 'N/A', // Fallback for older templates
            'AdminNote' => $adminNoteText,           // Matches $AdminNote
            'ApprovedDays' => $approvedDaysText,     // Matches $ApprovedDays
            'SchoolName' => $schoolName,             // Matches $SchoolName
        ];

        if ($sendSms && $phone) {
            $this->sendNotificationEvent($eventKey, $phone, $data, $institutionId, 'sms');
        }

        if ($sendWa && $phone) {
            $this->sendNotificationEvent($eventKey, $phone, $data, $institutionId, 'whatsapp');
        }

        if ($sendEmail && $email) {
            $this->sendEmailTemplate($eventKey, $email, $data, $institutionId);
        }
    }

    public function sendUserCredentials(
        User $user,
        $plainPassword,
        $roleEnumVal,
        ?Student $student = null,
        ?\App\Models\StudentParent $parent = null
    ) {
        $schoolName = $user->institute ? $user->institute->name : config('app.name');
        $roleLabel = $roleEnumVal;
        $institutionId = $user->institute_id;

        $eventKey = 'user_welcome';
        $templateKey = 'user_welcome';

        if (stripos($roleEnumVal, 'Student') !== false) {
            $eventKey = 'student_welcome';
            $templateKey = 'student_welcome';
        } elseif (stripos($roleEnumVal, 'Teacher') !== false) {
            $eventKey = 'teacher_welcome';
            $templateKey = 'teacher_welcome';
        } elseif (stripos($roleEnumVal, 'Staff') !== false) {
            $eventKey = 'staff_welcome';
            $templateKey = 'staff_welcome';
        } elseif (stripos($roleEnumVal, 'Head Officer') !== false || stripos($roleEnumVal, 'HeadOfficer') !== false) {
            $eventKey = 'head_officer_welcome';
            $templateKey = 'head_officer_welcome';
        } elseif (stripos($roleEnumVal, 'Guardian') !== false || stripos($roleEnumVal, 'Parent') !== false) {
            $eventKey = 'guardian_welcome';
            $templateKey = 'guardian_welcome';
        }

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
            'school_name' => $schoolName,
        ];

        if ($this->isChannelEnabled($institutionId, $eventKey, 'system')) {
            try {
                app(InAppNotificationService::class)->notifyUser(
                    $user,
                    $eventKey,
                    'welcome',
                    __('configuration.welcome_notification_title', ['school' => $schoolName]),
                    __('configuration.welcome_notification_body', ['name' => $user->name, 'role' => $roleLabel]),
                    route('login'),
                    $institutionId,
                    'fa-user-plus'
                );
            } catch (\Throwable $e) {
                Log::warning('Welcome in-app notification failed: ' . $e->getMessage());
            }
        }

        if ($this->isChannelEnabled($institutionId, $eventKey, 'email') && $user->email) {
            try {
                if (app(MailSettingsService::class)->applyForInstitution($institutionId) && class_exists(UserCredentialsMail::class)) {
                    Mail::to($user->email)->send(new UserCredentialsMail($data));
                } else {
                    Log::warning('Welcome email skipped: SMTP not configured', ['event' => $eventKey, 'user_id' => $user->id]);
                }
            } catch (\Exception $e) {
                Log::error('Welcome email error: ' . $e->getMessage(), ['event' => $eventKey, 'user_id' => $user->id]);
            }
        }

        $phones = $this->resolveWelcomePhones($user, $student, $parent, $roleEnumVal);
        foreach ($phones as $phone) {
            if ($this->isChannelEnabled($institutionId, $eventKey, 'sms')) {
                $res = $this->sendNotificationEvent($templateKey, $phone, $data, $institutionId, 'sms');
                if (! $res['success']) {
                    $res = $this->sendNotificationEvent('user_welcome', $phone, $data, $institutionId, 'sms');
                }
                if (! $res['success']) {
                    Log::warning('Welcome SMS failed', ['event' => $eventKey, 'phone' => $phone, 'message' => $res['message'] ?? '']);
                }
            }

            if ($this->isChannelEnabled($institutionId, $eventKey, 'whatsapp')) {
                $res = $this->sendNotificationEvent($templateKey, $phone, $data, $institutionId, 'whatsapp');
                if (! $res['success']) {
                    $res = $this->sendNotificationEvent('user_welcome', $phone, $data, $institutionId, 'whatsapp');
                }
                if (! $res['success']) {
                    Log::warning('Welcome WhatsApp failed', ['event' => $eventKey, 'phone' => $phone, 'message' => $res['message'] ?? '']);
                }
            }
        }
    }

    /**
     * @return list<string>
     */
    protected function resolveWelcomePhones(User $user, ?Student $student, ?\App\Models\StudentParent $parent, string $roleEnumVal): array
    {
        $phones = [];

        if (! empty($user->phone)) {
            $phones[] = $user->phone;
        }

        if (stripos($roleEnumVal, 'Student') !== false) {
            if ($student?->mobile_number) {
                $phones[] = $student->mobile_number;
            }

            if ($parent) {
                $phones[] = $this->resolveStudentContactPhone($student, $parent);
            }
        }

        return array_values(array_unique(array_filter($phones)));
    }

    public function sendHeadOfficerCredentials(User $user, $plainPassword)
    {
        return $this->sendUserCredentials($user, $plainPassword, \App\Enums\RoleEnum::HEAD_OFFICER->value);
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
            $template = \App\Models\SmsTemplate::forEvent($eventKey, $institutionId)->first();
        }

        if (!$template || !$template->is_active) {
            return ['success' => false, 'message' => "Template '$eventKey' missing or inactive."];
        }

        $message = $template->body;
        foreach ($data as $key => $value) {
            $message = str_replace('$' . $key, $value, $message);
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        
        $isUnlimited = $this->preferences->isCreditExemptEvent($eventKey);

        return $this->performSend($to, $message, $institutionId, $isUnlimited, $channel);
    }

    public function sendEmailTemplate(string $eventKey, string $to, array $data = [], ?int $institutionId = null): array
    {
        if (!$this->isChannelEnabled($institutionId, $eventKey, 'email')) {
            return ['success' => false, 'message' => 'Email channel disabled'];
        }

        $template = \App\Models\EmailTemplate::forEvent($eventKey, $institutionId)->first();
        if (!$template || !$template->is_active) {
            return ['success' => false, 'message' => "Email template '$eventKey' missing or inactive."];
        }

        $subject = apply_sms_template_tags($template->subject, $data);
        $body = apply_sms_template_tags($template->body, $data);

        try {
            Mail::to($to)->send(new \App\Mail\GenericNotificationMail($subject, $body));
            return ['success' => true];
        } catch (\Exception $e) {
            Log::error('Email template send failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function sendTelegramMessage(string $chatId, string $message): array
    {
        $token = config('services.chatbot.telegram_bot_token');
        if (!$token || !$chatId) {
            return ['success' => false, 'message' => 'Telegram not configured'];
        }

        try {
            $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);

            return ['success' => $response->successful()];
        } catch (\Exception $e) {
            Log::error('Telegram send failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function performSend($to, $message, $institutionId, $isUnlimited = false, $channel = 'sms')
    {
        $institution = $institutionId ? Institution::find($institutionId) : null;
        $context = InstitutionSetting::resolveMessagingContext($institutionId, $channel);
        $finalProviderName = $context['resolved'];
        $credentialsContextId = $context['credentials_institution_id'];
        $shouldDeductCredits = $context['deduct_credits'];

        $creditCol = ($channel === 'whatsapp') ? 'whatsapp_credits' : 'sms_credits';
        
        if ($shouldDeductCredits && !$isUnlimited && $institution) {
            if ($institution->$creditCol <= 0) {
                return ['success' => false, 'message' => __('configuration.insufficient_credits')];
            }
        }

        try {
            $credentialFallbackId = ($credentialsContextId === null && $institutionId) ? $institutionId : null;
            $gateway = GatewayFactory::create($finalProviderName, $credentialsContextId, $credentialFallbackId);

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
            Log::error("Notification Error: " . $e->getMessage());
            return ['success' => false, 'message' => __('configuration.gateway_connection_error')];
        }
    }
    
    public function sendOtpNotification(Student $student, $otp)
    {
        $phone = $student->parent->father_phone 
              ?? $student->parent->mother_phone 
              ?? $student->parent->guardian_phone 
              ?? $student->mobile_number; 

        if (!$phone) {
            Log::error("OTP Failed: No phone for Student ID {$student->id}");
            return;
        }

        $message = __('chatbot.otp_sms_message', ['otp' => $otp]);
        $this->performSend($phone, $message, $student->institution_id, true, 'sms');
    }

    public function performSendFile($to, $fileUrl, $caption, $filename, $institutionId)
    {
        $providerName = InstitutionSetting::get($institutionId, 'whatsapp_provider', 'system');
        if ($providerName === 'system') {
            $providerName = InstitutionSetting::resolveSystemProvider('whatsapp');
            $institutionId = null; 
        }
        
        try {
            $gateway = GatewayFactory::create($providerName, $institutionId);
            
            if (method_exists($gateway, 'sendWhatsAppFile')) {
                return $gateway->sendWhatsAppFile($to, $fileUrl, $caption, $filename);
            }
            return $gateway->sendWhatsApp($to, $caption . " " . $fileUrl);

        } catch (\Exception $e) {
            Log::error("File Send Error: " . $e->getMessage());
            return ['success' => false];
        }
    }

    public function performSendImage($to, $imageUrl, $caption, $institutionId)
    {
        $providerName = InstitutionSetting::get($institutionId, 'whatsapp_provider', 'system');
        if ($providerName === 'system') {
            $providerName = InstitutionSetting::resolveSystemProvider('whatsapp');
            $institutionId = null;
        }

        try {
            $gateway = GatewayFactory::create($providerName, $institutionId);
            
            if (method_exists($gateway, 'sendWhatsAppImage')) {
                return $gateway->sendWhatsAppImage($to, $imageUrl, $caption);
            }
            return $gateway->sendWhatsApp($to, $caption . " " . $imageUrl);

        } catch (\Exception $e) {
            Log::error("Image Send Error: " . $e->getMessage());
            return ['success' => false];
        }
    }

    public function sendFundRequestConfirmation($fundRequest, $phone, $name, $institutionId)
    {
        $providerName = InstitutionSetting::get($institutionId, 'sms_provider', 'system');
        if ($providerName === 'system') {
            $providerName = InstitutionSetting::resolveSystemProvider('sms');
            $institutionId = null;
        }

        try {
            $gateway = GatewayFactory::create($providerName, $institutionId);
            $currency = \App\Enums\CurrencySymbol::default();
            $amount = $currency . ' ' . number_format($fundRequest->amount, 2);
            
            $message = "Hello {$name}, your fund request (Ticket: {$fundRequest->ticket_number}) for {$amount} has been successfully submitted and is pending approval.";
            
            $gateway->sendSms($phone, $message);
        } catch (\Exception $e) {
            Log::error("Fund Request Notification Error: " . $e->getMessage());
        }
    }

    // --- NEW: EVENT LISTENER METHOD (APPROVED / REJECTED) ---
    public function sendFundRequestProcessedNotification($fundRequest, $institutionId)
    {
        // Must load relationships to access data
        $fundRequest->loadMissing(['requester', 'budget.category']);
        $requester = $fundRequest->requester;
        $budget = $fundRequest->budget;

        if (!$requester || !$budget) return;

        $phone = $requester->staff->phone ?? $requester->phone ?? null;
        $email = $requester->email ?? null;

        $currency = \App\Enums\CurrencySymbol::default();
        $amount = $currency . ' ' . number_format($fundRequest->amount, 2);
        
        // Calculate remaining balance
        $remaining = $currency . ' ' . number_format(max(0, $budget->allocated_amount - $budget->spent_amount), 2);
        $categoryName = $budget->category->name ?? 'General';

        if ($fundRequest->status === 'approved') {
            $message = "Hello {$requester->name}, your fund request ({$fundRequest->ticket_number}) for {$amount} from the '{$categoryName}' budget has been APPROVED and deducted. Remaining budget balance is now {$remaining}.";
        } elseif ($fundRequest->status === 'rejected') {
            $message = "Hello {$requester->name}, your fund request ({$fundRequest->ticket_number}) for {$amount} was REJECTED. Reason: {$fundRequest->rejection_reason}";
        } else {
            return; // Ignore pending
        }

        // 1. Dispatch via SMS
        $providerName = InstitutionSetting::get($institutionId, 'sms_provider', 'system');
        if ($providerName === 'system') {
            $providerName = InstitutionSetting::get(null, 'sms_provider', 'system');
        }

        try {
            $gateway = GatewayFactory::create($providerName, $institutionId);
            if ($phone) {
                $gateway->sendSms($phone, $message);
            }
        } catch (\Exception $e) {
            Log::error("Fund Request Processed SMS Error: " . $e->getMessage());
        }

        // 2. Dispatch via Email (Raw Mail Dispatch)
        // If email notifications are enabled globally or locally for 'system_alert'
        if ($email && $this->isChannelEnabled($institutionId, 'system_alert', 'email')) {
             try {
                 Mail::raw($message, function($msg) use ($email, $fundRequest) {
                     $msg->to($email)
                         ->subject("Budget Update: Fund Request {$fundRequest->ticket_number}");
                 });
             } catch (\Exception $e) {
                 Log::error("Fund Request Processed Email Error: " . $e->getMessage());
             }
        }
    }

    /**
     * Notify finance team and budget responsible person when an expense is recorded against a budget line.
     */
    public function sendBudgetConsumedNotifications(FundRequest $fundRequest): void
    {
        if ($fundRequest->status !== 'approved') {
            return;
        }

        $fundRequest->loadMissing(['budget.category', 'budget.responsibleUser', 'institution', 'requester']);
        $budget = $fundRequest->budget;
        if (!$budget) {
            return;
        }

        $institutionId = $fundRequest->institution_id;
        $institution = $fundRequest->institution;
        $currency = \App\Enums\CurrencySymbol::default();
        $amount = $currency . ' ' . number_format((float) $fundRequest->amount, 2);
        $remaining = $currency . ' ' . number_format($budget->remainingAmount(), 2);
        $budgetLine = ($budget->category->name ?? __('budget.category')) . ($budget->period_name ? ' (' . $budget->period_name . ')' : '');

        $data = [
            'BudgetLine' => $budgetLine,
            'ExpenseTitle' => $fundRequest->title,
            'Amount' => $amount,
            'Remaining' => $remaining,
            'SchoolName' => $institution->name ?? config('app.name'),
            'Requester' => $fundRequest->requester->name ?? '',
        ];

        $eventKey = 'budget_consumed';
        $recipients = $this->budgetConsumptionRecipients($institutionId, $budget);

        foreach ($recipients as $user) {
            $phone = $user->staff->phone ?? $user->phone ?? null;
            if ($phone && $this->isChannelEnabled($institutionId, $eventKey, 'sms')) {
                $this->sendNotificationEvent($eventKey, $phone, $data, $institutionId, 'sms');
            }
            if ($phone && $this->isChannelEnabled($institutionId, $eventKey, 'whatsapp')) {
                $this->sendNotificationEvent($eventKey, $phone, $data, $institutionId, 'whatsapp');
            }
            $email = $user->email ?? null;
            if ($email && $this->isChannelEnabled($institutionId, $eventKey, 'email')) {
                $body = __('budget.consumed_email_body', [
                    'line' => $budgetLine,
                    'title' => $fundRequest->title,
                    'amount' => $amount,
                    'remaining' => $remaining,
                ]);
                try {
                    Mail::raw($body, function ($msg) use ($email, $budgetLine) {
                        $msg->to($email)->subject(__('budget.consumed_email_subject', ['line' => $budgetLine]));
                    });
                } catch (\Exception $e) {
                    Log::error('Budget consumed email error: ' . $e->getMessage());
                }
            }
        }

        app(InAppNotificationService::class)->notifyBudgetConsumed($fundRequest, $budgetLine, $amount, $remaining);
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\User>
     */
    private function budgetConsumptionRecipients(int $institutionId, Budget $budget): \Illuminate\Support\Collection
    {
        $financeRoles = [
            \App\Enums\RoleEnum::SUPER_ADMIN->value,
            \App\Enums\RoleEnum::SCHOOL_ADMIN->value,
            \App\Enums\RoleEnum::HEAD_OFFICER->value,
            'Accountant',
            'accountant',
        ];

        $guard = config('auth.defaults.guard', 'web');
        $existingRoles = array_values(array_filter($financeRoles, fn ($role) =>
            \Spatie\Permission\Models\Role::where('name', $role)->where('guard_name', $guard)->exists()
        ));

        $users = collect();
        if ($existingRoles !== []) {
            $users = User::role($existingRoles)
                ->where(function ($query) use ($institutionId) {
                    $query->where('institute_id', $institutionId)
                        ->orWhereHas('institutes', fn ($q) => $q->where('institutions.id', $institutionId));
                })
                ->get();
        }

        if ($budget->responsible_user_id && $budget->responsibleUser) {
            $users = $users->push($budget->responsibleUser);
        }

        return $users->unique('id')->values();
    }

    /**
     * Notify parents when a disciplinary incident is recorded.
     */
    public function sendDisciplinaryIncidentNotification(DisciplinaryRecord $record): void
    {
        if (!$record->notify_parents) {
            return;
        }

        $record->loadMissing(['student.parent', 'student.user', 'institution']);
        $student = $record->student;
        if (!$student) {
            return;
        }

        $parent = $student->parent;
        $phone = $parent?->father_phone ?? $parent?->mother_phone ?? $parent?->guardian_phone ?? $student->mobile_number;
        if (!$phone) {
            return;
        }

        $institutionId = $record->institution_id;
        $data = [
            'StudentName' => $student->full_name,
            'IncidentType' => $record->typeLabel(),
            'Title' => $record->title,
            'Severity' => $record->severityLabel(),
            'Date' => $record->incident_date->format('d/m/Y'),
            'Reference' => $record->reference_no,
            'SchoolName' => $record->institution->name ?? config('app.name'),
            'ActionTaken' => $record->action_taken ?: '—',
        ];

        $eventKey = 'disciplinary_incident';
        if ($this->isChannelEnabled($institutionId, $eventKey, 'whatsapp')) {
            $this->sendNotificationEvent($eventKey, $phone, $data, $institutionId, 'whatsapp');
        }
        if ($this->isChannelEnabled($institutionId, $eventKey, 'sms')) {
            $this->sendNotificationEvent($eventKey, $phone, $data, $institutionId, 'sms');
        }

        $record->update(['parents_notified_at' => now()]);
    }

    // --- NEW: FCM HTTP v1 OAUTH TOKEN GENERATOR ---
    private function getFcmAccessToken($credentialsFilePath)
    {
        $jsonKey = json_decode(file_get_contents($credentialsFilePath), true);
        $clientEmail = $jsonKey['client_email'];
        $privateKey = $jsonKey['private_key'];

        $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $now = time();
        $payload = json_encode([
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now
        ]);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = '';
        openssl_sign($base64UrlHeader . "." . $base64UrlPayload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]);

        if ($response->successful()) {
            return $response->json('access_token');
        }

        Log::error("Failed to generate FCM Access Token: " . $response->body());
        return null;
    }

    // --- NEW: PUSH NOTIFICATION METHOD (HTTP v1) ---
    public function sendPushNotification($userId, $title, $body, $data = [])
    {
        try {
            $user = User::find($userId);
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }

            $deviceToken = $user->fcm_token ?? null;

            if (!$deviceToken) {
                Log::info("Push Notification Skipped: No device token for User ID {$userId}");
                return ['success' => false, 'message' => 'No device token associated with this user.'];
            }

            $credentialsFilePath = storage_path('app/firebase-credentials.json');

            if (!file_exists($credentialsFilePath)) {
                Log::warning("Push Notification Skipped: Missing firebase-credentials.json file in storage/app/.");
                return ['success' => false, 'message' => 'Missing Firebase V1 Credentials file.'];
            }

            $jsonKey = json_decode(file_get_contents($credentialsFilePath), true);
            $projectId = $jsonKey['project_id'];

            $accessToken = $this->getFcmAccessToken($credentialsFilePath);

            if (!$accessToken) {
                return ['success' => false, 'message' => 'Failed to authenticate with Firebase'];
            }

            // V1 requires all data payload values to be strict strings
            $stringifiedData = [];
            foreach ($data as $key => $value) {
                $stringifiedData[$key] = strval($value);
            }

            // HTTP V1 Payload Structure
            $payload = [
                'message' => [
                    'token' => $deviceToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                ]
            ];

            if (!empty($stringifiedData)) {
                $payload['message']['data'] = $stringifiedData;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $payload);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Push notification sent successfully via V1 API'];
            }

            Log::error("FCM API V1 Error: " . $response->body());
            return ['success' => false, 'message' => 'Failed to send push notification via FCM V1 gateway'];

        } catch (\Exception $e) {
            Log::error("Push Notification Exception: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}