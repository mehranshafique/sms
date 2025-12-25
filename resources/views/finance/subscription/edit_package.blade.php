@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('subscription.edit_package') }}</h4>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('packages.index') }}">{{ __('subscription.package_list') }}</a></li>
                    <li class="breadcrumb-item active"><a href="javascript:void(0)">Edit</a></li>
                </ol>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('packages.update', $package->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('subscription.package_name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ $package->name }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('subscription.price') }} <span class="text-danger">*</span></label>
                            <input type="number" name="price" class="form-control" value="{{ $package->price }}" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('subscription.duration') }} <span class="text-danger">*</span></label>
                            <input type="number" name="duration_days" class="form-control" value="{{ $package->duration_days }}" min="1" required>
                            <small class="text-muted">Days</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('subscription.status') }}</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" {{ $package->is_active ? 'checked' : '' }}>
                                <label class="form-check-label" for="isActive">{{ __('subscription.active') }}</label>
                            </div>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('subscription.modules') }}</label>
                            <div class="border rounded p-3 bg-light">
                                <div class="form-check custom-checkbox mb-3">
                                    <input type="checkbox" class="form-check-input" id="checkAllModulesEdit">
                                    <label class="form-check-label fw-bold" for="checkAllModulesEdit">Select All</label>
                                </div>
                                <div class="row">
                                    {{-- Loop through Granular Modules from DB --}}
                                    @foreach($modules as $mod)
                                        <div class="col-md-3 col-sm-6">
                                            <div class="form-check custom-checkbox mb-2">
                                                <input type="checkbox" 
                                                       name="modules[]" 
                                                       value="{{ $mod->slug }}" 
                                                       class="form-check-input module-check-edit" 
                                                       id="mod_{{ $mod->id }}"
                                                       {{ in_array($mod->slug, $package->modules ?? []) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="mod_{{ $mod->id }}">{{ $mod->name }}</label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary px-5">{{ __('subscription.update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    document.getElementById('checkAllModulesEdit').addEventListener('change', function() {
        const isChecked = this.checked;
        document.querySelectorAll('.module-check-edit').forEach(cb => cb.checked = isChecked);
    });
</script>
@endsection