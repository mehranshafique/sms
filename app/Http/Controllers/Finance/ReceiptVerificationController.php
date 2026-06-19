<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Finance\AnnualFeeCalculator;

class ReceiptVerificationController extends Controller
{
    public function __construct(
        protected AnnualFeeCalculator $annualFeeCalculator
    ) {}

    public function show(string $token)
    {
        $payment = Payment::where('receipt_verify_token', $token)
            ->with([
                'invoice.student.enrollments.classSection.gradeLevel',
                'invoice.academicSession',
                'invoice.institution',
            ])
            ->firstOrFail();

        $invoice = $payment->invoice;
        $student = $invoice->student;
        $enrollment = $student->enrollments
            ->firstWhere('academic_session_id', $invoice->academic_session_id)
            ?? $student->enrollments->sortByDesc('created_at')->first();

        $annualFee = $enrollment
            ? $this->annualFeeCalculator->forEnrollment($enrollment)
            : 0.0;

        $totalPaid = (float) Payment::whereHas('invoice', function ($q) use ($student, $invoice) {
            $q->where('student_id', $student->id)
                ->where('academic_session_id', $invoice->academic_session_id);
        })->sum('amount');

        $balanceDue = max(0, $annualFee - $totalPaid);

        return view('finance.receipts.verify', [
            'payment' => $payment,
            'invoice' => $invoice,
            'student' => $student,
            'enrollment' => $enrollment,
            'annualFee' => $annualFee,
            'totalPaid' => $totalPaid,
            'balanceDue' => $balanceDue,
        ]);
    }
}
