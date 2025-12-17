@extends('layout.layout')

@section('styles')
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/select/1.7.0/css/select.dataTables.min.css" rel="stylesheet">
@endsection

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('class_section.class_management') }}</h4>
                    <p class="mb-0">{{ __('class_section.manage_list_subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('class-sections.create') }}" class="btn btn-primary btn-rounded">
                    <i class="fa fa-plus me-2"></i> {{ __('class_section.create_new') }}
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-4 col-lg-6 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-primary text-primary"><i class="la la-users"></i></span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('class_section.total_classes') }}</p>
                                <h4 class="mb-0">{{ $totalClasses }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-lg-6 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-success text-success"><i class="la la-check-circle"></i></span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('class_section.active_classes') }}</p>
                                <h4 class="mb-0">{{ $activeClasses }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-lg-6 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-info text-info"><i class="la la-building"></i></span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('class_section.total_capacity') }}</p>
                                <h4 class="mb-0">{{ $totalCapacity }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('class_section.class_list') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="classTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        @can('class_section.delete')
                                        <th style="width: 50px;" class="no-sort">
                                            <div class="form-check custom-checkbox checkbox-primary check-lg me-3">
                                                <input type="checkbox" class="form-check-input" id="checkAll">
                                                <label class="form-check-label" for="checkAll"></label>
                                            </div>
                                        </th>
                                        @endcan
                                        <th>{{ __('class_section.table_no') }}</th>
                                        <th>{{ __('class_section.details') }}</th>
                                        <th>{{ __('class_section.grade') }}</th>
                                        <th>{{ __('class_section.teacher') }}</th>
                                        <th>{{ __('class_section.status') }}</th>
                                        <th class="text-end">{{ __('class_section.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/select/1.7.0/js/dataTables.select.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        $('#classTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('class-sections.index') }}",
            columns: [
                @can('class_section.delete')
                { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false },
                @endcan
                { data: 'DT_RowIndex', name: 'id' },
                { data: 'details', name: 'name' },
                { data: 'grade', name: 'gradeLevel.name' },
                { data: 'teacher', name: 'classTeacher.user.name' },
                { data: 'is_active', name: 'is_active' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
            ]
        });
        
        // Include bulk delete and single delete scripts similar to previous modules
    });
</script>
@endsection