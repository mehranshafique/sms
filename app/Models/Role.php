<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
        'institution_id',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function scopeForInstitution(Builder $query, int $institutionId): Builder
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeTemplates(Builder $query): Builder
    {
        return $query->whereNull('institution_id');
    }

    public function isGlobalTemplate(): bool
    {
        return $this->institution_id === null;
    }

    public function isProtectedSystemRole(): bool
    {
        return in_array($this->name, [
            \App\Enums\RoleEnum::SUPER_ADMIN->value,
            \App\Enums\RoleEnum::SCHOOL_ADMIN->value,
            \App\Enums\RoleEnum::HEAD_OFFICER->value,
            \App\Enums\RoleEnum::TEACHER->value,
            \App\Enums\RoleEnum::STUDENT->value,
            \App\Enums\RoleEnum::GUARDIAN->value,
        ], true);
    }
}
