@extends('layout.layout')

@section('content')
<style>
    .pkg-hero { border-radius:18px; background:linear-gradient(120deg,#0b2a6b 0%,#13386e 50%,#2563eb 100%); }
    .pkg-card { background:#fff; border:1px solid #eef0f4; border-radius:16px; }
    .pkg-table thead th { font-size:.78rem; text-transform:uppercase; letter-spacing:.04em; color:#6b7280; border-bottom:1px solid #eef0f4; background:#fafbfc; }
    [data-theme-version="dark"] .pkg-card { background:#1e2746; border-color:#2b365c; color:#e8ebf5; }
    [data-theme-version="dark"] .pkg-table thead th { background:#243054 !important; color:#e8ebf5 !important; border-color:#2b365c !important; }
</style>
<div class="content-body">
    <div class="container-fluid">

        <div class="row mb-4">
            <div class="col-12">
                <div class="pkg-hero shadow-sm p-4">
                    <h3 class="text-white fw-bold mb-1">{{ __('subscription.package_title') }}</h3>
                    <p class="mb-0 text-white opacity-75">{{ __('subscription.manage_packages') }}</p>
                </div>
            </div>
        </div>

        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif

        <div class="row g-3">
            <div class="col-xl-4">
                <div class="pkg-card shadow-sm">
                    <div class="p-3 border-bottom">
                        <h6 class="mb-0 fw-bold"><i class="la la-plus-circle text-primary"></i> {{ __('subscription.create_package') }}</h6>
                    </div>
                    <div class="p-3">
                        <form action="{{ route('packages.store') }}" method="POST" class="ajax-form">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">{{ __('subscription.package_name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="{{ __('subscription.enter_name') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('subscription.price') }} <span class="text-danger">*</span></label>
                                <input type="number" name="price" class="form-control" placeholder="{{ __('subscription.enter_price') }}" step="0.01" min="0" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('subscription.duration') }} <span class="text-danger">*</span></label>
                                <input type="number" name="duration_days" class="form-control" value="365" min="1" required>
                                <small class="text-muted">{{ __('subscription.days') }}</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('subscription.modules') }}</label>
                                <div class="border rounded p-3" style="max-height: 250px; overflow-y: auto;">
                                    <div class="form-check custom-checkbox mb-2">
                                        <input type="checkbox" class="form-check-input" id="checkAllModules">
                                        <label class="form-check-label fw-bold" for="checkAllModules">{{ __('subscription.select_all') }}</label>
                                    </div>
                                    <hr class="my-2">
                                    @foreach($modules as $mod)
                                        <div class="form-check custom-checkbox mb-2">
                                            <input type="checkbox" name="modules[]" value="{{ $mod->slug }}" class="form-check-input module-check" id="mod_{{ $mod->id }}">
                                            <label class="form-check-label" for="mod_{{ $mod->id }}">{{ $mod->name }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('subscription.ai_features') }}</label>
                                <div class="border rounded p-3">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="ai_enabled" value="1" id="aiEnabled">
                                        <label class="form-check-label" for="aiEnabled">{{ __('subscription.ai_enabled') }}</label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="ai_unlimited" value="1" id="aiUnlimited">
                                        <label class="form-check-label" for="aiUnlimited">{{ __('subscription.ai_unlimited') }}</label>
                                    </div>
                                    <label class="form-label small mb-1">{{ __('subscription.ai_monthly_limit') }}</label>
                                    <input type="number" name="ai_monthly_limit" class="form-control" min="0" placeholder="{{ config('ai.default_monthly_limit') }}">
                                    <small class="text-muted d-block mt-2">{{ __('subscription.ai_help') }}</small>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">{{ __('subscription.save') }}</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="pkg-card shadow-sm">
                    <div class="p-3 border-bottom">
                        <h6 class="mb-0 fw-bold"><i class="la la-box text-primary"></i> {{ __('subscription.package_list') }}</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table pkg-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('subscription.package_name') }}</th>
                                    <th>{{ __('subscription.price') }}</th>
                                    <th>{{ __('subscription.duration') }}</th>
                                    <th>{{ __('subscription.status') }}</th>
                                    <th class="text-end">{{ __('subscription.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($packages as $package)
                                <tr>
                                    <td><strong>{{ $package->name }}</strong></td>
                                    <td>${{ number_format($package->price, 2) }}</td>
                                    <td>{{ $package->duration_days }} {{ __('subscription.days') }}</td>
                                    <td>
                                        @if($package->is_active)
                                            <span class="badge bg-success">{{ __('subscription.active') }}</span>
                                        @else
                                            <span class="badge bg-danger">{{ __('subscription.inactive') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="{{ route('packages.edit', $package->id) }}" class="btn btn-primary shadow btn-xs sharp">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                            <form action="{{ route('packages.destroy', $package->id) }}" method="POST" class="d-inline pkg-delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger shadow btn-xs sharp">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">{{ __('subscription.no_records') }}</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
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
    const checkAll = document.getElementById('checkAllModules');
    if (checkAll) {
        checkAll.addEventListener('change', function () {
            document.querySelectorAll('.module-check').forEach(cb => cb.checked = this.checked);
        });
    }

    document.querySelectorAll('.pkg-delete-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            Swal.fire({
                title: @json(__('subscription.delete_package_title')),
                text: @json(__('subscription.delete_package_text')),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: @json(__('subscription.delete_confirm_yes')),
                cancelButtonText: @json(__('subscription.cancel'))
            }).then(function (result) {
                if (result.isConfirmed) form.submit();
            });
        });
    });
});
</script>
@endsection
