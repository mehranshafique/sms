<?php

use App\Models\InstitutionSetting;
use App\Models\SmsTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<string, array{name: string, permissions: array<int, string>, prefix: string}> */
    private array $modules = [
        'student_reenrollments' => [
            'name' => 'Student Reenrollments',
            'prefix' => 'student_reenrollment',
            'permissions' => ['view', 'create', 'update'],
        ],
        'pre_enrollments' => [
            'name' => 'Pre Enrollments',
            'prefix' => 'pre_enrollment',
            'permissions' => ['view', 'create', 'update'],
        ],
    ];

    public function up(): void
    {
        $permissionNames = [];

        foreach ($this->modules as $slug => $module) {
            $moduleId = DB::table('modules')->where('slug', $slug)->value('id');

            if (! $moduleId) {
                $moduleId = DB::table('modules')->insertGetId([
                    'name' => $module['name'],
                    'slug' => $slug,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($module['permissions'] as $action) {
                $name = $module['prefix'] . '.' . $action;
                $permissionNames[] = $name;

                $exists = DB::table('permissions')
                    ->where('name', $name)
                    ->where('guard_name', 'web')
                    ->exists();

                if (! $exists) {
                    DB::table('permissions')->insert([
                        'name' => $name,
                        'guard_name' => 'web',
                        'module_id' => $moduleId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        $permIds = DB::table('permissions')->whereIn('name', $permissionNames)->pluck('id');
        $roleIds = DB::table('roles')
            ->whereIn('name', ['Super Admin', 'School Admin', 'Head Officer'])
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permIds as $permId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permId,
                    'role_id' => $roleId,
                ]);
            }
        }

        // Schools that already have an explicit module whitelist keep access to the new screens.
        InstitutionSetting::where('key', 'enabled_modules')->each(function ($setting) {
            $modules = json_decode($setting->value, true);
            if (! is_array($modules)) {
                return;
            }

            $changed = false;
            foreach (array_keys($this->modules) as $slug) {
                if (! in_array($slug, $modules, true)) {
                    $modules[] = $slug;
                    $changed = true;
                }
            }

            if ($changed) {
                $setting->update(['value' => json_encode(array_values($modules))]);
            }
        });

        foreach ($this->templates() as $template) {
            SmsTemplate::firstOrCreate(
                ['event_key' => $template['event_key'], 'institution_id' => null],
                $template
            );
        }
    }

    public function down(): void
    {
        $prefixes = array_column($this->modules, 'prefix');

        foreach ($prefixes as $prefix) {
            DB::table('permissions')->where('name', 'like', $prefix . '.%')->delete();
        }

        DB::table('modules')->whereIn('slug', array_keys($this->modules))->delete();

        SmsTemplate::whereIn('event_key', array_column($this->templates(), 'event_key'))
            ->whereNull('institution_id')
            ->delete();
    }

    /** @return array<int, array<string, mixed>> */
    private function templates(): array
    {
        return [
            [
                'event_key' => 'reenrollment_invitation',
                'name' => 'Re-enrollment Invitation',
                'body' => 'Dear $ParentName, re-enrollment for $Session is now open for $StudentName ($Class). Reply to our WhatsApp menu (option 10) or visit the school to confirm. Deposit required: $AmountRequired. Deadline: $Deadline. — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $Class, $Session, $Campaign, $AmountRequired, $AmountPaid, $Remaining, $Deadline, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'reenrollment_reminder',
                'name' => 'Re-enrollment Reminder',
                'body' => 'Reminder: re-enrollment for $StudentName ($Class) for $Session is not confirmed yet. Deadline: $Deadline. Remaining to pay: $Remaining. — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $Class, $Session, $Campaign, $AmountRequired, $AmountPaid, $Remaining, $Deadline, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'reenrollment_confirmation_received',
                'name' => 'Re-enrollment Confirmation Received',
                'body' => 'Dear $ParentName, we received your re-enrollment confirmation for $StudentName ($Class). Status: $Status. — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $Class, $Status, $Campaign, $Session, $AmountRequired, $AmountPaid, $Remaining, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'reenrollment_partial_confirmation',
                'name' => 'Re-enrollment Partial Confirmation',
                'body' => 'Dear $ParentName, confirmation for $StudentName is partial. Please pay remaining $Remaining (required $AmountRequired). — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $Class, $Status, $AmountRequired, $AmountPaid, $Remaining, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'reenrollment_confirmed',
                'name' => 'Re-enrollment Approved',
                'body' => 'Dear $ParentName, re-enrollment for $StudentName has been approved for $Session. Class: $Class. — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $Class, $Session, $Campaign, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'reenrollment_declined',
                'name' => 'Re-enrollment Declined',
                'body' => 'Dear $ParentName, re-enrollment for $StudentName was recorded as declined. Contact the school if this is a mistake. — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $Class, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'reenrollment_rejected',
                'name' => 'Re-enrollment Rejected by School',
                'body' => 'Dear $ParentName, the school could not approve re-enrollment for $StudentName. Please contact the office. — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $Class, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'pre_enrollment_received',
                'name' => 'Pre-Enrollment Confirmation',
                'body' => 'Dear $ParentName, pre-enrollment for $StudentName is registered. Temporary ID: $TemporaryId. — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $TemporaryId, $Class, $Option, $Status, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'pre_enrollment_test_invite',
                'name' => 'Admission Test Invitation',
                'body' => 'Dear $ParentName, $StudentName ($TemporaryId) is invited for the admission test on $TestDate at $TestLocation. — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $TemporaryId, $TestDate, $TestLocation, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'pre_enrollment_test_reminder',
                'name' => 'Admission Test Reminder',
                'body' => 'Reminder: $StudentName ($TemporaryId) has an admission test on $TestDate at $TestLocation. — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $TemporaryId, $TestDate, $TestLocation, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'pre_enrollment_admitted',
                'name' => 'Admission Test Passed',
                'body' => 'Congratulations! $StudentName ($TemporaryId) has been admitted. Score: $TestScore. Please visit the school to finalize enrollment. — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $TemporaryId, $TestScore, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'pre_enrollment_not_admitted',
                'name' => 'Admission Test Not Passed',
                'body' => 'Dear $ParentName, $StudentName ($TemporaryId) was not admitted after the admission test. Score: $TestScore. — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $TemporaryId, $TestScore, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'pre_enrollment_finalize_invite',
                'name' => 'Finalize Enrollment Invitation',
                'body' => 'Dear $ParentName, please come to the school to finalize the enrollment of $StudentName ($TemporaryId). — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $TemporaryId, $SchoolName',
                'is_active' => true,
            ],
            [
                'event_key' => 'pre_enrollment_finalized',
                'name' => 'Enrollment Finalized',
                'body' => 'Dear $ParentName, enrollment of $StudentName is complete. Student ID: $AdmissionNumber (was $TemporaryId). Class: $Class. — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $TemporaryId, $AdmissionNumber, $Class, $SchoolName',
                'is_active' => true,
            ],
        ];
    }
};
