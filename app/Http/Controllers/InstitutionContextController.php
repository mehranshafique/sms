<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Institution;

class InstitutionContextController extends Controller
{
    /**
     * Switch the active institution context.
     * Params: $id can be an Integer (ID) or String ('global')
     */
    public function switch($id)
    {
        $user = Auth::user();
        
        // 1. Handle Global View (Clear Context)
        if ($id === 'global') {
            
            if ($user->hasRole('Super Admin')) {
                // FIXED: Use 'global' string instead of '0' for consistency
                session(['active_institution_id' => 'global']);
                
                return redirect()->route('dashboard')->with('success', 'Switched to Global View.');
            }
            return redirect()->back()->with('error', 'Unauthorized access.');
        }

        // 2. Validate & Switch to Specific School
        
        // Super Admin Access
        if ($user->hasRole('Super Admin')) {
            $exists = Institution::where('id', $id)->exists();
            if ($exists) {
                session(['active_institution_id' => $id]);
                return redirect()->route('dashboard')->with('success', 'Context switched successfully.');
            }
        }

        // Head Officer Access
        if ($user->institutes->contains('id', $id)) {
            session(['active_institution_id' => $id]);
            return redirect()->route('dashboard')->with('success', 'Context switched successfully.');
        }

        return redirect()->back()->with('error', 'Unauthorized access.');
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