@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('staff_leave.page_title') }}</h4>
                    <p class="mb-0">{{ __('staff_leave.subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('staff-leaves.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus me-2"></i> {{ __('staff_leave.create_new') }}
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="leaveTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('staff_leave.staff_member') }}</th>
                                        <th>{{ __('staff_leave.leave_type') }}</th>
                                        <th>{{ __('staff_leave.days') }}</th>
                                        <th>{{ __('staff_leave.status') }}</th>
                                        <th class="text-end">{{ __('staff_leave.action') ?? 'Action' }}</th>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        var table = $('#leaveTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('staff-leaves.index') }}",
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'staff_name', name: 'staff.user.name' },
                { data: 'type', name: 'type' },
                { data: 'dates', name: 'start_date' },
                { data: 'status', name: 'status' },
                { data: 'action', orderable: false, searchable: false, className: 'text-end' }
            ]
        });

        // Status Update
        $(document).on('click', '.update-status', function() {
            let id = $(this).data('id');
            let status = $(this).data('status');
            
            Swal.fire({
                title: status === 'approved' ? "{{ __('staff_leave.confirm_approve') }}" : "{{ __('staff_leave.confirm_reject') }}",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: status === 'approved' ? '#28a745' : '#d33',
                confirmButtonText: 'Yes'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "/staff-leaves/" + id + "/status",
                        type: 'POST',
                        data: { _token: '{{ csrf_token() }}', status: status },
                        success: function(res) {
                            table.ajax.reload();
                            Swal.fire('Updated', res.message, 'success');
                        }
                    });
                }
            });
        });

        // Delete
        $(document).on('click', '.delete-btn', function() {
            let id = $(this).data('id');
            Swal.fire({
                title: 'Are you sure?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Delete'
            }).then((result) => {
                if(result.isConfirmed) {
                    $.ajax({
                        url: "/staff-leaves/" + id,
                        type: 'DELETE',
                        data: { _token: '{{ csrf_token() }}' },
                        success: function(res) {
                            table.ajax.reload();
                            Swal.fire('Deleted', res.message, 'success');
                        }
                    });
                }
            });
        });
    });
</script>
@endsection