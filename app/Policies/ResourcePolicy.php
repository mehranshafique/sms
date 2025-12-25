<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Str;
use App\Enums\RoleEnum;

class ResourcePolicy
{
    use HandlesAuthorization;

    /**
     * Helper: Get permission name (e.g., "institution")
     */
    private function getPermissionName($modelOrClass)
    {
        // 1. If argument is missing, try to guess it from the Controller
        if (!$modelOrClass) {
            $modelOrClass = $this->guessModelFromRoute();
        }

        // 2. If we have an object, get its class name
        $className = is_object($modelOrClass) ? get_class($modelOrClass) : $modelOrClass;
        
        if (!$className) return null;

        $baseName = class_basename($className);
        
        // 3. Default: Snake case (e.g. StudentPromotion -> student_promotion)
        // This matches the database seeder logic where permissions are generated 
        // from module names via slug/snake_case.
        return Str::snake($baseName);
    }

    /**
     * Helper: Guess the model based on the current Controller
     */
    private function guessModelFromRoute()
    {
        $route = request()->route();
        
        if (!$route || !$route->controller) {
            return null;
        }

        $controllerClass = get_class($route->controller);
        $baseName = class_basename($controllerClass);
        $modelName = str_replace('Controller', '', $baseName);

        // Map Controller -> Model
        $map = [
            'Institute' => 'Institution',
            'HeadOfficers' => 'User', 
        ];

        if (isset($map[$modelName])) {
            $modelName = $map[$modelName];
        }

        $class = "App\\Models\\{$modelName}";
        return class_exists($class) ? $class : null;
    }

    /**
     * Determine if the user has the permission.
     * Super Admin bypass is handled here.
     */
    private function hasAccess(User $user, $permission)
    {
        // Enforce Enum usage for consistency
        $superAdminRole = defined('App\Enums\RoleEnum::SUPER_ADMIN') 
            ? RoleEnum::SUPER_ADMIN->value 
            : 'Super Admin';

        if ($user->hasRole($superAdminRole)) {
            return true;
        }
        return $user->hasPermissionTo($permission);
    }
    
    /**
     * Verify Institution Context Access
     * Ensures a user cannot access data from an institution they are not assigned to.
     */
    private function checkInstitutionContext(User $user, $model)
    {
        $superAdminRole = defined('App\Enums\RoleEnum::SUPER_ADMIN') 
            ? RoleEnum::SUPER_ADMIN->value 
            : 'Super Admin';

        if ($user->hasRole($superAdminRole)) {
            return true;
        }

        // If model has no institution_id, we assume it's global or public to the user's scope
        if (!method_exists($model, 'getAttribute')) {
            return true;
        }
        
        $modelInstId = $model->getAttribute('institution_id');
        
        // If model doesn't belong to an institution (e.g. System Settings), assume access if perms allow
        if (!$modelInstId) {
            return true; 
        }

        // 1. Direct Link (Staff/Student)
        if ($user->institute_id == $modelInstId) {
            return true;
        }

        // 2. Pivot Link (Head Officer)
        // Check if the user is assigned to this institution via pivot
        if ($user->institutes && $user->institutes->contains('id', $modelInstId)) {
            return true;
        }

        return false;
    }

    // --- Policy Methods ---

    public function viewAny(User $user, $modelClass = null)
    {
        $perm = $this->getPermissionName($modelClass);
        // Allow viewAny if they have either specific viewAny or generic view permission
        return $perm && ($this->hasAccess($user, "$perm.viewAny") || $this->hasAccess($user, "$perm.view"));
    }

    public function view(User $user, $model)
    {
        $perm = $this->getPermissionName($model);
        if (!$this->checkInstitutionContext($user, $model)) return false;
        
        return $perm && $this->hasAccess($user, "$perm.view");
    }

    public function create(User $user, $modelClass = null)
    {
        $perm = $this->getPermissionName($modelClass);
        // Note: For create, we can't check instance context yet, 
        // validation logic in Controller must ensure they create for their own institution.
        return $perm && $this->hasAccess($user, "$perm.create");
    }

    public function update(User $user, $model)
    {
        $perm = $this->getPermissionName($model);
        if (!$this->checkInstitutionContext($user, $model)) return false;

        return $perm && $this->hasAccess($user, "$perm.update");
    }

    public function delete(User $user, $model)
    {
        $perm = $this->getPermissionName($model);
        if (!$this->checkInstitutionContext($user, $model)) return false;

        return $perm && $this->hasAccess($user, "$perm.delete");
    }
    
    public function deleteAny(User $user, $modelClass = null)
    {
         $perm = $this->getPermissionName($modelClass);
         return $perm && ($this->hasAccess($user, "$perm.deleteAny") || $this->hasAccess($user, "$perm.delete"));
    }
    
    // Additional Resource Methods
    public function restore(User $user, $model)
    {
        $perm = $this->getPermissionName($model);
        if (!$this->checkInstitutionContext($user, $model)) return false;
        return $perm && $this->hasAccess($user, "$perm.delete"); // Re-use delete perm for restore usually
    }

    public function forceDelete(User $user, $model)
    {
        $perm = $this->getPermissionName($model);
        if (!$this->checkInstitutionContext($user, $model)) return false;
        return $perm && $this->hasAccess($user, "$perm.delete");
    }
}