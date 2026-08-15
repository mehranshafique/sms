<?php

namespace App\Jobs;

use App\Models\Assignment;
use App\Services\Academic\HomeworkNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotifyHomeworkPublishedJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(public int $assignmentId, public bool $force = false) {}

    public function handle(HomeworkNotificationService $notifications): void
    {
        $assignment = Assignment::find($this->assignmentId);

        if (! $assignment) {
            return;
        }

        try {
            $notifications->notifyPublished($assignment, $this->force);
        } catch (Throwable $e) {
            Log::warning('NotifyHomeworkPublishedJob failed', [
                'assignment_id' => $this->assignmentId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
