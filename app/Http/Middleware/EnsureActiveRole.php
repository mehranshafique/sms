<?php

namespace App\Http\Middleware;

use App\Enums\RoleEnum;
use App\Services\ActiveRoleService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Active-persona gate (Spatie RoleMiddleware replacement for web).
 * Dual-role users only pass when their session active role matches.
 */
class EnsureActiveRole
{
    public function __construct(private ActiveRoleService $activeRoles)
    {
    }

    public function handle(Request $request, Closure $next, string $role, ?string $guard = null): Response
    {
        $authGuard = Auth::guard($guard);
        $user = $authGuard->user();

        if (! $user) {
            abort(403, __('role.active_role_required'));
        }

        $roles = array_values(array_filter(array_map('trim', explode('|', $role))));

        if ($roles === []) {
            return $next($request);
        }

        if ($this->activeRoles->userActsAs($user, $roles)) {
            return $next($request);
        }

        // Super Admin stays on platform routes that allow Super Admin even when
        // active_role is unset / defaults oddly — never while in a portal persona.
        if (
            in_array(RoleEnum::SUPER_ADMIN->value, $roles, true)
            && $user->hasRole(RoleEnum::SUPER_ADMIN->value)
            && ! $this->activeRoles->isPortalPersona($user)
        ) {
            return $next($request);
        }

        abort(403, __('role.active_role_required'));
    }
}
