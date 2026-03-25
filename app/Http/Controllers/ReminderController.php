<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Institution;
use App\Models\StudentEnrollment;
use App\Models\Invoice;
use App\Models\ExamSchedule;
use App\Models\FeeStructure;
use App\Models\SmsTemplate;
use App\Models\InstitutionSetting;
use App\Models\ClassSection;
use App\Services\Sms\GatewayFactory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Enums\CurrencySymbol;

class ReminderController extends BaseController
{
    public function __construct()
    {
        $this->setPageTitle(__('reminders.page_title'));
    }

    public function index()
    {
        $institutionId = $this->getInstitutionId();
        
        // Get active fee structures/tranches for the dropdown
        $feeStructures = FeeStructure::where('institution_id', $institutionId)->get();
        
        // Get active class sections for the dropdown
        $classes = ClassSection::with('gradeLevel')
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->get();
        
        return view('reminders.index', compact('feeStructures', 'classes'));
    }

    /**
     * Check if the notification channel is enabled in Settings
     */
    private function checkPreferences($eventKey, $channel, $institutionId) 
    {
        $key = 'notify_' . $eventKey;
        $val = InstitutionSetting::where('institution_id', $institutionId)->where('key', $key)->value('value');
        
        if (!$val && $institutionId) {
             // Fallback to global setting if no local preference is defined
             $val = InstitutionSetting::whereNull('institution_id')->where('key', $key)->value('value');
        }
        
        $prefs = json_decode($val ?? '{}', true);
        return !empty($prefs[$channel]); 
    }

    /**
     * Send personalized Fee Reminders based on real debt.
     */
    public function sendFeeReminders(Request $request)
    {
        $request->validate([
            'fee_structure_id' => 'nullable|exists:fee_structures,id',
            'class_section_id' => 'nullable|exists:class_sections,id',
            'channel' => 'required|in:sms,whatsapp,email' // UPDATED: Included email
        ]);

        $institutionId = $this->getInstitutionId();
        $feeStructureId = $request->fee_structure_id;
        $classSectionId = $request->class_section_id; 
        $channel = $request->channel;
        $eventKey = 'fee_reminder';

        // 1. Check Notification Preferences
        if (!$this->checkPreferences($eventKey, $channel, $institutionId)) {
            return response()->json(['message' => __('reminders.messages.notifications_disabled'), 'status' => 'error'], 400);
        }

        // 2. Fetch Template
        $template = SmsTemplate::forEvent($eventKey, $institutionId)->first();
        if (!$template || !$template->is_active) {
            return response()->json(['message' => __('reminders.messages.template_not_found'), 'status' => 'error'], 400);
        }

        // 3. Find all students in this institution with active debt
        $studentsQuery = Student::with(['parent', 'invoices' => function($q) {
            $q->whereIn('status', ['unpaid', 'partial', 'overdue']);
        }])->where('institution_id', $institutionId);

        // Target a specific Class/Option Section
        if ($classSectionId) {
            $studentsQuery->whereHas('enrollments', function($q) use ($classSectionId) {
                $q->where('class_section_id', $classSectionId)
                  ->where('status', 'active');
            });
        }

        // Target a specific tranche, but keep the global debt calculation via the loaded invoices
        if ($feeStructureId) {
            $studentsQuery->whereHas('invoices.items', function($q) use ($feeStructureId) {
                $q->where('fee_structure_id', $feeStructureId);
            })->whereHas('invoices', function($q) {
                $q->whereIn('status', ['unpaid', 'partial', 'overdue']);
            });
        }

        $students = $studentsQuery->get();
        $sentCount = 0;
        $errors = 0;
        $schoolName = Institution::find($institutionId)->name ?? 'School';

        // Initialize Gateway (Only if not sending via Email)
        $gateway = null;
        if ($channel !== 'email') {
            $providerName = InstitutionSetting::get($institutionId, $channel . '_provider', 'system');
            if ($providerName === 'system') {
                $providerName = InstitutionSetting::get(null, $channel . '_provider', 'system');
            }

            try {
                $gateway = GatewayFactory::create($providerName, $institutionId);
            } catch (\Exception $e) {
                return response()->json(['message' => __('reminders.messages.gateway_config_error', ['error' => $e->getMessage()])], 500);
            }
        }

        $currency = CurrencySymbol::default();

        foreach ($students as $student) {
            // Calculate real global outstanding balance
            $totalDebt = $student->invoices->sum(function($inv) {
                return max(0, $inv->total_amount - $inv->paid_amount);
            });

            if ($totalDebt <= 0) continue;

            $parent = $student->parent;
            if (!$parent) continue;

            $contactInfo = null;
            
            // Determine Contact info based on channel
            if ($channel === 'email') {
                // Prioritize Guardian Email, fallback to Student Email
                $contactInfo = $parent->guardian_email ?? $student->email;
            } else {
                $phoneField = ($parent->primary_guardian ?? 'father') . '_phone';
                $contactInfo = $parent->$phoneField ?? $parent->father_phone ?? $parent->mother_phone ?? $parent->guardian_phone;
            }

            if (empty($contactInfo)) continue;

            $parentName = $parent->father_name ?? 'Parent';
            
            // Build Dynamic Message
            $search = ['$ParentName', '$StudentName', '$Currency', '$TotalDebt', '$SchoolName'];
            $replace = [$parentName, $student->first_name, $currency, number_format($totalDebt, 2), $schoolName];
            $message = str_replace($search, $replace, $template->body);

            try {
                if ($channel === 'email') {
                    // Send Email natively
                    Mail::raw($message, function($msg) use ($contactInfo, $schoolName) {
                        $msg->to($contactInfo)
                            ->subject("Fee Reminder - " . $schoolName);
                    });
                    $sentCount++;
                } else {
                    // Send SMS/WhatsApp
                    $response = ($channel === 'whatsapp') 
                        ? $gateway->sendWhatsApp($contactInfo, $message) 
                        : $gateway->sendSms($contactInfo, $message);

                    if ($response['success']) {
                        $sentCount++;
                    } else {
                        $errors++;
                    }
                }
            } catch (\Exception $e) {
                $errors++;
                Log::error("Fee Reminder Send Error: " . $e->getMessage());
            }
        }

        $failedMsg = $errors > 0 ? __('reminders.messages.failed_count', ['count' => $errors]) : '';
        return response()->json([
            'message' => __('reminders.messages.success_sent', ['count' => $sentCount, 'failedMsg' => $failedMsg]),
            'status' => 'success'
        ]);
    }

    /**
     * Send personalized Exam Reminders for tomorrow's schedule.
     */
    public function sendExamReminders(Request $request)
    {
        $request->validate([
            'class_section_id' => 'nullable|exists:class_sections,id', 
            'channel' => 'required|in:sms,whatsapp,email' // UPDATED: Included email
        ]);

        $institutionId = $this->getInstitutionId();
        $classSectionId = $request->class_section_id; 
        $channel = $request->channel;
        $eventKey = 'exam_reminder';
        $tomorrow = Carbon::tomorrow()->toDateString();

        // 1. Check Notification Preferences
        if (!$this->checkPreferences($eventKey, $channel, $institutionId)) {
            return response()->json(['message' => __('reminders.messages.notifications_disabled'), 'status' => 'error'], 400);
        }

        // 2. Fetch Template
        $template = SmsTemplate::forEvent($eventKey, $institutionId)->first();
        if (!$template || !$template->is_active) {
            return response()->json(['message' => __('reminders.messages.template_not_found'), 'status' => 'error'], 400);
        }

        // 3. Query the schedules
        $schedulesQuery = ExamSchedule::with(['subject', 'classSection', 'exam'])
            ->where(function($q) use ($tomorrow) {
                $q->whereDate('exam_date', $tomorrow)->orWhereDate('date', $tomorrow);
            })
            ->whereHas('exam', function($q) use ($institutionId) {
                $q->where('institution_id', $institutionId);
            });

        // Filter schedules strictly by selected class section
        if ($classSectionId) {
            $schedulesQuery->where('class_section_id', $classSectionId);
        }

        $schedules = $schedulesQuery->get();

        if ($schedules->isEmpty()) {
            return response()->json(['message' => __('reminders.messages.no_exams', ['default' => 'No exams found for the selected criteria tomorrow.']), 'status' => 'info']);
        }

        // Initialize Gateway (Only if not sending via Email)
        $gateway = null;
        if ($channel !== 'email') {
            $providerName = InstitutionSetting::get($institutionId, $channel . '_provider', 'system');
            if ($providerName === 'system') {
                $providerName = InstitutionSetting::get(null, $channel . '_provider', 'system');
            }

            try {
                $gateway = GatewayFactory::create($providerName, $institutionId);
            } catch (\Exception $e) {
                return response()->json(['message' => __('reminders.messages.gateway_config_error', ['error' => $e->getMessage()])], 500);
            }
        }

        $classSchedules = $schedules->groupBy('class_section_id');
        $sentCount = 0;
        $errors = 0;
        $schoolName = Institution::find($institutionId)->name ?? 'School';

        foreach ($classSchedules as $classId => $scheds) {
            $classSection = $scheds->first()->classSection;
            $className = $classSection ? $classSection->name : 'Class';

            // Group exam details
            $examDetails = $scheds->map(function($s) {
                $time = $s->start_time ? Carbon::parse($s->start_time)->format('h:i A') : 'TBA';
                $room = $s->room_number ?? 'TBA';
                return "{$s->subject->name}, Room: {$room}, {$time}";
            })->implode(' | ');

            $enrollments = StudentEnrollment::with('student.parent')
                ->where('class_section_id', $classId)
                ->where('status', 'active')
                ->get();

            foreach ($enrollments as $enrollment) {
                $student = $enrollment->student;
                if (!$student) continue;
                
                $parent = $student->parent;
                if (!$parent) continue;

                $contactInfo = null;

                // Determine Contact info based on channel
                if ($channel === 'email') {
                    $contactInfo = $parent->guardian_email ?? $student->email;
                } else {
                    $phoneField = ($parent->primary_guardian ?? 'father') . '_phone';
                    $contactInfo = $parent->$phoneField ?? $parent->father_phone ?? $parent->mother_phone ?? $parent->guardian_phone;
                }

                if (empty($contactInfo)) continue;

                $parentName = $parent->father_name ?? 'Parent';
                
                // Build Dynamic Message
                $search = ['$ParentName', '$StudentName', '$ClassName', '$ExamDetails', '$SchoolName'];
                $replace = [$parentName, $student->first_name, $className, $examDetails, $schoolName];
                $message = str_replace($search, $replace, $template->body);

                try {
                    if ($channel === 'email') {
                        // Send Email natively
                        Mail::raw($message, function($msg) use ($contactInfo, $schoolName) {
                            $msg->to($contactInfo)
                                ->subject("Exam Schedule Reminder - " . $schoolName);
                        });
                        $sentCount++;
                    } else {
                        // Send SMS/WhatsApp
                        $response = ($channel === 'whatsapp') 
                            ? $gateway->sendWhatsApp($contactInfo, $message) 
                            : $gateway->sendSms($contactInfo, $message);

                        if ($response['success']) {
                            $sentCount++;
                        } else {
                            $errors++;
                        }
                    }
                } catch (\Exception $e) {
                    $errors++;
                    Log::error("Exam Reminder Error: " . $e->getMessage());
                }
            }
        }

        $failedMsg = $errors > 0 ? __('reminders.messages.failed_count', ['count' => $errors]) : '';
        return response()->json([
            'message' => __('reminders.messages.success_sent', ['count' => $sentCount, 'failedMsg' => $failedMsg]),
            'status' => 'success'
        ]);
    }
}