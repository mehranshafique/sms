<?php

namespace App\Services\Ai;

use App\Models\Exam;
use App\Models\ExamRecord;
use App\Models\ExamSchedule;
use App\Models\Invoice;
use App\Models\Notice;
use App\Models\PlanUpgradeRequest;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\PlanContextService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Read-only, permission-scoped data for embedded AI tools.
 */
class AiContextResolver
{
    public function institutionId(?User $user = null): ?int
    {
        return app(PlanContextService::class)->resolveInstitutionId($user ?? Auth::user());
    }

    public function studentSummary(int $studentId, ?int $institutionId, User $user): ?array
    {
        $student = Student::with(['institution', 'enrollments.classSection.gradeLevel'])
            ->when($institutionId, fn ($q) => $q->where('institution_id', $institutionId))
            ->find($studentId);

        if (!$student) {
            return null;
        }
        if ($institutionId && (int) $student->institution_id !== (int) $institutionId) {
            return null;
        }
        if (!$user->can('view', $student)) {
            return null;
        }

        $enrollment = $student->enrollments()->where('status', 'active')->latest()->first();
        $className  = $enrollment?->classSection
            ? trim(($enrollment->classSection->gradeLevel->name ?? '') . ' ' . ($enrollment->classSection->name ?? ''))
            : null;

        $attendanceRate = null;
        if ($institutionId) {
            $total = StudentAttendance::where('student_id', $studentId)
                ->where('institution_id', $institutionId)
                ->where('attendance_date', '>=', now()->subDays(30))
                ->count();
            $present = StudentAttendance::where('student_id', $studentId)
                ->where('institution_id', $institutionId)
                ->where('attendance_date', '>=', now()->subDays(30))
                ->where('status', 'present')
                ->count();
            $attendanceRate = $total > 0 ? round(($present / $total) * 100) : null;
        }

        $invoices = Invoice::where('student_id', $studentId)
            ->when($institutionId, fn ($q) => $q->where('institution_id', $institutionId))
            ->get();

        $totalDue = $invoices->sum(fn ($i) => max(0, $i->total_amount - $i->paid_amount));

        $recentMarks = ExamRecord::with(['exam', 'subject'])
            ->where('student_id', $studentId)
            ->latest('updated_at')
            ->limit(8)
            ->get()
            ->map(fn ($r) => [
                'exam'    => $r->exam?->name,
                'subject' => $r->subject?->name,
                'marks'   => $r->is_absent ? 'Absent' : $r->marks_obtained,
                'remarks' => $r->remarks,
            ])
            ->all();

        return [
            'name'             => $student->full_name,
            'admission_number' => $student->admission_number,
            'class'            => $className,
            'status'           => $student->status,
            'attendance_30d'   => $attendanceRate,
            'balance_due'      => $totalDue,
            'recent_marks'     => $recentMarks,
        ];
    }

    public function examClassMarks(int $examId, int $classSectionId, ?int $institutionId, User $user): ?array
    {
        if (!$user->can('exam_mark.create') && !$user->hasAnyRole(['Super Admin', 'Head Officer', 'School Admin', 'Teacher'])) {
            return null;
        }

        $exam = Exam::when($institutionId, fn ($q) => $q->where('institution_id', $institutionId))->find($examId);
        if (!$exam) {
            return null;
        }

        $enrollments = StudentEnrollment::with('student')
            ->where('class_section_id', $classSectionId)
            ->where('status', 'active')
            ->when($institutionId, fn ($q) => $q->where('institution_id', $institutionId))
            ->get();

        $students = [];
        foreach ($enrollments as $en) {
            if (!$en->student) {
                continue;
            }
            $records = ExamRecord::with('subject')
                ->where('exam_id', $examId)
                ->where('student_id', $en->student_id)
                ->where(function ($q) use ($classSectionId) {
                    $q->where('class_section_id', $classSectionId)
                        ->orWhereNull('class_section_id');
                })
                ->get();

            $marks = $records->map(fn ($r) => [
                'subject' => $r->subject?->name,
                'marks'   => $r->is_absent ? null : (float) $r->marks_obtained,
            ])->all();

            $avg = collect($marks)->filter(fn ($m) => $m['marks'] !== null)->avg('marks');

            $students[] = [
                'student_id'   => $en->student_id,
                'name'         => $en->student->full_name,
                'marks'        => $marks,
                'average'      => $avg !== null ? round($avg, 1) : null,
                'existing_remarks' => $records->pluck('remarks')->filter()->first(),
            ];
        }

        return [
            'exam'     => $exam->name,
            'students' => $students,
        ];
    }

    public function dashboardBriefing(?int $institutionId, User $user): array
    {
        $lines = [];
        $today = now();

        if ($institutionId) {
            if ($user->can('notice.view')) {
                $draftNotices = Notice::where('institution_id', $institutionId)->where('is_published', false)->count();
                if ($draftNotices > 0) {
                    $lines[] = "Unpublished notices: {$draftNotices}";
                }
            }

            if ($user->can('invoice.view')) {
                $overdue = Invoice::where('institution_id', $institutionId)
                    ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                    ->where('due_date', '<', $today)
                    ->count();
                $overdueAmount = Invoice::where('institution_id', $institutionId)
                    ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                    ->get()
                    ->sum(fn ($i) => max(0, $i->total_amount - $i->paid_amount));
                if ($overdue > 0) {
                    $lines[] = "Overdue invoices: {$overdue} (total due approx " . number_format($overdueAmount, 0) . ")";
                }
            }

            if ($user->can('exam.view')) {
                $upcomingExams = ExamSchedule::whereHas('exam', fn ($q) => $q->where('institution_id', $institutionId))
                    ->where('exam_date', '>=', $today)
                    ->where('exam_date', '<=', $today->copy()->addDays(14))
                    ->count();
                if ($upcomingExams > 0) {
                    $lines[] = "Exam schedules in next 14 days: {$upcomingExams}";
                }
            }

            $pendingUpgrades = PlanUpgradeRequest::where('institution_id', $institutionId)
                ->where('status', PlanUpgradeRequest::STATUS_PENDING)
                ->count();
            if ($pendingUpgrades > 0 && $user->hasRole('Super Admin')) {
                $lines[] = "Pending plan upgrade requests: {$pendingUpgrades}";
            }
        } elseif ($user->hasRole('Super Admin')) {
            $lines[] = 'Platform global view — switch to a school for institution-specific insights.';
        }

        return ['facts' => $lines, 'date' => $today->toDateString()];
    }

    public function invoiceContext(int $invoiceId, ?int $institutionId, User $user): ?array
    {
        $invoice = Invoice::with(['student', 'institution', 'items.feeStructure'])
            ->when($institutionId, fn ($q) => $q->where('institution_id', $institutionId))
            ->find($invoiceId);

        if (!$invoice || !$user->can('view', $invoice)) {
            return null;
        }

        return [
            'invoice_number' => $invoice->invoice_number,
            'student'        => $invoice->student?->full_name,
            'status'         => $invoice->status,
            'total'          => $invoice->total_amount,
            'paid'           => $invoice->paid_amount,
            'due'            => max(0, $invoice->total_amount - $invoice->paid_amount),
            'due_date'       => $invoice->due_date?->format('Y-m-d'),
            'issue_date'     => $invoice->issue_date?->format('Y-m-d'),
        ];
    }

    public function feesOverdueSummary(?int $institutionId, ?int $classSectionId, ?int $feeStructureId): array
    {
        $query = Invoice::with('student')
            ->when($institutionId, fn ($q) => $q->where('institution_id', $institutionId))
            ->whereIn('status', ['unpaid', 'partial', 'overdue']);

        if ($classSectionId) {
            $studentIds = StudentEnrollment::where('class_section_id', $classSectionId)
                ->where('status', 'active')
                ->pluck('student_id');
            $query->whereIn('student_id', $studentIds);
        }

        $invoices = $query->get()->filter(function ($inv) use ($feeStructureId) {
            if (!$feeStructureId) {
                return true;
            }
            return $inv->items->contains(fn ($item) => $item->fee_structure_id == $feeStructureId);
        });

        $totalDue = $invoices->sum(fn ($i) => max(0, $i->total_amount - $i->paid_amount));

        return [
            'count'     => $invoices->count(),
            'total_due' => $totalDue,
            'sample'    => $invoices->take(5)->map(fn ($i) => [
                'student' => $i->student?->full_name,
                'due'     => max(0, $i->total_amount - $i->paid_amount),
            ])->values()->all(),
        ];
    }

    public function ticketThread(int $ticketId, User $user): ?array
    {
        $ticket = SupportTicket::with(['messages.user', 'institution'])->find($ticketId);
        if (!$ticket) {
            return null;
        }

        $isAgent = $user->hasRole('Super Admin');
        if (!$isAgent && (int) $ticket->user_id !== (int) $user->id) {
            return null;
        }

        return [
            'subject'  => $ticket->subject,
            'status'   => $ticket->status,
            'category' => $ticket->category,
            'school'   => $ticket->institution?->name,
            'messages' => $ticket->messages->map(fn ($m) => [
                'from'    => $m->user?->name ?? 'User',
                'body'    => $m->body,
                'is_staff'=> (bool) $m->is_support,
            ])->all(),
        ];
    }

    public function helpSnippets(?string $routeName): string
    {
        $articles = config('help_center.articles', []);
        $chunks   = [];
        foreach ($articles as $slug => $meta) {
            $title = $meta['title']['en'] ?? $slug;
            $summary = $meta['summary']['en'] ?? '';
            $chunks[] = "- {$title}: {$summary}";
        }
        $base = implode("\n", array_slice($chunks, 0, 12));

        if ($routeName && str_contains($routeName, 'invoice')) {
            $base .= "\n- For invoices: use Finance → Invoices, or share payment links with parents.";
        }
        if ($routeName && str_contains($routeName, 'notice')) {
            $base .= "\n- For notices: choose audience (all/staff/student/parent) before publishing.";
        }

        return $base;
    }
}
