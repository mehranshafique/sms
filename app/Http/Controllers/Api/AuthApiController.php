<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Services\Mobile\MobileContextService;

class AuthApiController extends Controller
{
    public function __construct(
        protected MobileContextService $contextService
    ) {}

    /**
     * Mobile App Staff Login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)
                    ->orWhere('username', $request->email)
                    ->orWhere('shortcode', $request->email)
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials. Please verify your email/username and password.'
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is inactive. Please contact the administrator.'
            ], 403);
        }

        $token = $user->createToken('mobile_app_v2')->plainTextToken;

        // --- STRICT ROLE DETERMINATION (Fixes Student Dashboard Bug) ---
        $roleName = $user->roles->pluck('name')->first();
        
        if (!$roleName) {
            // Fallback checks using Eloquent Relationships
            if ($user->student) {
                $roleName = 'Student';
            } elseif (\App\Models\StudentParent::where('user_id', $user->id)->exists()) {
                $roleName = 'Guardian';
            } elseif ($user->staff) {
                $roleName = 'Staff'; 
            } else {
                $roleName = 'User';
            }
        }

        // Fetch dynamic School Name and Logo
        $institution = $user->institute;
        $schoolName = $institution ? $institution->name : 'Digitex';
        $schoolLogo = ($institution && $institution->logo) ? asset('storage/' . $institution->logo) : null;
        $institutionType = $institution
            ? (is_object($institution->type) ? $institution->type->value : $institution->type)
            : null;
        $context = $this->contextService->build($user);

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $roleName,
                'roles' => $context['roles'],
                'institution_id' => $user->institute_id,
                'institution_type' => $institutionType,
                'is_subject_wise' => $context['is_subject_wise'],
                'capabilities' => $context['capabilities'],
                'school_name' => $schoolName,
                'school_logo' => $schoolLogo,
            ]
        ], 200);
    }

    /**
     * Update FCM Token for Push Notifications
     */
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = $request->user();
        
        if ($user) {
            $user->update(['fcm_token' => $request->fcm_token]);
        }

        return response()->json([
            'success' => true,
            'message' => 'FCM Token updated successfully.'
        ]);
    }
}