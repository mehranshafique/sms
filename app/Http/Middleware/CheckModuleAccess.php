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
        // We check the session first (for multi-school admins), then the fixed ID (for staff/students)
        $activeId = session('active_institution_id');
        $fixedId = $user->institute_id;
        
        $institutionId = $fixedId ?? $activeId;

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

            // If no setting found, assume NO modules enabled (Strict Security)
            $enabledModules = $setting ? json_decode($setting->value, true) : [];
            if (!is_array($enabledModules)) {
                $enabledModules = [];
            }

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