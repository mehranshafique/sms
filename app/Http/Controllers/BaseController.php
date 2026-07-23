<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as LaravelController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Models\Institution;

class BaseController extends LaravelController
{
    use AuthorizesRequests;

    protected $pageTitle = null;

    public function __construct() 
    {
        // Share common data if needed
    }

    public function setPageTitle($title)
    {
        $this->pageTitle = $title;
        view()->share('pageTitle', $title);
    }

    /**
     * CORE SECURITY LOGIC
     * specific to Multi-Tenancy Data Isolation.
     * Returns the ID of the institution the user is CURRENTLY working in.
     */
    protected function getInstitutionId()
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        // 1. Get Allowed Institutes for this user
        $allowedIds = $this->getAllowedInstitutionIds($user);

        // 2. Check Session Context (Priority)
        $activeId = session('active_institution_id');

        // FIX: Explicitly handle 'global' session value
        // If session says 'global', we MUST return null immediately 
        // to prevent falling back to the default institution below.
        if (($activeId === 'global' || $activeId === 0 || $activeId === '0') && $user->hasRole('Super Admin')) {
            return null; // Null indicates Global Context
        }

        // Check if session ID is valid and allowed
        if (!empty($activeId) && in_array($activeId, $allowedIds)) {
            return $activeId;
        }

        // 3. Fallback: User's Default Institute
        // Only if no valid session is set.
        if ($user->institute_id && in_array($user->institute_id, $allowedIds)) {
            session(['active_institution_id' => $user->institute_id]);
            return $user->institute_id;
        }

        // 4. Fallback: First Available Institute
        if (!empty($allowedIds)) {
            $firstId = $allowedIds[0];
            session(['active_institution_id' => $firstId]); 
            return $firstId;
        }

        return null; 
    }

    /**
     * Institutions for admin dropdowns (Super Admin: all active; others: allowed only).
     *
     * @return array<int, string>
     */
    protected function getInstitutesForSelect(): array
    {
        $user = Auth::user();
        if (! $user) {
            return [];
        }

        $query = Institution::query()
            ->where('is_active', true)
            ->orderBy('name');

        if ($user->hasRole('Super Admin')) {
            return $query->pluck('name', 'id')->all();
        }

        $allowed = $this->getAllowedInstitutionIds($user);
        if ($allowed === []) {
            return [];
        }

        return $query->whereIn('id', $allowed)->pluck('name', 'id')->all();
    }

    /**
     * Room labels from school configuration (Room 1 … Room N).
     *
     * @return array<string, string>
     */
    protected function getRoomOptions(?int $institutionId = null): array
    {
        $institutionId = $institutionId ?? $this->getInstitutionId();
        if (! $institutionId) {
            return [];
        }

        $count = max(1, (int) \App\Models\InstitutionSetting::get($institutionId, 'school_rooms_count', 10));
        $options = [];
        for ($i = 1; $i <= $count; $i++) {
            $label = __('class_section.room_option', ['number' => $i]);
            $options[$label] = $label;
        }

        return $options;
    }

    /**
     * Helper: Get list of all IDs user can access.
     */
   protected function getAllowedInstitutionIds($user)
    {
        if ($user->hasRole('Super Admin')) {
            // Super Admin can access ALL
            return Institution::pluck('id')->toArray();
        }

        $ids = [];

        // 1. Direct Assignment (Staff/Student/Primary)
        if ($user->institute_id) {
            $ids[] = $user->institute_id;
        }

        // 2. Pivot Assignment (Head Officers)
        if ($user->institutes && $user->institutes->count() > 0) {
            $ids = array_merge($ids, $user->institutes->pluck('id')->toArray());
        }

        return array_unique($ids);
    }

    /**
     * Ensure the active institution context matches a model's institution (skip when global).
     */
    protected function assertInstitutionMatch(int $modelInstitutionId): void
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && (int) $modelInstitutionId !== (int) $institutionId) {
            abort(403);
        }
    }

    /**
     * Ensure a Spatie role belongs to the current institution context (or is global for Super Admin).
     */
    protected function checkInstitution(\Spatie\Permission\Models\Role|\App\Models\Role $role): void
    {
        $user = Auth::user();
        if ($user->hasRole('Super Admin')) {
            return;
        }

        $institutionId = $this->getInstitutionId();

        if ($role->institution_id === null) {
            abort(403, __('roles.messages.cannot_edit_system'));
        }

        if ($institutionId && (int) $role->institution_id !== (int) $institutionId) {
            abort(403);
        }
    }

    /** @return list<string> */
    protected function adminRoleNames(): array
    {
        return ['Super Admin', 'School Admin', 'Head Officer'];
    }

    protected function userIsSchoolAdmin(?\App\Models\User $user = null): bool
    {
        $user = $user ?? Auth::user();

        return $user && $user->hasRole($this->adminRoleNames());
    }

    protected function authorizeAdminOrPermission(string $permission, int $status = 403): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }
        if ($this->userIsSchoolAdmin($user)) {
            return;
        }

        try {
            if ($user->can($permission)) {
                return;
            }
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
            abort($status);
        }

        abort($status);
    }

    /** @param list<string> $permissions */
    protected function authorizeAdminOrAnyPermission(array $permissions, int $status = 403): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }
        if ($this->userIsSchoolAdmin($user)) {
            return;
        }

        foreach ($permissions as $permission) {
            try {
                if ($user->can($permission)) {
                    return;
                }
            } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
                continue;
            }
        }

        abort($status);
    }

    protected function denyStudentLikeRoles(): void
    {
        $user = Auth::user();
        if ($user && $user->hasRole(['Student', 'Guardian'])) {
            abort(403);
        }
    }

    protected function wantsAjaxResponse(?\Illuminate\Http\Request $request = null): bool
    {
        $request = $request ?? request();

        return $request->ajax() || $request->wantsJson() || $request->expectsJson();
    }

    /**
     * JSON for AJAX forms (loader/toastr); flash redirect for classic posts.
     */
    protected function successResponse(string $message, ?string $redirect = null, array $extra = [])
    {
        if ($this->wantsAjaxResponse()) {
            return response()->json(array_merge([
                'success' => true,
                'message' => $message,
                'redirect' => $redirect,
            ], $extra));
        }

        $response = $redirect ? redirect()->to($redirect) : back();

        return $response->with('success', $message);
    }

    protected function errorResponse(string $message, int $status = 422, array $errors = [])
    {
        if ($this->wantsAjaxResponse()) {
            $payload = [
                'success' => false,
                'message' => $message,
            ];
            if ($errors !== []) {
                $payload['errors'] = $errors;
            }

            return response()->json($payload, $status);
        }

        $response = back()->withInput()->with('error', $message);
        if ($errors !== []) {
            $response = $response->withErrors($errors);
        }

        return $response;
    }
}