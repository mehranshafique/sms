<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\Module;
use App\Services\NotificationService;
use Exception;

class ConfigurationController extends BaseController
{
    public function __construct()
    {
        $this->setPageTitle(__('configuration.page_title'));
    }

    /**
     * Main Configuration Dashboard for Platform Admin
     * Maps to the "Configuration" menu section.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $institutionId = $this->getInstitutionId(); 
        
        if (!$institutionId && $user->hasRole('Super Admin')) {
             $firstInstitution = Institution::first();
             if ($firstInstitution) {
                 $institutionId = $firstInstitution->id;
                 session(['active_institution_id' => $institutionId]);
             } else {
                 return redirect()->route('institutes.create')->with('info', 'Please create your first institution.');
             }
        }

        if (!$institutionId) {
             return redirect()->route('dashboard')->with('error', __('configuration.select_institution_context'));
        }

        $institution = Institution::findOrFail($institutionId);
        $settings = InstitutionSetting::where('institution_id', $institutionId)->pluck('value', 'key');

        // ... (SMTP, SMS, School Year configs remain the same) ...
        $smtp = [
            'driver' => $settings['mail_driver'] ?? 'smtp',
            'host' => $settings['mail_host'] ?? '',
            'port' => $settings['mail_port'] ?? '587',
            'username' => $settings['mail_username'] ?? '',
            'password' => $settings['mail_password'] ?? '',
            'encryption' => $settings['mail_encryption'] ?? 'tls',
            'from_address' => $settings['mail_from_address'] ?? '',
            'from_name' => $settings['mail_from_name'] ?? $institution->name,
        ];

        $sms = [
            'sender_id' => $settings['sms_sender_id'] ?? '',
            'provider' => $settings['sms_provider'] ?? 'mobishastra',
        ];
        
        $schoolYear = [
            'start_date' => $settings['academic_start_date'] ?? date('Y-09-01'),
            'end_date'   => $settings['academic_end_date'] ?? date('Y-06-30'),
            'start_time' => $settings['school_start_time'] ?? '08:00',
            'end_time'   => $settings['school_end_time'] ?? '15:00',
        ];

        // --- NEW: Notification Preferences ---
        $rawPrefs = $settings['notification_preferences'] ?? '[]';
        $notificationPrefs = json_decode($rawPrefs, true);
        
        $defaultEvents = [
            'student_created' => ['email' => true, 'sms' => true, 'whatsapp' => true],
            'staff_created' => ['email' => true, 'sms' => true, 'whatsapp' => true],
            'payment_received' => ['email' => true, 'sms' => true, 'whatsapp' => true],
            'institution_created' => ['email' => true, 'sms' => true, 'whatsapp' => true], // Usually hidden or forced true
        ];

        // Merge saved prefs with defaults to ensure all keys exist
        $notificationPrefs = array_merge($defaultEvents, is_array($notificationPrefs) ? $notificationPrefs : []);

        $allModules = Module::orderBy('name')->get();
        $enabledModules = json_decode($settings['enabled_modules'] ?? '[]', true);
        if(!is_array($enabledModules)) $enabledModules = [];

        $credits = [
            'sms' => $institution->sms_credits,
            'whatsapp' => $institution->whatsapp_credits
        ];

        return view('configuration.index', compact('institution', 'smtp', 'sms', 'schoolYear', 'allModules', 'enabledModules', 'credits', 'notificationPrefs'));
    }

    /**
     * Update SMTP Settings (Menu: SMTP)
     */
    public function updateSmtp(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        $this->authorize('update', Institution::findOrFail($institutionId));
        
        $data = $request->validate([
            'mail_driver' => 'required|string',
            'mail_host' => ['required', 'string', function($attribute, $value, $fail) {
                if (str_contains($value, '@')) {
                    $fail(__('configuration.mail_host_error'));
                }
            }],
            'mail_port' => 'required|numeric',
            'mail_username' => 'nullable|string',
            'mail_password' => 'nullable|string',
            'mail_encryption' => 'nullable|string',
            'mail_from_address' => 'required|email',
            'mail_from_name' => 'required|string',
        ]);

        foreach ($data as $key => $value) {
            InstitutionSetting::set($institutionId, $key, trim($value), 'smtp');
        }

        return back()->with('success', __('configuration.update_success'));
    }

    /**
     * Test SMTP Connection
     */
    public function testSmtp(Request $request)
    {
        $request->validate([
            'test_email' => 'required|email'
        ]);

        try {
            Mail::raw(__('configuration.test_email_body'), function ($message) use ($request) {
                $message->to($request->test_email)
                        ->subject(__('configuration.test_email_subject'));
            });

            return response()->json([
                'status' => 'success',
                'message' => __('configuration.test_email_success', ['email' => $request->test_email])
            ]);
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            if (str_contains($errorMsg, 'getaddrinfo') || str_contains($errorMsg, 'stream_socket_client')) {
                $errorMsg = __('configuration.connection_failed');
            }

            return response()->json([
                'status' => 'error',
                'message' => __('configuration.test_email_failed', ['error' => $errorMsg])
            ], 500);
        }
    }

    /**
     * Update ID Sender SMS (Menu: ID Sender SMS Creat)
     */
    public function updateSms(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        
        $data = $request->validate([
            'sms_sender_id' => 'required|string|max:11',
            'sms_provider' => 'required|string',
        ]);

        foreach ($data as $key => $value) {
            InstitutionSetting::set($institutionId, $key, $value, 'sms');
        }

        return back()->with('success', __('configuration.update_success'));
    }

    /**
     * Test SMS/WhatsApp Connection
     */
    public function testSms(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|min:8',
            'channel' => 'required|in:sms,whatsapp'
        ]);

        $institutionId = $this->getInstitutionId();
        $institution = Institution::findOrFail($institutionId);
        
        $notificationService = app(NotificationService::class);
        
        $message = __('configuration.test_msg_content', ['school' => $institution->name]);
        
        // We use performSend which handles:
        // 1. Credit Check (deducts if successful)
        // 2. Provider Selection (Factory)
        // 3. Error Handling (Returns array with success boolean and message)
        // We pass $isUnlimited = false to respect credit limits as requested.
        
        $result = $notificationService->performSend(
            $request->phone, 
            $message, 
            $institutionId, 
            false, // Enforce credit check
            $request->channel
        );

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'message' => $result['message']
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => $result['message']
            ], 500);
        }
    }

    /**
     * Update Module Purchased (Menu: Module Purchased)
     */
    public function updateModules(Request $request)
    {
        if (!Auth::user()->hasRole('Super Admin')) abort(403, __('configuration.only_super_admin_modules'));

        $institutionId = $this->getInstitutionId();
        
        $modules = $request->input('modules', []);
        InstitutionSetting::set($institutionId, 'enabled_modules', json_encode($modules), 'modules');

        return back()->with('success', __('configuration.update_success'));
    }

    /**
     * Recharge SMS/WhatsApp (Menu: SMS Recharging / Whatsapp Recharging)
     */
    public function recharge(Request $request)
    {
        if (!Auth::user()->hasRole('Super Admin')) abort(403, __('configuration.only_super_admin_recharge'));
        
        $institutionId = $this->getInstitutionId();
        $institution = Institution::findOrFail($institutionId);

        $request->validate([
            'type' => 'required|in:sms,whatsapp',
            'amount' => 'required|integer|min:1',
        ]);

        // Explicitly update and save to ensure database persistence
        if ($request->type === 'sms') {
            $institution->sms_credits = (int)$institution->sms_credits + (int)$request->amount;
        } else {
            $institution->whatsapp_credits = (int)$institution->whatsapp_credits + (int)$request->amount;
        }
        
        $institution->save();

        return back()->with('success', __('configuration.recharge_success') . ' - ' . __('configuration.balance') . ': ' . ($request->type === 'sms' ? $institution->sms_credits : $institution->whatsapp_credits));
    }
    
    /**
     * Update School Year Config (Menu: School Year Config)
     */
    public function updateSchoolYear(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        
        // UPDATED: Validating Dates AND Times
        $data = $request->validate([
            'academic_start_date' => 'required|date',
            'academic_end_date'   => 'required|date|after:academic_start_date',
            'school_start_time'   => 'required|date_format:H:i',
            'school_end_time'     => 'required|date_format:H:i|after:school_start_time',
        ]);
        
        foreach ($data as $key => $value) {
            InstitutionSetting::set($institutionId, $key, $value, 'general');
        }

        return back()->with('success', __('configuration.update_success'));
    }
    /**
     * NEW: Update Notification Preferences
     */
    public function updateNotifications(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        
        // Validate input array structure
        // Expecting: preferences[event_key][channel] = 1/0
        $data = $request->input('preferences', []);
        
        // Save as JSON
        InstitutionSetting::set($institutionId, 'notification_preferences', json_encode($data), 'notifications');

        return back()->with('success', __('configuration.update_success'));
    }
    /**
     * Helper to get current institution model
     */
    private function getInstitution()
    {
        return Institution::findOrFail($this->getInstitutionId());
    }
}