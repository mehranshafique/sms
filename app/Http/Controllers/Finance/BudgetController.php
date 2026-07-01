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
use Illuminate\Support\Str; 
use App\Services\NotificationService; 
use App\Enums\RoleEnum; 
use App\Models\Institution; 
use App\Models\User;
use App\Events\BudgetDeducted;
use Illuminate\Support\Facades\Cache;

class BudgetController extends BaseController
{
    protected $notificationService;
    
    public function __construct(NotificationService $notificationService)
    {
        $this->middleware('auth');
        $this->authorizeResource(Budget::class, 'budget');
        $this->setPageTitle(__('budget.page_title'));
        $this->notificationService = $notificationService;
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

        // Fetch the current session and name it $session globally within the method 
        // to prevent the undefined variable error later in the view or callbacks
        $currentSessionQuery = AcademicSession::where('is_current', true);
        if ($institutionId) {
            $currentSessionQuery->where('institution_id', $institutionId);
        }
        $session = $currentSessionQuery->first();

        if ($request->ajax()) {
            // Restored relationships and added table prefixes to fix ambiguous column errors
            $data = Budget::with(['category', 'academicSession'])->select('budgets.*');
            
            if ($institutionId) {
                $data->where('budgets.institution_id', $institutionId);
            }

            // Restored the original Academic Session filter logic
            if ($request->has('academic_session_id') && $request->filled('academic_session_id')) {
                $data->where('budgets.academic_session_id', $request->academic_session_id);
            } elseif ($session) {
                // Show current active session budget if no filter is applied
                $data->where('budgets.academic_session_id', $session->id);
            }
            
            $data->latest('budgets.created_at');

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
        $staffUsers = User::whereHas('staff', fn ($q) => $q->where('institution_id', $institutionId))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('finance.budgets.index', compact('categories', 'session', 'globalStats', 'staffUsers'));
    }

    public function store(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->firstOrFail();

        $request->validate([
            'budget_category_id' => 'required|exists:budget_categories,id',
            'allocated_amount' => 'required|numeric|min:0',
            'responsible_user_id' => 'nullable|exists:users,id',
            'notify_user_ids' => 'nullable|array',
            'notify_user_ids.*' => 'exists:users,id',
            'period_name' => 'nullable|string|max:100',
            'start_date' => 'nullable|date|after_or_equal:today', // Enforce Future Date
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $budget = Budget::create([
            'institution_id' => $institutionId,
            'academic_session_id' => $session->id,
            'budget_category_id' => $request->budget_category_id,
            'responsible_user_id' => $request->responsible_user_id ?? ($request->notify_user_ids[0] ?? null),
            'allocated_amount' => $request->allocated_amount,
            'period_name' => $request->period_name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'spent_amount' => 0
        ]);

        if ($request->filled('notify_user_ids')) {
            $budget->notificationRecipients()->sync($request->notify_user_ids);
        } elseif ($request->responsible_user_id) {
            $budget->notificationRecipients()->sync([$request->responsible_user_id]);
        }

        return response()->json(['message' => __('budget.success_allocated')]);
    }

    public function edit(Budget $budget)
    {
        // Return JSON for Modal Population
        $this->authorize('update', $budget);
        
        return response()->json([
            'id' => $budget->id,
            'budget_category_id' => $budget->budget_category_id,
            'responsible_user_id' => $budget->responsible_user_id,
            'notify_user_ids' => $budget->notificationRecipients()->pluck('users.id'),
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
            'responsible_user_id' => 'nullable|exists:users,id',
            'notify_user_ids' => 'nullable|array',
            'notify_user_ids.*' => 'exists:users,id',
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
            'responsible_user_id' => $request->responsible_user_id ?? ($request->notify_user_ids[0] ?? $budget->responsible_user_id),
            'period_name' => $request->period_name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        if ($request->has('notify_user_ids')) {
            $budget->notificationRecipients()->sync($request->notify_user_ids ?? []);
        }

        return response()->json(['message' => __('budget.success_update')]);
    }

    // --- FUND REQUESTS ---
    public function fundRequests(Request $request)
    {
        $user = Auth::user();
        $isHeadOfficer = $user->hasRole(RoleEnum::HEAD_OFFICER->value) || $user->hasRole(RoleEnum::SUPER_ADMIN->value);
        $institutionId = $this->getInstitutionId();

        // Scope institutions based on hierarchy
        $allowedInstitutionIds = [];
        if ($isHeadOfficer && $user->institutes) {
            $allowedInstitutionIds = $user->institutes->pluck('id')->toArray();
        } elseif ($institutionId) {
            $allowedInstitutionIds = [$institutionId];
        }

        if ($request->ajax()) {
            $query = FundRequest::with(['budget.category', 'requester', 'institution'])
                ->select('fund_requests.*')->latest();

            if (!empty($allowedInstitutionIds)) {
                $query->whereIn('fund_requests.institution_id', $allowedInstitutionIds);
            }

            return DataTables::of($query)
                ->addColumn('ticket_number', fn($row) => '<span class="text-primary fw-bold">REQ-'.str_pad($row->id, 6, '0', STR_PAD_LEFT).'</span>')
                ->addColumn('branch', fn($row) => $row->institution->name ?? 'N/A')
                ->editColumn('created_at', fn($row) => $row->created_at->format('d M, Y H:i'))
                ->addColumn('request_title', fn($row) => $row->title)
                ->addColumn('category', fn($row) => $row->budget->category->name ?? 'N/A')
                ->editColumn('amount', fn($row) => number_format($row->amount, 2))
                ->addColumn('requested_by', fn($row) => $row->requester->name ?? 'N/A')
                ->editColumn('status', function($row){
                    $badges = ['pending' => 'badge-warning', 'approved' => 'badge-success', 'rejected' => 'badge-danger'];
                    return '<span class="badge '.($badges[$row->status] ?? 'badge-secondary').'">'.ucfirst($row->status).'</span>';
                })
                ->addColumn('action', function($row) use ($user) {
                    if ($row->status == 'pending' && $user->can('budget.approve_funds')) {
                        return '<div class="d-flex justify-content-end">
                            <button class="btn btn-success btn-xs shadow approve-btn me-1" data-id="'.$row->id.'" title="Approve"><i class="fa fa-check"></i></button>
                            <button class="btn btn-danger btn-xs shadow reject-btn" data-id="'.$row->id.'" title="Reject"><i class="fa fa-times"></i></button>
                        </div>';
                    }
                    return '';
                })
                ->rawColumns(['ticket_number', 'status', 'action'])
                ->make(true);
        }

        // Summary Metrics for HeadOff & Admins
        $statsQuery = FundRequest::query();
        if (!empty($allowedInstitutionIds)) {
            $statsQuery->whereIn('institution_id', $allowedInstitutionIds);
        }

        $totalPending = (clone $statsQuery)->where('status', 'pending')->count();
        $totalProcessed = (clone $statsQuery)->whereIn('status', ['approved', 'rejected'])->count();
        $totalRequestedAmt = (clone $statsQuery)->sum('amount');
        $totalApprovedAmt = (clone $statsQuery)->where('status', 'approved')->sum('amount');
        
        return view('finance.budgets.requests', compact('totalPending', 'totalProcessed', 'totalRequestedAmt', 'totalApprovedAmt', 'isHeadOfficer'));
    }

    public function storeFundRequest(Request $request)
    {
        $this->authorize('create', FundRequest::class);

        $request->validate([
            'budget_id' => 'required|exists:budgets,id',
            'amount' => 'required|numeric|min:0.01',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $institutionId = $this->getInstitutionId();
        $budget = Budget::when($institutionId, fn ($q) => $q->where('institution_id', $institutionId))
            ->findOrFail($request->budget_id);
        $institutionId = $budget->institution_id;
        
        // Check Balance
        $remaining = $budget->allocated_amount - $budget->spent_amount;
        if ($request->amount > $remaining) {
            // Support both AJAX JSON response and standard form redirects
            if($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => __('budget.insufficient_funds')], 422);
            }
            return redirect()->back()->with('error', __('budget.insufficient_funds'));
        }

        $fundRequest = FundRequest::create([
            'institution_id' => $institutionId,
            'budget_id' => $budget->id,
            'requested_by' => Auth::id(),
            'amount' => $request->amount,
            'title' => $request->title,
            'description' => $request->description,
            'status' => 'pending'
        ]);

        // Generate dynamic ticket number for the notification
        $fundRequest->ticket_number = 'REQ-' . str_pad($fundRequest->id, 6, '0', STR_PAD_LEFT);

        // Send Initial Notification to the Requester
        $user = Auth::user();
        $phone = $user->staff->phone ?? $user->phone ?? null; 
        if ($phone) {
            $this->notificationService->sendFundRequestConfirmation($fundRequest, $phone, $user->name, $institutionId);
        }

        app(\App\Services\InAppNotificationService::class)->notifyFundRequestSubmitted($fundRequest);

        if($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => __('budget.success_request_submitted')]);
        }
        
        return redirect()->back()->with('success', __('budget.success_request_submitted'));
    }

    // --- FINANCE OVERVIEW (HEADOFF DASHBOARD) ---
    public function financeOverview()
    {
        $this->authorizeAdminOrAnyPermission(['budget.view', 'budget.viewAny']);
        $user = Auth::user();
        $isHeadOfficer = $user->hasRole(RoleEnum::HEAD_OFFICER->value) || $user->hasRole(RoleEnum::SUPER_ADMIN->value);
        $institutionId = $this->getInstitutionId();
        $this->setPageTitle(__('budget.finance_overview'));
        
        $allowedInstitutionIds = [];
        if ($isHeadOfficer && $user->institutes) {
            $allowedInstitutionIds = $user->institutes->pluck('id')->toArray();
        } elseif ($institutionId) {
            $allowedInstitutionIds = [$institutionId];
        }

        // Global Aggregation
        $enrollments = \App\Models\StudentEnrollment::where('status', 'active');
        $invoices = \App\Models\Invoice::query();

        if (!empty($allowedInstitutionIds)) {
            $enrollments->whereIn('institution_id', $allowedInstitutionIds);
            $invoices->whereIn('institution_id', $allowedInstitutionIds);
        }

        $totalStudents = $enrollments->count();
        $totalExpected = $invoices->sum('total_amount');
        $totalPaid = $invoices->sum('paid_amount');
        $remainingBalance = max(0, $totalExpected - $totalPaid);

        // Breakdown by school
        $schoolBreakdown = [];
        if ($isHeadOfficer) {
            $schools = Institution::whereIn('id', $allowedInstitutionIds)->get();
            foreach ($schools as $school) {
                $schoolExpected = \App\Models\Invoice::where('institution_id', $school->id)->sum('total_amount');
                $schoolPaid = \App\Models\Invoice::where('institution_id', $school->id)->sum('paid_amount');
                
                $schoolBreakdown[] = [
                    'name' => $school->name,
                    'students' => \App\Models\StudentEnrollment::where('status', 'active')->where('institution_id', $school->id)->count(),
                    'expected' => $schoolExpected,
                    'paid' => $schoolPaid,
                    'remaining' => max(0, $schoolExpected - $schoolPaid)
                ];
            }
        }

        return view('finance.budgets.overview', compact('totalStudents', 'totalExpected', 'totalPaid', 'remainingBalance', 'isHeadOfficer', 'schoolBreakdown'));
    }

    public function approveFundRequest(Request $request, $id)
    {
        if (!Auth::user()->can('budget.approve_funds')) {
             abort(403);
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'nullable|string|max:500',
            'password' => 'required_if:status,approved|string|nullable',
        ]);

        if ($request->status === 'approved' && !\Illuminate\Support\Facades\Hash::check((string) $request->password, Auth::user()->password)) {
            return response()->json(['message' => __('budget.invalid_password')], 422);
        }

        $institutionId = $this->getInstitutionId();
        $fundRequest = FundRequest::when($institutionId, fn ($q) => $q->where('institution_id', $institutionId))
            ->findOrFail($id);
        if($fundRequest->status != 'pending') abort(403, __('budget.request_already_processed') ?? 'Request already processed');

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

        // Dynamically assign ticket number for the notification AFTER the update
        // This ensures Laravel's save() doesn't try to persist the virtual column 'ticket_number' to the database
        $fundRequest->ticket_number = 'REQ-' . str_pad($fundRequest->id, 6, '0', STR_PAD_LEFT);

        // Prepare and trigger the Notification explicitly in the controller as requested
        try {
            $this->notificationService->sendFundRequestProcessedNotification($fundRequest, $fundRequest->institution_id);
            app(\App\Services\InAppNotificationService::class)->notifyFundRequestProcessed($fundRequest);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Budget Notification Error: " . $e->getMessage());
        }

        // Keep the event dispatch for broader system reactivity
        BudgetDeducted::dispatch($fundRequest);

        if (class_exists(\App\Services\AuditLogger::class)) {
            \App\Services\AuditLogger::log(
                'fund_request.' . $request->status,
                'Budget',
                'Fund request #' . $fundRequest->id . ' ' . $request->status,
                null,
                ['approved_by' => Auth::id(), 'status' => $request->status]
            );
        }

        $msg = $request->status == 'approved' ? __('budget.success_approved') : __('budget.success_rejected');
        return response()->json(['message' => $msg]);
    }

    /**
     * Mark a Fund Request Notification as Read and clear it from the dashboard counter
     */
    public function markRequestAsRead($id)
    {
        $fundRequest = FundRequest::findOrFail($id);
        
        if ($fundRequest->requested_by == Auth::id()) {
            // Cache the "read" state for 30 days to avoid modifying DB schema
            Cache::put('fund_req_read_'.Auth::id().'_'.$id, true, now()->addDays(30));
        }

        return redirect()->route('budgets.requests');
    }
}