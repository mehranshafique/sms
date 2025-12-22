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
use PDF; 

class InvoiceController extends BaseController
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':invoice.view')->only(['index', 'show', 'print', 'downloadPdf']);
        $this->middleware(PermissionMiddleware::class . ':invoice.create')->only(['create', 'store', 'getStudents']);
        $this->setPageTitle(__('invoice.page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();

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
        $institutionId = $this->getInstitutionId();
        
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

    /**
     * AJAX: Get Students for a selected Class
     */
    public function getStudents(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        $classId = $request->class_section_id;

        if (!$classId) return response()->json([]);

        // If Super Admin/Global, infer institution from class
        if (!$institutionId) {
            $class = ClassSection::find($classId);
            $institutionId = $class ? $class->institution_id : null;
        }

        $session = AcademicSession::where('institution_id', $institutionId)
            ->where('is_current', true)
            ->first();

        if (!$session) return response()->json([]);

        $students = StudentEnrollment::with('student')
            ->where('class_section_id', $classId)
            ->where('academic_session_id', $session->id)
            ->where('status', 'active')
            ->get()
            ->map(function($enrollment) {
                return [
                    'id' => $enrollment->student_id,
                    'name' => $enrollment->student->full_name,
                    'roll_no' => $enrollment->roll_number ?? 'N/A'
                ];
            });

        return response()->json($students);
    }

    public function store(Request $request)
    {
        $request->validate([
            'class_section_id' => 'required|exists:class_sections,id',
            'student_ids' => 'required|array', // Now an array of selected IDs
            'student_ids.*' => 'exists:students,id',
            'fee_structure_ids' => 'required|array',
            'fee_structure_ids.*' => 'exists:fee_structures,id',
            'due_date' => 'required|date',
            'issue_date' => 'required|date',
        ]);

        $institutionId = $this->getInstitutionId();
        
        if(!$institutionId) {
            $class = ClassSection::find($request->class_section_id);
            $institutionId = $class->institution_id;
        }
        
        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();
        if(!$session) return response()->json(['message' => __('invoice.no_active_session')], 422);

        $fees = FeeStructure::whereIn('id', $request->fee_structure_ids)->get();
        $totalAmount = $fees->sum('amount');
        
        $selectedStudentIds = $request->student_ids;

        DB::transaction(function () use ($selectedStudentIds, $fees, $totalAmount, $request, $institutionId, $session) {
            foreach ($selectedStudentIds as $studentId) {
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

        $count = count($selectedStudentIds);
        $msg = $count > 1 ? __('invoice.success_generated_bulk', ['count' => $count]) : __('invoice.success_generated_single');

        return response()->json(['message' => $msg, 'redirect' => route('invoices.index')]);
    }

    public function show(Invoice $invoice)
    {
        $institutionId = $this->getInstitutionId();
        if($institutionId && $invoice->institution_id != $institutionId) abort(403);

        $invoice->load(['student', 'items', 'academicSession', 'institution']);
        return view('finance.invoices.show', compact('invoice'));
    }

    public function print($id)
    {
        $institutionId = $this->getInstitutionId();
        $invoice = Invoice::with(['student', 'items', 'institution', 'academicSession', 'payments'])->findOrFail($id);
        
        if($institutionId && $invoice->institution_id != $institutionId) abort(403);

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
}