<?php

use App\Enums\RoleEnum;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\Role;
use App\Models\SchoolBackup;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Backup\SchoolBackupExporter;
use App\Services\Backup\SchoolBackupImporter;
use App\Services\Backup\SchoolBackupPath;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function backupMakeInstitution(string $code): Institution
{
    $institution = new Institution([
        'name' => 'Backup School ' . $code,
        'code' => $code,
        'type' => 'secondary',
        'is_active' => true,
    ]);
    $institution->forceFill(['acronym' => 'BK' . substr($code, -2)])->save();

    return $institution->fresh();
}

function backupEnableModule(Institution $institution): void
{
    InstitutionSetting::set($institution->id, 'enabled_modules', json_encode(['school_backups']), 'modules');

    foreach (['school_backup.view', 'school_backup.create', 'school_backup.manage'] as $name) {
        Permission::findOrCreate($name, 'web');
    }

    $role = Role::firstOrCreate([
        'name' => RoleEnum::SCHOOL_ADMIN->value,
        'guard_name' => 'web',
        'institution_id' => $institution->id,
    ]);
    $role->givePermissionTo(['school_backup.view', 'school_backup.create', 'school_backup.manage']);

    // Active subscription so CheckModuleAccess passes
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

it('exports a zip with digitex-backup manifest', function () {
    $institution = backupMakeInstitution('BK100001');
    InstitutionSetting::set($institution->id, 'backup_test', 'yes', 'general');

    if (Schema::hasTable('students')) {
        Student::query()->create([
            'institution_id' => $institution->id,
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'admission_number' => 'ADM-BK-1',
            'admission_date' => now()->toDateString(),
            'dob' => '2000-01-01',
            'status' => 'active',
            'gender' => 'female',
        ]);
    }

    $backup = SchoolBackup::create([
        'institution_id' => $institution->id,
        'type' => 'manual',
        'status' => 'pending',
        'include_files' => false,
    ]);

    $result = app(SchoolBackupExporter::class)->export($backup);
    $zipPath = SchoolBackupPath::absolute($result->disk_path);

    expect($result->status)->toBe('completed')
        ->and($result->disk_path)->not->toBeNull()
        ->and($zipPath)->not->toBeNull()
        ->and(is_file($zipPath))->toBeTrue();

    $zip = new ZipArchive();
    expect($zip->open($zipPath))->toBeTrue();
    $manifest = json_decode($zip->getFromName('digitex-backup.json'), true);
    $zip->close();

    expect($manifest['format'])->toBe('digitex-school-backup')
        ->and($manifest['schema_version'])->toBe(1)
        ->and($manifest['institution']['code'])->toBe('BK100001');
});

it('imports students into another institution with id remap', function () {
    $source = backupMakeInstitution('BK200001');
    $target = backupMakeInstitution('BK200002');

    if (!Schema::hasTable('students')) {
        $this->markTestSkipped('students table missing');
    }

    Student::query()->create([
        'institution_id' => $source->id,
        'first_name' => 'Grace',
        'last_name' => 'Hopper',
        'admission_number' => 'ADM-IMP-1',
        'admission_date' => now()->toDateString(),
        'dob' => '1999-05-05',
        'status' => 'active',
        'gender' => 'female',
    ]);

    $backup = SchoolBackup::create([
        'institution_id' => $source->id,
        'type' => 'manual',
        'status' => 'pending',
        'include_files' => false,
    ]);
    app(SchoolBackupExporter::class)->export($backup);

    $result = app(SchoolBackupImporter::class)->import(
        SchoolBackupPath::absolute($backup->fresh()->disk_path),
        (int) $target->id,
        false
    );

    expect($result['imported']['students'] ?? 0)->toBeGreaterThan(0);

    $imported = Student::where('institution_id', $target->id)
        ->where(function ($q) {
            $q->where('admission_number', 'ADM-IMP-1')
                ->orWhere('admission_number', 'like', 'ADM-IMP-1-I%');
        })
        ->exists();

    expect($imported)->toBeTrue();
});

it('forbids downloading another institution backup', function () {
    $schoolA = backupMakeInstitution('BK300001');
    $schoolB = backupMakeInstitution('BK300002');
    backupEnableModule($schoolA);
    backupEnableModule($schoolB);

    Role::firstOrCreate([
        'name' => RoleEnum::SCHOOL_ADMIN->value,
        'guard_name' => 'web',
        'institution_id' => null,
    ]);

    $userB = User::factory()->create(['institute_id' => $schoolB->id]);
    $roleB = Role::forInstitution((int) $schoolB->id)->where('name', RoleEnum::SCHOOL_ADMIN->value)->firstOrFail();
    $userB->assignRole($roleB);

    $backup = SchoolBackup::create([
        'institution_id' => $schoolA->id,
        'type' => 'manual',
        'status' => 'completed',
        'disk_path' => 'school-backups/' . $schoolA->id . '/fake.zip',
        'include_files' => false,
    ]);

    $this->actingAs($userB)
        ->withSession(['active_institution_id' => $schoolB->id])
        ->get(route('school-backups.download', $backup))
        ->assertForbidden();
});

it('skips scheduled backups when schedule is off', function () {
    $institution = backupMakeInstitution('BK400001');
    InstitutionSetting::set($institution->id, 'backup_schedule', 'off', 'backup');

    $this->artisan('school-backups:run-scheduled')->assertSuccessful();

    expect(SchoolBackup::where('institution_id', $institution->id)->where('type', 'scheduled')->count())->toBe(0);
});

it('previews the same zip that was just exported', function () {
    $institution = backupMakeInstitution('BK500001');
    backupEnableModule($institution);

    Role::firstOrCreate([
        'name' => RoleEnum::SCHOOL_ADMIN->value,
        'guard_name' => 'web',
        'institution_id' => null,
    ]);

    $user = User::factory()->create(['institute_id' => $institution->id]);
    $role = Role::forInstitution((int) $institution->id)->where('name', RoleEnum::SCHOOL_ADMIN->value)->firstOrFail();
    $user->assignRole($role);

    $backup = SchoolBackup::create([
        'institution_id' => $institution->id,
        'type' => 'manual',
        'status' => 'pending',
        'include_files' => true,
    ]);
    $exported = app(SchoolBackupExporter::class)->export($backup);
    $zipPath = SchoolBackupPath::absolute($exported->disk_path);
    expect($zipPath)->not->toBeNull();

    $copy = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'digitex-backup-upload-' . uniqid('', true) . '.zip';
    copy($zipPath, $copy);

    $this->actingAs($user)
        ->withSession(['active_institution_id' => $institution->id])
        ->post(route('school-backups.import.preview'), [
            'backup_file' => new UploadedFile($copy, 'school-backup.zip', 'application/zip', null, true),
        ])
        ->assertRedirect()
        ->assertSessionHas('info')
        ->assertSessionHas('school_backup_import_preview')
        ->assertSessionMissing('error');

    $stored = session('school_backup_import_path');
    expect($stored)->not->toBeNull()
        ->and(Storage::disk('local')->exists($stored))->toBeTrue()
        ->and(is_file(Storage::disk('local')->path($stored)))->toBeTrue();
});
