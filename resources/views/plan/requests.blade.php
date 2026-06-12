@extends('layout.layout')

@section('content')
<style>
    .preq-hero { border-radius:18px; background:linear-gradient(120deg,#0b2a6b 0%,#13386e 50%,#2563eb 100%); position:relative; overflow:hidden; }
    .preq-card { background:#fff; border:1px solid #eef0f4; border-radius:16px; }
    .preq-filter { display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border-radius:999px; font-size:.82rem; font-weight:600; text-decoration:none; border:1px solid #e5e7eb; color:#374151; }
    .preq-filter.active { background:#0b2a6b; color:#fff; border-color:#0b2a6b; }
    .preq-table thead th { font-size:.78rem; text-transform:uppercase; letter-spacing:.04em; color:#6b7280; border-bottom:1px solid #eef0f4; background:#fafbfc; }
    [data-theme="dark"] .preq-card { background:#1e2746; border-color:#2b365c; color:#e8ebf5; }
</style>
<div class="content-body">
    <div class="container-fluid">

        <div class="row mb-4">
            <div class="col-12">
                <div class="preq-hero shadow-sm">
                    <div class="d-flex flex-wrap justify-content-between align-items-center p-4" style="position:relative; z-index:1;">
                        <div>
                            <h3 class="text-white fw-bold mb-1">{{ __('plan.upgrade_requests') }}</h3>
                            <p class="mb-0 text-white opacity-75">{{ __('plan.upgrade_requests_subtitle') }}</p>
                        </div>
                        <i class="la la-arrow-up d-none d-md-block" style="font-size:3.4rem; color:rgba(255,255,255,.35);"></i>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif

        <div class="d-flex flex-wrap gap-2 mb-3">
            @php $filters = ['' => 'all', 'pending'=>'pending', 'contacted'=>'contacted', 'approved'=>'approved', 'rejected'=>'rejected']; @endphp
            @foreach($filters as $key => $label)
                <a href="{{ route('plan.requests', $key ? ['status' => $key] : []) }}"
                   class="preq-filter {{ (string)$status === (string)$key ? 'active' : '' }}">
                    {{ __('plan.filter_' . $label) }}
                    <span class="badge bg-light text-dark">{{ $counts[$label] ?? 0 }}</span>
                </a>
            @endforeach
        </div>

        <div class="preq-card shadow-sm">
            <div class="table-responsive">
                <table class="table preq-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('plan.school') }}</th>
                            <th>{{ __('plan.requested_by') }}</th>
                            <th>{{ __('plan.current_plan') }}</th>
                            <th>{{ __('plan.requested_plan') }}</th>
                            <th>{{ __('plan.message') }}</th>
                            <th>{{ __('plan.status') }}</th>
                            <th class="text-end">{{ __('plan.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $req)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $req->institution->name ?? '—' }}</div>
                                    <small class="text-muted">{{ $req->created_at->diffForHumans() }}</small>
                                </td>
                                <td>{{ $req->user->name ?? '—' }}</td>
                                <td>{{ $req->currentPackage->name ?? '—' }}</td>
                                <td>{{ $req->requestedPackage->name ?? __('plan.any_higher_plan') }}</td>
                                <td style="max-width:220px;"><small>{{ $req->message ?: '—' }}</small></td>
                                <td>
                                    @php $badge = ['pending'=>'warning','contacted'=>'info','approved'=>'success','rejected'=>'danger'][$req->status] ?? 'secondary'; @endphp
                                    <span class="badge bg-{{ $badge }}">{{ __('plan.status_' . $req->status) }}</span>
                                </td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">{{ __('plan.set_status') }}</button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @foreach(['contacted','approved','rejected','pending'] as $st)
                                                <li>
                                                    <form action="{{ route('plan.requests.handle', $req->id) }}" method="POST" class="plan-status-form" data-status="{{ $st }}">
                                                        @csrf
                                                        <input type="hidden" name="status" value="{{ $st }}">
                                                        <button type="submit" class="dropdown-item">{{ __('plan.status_' . $st) }}</button>
                                                    </form>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">{{ __('plan.no_requests') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($requests->hasPages())
                <div class="p-3">{{ $requests->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.plan-status-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const status = form.dataset.status;
            if (status !== 'approved' && status !== 'rejected') {
                return;
            }
            e.preventDefault();
            const isApprove = status === 'approved';
            Swal.fire({
                title: isApprove ? @json(__('plan.confirm_approve_title')) : @json(__('plan.confirm_reject_title')),
                text: isApprove ? @json(__('plan.confirm_approve_text')) : @json(__('plan.confirm_reject_text')),
                icon: isApprove ? 'question' : 'warning',
                showCancelButton: true,
                confirmButtonColor: isApprove ? '#059669' : '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: isApprove ? @json(__('plan.confirm_approve_yes')) : @json(__('plan.confirm_reject_yes')),
                cancelButtonText: @json(__('subscription.cancel'))
            }).then(function (result) {
                if (result.isConfirmed) form.submit();
            });
        });
    });
});
</script>
@endsection
