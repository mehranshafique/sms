<?php

use App\Enums\RoleEnum;
use App\Models\Institution;
use App\Models\Role;
use App\Models\User;
use App\Services\ActiveRoleService;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    session()->flush();
});

it('defaults dual School Admin + Guardian users to School Admin', function () {
    $institution = Institution::query()->create([
        'name' => 'Dual Role School',
        'code' => 'DR900001',
        'type' => 'secondary',
        'is_active' => true,
    ]);

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

    // Attach Guardian first so roles->first() would be Guardian without active-role awareness
    $user->assignRole($guardian);
    $user->assignRole($admin);
    $user->unsetRelation('roles');

    $service = app(ActiveRoleService::class);

    expect($service->getActiveRole($user->fresh()))->toBe(RoleEnum::SCHOOL_ADMIN->value)
        ->and($service->userActsAs($user->fresh(), RoleEnum::SCHOOL_ADMIN->value))->toBeTrue()
        ->and($service->userActsAs($user->fresh(), RoleEnum::GUARDIAN->value))->toBeFalse();

    $service->setActiveRole($user->fresh(), RoleEnum::GUARDIAN->value);

    expect($service->getActiveRole($user->fresh()))->toBe(RoleEnum::GUARDIAN->value)
        ->and($service->userActsAs($user->fresh(), RoleEnum::GUARDIAN->value))->toBeTrue()
        ->and($service->userActsAs($user->fresh(), RoleEnum::SCHOOL_ADMIN->value))->toBeFalse();

    expect($service->availableRoles($user->fresh())->values()->all())
        ->toBe([RoleEnum::SCHOOL_ADMIN->value, RoleEnum::GUARDIAN->value]);
});
