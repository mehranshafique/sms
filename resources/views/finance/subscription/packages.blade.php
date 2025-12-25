@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('subscription.package_title') }}</h4>
                    <p class="mb-0">{{ __('subscription.manage_packages') }}</p>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Create Form --}}
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('subscription.create_package') }}</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('packages.store') }}" method="POST">
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
                                <small class="text-muted">Days</small>
                            </div>
                            
                            {{-- Granular Modules List --}}
                            <div class="mb-3">
                                <label class="form-label">{{ __('subscription.modules') }}</label>
                                <div class="border rounded p-3" style="max-height: 250px; overflow-y: auto;">
                                    <div class="form-check custom-checkbox mb-2">
                                        <input type="checkbox" class="form-check-input" id="checkAllModules">
                                        <label class="form-check-label fw-bold" for="checkAllModules">Select All</label>
                                    </div>
                                    <hr class="my-2">
                                    {{-- Use Granular Modules from DB --}}
                                    @foreach($modules as $mod)
                                        <div class="form-check custom-checkbox mb-2">
                                            <input type="checkbox" 
                                                   name="modules[]" 
                                                   value="{{ $mod->slug }}" 
                                                   class="form-check-input module-check" 
                                                   id="mod_{{ $mod->id }}">
                                            <label class="form-check-label" for="mod_{{ $mod->id }}">{{ $mod->name }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">{{ __('subscription.save') }}</button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- List --}}
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('subscription.package_list') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
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
                                        <td>{{ $package->duration_days }} Days</td>
                                        <td>
                                            @if($package->is_active)
                                                <span class="badge badge-success">{{ __('subscription.active') }}</span>
                                            @else
                                                <span class="badge badge-danger">{{ __('subscription.inactive') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end">
                                                {{-- Edit Button --}}
                                                <a href="{{ route('packages.edit', $package->id) }}" class="btn btn-primary shadow btn-xs sharp me-1">
                                                    <i class="fa fa-pencil"></i>
                                                </a>
                                                
                                                {{-- Delete Button --}}
                                                <form action="{{ route('packages.destroy', $package->id) }}" method="POST" onsubmit="return confirm('{{ __('subscription.are_you_sure') }}')">
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
                                        <td colspan="5" class="text-center text-muted">{{ __('subscription.no_records') }}</td>
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
</div>

@section('js')
<script>
    document.getElementById('checkAllModules').addEventListener('change', function() {
        const isChecked = this.checked;
        document.querySelectorAll('.module-check').forEach(cb => cb.checked = isChecked);
    });
</script>
@endsection
@endsection