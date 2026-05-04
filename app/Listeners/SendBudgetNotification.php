<?php

namespace App\Listeners;

use App\Events\BudgetDeducted;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendBudgetNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected $notificationService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\BudgetDeducted  $event
     * @return void
     */
    public function handle(BudgetDeducted $event)
    {
        try {
            $this->notificationService->sendFundRequestProcessedNotification(
                $event->fundRequest, 
                $event->fundRequest->institution_id
            );
        } catch (\Exception $e) {
            Log::error("Budget Notification Listener Error: " . $e->getMessage());
        }
    }
}