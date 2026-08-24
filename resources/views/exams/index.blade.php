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
                    <h4>{{ __('exam.page_title') }}</h4>
                    <p class="mb-0">{{ __('exam.manage_list_subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                @can('exam.create')
                <a href="{{ route('exams.create') }}" class="btn btn-primary btn-rounded">
                    <i class="fa fa-plus me-2"></i> {{ __('exam.create_new') }}
                </a>
                @endcan
            </div>
        </div>

        @if(!empty($missingCategories))
        <div class="row">
            <div class="col-12">
                <div class="alert alert-warning">
                    <strong>{{ __('exam.missing_categories_title') }}</strong>
                    <p class="mb-0">{{ __('exam.missing_categories_body', ['list' => implode(', ', $missingCategories)]) }}</p>
                </div>
            </div>
        </div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('exam.exam_list') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="examTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        @can('exam.delete')
                                        <th style="width: 50px;" class="no-sort">
                                            <div class="form-check custom-checkbox checkbox-primary check-lg me-3">
                                                <input type="checkbox" class="form-check-input" id="checkAll">
                                                <label class="form-check-label" for="checkAll"></label>
                                            </div>
                                        </th>
                                        @endcan
                                        <th>{{ __('exam.table_no') }}</th>
                                        <th>{{ __('exam.exam_name') }}</th>
                                        <th>{{ __('exam.category') }}</th>
                                        <th>{{ __('exam.session') }}</th>
                                        <th>{{ __('exam.start_date') }}</th>
                                        <th>{{ __('exam.status') }}</th>
                                        <th class="text-end">{{ __('exam.action') }}</th>
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
        const table = $('#examTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('exams.index') }}",
            dom: '<"row me-2"<"col-md-2"<"me-3"l>><"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0"fB>>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            buttons: [
                @can('exam.delete')
                {
                    text: '<i class="fa fa-trash me-0 me-sm-1"></i><span class="d-none d-sm-inline-block">{{ __("exam.bulk_delete") }}</span>',
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
            columns: [
                @can('exam.delete')
                { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false },
                @endcan
                { data: 'DT_RowIndex', name: 'id' },
                { data: 'name', name: 'name' },
                { data: 'category', name: 'category' },
                { data: 'session', name: 'academicSession.name' },
                { data: 'start_date', name: 'start_date' },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
            ]
        });

        $('#checkAll').on('click', function() {
            $('.single-checkbox').prop('checked', this.checked);
            toggleBulkDeleteBtn();
        });

        $('#examTable tbody').on('change', '.single-checkbox', function() {
            toggleBulkDeleteBtn();
        });

        function toggleBulkDeleteBtn() {
            const count = $('.single-checkbox:checked').length;
            const btn = table.button('.bulk-delete-btn');
            if (!btn || !btn.node) {
                return;
            }
            btn.enable(count > 0);
            if (count > 0) {
                $(btn.node()).html(`<i class="fa fa-trash me-1"></i> {{ __('exam.bulk_delete') }} (${count})`);
            } else {
                $(btn.node()).html(`<i class="fa fa-trash me-1"></i> {{ __('exam.bulk_delete') }}`);
            }
        }

        function handleBulkDelete(ids) {
            if (!ids.length) {
                return;
            }
            Swal.fire({
                title: "{{ __('exam.are_you_sure') }}",
                text: "{{ __('exam.delete_warning') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "{{ __('exam.yes_bulk_delete') }}",
                cancelButtonText: "{{ __('exam.cancel') }}"
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }
                $.ajax({
                    url: "{{ route('exams.bulkDelete') }}",
                    type: 'POST',
                    data: { ids: ids },
                    headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
                    success: function(response) {
                        Swal.fire("{{ __('exam.success') }}", response.success || response.message, 'success');
                        table.ajax.reload();
                        $('#checkAll').prop('checked', false);
                        toggleBulkDeleteBtn();
                    },
                    error: function(xhr) {
                        const msg = (xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.error))
                            ? (xhr.responseJSON.message || xhr.responseJSON.error)
                            : "{{ __('exam.something_went_wrong') }}";
                        Swal.fire("{{ __('exam.error_occurred') }}", msg, 'error');
                    }
                });
            });
        }

        $('#examTable tbody').on('click', '.delete-btn', function() {
            const id = $(this).data('id');
            const url = "{{ route('exams.destroy', ':id') }}".replace(':id', id);
            Swal.fire({
                title: "{{ __('exam.are_you_sure') }}",
                text: "{{ __('exam.delete_warning') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "{{ __('exam.yes_delete') }}",
                cancelButtonText: "{{ __('exam.cancel') }}"
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
                    success: function(response) {
                        Swal.fire("{{ __('exam.success') }}", response.message, 'success');
                        table.ajax.reload();
                    },
                    error: function(xhr) {
                        const msg = (xhr.responseJSON && xhr.responseJSON.message)
                            ? xhr.responseJSON.message
                            : "{{ __('exam.something_went_wrong') }}";
                        Swal.fire("{{ __('exam.error_occurred') }}", msg, 'error');
                    }
                });
            });
        });
    });
</script>
@endsection
