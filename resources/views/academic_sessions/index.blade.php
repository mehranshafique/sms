@extends('layout.layout')

@section('styles')
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/select/1.7.0/css/select.dataTables.min.css" rel="stylesheet">
    
    <style>
        .dt-buttons .dropdown-toggle {
            background-color: #fff !important;
            color: #697a8d !important;
            border-color: #d9dee3 !important;
            box-shadow: 0 0.125rem 0.25rem 0 rgba(105, 122, 141, 0.1);
            border-radius: 0.375rem; 
            padding: 0.4375rem 1rem;
        }
        .dt-buttons .dropdown-toggle:hover {
            background-color: #f8f9fa !important;
        }
        .dt-buttons .btn-danger {
            background-color: #ff3e1d !important;
            border-color: #ff3e1d !important;
            color: #fff !important;
            border-radius: 0.375rem;
        }
        .dt-buttons {
            display: inline-flex;
            vertical-align: middle;
            gap: 10px;
            margin-bottom: 1rem;
        }
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #d9dee3;
            border-radius: 0.375rem;
            padding: 0.4375rem 0.875rem;
            margin-left: 0.5em;
            outline: none;
        }
    </style>
@endsection

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        {{-- TITLE BAR --}}
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('academic_session.session_management') }}</h4>
                    <p class="mb-0">{{ __('academic_session.manage_list_subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                @can('create', App\Models\AcademicSession::class)
                <a href="{{ route('academic-sessions.create') }}" class="btn btn-primary btn-rounded">
                    <i class="fa fa-plus me-2"></i> {{ __('academic_session.create_new') }}
                </a>
                @endcan
            </div>
        </div>

        {{-- STATS CARDS --}}
        <div class="row">
            <div class="col-xl-3 col-xxl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-primary text-primary">
                                <i class="la la-calendar"></i>
                            </span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('academic_session.total_sessions') }}</p>
                                <h4 class="mb-0">{{ $totalSessions ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-xxl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-success text-success">
                                <i class="la la-check-circle"></i>
                            </span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('academic_session.active_sessions') }}</p>
                                <h4 class="mb-0">{{ $activeSessions ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-xxl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-info text-info">
                                <i class="la la-calendar-plus-o"></i>
                            </span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('academic_session.planned_sessions') }}</p>
                                <h4 class="mb-0">{{ $plannedSessions ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-xxl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-danger text-danger">
                                <i class="la la-times-circle"></i>
                            </span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('academic_session.closed_sessions') }}</p>
                                <h4 class="mb-0">{{ $closedSessions ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- TABLE SECTION --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('academic_session.session_list') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="sessionTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        @can('deleteAny', App\Models\AcademicSession::class)
                                        <th style="width: 50px;" class="no-sort">
                                            <div class="form-check custom-checkbox checkbox-primary check-lg me-3">
                                                <input type="checkbox" class="form-check-input" id="checkAll">
                                                <label class="form-check-label" for="checkAll"></label>
                                            </div>
                                        </th>
                                        @endcan
                                        <th>{{ __('academic_session.table_no') }}</th>
                                        <th>{{ __('academic_session.name') }}</th>
                                        <th>{{ __('academic_session.institution') }}</th>
                                        <th>{{ __('academic_session.start_date') }}</th>
                                        <th>{{ __('academic_session.end_date') }}</th>
                                        <th>{{ __('academic_session.status') }}</th>
                                        <th>{{ __('academic_session.is_current') }}</th>
                                        <th class="text-end no-sort">{{ __('academic_session.action') }}</th>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>
<script src="https://cdn.datatables.net/select/1.7.0/js/dataTables.select.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const table = $('#sessionTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('academic-sessions.index') }}",
            dom: '<"row me-2"<"col-md-2"<"me-3"l>><"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0"fB>>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            buttons: [
                {
                    extend: 'collection',
                    className: 'btn btn-outline-secondary dropdown-toggle me-2',
                    text: '<i class="fa fa-download me-1"></i> {{ __("academic_session.export") }}',
                    buttons: [
                        { extend: 'print', text: '<i class="fa fa-print me-2"></i> Print', className: 'dropdown-item' },
                        { extend: 'csv', text: '<i class="fa fa-file-text-o me-2"></i> CSV', className: 'dropdown-item' },
                        { extend: 'excel', text: '<i class="fa fa-file-excel-o me-2"></i> Excel', className: 'dropdown-item' },
                        { extend: 'pdf', text: '<i class="fa fa-file-pdf-o me-2"></i> PDF', className: 'dropdown-item' },
                        { extend: 'copy', text: '<i class="fa fa-copy me-2"></i> Copy', className: 'dropdown-item' }
                    ]
                },
                @can('deleteAny', App\Models\AcademicSession::class)
                {
                    text: '<i class="fa fa-trash me-0 me-sm-1"></i><span class="d-none d-sm-inline-block">{{ __("academic_session.bulk_delete") }}</span>',
                    className: 'bulk-delete-btn btn btn-danger',
                    enabled: false,
                    action: function () {
                        let selectedIds = [];
                        $('.single-checkbox:checked').each(function() {
                            selectedIds.push($(this).val());
                        });
                        handleBulkDelete(selectedIds);
                    }
                }
                @endcan
            ],
            ordering: true,
            order: [[ @can('deleteAny', App\Models\AcademicSession::class) 2 @else 1 @endcan, 'desc']],
            columns: [
                @can('deleteAny', App\Models\AcademicSession::class)
                { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false },
                @endcan
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'institution_name', name: 'institution_name', orderable: false, searchable: false },
                { data: 'start_date', name: 'start_date' },
                { data: 'end_date', name: 'end_date' },
                { data: 'status', name: 'status' },
                { data: 'is_current', name: 'is_current' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
            ],
            language: {
                search: "",
                searchPlaceholder: "{{ __('academic_session.search_placeholder') }}",
                emptyTable: "{{ __('academic_session.no_records_found') }}",
                processing: '<i class="fa fa-spinner fa-spin fa-2x fa-fw"></i>',
                lengthMenu: "_MENU_",
                paginate: { next: '<i class="fa fa-angle-right"></i>', previous: '<i class="fa fa-angle-left"></i>' }
            },
            drawCallback: function() {
                updateBulkDeleteState();
                $('#checkAll').prop('checked', false);
            }
        });

        $('#checkAll').on('click', function() {
            const isChecked = this.checked;
            $('.single-checkbox').prop('checked', isChecked);
            updateBulkDeleteState();
        });

        $('#sessionTable').on('change', '.single-checkbox', function() {
            updateBulkDeleteState();
            if ($('.single-checkbox:checked').length === $('.single-checkbox').length) {
                $('#checkAll').prop('checked', true);
            } else {
                $('#checkAll').prop('checked', false);
            }
        });

        function updateBulkDeleteState() {
            const count = $('.single-checkbox:checked').length;
            const btn = table.button('.bulk-delete-btn');
            if (count > 0) {
                btn.enable();
                $(btn.node()).html(`<i class="fa fa-trash me-1"></i> {{ __('academic_session.bulk_delete') }} (${count})`);
            } else {
                btn.disable();
                $(btn.node()).html(`<i class="fa fa-trash me-1"></i> {{ __('academic_session.bulk_delete') }}`);
            }
        }

        function handleBulkDelete(ids) {
            if (ids.length === 0) return;
            Swal.fire({
                title: "{{ __('academic_session.are_you_sure_bulk') }}",
                text: "{{ __('academic_session.bulk_delete_warning') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: "{{ __('academic_session.yes_bulk_delete') }}",
                cancelButtonText: "{{ __('academic_session.cancel') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('academic-sessions.destroy', 'bulk') }}".replace('bulk', 'bulk-delete') , // NOTE: Route needs explicit define if standard resource doesn't cover bulk
                        type: 'POST', // Using POST for bulk delete convention in this project
                        data: { 
                            _method: 'DELETE', // Spoof DELETE if route expects it, or check web.php
                            ids: ids, 
                            _token: "{{ csrf_token() }}" 
                        },
                        // Wait, previous examples used explicit bulk-delete route. 
                        // Let's assume web.php has Route::delete('academic-sessions/bulk-delete', ...)
                        url: "/academic-sessions/bulk-delete", // Direct URL to match pattern
                        success: function(response) {
                            Swal.fire("{{ __('academic_session.success') }}", response.success || response.message, 'success');
                            table.ajax.reload();
                            $('#checkAll').prop('checked', false);
                        },
                        error: function() {
                            Swal.fire("{{ __('academic_session.error_occurred') }}", "{{ __('academic_session.something_went_wrong') }}", 'error');
                        }
                    });
                }
            });
        }

        $('#sessionTable tbody').on('click', '.delete-btn', function() {
            let id = $(this).data('id');
            let url = "{{ route('academic-sessions.destroy', ':id') }}".replace(':id', id);
            Swal.fire({
                title: "{{ __('academic_session.are_you_sure') }}",
                text: "{{ __('academic_session.delete_warning') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: "{{ __('academic_session.yes_delete') }}",
                cancelButtonText: "{{ __('academic_session.cancel') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
                        success: function(response) {
                            Swal.fire("{{ __('academic_session.success') }}", response.message, 'success');
                            table.ajax.reload();
                        },
                        error: function() {
                            Swal.fire("{{ __('academic_session.error_occurred') }}", "{{ __('academic_session.something_went_wrong') }}", 'error');
                        }
                    });
                }
            });
        });
    });
</script>
@endsection