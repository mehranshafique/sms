<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\BaseController;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\FeeStructure;
use App\Models\ClassSection;
use App\Models\StudentEnrollment;
use App\Models\AcademicSession;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Middleware\PermissionMiddleware;
use PDF; // Requires barryvdh/laravel-dompdf

class InvoiceController extends BaseController
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':invoice.view')->only(['index', 'show', 'print', 'downloadPdf']);
        $this->middleware(PermissionMiddleware::class . ':invoice.create')->only(['create', 'store']);
        $this->setPageTitle(__('invoice.page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = Auth::user()->institute_id;

        if ($request->ajax()) {
            $data = Invoice::with(['student', 'academicSession'])
                ->select('invoices.*');

            if ($institutionId) {
                $data->where('institution_id', $institutionId);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('student_name', function($row){
                    return $row->student->full_name ?? 'N/A';
                })
                ->addColumn('invoice_number', function($row){
                    return '<a href="'.route('invoices.show', $row->id).'" class="text-primary fw-bold">#'.$row->invoice_number.'</a>';
                })
                ->editColumn('total_amount', function($row){
                    return number_format($row->total_amount, 2);
                })
                ->editColumn('paid_amount', function($row){
                    return number_format($row->paid_amount, 2);
                })
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
                    if(auth()->user()->can('invoice.view')){
                        $btn .= '<a href="'.route('invoices.show', $row->id).'" class="btn btn-info shadow btn-xs sharp me-1"><i class="fa fa-eye"></i></a>';
                    }
                    if(auth()->user()->can('payment.create') && $row->status != 'paid'){
                        $btn .= '<a href="'.route('payments.create', ['invoice_id' => $row->id]).'" class="btn btn-success shadow btn-xs sharp me-1" title="Pay"><i class="fa fa-money"></i></a>';
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
        $institutionId = Auth::user()->institute_id;
        
        $classesQuery = ClassSection::query();
        $feesQuery = FeeStructure::query();

        if ($institutionId) {
            $classesQuery->where('institution_id', $institutionId);
            $feesQuery->where('institution_id', $institutionId);
        }

        $classes = $classesQuery->pluck('name', 'id');
        $feeStructures = $feesQuery->pluck('name', 'id');
        
        return view('finance.invoices.create', compact('classes', 'feeStructures'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'class_section_id' => 'required|exists:class_sections,id',
            'fee_structure_ids' => 'required|array',
            'fee_structure_ids.*' => 'exists:fee_structures,id',
            'due_date' => 'required|date',
            'issue_date' => 'required|date',
        ]);

        $institutionId = Auth::user()->institute_id;
        
        if(!$institutionId) {
            $class = ClassSection::find($request->class_section_id);
            $institutionId = $class->institution_id;
        }
        
        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();
        if(!$session) return response()->json(['message' => __('invoice.no_active_session')], 422);

        $students = StudentEnrollment::where('class_section_id', $request->class_section_id)
            ->where('academic_session_id', $session->id)
            ->where('status', 'active')
            ->pluck('student_id');

        if($students->isEmpty()) return response()->json(['message' => __('invoice.no_students_found')], 422);

        $fees = FeeStructure::whereIn('id', $request->fee_structure_ids)->get();
        $totalAmount = $fees->sum('amount');

        DB::transaction(function () use ($students, $fees, $totalAmount, $request, $institutionId, $session) {
            foreach ($students as $studentId) {
                $invoice = Invoice::create([
                    'institution_id' => $institutionId,
                    'academic_session_id' => $session->id,
                    'student_id' => $studentId,
                    'invoice_number' => 'INV-' . strtoupper(Str::random(8)),
                    'issue_date' => $request->issue_date,
                    'due_date' => $request->due_date,
                    'total_amount' => $totalAmount,
                    'status' => 'unpaid',
                ]);

                foreach ($fees as $fee) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'fee_structure_id' => $fee->id,
                        'description' => $fee->name,
                        'amount' => $fee->amount,
                    ]);
                }
            }
        });

        return response()->json(['message' => __('invoice.success_generated'), 'redirect' => route('invoices.index')]);
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['student', 'items', 'academicSession', 'institution']);
        return view('finance.invoices.show', compact('invoice'));
    }

    /**
     * Display a printable version of the invoice.
     */
    public function print($id)
    {
        $invoice = Invoice::with(['student', 'items', 'institution', 'academicSession', 'payments'])->findOrFail($id);
        return view('finance.invoices.print', compact('invoice'));
    }

    /**
     * Download the invoice as PDF.
     */
    public function downloadPdf($id)
    {
        $invoice = Invoice::with(['student', 'items', 'institution', 'academicSession', 'payments'])->findOrFail($id);
        
        // Use DomPDF if available, otherwise fallback to print view
        if (class_exists('PDF')) {
            $pdf = \PDF::loadView('finance.invoices.print', compact('invoice'));
            return $pdf->download('Invoice-'.$invoice->invoice_number.'.pdf');
        } else {
            // Fallback if package missing
            return redirect()->route('invoices.print', $id);
        }
    }
}