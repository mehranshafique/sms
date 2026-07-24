<?php

use App\Enums\RoleEnum;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Services\ActiveRoleService;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    session()->flush();
});

function activeRoleMakeInstitution(string $code): Institution
{
    $institution = new Institution([
        'name' => 'Active Role School ' . $code,
        'code' => $code,
        'type' => 'secondary',
        'is_active' => true,
    ]);
    $institution->forceFill(['acronym' => 'AR' . substr($code, -2)])->save();

    return $institution->fresh();
}

function activeRoleDualUser(Institution $institution): User
{
    foreach ([RoleEnum::SCHOOL_ADMIN->value, RoleEnum::GUARDIAN->value] as $name) {
        Role::firstOrCreate([
            'name' => $name,
            'guard_name' => 'web',
            'institution_id' => null,
        ]);
        Role::firstOrCreate([
            'name' => $name,
            'guard_name' => 'web',
            'institution_id' => $institution->id,
        ]);
    }

    $user = User::factory()->create(['institute_id' => $institution->id]);
    $guardian = Role::forInstitution((int) $institution->id)->where('name', RoleEnum::GUARDIAN->value)->firstOrFail();
    $admin = Role::forInstitution((int) $institution->id)->where('name', RoleEnum::SCHOOL_ADMIN->value)->firstOrFail();

    $user->assignRole($guardian);
    $user->assignRole($admin);
    $user->unsetRelation('roles');

    return $user->fresh();
}

function activeRolePrepareModules(Institution $institution): void
{
    InstitutionSetting::set(
        $institution->id,
        'enabled_modules',
        json_encode(['academic_reports', 'school_backups']),
        'modules'
    );

    if (Schema::hasTable('subscriptions')) {
        Subscription::query()->updateOrCreate(
            ['institution_id' => $institution->id, 'status' => 'active'],
            [
                'start_date' => now()->subDay(),
                'end_date' => now()->addYear(),
                'price_paid' => 0,
            ]
        );
    }
}

it('treats portal persona as blocking staff capabilities in ActiveRoleService', function () {
    $institution = activeRoleMakeInstitution('AR900003');
    $user = activeRoleDualUser($institution);
    $service = app(ActiveRoleService::class);

    $service->setActiveRole($user, RoleEnum::GUARDIAN->value);

    expect($service->isPortalPersona($user->fresh()))->toBeTrue()
        ->and($service->isAdminPersona($user->fresh()))->toBeFalse()
        ->and($service->canUseStaffCapabilities($user->fresh()))->toBeFalse();

    $service->setActiveRole($user->fresh(), RoleEnum::SCHOOL_ADMIN->value);

    expect($service->isPortalPersona($user->fresh()))->toBeFalse()
        ->and($service->isAdminPersona($user->fresh()))->toBeTrue()
        ->and($service->canUseStaffCapabilities($user->fresh()))->toBeTrue();
});

it('blocks staff module deep links while acting as Guardian', function () {
    $institution = activeRoleMakeInstitution('AR900001');
    $user = activeRoleDualUser($institution);
    activeRolePrepareModules($institution);

    $this->actingAs($user)
        ->withSession([
            'active_role' => RoleEnum::GUARDIAN->value,
            'active_institution_id' => $institution->id,
        ])
        ->get(route('reports.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->withSession([
            'active_role' => RoleEnum::GUARDIAN->value,
            'active_institution_id' => $institution->id,
        ])
        ->get(route('school-backups.index'))
        ->assertForbidden();
});

it('allows reports while acting as School Admin on a dual-role account', function () {
    $institution = activeRoleMakeInstitution('AR900002');
    $user = activeRoleDualUser($institution);
    activeRolePrepareModules($institution);

    $this->actingAs($user)
        ->withSession([
            'active_role' => RoleEnum::SCHOOL_ADMIN->value,
            'active_institution_id' => $institution->id,
        ])
        ->get(route('reports.index'))
        ->assertOk();
});
