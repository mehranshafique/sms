<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StudentPickup;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;

class AppPickupController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function getPendingCount(Request $request)
    {
        $user = Auth::user();

        // STRICT SCOPE: Block Students & Parents from accessing this Staff API
        if ($user->hasRole(['Student', 'Guardian'])) {
            return response()->json(['success' => false, 'count' => 0]);
        }

        $query = StudentPickup::whereIn('status', ['pending', 'scanned'])
              ->where('created_at', '>=', now()->subDays(7));

        // 1. INSTITUTION FILTERING (Super Admin Bypass)
        if (!$user->hasRole('Super Admin')) {
            $query->where('institution_id', $user->institute_id);
        }

        // 2. ROLE PERMISSION: Filter by assigned class if the user is a Teacher
        if ($user->hasRole('Teacher')) {
            if ($user->staff) {
                $staffId = $user->staff->id;
                $dayOfWeek = strtolower(now()->format('l')); // e.g., 'tuesday'
                
                // --- THE FIX: Direct relationship queries to prevent 500 crashes ---
                $homeroomIds = \App\Models\ClassSection::where('staff_id', $staffId)->pluck('id')->toArray();
                $timetableIds = \App\Models\Timetable::where('teacher_id', $staffId)->where('day_of_week', $dayOfWeek)->pluck('class_section_id')->toArray();
                $allocatedIds = \App\Models\ClassSubject::where('teacher_id', $staffId)->pluck('class_section_id')->toArray();
                
                $classIds = array_unique(array_merge($homeroomIds, $timetableIds, $allocatedIds));
                // --- END FIX ---

                $query->whereHas('student.enrollments', function($q) use ($classIds) {
                    $q->whereIn('class_section_id', $classIds)->where('status', 'active');
                });
            } else {
                $query->whereRaw('1 = 0'); // Failsafe if teacher has no staff profile
            }
        }

        return response()->json([
            'success' => true, 
            'count' => $query->count()
        ]);
    }

    /**
     * Fetch Live Pending Pickups for the App
     * Endpoint: GET /api/v1/pickup/pending
     */
    public function getPendingPickups(Request $request)
    {
        $user = Auth::user();
        
        // STRICT SCOPE: Block Students & Parents
        if ($user->hasRole(['Student', 'Guardian'])) {
            return response()->json(['success' => false, 'data' => []]);
        }
        
        \Illuminate\Support\Facades\Log::info('--- START getPendingPickups ---', ['user_id' => $user->id ?? 'guest']);

        $query = StudentPickup::with(['student.enrollments.classSection', 'student.parent']);

        // 1. INSTITUTION FILTERING (Super Admin Bypass)
        if (!$user->hasRole('Super Admin')) {
            $query->where('institution_id', $user->institute_id);
        }

        // 2. STATUS FILTERING — include pending/scanned up to 7 days so staff can identify older requests
        $query->whereIn('status', ['pending', 'scanned'])
              ->where('created_at', '>=', now()->subDays(7));

        // 3. ROLE PERMISSION: Filter by assigned class if the user is a Teacher
        if ($user->hasRole('Teacher')) {
            if ($user->staff) {
                $staffId = $user->staff->id;
                $dayOfWeek = strtolower(now()->format('l'));
                
                // --- THE FIX: Direct relationship queries to prevent 500 crashes ---
                $homeroomIds = \App\Models\ClassSection::where('staff_id', $staffId)->pluck('id')->toArray();
                $timetableIds = \App\Models\Timetable::where('teacher_id', $staffId)->where('day_of_week', $dayOfWeek)->pluck('class_section_id')->toArray();
                $allocatedIds = \App\Models\ClassSubject::where('teacher_id', $staffId)->pluck('class_section_id')->toArray();
                
                $classIds = array_unique(array_merge($homeroomIds, $timetableIds, $allocatedIds));
                
                \Illuminate\Support\Facades\Log::info("Teacher Staff ID {$staffId} assigned class IDs: ", $classIds);
                // --- END FIX ---

                $query->whereHas('student.enrollments', function($q) use ($classIds) {
                    $q->whereIn('class_section_id', $classIds)->where('status', 'active');
                });
            } else {
                \Illuminate\Support\Facades\Log::warning("Teacher user {$user->id} has no staff profile attached!");
                $query->whereRaw('1 = 0'); // Failsafe if teacher has no staff profile
            }
        }

        $pickups = $query->latest('created_at')->get()->map(function($pickup) {
            $activeEnrollment = $pickup->student->enrollments->where('status', 'active')->first() 
                                ?? $pickup->student->enrollments->first();
                                
            $class = $activeEnrollment->classSection->name ?? 'N/A';
            $admNo = $pickup->student->admission_number ?? 'N/A';
            
            $parent = $pickup->student->parent;
            $parentPhone = $parent->guardian_phone ?? $parent->father_phone ?? $parent->mother_phone ?? 'N/A';

            $createdAt = $pickup->created_at;
            $scannedAt = $pickup->scanned_at;
            $displayAt = $scannedAt ?? $createdAt;
            
            return [
                'pickup_id' => $pickup->id,
                'student_name' => $pickup->student->full_name . ' (' . $admNo . ')',
                'admission_number' => $admNo,
                'parent_phone' => $parentPhone,
                'class_name' => $class,
                'requested_by' => $pickup->requested_by,
                'status' => $pickup->status, 
                'scanned_by_device' => $pickup->scanned_by_device ?? 'Not Scanned (Web Request)',
                'time' => $displayAt ? $displayAt->format('H:i') : '--:--',
                'date' => $createdAt ? $createdAt->format('Y-m-d') : null,
                'date_label' => $createdAt ? $createdAt->format('D, d M Y') : 'N/A',
                'created_at' => $createdAt?->toIso8601String(),
                'scanned_at' => $scannedAt?->toIso8601String(),
                'datetime_label' => $displayAt ? $displayAt->format('d M Y, h:i A') : 'N/A',
                'is_today' => $createdAt ? $createdAt->isToday() : false,
            ];
        });
        
        \Illuminate\Support\Facades\Log::info("Found " . count($pickups) . " pending pickups.");

        return response()->json(['success' => true, 'data' => $pickups]);
    }

    /**
     * Task 8: Generate OTP for parents without WhatsApp/App
     * Endpoint: POST /api/v1/pickup/generate-otp
     */
    public function generateOtp(Request $request)
    {
        $request->validate(['student_id' => 'required']);
        $user = Auth::user();

        $allowedRoles = ['Guardian', 'Super Admin', 'School Admin', 'Head Officer', 'Teacher', 'Staff'];
        if (!$user->hasRole($allowedRoles)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $student = \App\Models\Student::where('id', $request->student_id)
            ->orWhere('admission_number', $request->student_id)
            ->firstOrFail();

        if (!$this->userCanAccessStudent($user, $student)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        
        // Generate 6 digit OTP
        $otp = rand(100000, 999999);
        
        // Save OTP securely in cache for 15 mins
        \Illuminate\Support\Facades\Cache::put('pickup_otp_' . $student->id, $otp, now()->addMinutes(15));
        
        // Notify via SMS
        $this->notificationService->sendOtpNotification($student, $otp);
        
        return response()->json([
            'success' => true, 
            'message' => 'pickup_otp_sent', 
            'student_id' => $student->id // Return actual ID for verification
        ]);
    }

    /**
     * Task 8: Verify OTP and place in pending queue
     * Endpoint: POST /api/v1/pickup/verify-otp
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'student_id' => 'required',
            'otp' => 'required'
        ]);
        
        $cachedOtp = \Illuminate\Support\Facades\Cache::get('pickup_otp_' . $request->student_id);
        
        if (!$cachedOtp || $cachedOtp != $request->otp) {
            return response()->json(['success' => false, 'message' => 'pickup_invalid_otp'], 400);
        }
        
        $student = \App\Models\Student::findOrFail($request->student_id);

        if (!$this->userCanAccessStudent(Auth::user(), $student)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        
        // Move to queue
        $pickup = StudentPickup::create([
            'institution_id' => $student->institution_id,
            'student_id' => $student->id,
            'requested_by' => 'SMS OTP', 
            'status' => 'scanned',
            'scanned_at' => now(),
            'scanned_by_device' => 'Manual OTP',
            'token' => 'OTP-' . \Illuminate\Support\Str::random(8),
        ]);

        // Clear cache
        \Illuminate\Support\Facades\Cache::forget('pickup_otp_' . $request->student_id);

        $admNo = $student->admission_number ?? 'N/A';

        return response()->json([
            'success' => true, 
            'message' => 'pickup_wait_for_teacher',
            'data' => [
                'student_name' => $student->full_name . ' (' . $admNo . ')',
                'color' => '#F59E0B'
            ]
        ]);
    }

    public function approvePickup(Request $request)
    {
        $request->validate(['pickup_id' => 'required']);
        $user = Auth::user();

        $pickup = StudentPickup::with('student.enrollments.classSection', 'student.parent')->findOrFail($request->pickup_id);

        // 4. ROLE PERMISSION: Verify Authorization before saving
        $canApprove = false;
        
        if ($user->hasRole(['Super Admin', 'School Admin', 'Head Officer'])) {
            if (!$this->userCanAccessPickupInstitution($user, (int) $pickup->institution_id)) {
                return response()->json(['success' => false, 'message' => 'pickup_unauthorized'], 403);
            }
            $canApprove = true;
        } elseif ($user->hasRole('Teacher')) {
            if ($user->staff) {
                $staffId = $user->staff->id;
                $dayOfWeek = strtolower(now()->format('l'));
                
                // --- THE FIX: Direct relationship queries to prevent 500 crashes ---
                $homeroomIds = \App\Models\ClassSection::where('staff_id', $staffId)->pluck('id')->toArray();
                $timetableIds = \App\Models\Timetable::where('teacher_id', $staffId)->where('day_of_week', $dayOfWeek)->pluck('class_section_id')->toArray();
                $allocatedIds = \App\Models\ClassSubject::where('teacher_id', $staffId)->pluck('class_section_id')->toArray();
                
                $classIds = array_unique(array_merge($homeroomIds, $timetableIds, $allocatedIds));
                // --- END FIX ---

                $studentEnrollment = $pickup->student->enrollments->where('status', 'active')->first();
                
                if ($studentEnrollment && in_array($studentEnrollment->class_section_id, $classIds)) {
                    $canApprove = true;
                }
            }
        }

        if (!$canApprove) {
            return response()->json(['success' => false, 'message' => 'pickup_unauthorized'], 403);
        }

        $pickup->update([
            'status' => 'approved',
            'approved_by_user_id' => $user->id, 
            'approved_at' => now(),
        ]);

        $this->notifyParent($pickup->student, 'departure', now()->format('h:i A'), $pickup->institution_id, $pickup->id);

        return response()->json(['success' => true, 'message' => 'pickup_approved_success']);
    }

    private function notifyParent($student, $action, $timeStr, $institutionId, $pickupId = null)
    {
        $parent = $student->parent;
        if (!$parent) return;

        $phoneField = ($parent->primary_guardian ?? 'father') . '_phone';
        $phone = $parent->$phoneField ?? $parent->father_phone ?? $parent->mother_phone ?? $parent->guardian_phone;

        if ($phone) {
            $eventKey = 'student_departure';
            
            $data = [
                'StudentName' => $student->first_name,
                'ParentName' => $parent->father_name ?? 'Parent',
                'Time' => $timeStr,
                'Date' => now()->format('d M Y'),
                'SchoolName' => optional($student->institution)->name ?? 'School'
            ];

            $this->notificationService->sendNotificationEvent($eventKey, $phone, $data, $institutionId, 'whatsapp');
            $this->notificationService->sendNotificationEvent($eventKey, $phone, $data, $institutionId, 'sms');
        }

        if ($parent && isset($parent->user_id) && method_exists($this->notificationService, 'sendPushNotification')) {
            $this->notificationService->sendPushNotification(
                $parent->user_id,
                "Student Released ✅",
                "{$student->first_name} has been released safely at {$timeStr}.",
                ['pickup_id' => $pickupId, 'type' => 'pickup_approved']
            );
        }
    }

    private function userCanAccessPickupInstitution($user, int $pickupInstitutionId): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        $allowedIds = array_filter(array_unique(array_merge(
            [$user->institute_id],
            $user->institutes?->pluck('id')->toArray() ?? []
        )));

        return in_array($pickupInstitutionId, $allowedIds, true);
    }

    private function userCanAccessStudent($user, \App\Models\Student $student): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if (!$this->userCanAccessPickupInstitution($user, (int) $student->institution_id)) {
            return false;
        }

        if ($user->hasRole('Guardian')) {
            $parent = \App\Models\StudentParent::where('user_id', $user->id)->first();

            return $parent && (int) $student->parent_id === (int) $parent->id;
        }

        return $user->hasRole(['School Admin', 'Head Officer', 'Teacher', 'Staff']);
    }
}