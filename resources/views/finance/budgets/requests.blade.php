@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0 mb-4">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('budget.requests_title') }}</h4>
                    <p class="mb-0 text-muted">Manage and monitor all fund requests</p>
                </div>
            </div>
        </div>

        {{-- HeadOff Overview Summary Cards --}}
        <div class="row mb-4">
            <div class="col-xl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card shadow-sm bg-warning light">
                    <div class="card-body p-4">
                        <div class="media">
                            <span class="me-3"><i class="fa fa-clock-o text-warning fs-24"></i></span>
                            <div class="media-body text-end">
                                <p class="mb-1 text-dark">{{ __('budget.total_pending_req') }}</p>
                                <h3 class="text-dark">{{ $totalPending }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card shadow-sm bg-info light">
                    <div class="card-body p-4">
                        <div class="media">
                            <span class="me-3"><i class="fa fa-tasks text-info fs-24"></i></span>
                            <div class="media-body text-end">
                                <p class="mb-1 text-dark">{{ __('budget.total_processed_req') }}</p>
                                <h3 class="text-dark">{{ $totalProcessed }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card shadow-sm bg-primary light">
                    <div class="card-body p-4">
                        <div class="media">
                            <span class="me-3"><i class="fa fa-money text-primary fs-24"></i></span>
                            <div class="media-body text-end">
                                <p class="mb-1 text-dark">{{ __('budget.total_requested_amt') }}</p>
                                <h3 class="text-dark">{{ number_format($totalRequestedAmt, 2) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card shadow-sm bg-success light">
                    <div class="card-body p-4">
                        <div class="media">
                            <span class="me-3"><i class="fa fa-check-circle text-success fs-24"></i></span>
                            <div class="media-body text-end">
                                <p class="mb-1 text-dark">{{ __('budget.total_approved_amt') }}</p>
                                <h3 class="text-dark">{{ number_format($totalApprovedAmt, 2) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="requestsTable" class="display table table-striped" style="min-width: 845px">
                                <thead>
                                    <tr>
                                        <th>{{ __('budget.ticket_number') }}</th>
                                        @if($isHeadOfficer)
                                        <th>{{ __('budget.branch') }}</th>
                                        @endif
                                        <th>{{ __('budget.date') }}</th>
                                        <th>{{ __('budget.request_title') }}</th>
                                        <th>{{ __('budget.category') }}</th>
                                        <th>{{ __('budget.amount') }}</th>
                                        <th>{{ __('budget.requested_by') }}</th>
                                        <th>{{ __('budget.status') }}</th>
                                        <th class="text-end">{{ __('budget.actions') }}</th>
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
        var isHeadOfficer = {{ $isHeadOfficer ? 'true' : 'false' }};
        
        var columns = [
            { data: 'ticket_number', name: 'ticket_number' }
        ];

        if (isHeadOfficer) {
            columns.push({ data: 'branch', name: 'institution.name' });
        }

        columns.push(
            { data: 'created_at', name: 'created_at' },
            { data: 'request_title', name: 'title' },
            { data: 'category', name: 'budget.category.name' },
            { data: 'amount', name: 'amount' },
            { data: 'requested_by', name: 'requester.name' },
            { data: 'status', name: 'status' },
            { data: 'action', orderable: false, searchable: false, className: 'text-end' }
        );

        var table = $('#requestsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('budgets.requests') }}",
            columns: columns,
            order: [[isHeadOfficer ? 2 : 1, 'desc']] // Order by Date natively
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
                title: '{{ __('budget.rejection_reason') }}',
                input: 'textarea',
                inputPlaceholder: 'Enter reason for rejection...',
                showCancelButton: true,
                confirmButtonText: '{{ __('budget.reject') }}',
                confirmButtonColor: '#d33',
                preConfirm: (reason) => {
                    if (!reason) Swal.showValidationMessage('Reason is required');
                    return reason;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "/finance/budgets/requests/" + id + "/update",
                        type: 'POST',
                        data: { _token: '{{ csrf_token() }}', status: 'rejected', rejection_reason: result.value },
                        success: function(res) {
                            Swal.fire('Rejected!', res.message, 'success');
                            table.ajax.reload();
                        }
                    });
                }
            });
        });

        function updateRequest(id, status) {
            Swal.fire({
                title: '{{ __('budget.confirm_approve') }}',
                text: "You are about to " + status + " this fund request.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Yes, ' + status + ' it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "/finance/budgets/requests/" + id + "/update",
                        type: 'POST',
                        data: { _token: '{{ csrf_token() }}', status: status },
                        success: function(response) {
                            Swal.fire('Success', response.message, 'success');
                            table.ajax.reload();
                            // Reload page to update summary cards
                            setTimeout(() => location.reload(), 1500);
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