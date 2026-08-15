<?php

namespace App\Services\Academic;

use App\Enums\RoleEnum;
use App\Models\Assignment;
use App\Models\InstitutionSetting;
use App\Models\Role;
use App\Models\User;

/**
 * Per-school homework approval policy.
 *
 * A school can require that homework is reviewed before parents and students
 * see it, and choose which roles hold that authority (e.g. Director / Academic
 * Coordinator). When approval is off — the default — homework is published the
 * moment a teacher saves it, exactly as before.
 */
class HomeworkApprovalService
{
    public const SETTING_REQUIRED = 'homework_approval_required';
    public const SETTING_ROLES = 'homework_approver_roles';
    public const SETTING_GROUP = 'academics';

    /** Fallback approvers when the school has not nominated any role. */
    public const DEFAULT_APPROVER_ROLES = [
        RoleEnum::SCHOOL_ADMIN->value,
        RoleEnum::HEAD_OFFICER->value,
    ];

    /**
     * Roles that keep the authority whatever the school selects, so a school can
     * never lock itself out of its own approval queue.
     */
    public const ALWAYS_APPROVER_ROLES = [
        RoleEnum::SUPER_ADMIN->value,
        RoleEnum::SCHOOL_ADMIN->value,
    ];

    public function isRequired(?int $institutionId): bool
    {
        if (! $institutionId) {
            return false;
        }

        return (string) InstitutionSetting::get($institutionId, self::SETTING_REQUIRED, '0') === '1';
    }

    /**
     * Roles the school nominated as approvers.
     *
     * @return array<int, string>
     */
    public function approverRoles(?int $institutionId): array
    {
        if (! $institutionId) {
            return [];
        }

        $decoded = json_decode((string) InstitutionSetting::get($institutionId, self::SETTING_ROLES, '[]'), true);

        return is_array($decoded) ? array_values(array_filter(array_map('strval', $decoded))) : [];
    }

    /**
     * Roles a school can pick from: its own roles plus the global templates,
     * minus the ones that can never be approvers.
     *
     * @return array<int, string>
     */
    public function assignableRoles(?int $institutionId): array
    {
        $excluded = [
            RoleEnum::SUPER_ADMIN->value,
            RoleEnum::STUDENT->value,
            RoleEnum::GUARDIAN->value,
        ];

        return Role::query()
            ->when($institutionId, fn ($query) => $query->where(function ($q) use ($institutionId) {
                $q->where('institution_id', $institutionId)->orWhereNull('institution_id');
            }))
            ->whereNotIn('name', $excluded)
            ->pluck('name')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function saveSettings(?int $institutionId, bool $required, array $roles): void
    {
        if (! $institutionId) {
            return;
        }

        $allowed = $this->assignableRoles($institutionId);
        $roles = array_values(array_intersect(array_map('strval', $roles), $allowed));

        InstitutionSetting::set($institutionId, self::SETTING_REQUIRED, $required ? '1' : '0', self::SETTING_GROUP);
        InstitutionSetting::set($institutionId, self::SETTING_ROLES, json_encode($roles), self::SETTING_GROUP);
    }

    /**
     * Roles that actually hold the authority for this school.
     *
     * @return array<int, string>
     */
    public function effectiveApproverRoles(?int $institutionId): array
    {
        $selected = $this->approverRoles($institutionId);
        $roles = $selected !== [] ? $selected : self::DEFAULT_APPROVER_ROLES;

        return array_values(array_unique(array_merge($roles, self::ALWAYS_APPROVER_ROLES)));
    }

    /**
     * Can this user approve or reject homework for the institution?
     */
    public function canApprove(?User $user, ?int $institutionId): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        if (! $user->can('assignment.approve')) {
            return false;
        }

        return $user->hasAnyRole($this->effectiveApproverRoles($institutionId));
    }

    /**
     * Status a newly created homework should get.
     */
    public function initialStatus(?User $user, ?int $institutionId): string
    {
        if (! $this->isRequired($institutionId)) {
            return Assignment::STATUS_APPROVED;
        }

        // Someone who could approve it anyway does not need to wait for review.
        return $this->canApprove($user, $institutionId)
            ? Assignment::STATUS_APPROVED
            : Assignment::STATUS_PENDING;
    }

    /**
     * Attributes to persist alongside a new homework record.
     *
     * @return array<string, mixed>
     */
    public function attributesForNew(?User $user, ?int $institutionId): array
    {
        $status = $this->initialStatus($user, $institutionId);
        $now = now();

        if ($status === Assignment::STATUS_APPROVED) {
            return [
                'status' => $status,
                'submitted_at' => $now,
                'approved_by' => $user?->id,
                'approved_at' => $now,
                'published_at' => $now,
            ];
        }

        return [
            'status' => $status,
            'submitted_at' => $now,
        ];
    }

    public function approve(Assignment $assignment, User $approver): Assignment
    {
        $assignment->update([
            'status' => Assignment::STATUS_APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'rejection_reason' => null,
            'published_at' => $assignment->published_at ?? now(),
        ]);

        return $assignment->refresh();
    }

    public function reject(Assignment $assignment, User $approver, ?string $reason = null): Assignment
    {
        $assignment->update([
            'status' => Assignment::STATUS_REJECTED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
            'published_at' => null,
        ]);

        return $assignment->refresh();
    }

    /**
     * Users who should be told that homework is waiting for review.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function approvers(?int $institutionId)
    {
        if (! $institutionId) {
            return collect();
        }

        $roles = array_diff($this->effectiveApproverRoles($institutionId), [RoleEnum::SUPER_ADMIN->value]);

        return User::query()
            ->where('institute_id', $institutionId)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', $roles))
            ->get()
            ->filter(fn (User $user) => $user->can('assignment.approve'))
            ->values();
    }
}
