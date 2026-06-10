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
        $this->notifications->markAsRead($id, Auth::id());

        return response()->json(['success' => true]);
    }

    public function markAllRead()
    {
        $count = $this->notifications->markAllAsRead(Auth::id());

        return response()->json(['success' => true, 'count' => $count]);
    }
}
