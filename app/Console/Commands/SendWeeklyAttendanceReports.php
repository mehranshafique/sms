<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AttendanceReportDispatchService;

class SendWeeklyAttendanceReports extends Command
{
    protected $signature = 'attendance:send-weekly-reports';
    protected $description = 'Send weekly attendance summary reports to parents';

    public function handle(AttendanceReportDispatchService $service): int
    {
        $this->info('Dispatching weekly attendance reports...');
        $result = $service->dispatchAll('week');
        $this->info("Sent: {$result['sent']}, Skipped: {$result['skipped']}");
        return self::SUCCESS;
    }
}
