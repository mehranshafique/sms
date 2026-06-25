<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Models\InstitutionSetting;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Str;

class InstitutionModuleAccessService
{
    /** @var array<string, string> */
    private const PERMISSION_PREFIX_MAP = [
        'class_subjects' => 'class_subject',
        'student_requests' => 'student_request',
        'staff_leaves' => 'staff_leave',
        'staff_attendance' => 'staff_attendance',
        'student_attendance' => 'student_attendance',
        'student_enrollments' => 'student_enrollment',
        'university_enrollments' => 'university_enrollment',
        'academic_sessions' => 'academic_session',
        'grade_levels' => 'grade_level',
        'class_sections' => 'class_section',
        'exam_schedules' => 'exam_schedule',
        'exam_marks' => 'exam_mark',
        'fee_structures' => 'fee_structure',
        'fee_types' => 'fee_type',
        'sms_templates' => 'sms_template',
        'audit_logs' => 'audit_log',
        'fund_requests' => 'fund_request',
    ];

    public function hasActiveSubscription(int $institutionId): bool
    {
        return $this->activeSubscription($institutionId) !== null;
    }

    public function activeSubscription(int $institutionId): ?Subscription
    {
        return Subscription::with('package')
            ->where('institution_id', $institutionId)
            ->where('status', 'active')
            ->where('end_date', '>=', now()->startOfDay())
            ->latest('created_at')
            ->first();
    }

    /** @return list<string> */
    public function getEffectiveModules(int $institutionId): array
    {
        $setting = InstitutionSetting::where('institution_id', $institutionId)
            ->where('key', 'enabled_modules')
            ->value('value');

        $configured = $this->normalizeModuleList($setting);

        if ($configured !== []) {
            return $configured;
        }

        $subscription = $this->activeSubscription($institutionId);
        $packageModules = $subscription?->package?->modules ?? [];

        return $this->normalizeModuleList($packageModules);
    }

    public function isModuleEnabled(int $institutionId, string $moduleSlug): bool
    {
        $slug = strtolower(trim($moduleSlug));
        $modules = $this->getEffectiveModules($institutionId);

        return in_array($slug, $modules, true);
    }

    public function userHasModulePermission(User $user, string $moduleSlug): bool
    {
        if ($user->hasRole([
            RoleEnum::SUPER_ADMIN->value,
            RoleEnum::SCHOOL_ADMIN->value,
            RoleEnum::HEAD_OFFICER->value,
        ])) {
            return true;
        }

        $prefix = $this->permissionPrefix($moduleSlug);
        $candidates = [
            "{$prefix}.view",
            "{$prefix}.viewAny",
            "{$prefix}.create",
        ];

        foreach ($candidates as $permission) {
            try {
                if ($user->can($permission)) {
                    return true;
                }
            } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
                continue;
            }
        }

        return false;
    }

    public function canAccessModule(User $user, int $institutionId, string $moduleSlug): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        if (!$this->hasActiveSubscription($institutionId)) {
            return false;
        }

        if (!$this->isModuleEnabled($institutionId, $moduleSlug)) {
            return false;
        }

        return $this->userHasModulePermission($user, $moduleSlug);
    }

    private function permissionPrefix(string $moduleSlug): string
    {
        $slug = strtolower(trim($moduleSlug));

        return self::PERMISSION_PREFIX_MAP[$slug] ?? Str::singular($slug);
    }

    /** @return list<string> */
    private function normalizeModuleList(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_map(
            fn ($module) => strtolower(trim((string) $module)),
            $value
        )));
    }
}
