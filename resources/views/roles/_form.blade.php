<form action="{{ isset($role) ? route('roles.update', $role->id) : route('roles.store') }}" method="POST" id="roleForm">
    @csrf
    @if(isset($role))
        @method('PUT')
    @endif

    <div class="row">
        <!-- Role Name Input -->
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
                        <label class="form-check-label fw-bold" for="checkAllPermissions">{{ __('roles.select_all') }}</label>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        @forelse($modules as $module)
                            @php
                                $isDisabled = !$module->is_subscribed;
                                $opacity = $isDisabled ? '0.6' : '1';
                                $badge = $isDisabled ? '<span class="badge badge-xs badge-danger ms-2">Inactive</span>' : '';
                            @endphp
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="border rounded h-100 shadow-sm" style="opacity: {{ $opacity }};">
                                    {{-- Module Header with Select All --}}
                                    <div class="d-flex justify-content-between align-items-center p-3 border-bottom bg-light">
                                        <div class="d-flex align-items-center">
                                            <h5 class="mb-0 text-primary fw-bold text-uppercase fs-14">{{ $module->name }}</h5>
                                            {!! $badge !!}
                                        </div>
                                        <div class="form-check custom-checkbox">
                                            <input type="checkbox" 
                                                   class="form-check-input module-check" 
                                                   id="mod_check_{{ $module->id }}" 
                                                   data-module-id="{{ $module->id }}"
                                                   {{ $isDisabled ? 'disabled' : '' }}>
                                            <label class="form-check-label" for="mod_check_{{ $module->id }}"></label>
                                        </div>
                                    </div>
                                    
                                    {{-- Permission List --}}
                                    <div class="permission-list p-3" style="max-height: 250px; overflow-y: auto;">
                                        @foreach($module->permissions as $permission)
                                            <div class="form-check custom-checkbox mb-2">
                                                <input type="checkbox" 
                                                       name="permissions[]" 
                                                       value="{{ $permission->name }}" 
                                                       class="form-check-input permission-checkbox module-{{ $module->id }}" 
                                                       id="perm_{{ $permission->id }}"
                                                       {{ (isset($rolePermissions) && in_array($permission->name, $rolePermissions)) ? 'checked' : '' }}
                                                       {{ $isDisabled ? 'disabled' : '' }}>
                                                <label class="form-check-label" for="perm_{{ $permission->id }}">
                                                    {{ ucfirst(last(explode('.', $permission->name))) }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center py-5">
                                <p class="text-muted">{{ __('roles.no_permissions_available') }}</p>
                            </div>
                        @endforelse
                    </div>
                    
                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-primary btn-lg shadow-sm px-5">
                            {{ isset($role) ? __('roles.update_role') : __('roles.save_role') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Global Select All
        document.getElementById('checkAllPermissions').addEventListener('change', function() {
            const isChecked = this.checked;
            document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = isChecked);
            document.querySelectorAll('.module-check').forEach(cb => cb.checked = isChecked);
        });

        // 2. Module Select All
        document.querySelectorAll('.module-check').forEach(moduleCb => {
            moduleCb.addEventListener('change', function() {
                const moduleId = this.getAttribute('data-module-id');
                const isChecked = this.checked;
                document.querySelectorAll(`.module-${moduleId}`).forEach(permCb => permCb.checked = isChecked);
            });
        });

        // 3. Logic: If a child is unchecked, uncheck the parent Module check
        document.querySelectorAll('.permission-checkbox').forEach(permCb => {
            permCb.addEventListener('change', function() {
                const moduleClass = Array.from(this.classList).find(c => c.startsWith('module-'));
                if(moduleClass) {
                    const moduleId = moduleClass.split('-')[1];
                    const moduleCheck = document.getElementById(`mod_check_${moduleId}`);
                    
                    if(!this.checked) {
                        moduleCheck.checked = false;
                        document.getElementById('checkAllPermissions').checked = false;
                    } else {
                        const allSiblings = document.querySelectorAll(`.${moduleClass}`);
                        const allChecked = Array.from(allSiblings).every(cb => cb.checked);
                        if(allChecked) {
                            moduleCheck.checked = true;
                        }
                    }
                }
            });
        });
    });
</script>