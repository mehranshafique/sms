@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        <div class="row page-titles mx-0 mb-4 p-4 bg-white rounded shadow-sm align-items-center">
            <div class="col-sm-6 p-0">
                <div class="welcome-text">
                    <h4 class="text-primary fw-bold">{{ __('pickup.manager_title') }}</h4>
                    <p class="mb-0 text-muted">{{ __('pickup.manager_subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-0 text-end">
                <button class="btn btn-light btn-sm" onclick="window.location.reload()"><i class="fa fa-refresh"></i> {{ __('pickup.refresh_btn') }}</button>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="pickupTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('pickup.student') }}</th>
                                        <th>{{ __('pickup.pickup_by') }}</th>
                                        <th>{{ __('pickup.scanned_by') }}</th>
                                        <th>{{ __('pickup.status') }}</th>
                                        <th class="text-end">{{ __('pickup.action') }}</th>
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
        var table = $('#pickupTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('pickups.teacher') }}",
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'student_name', name: 'student.first_name', className: 'fw-bold' },
                { data: 'pickup_by', name: 'requested_by' },
                { data: 'scanned_by', name: 'scanner.name' },
                { data: 'status', name: 'status' },
                { data: 'action', orderable: false, searchable: false, className: 'text-end' }
            ],
            // Auto reload every 30 seconds to check for new scans
            initComplete: function() {
                setInterval(() => table.ajax.reload(null, false), 30000);
            }
        });

        // Action Handlers
        $('#pickupTable').on('click', '.approve-btn', function() { updateStatus($(this).data('id'), 'approved'); });
        $('#pickupTable').on('click', '.reject-btn', function() { updateStatus($(this).data('id'), 'rejected'); });

        function updateStatus(id, status) {
            Swal.fire({
                title: "{{ __('pickup.confirm_title') }}",
                text: status === 'approved' ? "{{ __('pickup.confirm_release') }}" : "{{ __('pickup.confirm_reject') }}",
                icon: status === 'approved' ? 'question' : 'warning',
                showCancelButton: true,
                confirmButtonColor: status === 'approved' ? '#28a745' : '#d33',
                confirmButtonText: status === 'approved' ? "{{ __('pickup.yes_release') }}" : "{{ __('pickup.yes_reject') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "/pickup/update/" + id,
                        type: 'POST',
                        data: { _token: '{{ csrf_token() }}', status: status },
                        success: function(res) {
                            table.ajax.reload();
                            Swal.fire('Updated', res.message, 'success');
                        }
                    });
                }
            });
        }
    });
</script>
@endsection