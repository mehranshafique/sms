<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\InstitutionSetting;
use App\Models\Institution;
use Symfony\Component\HttpFoundation\Response;

class LoadInstitutionSettings
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $institutionId = null;

            // 1. Determine Active Institution ID (Logic mirrored from BaseController)
            if ($user->institute_id) {
                // Locked User (Staff/Student)
                $institutionId = $user->institute_id;
            } else {
                // Multi-Institute User (Super Admin / Head Officer)
                $institutionId = session('active_institution_id');
                
                // Fallback if no session set
                if (!$institutionId) {
                    if ($user->institutes && $user->institutes->count() > 0) {
                        $institutionId = $user->institutes->first()->id;
                        session(['active_institution_id' => $institutionId]);
                    } elseif ($user->hasRole('Super Admin')) {
                        // If Super Admin hasn't selected context, pick first active or allow null (Global)
                        $firstInst = Institution::where('is_active', true)->first();
                        $institutionId = $firstInst ? $firstInst->id : null;
                        if($institutionId) session(['active_institution_id' => $institutionId]);
                    }
                }
            }

            // 2. Apply Settings if ID found
            if ($institutionId) {
                $settings = InstitutionSetting::where('institution_id', $institutionId)
                    ->pluck('value', 'key');

                // A. Apply SMTP Configuration
                if ($settings->has('mail_host')) {
                    config([
                        'mail.mailers.smtp.transport' => $settings['mail_driver'] ?? 'smtp',
                        'mail.mailers.smtp.host' => $settings['mail_host'],
                        'mail.mailers.smtp.port' => $settings['mail_port'],
                        'mail.mailers.smtp.username' => $settings['mail_username'],
                        'mail.mailers.smtp.password' => $settings['mail_password'],
                        'mail.mailers.smtp.encryption' => $settings['mail_encryption'],
                        'mail.from.address' => $settings['mail_from_address'],
                        'mail.from.name' => $settings['mail_from_name'],
                    ]);
                }

                // B. Apply SMS Configuration
                if ($settings->has('sms_provider')) {
                    $provider = $settings['sms_provider'];
                    config(['sms.default' => $provider]);
                    
                    if ($settings->has('sms_sender_id')) {
                        // Override sender ID for the selected provider
                        config(["sms.{$provider}.sender_id" => $settings['sms_sender_id']]);
                    }
                }

                // C. Share Enabled Modules with Views (Sidebar)
                $rawModules = $settings['enabled_modules'] ?? '[]';
                $enabledModules = json_decode($rawModules, true);
                
                // If JSON fails or empty, default to ALL modules enabled for Super Admin, or none
                if (!is_array($enabledModules)) {
                    $enabledModules = []; 
                }
                
                // Make $enabledModules available in all Blade files
                view()->share('enabledModules', $enabledModules);
                
                // Also share settings generally if needed
                view()->share('institutionSettings', $settings);
            }
        }

        return $next($request);
    }
}