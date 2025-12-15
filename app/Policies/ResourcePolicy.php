<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Str;

class ResourcePolicy
{
    use HandlesAuthorization;

    /**
     * Helper: Get permission name (e.g., "institute")
     */
    private function getPermissionName($modelOrClass)
    {
        // 1. If argument is missing, try to guess it from the Controller
        if (!$modelOrClass) {
            $modelOrClass = $this->guessModelFromRoute();
        }

        // 2. If we have an object, get its class name
        $className = is_object($modelOrClass) ? get_class($modelOrClass) : $modelOrClass;
        
        // 3. Convert "App\Models\Institute" -> "institute"
        return Str::snake(class_basename($className));
    }

    /**
     * Helper: Guess the model based on the current Controller
     * e.g., "InstituteController" -> "App\Models\Institute"
     */
    private function guessModelFromRoute()
    {
        $route = request()->route();
        
        if (!$route || !$route->controller) {
            return null;
        }

        // Get Controller Class (e.g., App\Http\Controllers\InstituteController)
        $controllerClass = get_class($route->controller);
        
        // Get Base Name (e.g., InstituteController)
        $baseName = class_basename($controllerClass);
        
        // Remove "Controller" (e.g., Institute)
        $modelName = str_replace('Controller', '', $baseName);

        // Return full Model Class
        return "App\\Models\\{$modelName}";
    }

    // --- Updated Methods ---

    // 1. Make $modelClass optional (= null)
    public function viewAny(User $user, $modelClass = null)
    {
        return $user->hasPermissionTo($this->getPermissionName($modelClass) . '.view');
    }

    // 2. Make $modelClass optional (= null)
    public function create(User $user, $modelClass = null)
    {
        return $user->hasPermissionTo($this->getPermissionName($modelClass) . '.create');
    }

    // Standard methods (These receive the $model instance, so they are fine)
    public function view(User $user, $model)
    {
        return $user->hasPermissionTo($this->getPermissionName($model) . '.view');
    }

    public function update(User $user, $model)
    {
        return $user->hasPermissionTo($this->getPermissionName($model) . '.edit');
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