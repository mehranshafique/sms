<?php

namespace App\Console\Commands;

use App\Models\StudentRequest;
use App\Services\NotificationService;
use App\Services\StudentRequestNotificationDispatcher;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessDerogationCompliance extends Command
{
    protected $signature = 'derogations:process-compliance';

    protected $description = 'Send derogation reminders and transition honored/expired statuses';

    public function handle(NotificationService $notifications): int
    {
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();

        $approved = StudentRequest::with(['student.parent', 'student.invoices'])
            ->where('type', 'fee_extension')
            ->whereIn('status', ['approved', 'partially_approved'])
            ->whereNotNull('payment_deadline')
            ->get();

        foreach ($approved as $req) {
            $deadline = Carbon::parse($req->payment_deadline);
            $student = $req->student;
            if (!$student) {
                continue;
            }

            $balance = (float) $student->invoices->sum(fn ($i) => max(0, (float) $i->total_amount - (float) $i->paid_amount));

            if ($balance <= 0) {
                $req->update(['status' => 'honored']);
                $this->notify($notifications, $req, 'derogation_honored', [
                    'StudentName' => $student->full_name,
                    'TicketNumber' => $req->ticket_number,
                    'SchoolName' => $student->institution?->name ?? config('app.name'),
                ]);
                continue;
            }

            if ($deadline->isSameDay($tomorrow)) {
                $this->notify($notifications, $req, 'derogation_reminder', [
                    'StudentName' => $student->full_name,
                    'TicketNumber' => $req->ticket_number,
                    'Deadline' => localized_date($deadline, 'd M Y'),
                    'SchoolName' => $student->institution?->name ?? config('app.name'),
                ]);
            }

            if ($deadline->isSameDay($today)) {
                $this->notify($notifications, $req, 'derogation_reminder', [
                    'StudentName' => $student->full_name,
                    'TicketNumber' => $req->ticket_number,
                    'Deadline' => localized_date($deadline, 'd M Y'),
                    'SchoolName' => $student->institution?->name ?? config('app.name'),
                ]);
            }

            if ($deadline->lt($today)) {
                $req->update(['status' => 'expired']);
                $this->notify($notifications, $req, 'derogation_expired', [
                    'StudentName' => $student->full_name,
                    'TicketNumber' => $req->ticket_number,
                    'SchoolName' => $student->institution?->name ?? config('app.name'),
                ]);
            }
        }

        return self::SUCCESS;
    }

    private function notify(NotificationService $notifications, StudentRequest $req, string $eventKey, array $data): void
    {
        $parent = $req->student?->parent;
        $phone = $parent?->father_phone ?? $parent?->mother_phone ?? $parent?->guardian_phone ?? $req->student?->mobile_number;
        if (!$phone) {
            return;
        }

        try {
            if ($notifications->channelEnabled($req->institution_id, $eventKey, 'sms')) {
                $notifications->sendNotificationEvent($eventKey, $phone, $data, $req->institution_id, 'sms');
            }
            if ($notifications->channelEnabled($req->institution_id, $eventKey, 'whatsapp')) {
                $notifications->sendNotificationEvent($eventKey, $phone, $data, $req->institution_id, 'whatsapp');
            }
        } catch (\Throwable $e) {
            Log::error("Derogation compliance notify failed: {$e->getMessage()}");
        }
    }
}
