<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Institution;

class InstitutionContextController extends Controller
{
    /**
     * Switch the active institution context for the current session.
     */
    public function switch(Request $request, $id)
    {
        $user = Auth::user();
        
        // 1. Validate: Is the user allowed to access this institution?
        if (!$this->canAccessInstitution($user, $id)) {
            abort(403, 'Unauthorized access to this institution.');
        }

        // 2. Set Session
        session(['active_institution_id' => $id]);

        // 3. Optional: Add a flash message
        $institutionName = Institution::find($id)->name ?? 'Institution';
        
        return redirect()->back()->with('success', "Switched context to {$institutionName}");
    }

    /**
     * Helper to verify access rights.
     */
    private function canAccessInstitution($user, $institutionId)
    {
        // Super Admin access all
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // Single Institute Admin (Should generally not switch, but valid check)
        if ($user->institute_id == $institutionId) {
            return true;
        }

        // Head Officer (Check pivot table)
        if ($user->institutes->contains('id', $institutionId)) {
            return true;
        }

        return false;
    }
}