<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\BaseController;
use App\Models\Budget;
use App\Models\BudgetCategory;
use App\Models\FundRequest;
use App\Models\AcademicSession;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BudgetController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->authorizeResource(Budget::class, 'budget');
        $this->setPageTitle(__('budget.page_title'));
    }

    // --- BUDGET CATEGORIES ---
    public function categories(Request $request)
    {
        $this->authorize('viewAny', Budget::class);
        $institutionId = $this->getInstitutionId();
        
        if ($request->ajax()) {
            $data = BudgetCategory::where('institution_id', $institutionId)->latest();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function($row){
                    return '<button class="btn btn-primary btn-xs edit-cat shadow" data-id="'.$row->id.'" data-name="'.$row->name.'"><i class="fa fa-pencil"></i></button>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('finance.budgets.categories');
    }

    public function storeCategory(Request $request)
    {
        $this->authorize('create', Budget::class);
        $request->validate(['name' => 'required|string|max:255']);
        
        BudgetCategory::create([
            'institution_id' => $this->getInstitutionId(),
            'name' => $request->name,
            'description' => $request->description
        ]);
        return response()->json(['message' => __('budget.success_category_created')]);
    }

    // --- BUDGET ALLOCATION ---
    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();

        if ($request->ajax()) {
            $data = Budget::with('category')
                ->where('institution_id', $institutionId)
                ->where('academic_session_id', $session->id ?? 0)
                ->orderBy('budget_category_id')
                ->orderByDesc('created_at');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('period_info', function($row){
                    $label = '<span class="fw-bold text-primary">'.$row->period_label.'</span>';
                    if($row->start_date && $row->end_date) {
                        $label .= '<br><small class="text-muted">'.$row->start_date->format('d M').' - '.$row->end_date->format('d M, Y').'</small>';
                    }
                    return $label;
                })
                ->editColumn('allocated_amount', fn($row) => number_format($row->allocated_amount, 2))
                ->editColumn('spent_amount', fn($row) => number_format($row->spent_amount, 2))
                ->addColumn('remaining', function($row){
                    $val = $row->allocated_amount - $row->spent_amount;
                    $color = $val < 0 ? 'text-danger' : 'text-success';
                    return '<span class="'.$color.' fw-bold">'.number_format($val, 2).'</span>';
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex">';
                    
                    // Edit Button
                    if(auth()->user()->can('update', $row)){
                        $btn .= '<button class="btn btn-primary btn-xs shadow me-1 edit-budget-btn" data-id="'.$row->id.'"><i class="fa fa-pencil"></i></button>';
                    }

                    // Request Fund Button
                    $btn .= '<button class="btn btn-info btn-xs shadow request-fund-btn" data-id="'.$row->id.'" data-cat="'.$row->category->name.' ('.$row->period_label.')"><i class="fa fa-plus me-1"></i> '.__('budget.request_fund').'</button>';
                    
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['period_info', 'remaining', 'action'])
                ->make(true);
        }

        // Global Financial View (Aggregated Stats for Session)
        $globalStats = [
            'allocated' => 0,
            'spent' => 0,
            'remaining' => 0
        ];

        if($session) {
            $globalStats['allocated'] = Budget::where('institution_id', $institutionId)->where('academic_session_id', $session->id)->sum('allocated_amount');
            $globalStats['spent'] = Budget::where('institution_id', $institutionId)->where('academic_session_id', $session->id)->sum('spent_amount');
            $globalStats['remaining'] = $globalStats['allocated'] - $globalStats['spent'];
        }

        $categories = BudgetCategory::where('institution_id', $institutionId)->get();
        return view('finance.budgets.index', compact('categories', 'session', 'globalStats'));
    }

    public function store(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->firstOrFail();

        $request->validate([
            'budget_category_id' => 'required|exists:budget_categories,id',
            'allocated_amount' => 'required|numeric|min:0',
            'period_name' => 'nullable|string|max:100',
            'start_date' => 'nullable|date|after_or_equal:today', // Enforce Future Date
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        Budget::create([
            'institution_id' => $institutionId,
            'academic_session_id' => $session->id,
            'budget_category_id' => $request->budget_category_id,
            'allocated_amount' => $request->allocated_amount,
            'period_name' => $request->period_name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'spent_amount' => 0
        ]);

        return response()->json(['message' => __('budget.success_allocated')]);
    }

    public function edit(Budget $budget)
    {
        // Return JSON for Modal Population
        $this->authorize('update', $budget);
        
        return response()->json([
            'id' => $budget->id,
            'budget_category_id' => $budget->budget_category_id,
            'allocated_amount' => $budget->allocated_amount,
            'period_name' => $budget->period_name,
            'start_date' => $budget->start_date ? $budget->start_date->format('Y-m-d') : '',
            'end_date' => $budget->end_date ? $budget->end_date->format('Y-m-d') : '',
        ]);
    }

    public function update(Request $request, Budget $budget)
    {
        $this->authorize('update', $budget);

        $request->validate([
            'allocated_amount' => 'required|numeric|min:0',
            'period_name' => 'nullable|string|max:100',
            'start_date' => 'nullable|date', 
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Accounting Rule: Cannot reduce allocation below what is already spent
        if ($request->allocated_amount < $budget->spent_amount) {
            return response()->json([
                'message' => __('budget.error_allocation_less_than_spent', ['spent' => number_format($budget->spent_amount, 2)])
            ], 422);
        }

        $budget->update([
            'allocated_amount' => $request->allocated_amount,
            'period_name' => $request->period_name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return response()->json(['message' => __('budget.success_update')]);
    }

    // --- FUND REQUESTS ---
    public function storeFundRequest(Request $request)
    {
        $this->authorize('create', Budget::class);

        $request->validate([
            'budget_id' => 'required|exists:budgets,id',
            'amount' => 'required|numeric|min:1',
            'title' => 'required|string|max:255',
        ]);

        $budget = Budget::findOrFail($request->budget_id);
        
        // Check Balance
        $remaining = $budget->allocated_amount - $budget->spent_amount;
        if ($request->amount > $remaining) {
            return response()->json(['message' => __('budget.insufficient_funds')], 422);
        }

        FundRequest::create([
            'institution_id' => $this->getInstitutionId(),
            'budget_id' => $budget->id,
            'requested_by' => Auth::id(),
            'amount' => $request->amount,
            'title' => $request->title,
            'description' => $request->description,
            'status' => 'pending'
        ]);

        return response()->json(['message' => __('budget.success_request_submitted')]);
    }

    public function fundRequests(Request $request)
    {
        $this->authorize('viewAny', Budget::class);
        $institutionId = $this->getInstitutionId();
        
        if ($request->ajax()) {
            $data = FundRequest::with(['budget.category', 'requester'])
                ->where('institution_id', $institutionId)
                ->latest();

            return DataTables::of($data)
                ->editColumn('status', function($row){
                    $badges = ['pending'=>'warning', 'approved'=>'success', 'rejected'=>'danger'];
                    $statusKey = 'budget.' . $row->status;
                    return '<span class="badge badge-'.$badges[$row->status].'">'.__($statusKey).'</span>';
                })
                ->addColumn('requester_name', function($row){
                    return $row->requester->name ?? 'Unknown';
                })
                ->addColumn('category_name', function($row){
                    return $row->budget->category->name ?? '-';
                })
                ->addColumn('period', function($row){
                    return $row->budget->period_label;
                })
                ->editColumn('created_at', function($row){
                    return $row->created_at->format('d M, Y');
                })
                ->addColumn('action', function($row){
                    if($row->status == 'pending' && Auth::user()->can('budget.approve_funds')) {
                        return '
                        <div class="d-flex">
                            <button class="btn btn-success btn-xs me-1 approve-btn" data-id="'.$row->id.'"><i class="fa fa-check"></i></button>
                            <button class="btn btn-danger btn-xs reject-btn" data-id="'.$row->id.'"><i class="fa fa-times"></i></button>
                        </div>';
                    }
                    return '';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        
        return view('finance.budgets.requests');
    }

    public function approveFundRequest(Request $request, $id)
    {
        if (!Auth::user()->can('budget.approve_funds')) {
             abort(403);
        }

        $fundRequest = FundRequest::findOrFail($id);
        if($fundRequest->status != 'pending') abort(403, 'Request already processed');

        DB::transaction(function() use ($fundRequest, $request) {
            $fundRequest->update([
                'status' => $request->status, 
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'rejection_reason' => $request->rejection_reason
            ]);

            if ($request->status == 'approved') {
                $budget = $fundRequest->budget;
                $budget->increment('spent_amount', $fundRequest->amount);
            }
        });

        $msg = $request->status == 'approved' ? __('budget.success_approved') : __('budget.success_rejected');
        return response()->json(['message' => $msg]);
    }
}