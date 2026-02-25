@extends('layout.layout')

@section('styles')
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/select/1.7.0/css/select.dataTables.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
@endsection

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('invoice.page_title') }}</h4>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-rounded">
                    <i class="fa fa-plus me-2"></i> {{ __('invoice.generate_btn') }}
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="invoiceTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('invoice.invoice_number') }}</th>
                                        <th>{{ __('invoice.student') }}</th>
                                        <th>{{ __('invoice.description') }}</th>
                                        <th>{{ __('invoice.issue_date') }}</th>
                                        <th>{{ __('invoice.due_date') }}</th>
                                        <th>{{ __('invoice.total') }}</th>
                                        <th>{{ __('invoice.paid') }}</th>
                                        <th>{{ __('invoice.status') }}</th>
                                        <th class="text-end">{{ __('invoice.action') }}</th>
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
        var table = $('#invoiceTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('invoices.index') }}",
            columns: [
                { data: 'DT_RowIndex', name: 'id', orderable: false, searchable: false },
                { data: 'invoice_number', name: 'invoice_number' },
                // CHANGED: Use student_name as the internal 'name' property to map to the new backend flexible search filter.
                { data: 'student_name', name: 'student_name' }, 
                { data: 'fee_name', name: 'fee_name', orderable: false, searchable: false },
                { data: 'issue_date', name: 'issue_date' },
                { data: 'due_date', name: 'due_date' },
                { data: 'total_amount', name: 'total_amount' },
                { data: 'paid_amount', name: 'paid_amount' },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
            ]
        });

        // Delete Action
        $(document).on('click', '.delete-btn', function() {
            let id = $(this).data('id');
            let url = "{{ route('invoices.destroy', ':id') }}";
            url = url.replace(':id', id);

            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            Swal.fire(
                                'Deleted!',
                                response.message,
                                'success'
                            );
                            table.ajax.reload();
                        },
                        error: function(xhr) {
                            let msg = xhr.responseJSON.message || 'Error occurred';
                            Swal.fire(
                                'Error!',
                                msg,
                                'error'
                            );
                        }
                    });
                }
            });
        });
    });
</script>
@endsection