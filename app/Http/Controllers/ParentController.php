<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\StudentParent;
use App\Enums\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParentController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * AJAX: Check if a parent exists by phone number.
     * Checks both the StudentParent (parents) table and the User table.
     */
    public function check(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|min:6',
        ]);

        $phone = $request->phone;

        // 1. Check 'parents' table (StudentParent model)
        // We look for the phone number in father, mother, or guardian fields
        $studentParent = StudentParent::where(function($query) use ($phone) {
            $query->where('father_phone', $phone)
                  ->orWhere('mother_phone', $phone)
                  ->orWhere('guardian_phone', $phone);
        })->first();

        if ($studentParent) {
            // Determine the most relevant name to display
            $displayName = $studentParent->guardian_name ?? $studentParent->father_name ?? $studentParent->mother_name ?? 'Parent';
            
            // Try to be specific if we know which phone matched
            if ($studentParent->father_phone == $phone && !empty($studentParent->father_name)) {
                $displayName = $studentParent->father_name;
            } elseif ($studentParent->mother_phone == $phone && !empty($studentParent->mother_name)) {
                $displayName = $studentParent->mother_name;
            } elseif ($studentParent->guardian_phone == $phone && !empty($studentParent->guardian_name)) {
                $displayName = $studentParent->guardian_name;
            }

            return response()->json([
                'exists' => true,
                'source' => 'parent_record',
                'id' => $studentParent->id,           // ID from parents table
                'parent_id' => $studentParent->id,    // Explicit parent ID
                'user_id' => $studentParent->user_id, // Linked User ID (if any)
                'name' => $displayName,
                'email' => $studentParent->guardian_email,
                
                // Return all specific fields for auto-filling the form
                'father_name' => $studentParent->father_name,
                'father_phone' => $studentParent->father_phone,
                'mother_name' => $studentParent->mother_name,
                'mother_phone' => $studentParent->mother_phone,
                'guardian_name' => $studentParent->guardian_name,
                'guardian_email' => $studentParent->guardian_email,
                'guardian_phone' => $studentParent->guardian_phone,
            ]);
        }

        // 2. Check 'users' table (Fallback)
        $userParent = User::where('phone', $phone)
            ->where('user_type', UserType::GUARDIAN->value)
            ->first();

        if ($userParent) {
            return response()->json([
                'exists' => true,
                'source' => 'user_account',
                'id' => $userParent->id,         // ID from users table
                'parent_id' => null,             // No parent record yet
                'user_id' => $userParent->id,    // Explicit User ID
                'name' => $userParent->name,
                'email' => $userParent->email,
                // Map generic user info to guardian fields as fallback
                'guardian_name' => $userParent->name,
                'guardian_email' => $userParent->email,
                'guardian_phone' => $userParent->phone,
            ]);
        }

        return response()->json(['exists' => false]);
    }
}