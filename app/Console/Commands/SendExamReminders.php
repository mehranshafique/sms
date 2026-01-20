<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ExamSchedule;
use App\Models\StudentEnrollment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
// Use your Notification Service here (e.g. App\Services\NotificationService)

class SendExamReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exams:notify-parents';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily exam reminders to parents for papers scheduled tomorrow';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');
        
        $this->info("Checking exams for: $tomorrow");

        // 1. Fetch schedules for tomorrow
        $schedules = ExamSchedule::with(['exam', 'subject', 'classSection', 'classSection.gradeLevel'])
            ->where('exam_date', $tomorrow)
            ->get();

        if ($schedules->isEmpty()) {
            $this->info('No exams scheduled for tomorrow.');
            return;
        }

        $count = 0;

        foreach ($schedules as $schedule) {
            $examName = $schedule->exam->name;
            $subjectName = $schedule->subject->name;
            $className = $schedule->classSection->gradeLevel->name . ' ' . $schedule->classSection->name;
            $time = $schedule->start_time->format('H:i');

            // 2. Fetch Students enrolled in this class
            $students = StudentEnrollment::with(['student', 'student.parent'])
                ->where('class_section_id', $schedule->class_section_id)
                ->where('status', 'active')
                ->get();

            foreach ($students as $enrollment) {
                $student = $enrollment->student;
                $parent = $student->parent;

                if (!$parent) continue;

                // 3. Construct Message
                $message = "Tomorrow, your child {$student->full_name} in {$className} has a {$subjectName} exam at {$time}. Exam: {$examName}. Please ensure they arrive on time.";

                // 4. Send Notification (Placeholder for actual SMS/WhatsApp logic)
                try {
                    // Example: NotificationService::send($parent->phone, $message);
                    // Or Laravel Notification: $parent->notify(new ExamReminderNotification($message));
                    
                    // For now, logging to demonstrate success
                    Log::info("Exam Reminder Sent to {$parent->phone}: $message");
                    $count++;
                } catch (\Exception $e) {
                    Log::error("Failed to send exam reminder to student ID {$student->id}: " . $e->getMessage());
                }
            }
        }

        $this->info("Sent $count reminders successfully.");
    }
}