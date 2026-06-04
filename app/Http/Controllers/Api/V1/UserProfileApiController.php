<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserProfileApiController extends Controller
{
    /**
     * Get the logged-in user's profile data.
     */
    public function getProfile(Request $request)
    {
        $user = Auth::user();
        
        // Eager load relationships based on user type to provide more context
        if ($user->hasRole('Student')) {
            $user->load(['student.classSection', 'student.gradeLevel']);
        } else {
             $user->load('staff');
        }
        
        $role = $user->roles->pluck('name')->first() ?? 'User';

        $profileData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'shortcode' => $user->shortcode,
            'phone' => $user->phone,
            'address' => $user->address,
            'role' => $role,
            'profile_picture' => $user->profile_picture ? asset('storage/' . $user->profile_picture) : null,
        ];

        // Append specific data based on role
        if ($user->hasRole('Student') && $user->student) {
            $profileData['student_details'] = [
                'admission_number' => $user->student->admission_number,
                'class' => $user->student->classSection->name ?? 'N/A',
                'grade' => $user->student->gradeLevel->name ?? 'N/A',
            ];
        } elseif ($user->staff) {
            $profileData['staff_details'] = [
                'employee_id' => $user->staff->employee_id,
                'designation' => $user->staff->designation ?? 'N/A',
                'department' => $user->staff->department ?? 'N/A',
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $profileData
        ]);
    }

    /**
     * Update the logged-in user's profile.
     * Restricts which fields can be updated based on role.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        // 1. Define allowed fields. 
        // We do NOT allow updating role, institute_id, shortcode, etc., here.
        $rules = [
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            // Example: Only allow changing name/email if NOT a student (or implement custom logic)
            // For now, let's allow everyone to update their phone, address, and profile picture.
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];
        
        // Optional: Allow changing password
        if ($request->filled('current_password')) {
            $rules['current_password'] = 'required|current_password';
            $rules['new_password'] = ['required', 'confirmed', Password::min(6)]; // Customize min length
        }

        $request->validate($rules);

        // 2. Perform Updates
        if ($request->hasFile('profile_picture')) {
            if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
                Storage::disk('public')->delete($user->profile_picture);
            }
            $user->profile_picture = $request->file('profile_picture')->store('profile_pictures', 'public');
        }

        if ($request->has('phone')) {
            $user->phone = $request->phone;
            // Sync with related models if necessary
            if ($user->student) $user->student->update(['mobile_number' => $request->phone]);
            if ($user->staff) $user->staff->update(['phone' => $request->phone]);
        }

        if ($request->has('address')) {
            $user->address = $request->address;
             // Sync with related models if necessary
            if ($user->student) $user->student->update(['current_address' => $request->address]);
            if ($user->staff) $user->staff->update(['address' => $request->address]);
        }
        
        if ($request->filled('new_password')) {
            $user->password = Hash::make($request->new_password);
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                 'profile_picture' => $user->profile_picture ? asset('storage/' . $user->profile_picture) : null,
            ]
        ]);
    }
}