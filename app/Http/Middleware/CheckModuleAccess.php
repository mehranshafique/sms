<?php

namespace App\Http\Middleware;

use App\Enums\RoleEnum;
use App\Services\InstitutionModuleAccessService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckModuleAccess
{
    public function __construct(private InstitutionModuleAccessService $moduleAccess)
    {
    }

    public function handle(Request $request, Closure $next, ?string $moduleName = null)
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return $next($request);
        }

        $activeId = session('active_institution_id');
        $institutionId = $activeId ?: $user->institute_id;

        if ($institutionId === 'global') {
            abort(403, 'Select an institution to access this module.');
        }

        if (!$institutionId) {
            abort(403, 'No institution context selected.');
        }

        $institutionId = (int) $institutionId;

        $belongsToInstitution = (int) $user->institute_id === $institutionId
            || $user->institutes()->where('institution_id', $institutionId)->exists();

        if (!$belongsToInstitution) {
            abort(403, 'Unauthorized: You do not have access to the selected institution context.');
        }

        if (empty($moduleName)) {
            return $next($request);
        }

        if (!$this->moduleAccess->hasActiveSubscription($institutionId)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => __('subscription.expired_or_missing')], 403);
            }

            abort(403, __('subscription.expired_or_missing'));
        }

        if (!$this->moduleAccess->isModuleEnabled($institutionId, $moduleName)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => 'Module access denied.'], 403);
            }

            $prettyName = ucwords(str_replace('_', ' ', $moduleName));
            abort(403, "Access Denied: The '{$prettyName}' module is not enabled for your institution.");
        }

        if (!$this->moduleAccess->userHasModulePermission($user, $moduleName)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => __('configuration.unauthorized_action')], 403);
            }

            abort(403, __('configuration.unauthorized_action'));
        }

        return $next($request);
    }
}
