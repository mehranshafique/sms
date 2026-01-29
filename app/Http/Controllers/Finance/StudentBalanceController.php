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
use Spatie\Permission\Middleware\PermissionMiddleware; // Added

class StudentBalanceController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        // Permission check: Reuse 'invoice.view' or add specific 'balance.view'
        // Using 'invoice.view' as it's finance related and usually shared
        $this->middleware(PermissionMiddleware::class . ':invoice.view')->only(['index', 'getClassDetails']);
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
                    // LOGIC: Paid Count = (Students with Invoices) - (Students with Debt)
                    $studentIds = $row->enrollments()->where('status', 'active')->pluck('student_id');
                    
                    if($studentIds->isEmpty()) return 0;

                    $invoicedStudents = Invoice::whereIn('student_id', $studentIds)
                        ->select('student_id')
                        ->groupBy('student_id')
                        ->get()
                        ->pluck('student_id');
                    
                    if($invoicedStudents->isEmpty()) return 0;

                    $debtorIds = Invoice::whereIn('student_id', $studentIds)
                        ->select('student_id')
                        ->groupBy('student_id')
                        ->havingRaw('SUM(total_amount - paid_amount) > 0.01')
                        ->pluck('student_id');

                    $fullyPaidCount = $invoicedStudents->diff($debtorIds)->count();

                    return $fullyPaidCount;
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
        
        if ($institutionId && $classSection->institution_id != $institutionId) {
            abort(403, __('finance.unauthorized_access'));
        }

        $feeStructures = FeeStructure::where('institution_id', $institutionId)
            ->where(function($q) use ($classSection) {
                $q->where('grade_level_id', $classSection->grade_level_id)
                  ->orWhere('class_section_id', $classSection->id);
            })
            ->get();

        // Organize Tabs
        $tabs = [];

        // A. Summary Tab (Moved to FIRST Position)
        $tabs[] = [
            'id' => 'summary',
            'label' => __('finance.summary') ?? 'Summary',
            'description' => __('finance.tab_info_summary') ?? 'Accumulated totals for all student payments.'
        ];

        // B. Installment Tabs
        $installments = $feeStructures->where('payment_mode', 'installment')
            ->groupBy('installment_order')
            ->sortKeys();

        foreach ($installments as $order => $fees) {
            $label = $fees->first()->name; 
            if ($fees->count() > 1) {
                $label = __('finance.installment_label') . " $order";
            }
            $tabs[] = [
                'id' => 'inst_'.$order, 
                'label' => $label, 
                'order' => $order,
                'description' => __('finance.tab_info_installment')
            ];
        }

        // C. Global Tab (Moved to LAST Position)
        if ($feeStructures->where('payment_mode', 'global')->isNotEmpty()) {
            $tabs[] = [
                'id' => 'global', 
                'label' => __('finance.annual_fee'),
                'description' => __('finance.tab_info_global')
            ];
        }
        
        // 2. Fetch Students & Their Invoices
        $allStudents = StudentEnrollment::with('student.invoices.items.feeStructure')
            ->where('class_section_id', $id)
            ->where('status', 'active')
            ->get();

        // 3. Map Student Status per Tab
        $tabData = [];

        foreach ($tabs as $tab) {
            $filteredStudents = [];

            foreach ($allStudents as $enrollment) {
                $student = $enrollment->student;
                $studentMode = $student->payment_mode ?? 'installment'; 

                // --- FILTER LOGIC (Except for Summary) ---
                if ($tab['id'] !== 'summary') {
                    if ($tab['id'] === 'global' && $studentMode !== 'global') continue; 
                    if (str_starts_with($tab['id'], 'inst_') && $studentMode !== 'installment') continue;
                }

                $status = 'N/A';
                $style = 'secondary';
                $label = 'N/A'; 
                $paidAmount = 0;
                $dueAmount = 0;
                $matchingInvoice = null;
                $hasInvoice = false;

                // --- Calculate Amounts ---
                if ($tab['id'] === 'summary') {
                    // For Summary: Sum of ALL invoices for this student in this session
                    // We assume invoices are loaded eager-ly via 'student.invoices'
                    // but we should filter by academic session if needed. 
                    // Assuming $student->invoices contains all invoices for the student.
                    // Ideally filter by current academic session.
                    
                    // Simple sum for demonstration (refine with session filter if needed)
                    $paidAmount = $student->invoices->sum('paid_amount');
                    $totalAmount = $student->invoices->sum('total_amount');
                    $dueAmount = $totalAmount - $paidAmount;
                    
                    if ($student->invoices->isNotEmpty()) {
                        $hasInvoice = true;
                        // Determine Overall Status
                        if ($dueAmount <= 0.01 && $totalAmount > 0) {
                            $style = 'success'; $label = __('finance.paid');
                        } elseif ($paidAmount > 0) {
                            $style = 'warning'; $label = __('finance.status_partial');
                        } elseif ($totalAmount > 0) {
                            $style = 'danger'; $label = __('finance.status_unpaid');
                        } else {
                            $style = 'secondary'; $label = '-';
                        }
                    }

                } else {
                    // Standard Logic for Specific Tabs
                    if ($tab['id'] === 'global') {
                        $matchingInvoice = $student->invoices->first(function($inv) {
                            return $inv->items->contains(function($item) {
                                return $item->feeStructure && $item->feeStructure->payment_mode === 'global';
                            });
                        });
                    } else {
                        $order = $tab['order'];
                        $matchingInvoice = $student->invoices->first(function($inv) use ($order) {
                            return $inv->items->contains(function($item) use ($order) {
                                return $item->feeStructure && $item->feeStructure->installment_order == $order;
                            });
                        });
                    }

                    if ($matchingInvoice) {
                        $hasInvoice = true;
                        $rawStatus = ucfirst($matchingInvoice->status); 
                        $paidAmount = $matchingInvoice->paid_amount;
                        $dueAmount = $matchingInvoice->total_amount - $matchingInvoice->paid_amount;

                        if ($rawStatus === 'Paid') { $style = 'success'; $label = __('finance.paid'); } 
                        elseif ($rawStatus === 'Partial') { $style = 'warning'; $label = __('finance.status_partial') ?? 'Partial'; } 
                        elseif ($rawStatus === 'Unpaid') { $style = 'danger'; $label = __('finance.status_unpaid') ?? 'Unpaid'; } 
                        elseif ($rawStatus === 'Overdue') { $style = 'dark'; $label = __('finance.status_overdue') ?? 'Overdue'; } 
                        else { $label = $rawStatus; }
                    }
                }

                $statusObj = [
                    'label' => $label, 
                    'style' => $style,
                    'paid' => CurrencySymbol::default() . ' ' . number_format($paidAmount, 2),
                    'due' => CurrencySymbol::default() . ' ' . number_format($dueAmount, 2),
                    'has_invoice' => $hasInvoice
                ];

                $filteredStudents[] = [
                    'student' => [
                        'id' => $student->id,
                        'name' => $student->full_name,
                        'photo' => $student->student_photo,
                        'admission_no' => $student->admission_number,
                    ],
                    'status' => $statusObj
                ];
            }
            
            $tabData[$tab['id']] = $filteredStudents;
        }

        return response()->json([
            'tabs' => $tabs,
            'students_by_tab' => $tabData 
        ]);
    }

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