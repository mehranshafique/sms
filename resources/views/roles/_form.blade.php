<form action="{{ isset($role) ? route('roles.update', $role->id) : route('roles.store') }}" method="POST" id="roleForm">
    @csrf
    @if(isset($role))
        @method('PUT')
    @endif

    {{-- Read Only Indicator --}}
    @if(isset($isReadOnly) && $isReadOnly)
        <div class="alert alert-info solid alert-dismissible fade show">
            <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="me-2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
            <strong>{{ __('roles.read_only_mode') ?? 'Read Only Mode' }}:</strong> {{ __('roles.cannot_edit_own_role_permissions') ?? 'This is your active role. You cannot edit permissions assigned to yourself.' }}
        </div>
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
                            <input type="text" name="name" 
                                   value="{{ old('name', $role->name ?? '') }}" 
                                   class="form-control" 
                                   placeholder="{{ __('roles.enter_role_name') }}" 
                                   required 
                                   {{ (isset($role) && $role->name === 'Super Admin') || (isset($isReadOnly) && $isReadOnly) ? 'disabled' : '' }}>
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
                    
                    @if(!isset($isReadOnly) || !$isReadOnly)
                    <div class="form-check custom-checkbox">
                        <input type="checkbox" class="form-check-input" id="checkAllPermissions">
                        <label class="form-check-label fw-bold" for="checkAllPermissions">{{ __('roles.select_all') }}</label>
                    </div>
                    @endif
                </div>
                <div class="card-body">
                    <div class="row">
                        @forelse($modules as $module)
                            @php
                                $isDisabled = !$module->is_subscribed || (isset($isReadOnly) && $isReadOnly);
                                $opacity = $isDisabled ? '0.6' : '1';
                                $badge = !$module->is_subscribed ? '<span class="badge badge-xs badge-danger ms-2">Inactive</span>' : '';
                            @endphp
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="border rounded h-100 shadow-sm" style="opacity: {{ $opacity }};">
                                    {{-- Module Header with Select All --}}
                                    <div class="d-flex justify-content-between align-items-center p-3 border-bottom bg-light">
                                        <div class="d-flex align-items-center">
                                            <h5 class="mb-0 text-primary fw-bold text-uppercase fs-14">{{ $module->name }}</h5>
                                            {!! $badge !!}
                                        </div>
                                        @if(!isset($isReadOnly) || !$isReadOnly)
                                        <div class="form-check custom-checkbox">
                                            <input type="checkbox" 
                                                   class="form-check-input module-check" 
                                                   id="mod_check_{{ $module->id }}" 
                                                   data-module-id="{{ $module->id }}"
                                                   {{ $isDisabled ? 'disabled' : '' }}>
                                            <label class="form-check-label" for="mod_check_{{ $module->id }}"></label>
                                        </div>
                                        @endif
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
                    
                    @if(!isset($isReadOnly) || !$isReadOnly)
                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-primary btn-lg shadow-sm px-5">
                            {{ isset($role) ? __('roles.update_role') : __('roles.save_role') }}
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if(!isset($isReadOnly) || !$isReadOnly)
        // 1. Global Select All
        document.getElementById('checkAllPermissions').addEventListener('change', function() {
            const isChecked = this.checked;
            document.querySelectorAll('.permission-checkbox').forEach(cb => {
                if(!cb.disabled) cb.checked = isChecked;
            });
            document.querySelectorAll('.module-check').forEach(cb => {
                if(!cb.disabled) cb.checked = isChecked;
            });
        });

        // 2. Module Select All
        document.querySelectorAll('.module-check').forEach(moduleCb => {
            moduleCb.addEventListener('change', function() {
                const moduleId = this.getAttribute('data-module-id');
                const isChecked = this.checked;
                document.querySelectorAll(`.module-${moduleId}`).forEach(permCb => {
                    if(!permCb.disabled) permCb.checked = isChecked;
                });
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
        @endif
    });
</script>