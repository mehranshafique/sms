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
}