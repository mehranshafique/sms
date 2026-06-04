<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

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

        $request->validate([
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'password' => 'nullable|string|min:8'
        ]);

        if ($request->hasFile('profile_picture')) {
            // 1. Determine exact User ID (Admission No, Employee ID, or fallback to User ID)
            $identifier = $user->id;
            if ($user->student && $user->student->admission_number) {
                $identifier = $user->student->admission_number;
            } elseif ($user->staff && $user->staff->employee_id) {
                $identifier = $user->staff->employee_id;
            }
            
            // 2. Clean identifier to prevent file path errors and format filename
            $safeIdentifier = preg_replace('/[^A-Za-z0-9\-]/', '_', $identifier);
            $extension = $request->file('profile_picture')->getClientOriginalExtension();
            $filename = 'profile_' . $safeIdentifier . '.' . $extension;

            // 3. Prevent storage bloat: Delete old file if it exists and has a different name
            if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
                if ($user->profile_picture !== 'profile_pictures/' . $filename) {
                    Storage::disk('public')->delete($user->profile_picture);
                }
            }

            // 4. Store the file in the public disk
            $path = $request->file('profile_picture')->storeAs('profile_pictures', $filename, 'public');
            
            // 5. CRITICAL FIX: Sync the exact path across all connected tables
            $user->profile_picture = $path;
            
            if ($user->student) {
                $user->student->student_photo = $path;
                $user->student->save();
            }
        }

        // Sync Contact Info to Student table as well
        if ($request->has('phone')) {
            $user->phone = $request->phone;
            if ($user->student) {
                $user->student->mobile_number = $request->phone;
                $user->student->save();
            }
        }
        
        if ($request->has('address')) {
            $user->address = $request->address;
            if ($user->student) {
                $user->student->current_address = $request->address;
                $user->student->save();
            }
        }

        if ($request->password) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json(['success' => true, 'message' => __('api.profile_updated') ?? 'Profile Updated']);
    }
}