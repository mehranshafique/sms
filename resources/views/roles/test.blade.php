@extends('layout.layout')

@section('styles')
<style>
    .module-card { transition: all 0.2s ease; }
    .module-card:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.08)!important; }
    .bg-success-light { background-color: rgba(43, 193, 85, 0.1) !important; border-bottom: 1px solid rgba(43, 193, 85, 0.2) !important; }
    .bg-danger-light { background-color: rgba(249, 70, 135, 0.1) !important; border-bottom: 1px solid rgba(249, 70, 135, 0.2) !important; }
</style>
@endsection

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <!-- Header -->
        <div class="row page-titles mx-0 mb-4 p-4 bg-white rounded shadow-sm d-flex justify-content-between align-items-center">
            <div class="col-sm-8 p-0">
                <div class="welcome-text">
                    <h4 class="mb-1"><i class="fa fa-shield text-info me-2"></i> Role Integrity Test</h4>
                    <p class="mb-0 text-muted">Simulating module visibility and access restrictions for: <strong class="text-primary fs-16">{{ $role->name }}</strong></p>
                </div>
            </div>
            <div class="col-sm-4 p-0 text-end mt-sm-0 mt-3">
                <a href="{{ route('roles.index') }}" class="btn btn-outline-dark btn-sm"><i class="fa fa-arrow-left me-2"></i> Back to Roles</a>
                <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-primary btn-sm ms-2"><i class="fa fa-pencil me-2"></i> Edit Permissions</a>
            </div>
        </div>

        <!-- Info Alert -->
        <div class="alert alert-info solid alert-dismissible fade show shadow-sm mb-4">
            <i class="fa fa-info-circle me-2 fs-18"></i>
            <strong>Testing Environment:</strong> This matrix accurately reflects what a user assigned the <b>{{ $role->name }}</b> role will see and be authorized to do across the platform. Modules marked as "Hidden" will be completely removed from their sidebar navigation.
        </div>

        <!-- Matrix -->
        <div class="row">
            @foreach($modules as $module)
                @php
                    // Determine if the role has ANY permission within this module
                    $modulePerms = $module->permissions->pluck('name')->toArray();
                    $grantedPerms = array_intersect($modulePerms, $rolePermissions);
                    $hasModuleAccess = count($grantedPerms) > 0;
                @endphp
                <div class="col-xl-3 col-lg-4 col-sm-6 mb-4">
                    <div class="card module-card border {{ $hasModuleAccess ? 'border-success' : 'border-danger' }} h-100 shadow-sm">
                        <div class="card-header p-3 {{ $hasModuleAccess ? 'bg-success-light' : 'bg-danger-light' }} d-flex justify-content-between align-items-center">
                            <h5 class="card-title fs-15 mb-0 text-dark">{{ $module->name }}</h5>
                            @if($hasModuleAccess)
                                <span class="badge badge-success light"><i class="fa fa-eye me-1"></i> Visible</span>
                            @else
                                <span class="badge badge-danger light opacity-75"><i class="fa fa-eye-slash me-1"></i> Hidden</span>
                            @endif
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                @foreach($module->permissions as $perm)
                                    @php
                                        $hasPerm = in_array($perm->name, $rolePermissions);
                                        // Extract action (e.g., 'student.create' -> 'create')
                                        $parts = explode('.', $perm->name);
                                        $permAction = end($parts);
                                    @endphp
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3 {{ $hasPerm ? 'bg-white' : 'bg-light text-muted' }}">
                                        <span class="text-capitalize fs-13">{{ str_replace('_', ' ', $permAction) }}</span>
                                        @if($hasPerm)
                                            <i class="fa fa-check-circle text-success fs-16"></i>
                                        @else
                                            <i class="fa fa-times-circle text-danger fs-16 opacity-50"></i>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
</div>
@endsection