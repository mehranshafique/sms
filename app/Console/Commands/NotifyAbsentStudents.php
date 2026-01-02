<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StudentAttendance;
use App\Models\InstitutionSetting;
use App\Services\NotificationService;
use Carbon\Carbon;

class NotifyAbsentStudents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:notify-absent';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send SMS notifications to parents of students marked absent today';

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today()->format('Y-m-d');
        
        $this->info("Starting absence notifications for {$today}...");

        // 1. Fetch Absentees
        // Optimize: Group by Institution to check institution settings (like school end time) if needed
        // For simplicity, we process all active institutions.
        
        $absentees = StudentAttendance::with(['student', 'institution'])
            ->whereDate('attendance_date', $today)
            ->where('status', 'absent')
            ->get();

        $count = 0;

        foreach ($absentees as $record) {
            $student = $record->student;
            $institution = $record->institution;

            if (!$student || !$institution) continue;

            // Check if Institution has Auto-SMS enabled (Optional, good practice)
            // $autoSmsEnabled = InstitutionSetting::get($institution->id, 'auto_sms_absent', true);
            // if (!$autoSmsEnabled) continue;

            // Get Parent Phone
            $parentPhone = $student->father_phone ?? $student->mother_phone ?? $student->guardian_phone;

            if ($parentPhone) {
                // Prepare Data for SMS Template
                $smsData = [
                    'StudentName' => $student->full_name,
                    'Date' => $today,
                    'SchoolName' => $institution->name
                ];

                // Send SMS via Service (which handles Template 'student_absent')
                $this->notificationService->sendSmsEvent('student_absent', $parentPhone, $smsData, $institution->id);
                
                $count++;
            }
        }

        $this->info("Notifications sent to {$count} parents.");
    }
}