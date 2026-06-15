<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\InstitutionSetting;
use App\Models\User;
use Illuminate\Support\Facades\Session;

class InstitutionSetupAlertService
{
    public function getAlertsForUser(?User $user): array
    {
        if (!$user || !$user->hasRole(['School Admin', 'Head Officer', 'Super Admin'])) {
            return [];
        }

        $institutionId = session('active_institution_id') ?: $user->institute_id;
        if (!$institutionId || $institutionId === 'global') {
            return [];
        }

        $settings = InstitutionSetting::where('institution_id', $institutionId)
            ->pluck('value', 'key')
            ->toArray();

        $dismissed = Session::get('dismissed_setup_alerts_' . $institutionId, []);
        $alerts = [];

        if (empty($settings['smtp_host']) || empty($settings['smtp_from_address'])) {
            $alerts[] = $this->alert(
                'smtp',
                __('configuration.alert_smtp_missing'),
                route('configuration.index') . '#smtp'
            );
        }

        if (empty($settings['school_start_time']) || empty($settings['school_end_time'])) {
            $alerts[] = $this->alert(
                'school_hours',
                __('configuration.alert_school_hours_missing'),
                route('configuration.index') . '#school_year'
            );
        }

        if (empty($settings['academic_start_date']) || empty($settings['academic_end_date'])) {
            $alerts[] = $this->alert(
                'academic_dates',
                __('configuration.alert_academic_dates_missing'),
                route('configuration.index') . '#school_year'
            );
        }

        $hasCommSetup = InstitutionSetting::where('institution_id', $institutionId)
            ->where('group', 'sms')
            ->exists();

        if (!$hasCommSetup) {
            $alerts[] = $this->alert(
                'communication',
                __('configuration.alert_communication_missing'),
                route('configuration.index') . '#sms'
            );
        }

        $hasCurrentSession = AcademicSession::where('institution_id', $institutionId)
            ->where('is_current', true)
            ->exists();

        if (!$hasCurrentSession) {
            $alerts[] = $this->alert(
                'academic_session',
                __('configuration.alert_no_current_session'),
                route('academic-sessions.index')
            );
        }

        $hasCurrencyConfig = InstitutionSetting::where('institution_id', $institutionId)
            ->where('group', 'currency')
            ->where('key', 'currency_code')
            ->exists();

        if (!$hasCurrencyConfig) {
            $alerts[] = $this->alert(
                'currency',
                __('configuration.alert_currency_missing'),
                route('currency.index')
            );
        }

        return array_values(array_filter(
            $alerts,
            fn (array $alert) => !in_array($alert['key'], $dismissed, true)
        ));
    }

    public function dismiss(int|string $institutionId, string $key): void
    {
        $sessionKey = 'dismissed_setup_alerts_' . $institutionId;
        $dismissed = Session::get($sessionKey, []);

        if (!in_array($key, $dismissed, true)) {
            $dismissed[] = $key;
            Session::put($sessionKey, $dismissed);
        }
    }

    private function alert(string $key, string $message, string $url): array
    {
        return [
            'key' => $key,
            'message' => $message,
            'url' => $url,
        ];
    }
}
