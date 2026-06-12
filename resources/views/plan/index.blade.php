@extends('layout.layout')

@section('content')
<style>
    .plan-hero { border-radius:18px; background:linear-gradient(120deg,#0b2a6b 0%,#13386e 50%,#2563eb 100%); position:relative; overflow:hidden; }
    .plan-hero::after { content:""; position:absolute; right:-40px; top:-60px; width:220px; height:220px; background:rgba(255,255,255,.08); border-radius:50%; }
    .plan-card { background:#fff; border:1px solid #eef0f4; border-radius:16px; }
    .plan-stat { background:#fff; border:1px solid #eef0f4; border-radius:14px; padding:18px; height:100%; }
    .plan-stat__icon { width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; color:#fff; margin-bottom:10px; }
    .plan-pill { display:inline-flex; align-items:center; gap:6px; font-size:.8rem; font-weight:600; padding:4px 12px; border-radius:999px; }
    .plan-pill.active { background:#ecfdf5; color:#059669; }
    .plan-pill.expired { background:#fef2f2; color:#dc2626; }
    .plan-option { border:1px solid #eef0f4; border-radius:14px; padding:18px; height:100%; transition:.15s; }
    .plan-option:hover { border-color:#bfdbfe; box-shadow:0 6px 20px rgba(37,99,235,.08); transform:translateY(-2px); }
    .plan-option__price { font-size:1.6rem; font-weight:700; color:#0b2a6b; }
    [data-theme="dark"] .plan-card, [data-theme="dark"] .plan-stat, [data-theme="dark"] .plan-option { background:#1e2746; border-color:#2b365c; color:#e8ebf5; }
</style>
<div class="content-body">
    <div class="container-fluid">

        {{-- Hero --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="plan-hero shadow-sm">
                    <div class="d-flex flex-wrap justify-content-between align-items-center p-4" style="position:relative; z-index:1;">
                        <div>
                            <span class="text-white opacity-75 d-block mb-1"><i class="la la-crown"></i> {{ __('plan.current_plan') }}</span>
                            <h3 class="text-white fw-bold mb-1">
                                {{ $package->name ?? __('plan.no_plan') }}
                                @if(!empty($isPro))
                                    <span class="badge bg-warning text-dark ms-2 align-middle">PRO</span>
                                @endif
                            </h3>
                            @if($subscription)
                                <span class="plan-pill {{ $subscription->end_date->endOfDay()->isPast() ? 'expired' : 'active' }}">
                                    <i class="la la-circle"></i>
                                    {{ $subscription->end_date->endOfDay()->isPast() ? __('plan.expired') : __('plan.active') }}
                                </span>
                                <span class="text-white opacity-75 ms-2 small">
                                    {{ __('plan.valid_until', ['date' => $subscription->end_date->translatedFormat('d M Y')]) }}
                                </span>
                            @endif
                        </div>
                        <i class="la la-gem d-none d-md-block" style="font-size:3.6rem; color:rgba(255,255,255,.35);"></i>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif

        @if($pendingRequest)
            <div class="alert alert-info d-flex align-items-center gap-2">
                <i class="la la-clock fs-4"></i>
                <div>{{ __('plan.pending_banner') }}</div>
            </div>
        @endif

        {{-- Stats --}}
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6 col-6">
                <div class="plan-stat">
                    <div class="plan-stat__icon" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);"><i class="la la-dollar-sign"></i></div>
                    <div class="text-muted small">{{ __('plan.price') }}</div>
                    <h4 class="fw-bold mb-0">{{ $package ? number_format($package->price, 2) : '—' }}</h4>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 col-6">
                <div class="plan-stat">
                    <div class="plan-stat__icon" style="background:linear-gradient(135deg,#7c3aed,#5b21b6);"><i class="la la-th-large"></i></div>
                    <div class="text-muted small">{{ __('plan.modules_enabled') }}</div>
                    <h4 class="fw-bold mb-0">{{ $enabledCount }} <span class="text-muted fs-6">/ {{ $totalModules }}</span></h4>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 col-6">
                <div class="plan-stat">
                    <div class="plan-stat__icon" style="background:linear-gradient(135deg,#059669,#047857);"><i class="la la-calendar-check"></i></div>
                    <div class="text-muted small">{{ __('plan.days_left') }}</div>
                    <h4 class="fw-bold mb-0">{{ $daysLeft !== null ? $daysLeft : '—' }}</h4>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 col-6">
                <div class="plan-stat">
                    <div class="plan-stat__icon" style="background:linear-gradient(135deg,#d97706,#b45309);"><i class="la la-magic"></i></div>
                    <div class="text-muted small">{{ __('plan.ai_access') }}</div>
                    <h4 class="fw-bold mb-0">
                        @if(!empty($hasAi))
                            {{ __('plan.ai_included') }}
                        @else
                            {{ __('plan.ai_not_included') }}
                        @endif
                    </h4>
                    @if(!empty($hasAi) && empty($aiUsable))
                        <small class="text-warning d-block mt-1">{{ __('plan.ai_platform_disabled') }}</small>
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-3">
            {{-- Available plans --}}
            <div class="col-lg-8">
                <div class="plan-card">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold"><i class="la la-arrow-up text-primary"></i> {{ __('plan.upgrade_options') }}</h6>
                    </div>
                    <div class="p-3">
                        @if($availablePlans->isEmpty())
                            <p class="text-muted text-center py-4 mb-0">{{ __('plan.no_other_plans') }}</p>
                        @else
                            @php $planService = app(\App\Services\PlanContextService::class); @endphp
                            <div class="row g-3">
                                @foreach($availablePlans as $plan)
                                    <div class="col-md-6">
                                        <div class="plan-option">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <h6 class="fw-bold mb-0">{{ $plan->name }}</h6>
                                                @if($planService->packageIncludesAi($plan))
                                                    <span class="badge bg-primary"><i class="la la-magic"></i> AI</span>
                                                @endif
                                            </div>
                                            <div class="plan-option__price my-2">{{ number_format($plan->price, 2) }}
                                                <span class="fs-6 text-muted fw-normal">/ {{ $plan->duration_days }} {{ __('plan.days') }}</span>
                                            </div>
                                            <ul class="list-unstyled small text-muted mb-3">
                                                <li><i class="la la-check text-success"></i> {{ count($plan->modules ?? []) }} {{ __('plan.modules') }}</li>
                                                <li><i class="la la-check text-success"></i> {{ $plan->student_limit ? $plan->student_limit . ' ' . __('plan.students') : __('plan.unlimited_students') }}</li>
                                                @if($plan->ai_enabled)
                                                    <li><i class="la la-check text-success"></i> {{ $plan->ai_unlimited ? __('plan.ai_unlimited') : __('plan.ai_included') }}</li>
                                                @endif
                                            </ul>
                                            <form action="{{ route('plan.upgrade.request') }}" method="POST" class="plan-upgrade-form">
                                                @csrf
                                                <input type="hidden" name="requested_package_id" value="{{ $plan->id }}">
                                                <button type="submit" class="btn btn-sm btn-outline-primary w-100" {{ $pendingRequest ? 'disabled' : '' }}>
                                                    <i class="la la-paper-plane"></i> {{ __('plan.request_this_plan') }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Custom request + history --}}
            <div class="col-lg-4">
                <div class="plan-card mb-3">
                    <div class="p-3 border-bottom"><h6 class="mb-0 fw-bold"><i class="la la-headset text-success"></i> {{ __('plan.talk_to_us') }}</h6></div>
                    <div class="p-3">
                        <form action="{{ route('plan.upgrade.request') }}" method="POST" class="plan-upgrade-form">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label small">{{ __('plan.interested_in') }}</label>
                                <select name="requested_package_id" class="form-select form-select-sm">
                                    <option value="">{{ __('plan.any_higher_plan') }}</option>
                                    @foreach($availablePlans as $plan)
                                        <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">{{ __('plan.message') }}</label>
                                <textarea name="message" rows="3" class="form-control form-control-sm" placeholder="{{ __('plan.message_placeholder') }}"></textarea>
                            </div>
                            <button class="btn btn-primary btn-sm w-100" {{ $pendingRequest ? 'disabled' : '' }}>
                                <i class="la la-paper-plane"></i> {{ __('plan.send_request') }}
                            </button>
                        </form>
                    </div>
                </div>

                <div class="plan-card">
                    <div class="p-3 border-bottom"><h6 class="mb-0 fw-bold"><i class="la la-history text-info"></i> {{ __('plan.my_requests') }}</h6></div>
                    <div class="p-3">
                        @forelse($recentRequests as $req)
                            <div class="d-flex justify-content-between align-items-center py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                <div>
                                    <div class="fw-semibold small">{{ $req->requestedPackage->name ?? __('plan.any_higher_plan') }}</div>
                                    <small class="text-muted">{{ $req->created_at->diffForHumans() }}</small>
                                </div>
                                @php
                                    $badge = ['pending'=>'warning','contacted'=>'info','approved'=>'success','rejected'=>'danger'][$req->status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $badge }}">{{ __('plan.status_' . $req->status) }}</span>
                            </div>
                        @empty
                            <p class="text-muted small text-center mb-0 py-2">{{ __('plan.no_requests') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.plan-upgrade-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            Swal.fire({
                title: @json(__('plan.confirm_upgrade_title')),
                text: @json(__('plan.confirm_upgrade_text')),
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#6b7280',
                confirmButtonText: @json(__('plan.confirm_upgrade_yes')),
                cancelButtonText: @json(__('subscription.cancel'))
            }).then(function (result) {
                if (result.isConfirmed) form.submit();
            });
        });
    });
});
</script>
@endsection
