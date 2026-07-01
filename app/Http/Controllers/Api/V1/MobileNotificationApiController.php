<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\InAppNotificationService;
use Illuminate\Http\Request;

class MobileNotificationApiController extends Controller
{
    public function __construct(private InAppNotificationService $notifications)
    {
    }

    public function feed(Request $request)
    {
        $userId = $request->user()->id;
        $limit = min(30, max(5, (int) $request->query('limit', 15)));

        return response()->json([
            'success' => true,
            'data' => $this->notifications->feedForUser($userId, $limit),
        ]);
    }

    public function markRead(Request $request, int $id)
    {
        $result = $this->notifications->markAsRead($id, $request->user()->id);

        return response()->json(array_merge(['success' => $result['success']], $result), $result['success'] ? 200 : 404);
    }

    public function markAllRead(Request $request)
    {
        $marked = $this->notifications->markAllAsRead($request->user()->id);

        return response()->json([
            'success' => true,
            'marked' => $marked,
            'unread_count' => $this->notifications->getUnreadCount($request->user()->id),
        ]);
    }
}
