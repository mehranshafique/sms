<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\BaseController;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\FeeStructure;
use App\Models\ClassSection;
use App\Models\GradeLevel;
use App\Models\StudentEnrollment;
use App\Models\AcademicSession;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Middleware\PermissionMiddleware;
use PDF;
use App\Enums\CurrencySymbol;

class InvoiceController extends BaseController
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':invoice.view')->only(['index', 'show', 'print', 'downloadPdf']);
        $this->middleware(PermissionMiddleware::class . ':invoice.create')->only(['create', 'store']);
        $this->middleware(PermissionMiddleware::class . ':invoice.delete')->only(['destroy']);
        $this->setPageTitle(__('invoice.page_title'));
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $institutionId = session('active_institution_id') ?? $user->institute_id;
        if ($institutionId === 'global') $institutionId = null;

        if ($request->ajax()) {
            $data = Invoice::with(['student', 'academicSession'])
                ->select('invoices.*')
                ->latest();

            if ($institutionId) {
                $data->where('institution_id', $institutionId);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('student_name', fn($row) => $row->student->full_name ?? 'N/A')
                ->addColumn('invoice_number', fn($row) => '<a href="'.route('invoices.show', $row->id).'" class="text-primary fw-bold">#'.$row->invoice_number.'</a>')
                ->editColumn('total_amount', fn($row) => CurrencySymbol::default() . ' ' . number_format($row->total_amount, 2))
                ->editColumn('paid_amount', fn($row) => CurrencySymbol::default() . ' ' . number_format($row->paid_amount, 2))
                ->editColumn('issue_date', fn($row) => $row->issue_date->format('d M, Y'))
                ->editColumn('due_date', fn($row) => $row->due_date->format('d M, Y'))
                ->editColumn('status', function($row){
                    $badges = [
                        'unpaid' => 'badge-danger',
                        'partial' => 'badge-warning', 
                        'paid' => 'badge-success', 
                        'overdue' => 'badge-dark'
                    ];
                    return '<span class="badge '.($badges[$row->status] ?? 'badge-secondary').'">'.ucfirst($row->status).'</span>';
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    
                    if(auth()->user()->can('invoice.view')) {
                        $btn .= '<a href="'.route('invoices.show', $row->id).'" class="btn btn-info shadow btn-xs sharp me-1" title="'.__('invoice.view').'"><i class="fa fa-eye"></i></a>';
                    }
                    
                    if(auth()->user()->can('payment.create') && $row->status != 'paid') {
                        $btn .= '<a href="'.route('payments.create', ['invoice_id' => $row->id]).'" class="btn btn-success shadow btn-xs sharp me-1" title="'.__('invoice.pay').'"><i class="fa-solid fa-money-bill-wave"></i></a>';
                    }

                    if(auth()->user()->can('invoice.delete')) {
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'" title="'.__('invoice.delete').'"><i class="fa fa-trash"></i></button>';
                    }

                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['invoice_number', 'status', 'action'])
                ->make(true);
        }
        return view('finance.invoices.index');
    }

    public function create()
    {
        $user = Auth::user();
        $institutionId = session('active_institution_id') ?? $user->institute_id;
        if ($institutionId === 'global') $institutionId = null;
        
        $gradesQuery = GradeLevel::query();
        if ($institutionId) $gradesQuery->where('institution_id', $institutionId);
        $grades = $gradesQuery->pluck('name', 'id');
        
        return view('finance.invoices.create', compact('grades'));
    }

    public function getClassSections(Request $request)
    {
        $request->validate(['grade_id' => 'required|exists:grade_levels,id']);
        $user = Auth::user();
        $institutionId = session('active_institution_id') ?? $user->institute_id;
        if ($institutionId === 'global') $institutionId = null;
        
        $query = ClassSection::where('grade_level_id', $request->grade_id);
        if ($institutionId) $query->where('institution_id', $institutionId);
        return response()->json($query->pluck('name', 'id'));
    }

    public function getStudents(Request $request)
    {
        $request->validate(['class_section_id' => 'required|exists:class_sections,id']);
        $user = Auth::user();
        $institutionId = session('active_institution_id') ?? $user->institute_id;
        if ($institutionId === 'global') $institutionId = null;
        if(!$institutionId) {
            $class = ClassSection::find($request->class_section_id);
            $institutionId = $class->institution_id;
        }
        
        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();
        if(!$session) return response()->json([]);

        $students = StudentEnrollment::where('class_section_id', $request->class_section_id)
            ->where('academic_session_id', $session->id)
            ->where('status', 'active')
            ->with('student')
            ->get()
            ->map(fn($e) => ['id' => $e->student->id, 'name' => $e->student->full_name, 'admission_number' => $e->student->admission_number]);
            
        return response()->json($students);
    }

    public function getFees(Request $request)
    {
        $request->validate(['class_section_id' => 'required|exists:class_sections,id']);
        $user = Auth::user();
        $institutionId = session('active_institution_id') ?? $user->institute_id;
        if ($institutionId === 'global') $institutionId = null;
        if(!$institutionId) {
            $class = ClassSection::find($request->class_section_id);
            $institutionId = $class->institution_id;
        }

        $section = ClassSection::find($request->class_section_id);
        $fees = FeeStructure::with('feeType')
            ->where('institution_id', $institutionId)
            ->where(function($q) use ($section, $request) {
                $q->where('grade_level_id', $section->grade_level_id)
                  ->orWhere('class_section_id', $request->class_section_id);
            })
            ->get()
            ->map(fn($fee) => [
                'id' => $fee->id,
                'name' => $fee->name,
                'amount' => CurrencySymbol::default() . ' ' . number_format($fee->amount, 2),
                'type' => $fee->feeType->name ?? 'N/A',
                'frequency' => ucfirst($fee->frequency),
                'payment_mode' => ucfirst($fee->payment_mode),
                'order' => $fee->installment_order ?? '-'
            ]);

        return response()->json($fees);
    }

    public function checkDuplicates(Request $request)
    {
        $studentIds = $request->student_ids ?? [];
        $feeIds = $request->fee_structure_ids ?? [];
        if (empty($studentIds) || empty($feeIds)) return response()->json(['has_duplicates' => false]);

        $user = Auth::user();
        $institutionId = session('active_institution_id') ?? $user->institute_id;
        if ($institutionId === 'global') $institutionId = null;
        if(!$institutionId && $request->class_section_id) {
             $institutionId = ClassSection::find($request->class_section_id)->institution_id;
        }

        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();
        if (!$session) return response()->json(['has_duplicates' => false]);

        // Check specifically for fee structures already invoiced to selected students in the current session
        $count = InvoiceItem::whereIn('fee_structure_id', $feeIds)
            ->whereHas('invoice', fn($q) => $q->whereIn('student_id', $studentIds)->where('academic_session_id', $session->id))
            ->distinct('invoice_id')->count();

        // Calculate total students selected
        $totalSelected = count($studentIds);
        
        // Calculate new students who will actually get an invoice (Total - Duplicates roughly, logic improved below)
        // Actually, let's just return the info. The JS will decide whether to prompt.
        
        $message = "";
        if ($count > 0) {
            // Find how many students already have invoices vs how many don't
            // This is an approximation for the warning message
            $studentsWithInvoice = Invoice::whereIn('student_id', $studentIds)
                ->where('academic_session_id', $session->id)
                ->whereHas('items', fn($q) => $q->whereIn('fee_structure_id', $feeIds))
                ->distinct('student_id')
                ->count();
            
            $studentsWithoutInvoice = $totalSelected - $studentsWithInvoice;
            
            $message = "Warning: {$studentsWithInvoice} student(s) already have invoices for these fees. Only {$studentsWithoutInvoice} new invoice(s) will be generated for missing students.";
        }

        return response()->json([
            'has_duplicates' => $count > 0,
            'count' => $count,
            'message' => $message
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'class_section_id' => 'required|exists:class_sections,id',
            'fee_structure_ids' => 'required|array',
            'fee_structure_ids.*' => 'exists:fee_structures,id',
            'due_date' => 'required|date',
            'issue_date' => 'required|date',
            'student_ids' => 'required|array', 
            'student_ids.*' => 'exists:students,id'
        ]);

        $user = Auth::user();
        $institutionId = session('active_institution_id') ?? $user->institute_id;
        if ($institutionId === 'global') $institutionId = null;
        
        if(!$institutionId) {
            $class = ClassSection::find($request->class_section_id);
            $institutionId = $class->institution_id;
        }
        
        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();
        if(!$session) return response()->json(['message' => __('invoice.no_active_session')], 422);

        $fees = FeeStructure::whereIn('id', $request->fee_structure_ids)->with('feeType')->get();
        $totalFeeAmount = $fees->sum('amount');
        
        // --- 1. Mode Determination ---
        // If ANY selected fee is 'one_time', we bypass the strict mode check.
        $isOneTimeInvoice = $fees->contains('frequency', 'one_time');
        
        // If NOT one-time, we respect the payment mode of the first fee (global vs installment)
        $targetMode = $fees->first()->payment_mode; 

        // --- 2. Fee Cap Check Setup ---
        $classSection = ClassSection::find($request->class_section_id);
        $globalFeeStructure = FeeStructure::where('institution_id', $institutionId)
            ->where('academic_session_id', $session->id)
            ->where('payment_mode', 'global')
            ->where(function($q) use ($classSection) {
                $q->where('class_section_id', $classSection->id)
                  ->orWhere('grade_level_id', $classSection->grade_level_id);
            })
            ->whereHas('feeType', function($q) {
                $q->where('name', 'like', '%Tuition%'); // Only track Tuition against cap
            })
            ->first();

        $annualCap = $globalFeeStructure ? $globalFeeStructure->amount : 0;

        // --- 3. Filter Students ---
        $studentsQuery = StudentEnrollment::where('class_section_id', $request->class_section_id)
            ->where('academic_session_id', $session->id)
            ->whereIn('student_id', $request->student_ids)
            ->where('status', 'active')
            ->with('student');

        $students = $studentsQuery->get();

        // Strict Mode Check (Skipped if One-Time fee)
        if (!$isOneTimeInvoice) {
            $students = $students->filter(function($enrollment) use ($targetMode) {
                $studentMode = $enrollment->student->payment_mode ?? 'installment';
                return $studentMode === $targetMode;
            });
        }

        if($students->isEmpty()) {
            $modeMsg = $isOneTimeInvoice ? 'active' : ucfirst($targetMode);
            return response()->json([
                'message' => __('invoice.no_students_found_for_mode', ['mode' => $modeMsg])
            ], 422);
        }

        $generatedCount = 0;
        $skippedCount = 0;

        DB::transaction(function () use ($students, $fees, $totalFeeAmount, $request, $institutionId, $session, &$generatedCount, &$skippedCount, $targetMode, $annualCap, $isOneTimeInvoice) {
            foreach ($students as $enrollment) {
                $studentId = $enrollment->student_id;
                
                // A. Conflict Check (Only for non-one-time invoices)
                if (!$isOneTimeInvoice) {
                    $hasGlobal = Invoice::where('student_id', $studentId)
                        ->where('academic_session_id', $session->id)
                        ->whereHas('items.feeStructure', fn($q) => $q->where('payment_mode', 'global')->where('frequency', '!=', 'one_time'))
                        ->exists();

                    $hasInstallment = Invoice::where('student_id', $studentId)
                        ->where('academic_session_id', $session->id)
                        ->whereHas('items.feeStructure', fn($q) => $q->where('payment_mode', 'installment'))
                        ->exists();

                    if ($targetMode === 'installment' && $hasGlobal) { $skippedCount++; continue; }
                    if ($targetMode === 'global' && ($hasGlobal || $hasInstallment)) { $skippedCount++; continue; }
                }

                // B. Calculate Discount
                // FIX: Only apply discount to standard (Global/Installment) invoices, NOT one-time extras.
                $discountValue = 0;
                $discountDescription = null;

                if (!$isOneTimeInvoice && $enrollment->discount_amount > 0) {
                    if ($enrollment->discount_type === 'percentage') {
                        $discountValue = ($totalFeeAmount * $enrollment->discount_amount) / 100;
                        $discountDescription = __('invoice.discount_scholarship') . " ({$enrollment->discount_amount}%)";
                    } else {
                        $discountValue = $enrollment->discount_amount;
                        $discountDescription = __('invoice.discount_scholarship') . " (" . __('invoice.fixed') . ")";
                    }
                    if($enrollment->scholarship_reason) {
                        $discountDescription .= ": " . $enrollment->scholarship_reason;
                    }
                }

                $finalAmount = max(0, $totalFeeAmount - $discountValue);

                // C. Fee Cap Check
                // Applies only if this invoice contains "Tuition" related fees
                if ($annualCap > 0) {
                    $isTuitionInvoice = $fees->contains(fn($fee) => $fee->feeType && stripos($fee->feeType->name, 'Tuition') !== false);

                    if ($isTuitionInvoice) {
                        $existingTuitionTotal = Invoice::where('student_id', $studentId)
                            ->where('academic_session_id', $session->id)
                            ->with(['items.feeStructure.feeType'])
                            ->get()
                            ->flatMap(fn($inv) => $inv->items)
                            ->filter(fn($item) => $item->feeStructure && $item->feeStructure->feeType && stripos($item->feeStructure->feeType->name, 'Tuition') !== false)
                            ->sum('amount'); // Sum of previous invoices (gross or net depends on policy, usually gross for caps)

                        // Check if adding this invoice exceeds cap
                        // Note: We check against the GROSS fee ($totalFeeAmount), not net, because caps usually apply to sticker price.
                        // Discounts reduce what they pay, but the cap is on the 'price'.
                        if (($existingTuitionTotal + $totalFeeAmount) > ($annualCap + 0.01)) {
                            $skippedCount++; continue;
                        }
                    }
                }

                // D. Duplicate Item Check
                $feesToProcess = $fees;
                $alreadyInvoicedFees = InvoiceItem::whereHas('invoice', fn($q) => $q->where('student_id', $studentId)->where('academic_session_id', $session->id))
                    ->whereIn('fee_structure_id', $fees->pluck('id'))
                    ->pluck('fee_structure_id')->toArray();

                if (!empty($alreadyInvoicedFees)) {
                    $feesToProcess = $fees->whereNotIn('id', $alreadyInvoicedFees);
                    if ($feesToProcess->isEmpty()) { $skippedCount++; continue; }
                }

                // E. Create Invoice
                $invoice = Invoice::create([
                    'institution_id' => $institutionId,
                    'academic_session_id' => $session->id,
                    'student_id' => $studentId,
                    'invoice_number' => 'INV-' . strtoupper(Str::random(8)),
                    'issue_date' => $request->issue_date,
                    'due_date' => $request->due_date,
                    'total_amount' => $finalAmount,
                    'status' => 'unpaid',
                ]);

                foreach ($feesToProcess as $fee) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'fee_structure_id' => $fee->id,
                        'description' => $fee->name,
                        'amount' => $fee->amount,
                    ]);
                }

                // F. Add Negative Line Item for Discount
                if ($discountValue > 0) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'fee_structure_id' => null,
                        'description' => $discountDescription,
                        'amount' => -$discountValue,
                    ]);
                }

                $generatedCount++;
            }
        });

        if ($generatedCount === 0) {
            return response()->json([
                'message' => __('invoice.no_invoices_generated_error', ['count' => $skippedCount])
            ], 422);
        }

        $msg = __('invoice.success_generated_count', ['count' => $generatedCount]);
        if ($skippedCount > 0) {
            $msg .= " " . __('invoice.skipped_count_msg', ['count' => $skippedCount]);
        }

        return response()->json(['message' => $msg, 'redirect' => route('invoices.index')]);
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['student', 'items', 'academicSession', 'institution']);
        return view('finance.invoices.show', compact('invoice'));
    }

    public function print($id)
    {
        $invoice = Invoice::with(['student', 'items', 'institution', 'academicSession', 'payments'])->findOrFail($id);
        return view('finance.invoices.print', compact('invoice'));
    }

    public function downloadPdf($id)
    {
        $invoice = Invoice::with(['student', 'items', 'institution', 'academicSession', 'payments'])->findOrFail($id);
        
        if (class_exists('PDF')) {
            $pdf = \PDF::loadView('finance.invoices.print', compact('invoice'));
            return $pdf->download('Invoice-'.$invoice->invoice_number.'.pdf');
        } else {
            return redirect()->route('invoices.print', $id);
        }
    }

    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);
        
        $institutionId = session('active_institution_id') ?? Auth::user()->institute_id;
        if ($institutionId && $institutionId !== 'global' && $invoice->institution_id != $institutionId) {
            abort(403);
        }

        if ($invoice->paid_amount > 0) {
            return response()->json(['message' => __('invoice.error_delete_paid')], 422);
        }

        $invoice->delete();

        return response()->json(['message' => __('invoice.success_deleted')]);
    }
}