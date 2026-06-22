<?php

namespace App\Http\Middleware;

use App\Services\MailSettingsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\InstitutionSetting;
use App\Models\Institution;

class LoadInstitutionSettings
{
    public function __construct(
        protected MailSettingsService $mailSettings
    ) {}

    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $institutionId = $this->resolveInstitutionId($user);

            $this->mailSettings->applyForInstitution($institutionId);

            if ($institutionId) {
                $settings = InstitutionSetting::where('institution_id', $institutionId)
                    ->pluck('value', 'key');

                // B. Apply SMS Configuration
                if ($settings->has('sms_provider')) {
                    $provider = $settings['sms_provider'];
                    config(['sms.default' => $provider]);
                    
                    if ($settings->has('sms_sender_id')) {
                        config(["sms.{$provider}.sender_id" => $settings['sms_sender_id']]);
                    }
                }

                app(\App\Services\CurrencyService::class)->applyToConfig($institutionId);

                $rawModules = $settings['enabled_modules'] ?? '[]';
                $enabledModules = json_decode($rawModules, true);
                
                if (!is_array($enabledModules)) {
                    $enabledModules = []; 
                }
                
                view()->share('enabledModules', $enabledModules);
                view()->share('institutionSettings', $settings);
            }
        }

        return $next($request);
    }

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
