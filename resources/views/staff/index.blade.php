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
                    <h4>{{ __('staff.page_title') }}</h4>
                    <p class="mb-0">{{ __('staff.manage_list_subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('staff.create') }}" class="btn btn-primary btn-rounded">
                    <i class="fa fa-plus me-2"></i> {{ __('staff.create_new') }}
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('staff.staff_list') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="staffTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>{{ __('staff.table_no') }}</th>
                                        <th>{{ __('staff.details') }}</th>
                                        <th>{{ __('staff.role') }}</th>
                                        <th>{{ __('staff.status') }}</th>
                                        <th class="text-end">{{ __('staff.action') }}</th>
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
        $('#staffTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('staff.index') }}",
            columns: [
                { data: 'DT_RowIndex', name: 'id' },
                { data: 'details', name: 'user.name' },
                { data: 'role', name: 'role', orderable: false, searchable: false },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
            ]
        });
        
        // Delete Action Delegate
        $('#staffTable tbody').on('click', '.delete-btn', function() {
            let id = $(this).data('id');
            let url = "{{ route('staff.destroy', ':id') }}".replace(':id', id);
            Swal.fire({
                title: "{{ __('staff.are_you_sure') }}",
                text: "{{ __('staff.delete_warning') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: "{{ __('staff.yes_delete') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
                        success: function(response) {
                            Swal.fire("{{ __('staff.success') }}", response.message, 'success');
                            $('#staffTable').DataTable().ajax.reload();
                        }
                    });
                }
            });
        });
    });
</script>
@endsection