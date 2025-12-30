<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\Module;
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

        // 1. SMTP
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

        // 2. SMS
        $sms = [
            'sender_id' => $settings['sms_sender_id'] ?? '',
            'provider' => $settings['sms_provider'] ?? 'mobishastra',
        ];
        
        // 3. School Year (UPDATED: Now using full dates)
        $schoolYear = [
            'start_date' => $settings['academic_start_date'] ?? date('Y-09-01'),
            'end_date'   => $settings['academic_end_date'] ?? date('Y-06-30'),
        ];

        // 4. Modules (Dynamic from DB)
        $allModules = Module::orderBy('name')->get();
        $enabledModules = json_decode($settings['enabled_modules'] ?? '[]', true);
        if(!is_array($enabledModules)) $enabledModules = [];

        // 5. Credits
        $credits = [
            'sms' => $institution->sms_credits,
            'whatsapp' => $institution->whatsapp_credits
        ];

        return view('configuration.index', compact('institution', 'smtp', 'sms', 'schoolYear', 'allModules', 'enabledModules', 'credits'));
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
                    $fail('The Mail Host must be a server address (e.g. smtp.gmail.com), not an email address.');
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
            Mail::raw('This is a test email from the Digitex System Configuration check.', function ($message) use ($request) {
                $message->to($request->test_email)
                        ->subject('SMTP Configuration Test');
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Test email sent successfully to ' . $request->test_email
            ]);
        } catch (Exception $e) {
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
     * Update Module Purchased (Menu: Module Purchased)
     */
    public function updateModules(Request $request)
    {
        if (!Auth::user()->hasRole('Super Admin')) abort(403, 'Only Main Admin can configure purchased modules.');

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
        if (!Auth::user()->hasRole('Super Admin')) abort(403, 'Only Main Admin can recharge credits.');
        
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

        return back()->with('success', __('configuration.recharge_success') . ' - New Balance: ' . ($request->type === 'sms' ? $institution->sms_credits : $institution->whatsapp_credits));
    }
    
    /**
     * Update School Year Config (Menu: School Year Config)
     */
    public function updateSchoolYear(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        
        // UPDATED: Validating Dates
        $data = $request->validate([
            'academic_start_date' => 'required|date',
            'academic_end_date'   => 'required|date|after:academic_start_date',
        ]);
        
        foreach ($data as $key => $value) {
            InstitutionSetting::set($institutionId, $key, $value, 'general');
        }

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