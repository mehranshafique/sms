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
                    <h4>{{ __('roles.page_title') }}</h4>
                    <p class="mb-0">{{ __('roles.manage_list_subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                @can('role.create')
                <a href="{{ route('roles.create') }}" class="btn btn-primary btn-rounded">
                    <i class="fa fa-plus me-2"></i> {{ __('roles.create_new') }}
                </a>
                @endcan
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('roles.role_list') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="roleTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>{{ __('roles.table_no') }}</th>
                                        <th>{{ __('roles.role_name') }}</th>
                                        <th>{{ __('roles.users_count') }}</th>
                                        <th class="text-end">{{ __('roles.actions') }}</th>
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
        const table = $('#roleTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('roles.index') }}",
            columns: [
                { data: 'DT_RowIndex', name: 'id', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'users_count', name: 'users_count', searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
            ],
            order: [[0, 'desc']] // Rule 3: Latest First (assuming ID correlates to time)
        });

        $('#roleTable tbody').on('click', '.delete-btn', function() {
            let id = $(this).data('id');
            let url = "{{ route('roles.destroy', ':id') }}".replace(':id', id);
            
            Swal.fire({
                title: "{{ __('roles.are_you_sure') }}",
                text: "{{ __('roles.delete_warning') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: "{{ __('roles.yes_delete') }}",
                cancelButtonText: "{{ __('roles.cancel') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
                        success: function(response) {
                            Swal.fire("{{ __('roles.success') }}", response.message, 'success');
                            table.ajax.reload();
                        },
                        error: function(xhr) {
                            Swal.fire("{{ __('roles.error_occurred') }}", xhr.responseJSON.message || "Error", 'error');
                        }
                    });
                }
            });
        });
    });
</script>
@endsection