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

    /**
     * Fetch optimized count of pending pickups for notification badges
     * Endpoint: GET /api/v1/pickup/count
     */
    public function getPendingCount(Request $request)
    {
        $user = Auth::user();

        $query = StudentPickup::whereIn('status', ['pending', 'scanned'])
              ->whereDate('created_at', '>=', now()->subDays(1));

        // 1. INSTITUTION FILTERING (Super Admin Bypass)
        if (!$user->hasRole('Super Admin')) {
            $query->where('institution_id', $user->institute_id);
        }

        // 2. ROLE PERMISSION: Filter by assigned class if the user is a Teacher
        if ($user->hasRole('Teacher')) {
            if ($user->staff) {
                // ALIGNED WITH WEB CONTROLLER: Use staff_id and active enrollments
                $classIds = \App\Models\ClassSection::where('staff_id', $user->staff->id)->pluck('id');
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

        $query = StudentPickup::with(['student.enrollments.classSection']);

        // 1. INSTITUTION FILTERING (Super Admin Bypass)
        if (!$user->hasRole('Super Admin')) {
            $query->where('institution_id', $user->institute_id);
        }

        // 2. STATUS FILTERING 
        $query->whereIn('status', ['pending', 'scanned'])
              ->whereDate('created_at', '>=', now()->subDays(1)); 

        // 3. ROLE PERMISSION: Filter by assigned class if the user is a Teacher
        if ($user->hasRole('Teacher')) {
            if ($user->staff) {
                // ALIGNED WITH WEB CONTROLLER: Use staff_id and active enrollments
                $classIds = \App\Models\ClassSection::where('staff_id', $user->staff->id)->pluck('id');
                $query->whereHas('student.enrollments', function($q) use ($classIds) {
                    $q->whereIn('class_section_id', $classIds)->where('status', 'active');
                });
            } else {
                $query->whereRaw('1 = 0'); // Failsafe if teacher has no staff profile
            }
        }

        $pickups = $query->latest()->get()->map(function($pickup) {
            // Prioritize the active enrollment for class display
            $activeEnrollment = $pickup->student->enrollments->where('status', 'active')->first() 
                                ?? $pickup->student->enrollments->first();
                                
            $class = $activeEnrollment->classSection->name ?? 'N/A';
            $admNo = $pickup->student->admission_number ?? 'N/A';
            
            // Task 7: Fetch parent contact
            $parent = $pickup->student->parent;
            $parentPhone = $parent->guardian_phone ?? $parent->father_phone ?? $parent->mother_phone ?? 'N/A';
            
            return [
                'pickup_id' => $pickup->id,
                // Task 1 Global: Append admission number
                'student_name' => $pickup->student->full_name . ' (' . $admNo . ')',
                'admission_number' => $admNo,
                'parent_phone' => $parentPhone,
                'class_name' => $class,
                'requested_by' => $pickup->requested_by,
                'status' => $pickup->status, 
                'scanned_by_device' => $pickup->scanned_by_device ?? 'Not Scanned (Web Request)',
                'time' => $pickup->scanned_at ? $pickup->scanned_at->format('H:i') : $pickup->created_at->format('H:i'),
            ];
        });

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
        
        $student = \App\Models\Student::findOrFail($request->student_id);
        
        // Generate 6 digit OTP
        $otp = rand(100000, 999999);
        
        // Save OTP securely in cache for 15 mins
        \Illuminate\Support\Facades\Cache::put('pickup_otp_' . $student->id, $otp, now()->addMinutes(15));
        
        // Notify via SMS
        $this->notificationService->sendOtpNotification($student, $otp);
        
        return response()->json(['success' => true, 'message' => 'pickup_otp_sent']);
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
        \Illuminate\Support\Facades\Cache::forget('pickup_otp_' . $student->id);

        return response()->json(['success' => true, 'message' => 'pickup_wait_for_teacher']);
    }

    /**
     * Manual Approval from the Teacher's Screen
     * Endpoint: POST /api/v1/pickup/approve
     */
    public function approvePickup(Request $request)
    {
        $request->validate(['pickup_id' => 'required']);
        $user = Auth::user();

        $pickup = StudentPickup::with('student.enrollments.classSection', 'student.parent')->findOrFail($request->pickup_id);

        // 4. ROLE PERMISSION: Verify Authorization before saving
        $canApprove = false;
        
        if ($user->hasRole(['Super Admin', 'School Admin', 'Head Officer'])) {
            $canApprove = true; // Admins can approve anyone
        } elseif ($user->hasRole('Teacher')) {
            if ($user->staff) {
                // ALIGNED WITH WEB CONTROLLER: Ensure student's active enrollment matches teacher's assigned class
                $classIds = \App\Models\ClassSection::where('staff_id', $user->staff->id)->pluck('id')->toArray();
                $studentEnrollment = $pickup->student->enrollments->where('status', 'active')->first();
                
                if ($studentEnrollment && in_array($studentEnrollment->class_section_id, $classIds)) {
                    $canApprove = true;
                }
            }
        }

        if (!$canApprove) {
            return response()->json(['success' => false, 'message' => 'pickup_unauthorized'], 403);
        }

        // 5. TRACKING: Record who approved the request
        $pickup->update([
            'status' => 'approved',
            'approved_by_user_id' => $user->id, 
            'approved_at' => now(),
        ]);

        // 6. Notify Parent of Departure
        $this->notifyParent($pickup->student, 'departure', now()->format('h:i A'), $pickup->institution_id, $pickup->id);

        return response()->json(['success' => true, 'message' => 'pickup_approved_success']);
    }

    /**
     * Notify Parent of Final Release
     */
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

        // NEW: Send FCM Push Notification to Parent's Mobile App
        if ($parent && isset($parent->user_id) && method_exists($this->notificationService, 'sendPushNotification')) {
            $this->notificationService->sendPushNotification(
                $parent->user_id,
                "Student Released ✅",
                "{$student->first_name} has been released safely at {$timeStr}.",
                ['pickup_id' => $pickupId, 'type' => 'pickup_approved']
            );
        }
    }
}