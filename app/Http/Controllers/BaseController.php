<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as LaravelController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class BaseController extends LaravelController
{
    use AuthorizesRequests;

    protected $pageTitle = null;

    public function __construct() 
    {
        // Share common data with all views (optional but helpful)
        // Note: Middleware usually handles this better for Auth checks
    }

    public function setPageTitle($title)
    {
        $this->pageTitle = $title;
        view()->share('pageTitle', $title);
    }

    /**
     * CORE SECURITY LOGIC
     * specific to Multi-Tenancy Data Isolation.
     * * Returns the ID of the institution the user is CURRENTLY working in.
     */
    protected function getInstitutionId()
    {
        $user = Auth::user();

        if (!$user) {
            return null; // Should be handled by Auth middleware
        }

        // 1. Standard Users (Teachers, Admins locked to one place)
        if ($user->institute_id) {
            return $user->institute_id;
        }

        // 2. Multi-Institute Users (Head Officers / Super Admins)
        // Check Session first
        $activeId = session('active_institution_id');

        // Validate the session ID against allowed list (Security check)
        $allowedIds = $this->getAllowedInstitutionIds($user);

        if ($activeId && in_array($activeId, $allowedIds)) {
            return $activeId;
        }

        // 3. Fallback: If no session or invalid, default to the first allowed one
        if (!empty($allowedIds)) {
            $firstId = $allowedIds[0];
            session(['active_institution_id' => $firstId]); // Auto-set session
            return $firstId;
        }

        // 4. No Access (Super Admin with no institutes created yet?)
        return null; 
    }

    /**
     * Helper: Get list of all IDs user can access.
     */
    protected function getAllowedInstitutionIds($user)
    {
        if ($user->hasRole('Super Admin')) {
            // Optimization: In a real large app, don't fetch ALL. 
            // But for dropdowns/checks, we need a list.
            return \App\Models\Institution::pluck('id')->toArray();
        }

        if ($user->institute_id) {
            return [$user->institute_id];
        }

        if ($user->institutes && $user->institutes->isNotEmpty()) {
            return $user->institutes->pluck('id')->toArray();
        }

        return [];
    }
}