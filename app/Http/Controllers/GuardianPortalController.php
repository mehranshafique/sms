<?php

namespace App\Http\Controllers;

use App\Models\StudentParent;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\ExamRecord;
use App\Models\StudentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuardianPortalController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->setPageTitle(__('guardian.page_title'));
    }

    public function index()
    {
        $children = $this->linkedChildren();
        return view('guardian.index', compact('children'));
    }

    public function fees(Request $request)
    {
        $student = $this->resolveChild($request);
        $invoices = Invoice::where('student_id', $student->id)->latest()->get();
        $outstanding = $invoices->sum(fn ($i) => max(0, $i->total_amount - $i->paid_amount));
        $currencyService = app(\App\Services\CurrencyDisplayService::class);
        $outstandingFormatted = $currencyService->formatWithSecondary($student->institution_id, (float) $outstanding);

        return view('guardian.fees', compact('student', 'invoices', 'outstanding', 'outstandingFormatted'));
    }

    public function results(Request $request)
    {
        $student = $this->resolveChild($request);
        $records = ExamRecord::with(['exam', 'subject'])
            ->where('student_id', $student->id)
            ->whereHas('exam', fn ($q) => $q->where('status', 'published'))
            ->latest()
            ->limit(50)
            ->get();

        return view('guardian.results', compact('student', 'records'));
    }

    public function requests(Request $request)
    {
        $student = $this->resolveChild($request);
        $requests = StudentRequest::where('student_id', $student->id)->latest()->get();

        return view('guardian.requests', compact('student', 'requests'));
    }

    public function attendance(Request $request)
    {
        $student = $this->resolveChild($request);
        $period = $request->query('period', 'week');

        $enrollment = $student->enrollments()->where('status', 'active')->latest()->first();
        $classSectionId = $enrollment?->class_section_id;

        $institution = $student->institution;
        $typeValue = $institution?->type instanceof \App\Enums\InstitutionType
            ? $institution->type->value
            : (string) ($institution?->type ?? '');
        $isSubjectWise = in_array($typeValue, ['university', 'vocational'], true);

        $comparisonTable = app(\App\Services\AttendanceAnalyticsService::class)
            ->getComparativeSummaryTable($student->id, $classSectionId, $period, $isSubjectWise);

        return view('guardian.attendance', compact('student', 'comparisonTable', 'period'));
    }

    private function linkedChildren()
    {
        $parent = StudentParent::where('user_id', Auth::id())->first();
        if (!$parent) {
            return collect();
        }

        return Student::where('parent_id', $parent->id)->where('status', 'active')->get();
    }

    private function resolveChild(Request $request): Student
    {
        $children = $this->linkedChildren();
        abort_if($children->isEmpty(), 403, __('guardian.no_children'));

        $studentId = $request->query('student_id');
        $student = $studentId
            ? $children->firstWhere('id', (int) $studentId)
            : $children->first();

        abort_if(!$student, 404, __('guardian.child_not_found'));

        return $student;
    }
}
