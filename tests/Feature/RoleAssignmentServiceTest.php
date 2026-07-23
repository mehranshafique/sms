<?php

use App\Enums\RoleEnum;
use App\Models\Institution;
use App\Models\Role;
use App\Models\User;
use App\Services\RoleAssignmentService;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function makeInstitution(string $code = '01180005'): Institution
{
    $institution = new Institution([
        'name' => 'Test School ' . $code,
        'code' => $code,
        'type' => 'secondary',
        'is_active' => true,
    ]);
    $institution->forceFill(['acronym' => 'TS' . substr($code, -2)])->save();

    return $institution->fresh();
}

function makeGlobalAndInstitutionRoles(Institution $institution): array
{
    $global = Role::firstOrCreate([
        'name' => RoleEnum::SCHOOL_ADMIN->value,
        'guard_name' => 'web',
        'institution_id' => null,
    ]);

    $scoped = Role::firstOrCreate([
        'name' => RoleEnum::SCHOOL_ADMIN->value,
        'guard_name' => 'web',
        'institution_id' => $institution->id,
    ]);

    Role::firstOrCreate([
        'name' => RoleEnum::TEACHER->value,
        'guard_name' => 'web',
        'institution_id' => null,
    ]);

    Role::firstOrCreate([
        'name' => RoleEnum::TEACHER->value,
        'guard_name' => 'web',
        'institution_id' => $institution->id,
    ]);

    return compact('global', 'scoped');
}

it('resolves the institution-scoped role never the global template', function () {
    $institution = makeInstitution();
    ['global' => $global, 'scoped' => $scoped] = makeGlobalAndInstitutionRoles($institution);

    $resolved = app(RoleAssignmentService::class)
        ->resolveForInstitution(RoleEnum::SCHOOL_ADMIN->value, (int) $institution->id);

    expect($resolved->id)->toBe($scoped->id)
        ->and($resolved->id)->not->toBe($global->id)
        ->and($resolved->institution_id)->toBe($institution->id);
});

it('assigns users to the institution role id so users_count matches', function () {
    $institution = makeInstitution('01180006');
    ['global' => $global, 'scoped' => $scoped] = makeGlobalAndInstitutionRoles($institution);

    $user = User::factory()->create([
        'institute_id' => $institution->id,
    ]);

    app(RoleAssignmentService::class)->assign($user, RoleEnum::SCHOOL_ADMIN->value, (int) $institution->id);

    expect($user->fresh()->roles->pluck('id')->all())->toContain($scoped->id)
        ->and($user->fresh()->roles->pluck('id')->all())->not->toContain($global->id)
        ->and($scoped->fresh()->users()->where('users.institute_id', $institution->id)->count())->toBe(1);
});

it('rejects assigning a role from another institution', function () {
    $schoolA = makeInstitution('A1000001');
    $schoolB = makeInstitution('B1000002');
    makeGlobalAndInstitutionRoles($schoolA);
    makeGlobalAndInstitutionRoles($schoolB);

    $roleB = Role::forInstitution((int) $schoolB->id)
        ->where('name', RoleEnum::SCHOOL_ADMIN->value)
        ->firstOrFail();

    $user = User::factory()->create(['institute_id' => $schoolA->id]);

    app(RoleAssignmentService::class)->assign($user, $roleB->id, (int) $schoolA->id);
})->throws(\Symfony\Component\HttpKernel\Exception\HttpException::class);

it('repairs users stuck on the global School Admin template', function () {
    $institution = makeInstitution('01180007');
    ['global' => $global, 'scoped' => $scoped] = makeGlobalAndInstitutionRoles($institution);

    $user = User::factory()->create(['institute_id' => $institution->id]);
    $user->assignRole($global);

    expect($scoped->users()->count())->toBe(0);

    $this->artisan('roles:repair-institutions')->assertSuccessful();

    $user->refresh();
    expect($user->roles->pluck('id')->all())->toContain($scoped->id)
        ->and($user->roles->pluck('id')->all())->not->toContain($global->id)
        ->and($scoped->fresh()->users()->count())->toBe(1);
});

it('treats institution-scoped School Admin as hasRole School Admin', function () {
    $institution = makeInstitution('01180008');
    ['global' => $global, 'scoped' => $scoped] = makeGlobalAndInstitutionRoles($institution);

    $user = User::factory()->create(['institute_id' => $institution->id]);
    $user->assignRole($scoped);
    $user->unsetRelation('roles');

    expect($user->fresh()->hasRole(RoleEnum::SCHOOL_ADMIN->value))->toBeTrue()
        ->and($user->fresh()->hasRole(['Teacher', RoleEnum::SCHOOL_ADMIN->value]))->toBeTrue()
        ->and($user->fresh()->hasRole($global))->toBeTrue() // same name as assigned institution role
        ->and($user->fresh()->roles->pluck('id')->all())->toContain($scoped->id)
        ->and($user->fresh()->roles->pluck('id')->all())->not->toContain($global->id);
});

it('lets institution School Admin open reports without academic_report.view on the role', function () {
    $institution = makeInstitution('01180009');
    ['scoped' => $scoped] = makeGlobalAndInstitutionRoles($institution);

    \App\Models\Subscription::create([
        'institution_id' => $institution->id,
        'start_date' => now()->subDay(),
        'end_date' => now()->addYear(),
        'status' => 'active',
        'price_paid' => 0,
    ]);

    // Intentionally do NOT attach academic_report.view — admin bypass must still work.
    $user = User::factory()->create(['institute_id' => $institution->id]);
    $user->assignRole($scoped);

    $this->actingAs($user)
        ->withSession(['active_institution_id' => $institution->id])
        ->get(route('reports.index'))
        ->assertOk();
});

it('forbids custom roles without academic_report.view from reports', function () {
    $institution = makeInstitution('01180010');
    makeGlobalAndInstitutionRoles($institution);

    \App\Models\Subscription::create([
        'institution_id' => $institution->id,
        'start_date' => now()->subDay(),
        'end_date' => now()->addYear(),
        'status' => 'active',
        'price_paid' => 0,
    ]);

    $custom = Role::firstOrCreate([
        'name' => 'Clerk',
        'guard_name' => 'web',
        'institution_id' => $institution->id,
    ]);

    $user = User::factory()->create(['institute_id' => $institution->id]);
    $user->assignRole($custom);

    $this->actingAs($user)
        ->withSession(['active_institution_id' => $institution->id])
        ->get(route('reports.index'))
        ->assertForbidden();
});

it('repairs a single institution by code', function () {
    $target = makeInstitution('01180005');
    $other = makeInstitution('01189999');
    makeGlobalAndInstitutionRoles($target);
    makeGlobalAndInstitutionRoles($other);

    $user = User::factory()->create(['institute_id' => $target->id]);
    $global = Role::templates()->where('name', RoleEnum::SCHOOL_ADMIN->value)->firstOrFail();
    $user->roles()->attach($global->id);

    $this->artisan('roles:repair-institutions', ['--code' => '01180005'])->assertSuccessful();

    $scoped = Role::forInstitution((int) $target->id)
        ->where('name', RoleEnum::SCHOOL_ADMIN->value)
        ->firstOrFail();

    expect($user->fresh()->roles->pluck('id')->all())->toContain($scoped->id)
        ->and($user->fresh()->roles->pluck('id')->all())->not->toContain($global->id);
});
