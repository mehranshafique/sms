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
        <div class="row page-titles mx-0 mb-4 p-4 bg-white rounded shadow-sm align-items-center">
            <div class="col-sm-6 p-0">
                <div class="welcome-text">
                    <h4 class="text-primary fw-bold fs-20">{{ __('grade_level.grade_management') }}</h4>
                    <p class="mb-0 text-muted fs-14">{{ __('grade_level.manage_list_subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                @can('grade_level.create')
                <a href="{{ route('grade-levels.create') }}" class="btn btn-primary btn-rounded shadow-sm fw-bold px-4 py-2">
                    <i class="fa fa-plus me-2"></i> {{ __('grade_level.create_new') }}
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
                            <span class="me-3 bgl-primary text-primary"><i class="la la-graduation-cap"></i></span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('grade_level.total_grades') }}</p>
                                <h4 class="mb-0">{{ $totalGrades ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-xxl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-info text-info"><i class="la la-child"></i></span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('grade_level.primary_cycle') }}</p>
                                <h4 class="mb-0">{{ $primaryGrades ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-xxl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-warning text-warning"><i class="la la-book"></i></span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('grade_level.secondary_cycle') }}</p>
                                <h4 class="mb-0">{{ $secondaryGrades ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-xxl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-success text-success"><i class="la la-university"></i></span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('grade_level.university_cycle') }}</p>
                                <h4 class="mb-0">{{ $universityGrades ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- TABLE SECTION --}}
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0" style="border-radius: 15px;">
                    <div class="card-header border-0 pb-0 pt-4 px-4 bg-white" style="border-radius: 15px 15px 0 0;">
                        <h4 class="card-title mb-0 fw-bold fs-18">{{ __('grade_level.grade_list') }}</h4>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div class="table-responsive">
                            <table id="gradeTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        @can('grade_level.delete')
                                        <th style="width: 50px;" class="no-sort">
                                            <div class="form-check custom-checkbox checkbox-primary check-lg me-3">
                                                <input type="checkbox" class="form-check-input" id="checkAll">
                                                <label class="form-check-label" for="checkAll"></label>
                                            </div>
                                        </th>
                                        @endcan
                                        <th>{{ __('grade_level.table_no') }}</th>
                                        <th>{{ __('grade_level.name') }}</th>
                                        <th>{{ __('grade_level.code') }}</th>
                                        <th>{{ __('grade_level.order') }}</th>
                                        <th>{{ __('grade_level.cycle') }}</th>
                                        <th class="text-end no-sort">{{ __('grade_level.action') }}</th>
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
        const table = $('#gradeTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('grade-levels.index') }}",
            dom: '<"row me-2"<"col-md-2"<"me-3"l>><"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0"fB>>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            buttons: [
                {
                    extend: 'collection',
                    className: 'btn btn-outline-secondary dropdown-toggle me-2',
                    text: '<i class="fa fa-download me-1"></i> {{ __("grade_level.export") }}',
                    buttons: [
                        { extend: 'print', text: '<i class="fa fa-print me-2"></i> Print', className: 'dropdown-item' },
                        { extend: 'csv', text: '<i class="fa fa-file-text-o me-2"></i> CSV', className: 'dropdown-item' },
                        { extend: 'excel', text: '<i class="fa fa-file-excel-o me-2"></i> Excel', className: 'dropdown-item' },
                        { extend: 'pdf', text: '<i class="fa fa-file-pdf-o me-2"></i> PDF', className: 'dropdown-item' },
                        { extend: 'copy', text: '<i class="fa fa-copy me-2"></i> Copy', className: 'dropdown-item' }
                    ]
                },
                @can('grade_level.delete')
                {
                    text: '<i class="fa fa-trash me-0 me-sm-1"></i><span class="d-none d-sm-inline-block">{{ __("grade_level.bulk_delete") }}</span>',
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
            order: [[3, 'asc']], // Order by 'order_index'
            columns: [
                @can('grade_level.delete')
                { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false },
                @endcan
                { data: 'DT_RowIndex', name: 'id', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'code', name: 'code' },
                { data: 'order_index', name: 'order_index' },
                { data: 'education_cycle', name: 'education_cycle' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
            ],
            language: {
                search: "",
                searchPlaceholder: "{{ __('grade_level.search_placeholder') }}",
                emptyTable: "{{ __('grade_level.no_records_found') }}",
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

        $('#gradeTable').on('change', '.single-checkbox', function() {
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
                $(btn.node()).html(`<i class="fa fa-trash me-1"></i> {{ __('grade_level.bulk_delete') }} (${count})`);
            } else {
                btn.disable();
                $(btn.node()).html(`<i class="fa fa-trash me-1"></i> {{ __('grade_level.bulk_delete') }}`);
            }
        }

        function handleBulkDelete(ids) {
            if (ids.length === 0) return;
            Swal.fire({
                title: "{{ __('grade_level.are_you_sure') }}",
                text: "{{ __('grade_level.delete_warning') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: "{{ __('grade_level.yes_bulk_delete') }}",
                cancelButtonText: "{{ __('grade_level.cancel') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('grade-levels.bulkDelete') }}",
                        type: 'POST',
                        data: { ids: ids, _token: "{{ csrf_token() }}" },
                        success: function(response) {
                            Swal.fire("{{ __('grade_level.success') }}", response.success, 'success');
                            table.ajax.reload();
                            $('#checkAll').prop('checked', false);
                        },
                        error: function() {
                            Swal.fire("{{ __('grade_level.error_occurred') }}", "{{ __('grade_level.something_went_wrong') }}", 'error');
                        }
                    });
                }
            });
        }

        $('#gradeTable tbody').on('click', '.delete-btn', function() {
            let id = $(this).data('id');
            let url = "{{ route('grade-levels.destroy', ':id') }}".replace(':id', id);
            Swal.fire({
                title: "{{ __('grade_level.are_you_sure') }}",
                text: "{{ __('grade_level.delete_warning') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: "{{ __('grade_level.yes_delete') }}",
                cancelButtonText: "{{ __('grade_level.cancel') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
                        success: function(response) {
                            Swal.fire("{{ __('grade_level.success') }}", response.message, 'success');
                            table.ajax.reload();
                        },
                        error: function() {
                            Swal.fire("{{ __('grade_level.error_occurred') }}", "{{ __('grade_level.something_went_wrong') }}", 'error');
                        }
                    });
                }
            });
        });
    });
</script>
@endsection