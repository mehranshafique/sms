@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('budget.requests_title') }}</h4>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="requestsTable" class="display" style="min-width: 845px">
                                <thead>
                                    <tr>
                                        <th>{{ __('budget.date') }}</th>
                                        <th>{{ __('budget.request_title') }}</th>
                                        <th>{{ __('budget.category') }}</th>
                                        <th>{{ __('budget.amount') }}</th>
                                        <th>{{ __('budget.requested_by') }}</th>
                                        <th>{{ __('budget.status') }}</th>
                                        <th>{{ __('budget.actions') }}</th>
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
            ajax: "{{ route('budgets.requests') }}",
            columns: [
                { data: 'created_at', name: 'created_at' },
                { data: 'title', name: 'title' },
                { data: 'category_name', name: 'budget.category.name' },
                { data: 'amount', name: 'amount' },
                { data: 'requester_name', name: 'requester.name' },
                { data: 'status', name: 'status' },
                { data: 'action', orderable: false, searchable: false }
            ],
            order: [[0, 'desc']]
        });

        // Approve Action
        $(document).on('click', '.approve-btn', function() {
            let id = $(this).data('id');
            updateRequest(id, 'approved');
        });

        // Reject Action
        $(document).on('click', '.reject-btn', function() {
            let id = $(this).data('id');
            
            Swal.fire({
                title: '{{ __('budget.reject') }} Request',
                input: 'text',
                inputLabel: '{{ __('budget.rejection_reason') }}',
                showCancelButton: true,
                confirmButtonText: '{{ __('budget.reject') }}',
                confirmButtonColor: '#d33',
                showLoaderOnConfirm: true,
                preConfirm: (reason) => {
                    return $.ajax({
                        url: "/finance/budgets/requests/" + id + "/update",
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            status: 'rejected',
                            rejection_reason: reason
                        }
                    }).catch(error => {
                        Swal.showValidationMessage(`Request failed: ${error}`);
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire('Rejected!', '', 'success');
                    table.ajax.reload();
                }
            });
        });

        function updateRequest(id, status) {
            Swal.fire({
                title: '{{ __('budget.confirm_approve') }}',
                text: "You are about to " + status + " this request.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Yes, ' + status + ' it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "/finance/budgets/requests/" + id + "/update",
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            status: status
                        },
                        success: function(response) {
                            Swal.fire('Success', response.message, 'success');
                            table.ajax.reload();
                        },
                        error: function() {
                            Swal.fire('Error', 'Action failed', 'error');
                        }
                    });
                }
            });
        }
    });
</script>
@endsection