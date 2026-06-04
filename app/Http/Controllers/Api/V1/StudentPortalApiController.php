<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StudentAttendance;
use App\Models\StudentPickup;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ExamRecord;
use App\Models\Assignment;
use App\Models\StudentRequest;
use App\Models\AcademicSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class StudentPortalApiController extends Controller
{
    /**
     * Helper to get the student securely for both Students AND Guardians
     */
    private function getStudent()
    {
        $user = Auth::user();
        if (!$user) abort(401, 'Unauthenticated');

        // If the user is a Student, return their profile
        if ($user->hasRole('Student')) {
            $student = $user->student;
            if (!$student) abort(404, __('api.student_profile_missing'));
            return $student;
        }

        // If the user is a Guardian, return their first linked child
        if ($user->hasRole('Guardian')) {
            $parent = \App\Models\StudentParent::where('user_id', $user->id)->first();
            if (!$parent) abort(404, 'Parent profile missing.');
            
            $student = \App\Models\Student::where('parent_id', $parent->id)->first();
            if (!$student) abort(404, 'No children linked to this account.');
            
            return $student;
        }

        abort(403, __('api.unauthorized_access'));
    }

    /**
     * Fetch Student's Attendance History
     */
    public function getAttendance()
    {
        try {
            $student = $this->getStudent();
            
            $records = StudentAttendance::with('subject')
                ->where('student_id', $student->id)
                ->latest('attendance_date')
                ->take(30)
                ->get()
                ->map(function($att) {
                    return [
                        'id' => $att->id,
                        'date' => $att->attendance_date->format('d M, Y'),
                        'subject' => $att->subject->name ?? __('api.daily_attendance', [], 'en'),
                        'status' => ucfirst($att->status),
                        'time_in' => $att->check_in ? Carbon::parse($att->check_in)->format('h:i A') : '--:--',
                        'time_out' => $att->check_out ? Carbon::parse($att->check_out)->format('h:i A') : '--:--',
                        'color' => $att->status === 'present' ? '#10B981' : ($att->status === 'late' ? '#F59E0B' : '#DC2626')
                    ];
                });

            return response()->json(['success' => true, 'data' => $records]);
        } catch (\Exception $e) {
            Log::error("Student API Attendance Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch Student's Fee Balances and Invoices
     */
    public function getFees()
    {
        try {
            $student = $this->getStudent();
            
            $invoices = Invoice::where('student_id', $student->id)->latest()->get();
            $totalFees = $invoices->sum('total_amount');
            $paidFees = $invoices->sum('paid_amount');
            $outstanding = max(0, $totalFees - $paidFees);

            $invoiceList = $invoices->map(function($inv) {
                return [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'total' => number_format($inv->total_amount, 2),
                    'paid' => number_format($inv->paid_amount, 2),
                    'due' => number_format($inv->total_amount - $inv->paid_amount, 2),
                    'due_date' => $inv->due_date ? Carbon::parse($inv->due_date)->format('d M, Y') : 'N/A',
                    'status' => ucfirst($inv->status),
                    'color' => $inv->status === 'paid' ? '#10B981' : '#DC2626'
                ];
            });

            // Safe fallback for currency to prevent Enum crashes
            $currency = config('app.currency_symbol', '$');

            return response()->json([
                'success' => true,
                'data' => [
                    'total_fees' => number_format($totalFees, 2),
                    'paid_fees' => number_format($paidFees, 2),
                    'outstanding' => number_format($outstanding, 2),
                    'currency' => $currency,
                    'invoices' => $invoiceList
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Student API Fees Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch Recent Homework/Assignments
     */
    public function getHomework()
    {
        try {
            $student = $this->getStudent();
            $enrollment = $student->enrollments()->where('status', 'active')->latest()->first();

            // Safe fallback if student is not enrolled in a class yet
            if (!$enrollment) {
                return response()->json(['success' => true, 'data' => []]);
            }

            $assignments = Assignment::with(['subject', 'teacher.user'])
                ->where('class_section_id', $enrollment->class_section_id)
                ->where('deadline', '>=', now()->subDays(7))
                ->latest('deadline')
                ->get()
                ->map(function($hw) {
                    return [
                        'id' => $hw->id,
                        'title' => $hw->title,
                        'subject' => $hw->subject->name ?? 'General',
                        'teacher' => $hw->teacher->user->name ?? 'Teacher',
                        'deadline' => $hw->deadline ? Carbon::parse($hw->deadline)->format('d M Y') : 'N/A',
                        'is_overdue' => $hw->deadline ? Carbon::parse($hw->deadline)->isPast() : false,
                        'description' => strip_tags($hw->description),
                        'file_url' => $hw->file_path ? asset('storage/' . $hw->file_path) : null
                    ];
                });

            return response()->json(['success' => true, 'data' => $assignments]);
        } catch (\Exception $e) {
            Log::error("Student API Homework Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Submit a Student Request (Absence, Late, Derogation)
     */
    public function submitRequest(Request $request)
    {
        try {
            $request->validate([
                'type' => 'required|in:absence,late,leave,other',
                'reason' => 'required|string|max:500'
            ]);

            $student = $this->getStudent();
            $enrollment = $student->enrollments()->latest()->first();
            $sessionId = $enrollment ? $enrollment->academic_session_id : null;
            $ticket = 'REQ-' . strtoupper(Str::random(8));

            StudentRequest::create([
                'institution_id' => $student->institution_id,
                'student_id' => $student->id,
                'academic_session_id' => $sessionId,
                'type' => $request->type,
                'reason' => $request->reason,
                'start_date' => now(),
                'status' => 'pending',
                'ticket_number' => $ticket,
                'created_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true, 
                'message' => "Request submitted successfully! Ticket: $ticket"
            ]);
        } catch (\Exception $e) {
            Log::error("Student API Request Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate QR Code for Gate Pass
     */
    public function generateGatePass()
    {
        try {
            $student = $this->getStudent();
            $institutionId = $student->institution_id;

            $lastPickup = StudentPickup::where('student_id', $student->id)
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->first();

            if ($lastPickup) {
                $token = $lastPickup->token;
                $expiry = Carbon::parse($lastPickup->expires_at);
            } else {
                $token = 'PKUP-' . Str::upper(Str::random(12));
                $expiry = now()->addHours(2);
                
                StudentPickup::create([
                    'institution_id' => $institutionId,
                    'student_id' => $student->id,
                    'requested_by' => Auth::user()->name, 
                    'token' => $token,
                    'status' => 'pending',
                    'expires_at' => $expiry
                ]);
            }

            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($token);

            return response()->json([
                'success' => true,
                'data' => [
                    'qr_url' => $qrUrl,
                    'token' => $token,
                    'expires_at' => $expiry->format('h:i A'),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Student API Gate Pass Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch Exam Results
     */
    public function getResults()
    {
        try {
            $student = $this->getStudent();
            $enrollment = $student->enrollments()->where('status', 'active')->latest()->first();

            if (!$enrollment) {
                return response()->json(['success' => true, 'data' => []]);
            }

            // --- FINANCIAL BLOCK LOGIC ---
            $isBlocked = \App\Models\InstitutionSetting::where('institution_id', $student->institution_id)
                ->where('key', 'block_reports_on_debt')
                ->value('value');

            if ($isBlocked == '1') {
                $unpaid = \App\Models\Invoice::where('student_id', $student->id)
                    ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                    ->sum(\Illuminate\Support\Facades\DB::raw('total_amount - paid_amount'));

                if ($unpaid > 0) {
                    $currency = config('app.currency_symbol', '$');
                    return response()->json([
                        'success' => false,
                        'is_blocked' => true,
                        'amount' => $currency . ' ' . number_format($unpaid, 2)
                    ]);
                }
            }

            $records = ExamRecord::with(['subject', 'exam.academicSession'])
                ->where('student_id', $student->id)
                ->whereHas('exam', fn($q) => $q->where('academic_session_id', $enrollment->academic_session_id)->where('status', 'published'))
                ->latest('updated_at')
                ->get()
                ->map(function($r) {
                    return [
                        'exam_name' => $r->exam->name ?? 'Exam',
                        'subject' => $r->subject->name ?? 'N/A',
                        'marks' => $r->marks_obtained,
                        'is_absent' => $r->is_absent
                    ];
                });

            return response()->json(['success' => true, 'data' => $records]);
        } catch (\Exception $e) {
            Log::error("Student API Results Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}