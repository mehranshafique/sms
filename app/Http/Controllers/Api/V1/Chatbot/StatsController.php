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

        $currentSession = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();
        if (!$currentSession) return $this->sendError(__('chatbot.no_session'), 200);

        $enrollment = $student->enrollments()->where('status', 'active')->latest()->first();
        if (! $enrollment) {
            return $this->sendError(__('chatbot.student_not_found'), 404);
        }

        $cycleService = app(\App\Services\AcademicCycleService::class);
        $cycle = $cycleService->resolveCycle($enrollment);
        $periodService = app(\App\Services\AssessmentPeriodService::class);
        $latest = $periodService->latestOfficialStage((int) $institutionId, (int) $currentSession->id, $cycle);

        if (! $latest) {
            return $this->sendError(__('chatbot.no_results_found'), 200);
        }

        $access = app(\App\Services\ReportCardAccessService::class)
            ->check($student, (int) $institutionId, $latest['key']);
        if (! $access['allowed']) {
            return $this->sendError($access['message_en'] ?: __('chatbot.financial_restriction_msg', ['amount' => '']), 200);
        }

        $downloadUrl = URL::signedRoute('reports.bulletin.signed', array_merge(
            ['student_id' => $student->id],
            $latest['params']
        ), expiration: now()->addMinutes(30));

        return $this->sendResponse([
            'file_url' => $downloadUrl,
            'filename' => "Bulletin_{$student->admission_number}.pdf",
        ], __('chatbot.result_generated'));
    }
}