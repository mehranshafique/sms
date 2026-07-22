<?php

namespace App\Services;

use App\Models\DisciplinaryRecord;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Models\StudentRequest;
use Illuminate\Support\Collection;

class StudentRequestContextService
{
    public function buildDossier(Student $student, ?int $academicSessionId = null): array
    {
        $sessionId = $academicSessionId ?? $student->enrollments()
            ->where('status', 'active')
            ->latest()
            ->value('academic_session_id');

        $enrollment = StudentEnrollment::with(['classSection.gradeLevel', 'academicSession'])
            ->where('student_id', $student->id)
            ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId))
            ->where('status', 'active')
            ->latest()
            ->first();

        $invoices = Invoice::where('student_id', $student->id)
            ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId))
            ->get();

        $totalFees = (float) $invoices->sum('total_amount');
        $totalPaid = (float) $invoices->sum('paid_amount');
        $balance = max(0, $totalFees - $totalPaid);

        $recentPayments = Payment::whereHas('invoice', fn ($q) => $q->where('student_id', $student->id))
            ->with('invoice')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($p) => [
                'date' => localized_date($p->payment_date, 'd M Y'),
                'amount' => number_format((float) $p->amount, 2),
                'invoice' => $p->invoice?->invoice_number,
            ]);

        $attendanceStats = $this->attendanceSummary($student->id, $sessionId);
        $disciplineCount = DisciplinaryRecord::where('student_id', $student->id)->count();
        $previousRequests = StudentRequest::where('student_id', $student->id)
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'ticket' => $r->ticket_number,
                'type' => $r->typeLabel(),
                'status' => __('requests.status_' . $r->status),
                'date' => localized_date($r->created_at, 'd M Y'),
                'deadline' => ($r->payment_deadline ?? $r->end_date)
                    ? localized_date($r->payment_deadline ?? $r->end_date, 'd M Y')
                    : '—',
            ]);

        $parent = $student->parent;

        return [
            'student_name' => $student->full_name,
            'admission_number' => $student->admission_number,
            'class_section' => class_section_label($enrollment?->classSection),
            'session_name' => $enrollment?->academicSession?->name,
            'total_fees' => number_format($totalFees, 2),
            'amount_paid' => number_format($totalPaid, 2),
            'outstanding_balance' => number_format($balance, 2),
            'recent_payments' => $recentPayments,
            'attendance' => $attendanceStats,
            'discipline_incidents' => $disciplineCount,
            'previous_requests' => $previousRequests,
            'parent_name' => $parent?->full_name ?? $parent?->father_name ?? '-',
            'parent_phones' => array_filter([
                $parent?->father_phone,
                $parent?->mother_phone,
                $parent?->guardian_phone,
            ]),
        ];
    }

    private function attendanceSummary(int $studentId, ?int $sessionId): array
    {
        $query = StudentAttendance::where('student_id', $studentId);
        if ($sessionId) {
            $query->where('academic_session_id', $sessionId);
        }

        $rows = $query->get();
        $total = $rows->count();
        if ($total === 0) {
            return ['present' => 0, 'absent' => 0, 'late' => 0, 'percentage' => 0];
        }

        $present = $rows->whereIn('status', ['present', 'excused'])->count();
        $absent = $rows->where('status', 'absent')->count();
        $late = $rows->where('status', 'late')->count();

        return [
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'percentage' => round(($present / $total) * 100, 1),
        ];
    }
}
