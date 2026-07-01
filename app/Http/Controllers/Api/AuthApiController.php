<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Staff;
use App\Services\Mobile\MobileContextService;
use App\Services\OtpAuthService;

class AuthApiController extends Controller
{
    public function __construct(
        protected MobileContextService $contextService,
        protected OtpAuthService $otpAuth,
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

        $credential = trim($request->email);

        $user = $this->otpAuth->resolveUser($credential);

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials. Please verify your email, username, staff ID and password.'
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
        $sessionName = $context['academic_session_name'] ?? null;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $context['active_role'] ?? $roleName,
                'roles' => $context['roles'],
                'active_role' => $context['active_role'],
                'switchable_roles' => $context['switchable_roles'],
                'institution_id' => $user->institute_id,
                'institution_type' => $institutionType,
                'is_subject_wise' => $context['is_subject_wise'],
                'capabilities' => $context['capabilities'],
                'school_name' => $schoolName,
                'school_logo' => $schoolLogo,
                'academic_session_name' => $sessionName,
                'currency' => $context['currency'],
                'subscription' => $context['subscription'],
                'menu' => $context['menu'],
                'features' => $context['features'],
                'children' => $context['children'],
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

    public function requestOtp(Request $request)
    {
        $request->validate(['identifier' => 'required|string']);

        $result = $this->otpAuth->requestOtp($request->identifier, $request->user()?->institute_id);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'] ?? null,
            'user_id' => $result['user_id'] ?? null,
            'masked_phone' => $result['masked_phone'] ?? null,
        ], $result['success'] ? 200 : ($result['code'] ?? 400));
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'otp' => 'required|string|size:6',
        ]);

        $user = $this->otpAuth->verifyOtp((int) $request->user_id, $request->otp);
        if (!$user) {
            return response()->json(['success' => false, 'message' => __('auth.invalid_otp')], 401);
        }

        $token = $user->createToken('mobile_app_otp')->plainTextToken;
        $context = $this->contextService->build($user);

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $context['active_role'] ?? $user->roles->pluck('name')->first(),
                'roles' => $context['roles'],
                'active_role' => $context['active_role'],
                'institution_id' => $user->institute_id,
                'menu' => $context['menu'],
                'features' => $context['features'],
            ],
        ]);
    }
}