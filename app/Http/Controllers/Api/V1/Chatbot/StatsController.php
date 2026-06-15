<?php

namespace App\Http\Controllers\Api\V1\Chatbot;

use App\Models\Student;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\AcademicSession;
use App\Models\ExamRecord;
use App\Models\InstitutionSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;
use App\Services\CurrencyService;

class StatsController extends ChatbotBaseController
{
    public function __construct(
        protected CurrencyService $currencyService
    ) {}

    /**
     * Dashboard for Admins
     */
    public function getInstitutionSummary(Request $request)
    {
        $institutionId = $request->user()->institute_id;
        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();

        if (!$session) return $this->sendError(__('chatbot.no_active_session'), 200);

        $totalInvoiced = Invoice::where('institution_id', $institutionId)
            ->where('academic_session_id', $session->id)
            ->sum('total_amount');

        $totalPaid = Payment::where('institution_id', $institutionId)
            ->whereHas('invoice', fn($q) => $q->where('academic_session_id', $session->id))
            ->sum('amount');

        return $this->sendResponse([
            'student_count' => Student::where('institution_id', $institutionId)->count(),
            'total_invoiced' => number_format($totalInvoiced, 2),
            'total_collected' => number_format($totalPaid, 2),
            'outstanding' => number_format($totalInvoiced - $totalPaid, 2),
            'currency' => $this->currencyService->apiPayload($institutionId),
        ], __('chatbot.summary_retrieved'));
    }

    public function getStudentBalance(Request $request)
    {
        $institutionId = $request->user()->institute_id;
        
        $student = Student::where('institution_id', $institutionId)
            ->where(function($q) use ($request) {
                $q->where('id', $request->student_id)->orWhere('admission_number', $request->student_id);
            })->first();

        if (!$student) return $this->sendError(__('chatbot.student_not_found'), 404);

        $totalInvoiced = Invoice::where('student_id', $student->id)->sum('total_amount');
        $totalPaid = Payment::whereHas('invoice', fn($q) => $q->where('student_id', $student->id))->sum('amount');

        return $this->sendResponse([
            'student_name' => $student->full_name,
            'total_fees' => number_format($totalInvoiced, 2),
            'paid' => number_format($totalPaid, 2),
            'balance' => number_format($totalInvoiced - $totalPaid, 2),
            'currency' => $this->currencyService->apiPayload($institutionId),
        ], __('chatbot.balance_retrieved'));
    }

    public function getStudentResult(Request $request)
    {
        $institutionId = $request->user()->institute_id;
        
        $student = Student::where('institution_id', $institutionId)
            ->where(function($q) use ($request) {
                $q->where('id', $request->student_id)->orWhere('admission_number', $request->student_id);
            })->first();

        if (!$student) return $this->sendError(__('chatbot.student_not_found'), 404);

        // --- 1. FINANCIAL RESTRICTION CHECK ---
        $isBlocked = InstitutionSetting::where('institution_id', $institutionId)
                        ->where('key', 'block_reports_on_debt')
                        ->value('value');
                        
        if ($isBlocked == '1') {
            $unpaid = Invoice::where('student_id', $student->id)
                ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                ->sum(DB::raw('total_amount - paid_amount'));
                
            if ($unpaid > 0) {
                $formattedDebt = $this->currencyService->format($unpaid, $institutionId);

                return $this->sendError(__('chatbot.financial_restriction_msg', ['amount' => $formattedDebt]), 200);
            }
        }

        // --- 2. SESSION & EMPTY BULLETIN CHECK ---
        $currentSession = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();
        if (!$currentSession) return $this->sendError(__('chatbot.no_session'), 200);

        $hasMarks = ExamRecord::where('student_id', $student->id)
            ->whereHas('exam', fn($q) => $q->where('academic_session_id', $currentSession->id))
            ->exists();

        if (!$hasMarks) return $this->sendError(__('chatbot.no_results_found'), 200);

        // --- 3. URL GENERATION ---
        // Generates the exact same PDF view used in the web dashboard, ensuring perfect symmetry.
        $downloadUrl = URL::signedRoute('reports.bulletin.signed', [
            'student_id' => $student->id,
            'mode' => 'single',
            'report_scope' => 'trimester',
            'trimester' => 1
        ], expiration: now()->addMinutes(30));

        return $this->sendResponse([
            'file_url' => $downloadUrl,
            'filename' => "Bulletin_{$student->admission_number}.pdf",
        ], __('chatbot.result_generated'));
    }
}