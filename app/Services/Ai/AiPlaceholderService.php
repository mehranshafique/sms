<?php

namespace App\Services\Ai;

use App\Models\AcademicSession;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\User;

/**
 * Resolves school/user context and replaces AI bracket placeholders in drafts.
 */
class AiPlaceholderService
{
    /** @return array<string, string> */
    public function resolve(?User $user, ?int $institutionId): array
    {
        $institution = $institutionId ? Institution::find($institutionId) : null;

        $senderName = trim((string) ($user?->name ?? ''));
        $position   = $this->resolvePosition($user);
        $schoolName = trim((string) ($institution?->name ?? config('app.name', 'School')));

        $phone = trim((string) ($institution?->phone ?? ''));
        $email = trim((string) (
            $institution?->email
            ?: InstitutionSetting::get($institutionId, 'mail_from_address')
            ?: InstitutionSetting::get($institutionId, 'smtp_from_address')
            ?: ''
        ));

        $contactParts = array_filter([
            $phone !== '' ? __('ai.contact_phone', ['phone' => $phone]) : null,
            $email !== '' ? __('ai.contact_email', ['email' => $email]) : null,
        ]);

        if ($contactParts === [] && $institution?->head_person_phone) {
            $contactParts[] = __('ai.contact_phone', ['phone' => $institution->head_person_phone]);
        }

        $address = trim((string) ($institution?->address ?? ''));
        $contactInfo = $contactParts !== [] ? implode(' | ', $contactParts) : $address;

        $session = $institutionId
            ? AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first()
            : null;

        return [
            'sender_name'      => $senderName !== '' ? $senderName : __('ai.default_sender_name'),
            'position'         => $position,
            'school_name'      => $schoolName,
            'contact_info'     => $contactInfo !== '' ? $contactInfo : __('ai.contact_not_set'),
            'phone'            => $phone,
            'email'            => $email,
            'address'          => $address,
            'academic_session' => trim((string) ($session?->name ?? '')),
            'head_name'        => trim((string) ($institution?->head_person_name ?? $senderName)),
        ];
    }

    public function promptBlock(?User $user, ?int $institutionId): string
    {
        $ctx = $this->resolve($user, $institutionId);

        $lines = [
            'SIGN-OFF CONTEXT (use these exact values in the message — never output bracket placeholders):',
            "- Sender name: {$ctx['sender_name']}",
            "- Title/role: {$ctx['position']}",
            "- School: {$ctx['school_name']}",
            "- Contact: {$ctx['contact_info']}",
        ];

        if ($ctx['academic_session'] !== '') {
            $lines[] = "- Academic session: {$ctx['academic_session']}";
        }

        return implode("\n", $lines);
    }

    public function apply(string $text, ?User $user, ?int $institutionId): string
    {
        if ($text === '') {
            return $text;
        }

        $ctx = $this->resolve($user, $institutionId);

        $replacements = [
            '[Your Name]'           => $ctx['sender_name'],
            '[Sender Name]'         => $ctx['sender_name'],
            '[Author Name]'         => $ctx['sender_name'],
            '[Your Position]'       => $ctx['position'],
            '[Position]'            => $ctx['position'],
            '[Your Role]'           => $ctx['position'],
            '[Role]'                => $ctx['position'],
            '[Job Title]'           => $ctx['position'],
            '[School Name]'         => $ctx['school_name'],
            '[Institution Name]'    => $ctx['school_name'],
            '[School]'              => $ctx['school_name'],
            '[Contact Information]' => $ctx['contact_info'],
            '[Contact Info]'        => $ctx['contact_info'],
            '[Contact Details]'     => $ctx['contact_info'],
            '[Phone Number]'        => $ctx['phone'] ?: $ctx['contact_info'],
            '[School Phone]'        => $ctx['phone'] ?: $ctx['contact_info'],
            '[Phone]'               => $ctx['phone'] ?: $ctx['contact_info'],
            '[Email Address]'       => $ctx['email'] ?: $ctx['contact_info'],
            '[School Email]'        => $ctx['email'] ?: $ctx['contact_info'],
            '[Email]'               => $ctx['email'] ?: $ctx['contact_info'],
            '[School Address]'      => $ctx['address'],
            '[Address]'             => $ctx['address'],
            '[Academic Session]'    => $ctx['academic_session'],
            '[Academic Year]'       => $ctx['academic_session'],
            '[Head Name]'           => $ctx['head_name'],
            '[Principal Name]'      => $ctx['head_name'],
        ];

        foreach ($replacements as $placeholder => $value) {
            $text = str_ireplace($placeholder, $value, $text);
        }

        // Standalone [Name] only when not part of [Student Name], [Parent Name], etc.
        $text = preg_replace(
            '/(?<!(Student |Parent |Guardian |Child |Pupil ))\[Name\]/iu',
            $ctx['sender_name'],
            $text
        ) ?? $text;

        return $text;
    }

    protected function resolvePosition(?User $user): string
    {
        if (!$user) {
            return __('ai.default_sender_position');
        }

        $user->loadMissing('staff');

        if ($user->staff?->designation) {
            return trim((string) $user->staff->designation);
        }

        if (method_exists($user, 'getRoleNames')) {
            $role = $user->getRoleNames()->first();
            if ($role) {
                $roles = __('dashboard.roles');
                if (is_array($roles) && isset($roles[$role])) {
                    return $roles[$role];
                }
                return ucwords(str_replace(['_', '-'], ' ', (string) $role));
            }
        }

        return __('ai.default_sender_position');
    }
}
