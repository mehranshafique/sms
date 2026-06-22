<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\Module;
use App\Services\Sms\GatewayFactory;
use App\Services\SystemCommunicationConfigService;
use App\Services\InstitutionSetupAlertService;
use App\Services\MailSettingsService;
use App\Services\Finance\InstitutionCreditService;
use App\Models\InstitutionCreditTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Enums\RoleEnum;

class ConfigurationController extends BaseController
{
    public function __construct()
    {
        $this->setPageTitle(__('configuration.page_title'));
    }

    public function index()
    {
        $institutionId = $this->getInstitutionId(); 
        $user = Auth::user();
        $isSuperAdmin = $user->hasRole(RoleEnum::SUPER_ADMIN->value) && is_null($institutionId);

        $settings = InstitutionSetting::where('institution_id', $institutionId)->pluck('value', 'key')->toArray();

        $globalSettings = [];
        if (!$isSuperAdmin) {
            $globalSettings = InstitutionSetting::whereNull('institution_id')->pluck('value', 'key')->toArray();
        } else {
            $globalSettings = $settings; 
        }

        $defaultSms = '["mobishastra","infobip","twilio","signalwire"]';
        $defaultWa = '["meta","infobip","twilio"]';
        $allowedSms = json_decode($globalSettings['allowed_sms_providers'] ?? $defaultSms, true);
        $allowedWa = json_decode($globalSettings['allowed_whatsapp_providers'] ?? $defaultWa, true);

        $sms = [
            'provider' => $settings['sms_provider'] ?? 'system',
            'sender_id' => $settings['sms_sender_id'] ?? '',
        ];
        
        $notificationPrefs = isset($settings['notification_preferences']) ? json_decode($settings['notification_preferences'], true) : [];
        $schoolYear = [
            'start_date' => $settings['academic_start_date'] ?? date('Y-09-01'),
            'end_date' => $settings['academic_end_date'] ?? date('Y-07-01'),
            'start_time' => $settings['school_start_time'] ?? '08:00',
            'end_time' => $settings['school_end_time'] ?? '15:00',
            'late_margin_time' => $settings['late_margin_time'] ?? '0',
            'double_tap_wait_time' => $settings['double_tap_wait_time'] ?? '15',
            'rooms_count' => $settings['school_rooms_count'] ?? '10',
        ];
        
        $billingSettings = [
            'chatbot_free_interactions' => $settings['chatbot_free_interactions'] ?? '0',
        ];

        $institution = $institutionId ? Institution::find($institutionId) : Auth::user(); 
        $allModules = Module::all();
        $enabledModules = isset($settings['enabled_modules']) ? json_decode($settings['enabled_modules'], true) : [];
        $smtp = $this->getSmtpSettings($settings);

        $creditTransactions = [];
        if ($institutionId && Auth::user()->hasRole('Super Admin')) {
            $creditTransactions = InstitutionCreditTransaction::with('performer')
                ->where('institution_id', $institutionId)
                ->latest()
                ->limit(25)
                ->get();
        }

        // --- ONLY ADDED EVENTS LOGIC HERE ---
        $globalTemplates = \App\Models\SmsTemplate::whereNull('institution_id')->get()->keyBy('event_key');
        if ($institutionId) {
            $institutionTemplates = \App\Models\SmsTemplate::where('institution_id', $institutionId)->get()->keyBy('event_key');
            $events = $globalTemplates->merge($institutionTemplates)->values();
        } else {
            $events = $globalTemplates->values();
        }

        return view('configuration.index', compact(
            'institution', 'institutionId', 'settings', 'globalSettings', 'smtp', 'sms', 
            'notificationPrefs', 'schoolYear', 'allModules', 'enabledModules',
            'allowedSms', 'allowedWa', 'isSuperAdmin', 'events', 'billingSettings', 'creditTransactions'
        ));
    }

    // --- 1. SMTP ---
    public function updateSmtp(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        
        $keys = ['mail_host', 'mail_port', 'mail_username', 'mail_encryption', 'mail_from_address', 'mail_from_name'];
        
        foreach ($keys as $key) {
            $dbKey = str_replace('mail_', 'smtp_', $key);
            $this->saveSetting($institutionId, $dbKey, $request->input($key), 'smtp');
        }

        if ($request->filled('mail_password')) {
            $encrypted = Crypt::encryptString($request->mail_password);
            $this->saveSetting($institutionId, 'smtp_password', $encrypted, 'smtp');
        }

        return response()->json(['message' => __('configuration.settings_saved')]);
    }

    public function dismissSetupAlert(Request $request, InstitutionSetupAlertService $alerts)
    {
        $request->validate(['key' => 'required|string|max:80']);

        $institutionId = $this->getInstitutionId();
        if (!$institutionId) {
            return response()->json(['message' => 'No institution context.'], 422);
        }

        $alerts->dismiss($institutionId, $request->key);

        return response()->json(['success' => true]);
    }

    /**
     * Test SMTP Connection
     */
    public function testSmtp(Request $request, MailSettingsService $mailSettings)
    {
        $request->validate([
            'test_email' => 'required|email'
        ]);

        try {
            $institutionId = $this->getInstitutionId();
            $mailSettings->applyForInstitution($institutionId);

            Mail::raw(__('configuration.test_email_body'), function ($message) use ($request) {
                $message->to($request->test_email)
                        ->subject(__('configuration.test_email_subject'));
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Test email sent successfully to ' . $request->test_email
            ]);
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            if (str_contains($errorMsg, 'getaddrinfo') || str_contains($errorMsg, 'stream_socket_client')) {
                $errorMsg = "Connection failed: Could not connect to the Mail Host. Please check your Host and Port settings.";
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Test failed: ' . $errorMsg
            ], 500);
        }
    }

    // --- 2. SMS / WHATSAPP ---
    public function updateSms(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        $user = Auth::user();
        $isSuperAdmin = $user->hasRole(RoleEnum::SUPER_ADMIN->value) && is_null($institutionId);

        $request->validate([
            'sms_provider' => 'required',
            'whatsapp_provider' => 'required',
        ]);

        // 1. Save Active Selection
        $this->saveSetting($institutionId, 'sms_provider', $request->sms_provider, 'sms');
        $this->saveSetting($institutionId, 'whatsapp_provider', $request->whatsapp_provider, 'sms');

        // 2. Super Admin: Global Allow List
        if ($isSuperAdmin) {
            $allowedSms = $request->input('allowed_sms', []);
            $allowedWa = $request->input('allowed_whatsapp', []);
            $this->saveSetting(null, 'allowed_sms_providers', json_encode($allowedSms), 'system');
            $this->saveSetting(null, 'allowed_whatsapp_providers', json_encode($allowedWa), 'system');
        }

        // 3. Save Public Keys (Updated for Mobishastra & Infobip)
        $publicKeys = [
            // Mobishastra
            'mobishastra_user', 'mobishastra_sender_id',
            // Infobip
            'infobip_subdomain', 'infobip_whatsapp_from', 'infobip_sender_id', // Subdomain stored raw
            // Meta
            'meta_phone_number_id', 'meta_business_account_id',
            // Twilio
            'twilio_sid', 'twilio_from', 'twilio_whatsapp_from',
            // SignalWire
            'sw_project_id', 'sw_space_url', 'sw_from'
        ];

        foreach ($publicKeys as $key) {
            if ($request->has($key)) {
                $this->saveSetting($institutionId, $key, $request->input($key), 'sms');
            }
        }

        // 4. Save Encrypted Secrets (Passwords/API Keys)
        $secretKeys = [
            'mobishastra_password', // Mobishastra uses Password as key
            'infobip_api_key', 
            'meta_access_token',
            'twilio_token', 
            'sw_token'
        ];

        foreach ($secretKeys as $key) {
            if ($request->filled($key)) {
                $encrypted = Crypt::encryptString($request->input($key));
                $this->saveSetting($institutionId, $key, $encrypted, 'sms');
            }
        }

        // ADDED: Super Admin Billing Privileges
        if (Auth::user()->hasRole(\App\Enums\RoleEnum::SUPER_ADMIN->value)) {
            $this->saveSetting($institutionId, 'chatbot_free_interactions', $request->chatbot_free_interactions ?? '0');
        }

        if ($isSuperAdmin) {
            app(SystemCommunicationConfigService::class)->applyGlobalOverrides();
        }

        return response()->json(['message' => __('configuration.sms_settings_updated')]);
    }

    public function testSms(Request $request)
    {
        $request->validate(['phone' => 'required', 'channel' => 'required|in:sms,whatsapp']);
        $institutionId = $this->getInstitutionId();
        
        // Resolve Notification Service
        try {
            $notificationService = app(\App\Services\NotificationService::class);
            $result = $notificationService->performSend(
                $request->phone, 
                "Test message from E-Digitex (" . date('H:i:s') . ")", 
                $institutionId, // NULL handles Super Admin check in service if needed
                true, // Unlimited / No credit deduction for test
                $request->channel
            );

            if($result['success']) {
                return response()->json(['message' => $result['message']]);
            }
            return response()->json(['message' => $result['message']], 422);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    // --- 3. NOTIFICATIONS ---
    public function updateNotifications(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        
        // Loop through all events dynamically from DB instead of hardcoded array
        $globalTemplates = \App\Models\SmsTemplate::whereNull('institution_id')->get();
        
        foreach ($globalTemplates as $template) {
            $event = $template->event_key;
            $val = [
                'sms' => $request->has("{$event}_sms"),
                'whatsapp' => $request->has("{$event}_whatsapp"),
                'email' => $request->has("{$event}_email"),
                'system' => $request->has("{$event}_system"),
            ];
            $this->saveSetting($institutionId, "notify_{$event}", json_encode($val), 'notifications');
        }

        return redirect()->back()->with('success', __('configuration.notifications_updated') ?? 'Notifications updated successfully.');
    }

    // --- 4. SCHOOL YEAR ---
    public function updateSchoolYear(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        $keys = ['academic_start_date', 'academic_end_date', 'school_start_time', 'school_end_time', 'school_rooms_count', 'late_margin_time', 'double_tap_wait_time'];
        
        foreach($keys as $key) {
            $this->saveSetting($institutionId, $key, $request->input($key), 'academics');
        }
        return response()->json(['message' => __('configuration.settings_saved')]);
    }

    // --- 5. MODULES (Super Admin) ---
    public function updateModules(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        $modules = $request->input('modules', []);
        
        $this->saveSetting($institutionId, 'enabled_modules', json_encode($modules), 'system');
        return response()->json(['message' => __('configuration.settings_saved')]);
    }

    public function updateYear(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        
        $this->saveSetting($institutionId, 'academic_start_date', $request->academic_start_date);
        $this->saveSetting($institutionId, 'academic_end_date', $request->academic_end_date);
        $this->saveSetting($institutionId, 'school_start_time', $request->school_start_time);
        $this->saveSetting($institutionId, 'school_end_time', $request->school_end_time);
        if ($request->has('school_rooms_count')) {
            $this->saveSetting($institutionId, 'school_rooms_count', max(1, (int) $request->school_rooms_count), 'academics');
        }
        
        // ADDED: Hardware and Attendance Timings
        if ($request->has('late_margin_time')) {
            $this->saveSetting($institutionId, 'late_margin_time', $request->late_margin_time);
        }
        if ($request->has('double_tap_wait_time')) {
            $this->saveSetting($institutionId, 'double_tap_wait_time', $request->double_tap_wait_time);
        }

        return response()->json(['message' => __('configuration.success_saved') ?? 'Saved successfully']);
    }

    /**
     * Recharge SMS/WhatsApp (Menu: SMS Recharging / Whatsapp Recharging)
     */
    public function recharge(Request $request, InstitutionCreditService $credits)
    {
        if (!Auth::user()->hasRole('Super Admin')) abort(403, __('configuration.only_super_admin_recharge'));
        
        $institutionId = $this->getInstitutionId();
        if (!$institutionId) {
            abort(422, __('configuration.select_school_for_recharge'));
        }

        $request->validate([
            'type' => 'required|in:sms,whatsapp',
            'amount' => 'required|integer|min:1',
            'note' => 'nullable|string|max:500',
        ]);

        $transaction = $credits->recharge(
            (int) $institutionId,
            $request->type,
            (int) $request->amount,
            $request->note
        );

        $institution = Institution::findOrFail($institutionId);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('configuration.recharge_success'),
                'new_balance' => $request->type === 'sms' ? $institution->sms_credits : $institution->whatsapp_credits,
                'transaction_id' => $transaction->id,
            ]);
        }

        return back()->with('success', __('configuration.recharge_success') . ' - ' . __('configuration.balance') . ': ' . ($request->type === 'sms' ? $institution->sms_credits : $institution->whatsapp_credits));
    }

    public function reverseRecharge(Request $request, InstitutionCreditTransaction $transaction, InstitutionCreditService $credits)
    {
        if (!Auth::user()->hasRole('Super Admin')) abort(403, __('configuration.only_super_admin_recharge'));

        $institutionId = $this->getInstitutionId();
        if (!$institutionId || $transaction->institution_id != $institutionId) {
            abort(403);
        }

        $request->validate(['reason' => 'nullable|string|max:500']);

        $credits->reverse($transaction, $request->reason);
        $institution = Institution::findOrFail($institutionId);

        return response()->json([
            'message' => __('configuration.recharge_reversed'),
            'sms_balance' => $institution->fresh()->sms_credits,
            'whatsapp_balance' => $institution->fresh()->whatsapp_credits,
        ]);
    }

    public function correctRecharge(Request $request, InstitutionCreditTransaction $transaction, InstitutionCreditService $credits)
    {
        if (!Auth::user()->hasRole('Super Admin')) abort(403, __('configuration.only_super_admin_recharge'));

        $institutionId = $this->getInstitutionId();
        if (!$institutionId || $transaction->institution_id != $institutionId) {
            abort(403);
        }

        $request->validate([
            'correct_amount' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:500',
        ]);

        $credits->correct($transaction, (int) $request->correct_amount, $request->reason);
        $institution = Institution::findOrFail($institutionId);

        return response()->json([
            'message' => __('configuration.recharge_corrected_success'),
            'sms_balance' => $institution->fresh()->sms_credits,
            'whatsapp_balance' => $institution->fresh()->whatsapp_credits,
        ]);
    }

   /**
     * Helper to save settings using updateOrCreate.
     */
    private function saveSetting($institutionId, $key, $value, $group = 'general')
    {
        // institutionId can be NULL for Global Settings
        InstitutionSetting::updateOrCreate(
            ['institution_id' => $institutionId, 'key' => $key],
            ['value' => $value, 'group' => $group]
        );
    }

    private function getSmtpSettings($settings) {
        $institutionId = $this->getInstitutionId();
        $resolved = app(MailSettingsService::class)->resolve($institutionId);

        return [
            'host' => $resolved['host'] ?? '',
            'port' => $resolved['port'] ?? '',
            'username' => $resolved['username'] ?? '',
            'password' => isset($settings['smtp_password']) ? '' : '',
            'encryption' => $resolved['encryption'] ?? 'tls',
            'from_address' => $resolved['from_address'] ?? '',
            'from_name' => $resolved['from_name'] ?? '',
            'driver' => 'smtp',
        ];
    }
}

// Global Helper function to decrypt settings (if not exists elsewhere)
if (!function_exists('try_decrypt')) {
    function try_decrypt($value) {
        try {
            return \Illuminate\Support\Facades\Crypt::decryptString($value);
        } catch (\Exception $e) {
            return '';
        }
    }
}