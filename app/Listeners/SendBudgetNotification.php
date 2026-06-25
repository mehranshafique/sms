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

    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function handle(BudgetDeducted $event): void
    {
        try {
            if ($event->fundRequest->status === 'approved') {
                $this->notificationService->sendBudgetConsumedNotifications($event->fundRequest);
            }
        } catch (\Exception $e) {
            Log::error('Budget consumption notification error: ' . $e->getMessage());
        }
    }
}
