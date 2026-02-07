@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        <div class="row page-titles mx-0 mb-4 p-4 bg-white rounded shadow-sm">
            <div class="col-sm-6 p-0">
                <div class="welcome-text">
                    <h4 class="text-primary">{{ __('finance.student_statement') }}</h4>
                    <p class="mb-0 text-muted">{{ $student->full_name }} ({{ $student->admission_number }})</p>
                </div>
            </div>
            <div class="col-sm-6 p-0 text-end">
                <a href="{{ route('statement.pdf', $student->id) }}" class="btn btn-danger btn-sm shadow">
                    <i class="fa fa-file-pdf-o me-2"></i> {{ __('finance.export_pdf') }}
                </a>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="row mb-4">
            <div class="col-xl-4 col-sm-6">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <h6 class="text-white opacity-75">{{ __('finance.total_invoiced') }}</h6>
                        <h3 class="text-white mb-0">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($totalInvoiced, 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-sm-6">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <h6 class="text-white opacity-75">{{ __('finance.total_paid') }}</h6>
                        <h3 class="text-white mb-0">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($totalPaid, 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-sm-6">
                <div class="card {{ $balance > 0 ? 'bg-danger' : 'bg-secondary' }} text-white h-100">
                    <div class="card-body">
                        <h6 class="text-white opacity-75">{{ __('finance.outstanding_balance') }}</h6>
                        <h3 class="text-white mb-0">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($balance, 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        {{-- Ledger Table --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title">{{ __('finance.transaction_history') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="statementTable" class="table table-striped table-bordered display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('finance.date') }}</th>
                                        <th>{{ __('finance.type') }}</th>
                                        <th>{{ __('finance.reference') }}</th>
                                        <th>{{ __('finance.description') }}</th>
                                        <th>{{ __('finance.debit') }} (Invoice)</th>
                                        <th>{{ __('finance.credit') }} (Payment)</th>
                                        <th>{{ __('finance.status') }}</th>
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
<script>
    $(document).ready(function() {
        $('#statementTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('statement.show', $student->id) }}",
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'date' },
                { 
                    data: 'type',
                    render: function(data) {
                        let color = data === 'invoice' ? 'primary' : 'success';
                        return `<span class="badge badge-${color} light">${data}</span>`;
                    }
                },
                { data: 'ref' },
                { data: 'description' },
                { data: 'debit', className: 'text-end text-danger' },
                { data: 'credit', className: 'text-end text-success fw-bold' },
                { data: 'status' }
            ],
            order: [[1, 'desc']], // Date DESC
            pageLength: 25
        });
    });
</script>
@endsection