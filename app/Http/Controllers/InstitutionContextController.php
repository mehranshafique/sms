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
            
            // Check if user is Super Admin OR (Head Officer with multiple schools)
            $isSuperAdmin = $user->hasRole('Super Admin');
            $isHeadOfficerWithMultiple = $user->hasRole('Head Officer') && $user->institutes && $user->institutes->count() > 1;

            if ($isSuperAdmin || $isHeadOfficerWithMultiple) {
                // FIX: Set explicit 'global' state so middlewares know to bypass school-specific checks
                session(['active_institution_id' => 'global']);
                
                return redirect()->route('dashboard')->with('success', __('messages.switched_global_view'));
            }
            
            return redirect()->back()->with('error', __('messages.unauthorized_access'));
        }

        // 2. Validate & Switch to Specific School
        $canSwitch = false;

        // Super Admin Access (Check if ID exists in DB)
        if ($user->hasRole('Super Admin')) {
            $canSwitch = Institution::where('id', $id)->exists();
        } 
        // Head Officer Access (Check if ID exists in their assigned list)
        elseif ($user->institutes && $user->institutes->contains('id', $id)) {
            $canSwitch = true;
        }

        if ($canSwitch) {
            session(['active_institution_id' => $id]);
            return redirect()->route('dashboard')->with('success', __('messages.context_switched_success'));
        }

        return redirect()->back()->with('error', __('messages.unauthorized_access'));
    }
}