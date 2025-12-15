<form action="{{ isset($role) ? route('roles.update', $role->id) : route('roles.store') }}" method="POST" id="roleForm">
    @csrf
    @if(isset($role))
        @method('PUT')
    @endif

    <div class="row">
        <!-- Role Name -->
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header border-0 pb-0">
                    <h4 class="card-title">{{ __('roles.role_details') }}</h4>
                </div>
                <div class="card-body">
                    <div class="basic-form">
                        <div class="mb-3">
                            <label class="form-label">{{ __('roles.role_name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $role->name ?? '') }}" class="form-control" placeholder="{{ __('roles.enter_role_name') }}" required {{ (isset($role) && $role->name === 'Super Admin') ? 'readonly' : '' }}>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Permissions Matrix -->
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                    <h4 class="card-title">{{ __('roles.assign_permissions') }}</h4>
                    <div class="form-check custom-checkbox">
                        <input type="checkbox" class="form-check-input" id="checkAllPermissions">
                        <label class="form-check-label" for="checkAllPermissions">{{ __('roles.select_all') }}</label>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($modules as $module)
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="border rounded p-3 h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                        <h5 class="mb-0 text-primary fw-bold text-uppercase fs-14">{{ $module->name }}</h5>
                                        <div class="form-check custom-checkbox">
                                            <input type="checkbox" class="form-check-input module-check" data-module-id="{{ $module->id }}">
                                        </div>
                                    </div>
                                    
                                    <div class="permission-list">
                                        @foreach($module->permissions as $permission)
                                            <div class="form-check custom-checkbox mb-2">
                                                <input type="checkbox" 
                                                       name="permissions[]" 
                                                       value="{{ $permission->name }}" 
                                                       class="form-check-input permission-checkbox module-{{ $module->id }}" 
                                                       id="perm_{{ $permission->id }}"
                                                       {{ (isset($rolePermissions) && in_array($permission->name, $rolePermissions)) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="perm_{{ $permission->id }}">
                                                    {{ $permission->name }} 
                                                    {{-- Optionally replace with a translated friendly name if available --}}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">{{ isset($role) ? __('roles.update_role') : __('roles.save_role') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle "Select All" for the entire page
        document.getElementById('checkAllPermissions').addEventListener('change', function() {
            const isChecked = this.checked;
            document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = isChecked);
            document.querySelectorAll('.module-check').forEach(cb => cb.checked = isChecked);
        });

        // Handle "Select All" per module
        document.querySelectorAll('.module-check').forEach(moduleCb => {
            moduleCb.addEventListener('change', function() {
                const moduleId = this.getAttribute('data-module-id');
                const isChecked = this.checked;
                document.querySelectorAll(`.module-${moduleId}`).forEach(permCb => permCb.checked = isChecked);
            });
        });
    });
</script>