<?php

use App\Enums\RoleEnum;
use App\Models\AcademicSession;
use App\Models\ClassSection;
use App\Models\GradeLevel;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AttendanceOverviewService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function overviewMakeInstitution(string $code): Institution
{
    $institution = new Institution([
        'name' => 'Overview School '.$code,
        'code' => $code,
        'type' => 'secondary',
        'is_active' => true,
    ]);
    $institution->forceFill(['acronym' => 'OV'.substr($code, -2)])->save();

    return $institution->fresh();
}

function overviewEnableAttendance(Institution $institution): void
{
    InstitutionSetting::set($institution->id, 'enabled_modules', json_encode(['student_attendance', 'staff_attendance', 'students', 'staff']), 'modules');

    foreach (['student_attendance.view', 'staff_attendance.view', 'student.view', 'staff.view'] as $name) {
        Permission::findOrCreate($name, 'web');
    }

    $role = Role::firstOrCreate([
        'name' => RoleEnum::SCHOOL_ADMIN->value,
        'guard_name' => 'web',
        'institution_id' => $institution->id,
    ]);
    $role->givePermissionTo(['student_attendance.view', 'staff_attendance.view', 'student.view', 'staff.view']);

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

function overviewMakeAdmin(Institution $institution): User
{
    $user = User::factory()->create([
        'institute_id' => $institution->id,
        'email' => 'overview-admin-'.$institution->id.'@example.com',
    ]);

    $role = Role::where('name', RoleEnum::SCHOOL_ADMIN->value)
        ->where('institution_id', $institution->id)
        ->first();
    $user->assignRole($role);

    return $user;
}

it('aggregates expected present late absent and not checked in by class', function () {
    $institution = overviewMakeInstitution('OV200001');
    overviewEnableAttendance($institution);

    $session = AcademicSession::query()->create([
        'institution_id' => $institution->id,
        'name' => '2025-2026',
        'start_date' => now()->subMonths(2),
        'end_date' => now()->addMonths(8),
        'is_current' => true,
        'status' => 'active',
    ]);

    $grade = GradeLevel::query()->create([
        'institution_id' => $institution->id,
        'name' => '1ère',
        'code' => '1E',
        'order_index' => 1,
    ]);

    $sectionA = ClassSection::query()->create([
        'institution_id' => $institution->id,
        'grade_level_id' => $grade->id,
        'name' => 'A',
        'code' => '1A',
        'is_active' => true,
    ]);

    $sectionB = ClassSection::query()->create([
        'institution_id' => $institution->id,
        'grade_level_id' => $grade->id,
        'name' => 'B',
        'code' => '1B',
        'is_active' => true,
    ]);

    $makeStudent = function (string $adm) use ($institution) {
        return Student::query()->create([
            'institution_id' => $institution->id,
            'first_name' => 'Stu',
            'last_name' => $adm,
            'admission_number' => $adm,
            'admission_date' => now()->toDateString(),
            'dob' => '2012-01-01',
            'gender' => 'male',
            'status' => 'active',
        ]);
    };

    $s1 = $makeStudent('OV-S1');
    $s2 = $makeStudent('OV-S2');
    $s3 = $makeStudent('OV-S3');
    $s4 = $makeStudent('OV-S4');

    foreach ([$s1, $s2, $s3] as $student) {
        StudentEnrollment::query()->create([
            'institution_id' => $institution->id,
            'academic_session_id' => $session->id,
            'student_id' => $student->id,
            'grade_level_id' => $grade->id,
            'class_section_id' => $sectionA->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);
    }

    StudentEnrollment::query()->create([
        'institution_id' => $institution->id,
        'academic_session_id' => $session->id,
        'student_id' => $s4->id,
        'grade_level_id' => $grade->id,
        'class_section_id' => $sectionB->id,
        'status' => 'active',
        'enrolled_at' => now(),
    ]);

    $today = Carbon::today();

    StudentAttendance::query()->create([
        'institution_id' => $institution->id,
        'academic_session_id' => $session->id,
        'class_section_id' => $sectionA->id,
        'student_id' => $s1->id,
        'attendance_date' => $today,
        'status' => 'present',
        'method' => 'manual',
    ]);
    StudentAttendance::query()->create([
        'institution_id' => $institution->id,
        'academic_session_id' => $session->id,
        'class_section_id' => $sectionA->id,
        'student_id' => $s2->id,
        'attendance_date' => $today,
        'status' => 'late',
        'method' => 'manual',
    ]);
    StudentAttendance::query()->create([
        'institution_id' => $institution->id,
        'academic_session_id' => $session->id,
        'class_section_id' => $sectionA->id,
        'student_id' => $s3->id,
        'attendance_date' => $today,
        'status' => 'absent',
        'method' => 'manual',
    ]);
    // s4 not checked in

    $staffUser = User::factory()->create(['institute_id' => $institution->id]);
    $staff = Staff::query()->create([
        'user_id' => $staffUser->id,
        'institution_id' => $institution->id,
        'employee_id' => 'EMP-OV-1',
        'designation' => 'Teacher',
        'status' => 'active',
    ]);
    StaffAttendance::query()->create([
        'institution_id' => $institution->id,
        'staff_id' => $staff->id,
        'attendance_date' => $today,
        'status' => 'present',
        'method' => 'manual',
    ]);

    $other = overviewMakeInstitution('OV200002');
    Student::query()->create([
        'institution_id' => $other->id,
        'first_name' => 'Other',
        'last_name' => 'School',
        'admission_number' => 'OV-OTHER',
        'admission_date' => now()->toDateString(),
        'dob' => '2012-01-01',
        'gender' => 'female',
        'status' => 'active',
    ]);
    // Isolation: other institution students must not affect this school's expected count.

    $overview = app(AttendanceOverviewService::class)->forInstitution($institution->id, $today);

    expect($overview['students']['expected'])->toBe(4)
        ->and($overview['students']['present'])->toBe(1)
        ->and($overview['students']['late'])->toBe(1)
        ->and($overview['students']['absent'])->toBe(1)
        ->and($overview['students']['not_checked_in'])->toBe(1)
        ->and($overview['staff']['expected'])->toBe(1)
        ->and($overview['staff']['present'])->toBe(1)
        ->and($overview['staff']['not_checked_in'])->toBe(0)
        ->and(count($overview['classes']))->toBe(2);

    $classA = collect($overview['classes'])->firstWhere('class_section_id', $sectionA->id);
    expect($classA['enrollment'])->toBe(3)
        ->and($classA['present'])->toBe(1)
        ->and($classA['late'])->toBe(1)
        ->and($classA['absent'])->toBe(1)
        ->and($classA['not_checked_in'])->toBe(0);

    $notChecked = app(AttendanceOverviewService::class)->details(
        $institution->id,
        'students',
        'not_checked_in',
        $today
    );
    expect($notChecked)->toHaveCount(1)
        ->and($notChecked[0]['id'])->toBe($s4->id);
});

it('requires authentication for attendance overview', function () {
    $response = $this->get(route('attendance.overview'));
    $response->assertRedirect();
});

it('allows school admin to open attendance overview', function () {
    $institution = overviewMakeInstitution('OV200003');
    overviewEnableAttendance($institution);
    $admin = overviewMakeAdmin($institution);

    $this->actingAs($admin)
        ->withSession(['active_institution_id' => $institution->id])
        ->get(route('attendance.overview'))
        ->assertOk();
});
