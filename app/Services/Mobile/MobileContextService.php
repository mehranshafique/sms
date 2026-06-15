<?php

namespace App\Services\Mobile;

use App\Enums\RoleEnum;
use App\Models\Institution;
use App\Models\AcademicSession;
use App\Models\Student;
use App\Models\StudentParent;
use App\Models\User;
use App\Services\CurrencyService;

class MobileContextService
{
    public function __construct(
        protected CurrencyService $currencyService
    ) {}

    public function build(User $user): array
    {
        $roles = $user->getRoleNames()->values()->all();
        $primaryRole = $roles[0] ?? $this->inferRole($user);
        $isGateAttendant = $user->hasRole(RoleEnum::GATE_ATTENDANT->value);

        $institution = $user->institute;
        $institutionId = $user->institute_id;
        $institutionType = null;
        $isSubjectWise = false;

        if ($institution) {
            $institutionType = is_object($institution->type) ? $institution->type->value : $institution->type;
            $isSubjectWise = in_array($institutionType, ['university', 'vocational', 'lmd'], true);
        }

        $permissions = $user->getAllPermissions()->pluck('name')->values()->all();

        $capabilities = [
            'student_portal' => in_array($primaryRole, ['Student', 'Guardian']) || $user->student !== null,
            'teacher_tools' => $user->hasRole(['Teacher', 'School Admin', 'Head Officer', 'Super Admin']) || ($user->staff !== null && !$isGateAttendant),
            'pickup_management' => $user->can('student.view') || $user->hasRole(['Teacher', 'School Admin', 'Head Officer', 'Super Admin', RoleEnum::GATE_ATTENDANT->value]),
            'mark_attendance' => !$isGateAttendant && ($user->can('student_attendance.create') || $user->hasRole(['Teacher', 'School Admin', 'Head Officer', 'Super Admin'])),
            'hardware_scan' => $user->hasRole(['Teacher', 'School Admin', 'Head Officer', 'Super Admin', 'Staff', RoleEnum::GATE_ATTENDANT->value]),
            'fee_lookup' => !$isGateAttendant && ($user->hasRole(['Teacher', 'School Admin', 'Head Officer', 'Super Admin', 'Accountant']) || $user->can('invoice.view')),
            'nfc_fee_check' => $isGateAttendant || $user->hasRole(['Teacher', 'School Admin', 'Head Officer', 'Super Admin', 'Staff', RoleEnum::GATE_ATTENDANT->value]),
            'nfc_report_card' => $isGateAttendant || $user->hasRole(['Teacher', 'School Admin', 'Head Officer', 'Super Admin', 'Staff']),
            'nfc_identity_check' => $isGateAttendant || $user->hasRole(['Teacher', 'School Admin', 'Head Officer', 'Super Admin', 'Staff', RoleEnum::GATE_ATTENDANT->value]),
            'staff_gate_attendance' => $isGateAttendant || $user->hasRole(['School Admin', 'Head Officer', 'Super Admin', RoleEnum::GATE_ATTENDANT->value]),
            'gate_mode' => $isGateAttendant,
            'super_admin' => $user->hasRole('Super Admin'),
            'head_officer' => $user->hasRole('Head Officer'),
        ];

        if ($capabilities['super_admin']) {
            foreach (array_keys($capabilities) as $key) {
                $capabilities[$key] = true;
            }
        }

        $children = [];
        if ($user->hasRole('Guardian')) {
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

        return [
            'user_id' => $user->id,
            'primary_role' => $primaryRole,
            'roles' => $roles ?: [$primaryRole],
            'permissions' => $permissions,
            'capabilities' => $capabilities,
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

