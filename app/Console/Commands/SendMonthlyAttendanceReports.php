<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AttendanceReportDispatchService;

class SendMonthlyAttendanceReports extends Command
{
    protected $signature = 'attendance:send-monthly-reports';
    protected $description = 'Send monthly attendance summary reports to parents';

    public function handle(AttendanceReportDispatchService $service): int
    {
        $this->info('Dispatching monthly attendance reports...');
        $result = $service->dispatchAll('month');
        $this->info("Sent: {$result['sent']}, Skipped: {$result['skipped']}");
        return self::SUCCESS;
    }
}
