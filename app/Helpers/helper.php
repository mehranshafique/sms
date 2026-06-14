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
        $user = auth()->user();
        if (!$user) {
            abort(401);
        }

        try {
            if (!$user->hasPermissionTo($permissions)) {
                abort(403);
            }
        } catch (PermissionDoesNotExist $e) {
            abort(403);
        }

        return true;
    }
}

if (!function_exists('has_ai_access')) {
    /**
     * Whether the current user can use embedded AI tools (plan + platform switch).
     */
    function has_ai_access(): bool
    {
        if (! Auth::check()) {
            return false;
        }

        try {
            return (bool) (app(\App\Services\PlanContextService::class)->snapshot()['has_ai'] ?? false);
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('brand_logo_url')) {
    /**
     * Resolve the logo shown in the UI: school logo when in institution context, else Digitex default.
     */
    function brand_logo_url(): string
    {
        $default = asset('images/digitex-logo.png');
        $user = Auth::user();

        if (! $user) {
            return $default;
        }

        $activeId = session('active_institution_id', $user->institute_id);

        if ((! $activeId || $activeId === 'global') && $user->institute_id) {
            $activeId = $user->institute_id;
        }

        if ($activeId && $activeId !== 'global') {
            $institution = \App\Models\Institution::find($activeId);
            if ($institution?->logo) {
                return asset('storage/' . $institution->logo);
            }
        }

        return $default;
    }
}

if (!function_exists('brand_logo_alt')) {
    /**
     * Accessible alt text for the resolved brand logo.
     */
    function brand_logo_alt(): string
    {
        $user = Auth::user();
        if (! $user) {
            return config('app.name', 'Digitex');
        }

        $activeId = session('active_institution_id', $user->institute_id);
        if ((! $activeId || $activeId === 'global') && $user->institute_id) {
            $activeId = $user->institute_id;
        }

        if ($activeId && $activeId !== 'global') {
            $institution = \App\Models\Institution::find($activeId);
            if ($institution?->logo) {
                return $institution->name;
            }
        }

        return config('app.name', 'Digitex');
    }
}
