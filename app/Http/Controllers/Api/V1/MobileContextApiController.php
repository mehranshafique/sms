<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\InAppNotificationService;
use App\Services\Mobile\MobileActiveRoleService;
use App\Services\Mobile\MobileContextService;
use Illuminate\Http\Request;

class MobileContextApiController extends Controller
{
    public function __construct(
        protected MobileContextService $contextService,
        protected MobileActiveRoleService $activeRoles,
        protected InAppNotificationService $notifications,
    ) {}

    public function context(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->contextService->build($request->user()),
        ]);
    }

    public function switchRole(Request $request)
    {
        $request->validate([
            'role' => 'required|string|max:100',
        ]);

        $user = $request->user();
        $this->activeRoles->setActiveRole($user, $request->role);

        return response()->json([
            'success' => true,
            'message' => __('role.switched_to', ['role' => $request->role]),
            'data' => $this->contextService->build($user),
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $this->activeRoles->clearActiveRole($user);
        $user->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }
}
