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
            $institutionId = $this->resolveInstitutionId($user);

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

                // B2. Apply Currency Configuration
                app(\App\Services\CurrencyService::class)->applyToConfig($institutionId);

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

    /**
     * Resolve active institution as int|null (mirrors BaseController).
     */
    private function resolveInstitutionId($user): ?int
    {
        if ($user->institute_id) {
            return (int) $user->institute_id;
        }

        $activeId = session('active_institution_id');

        if (($activeId === 'global' || $activeId === 0 || $activeId === '0') && $user->hasRole('Super Admin')) {
            return null;
        }

        if ($activeId !== null && $activeId !== '' && is_numeric($activeId)) {
            return (int) $activeId;
        }

        if ($user->institutes && $user->institutes->count() > 0) {
            $id = (int) $user->institutes->first()->id;
            session(['active_institution_id' => $id]);
            return $id;
        }

        if ($user->hasRole('Super Admin')) {
            $firstInst = Institution::where('is_active', true)->first();
            if ($firstInst) {
                session(['active_institution_id' => $firstInst->id]);
                return (int) $firstInst->id;
            }
        }

        return null;
    }
}