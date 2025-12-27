<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\InstitutionSetting;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $moduleName  The module key (e.g. 'finance', 'academics')
     */
    public function handle(Request $request, Closure $next, ?string $moduleName = null): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect('/login');
        }

        // 1. Super Admin Bypass
        // Super Admins have global access to debug or manage all features
        if ($user->hasRole('Super Admin')) {
            return $next($request);
        }

        // 2. Resolve Active Institution Context
        // Fix: Prioritize Session ID over User Fixed ID to support context switching
        $activeId = session('active_institution_id');
        $fixedId = $user->institute_id;
        
        // If session is set, use it. Otherwise fall back to user's home institute.
        $institutionId = $activeId ?: $fixedId;

        if (!$institutionId) {
            // If no context, strictly block access to protected routes
            abort(403, 'No institution context selected.');
        }

        // 3. Context Validation: Is the user actually allowed to access this Institution?
        // This ensures strict separation: A Head Officer of "School A" cannot access "School B"
        // even if they manipulate the session/URL, unless they are explicitly assigned in the DB.
        $hasAccess = false;
        
        if ($user->institute_id == $institutionId) {
            // Case A: Direct Link (Staff/Student) - Locked to this ID
            $hasAccess = true;
        } elseif ($user->institutes()->where('institution_id', $institutionId)->exists()) {
            // Case B: Pivot Link (Head Officer / Branch Admin) - Assigned via pivot table
            $hasAccess = true;
        }

        if (!$hasAccess) {
             abort(403, 'Unauthorized: You do not have access to the selected institution context.');
        }

        // 4. Module Subscription Check (Licensing)
        // Only run if a specific module is requested (e.g. 'finance')
        if (!empty($moduleName)) {
            $setting = InstitutionSetting::where('institution_id', $institutionId)
                ->where('key', 'enabled_modules')
                ->first();

            // Handle potential JSON string or Array (if model casts it)
            $value = $setting ? $setting->value : null;
            
            if (is_array($value)) {
                $enabledModules = $value;
            } else {
                $enabledModules = json_decode($value ?? '[]', true);
            }

            if (!is_array($enabledModules)) {
                $enabledModules = [];
            }

            // Normalize strings to lowercase to prevent case-sensitivity issues
            $moduleName = strtolower($moduleName);
            $enabledModules = array_map('strtolower', $enabledModules);

            // Check if the requested $moduleName is in the allowed list
            if (!in_array($moduleName, $enabledModules)) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['message' => 'Module access denied by subscription plan.'], 403);
                }
                abort(403, 'Access Denied: Your institution subscription does not include the ' . ucfirst($moduleName) . ' module.');
            }
        }

        return $next($request);
    }
}