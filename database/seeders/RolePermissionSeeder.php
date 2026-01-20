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
            'Assignments' => ['view', 'create', 'update', 'delete', 'viewAny', 'deleteAny'], // Added Assignments
            
            // Student & People
            'Students' => ['view', 'create', 'update', 'delete', 'viewAny', 'deleteAny'],
            'Enrollments' => ['view', 'create', 'update', 'delete', 'viewAny', 'deleteAny'],
            'Student Attendance' => ['view', 'create', 'update', 'delete'],
            'Student Promotion' => ['view', 'create'],
            'Student Transfers' => ['view', 'create', 'print'],
            'Staff' => ['view', 'create', 'update', 'delete', 'viewAny', 'deleteAny'],
            'Staff Attendance' => ['view', 'create', 'update', 'delete'],
            
            // Assessment
            'Examinations' => ['view', 'create', 'update', 'delete', 'viewAny', 'deleteAny'],
            'Exam Schedules' => ['view', 'create', 'update', 'delete', 'download_admit_card'],
            'Exam Marks' => ['create', 'update', 'view'],
            'Results' => ['view'], 
            'Academic Reports' => ['view'],
            
            // Finance
            'Fee Structures' => ['view', 'create', 'update', 'delete'],
            'Fee Types' => ['view', 'create', 'update', 'delete'],
            'Invoices' => ['view', 'create', 'update', 'delete'],
            'Payments' => ['view', 'create'],
            'Payrolls' => ['view', 'create', 'update', 'delete', 'viewAny'],
            'Salary Structures' => ['view', 'create', 'update', 'delete'],
            'Budgets' => ['view', 'create', 'update', 'delete', 'approve_funds', 'viewAny'],
            
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

            // Additional Modules (mentioned in premium plan)
            // 'Library' => ['view', 'create', 'update', 'delete'],
            // 'Transport' => ['view', 'create', 'update', 'delete'],
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
                
                // Exceptional cases
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

        $schoolAdminRole = Role::firstOrCreate([
            'name' => RoleEnum::SCHOOL_ADMIN->value, 
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

        // School Admin (Template)
        // Gets all permissions except super-admin specific ones
        $schoolAdminPermissions = array_filter($allPermissions, function($perm) {
            return !Str::startsWith($perm, ['institution.', 'package.', 'subscription.', 'audit_log.', 'module.']);
        });
        $schoolAdminRole->syncPermissions($schoolAdminPermissions);

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