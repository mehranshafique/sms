<?php

namespace App\Http\Controllers;

use App\Services\InAppNotificationService;
use App\Services\SidebarMenuBadgeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InAppNotificationController extends BaseController
{
    public function __construct(private InAppNotificationService $notifications)
    {
        //
    }

    public function feed(Request $request)
    {
        $userId = Auth::id();
        $limit  = min(30, max(5, (int) $request->query('limit', 15)));
        $data   = $this->notifications->feedForUser($userId, $limit);

        return response()->json([
            'ok'            => true,
            'unread_count'  => $data['unread_count'],
            'notifications' => $data['notifications'],
            'sidebar_badges' => app(SidebarMenuBadgeService::class)->countsForUser($userId),
        ]);
    }

    public function unreadCount()
    {
        return response()->json([
            'ok'           => true,
            'unread_count' => $this->notifications->getUnreadCount(Auth::id()),
        ]);
    }

    public function markRead(int $id)
    {
        $userId = Auth::id();
        $result = $this->notifications->markAsRead($id, $userId);

        if (!$result['success']) {
            return response()->json(array_merge(['ok' => false], $result), 404);
        }

        return response()->json(array_merge(['ok' => true], $result, [
            'sidebar_badges' => app(SidebarMenuBadgeService::class)->countsForUser($userId),
        ]));
    }

    public function markAllRead()
    {
        $marked = $this->notifications->markAllAsRead(Auth::id());

        return response()->json([
            'ok'           => true,
            'success'      => true,
            'marked'       => $marked,
            'unread_count' => $this->notifications->getUnreadCount(Auth::id()),
            'sidebar_badges' => app(SidebarMenuBadgeService::class)->countsForUser(Auth::id()),
        ]);
    }
}
