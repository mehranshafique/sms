<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamRecord;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Legacy chatbot / parent-app read endpoints (Sanctum + tenant scoped).
 */
class StudentApiController extends Controller
{
    public function profile($id)
    {
        $student = $this->findScopedStudent($id);

        $enrollment = StudentEnrollment::with('classSection.gradeLevel')
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        $className = 'N/A';
        if ($enrollment && $enrollment->classSection) {
            $grade = $enrollment->classSection->gradeLevel->name ?? '';
            $className = trim($grade . ' ' . $enrollment->classSection->name);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $student->id,
                'name' => $student->full_name,
                'admission_no' => $student->admission_number,
                'class' => $className,
                'photo' => $student->student_photo ? asset('storage/' . $student->student_photo) : null,
                'institution' => $student->institution->name ?? null,
            ],
        ]);
    }

    public function financialStatus($id)
    {
        $student = $this->findScopedStudent($id);

        $invoices = Invoice::where('student_id', $student->id)
            ->where('status', '!=', 'paid')
            ->get();

        $totalDue = $invoices->sum(fn ($inv) => $inv->total_amount - $inv->paid_amount);

        return response()->json([
            'success' => true,
            'data' => [
                'total_due' => $totalDue,
                'currency' => config('app.currency_symbol', '$'),
                'status' => $totalDue > 0 ? 'Outstanding' : 'Cleared',
                'pending_invoices' => $invoices->map(fn ($inv) => [
                    'id' => $inv->id,
                    'number' => $inv->invoice_number,
                    'due' => $inv->total_amount - $inv->paid_amount,
                    'due_date' => $inv->due_date->format('Y-m-d'),
                ]),
            ],
        ]);
    }

    public function attendanceHistory($id)
    {
        $student = $this->findScopedStudent($id);

        $present = StudentAttendance::where('student_id', $student->id)
            ->where('status', 'present')
            ->count();

        $absent = StudentAttendance::where('student_id', $student->id)
            ->where('status', 'absent')
            ->count();

        $total = $present + $absent;

        return response()->json([
            'success' => true,
            'data' => [
                'present_days' => $present,
                'absent_days' => $absent,
                'attendance_score' => $total > 0 ? round(($present / $total) * 100) . '%' : 'N/A',
            ],
        ]);
    }

    public function latestResults($id)
    {
        $student = $this->findScopedStudent($id);

        $enrollment = StudentEnrollment::where('student_id', $student->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        if (!$enrollment) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $records = ExamRecord::with(['subject', 'exam'])
            ->where('student_id', $student->id)
            ->whereHas('exam', function ($q) use ($enrollment) {
                $q->where('academic_session_id', $enrollment->academic_session_id)
                    ->where('status', 'published');
            })
            ->latest('updated_at')
            ->get();

        $mapped = $records->map(fn ($r) => [
            'subject' => $r->subject->name ?? 'N/A',
            'marks' => $r->marks_obtained,
            'max_marks' => $r->subject->total_marks ?? 100,
            'exam' => $r->exam->name ?? 'N/A',
            'is_absent' => (bool) $r->is_absent,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'student_name' => $student->full_name,
                'admission_no' => $student->admission_number,
                'results' => $mapped,
            ],
        ]);
    }

    private function findScopedStudent($id): Student
    {
        $user = Auth::user();

        $query = Student::with('institution')->where(function ($q) use ($id) {
            $q->where('id', $id)->orWhere('admission_number', $id);
        });

        if ($user && $user->institute_id) {
            $query->where('institution_id', $user->institute_id);
        }

        return $query->firstOrFail();
    }
}
