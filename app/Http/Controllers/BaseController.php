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
            return null;
        }

        // 1. Get Allowed Institutes for this user
        // This ensures security: User can only switch to what they own/manage.
        $allowedIds = $this->getAllowedInstitutionIds($user);

        // 2. Check Session Context (Priority)
        // If the user has switched context, we respect that selection first.
        $activeId = session('active_institution_id');

        if ($activeId && in_array($activeId, $allowedIds)) {
            return $activeId;
        }

        // 3. Fallback: User's Default Institute
        // If no session is set, use their primary assignment.
        if ($user->institute_id && in_array($user->institute_id, $allowedIds)) {
            // Auto-set session for consistency
            session(['active_institution_id' => $user->institute_id]);
            return $user->institute_id;
        }

        // 4. Fallback: First Available Institute
        // If no primary set (e.g. Super Admin or Multi-school Manager without home), pick first.
        if (!empty($allowedIds)) {
            $firstId = $allowedIds[0];
            session(['active_institution_id' => $firstId]); 
            return $firstId;
        }

        return null; // No access
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