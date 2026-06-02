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
            $staffId = $user->staff->id ?? null;
            $query->whereHas('student.enrollments.classSection', function($q) use ($staffId) {
                $q->where('class_teacher_id', $staffId);
            });
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
        // If the user is NOT a Super Admin, only show their school's pickups
        if (!$user->hasRole('Super Admin')) {
            $query->where('institution_id', $user->institute_id);
        }

        // 2. STATUS FILTERING (Fetch both manual web requests and gate-scanned requests)
        // This matches the web dashboard's "Waiting Approval" view
        $query->whereIn('status', ['pending', 'scanned'])
              ->whereDate('created_at', '>=', now()->subDays(1)); // Catch late requests

        // 3. ROLE PERMISSION: Filter by assigned class if the user is a Teacher
        if ($user->hasRole('Teacher')) {
            $staffId = $user->staff->id ?? null; // Assuming User has a 'staff' relationship
            
            // A teacher only sees pickups for students currently enrolled in a class they teach
            $query->whereHas('student.enrollments.classSection', function($q) use ($staffId) {
                $q->where('class_teacher_id', $staffId);
            });
        }

        $pickups = $query->latest()->get()->map(function($pickup) {
            $class = $pickup->student->enrollments->first()->classSection->name ?? 'N/A';
            return [
                'pickup_id' => $pickup->id,
                'student_name' => $pickup->student->full_name,
                'class_name' => $class,
                'requested_by' => $pickup->requested_by,
                // Tracking data to send to the mobile app
                'status' => $pickup->status, 
                'scanned_by_device' => $pickup->scanned_by_device ?? 'Not Scanned (Web Request)',
                'time' => $pickup->scanned_at ? $pickup->scanned_at->format('H:i') : $pickup->created_at->format('H:i'),
            ];
        });

        return response()->json(['success' => true, 'data' => $pickups]);
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
            $staffId = $user->staff->id ?? null;
            // Verify this teacher is actually assigned to this specific student's class
            $studentClassTeacherId = $pickup->student->enrollments->first()->classSection->class_teacher_id ?? null;
            
            if ($staffId && $staffId === $studentClassTeacherId) {
                $canApprove = true;
            }
        }

        if (!$canApprove) {
            // Returning Translation Key for Flutter localization
            return response()->json(['success' => false, 'message' => 'pickup_unauthorized'], 403);
        }

        // 5. TRACKING: Record who approved the request
        $pickup->update([
            'status' => 'approved',
            'approved_by_user_id' => $user->id, // Identifies the Teacher/Admin who clicked release
            'approved_at' => now(),
        ]);

        // 6. Notify Parent of Departure
        $this->notifyParent($pickup->student, 'departure', now()->format('h:i A'), $pickup->institution_id, $pickup->id);

        // Returning Translation Key for Flutter localization
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