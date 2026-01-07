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
        $this->setPageTitle('Financial Overview');
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
                    return $row->name . ' (' . ($row->gradeLevel->name ?? '-') . ')';
                })
                ->addColumn('students_count', function($row){
                    // Count active enrollments
                    return $row->enrollments()->where('status', 'active')->count();
                })
                ->addColumn('total_invoiced', function($row){
                    // Sum invoices for students currently in this class
                    // Note: Ideally, invoices should be linked to class_id, but usually linked to student.
                    // We sum invoices generated for students while they are in this class (snapshot approximation)
                    // Precise way: Invoice has `academic_session_id`. We filter by current session.
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
                    // The button that triggers the bottom view
                    return '<button type="button" class="btn btn-primary btn-sm shadow btn-rounded view-class-btn" data-id="'.$row->id.'" data-name="'.$row->name.'">
                                <i class="fa fa-eye me-1"></i> View Details
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
        
        // 1. Identify Installments / Fee Groups
        // We look at fee structures assigned to this Grade/Class to determine tabs
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
            $tabs[] = ['id' => 'global', 'label' => 'Annual / Global Fees'];
        }

        // B. Installment Tabs
        $installments = $feeStructures->where('payment_mode', 'installment')
            ->groupBy('installment_order')
            ->sortKeys();

        foreach ($installments as $order => $fees) {
            $label = $fees->first()->name; // Use first fee name or "Installment X"
            // If multiple fees have same order, maybe just "Installment X"
            if ($fees->count() > 1) $label = "Installment $order";
            
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
                    'invoices' => $enrollment->student->invoices // Eager loaded
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
                $status = 'N/A'; // Not Invoiced
                $style = 'secondary';
                
                // Logic: Find an invoice for this student that matches the Tab's Fee Structure
                $matchingInvoice = null;

                if ($tab['id'] === 'global') {
                    $matchingInvoice = $student['invoices']->first(function($inv) {
                        return $inv->items->contains(function($item) {
                            return $item->feeStructure && $item->feeStructure->payment_mode === 'global';
                        });
                    });
                } else {
                    // Installment Tab
                    $order = $tab['order'];
                    $matchingInvoice = $student['invoices']->first(function($inv) use ($order) {
                        return $inv->items->contains(function($item) use ($order) {
                            return $item->feeStructure && $item->feeStructure->installment_order == $order;
                        });
                    });
                }

                if ($matchingInvoice) {
                    $status = ucfirst($matchingInvoice->status); // paid, partial, unpaid
                    if ($status === 'Paid') $style = 'success';
                    elseif ($status === 'Partial') $style = 'warning';
                    elseif ($status === 'Unpaid') $style = 'danger';
                    elseif ($status === 'Overdue') $style = 'dark';
                }

                $row['statuses'][$tab['id']] = ['label' => $status, 'style' => $style];
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
        // Simplified Logic: Sum invoices for students currently in this class
        // For production, ensure invoices belong to the correct academic session
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