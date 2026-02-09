<?php

namespace App\Http\Controllers;

use App\Models\StudentPickup;
use App\Models\StudentEnrollment;
use App\Models\Student;
use App\Models\StudentParent;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use App\Enums\RoleEnum; // Ensure RoleEnum is imported

class PickupWebController extends BaseController
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        $this->setPageTitle(__('pickup.page_title'));
    }

    /**
     * Guard View: Scanner Interface
     */
    public function guardScanner()
    {
        return view('pickups.guard');
    }

    /**
     * Parent View: List Children & Generate QR
     */
    public function parentView()
    {
        $user = Auth::user();
        $institutionId = $this->getInstitutionId();
        
        $students = collect();
        
        // If Guardian, get linked students
        if ($user->hasRole(RoleEnum::GUARDIAN->value)) {
             // Assuming user_id is the link in parents table
             $parent = StudentParent::where('user_id', $user->id)->first();
             if ($parent) {
                 $students = Student::where('parent_id', $parent->id)
                     ->where('institution_id', $institutionId)
                     ->get();
             }
        } 
        // Fallback for Admin/Head Officer testing (Show all or a subset)
        elseif ($user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::HEAD_OFFICER->value])) {
             $students = Student::where('institution_id', $institutionId)->limit(10)->get();
        }

        return view('pickups.parent', compact('students'));
    }

    /**
     * AJAX: Generate QR for Parent
     */
    public function generateParentQr(Request $request)
    {
        $request->validate(['student_id' => 'required|exists:students,id']);
        
        $user = Auth::user();
        $institutionId = $this->getInstitutionId();
        $student = Student::findOrFail($request->student_id);

        // Security Check: Ensure user is the parent of this student
        if ($user->hasRole(RoleEnum::GUARDIAN->value)) {
            $parent = StudentParent::where('user_id', $user->id)->first();
            if (!$parent || $student->parent_id !== $parent->id) {
                return response()->json(['message' =>(__('pickup.unauthorized') ?? 'Unauthorized')], 403);
            }
        }

        // 1. Check for pickup activity in the last 24 hours
        $lastPickup = StudentPickup::where('student_id', $student->id)
            ->where('created_at', '>=', now()->subHours(24))
            ->latest()
            ->first();

        $token = null;
        $expiry = null;
        $shouldCreate = true;

        if ($lastPickup) {
            // Case A: Student was already picked up today
            if (in_array($lastPickup->status, ['scanned', 'approved'])) {
                return response()->json([
                    'success' => false,
                    'message' => __('pickup.daily_limit_reached') ?? 'Daily pickup limit reached. Student already picked up in the last 24 hours.'
                ], 422);
            }

            // Case B: Existing QR is still pending and valid -> Reuse it
            if ($lastPickup->status === 'pending' && $lastPickup->expires_at > now()) {
                $token = $lastPickup->token;
                $expiry = $lastPickup->expires_at;
                $shouldCreate = false;
            }
            
            // Case C: Existing QR is pending but expired -> Mark expired, allow new generation
            if ($lastPickup->status === 'pending' && $lastPickup->expires_at <= now()) {
                $lastPickup->update(['status' => 'expired']);
                $shouldCreate = true;
            }
        }

        if ($shouldCreate) {
            // Generate new Token
            $token = 'PKUP-' . Str::upper(Str::random(12));
            $expiry = now()->addHour(); // Valid for 1 Hour
            
            StudentPickup::create([
                'institution_id' => $institutionId,
                'student_id' => $student->id,
                'requested_by' => $user->name, // Parent Name
                'token' => $token,
                'status' => 'pending',
                'expires_at' => $expiry
            ]);
        }

        // Generate QR URL (Using QRServer API for simplicity in frontend)
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($token);

        return response()->json([
            'success' => true,
            'qr_url' => $qrUrl,
            'token' => $token,
            'expires_at' => $expiry->format('h:i A'),
            'student_name' => $student->full_name
        ]);
    }

    /**
     * Teacher View: Approval Dashboard
     */
    public function teacherView(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        $user = Auth::user();

        if ($request->ajax()) {
            $query = StudentPickup::with(['student', 'scanner'])
                ->where('institution_id', $institutionId)
                ->whereDate('created_at', today());

            // If Teacher, only show students in their classes
            if ($user->hasRole('Teacher') && $user->staff) {
                // Find classes where this staff is a teacher
                $classIds = \App\Models\ClassSection::where('staff_id', $user->staff->id)->pluck('id');
                
                $query->whereHas('student.enrollments', function($q) use ($classIds) {
                    $q->whereIn('class_section_id', $classIds)->where('status', 'active');
                });
            }

            // Order: Scanned (Waiting) > Pending > Approved > Rejected
            $query->orderByRaw("FIELD(status, 'scanned', 'pending', 'approved', 'rejected')");

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('student_name', fn($row) => $row->student->full_name)
                ->addColumn('pickup_by', fn($row) => $row->requested_by ?? 'Parent')
                ->addColumn('scanned_by', fn($row) => $row->scanner->name ?? '-')
                ->editColumn('status', function($row){
                    $badges = [
                        'pending' => 'warning', 
                        'scanned' => 'info', // Waiting approval
                        'approved' => 'success', 
                        'rejected' => 'danger',
                        'expired' => 'secondary'
                    ];
                    // Localize Status Label
                    $statusKey = 'pickup.status_' . $row->status;
                    $label = __($statusKey);
                    
                    return '<span class="badge badge-'.$badges[$row->status].'">'.$label.'</span>';
                })
                ->addColumn('action', function($row){
                    if($row->status == 'scanned') {
                        return '
                        <div class="d-flex justify-content-end">
                            <button class="btn btn-success btn-xs me-1 approve-btn" data-id="'.$row->id.'"><i class="fa fa-check"></i> '.__('pickup.btn_release').'</button>
                            <button class="btn btn-danger btn-xs reject-btn" data-id="'.$row->id.'"><i class="fa fa-times"></i> '.__('pickup.btn_reject').'</button>
                        </div>';
                    }
                    return '';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('pickups.teacher');
    }

    /**
     * Process Scan (Guard Action)
     */
    public function processScan(Request $request)
    {
        $request->validate(['qr_code' => 'required|string']);
        $user = Auth::user();

        $pickup = StudentPickup::with('student')
            ->where('token', $request->qr_code)
            ->where('institution_id', $this->getInstitutionId())
            ->first();

        if (!$pickup) return response()->json(['success' => false, 'message' => __('pickup.invalid_qr')], 404);

        if ($pickup->status !== 'pending') {
            return response()->json(['success' => false, 'message' => __('pickup.qr_used', ['status' => __('pickup.status_'.$pickup->status)])], 400);
        }

        if ($pickup->expires_at < now()) {
            $pickup->update(['status' => 'expired']);
            return response()->json(['success' => false, 'message' => __('pickup.qr_expired')], 400);
        }

        // Update to SCANNED (Waiting for Teacher)
        $pickup->update([
            'status' => 'scanned',
            'scanned_by' => $user->id,
            'scanned_at' => now()
        ]);

        // Notify Teacher
        $this->notifyTeacher($pickup);

        return response()->json([
            'success' => true,
            'message' => __('pickup.valid_scan'),
            'student' => [
                'name' => $pickup->student->full_name,
                'photo_url' => $pickup->student->student_photo ? asset('storage/'.$pickup->student->student_photo) : null,
                'pickup_by' => $pickup->requested_by
            ]
        ]);
    }

    /**
     * Teacher Action: Approve/Reject
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:approved,rejected']);
        
        $pickup = StudentPickup::findOrFail($id);
        
        if($pickup->status !== 'scanned') {
            return response()->json(['message' => __('pickup.not_waiting')], 400);
        }

        $pickup->update([
            'status' => $request->status,
            'approved_by' => Auth::id(),
            'approved_at' => now()
        ]);

        return response()->json(['message' => __('pickup.updated_success')]);
    }

    private function notifyTeacher($pickup)
    {
        $enrollment = StudentEnrollment::with(['classSection.classTeacher.user'])
            ->where('student_id', $pickup->student_id)
            ->where('status', 'active')
            ->latest()
            ->first();

        if ($enrollment && $enrollment->classSection && $enrollment->classSection->classTeacher) {
            $teacher = $enrollment->classSection->classTeacher->user;
            
            // Use localized message key
            $msg = __('chatbot.teacher_pickup_alert', [
                'student' => $pickup->student->full_name,
                'gate' => Auth::user()->name
            ]);

            $this->notificationService->performSend($teacher->phone, $msg, $pickup->institution_id, true);
        }
    }
}