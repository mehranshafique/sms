<?php

namespace App\Jobs;

use App\Services\StudentAbsenceNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotifyAbsentParentsJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    /**
     * @param  list<int>  $studentIds
     */
    public function __construct(
        public int $institutionId,
        public array $studentIds,
        public string $date
    ) {
    }

    public function handle(StudentAbsenceNotificationService $service): void
    {
        try {
            $stats = $service->notifyForStudents($this->institutionId, $this->studentIds, $this->date);
            Log::info('Absent parent notifications finished', [
                'institution_id' => $this->institutionId,
                'date' => $this->date,
                'stats' => $stats,
            ]);
        } catch (Throwable $e) {
            Log::error('Absent parent notifications failed: '.$e->getMessage(), [
                'institution_id' => $this->institutionId,
                'date' => $this->date,
            ]);
            throw $e;
        }
    }
}
