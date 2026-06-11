@extends('layout.layout')

@section('content')
<div class="content-body"><div class="container-fluid">
    <h4>{{ __('guardian.my_fees') }} — {{ $student->full_name }}</h4>
    <p class="text-danger fw-bold">{{ __('guardian.outstanding') }}: {{ $outstandingFormatted ?? number_format($outstanding, 2) }}</p>
    <div class="card"><div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>#</th><th>{{ __('payment.total_amount') }}</th><th>{{ __('payment.remaining_balance') }}</th><th>{{ __('state_exam.status') }}</th></tr></thead>
        <tbody>
            @foreach($invoices as $inv)
                <tr>
                    <td>{{ $inv->invoice_number }}</td>
                    <td>{{ number_format($inv->total_amount, 2) }}</td>
                    <td>{{ number_format($inv->total_amount - $inv->paid_amount, 2) }}</td>
                    <td>{{ ucfirst($inv->status) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table></div></div>
    <a href="{{ route('guardian.index') }}" class="btn btn-light mt-3">{{ __('institute.cancel') }}</a>
</div></div>
@endsection
