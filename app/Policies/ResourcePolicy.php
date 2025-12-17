<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Str;

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
        
        // 3. Handle case where string might not be a valid class path or needs normalization
        $baseName = class_basename($className);
        
        // 4. Convert "Institution" -> "institution"
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

        // Special Mapping for known discrepancies (InstituteController -> Institution)
        if ($modelName === 'Institute') {
            return "App\\Models\\Institution";
        }

        return "App\\Models\\{$modelName}";
    }

    // --- Updated Methods ---

    public function viewAny(User $user, $modelClass = null)
    {
        // Checks 'institution.view' (or .viewAny if your seeder used that)
        // Standardizing on '.view' as per your sidebar logic
        return $user->hasPermissionTo($this->getPermissionName($modelClass) . '.view');
    }

    public function create(User $user, $modelClass = null)
    {
        return $user->hasPermissionTo($this->getPermissionName($modelClass) . '.create');
    }

    public function view(User $user, $model)
    {
        return $user->hasPermissionTo($this->getPermissionName($model) . '.view');
    }

    public function update(User $user, $model)
    {
        // FIXED: Changed from '.edit' to '.update' to match Seeder
        return $user->hasPermissionTo($this->getPermissionName($model) . '.update');
    }

    public function delete(User $user, $model)
    {
        return $user->hasPermissionTo($this->getPermissionName($model) . '.delete');
    }
    
    public function deleteAny(User $user, $modelClass = null)
    {
         return $user->hasPermissionTo($this->getPermissionName($modelClass) . '.delete');
    }
}