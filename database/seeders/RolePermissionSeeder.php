<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\Permission; 
use App\Models\User;
use App\Models\Module;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Str;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Reset cached roles and permissions (Crucial for Spatie)
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Define Independent Modules
        $modulesData = [
            // System Infrastructure
            'Institutions' => ['view', 'create', 'update', 'delete', 'viewAny', 'deleteAny'],
            'Campuses' => ['view', 'create', 'update', 'delete', 'viewAny', 'deleteAny'],
            'Head Officers' => ['view', 'create', 'update', 'delete', 'viewAny', 'deleteAny'],
            'Modules' => ['view', 'create', 'update', 'delete'],
            'Roles' => ['view', 'create', 'update', 'delete', 'viewAny'],
            'Permissions' => ['view', 'create', 'update', 'delete'],
            
            // Academic Cycle
            'Academic Sessions' => ['view', 'create', 'update', 'delete', 'viewAny', 'deleteAny'],
            'Grade Levels' => ['view', 'create', 'update', 'delete', 'viewAny', 'deleteAny'],
            'Class Sections' => ['view', 'create', 'update', 'delete', 'viewAny', 'deleteAny'],
            'Subjects' => ['view', 'create', 'update', 'delete', 'viewAny', 'deleteAny'],
            'Timetables' => ['view', 'create', 'update', 'delete', 'viewAny', 'deleteAny'],
            
            // Student & People
            'Students' => ['view', 'create', 'update', 'delete', 'viewAny', 'deleteAny'],
            'Enrollments' => ['view', 'create', 'update', 'delete', 'viewAny', 'deleteAny'],
            'Student Attendance' => ['view', 'create', 'update', 'delete'],
            'Student Promotion' => ['view', 'create'],
            'Student Transfers' => ['view', 'create', 'print'], // Added Transfer
            'Staff' => ['view', 'create', 'update', 'delete', 'viewAny', 'deleteAny'],
            'Staff Attendance' => ['view', 'create', 'update', 'delete'], // Added Staff Attendance
            
            // Assessment
            'Exams' => ['view', 'create', 'update', 'delete', 'viewAny', 'deleteAny'],
            'Exam Marks' => ['create', 'update', 'view'],
            'Result Cards' => ['view'], 
            'Academic Reports' => ['view'], // Added Bulletin/Transcript
            
            // Finance
            'Fee Structures' => ['view', 'create', 'update', 'delete'],
            'Fee Types' => ['view', 'create', 'update', 'delete'],
            'Invoices' => ['view', 'create', 'update', 'delete'],
            'Payments' => ['view', 'create'],
            'Payrolls' => ['view', 'create', 'update', 'delete', 'viewAny'], // Added Payrolls
            'Salary Structures' => ['view', 'create', 'update', 'delete'], // Added Salary Structures
            'Budgets' => ['view', 'create', 'update', 'delete', 'approve_funds'], // Added Budgets
            
            // Security & Config
            'Audit Logs' => ['view'],
            'Settings' => ['manage'],
            'Subscriptions' => ['view', 'create', 'update', 'delete'],
            'Packages' => ['view', 'create', 'update', 'delete'],
            'Sms Templates' => ['view', 'update'],
            
            // Communication & Voting
            'Communication' => ['view', 'create', 'update', 'delete'],
            'Notices' => ['view', 'create', 'update', 'delete','viewAny'],
            'Voting' => ['view', 'create', 'update', 'delete'],
            'Elections' => ['view', 'create', 'update', 'delete', 'viewAny'],
        ];

        // 3. Create Modules and Permissions dynamically
        $allPermissions = [];
        foreach ($modulesData as $moduleName => $actions) {
            $slug = Str::slug($moduleName, '_');
            
            // Sync Module in database
            $module = Module::updateOrCreate(
                ['slug' => $slug],
                ['name' => $moduleName]
            );

            foreach ($actions as $action) {
                // Formatting: singular_module.action (e.g. academic_session.view)
                $singularKey = Str::singular($slug);
                
                // Exceptional cases for collective words
                if ($slug === 'settings') $singularKey = 'setting';
                if ($slug === 'audit_logs') $singularKey = 'audit_log';
                if ($slug === 'sms_templates') $singularKey = 'sms_template';

                $permissionName = "{$singularKey}.{$action}";

                $permission = Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                ], [
                    'module_id' => $module->id
                ]);

                // Ensure link exists if record was previously created without module_id
                if (!$permission->module_id) {
                    $permission->update(['module_id' => $module->id]);
                }

                $allPermissions[] = $permission->name;
            }
        }

        // 4. Create Global Template Roles (institution_id = null)
        $superAdminRole = Role::firstOrCreate([
            'name' => RoleEnum::SUPER_ADMIN->value, 
            'guard_name' => 'web', 
            'institution_id' => null
        ]);

        $headOfficerRole = Role::firstOrCreate([
            'name' => RoleEnum::HEAD_OFFICER->value, 
            'guard_name' => 'web', 
            'institution_id' => null
        ]);

        $teacherRole = Role::firstOrCreate([
            'name' => RoleEnum::TEACHER->value, 
            'guard_name' => 'web', 
            'institution_id' => null
        ]);

        // 5. Assign Permissions
        $superAdminRole->syncPermissions($allPermissions);

        // Head Officer (Template)
        $headOfficerPermissions = array_filter($allPermissions, function($perm) {
            return !Str::startsWith($perm, ['institution.', 'package.', 'subscription.', 'audit_log.', 'module.']);
        });
        $headOfficerRole->syncPermissions($headOfficerPermissions);

        // 6. Setup Initial System User
        $user = User::updateOrCreate(
            ['email' => 'admin@digitex.com'],
            [
                'name' => 'Digitex Super Admin',
                'password' => Hash::make('password'),
                'user_type' => UserType::SUPER_ADMIN->value,
                'is_active' => true,
                'institute_id' => null, // Global
            ]
        );

        $user->assignRole($superAdminRole);

        $this->command->info('Success: Modules initialized, Permissions generated, and Scoped Roles configured.');
    }
}