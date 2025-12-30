@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('dashboard.my_schools_overview') }}</h4>
                    <p class="mb-0">{{ __('dashboard.global_dashboard') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                 {{-- Button to create new institute if allowed --}}
                 @can('institution.create')
                 <a href="{{ route('institutes.create') }}" class="btn btn-primary btn-sm btn-rounded"><i class="fa fa-plus"></i> {{ __('dashboard.add_new_school') }}</a>
                 @endcan
            </div>
        </div>

        {{-- ROW 1: Aggregate Stats --}}
        <div class="row">
            {{-- Total Institutions --}}
            <div class="col-xl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card bg-primary text-white">
                    <div class="card-body p-4">
                        <div class="media">
                            <span class="me-3">
                                <i class="la la-university"></i>
                            </span>
                            <div class="media-body text-white">
                                <p class="mb-1 text-white opacity-75">{{ __('dashboard.my_schools') }}</p>
                                <h3 class="text-white">{{ $totalSchools }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Total Students --}}
            <div class="col-xl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card bg-info text-white">
                    <div class="card-body p-4">
                        <div class="media">
                            <span class="me-3">
                                <i class="la la-users"></i>
                            </span>
                            <div class="media-body text-white">
                                <p class="mb-1 text-white opacity-75">{{ __('dashboard.total_students') }}</p>
                                <h3 class="text-white">{{ $totalStudents }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Total Staff --}}
            <div class="col-xl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card bg-secondary text-white">
                    <div class="card-body p-4">
                        <div class="media">
                            <span class="me-3">
                                <i class="la la-chalkboard-teacher"></i>
                            </span>
                            <div class="media-body text-white">
                                <p class="mb-1 text-white opacity-75">{{ __('dashboard.total_staff') }}</p>
                                <h3 class="text-white">{{ $totalStaff }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Active Status --}}
            <div class="col-xl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card bg-success text-white">
                    <div class="card-body p-4">
                        <div class="media">
                            <span class="me-3">
                                <i class="la la-check-circle"></i>
                            </span>
                            <div class="media-body text-white">
                                <p class="mb-1 text-white opacity-75">{{ __('dashboard.active_schools') }}</p>
                                <h3 class="text-white">{{ $activeSchools }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ROW 2: Financial Aggregates --}}
        <div class="row">
            <div class="col-xl-6 col-lg-12">
                <div class="card">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title">{{ __('dashboard.financial_overview') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-4 border-end">
                                <h4 class="text-primary mb-2">${{ number_format($totalRevenue, 2) }}</h4>
                                <span class="text-muted">{{ __('dashboard.total_invoiced') }}</span>
                            </div>
                            <div class="col-4 border-end">
                                <h4 class="text-success mb-2">${{ number_format($collectedRevenue, 2) }}</h4>
                                <span class="text-muted">{{ __('dashboard.collected') }}</span>
                            </div>
                            <div class="col-4">
                                <h4 class="text-warning mb-2">${{ number_format($pendingRevenue, 2) }}</h4>
                                <span class="text-muted">{{ __('dashboard.pending') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Alerts / Activity --}}
            <div class="col-xl-6 col-lg-12">
                <div class="card">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title">{{ __('dashboard.recent_activity') }}</h4>
                    </div>
                    <div class="card-body">
                         <div class="d-flex align-items-center">
                             <span class="me-3 icon-box bg-light text-primary rounded-circle">
                                 <i class="la la-bell"></i>
                             </span>
                             <div>
                                 <h5 class="mb-1">{{ $auditLogCount }} {{ __('dashboard.actions') }}</h5>
                                 <p class="mb-0 fs-12 text-muted">{{ __('dashboard.system_actions_desc') }}</p>
                             </div>
                         </div>
                         <div class="mt-3 text-end">
                            <a href="{{ route('audit-logs.index') }}" class="btn-link">{{ __('dashboard.view_logs') }}</a>
                         </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ROW 3: My Institutions List --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title">{{ __('dashboard.my_institutions_list') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-responsive-sm table-hover">
                                <thead class="bg-light">
                                    <tr>
                                        <th>{{ __('dashboard.name') }}</th>
                                        <th>{{ __('dashboard.code') }}</th>
                                        <th>{{ __('dashboard.city') }}</th>
                                        <th>{{ __('dashboard.students') }}</th>
                                        <th>{{ __('dashboard.staff') }}</th>
                                        <th>{{ __('dashboard.status') }}</th>
                                        <th class="text-end">{{ __('dashboard.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($institutes as $inst)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($inst->logo)
                                                    <img src="{{ asset('storage/'.$inst->logo) }}" class="rounded-circle me-2" width="35" height="35" style="object-fit:cover;">
                                                @else
                                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" style="width:35px; height:35px;">
                                                        {{ substr($inst->name, 0, 1) }}
                                                    </div>
                                                @endif
                                                <div class="d-flex flex-column">
                                                    <strong>{{ $inst->name }}</strong>
                                                    <span class="fs-12 text-muted">{{ $inst->type }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $inst->code }}</td>
                                        <td>{{ $inst->city }}</td>
                                        <td><span class="badge badge-sm light badge-info">{{ $inst->student_count }}</span></td>
                                        <td><span class="badge badge-sm light badge-secondary">{{ $inst->staff_count }}</span></td>
                                        <td>
                                            <span class="badge badge-xs light badge-{{ $inst->is_active ? 'success' : 'danger' }}">
                                                {{ $inst->is_active ? __('dashboard.active') : __('dashboard.inactive') }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('institution.switch', $inst->id) }}" class="btn btn-primary btn-xs shadow sharp me-1" title="{{ __('dashboard.switch_dashboard') }}">
                                                <i class="fa fa-external-link-alt"></i>
                                            </a>
                                            <a href="{{ route('institutes.edit', $inst->id) }}" class="btn btn-warning btn-xs shadow sharp" title="{{ __('dashboard.edit') }}">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection