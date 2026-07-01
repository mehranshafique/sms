<?php

namespace App\Services\Mobile;

use App\Enums\RoleEnum;
use App\Models\AcademicSession;
use App\Models\Institution;
use App\Models\Student;
use App\Models\StudentParent;
use App\Models\User;
use App\Services\CurrencyService;
use App\Services\InstitutionModuleAccessService;
use App\Services\PlanContextService;

class MobileContextService
{
    public function __construct(
        protected CurrencyService $currencyService,
        protected InstitutionModuleAccessService $moduleAccess,
        protected MobileActiveRoleService $activeRoles,
        protected MobileMenuService $menuService,
        protected PlanContextService $planContext,
    ) {}

    public function build(User $user): array
    {
        $roles = $user->getRoleNames()->values()->all();
        $activeRole = $this->activeRoles->getActiveRole($user);
        $switchableRoles = $this->activeRoles->availableRoles($user)->values()->all();
        $actsAs = fn (string|array $r) => $this->activeRoles->userActsAs($user, $r);

        $isGateAttendant = $actsAs(RoleEnum::GATE_ATTENDANT->value);
        $isSuperAdmin = $actsAs(RoleEnum::SUPER_ADMIN->value);

        $institution = $user->institute;
        $institutionId = $user->institute_id;
        $institutionType = null;
        $isSubjectWise = false;

        if ($institution) {
            $institutionType = is_object($institution->type) ? $institution->type->value : $institution->type;
            $isSubjectWise = in_array($institutionType, ['university', 'vocational', 'lmd'], true);
        }

        $permissions = $user->getAllPermissions()->pluck('name')->values()->all();
        $enabledModules = $institutionId
            ? $this->moduleAccess->getEffectiveModules((int) $institutionId)
            : [];

        $hasActiveSubscription = $institutionId
            ? $this->moduleAccess->hasActiveSubscription((int) $institutionId)
            : true;

        $moduleEnabled = fn (string $slug): bool => $isSuperAdmin || (
            $hasActiveSubscription && in_array(strtolower($slug), $enabledModules, true)
        );

        $capabilities = [
            'student_portal' => $actsAs([RoleEnum::STUDENT->value, RoleEnum::GUARDIAN->value]) || $user->student !== null,
            'teacher_tools' => $actsAs([RoleEnum::TEACHER->value, RoleEnum::SCHOOL_ADMIN->value, RoleEnum::HEAD_OFFICER->value, RoleEnum::SUPER_ADMIN->value])
                || ($user->staff !== null && !$isGateAttendant),
            'pickup_management' => ($user->can('student.view') || $actsAs([RoleEnum::TEACHER->value, RoleEnum::SCHOOL_ADMIN->value, RoleEnum::HEAD_OFFICER->value, RoleEnum::SUPER_ADMIN->value, RoleEnum::GATE_ATTENDANT->value]))
                && $moduleEnabled('students'),
            'mark_attendance' => !$isGateAttendant
                && ($user->can('student_attendance.create') || $actsAs([RoleEnum::TEACHER->value, RoleEnum::SCHOOL_ADMIN->value, RoleEnum::HEAD_OFFICER->value, RoleEnum::SUPER_ADMIN->value]))
                && $moduleEnabled('student_attendance'),
            'hardware_scan' => $actsAs([RoleEnum::TEACHER->value, RoleEnum::SCHOOL_ADMIN->value, RoleEnum::HEAD_OFFICER->value, RoleEnum::SUPER_ADMIN->value, 'Staff', RoleEnum::GATE_ATTENDANT->value]),
            'fee_lookup' => !$isGateAttendant
                && ($actsAs([RoleEnum::TEACHER->value, RoleEnum::SCHOOL_ADMIN->value, RoleEnum::HEAD_OFFICER->value, RoleEnum::SUPER_ADMIN->value, 'Accountant', 'accountant']) || $user->can('invoice.view'))
                && $moduleEnabled('invoices'),
            'nfc_fee_check' => $isGateAttendant || $actsAs([RoleEnum::TEACHER->value, RoleEnum::SCHOOL_ADMIN->value, RoleEnum::HEAD_OFFICER->value, RoleEnum::SUPER_ADMIN->value, 'Staff', RoleEnum::GATE_ATTENDANT->value]),
            'nfc_report_card' => $isGateAttendant || $actsAs([RoleEnum::TEACHER->value, RoleEnum::SCHOOL_ADMIN->value, RoleEnum::HEAD_OFFICER->value, RoleEnum::SUPER_ADMIN->value, 'Staff']),
            'nfc_identity_check' => $isGateAttendant || $actsAs([RoleEnum::TEACHER->value, RoleEnum::SCHOOL_ADMIN->value, RoleEnum::HEAD_OFFICER->value, RoleEnum::SUPER_ADMIN->value, 'Staff', RoleEnum::GATE_ATTENDANT->value]),
            'staff_gate_attendance' => $isGateAttendant || $actsAs([RoleEnum::SCHOOL_ADMIN->value, RoleEnum::HEAD_OFFICER->value, RoleEnum::SUPER_ADMIN->value, RoleEnum::GATE_ATTENDANT->value]),
            'gate_mode' => $isGateAttendant,
            'super_admin' => $isSuperAdmin,
            'head_officer' => $actsAs(RoleEnum::HEAD_OFFICER->value),
            'multi_role' => count($switchableRoles) > 1,
        ];

        if ($capabilities['super_admin']) {
            foreach (array_keys($capabilities) as $key) {
                if ($key !== 'multi_role') {
                    $capabilities[$key] = true;
                }
            }
            $capabilities['multi_role'] = count($switchableRoles) > 1;
        }

        if (!$hasActiveSubscription && !$isSuperAdmin) {
            foreach (array_keys($capabilities) as $key) {
                if (!in_array($key, ['super_admin', 'head_officer', 'multi_role', 'gate_mode'], true)) {
                    $capabilities[$key] = false;
                }
            }
        }

        $children = [];
        if ($actsAs(RoleEnum::GUARDIAN->value) || $user->hasRole(RoleEnum::GUARDIAN->value)) {
            $parent = StudentParent::where('user_id', $user->id)->first();
            if ($parent) {
                $children = Student::where('parent_id', $parent->id)
                    ->get(['id', 'first_name', 'last_name', 'admission_number'])
                    ->map(fn ($s) => [
                        'id' => $s->id,
                        'name' => $s->full_name,
                        'admission_number' => $s->admission_number,
                    ])->values()->all();
            }
        }

        $sessionName = null;
        if ($institutionId) {
            $session = AcademicSession::where('institution_id', $institutionId)
                ->where('is_current', true)
                ->first();
            $sessionName = $session?->name;
        }

        $subscription = $this->subscriptionPayload($institutionId);
        $menu = $this->menuService->build($user, $capabilities, $enabledModules);

        return [
            'user_id' => $user->id,
            'primary_role' => $activeRole ?? ($roles[0] ?? $this->inferRole($user)),
            'active_role' => $activeRole,
            'roles' => $roles ?: [$this->inferRole($user)],
            'switchable_roles' => $switchableRoles,
            'permissions' => $permissions,
            'capabilities' => $capabilities,
            'enabled_modules' => $enabledModules,
            'subscription' => $subscription,
            'institution_id' => $institutionId,
            'institution_type' => $institutionType,
            'is_subject_wise' => $isSubjectWise,
            'school_name' => $institution?->name ?? 'Digitex',
            'school_logo' => ($institution && $institution->logo) ? asset('storage/' . $institution->logo) : null,
            'academic_session_name' => $sessionName,
            'staff_id' => $user->staff?->id,
            'student_id' => $user->student?->id,
            'children' => $children,
            'currency' => $this->currencyService->apiPayload($institutionId),
            'app_links' => $this->appLinks(),
            'menu' => $menu,
            'features' => [
                'notifications' => true,
                'role_switching' => count($switchableRoles) > 1,
                'support_tickets' => true,
                'ai_copilot' => ($this->planContext->snapshot($user)['has_ai'] ?? false),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function subscriptionPayload(?int $institutionId): array
    {
        if (!$institutionId) {
            return ['active' => false, 'plan_name' => null, 'expires_at' => null];
        }

        $sub = $this->moduleAccess->activeSubscription($institutionId);
        if (!$sub) {
            return ['active' => false, 'plan_name' => null, 'expires_at' => null];
        }

        return [
            'active' => true,
            'plan_name' => $sub->package?->name,
            'package_id' => $sub->package_id,
            'status' => $sub->status,
            'expires_at' => $sub->end_date?->toDateString(),
            'days_left' => $sub->end_date ? max(0, now()->startOfDay()->diffInDays($sub->end_date, false)) : null,
        ];
    }

    /** @return array<string, string> */
    private function appLinks(): array
    {
        $base = rtrim(config('app.url'), '/');

        return [
            'help' => $base . '/help',
            'manual_web' => $base . '/manual/web',
            'manual_mobile' => $base . '/manual/mobile',
            'community' => $base . '/community',
            'pay_lookup' => $base . '/pay',
            'support' => $base . '/support',
            'plan' => $base . '/plan',
        ];
    }

    private function inferRole(User $user): string
    {
        if ($user->hasRole(RoleEnum::GATE_ATTENDANT->value)) {
            return RoleEnum::GATE_ATTENDANT->value;
        }
        if ($user->student) {
            return 'Student';
        }
        if (StudentParent::where('user_id', $user->id)->exists()) {
            return 'Guardian';
        }
        if ($user->staff) {
            return 'Staff';
        }

        return 'User';
    }

    public function isSubjectWise(?int $institutionId): bool
    {
        if (!$institutionId) {
            return false;
        }
        $institution = Institution::find($institutionId);

        if (!$institution) {
            return false;
        }

        $type = is_object($institution->type) ? $institution->type->value : $institution->type;

        return in_array($type, ['university', 'vocational', 'lmd'], true);
    }
}
