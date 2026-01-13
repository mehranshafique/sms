<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\BaseController;
use App\Models\ClassSection;
use App\Models\StudentEnrollment;
use App\Models\FeeStructure;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use App\Enums\CurrencySymbol;

class StudentBalanceController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        // Permission check can be added here if needed, e.g., view_financial_reports
        $this->setPageTitle(__('finance.balance_overview')); 
    }

    /**
     * View 1: List of Classes with High-Level Stats
     */
    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        if ($request->ajax()) {
            // Fetch Classes
            $data = ClassSection::with('gradeLevel')
                ->where('institution_id', $institutionId)
                ->select('class_sections.*');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('class_name', function($row){
                    // Format: Grade + Section (e.g. 1er A)
                    $grade = $row->gradeLevel->name ?? '';
                    return ($grade ? $grade . ' ' : '') . $row->name;
                })
                ->addColumn('students_count', function($row){
                    // Count active enrollments
                    return $row->enrollments()->where('status', 'active')->count();
                })
                ->addColumn('paid_students_count', function($row){
                    // FIX: Ensure we only count students who HAVE invoices and have paid them off.
                    
                    // 1. Get all active students in class
                    $studentIds = $row->enrollments()->where('status', 'active')->pluck('student_id');
                    
                    if($studentIds->isEmpty()) return 0;

                    // 2. Find students who actually HAVE invoices (Billed Students)
                    $invoicedStudentIds = Invoice::whereIn('student_id', $studentIds)
                        ->distinct()
                        ->pluck('student_id');

                    // If no one is billed, paid count is 0
                    if($invoicedStudentIds->isEmpty()) return 0;

                    // 3. Find students who HAVE debt (due > 0.01)
                    $debtorIds = Invoice::whereIn('student_id', $studentIds)
                        ->selectRaw('student_id, SUM(total_amount - paid_amount) as due')
                        ->groupBy('student_id')
                        ->having('due', '>', 0.01) 
                        ->pluck('student_id');
                    
                    // 4. Paid Students = (Billed Students) - (Students with Debt)
                    // This excludes students who have never been invoiced.
                    return $invoicedStudentIds->diff($debtorIds)->count();
                })
                ->addColumn('total_invoiced', function($row){
                    return CurrencySymbol::default() . ' ' . number_format($this->getClassFinancials($row->id, 'total'), 2);
                })
                ->addColumn('total_collected', function($row){
                    return '<span class="text-success">' . CurrencySymbol::default() . ' ' . number_format($this->getClassFinancials($row->id, 'paid'), 2) . '</span>';
                })
                ->addColumn('balance', function($row){
                    $due = $this->getClassFinancials($row->id, 'due');
                    return '<span class="text-danger fw-bold">' . CurrencySymbol::default() . ' ' . number_format($due, 2) . '</span>';
                })
                ->addColumn('action', function($row){
                    // Construct Full Name for the Title (Grade + Section)
                    $grade = $row->gradeLevel->name ?? '';
                    $fullName = ($grade ? $grade . ' ' : '') . $row->name;

                    // Localized button
                    return '<button type="button" class="btn btn-primary btn-sm shadow btn-rounded view-class-btn" data-id="'.$row->id.'" data-name="'.$fullName.'">
                                <i class="fa fa-eye me-1"></i> ' . __('finance.view_details') . '
                            </button>';
                })
                ->rawColumns(['total_collected', 'balance', 'action'])
                ->make(true);
        }

        return view('finance.balances.index');
    }

    /**
     * View 2: AJAX Details for a specific Class (Tabs & Student List)
     */
    public function getClassDetails($id)
    {
        $institutionId = $this->getInstitutionId();
        $classSection = ClassSection::findOrFail($id);
        
        // --- SECURITY CHECK ---
        // Prevent accessing class details from another institution context
        if ($institutionId && $classSection->institution_id != $institutionId) {
            abort(403, __('finance.unauthorized_access'));
        }

        // 1. Identify Installments / Fee Groups
        $feeStructures = FeeStructure::where('institution_id', $institutionId)
            ->where(function($q) use ($classSection) {
                $q->where('grade_level_id', $classSection->grade_level_id)
                  ->orWhere('class_section_id', $classSection->id);
            })
            ->get();

        // Organize Tabs
        $tabs = [];
        
        // A. Global Tab
        if ($feeStructures->where('payment_mode', 'global')->isNotEmpty()) {
            $tabs[] = ['id' => 'global', 'label' => __('finance.annual_fee')];
        }

        // B. Installment Tabs
        $installments = $feeStructures->where('payment_mode', 'installment')
            ->groupBy('installment_order')
            ->sortKeys();

        foreach ($installments as $order => $fees) {
            $label = $fees->first()->name; 
            if ($fees->count() > 1) {
                $label = __('finance.installment_label') . " $order";
            }
            
            $tabs[] = ['id' => 'inst_'.$order, 'label' => $label, 'order' => $order];
        }

        // 2. Fetch Students & Their Invoices
        $students = StudentEnrollment::with('student.invoices.items.feeStructure')
            ->where('class_section_id', $id)
            ->where('status', 'active')
            ->get()
            ->map(function($enrollment) {
                return [
                    'id' => $enrollment->student->id,
                    'name' => $enrollment->student->full_name,
                    'photo' => $enrollment->student->student_photo,
                    'admission_no' => $enrollment->student->admission_number,
                    'invoices' => $enrollment->student->invoices 
                ];
            });

        // 3. Map Student Status per Tab
        $studentRows = [];
        foreach ($students as $student) {
            $row = [
                'student' => $student,
                'statuses' => []
            ];

            foreach ($tabs as $tab) {
                $status = 'N/A';
                $style = 'secondary';
                $label = 'N/A'; // Default localized label
                $paidAmount = 0;
                $dueAmount = 0;
                
                $matchingInvoice = null;

                if ($tab['id'] === 'global') {
                    $matchingInvoice = $student['invoices']->first(function($inv) {
                        return $inv->items->contains(function($item) {
                            return $item->feeStructure && $item->feeStructure->payment_mode === 'global';
                        });
                    });
                } else {
                    $order = $tab['order'];
                    $matchingInvoice = $student['invoices']->first(function($inv) use ($order) {
                        return $inv->items->contains(function($item) use ($order) {
                            return $item->feeStructure && $item->feeStructure->installment_order == $order;
                        });
                    });
                }

                if ($matchingInvoice) {
                    $rawStatus = ucfirst($matchingInvoice->status); 
                    
                    // Amounts
                    $paidAmount = $matchingInvoice->paid_amount;
                    $dueAmount = $matchingInvoice->total_amount - $matchingInvoice->paid_amount;

                    // Localize Status
                    if ($rawStatus === 'Paid') {
                        $style = 'success';
                        $label = __('finance.paid');
                    } elseif ($rawStatus === 'Partial') {
                        $style = 'warning';
                        $label = __('finance.status_partial') ?? 'Partial';
                    } elseif ($rawStatus === 'Unpaid') {
                        $style = 'danger';
                        $label = __('finance.status_unpaid') ?? 'Unpaid';
                    } elseif ($rawStatus === 'Overdue') {
                        $style = 'dark';
                        $label = __('finance.status_overdue') ?? 'Overdue';
                    } else {
                        $label = $rawStatus;
                    }
                }

                $row['statuses'][$tab['id']] = [
                    'label' => $label, 
                    'style' => $style,
                    'paid' => CurrencySymbol::default() . ' ' . number_format($paidAmount, 2),
                    'due' => CurrencySymbol::default() . ' ' . number_format($dueAmount, 2)
                ];
            }
            $studentRows[] = $row;
        }

        return response()->json([
            'tabs' => $tabs,
            'students' => $studentRows
        ]);
    }

    // --- Helper ---
    private function getClassFinancials($classId, $type)
    {
        $studentIds = StudentEnrollment::where('class_section_id', $classId)
            ->where('status', 'active')
            ->pluck('student_id');

        $query = Invoice::whereIn('student_id', $studentIds);

        if ($type === 'total') return $query->sum('total_amount');
        if ($type === 'paid') return $query->sum('paid_amount');
        if ($type === 'due') return $query->sum(DB::raw('total_amount - paid_amount'));
        
        return 0;
    }
}