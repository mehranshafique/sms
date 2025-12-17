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
                    <h4>{{ __('subject.subject_management') }}</h4>
                    <p class="mb-0">{{ __('subject.manage_list_subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                @can('subject.create')
                <a href="{{ route('subjects.create') }}" class="btn btn-primary btn-rounded">
                    <i class="fa fa-plus me-2"></i> {{ __('subject.create_new') }}
                </a>
                @endcan
            </div>
        </div>

        <div class="row">
            <div class="col-xl-6 col-lg-6 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-primary text-primary"><i class="la la-book"></i></span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('subject.total_subjects') }}</p>
                                <h4 class="mb-0">{{ $totalSubjects }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-lg-6 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-success text-success"><i class="la la-check-circle"></i></span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('subject.active_subjects') }}</p>
                                <h4 class="mb-0">{{ $activeSubjects }}</h4>
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
                        <h4 class="card-title">{{ __('subject.subject_list') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="subjectTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        @can('subject.delete')
                                        <th style="width: 50px;" class="no-sort">
                                            <div class="form-check custom-checkbox checkbox-primary check-lg me-3">
                                                <input type="checkbox" class="form-check-input" id="checkAll">
                                                <label class="form-check-label" for="checkAll"></label>
                                            </div>
                                        </th>
                                        @endcan
                                        <th>{{ __('subject.table_no') }}</th>
                                        <th>{{ __('subject.name') }}</th>
                                        <th>{{ __('subject.code') }}</th>
                                        <th>{{ __('subject.grade') }}</th>
                                        <th>{{ __('subject.type') }}</th>
                                        <th>{{ __('subject.status') }}</th>
                                        <th class="text-end">{{ __('subject.action') }}</th>
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
    document.addEventListener('DOMContentLoaded', function() {
        const table = $('#subjectTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('subjects.index') }}",
            dom: '<"row me-2"<"col-md-2"<"me-3"l>><"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0"fB>>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            buttons: [
                {
                    extend: 'collection',
                    className: 'btn btn-outline-secondary dropdown-toggle me-2',
                    text: '<i class="fa fa-download me-1"></i> {{ __("subject.export") }}',
                    buttons: [
                        { extend: 'print', text: '<i class="fa fa-print me-2"></i> Print', className: 'dropdown-item' },
                        { extend: 'csv', text: '<i class="fa fa-file-text-o me-2"></i> CSV', className: 'dropdown-item' },
                        { extend: 'excel', text: '<i class="fa fa-file-excel-o me-2"></i> Excel', className: 'dropdown-item' },
                        { extend: 'pdf', text: '<i class="fa fa-file-pdf-o me-2"></i> PDF', className: 'dropdown-item' },
                        { extend: 'copy', text: '<i class="fa fa-copy me-2"></i> Copy', className: 'dropdown-item' }
                    ]
                },
                @can('subject.delete')
                {
                    text: '<i class="fa fa-trash me-0 me-sm-1"></i><span class="d-none d-sm-inline-block">{{ __("subject.bulk_delete") }}</span>',
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
            order: [[2, 'asc']], 
            columns: [
                @can('subject.delete')
                { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false },
                @endcan
                { data: 'DT_RowIndex', name: 'id', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'code', name: 'code' },
                { data: 'grade', name: 'gradeLevel.name' },
                { data: 'type', name: 'type' },
                { data: 'is_active', name: 'is_active' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
            ],
            language: {
                search: "",
                searchPlaceholder: "{{ __('subject.search_placeholder') }}",
                emptyTable: "{{ __('subject.no_records_found') }}",
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

        $('#subjectTable').on('change', '.single-checkbox', function() {
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
                $(btn.node()).html(`<i class="fa fa-trash me-1"></i> {{ __('subject.bulk_delete') }} (${count})`);
            } else {
                btn.disable();
                $(btn.node()).html(`<i class="fa fa-trash me-1"></i> {{ __('subject.bulk_delete') }}`);
            }
        }

        function handleBulkDelete(ids) {
            if (ids.length === 0) return;
            Swal.fire({
                title: "{{ __('subject.are_you_sure') }}",
                text: "{{ __('subject.delete_warning') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: "{{ __('subject.yes_bulk_delete') }}",
                cancelButtonText: "{{ __('subject.cancel') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('subjects.bulkDelete') }}",
                        type: 'POST',
                        data: { ids: ids, _token: "{{ csrf_token() }}" },
                        success: function(response) {
                            Swal.fire("{{ __('subject.success') }}", response.success, 'success');
                            table.ajax.reload();
                            $('#checkAll').prop('checked', false);
                        },
                        error: function() {
                            Swal.fire("{{ __('subject.error_occurred') }}", "{{ __('subject.something_went_wrong') }}", 'error');
                        }
                    });
                }
            });
        }

        $('#subjectTable tbody').on('click', '.delete-btn', function() {
            let id = $(this).data('id');
            let url = "{{ route('subjects.destroy', ':id') }}".replace(':id', id);
            Swal.fire({
                title: "{{ __('subject.are_you_sure') }}",
                text: "{{ __('subject.delete_warning') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "{{ __('subject.yes_delete') }}",
                cancelButtonText: "{{ __('subject.cancel') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
                        success: function(response) {
                            Swal.fire("{{ __('subject.success') }}", response.message, 'success');
                            table.ajax.reload();
                        },
                        error: function() {
                            Swal.fire("{{ __('subject.error_occurred') }}", "{{ __('subject.something_went_wrong') }}", 'error');
                        }
                    });
                }
            });
        });
    });
</script>
@endsection