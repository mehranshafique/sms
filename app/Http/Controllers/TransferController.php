<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentTransfer;
use App\Models\AcademicSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PDF; 

class TransferController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->setPageTitle('Student Transfer / Withdrawal');
    }

    public function create(Student $student)
    {
        // Context Check
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $student->institution_id != $institutionId) abort(403);

        return view('students.transfer.create', compact('student'));
    }

    public function store(Request $request, Student $student)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $student->institution_id != $institutionId) abort(403);

        $request->validate([
            'transfer_date' => 'required|date',
            'reason' => 'required|string|max:255',
            'status' => 'required|in:transferred,withdrawn,expelled',
            'conduct' => 'required|string|max:100',
        ]);

        $currentSession = AcademicSession::where('institution_id', $institutionId)
            ->where('is_current', true)
            ->first();

        // Get current class name for record
        $currentClass = $student->enrollments()->latest()->first();
        $className = $currentClass ? $currentClass->classSection->name : 'N/A';

        DB::transaction(function () use ($request, $student, $institutionId, $currentSession, $className) {
            
            // 1. Create Transfer Record
            StudentTransfer::create([
                'institution_id' => $institutionId,
                'student_id' => $student->id,
                'academic_session_id' => $currentSession->id ?? null,
                'transfer_date' => $request->transfer_date,
                'reason' => $request->reason,
                'status' => $request->status,
                'conduct' => $request->conduct,
                'leaving_class' => $className,
                'remarks' => $request->remarks,
                'created_by' => Auth::id(),
            ]);

            // 2. Update Student Status
            $student->update(['status' => $request->status]);

            // 3. Mark enrollment as 'left' (Optional, depends on logic preference)
            if ($lastEnrollment = $student->enrollments()->latest()->first()) {
                $lastEnrollment->update(['status' => 'left']);
            }
        });

        return redirect()->route('students.show', $student->id)->with('success', 'Student marked as ' . $request->status);
    }

    public function printCertificate(Student $student)
    {
        $transfer = StudentTransfer::where('student_id', $student->id)->latest()->firstOrFail();
        
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $student->institution_id != $institutionId) abort(403);

        $pdf = PDF::loadView('students.transfer.certificate', compact('student', 'transfer'));
        return $pdf->stream('Transfer_Certificate_' . $student->admission_number . '.pdf');
    }
}