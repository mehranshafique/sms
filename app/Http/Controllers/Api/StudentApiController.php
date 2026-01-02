<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\ExamRecord;
use App\Models\StudentAttendance;

class StudentApiController extends Controller
{
    /**
     * Get Basic Profile
     */
    public function profile($id)
    {
        $student = Student::with('institution')->findOrFail($id);
        
        // Basic Security: In production, check if Auth::user() owns this student
        
        return response()->json([
            'id' => $student->id,
            'name' => $student->full_name,
            'admission_no' => $student->admission_number,
            'class' => $student->current_class_name, // Accessor needed on model
            'photo' => $student->student_photo ? asset('storage/'.$student->student_photo) : null,
            'institution' => $student->institution->name
        ]);
    }

    /**
     * Get Financial Status (Balance)
     */
    public function financialStatus($id)
    {
        $invoices = Invoice::where('student_id', $id)
            ->where('status', '!=', 'paid')
            ->get();

        $totalDue = $invoices->sum(function($inv) {
            return $inv->total_amount - $inv->paid_amount;
        });

        return response()->json([
            'total_due' => $totalDue,
            'currency' => config('app.currency_symbol', '$'),
            'status' => $totalDue > 0 ? 'Outstanding' : 'Cleared',
            'pending_invoices' => $invoices->map(function($inv) {
                return [
                    'id' => $inv->id,
                    'number' => $inv->invoice_number,
                    'due' => $inv->total_amount - $inv->paid_amount,
                    'due_date' => $inv->due_date->format('Y-m-d')
                ];
            })
        ]);
    }

    /**
     * Get Attendance Stats
     */
    public function attendanceHistory($id)
    {
        // Simplified Stats
        $present = StudentAttendance::whereHas('enrollment', fn($q) => $q->where('student_id', $id))
            ->where('status', 'present')
            ->count();
            
        $absent = StudentAttendance::whereHas('enrollment', fn($q) => $q->where('student_id', $id))
            ->where('status', 'absent')
            ->count();

        return response()->json([
            'present_days' => $present,
            'absent_days' => $absent,
            'attendance_score' => ($present + $absent) > 0 ? round(($present / ($present + $absent)) * 100) . '%' : 'N/A'
        ]);
    }
}