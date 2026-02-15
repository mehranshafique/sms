@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('requests.page_title') }}</h4>
                    <p class="mb-0">{{ __('requests.subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('requests.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus me-2"></i> {{ __('requests.create_new') }}
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="requestsTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('requests.ticket_number') }}</th>
                                        <th>{{ __('requests.applicant') }}</th>
                                        <th>{{ __('requests.request_type') }}</th>
                                        <th>{{ __('requests.date_submitted') }}</th>
                                        <th>{{ __('requests.status') }}</th>
                                        <th class="text-end">{{ __('requests.action') }}</th>
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
        var table = $('#requestsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('requests.index') }}",
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'ticket_number', name: 'ticket_number' },
                { data: 'applicant', name: 'student.first_name' },
                { data: 'type', name: 'type' },
                { data: 'created_at', name: 'created_at' },
                { data: 'status', name: 'status' },
                { data: 'action', orderable: false, searchable: false, className: 'text-end' }
            ]
        });

        // Approve/Reject Handlers
        $(document).on('click', '.approve-btn', function() { changeStatus($(this).data('id'), 'approved'); });
        $(document).on('click', '.reject-btn', function() { changeStatus($(this).data('id'), 'rejected'); });

        function changeStatus(id, status) {
            Swal.fire({
                title: status === 'approved' ? "{{ __('requests.confirm_approve') }}" : "{{ __('requests.confirm_reject') }}",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: status === 'approved' ? '#28a745' : '#d33',
                confirmButtonText: 'Yes'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "/requests/update-status/" + id,
                        type: 'GET', // Simplified for this example, ideally POST/PUT
                        data: { status: status },
                        success: function(res) {
                            table.ajax.reload();
                            Swal.fire('Success', res.message, 'success');
                        }
                    });
                }
            });
        }
    });
</script>
@endsection