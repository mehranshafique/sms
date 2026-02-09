<?php

namespace App\Http\Controllers\Api\V1\Chatbot;

use App\Models\Notice; 
use Illuminate\Http\Request;

class CalendarController extends ChatbotBaseController
{
    /**
     * Get Upcoming Activities/Events.
     * Legacy Option 6
     */
    public function getUpcomingEvents(Request $request)
    {
        $institutionId = $request->user()->institute_id;
        
        // Fetch Notices/Events for the next 30 days
        $events = Notice::where('institution_id', $institutionId)
            ->where('is_published', true)
            ->where('created_at', '>=', now()->subDays(30)) // Recent notices
            ->latest()
            ->take(5)
            ->get();

        if ($events->isEmpty()) {
            return $this->sendError(__('chatbot.no_events_found'), 200);
        }

        $data = $events->map(function($event) {
            return [
                'title' => $event->title,
                'date' => $event->created_at->format('d M Y'),
                'description' => strip_tags($event->content)
            ];
        });

        return $this->sendResponse($data, __('chatbot.events_retrieved'));
    }
}