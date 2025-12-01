<?php
use Illuminate\Support\Facades\Auth;
if (!function_exists('getLoggedInInstitute')) {
    /**
     * Get the institute for the currently logged-in admin user
     *
     * @return \App\Models\Institute|null
     */
    function getLoggedInInstitute()
    {
        $user = Auth::user();

        // Make sure user is logged in and has admin role
        if ($user && $user->hasRole('admin')) {
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
