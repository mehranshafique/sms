<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\InstitutionSetting;
use App\Models\Subscription;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $moduleName  The granular module key to verify (e.g., 'subjects', 'invoices')
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ?string $moduleName = null): Response
    {
        $user = Auth::user();

        // 1. Unauthenticated Bypass
        if (!$user) {
            return $next($request);
        }

        // 2. Super Admin Bypass
        if ($user->hasRole('Super Admin')) {
            return $next($request);
        }

        // 3. Resolve Active Institution Context
        $activeId = session('active_institution_id');
        $fixedId = $user->institute_id;
        
        $institutionId = $activeId ?: $fixedId;

        // FIX: Bypass module/context checks if in Global Dashboard mode
        if ($institutionId === 'global') {
            return $next($request);
        }

        if (!$institutionId) {
            abort(403, 'No institution context selected.');
        }

        // 4. Context Validation (Security Check)
        // Ensure the user actually belongs to this institution context
        $hasAccess = false;
        if ($user->institute_id == $institutionId) {
            $hasAccess = true;
        } elseif ($user->institutes()->where('institution_id', $institutionId)->exists()) {
            $hasAccess = true;
        }

        if (!$hasAccess) {
             abort(403, 'Unauthorized: You do not have access to the selected institution context.');
        }

        // 5. Granular Module Permission Check
        if (!empty($moduleName)) {
            // Fetch enabled modules from Institution Settings
            $setting = InstitutionSetting::where('institution_id', $institutionId)
                ->where('key', 'enabled_modules')
                ->first();

            $enabledModules = [];
            
            if ($setting && $setting->value) {
                $enabledModules = is_array($setting->value) 
                    ? $setting->value 
                    : json_decode($setting->value, true);
            }

            // Fallback: If settings are empty, check Subscription and seed settings
            if (empty($enabledModules)) {
                $activeSub = Subscription::with('package')
                    ->where('institution_id', $institutionId)
                    ->where('status', 'active')
                    ->where('end_date', '>=', now()->startOfDay())
                    ->latest('created_at')
                    ->first();

                if ($activeSub && $activeSub->package) {
                    $enabledModules = $activeSub->package->modules ?? [];
                    
                    // Persist for future requests to avoid heavy query overhead
                    if (!empty($enabledModules)) {
                        InstitutionSetting::updateOrCreate(
                            ['institution_id' => $institutionId, 'key' => 'enabled_modules'],
                            ['value' => json_encode($enabledModules), 'group' => 'modules']
                        );
                    }
                }
            }

            if (!is_array($enabledModules)) {
                $enabledModules = [];
            }

            // Normalize for comparison
            $moduleName = strtolower(trim($moduleName));
            $enabledModules = array_map(fn($m) => strtolower(trim($m)), $enabledModules);

            // Direct Granular Check
            // We strictly check if the requested module key exists in the enabled list.
            if (!in_array($moduleName, $enabledModules)) {
                
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['message' => 'Module access denied.'], 403);
                }

                $prettyName = ucwords(str_replace('_', ' ', $moduleName));
                abort(403, "Access Denied: The '{$prettyName}' module is not enabled for your institution.");
            }
        }

        return $next($request);
    }
}