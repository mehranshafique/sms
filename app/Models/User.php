<?php

namespace App\Models;

use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Institution; // Fixed: Import Institution Model

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'shortcode',
        'password',
        'phone',
        'address',
        'language',
        'profile_picture',
        'fcm_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * For Head Officers: Manage MULTIPLE Institutes.
     * Uses pivot table 'institution_head_officers'.
     */
    public function institutes()
    {
        return $this->belongsToMany(
            Institution::class, // Fixed: Changed from Institute to Institution
            'institution_head_officers', 
            'user_id', 
            'institution_id'
        )->withTimestamps();
    }

    /**
     * For Regular Staff/Students: Direct link to ONE institute.
     */
    public function institute()
    {
        return $this->belongsTo(Institution::class); // Fixed: Changed from Institute to Institution
    }

    /**
     * Get the staff profile associated with the user.
     */
    public function staff()
    {
        return $this->hasOne(Staff::class);
    }

    /**
     * Get the student profile associated with the user.
     */
    public function student()
    {
        return $this->hasOne(Student::class);
    }

    /**
     * Match role names against assigned roles by name (not Spatie findByName).
     *
     * Multiple rows can share the same name (global template + per-institution).
     * findByName() returns a single ambiguous row; comparing by id would wrongly
     * fail for users attached to the institution-scoped School Admin role.
     *
     * @param  string|int|array|RoleContract|Collection|\BackedEnum  $roles
     */
    public function hasRole($roles, ?string $guard = null): bool
    {
        $this->loadMissing('roles');

        if (is_string($roles) && str_contains($roles, '|')) {
            $roles = $this->convertPipeToArray($roles);
        }

        if ($roles instanceof \BackedEnum) {
            $roles = $roles->value;
        }

        if (is_int($roles) || PermissionRegistrar::isUid($roles)) {
            $key = (new ($this->getRoleClass())())->getKeyName();

            return $guard
                ? $this->roles->where('guard_name', $guard)->contains($key, $roles)
                : $this->roles->contains($key, $roles);
        }

        if (is_string($roles)) {
            return $this->roles
                ->when($guard, fn ($q) => $q->where('guard_name', $guard))
                ->contains(fn ($role) => $role->name === $roles);
        }

        if ($roles instanceof RoleContract) {
            // Name match covers institution-scoped duplicates of the same system role.
            if ($this->roles->contains(fn ($role) => $role->name === $roles->name
                && ($guard === null || $role->guard_name === $guard))) {
                return true;
            }

            return $this->roles->contains($roles->getKeyName(), $roles->getKey());
        }

        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($this->hasRole($role, $guard)) {
                    return true;
                }
            }

            return false;
        }

        if ($roles instanceof Collection) {
            foreach ($roles as $role) {
                if ($this->hasRole($role, $guard)) {
                    return true;
                }
            }

            return false;
        }

        throw new \TypeError('Unsupported type for $roles parameter to hasRole().');
    }

    /**
     * Prefer the user's institution-scoped role when resolving by name for assign/remove.
     */
    protected function getStoredRole($role): RoleContract
    {
        if ($role instanceof \BackedEnum) {
            $role = $role->value;
        }

        if (is_string($role) && $this->institute_id) {
            $scoped = Role::query()
                ->where('name', $role)
                ->where('guard_name', $this->getDefaultGuardName())
                ->where('institution_id', $this->institute_id)
                ->first();

            if ($scoped) {
                return $scoped;
            }
        }

        if (is_int($role) || PermissionRegistrar::isUid($role)) {
            return $this->getRoleClass()::findById($role, $this->getDefaultGuardName());
        }

        if (is_string($role)) {
            return $this->getRoleClass()::findByName($role, $this->getDefaultGuardName());
        }

        return $role;
    }
}