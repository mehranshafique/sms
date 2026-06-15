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
use App\Models\PaymentProofSubmission;
use App\Models\AcademicSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Services\LmdCalculationService;
use App\Services\PaymentMethodService;
use App\Services\PaymentGateways\PaymentGatewayConfigService;
use App\Services\PaymentGateways\PaymentGatewayManager;
use App\Services\PaymentProofService;
use App\Services\CurrencyService;

class StudentPortalApiController extends Controller
{
    public function __construct(
        protected PaymentMethodService $paymentMethodService,
        protected PaymentGatewayConfigService $gatewayConfigService,
        protected PaymentGatewayManager $gatewayManager,
        protected PaymentProofService $paymentProofService,
        protected CurrencyService $currencyService
    ) {}
    /**
     * Helper to get the student securely for both Students AND Guardians
     */
    private function getStudent(Request $request = null)
    {
        $user = Auth::user();
        if (!$user) abort(401, 'Unauthenticated');

        // If the user is a Student, return their profile
        if ($user->hasRole('Student')) {
            $student = $user->student;
            if (!$student) abort(404, __('api.student_profile_missing'));
            return $student;
        }

        // If the user is a Guardian, return selected or first linked child
        if ($user->hasRole('Guardian')) {
            $parent = \App\Models\StudentParent::where('user_id', $user->id)->first();
            if (!$parent) abort(404, 'Parent profile missing.');

            $studentId = $request?->query('student_id');
            $query = \App\Models\Student::where('parent_id', $parent->id);
            $student = $studentId ? $query->where('id', $studentId)->first() : $query->first();
            if (!$student) abort(404, 'No children linked to this account.');

            return $student;
        }

        abort(403, __('api.unauthorized_access'));
    }

    /**
     * Fetch Student's Attendance History
     */
    public function getAttendance(Request $request)
    {
        try {
            $student = $this->getStudent($request);
            
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
    public function getFees(Request $request)
    {
        try {
            $student = $this->getStudent($request);

            $invoices = Invoice::where('student_id', $student->id)->latest()->get();
            $totalFees = $invoices->sum('total_amount');
            $paidFees = $invoices->sum('paid_amount');
            $outstanding = max(0, $totalFees - $paidFees);

            $institutionId = $student->institution_id;
            $onlineEnabled = $this->paymentMethodService->isOnlineEnabled($institutionId);
            $gatewayActive = $this->gatewayConfigService->isGatewayActive($institutionId);
            $manualProofEnabled = $this->gatewayConfigService->isManualProofEnabled($institutionId);

            $pendingProofIds = PaymentProofSubmission::whereIn('invoice_id', $invoices->pluck('id'))
                ->where('status', 'pending')
                ->pluck('invoice_id')
                ->flip();

            $invoiceList = $invoices->map(function ($inv) use ($onlineEnabled, $gatewayActive, $manualProofEnabled, $pendingProofIds) {
                if (empty($inv->payment_token)) {
                    $inv->update(['payment_token' => Str::random(48)]);
                }

                $balance = max(0, (float) $inv->total_amount - (float) $inv->paid_amount);
                $canPay = $onlineEnabled && $balance > 0.01 && !in_array($inv->status, ['paid', 'cancelled'], true);

                return [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'total' => number_format($inv->total_amount, 2),
                    'paid' => number_format($inv->paid_amount, 2),
                    'due' => number_format($balance, 2),
                    'due_amount' => $balance,
                    'due_date' => $inv->due_date ? Carbon::parse($inv->due_date)->format('d M, Y') : 'N/A',
                    'status' => ucfirst($inv->status),
                    'color' => $inv->status === 'paid' ? '#10B981' : '#DC2626',
                    'pay_url' => $canPay ? route('pay.show', $inv->payment_token) : null,
                    'can_pay_online' => $canPay,
                    'gateway_active' => $gatewayActive,
                    'manual_proof_enabled' => $manualProofEnabled,
                    'has_pending_proof' => $pendingProofIds->has($inv->id),
                ];
            });

            $currencyPayload = $this->currencyService->apiPayload($institutionId);

            return response()->json([
                'success' => true,
                'data' => [
                    'total_fees' => number_format($totalFees, 2),
                    'paid_fees' => number_format($paidFees, 2),
                    'outstanding' => number_format($outstanding, 2),
                    'currency' => $currencyPayload['symbol'],
                    'currency_code' => $currencyPayload['code'],
                    'currency_settings' => $currencyPayload,
                    'online_enabled' => $onlineEnabled,
                    'gateway_active' => $gatewayActive,
                    'manual_proof_enabled' => $manualProofEnabled,
                    'invoices' => $invoiceList,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Student API Fees Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Payment methods and options for a specific invoice (mobile pay / proof flow).
     */
    public function getPaymentOptions(Request $request)
    {
        try {
            $student = $this->getStudent($request);
            $request->validate(['invoice_id' => 'required|integer']);

            $invoice = Invoice::where('student_id', $student->id)
                ->where('id', $request->invoice_id)
                ->firstOrFail();

            if (empty($invoice->payment_token)) {
                $invoice->update(['payment_token' => Str::random(48)]);
            }

            $institutionId = $invoice->institution_id;
            $methods = $this->paymentMethodService->getEnabledMethods($institutionId);
            $gatewayActive = $this->gatewayConfigService->isGatewayActive($institutionId);
            $manualProofEnabled = $this->gatewayConfigService->isManualProofEnabled($institutionId);
            $balance = max(0, (float) $invoice->total_amount - (float) $invoice->paid_amount);

            $methodList = [];
            foreach ($methods as $key => $method) {
                $supportsGateway = $gatewayActive && $this->gatewayManager->supportsMethod($institutionId, $key);
                $methodList[] = [
                    'key' => $key,
                    'label' => __('payment.' . $key),
                    'is_mobile' => (bool) ($method['is_mobile'] ?? false),
                    'supports_gateway' => $supportsGateway,
                    'merchant_code' => $method['merchant_code'] ?? null,
                    'bank_name' => $method['bank_name'] ?? null,
                    'account_name' => $method['account_name'] ?? null,
                    'account_number' => $method['account_number'] ?? null,
                    'instructions' => $method['instructions'] ?? null,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'balance_due' => number_format($balance, 2, '.', ''),
                    'pay_url' => route('pay.show', $invoice->payment_token),
                    'gateway_active' => $gatewayActive,
                    'manual_proof_enabled' => $manualProofEnabled,
                    'methods' => $methodList,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Student API Payment Options Error: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Submit manual payment proof from the mobile app.
     */
    public function submitPaymentProof(Request $request)
    {
        try {
            $student = $this->getStudent($request);

            $request->validate([
                'invoice_id' => 'required|integer',
                'amount' => 'required|numeric|min:0.01',
                'method' => 'required|string|max:50',
                'payer_name' => 'required|string|max:100',
                'payer_phone' => 'required|string|max:30',
                'paid_at' => 'required|date',
                'transaction_reference' => 'required|string|max:120',
                'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
                'notes' => 'nullable|string|max:500',
            ]);

            $invoice = Invoice::where('student_id', $student->id)
                ->where('id', $request->invoice_id)
                ->firstOrFail();

            if (!$this->gatewayConfigService->isManualProofEnabled($invoice->institution_id)) {
                return response()->json(['success' => false, 'message' => __('payment_proof.disabled')], 422);
            }

            $enabledKeys = $this->paymentMethodService->enabledMethodKeys($invoice->institution_id);
            if (!in_array($request->method, $enabledKeys, true)) {
                return response()->json(['success' => false, 'message' => __('payment.method_not_enabled')], 422);
            }

            $proof = $this->paymentProofService->submit($invoice, [
                'payer_name' => $request->payer_name,
                'payer_phone' => $request->payer_phone,
                'method' => $request->method,
                'amount' => $request->amount,
                'paid_at' => $request->paid_at,
                'transaction_reference' => $request->transaction_reference,
                'notes' => $request->notes,
            ], $request->file('receipt'));

            return response()->json([
                'success' => true,
                'message' => __('payment_proof.submitted'),
                'data' => [
                    'proof_id' => $proof->id,
                    'status' => $proof->status,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Student API Payment Proof Error: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch Recent Homework/Assignments
     */
    public function getHomework(Request $request)
    {
        try {
            $student = $this->getStudent($request);
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

            $student = $this->getStudent($request);
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
    public function generateGatePass(Request $request)
    {
        try {
            $student = $this->getStudent($request);
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
     * Fetch Exam Results (With Deep Diagnostic Logging)
     */
    public function getResults(Request $request)
    {
        try {
            $student = $this->getStudent($request);
            Log::info("[API Results] Starting fetch for Student ID: {$student->id} ({$student->full_name})");

            $enrollment = $student->enrollments()->where('status', 'active')->latest()->first();

            if (!$enrollment) {
                Log::warning("[API Results] No active enrollment found for Student ID: {$student->id}. Returning empty data.");
                return response()->json(['success' => true, 'data' => []]);
            }
            
            Log::info("[API Results] Enrollment confirmed. Class ID: {$enrollment->class_section_id}, Session ID: {$enrollment->academic_session_id}");

            // --- FINANCIAL BLOCK LOGIC ---
            $isBlocked = \App\Models\InstitutionSetting::where('institution_id', $student->institution_id)
                ->where('key', 'block_reports_on_debt')
                ->value('value');

            Log::info("[API Results] Financial Block Setting is: " . ($isBlocked ? 'Enabled (1)' : 'Disabled (0)'));

            if ($isBlocked == '1') {
                $unpaid = \App\Models\Invoice::where('student_id', $student->id)
                    ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                    ->sum(\Illuminate\Support\Facades\DB::raw('total_amount - paid_amount'));

                Log::info("[API Results] Total unpaid debt calculated: {$unpaid}");

                if ($unpaid > 0) {
                    Log::warning("[API Results] Access Denied due to debt. Amount: {$unpaid}");
                    return response()->json([
                        'success' => false,
                        'is_blocked' => true,
                        'amount' => $this->currencyService->format($unpaid, $student->institution_id),
                        'currency_settings' => $this->currencyService->apiPayload($student->institution_id),
                    ]);
                }
            }

            Log::info("[API Results] Financial clearance passed. Fetching published exam records...");

            $records = ExamRecord::with(['subject', 'exam.academicSession'])
                ->where('student_id', $student->id)
                ->whereHas('exam', function($q) use ($enrollment) {
                    $q->where('academic_session_id', $enrollment->academic_session_id)
                      ->where('status', 'published');
                })
                ->latest('updated_at')
                ->get();
                
            Log::info("[API Results] Raw valid records fetched: " . $records->count());

            // Deep Diagnostic Logging if array is empty
            if ($records->isEmpty()) {
                Log::info("[API Results Diagnostics] Digging deeper to find why records are empty...");
                
                $totalMarksInDB = ExamRecord::where('student_id', $student->id)->count();
                Log::info("[API Results Diagnostics] Total marks in DB across ALL history: {$totalMarksInDB}");
                
                $unpublishedCount = ExamRecord::where('student_id', $student->id)
                    ->whereHas('exam', fn($q) => $q->where('status', '!=', 'published'))
                    ->count();
                Log::info("[API Results Diagnostics] Marks hidden because Exam status is NOT published: {$unpublishedCount}");
                
                $wrongSessionCount = ExamRecord::where('student_id', $student->id)
                    ->whereHas('exam', fn($q) => $q->where('academic_session_id', '!=', $enrollment->academic_session_id))
                    ->count();
                Log::info("[API Results Diagnostics] Marks hidden because they belong to a different Academic Session: {$wrongSessionCount}");
            }

            // Extract comprehensive metadata
            $firstRecord = $records->first();
            $exam = $firstRecord ? $firstRecord->exam : null;
            
            $totalObtained = 0;
            $totalMax = 0;

            $mappedRecords = $records->map(function($r) use (&$totalObtained, &$totalMax) {
                $max = $r->subject->total_marks ?? 100;
                $obt = $r->is_absent ? 0 : $r->marks_obtained;
                
                $totalMax += $max;
                $totalObtained += $obt;

                return [
                    'subject' => $r->subject->name ?? 'N/A',
                    'marks' => $r->marks_obtained,
                    'max_marks' => $max,
                    'is_absent' => $r->is_absent
                ];
            });

            $admNo = $student->admission_number ?? 'N/A';
            $classSec = $enrollment->classSection;
            $gradeName = $classSec->gradeLevel->name ?? '';
            $className = trim($gradeName . ' ' . ($classSec->name ?? 'N/A'));

            $payload = [
                'student_name' => $student->full_name . ' (' . $admNo . ')',
                'class_name' => $className,
                'session_name' => $enrollment->academicSession->name ?? 'N/A',
                'exam_name' => $exam->name ?? 'Term Examination',
                'exam_category' => $exam->category ? ucwords(str_replace('_', ' ', $exam->category)) : 'N/A',
                'exam_year' => $exam->academicSession->name ?? 'N/A',
                'total_obtained' => $totalObtained,
                'total_max' => $totalMax,
                'marks' => $mappedRecords
            ];

            Log::info("[API Results] Successfully mapped data. Returning to app.");
            return response()->json(['success' => true, 'data' => $payload]);
            
        } catch (\Exception $e) {
            Log::error("[API Results Exception] Error: " . $e->getMessage() . " | Line: " . $e->getLine());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * List student request history
     */
    public function getRequests(Request $request)
    {
        try {
            $student = $this->getStudent($request);

            $requests = StudentRequest::where('student_id', $student->id)
                ->latest()
                ->limit(50)
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'ticket_number' => $r->ticket_number,
                    'type' => $r->type,
                    'reason' => $r->reason,
                    'status' => $r->status,
                    'start_date' => $r->start_date ? Carbon::parse($r->start_date)->format('d M, Y') : null,
                    'created_at' => $r->created_at?->format('d M, Y'),
                ]);

            return response()->json(['success' => true, 'data' => $requests]);
        } catch (\Exception $e) {
            Log::error("Student API Requests Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getAttendanceSummary(Request $request)
    {
        try {
            $student = $this->getStudent($request);
            $period = $request->query('period', 'week');
            $enrollment = $student->enrollments()->where('status', 'active')->latest()->first();
            $service = app(\App\Services\AttendanceAnalyticsService::class);
            $table = $service->getComparativeSummaryTable(
                $student->id,
                $enrollment?->class_section_id,
                in_array($period, ['week', 'month'], true) ? $period : 'week'
            );
            return response()->json(['success' => true, 'data' => $table]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * LMD semester results / transcript data for university students.
     */
    public function getLmdTranscript(Request $request, LmdCalculationService $lmdService)
    {
        try {
            $student = $this->getStudent($request);
            $student->load(['enrollments.academicSession', 'gradeLevel']);

            $cycle = $student->gradeLevel->education_cycle ?? 'primary';
            $cycleValue = is_object($cycle) ? $cycle->value : $cycle;

            if (!in_array($cycleValue, ['university', 'lmd', 'mixed'], true)) {
                return response()->json(['success' => false, 'message' => __('api.lmd_not_applicable')], 422);
            }

            $history = [];
            foreach ($student->enrollments as $enrol) {
                $sessionId = $enrol->academic_session_id;
                $sessionName = $enrol->academicSession->name ?? (string) $sessionId;

                $sem1 = $lmdService->calculateSemesterResults($student, $sessionId, 1);
                if ($sem1) {
                    $history[$sessionName]['semester_1'] = $sem1;
                }

                $sem2 = $lmdService->calculateSemesterResults($student, $sessionId, 2);
                if ($sem2) {
                    $history[$sessionName]['semester_2'] = $sem2;
                }
            }

            if (empty($history)) {
                return response()->json(['success' => false, 'message' => __('reports.no_records_found')], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'student_name' => $student->full_name,
                    'admission_number' => $student->admission_number,
                    'institution' => $student->institution->name ?? null,
                    'sessions' => $history,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Student API LMD Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}