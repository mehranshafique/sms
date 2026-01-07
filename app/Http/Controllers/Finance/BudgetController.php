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
        // FIX: Authorize Resource to protect methods
        $this->authorizeResource(Budget::class, 'budget');
        $this->setPageTitle(__('budget.page_title'));
    }

    // --- BUDGET CATEGORIES ---
    public function categories(Request $request)
    {
        // FIX: Ensure user has permission to view categories
        $this->authorize('viewAny', Budget::class);

        $institutionId = $this->getInstitutionId();
        
        if ($request->ajax()) {
            $data = BudgetCategory::where('institution_id', $institutionId)->latest();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function($row){
                    // Just a placeholder edit button for now
                    return '<button class="btn btn-primary btn-xs edit-cat shadow" data-id="'.$row->id.'" data-name="'.$row->name.'"><i class="fa fa-pencil"></i></button>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('finance.budgets.categories');
    }

    public function storeCategory(Request $request)
    {
        // FIX: Check Create Permission
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
        
        // Get Current Session
        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();

        if ($request->ajax()) {
            $data = Budget::with('category')
                ->where('institution_id', $institutionId)
                ->where('academic_session_id', $session->id ?? 0);

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('allocated_amount', fn($row) => number_format($row->allocated_amount, 2))
                ->editColumn('spent_amount', fn($row) => number_format($row->spent_amount, 2))
                ->addColumn('remaining', fn($row) => number_format($row->allocated_amount - $row->spent_amount, 2))
                ->addColumn('action', function($row){
                    return '<button class="btn btn-info btn-xs shadow request-fund-btn" data-id="'.$row->id.'" data-cat="'.$row->category->name.'"><i class="fa fa-plus me-1"></i> '.__('budget.request_fund').'</button>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $categories = BudgetCategory::where('institution_id', $institutionId)->get();
        return view('finance.budgets.index', compact('categories', 'session'));
    }

    public function store(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->firstOrFail();

        $request->validate([
            'budget_category_id' => 'required|exists:budget_categories,id',
            'allocated_amount' => 'required|numeric|min:0',
        ]);

        Budget::updateOrCreate(
            [
                'institution_id' => $institutionId,
                'academic_session_id' => $session->id,
                'budget_category_id' => $request->budget_category_id
            ],
            [
                'allocated_amount' => $request->allocated_amount
            ]
        );

        return response()->json(['message' => __('budget.success_allocated')]);
    }

    // --- FUND REQUESTS ---
    public function storeFundRequest(Request $request)
    {
        // Any authenticated user with access to the budget module can likely request funds
        // But let's check general create permissions
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
                ->editColumn('created_at', function($row){
                    return $row->created_at->format('d M, Y');
                })
                ->addColumn('action', function($row){
                    if($row->status == 'pending' && Auth::user()->can('approve_funds')) {
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
        // Permission check
        if (!Auth::user()->can('approve_funds')) {
             abort(403);
        }

        $fundRequest = FundRequest::findOrFail($id);
        if($fundRequest->status != 'pending') abort(403, 'Request already processed');

        DB::transaction(function() use ($fundRequest, $request) {
            $fundRequest->update([
                'status' => $request->status, // approved/rejected
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