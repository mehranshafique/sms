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
                    <h4>Invoices & Payments</h4>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-rounded">
                    <i class="fa fa-plus me-2"></i> Generate Bulk Invoices
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
                                        <th>Invoice #</th>
                                        <th>Student</th>
                                        <th>Issue Date</th>
                                        <th>Due Date</th>
                                        <th>Total</th>
                                        <th>Paid</th>
                                        <th>Status</th>
                                        <th class="text-end">Action</th>
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

<script>
    $(document).ready(function() {
        $('#invoiceTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('invoices.index') }}",
            columns: [
                { data: 'DT_RowIndex', name: 'id' },
                { data: 'invoice_number', name: 'invoice_number' },
                { data: 'student_name', name: 'student.first_name' },
                { data: 'issue_date', name: 'issue_date' },
                { data: 'due_date', name: 'due_date' },
                { data: 'total_amount', name: 'total_amount' },
                { data: 'paid_amount', name: 'paid_amount' },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
            ]
        });
    });
</script>
@endsection