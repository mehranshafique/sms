<?php

namespace App\Http\Controllers;

use App\Services\InAppNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InAppNotificationController extends Controller
{
    public function __construct(private InAppNotificationService $notifications)
    {
        $this->middleware('auth');
    }

    public function markRead(int $id)
    {
        $result = $this->notifications->markAsRead($id, Auth::id());

        return response()->json($result);
    }

    public function markAllRead()
    {
        $this->notifications->markAllAsRead(Auth::id());

        return response()->json([
            'success'      => true,
            'unread_count' => $this->notifications->getUnreadCount(Auth::id()),
        ]);
    }
}
