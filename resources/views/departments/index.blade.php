@extends('layout.layout')

@section('styles')
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
@endsection

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        <div class="row page-titles mx-0 mb-4 p-4 bg-white rounded shadow-sm align-items-center">
            <div class="col-sm-6 p-0">
                <div class="welcome-text">
                    <h4 class="text-primary fw-bold">{{ __('department.page_title') }}</h4>
                    <p class="mb-0 text-muted">{{ __('department.subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('departments.create') }}" class="btn btn-primary btn-rounded shadow-sm">
                    <i class="fa fa-plus me-2"></i> {{ __('department.create_new') }}
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header border-0 pb-0 pt-4 px-4 bg-white">
                        <h4 class="card-title mb-0 fw-bold">{{ __('department.department_list') }}</h4>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div class="table-responsive">
                            <table id="departmentTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>{{ __('department.table_no') }}</th>
                                        <th>{{ __('department.name') }}</th>
                                        <th>{{ __('department.code') }}</th>
                                        <th>{{ __('department.head_of_department') }}</th>
                                        <th class="text-end">{{ __('department.action') }}</th>
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
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        var table = $('#departmentTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('departments.index') }}",
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'code', name: 'code' },
                { data: 'head_of_department', name: 'headOfDepartment.user.name' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
            ],
            language: {
                searchPlaceholder: "{{ __('department.search_placeholder') }}",
                emptyTable: "{{ __('department.no_records_found') }}"
            }
        });

        $('#departmentTable').on('click', '.delete-btn', function() {
            let id = $(this).data('id');
            let url = "{{ route('departments.destroy', ':id') }}".replace(':id', id);
            
            Swal.fire({
                title: "{{ __('department.are_you_sure') }}",
                text: "{{ __('department.delete_warning') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: "{{ __('department.yes_delete') }}",
                cancelButtonText: "{{ __('department.cancel') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
                        success: function(response) {
                            Swal.fire("{{ __('department.success') }}", response.message, 'success');
                            table.ajax.reload();
                        },
                        error: function() {
                            Swal.fire("{{ __('department.error_occurred') }}", "{{ __('department.something_went_wrong') }}", 'error');
                        }
                    });
                }
            });
        });
    });
</script>
@endsection