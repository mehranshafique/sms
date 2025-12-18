<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\Permission; // Updated to use your Custom Permission Model
use App\Models\User;
use App\Models\Module;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Str;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Create Super Admin Role
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);

        // 2. Define Modules and Permissions
        // Format: 'Module Name' => ['permission_1', 'permission_2', ...]
        $modules = [
            'Roles' => ['view', 'create', 'update', 'delete'],
            'Permissions' => ['view', 'create', 'update', 'delete'],
            'Users' => ['view', 'create', 'update', 'delete'],
            'Modules' => ['view', 'create', 'update', 'delete'],
            
            // Core Structure
            'Institutes' => ['view', 'create', 'update', 'delete', 'viewAny', 'deleteAny'], 
            'Campuses' => ['view', 'create', 'update', 'delete', 'viewAny', 'deleteAny'],
            'Head Officers' => ['view', 'create', 'update', 'delete', 'viewAny', 'deleteAny'],
            
            // Academics
            'Academic Sessions' => ['view', 'create', 'update', 'delete', 'viewAny', 'deleteAny'],
            'Grade Levels' => ['view', 'create', 'update', 'delete', 'viewAny', 'deleteAny'],
            'Class Sections' => ['view', 'create', 'update', 'delete', 'viewAny', 'deleteAny'],
            'Subjects' => ['view', 'create', 'update', 'delete', 'viewAny', 'deleteAny'],
            'Timetables' => ['view', 'create', 'update', 'delete', 'viewAny', 'deleteAny'],
            
            // People & Students
            'Students' => ['view', 'create', 'update', 'delete', 'viewAny', 'deleteAny'],
            'Enrollments' => ['view', 'create', 'update', 'delete', 'viewAny', 'deleteAny'],
            'Student Attendance' => ['view', 'create', 'update', 'delete'], 
            'Student Promotion' => ['view', 'create'], // Special Tool
            'Staff' => ['view', 'create', 'update', 'delete', 'viewAny', 'deleteAny'],
            
            // Examinations
            'Exams' => ['view', 'create', 'update', 'delete', 'viewAny', 'deleteAny'],
            'Exam Marks' => ['create', 'update', 'view'],

            // Finance
            'Fee Structures' => ['view', 'create', 'update', 'delete'],
            'Fee Types' => ['view', 'create', 'update', 'delete'],
            'Invoices' => ['view', 'create', 'update', 'delete'],
            'Payments' => ['view', 'create'], // Added payments permission
        ];

        // 3. Create Permissions and Assign to Module
        $allPermissions = [];

        foreach ($modules as $moduleName => $actions) {
            // Create or find Module
            $moduleKey = strtolower(str_replace(' ', '_', $moduleName));
            $module = Module::updateOrCreate(['name' => $moduleName, 'slug'=>$moduleKey]);

            foreach ($actions as $action) {
                // Determine permission name convention
                // Lowercase and replace spaces with underscores for keys (e.g., "Head Officers" -> "head_officers")
                $moduleKey = strtolower(str_replace(' ', '_', $moduleName));
                
                // Force singular naming for all modules (e.g., 'roles' -> 'role', 'head_officers' -> 'head_officer')
                $singularModule = Str::singular($moduleKey);
                $permissionName = "{$singularModule}.{$action}";

                // Use the Custom App\Models\Permission to ensure module_id is saved
                $permission = Permission::firstOrCreate([
                    'name' => $permissionName, 
                    'guard_name' => 'web',
                ], [
                    'module_id' => $module->id // Only set on create
                ]);
                
                // Ensure module_id is set if it was found but null (optional safety)
                if ($permission->module_id !== $module->id) {
                    $permission->update(['module_id' => $module->id]);
                }

                $allPermissions[] = $permission;
            }
        }

        // 4. Assign All Permissions to Super Admin
        $superAdminRole->syncPermissions($allPermissions);

        // 5. Create or Update Test User
        $user = User::updateOrCreate(
            ['email' => 'test@gmail.com'],
            [
                'name' => 'Super Admin User',
                'password' => Hash::make('password'),
                'user_type' => 1,
                'is_active' => true,
            ]
        );

        // 6. Assign Role to User
        $user->assignRole($superAdminRole);

        $this->command->info('Super Admin role created, permissions assigned, and user test@gmail.com set up successfully.');
    }
}