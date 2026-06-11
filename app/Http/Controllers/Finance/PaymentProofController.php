<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\BaseController;
use App\Models\PaymentProofSubmission;
use App\Services\PaymentProofService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class PaymentProofController extends BaseController
{
    public function __construct(
        protected PaymentProofService $paymentProofService
    ) {
        $this->middleware('auth');
        $this->middleware(PermissionMiddleware::class . ':payment.create')->only(['index', 'approve', 'reject']);
        $this->setPageTitle(__('payment_proof.page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        if ($request->ajax()) {
            $query = PaymentProofSubmission::with(['invoice.student', 'reviewer'])
                ->when($institutionId, fn ($q) => $q->where('institution_id', $institutionId));

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            return DataTables::of($query)
                ->addColumn('invoice_no', fn ($row) => $row->invoice?->invoice_number ?? '-')
                ->addColumn('student', fn ($row) => $row->invoice?->student?->full_name ?? '-')
                ->addColumn('amount', fn ($row) => number_format((float) $row->amount, 2))
                ->addColumn('method_label', fn ($row) => __('payment.' . $row->method))
                ->addColumn('paid_at_fmt', fn ($row) => $row->paid_at?->format('d M Y H:i'))
                ->addColumn('receipt', function ($row) {
                    if (!$row->receipt_path) {
                        return '-';
                    }

                    return '<a href="' . asset('storage/' . $row->receipt_path) . '" target="_blank" class="btn btn-xs btn-info"><i class="fa fa-file"></i></a>';
                })
                ->addColumn('status_badge', function ($row) {
                    $class = match ($row->status) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    };

                    return '<span class="badge badge-' . $class . '">' . ucfirst($row->status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    if (!$row->isPending()) {
                        return '-';
                    }

                    return '<div class="d-flex gap-1">'
                        . '<button type="button" class="btn btn-success btn-xs approve-proof" data-id="' . $row->id . '"><i class="fa fa-check"></i></button>'
                        . '<button type="button" class="btn btn-danger btn-xs reject-proof" data-id="' . $row->id . '"><i class="fa fa-times"></i></button>'
                        . '</div>';
                })
                ->rawColumns(['receipt', 'status_badge', 'action'])
                ->make(true);
        }

        return view('finance.payment_proofs.index');
    }

    public function approve(PaymentProofSubmission $proof)
    {
        $this->assertInstitution($proof);
        $this->paymentProofService->approve($proof, Auth::id());

        return response()->json(['message' => __('payment_proof.approved')]);
    }

    public function reject(Request $request, PaymentProofSubmission $proof)
    {
        $this->assertInstitution($proof);
        $request->validate(['reason' => 'nullable|string|max:255']);
        $this->paymentProofService->reject($proof, Auth::id(), $request->reason);

        return response()->json(['message' => __('payment_proof.rejected')]);
    }

    private function assertInstitution(PaymentProofSubmission $proof): void
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $proof->institution_id != $institutionId) {
            abort(403);
        }
    }
}
