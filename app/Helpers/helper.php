<?php
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

if (!function_exists('institute')) {
    /**
     * Get the institute for the currently logged-in admin user
     *
     * @return \App\Models\Institute|null
     */
    function institute()
    {
        $user = Auth::user();

        // Make sure user is logged in and has admin role
        if ($user && $user->hasRole('Admin')) {
            return $user->institute; // Returns the Institute model
        }

        return null;

    }
}

if(!function_exists('isActive')){
        function isActive($routes, $class = 'mm-active')
        {
            if (is_array($routes)) {
                return in_array(request()->route()->getName(), $routes) ? $class : '';
            }

            return request()->routeIs($routes) ? $class : '';
        }

}

if(!function_exists('authorize')){
    function authorize($permissions){
        
        try {
            // Check permission if it exists
            if (!auth()->user()->hasPermissionTo($permissions)) {
                abort(403);
            }
        } catch (PermissionDoesNotExist $e) {
            // If permission does not exist, allow access
            return true;
        }

        return true;
    }
}
