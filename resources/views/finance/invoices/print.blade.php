<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('invoice.invoice') }} #{{ $invoice->invoice_number }}</title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }
        body {
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            color: #555;
            background: #fff;
            font-size: 14px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            font-size: 16px;
            line-height: 24px;
            color: #555;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        /* Left Aligned Institution Details */
        .invoice-header .company-details {
            text-align: left; /* Changed from right to left */
        }
        .invoice-header .company-details h2 {
            margin: 0;
            color: #333;
        }
        .invoice-logo {
            max-width: 150px;
            max-height: 100px;
        }
        .invoice-info {
            margin-bottom: 40px;
            display: flex;
            justify-content: space-between;
        }
        .bill-to h3 {
            margin-top: 0;
            margin-bottom: 5px;
            color: #333;
        }
        /* Left Aligned Meta Table */
        .invoice-meta table {
            text-align: left; /* Changed from right to left */
        }
        .table-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table-items th {
            background: #f8f9fa;
            color: #333;
            font-weight: bold;
            text-align: left;
            padding: 10px;
            border-bottom: 2px solid #ddd;
        }
        .table-items td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .table-items .amount {
            text-align: right;
        }
        .totals {
            margin-top: 20px;
            text-align: right;
        }
        .totals table {
            margin-left: auto;
            border-collapse: collapse;
        }
        .totals td {
            padding: 5px 10px;
        }
        .totals .total-row {
            font-weight: bold;
            font-size: 1.1em;
            color: #333;
            border-top: 2px solid #eee;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            color: #fff;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
        }
        .status-paid { background-color: #28a745; }
        .status-unpaid { background-color: #dc3545; }
        .status-partial { background-color: #ffc107; color: #333; }
        
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 12px;
            color: #777;
        }

        @media print {
            .invoice-box {
                border: none;
                box-shadow: none;
                max-width: 100%;
                margin: 0;
                padding: 20px;
            }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="invoice-box">
        <!-- Header -->
        <div class="invoice-header">
            <!-- Swapped order or alignment changes -->
            <div class="company-details">
                <h2>{{ $invoice->institution->name }}</h2>
                <div>{{ $invoice->institution->address }}</div>
                <div>{{ $invoice->institution->city }}, {{ $invoice->institution->country }}</div>
                <div>{{ $invoice->institution->phone }}</div>
                <div>{{ $invoice->institution->email }}</div>
            </div>
            <div class="logo">
                @if($invoice->institution->logo)
                    <img src="{{ asset('storage/'.$invoice->institution->logo) }}" alt="Logo" class="invoice-logo">
                @else
                    <h1 style="color: #ddd;">LOGO</h1>
                @endif
            </div>
        </div>

        <!-- Info Section -->
        <div class="invoice-info">
            <div class="bill-to">
                <div style="color: #888; margin-bottom: 5px; text-transform: uppercase; font-size: 10px; letter-spacing: 1px;">{{ __('invoice.bill_to') }}</div>
                <h3>{{ $invoice->student->full_name }}</h3>
                <div>{{ __('invoice.id') }}: {{ $invoice->student->admission_number }}</div>
                <div>{{ __('invoice.class') }}: {{ $invoice->student->enrollments->last()->classSection->name ?? 'N/A' }}</div>
                <div>{{ __('invoice.session') }}: {{ $invoice->academicSession->name }}</div>
            </div>
            <div class="invoice-meta">
                <table>
                    <tr>
                        <td style="font-weight: bold; color: #333; padding-right: 10px;">{{ __('invoice.invoice_number') }}:</td>
                        <td>{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold; color: #333; padding-right: 10px;">{{ __('invoice.date') }}:</td>
                        <td>{{ $invoice->issue_date->format('d M, Y') }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold; color: #333; padding-right: 10px;">{{ __('invoice.due_date') }}:</td>
                        <td>{{ $invoice->due_date->format('d M, Y') }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold; color: #333; padding-right: 10px;">{{ __('invoice.status_label') }}:</td>
                        <td>
                            <span class="status-badge status-{{ $invoice->status }}">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Line Items -->
        <table class="table-items">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('invoice.description') }}</th>
                    <th class="amount">{{ __('invoice.amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="amount">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($item->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <table>
                <tr>
                    <td>{{ __('invoice.subtotal') }}:</td>
                    <td>{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($invoice->total_amount, 2) }}</td>
                </tr>
                <tr>
                    <td>{{ __('invoice.paid_to_date') }}:</td>
                    <td>{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($invoice->paid_amount, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td>{{ __('invoice.balance_due') }}:</td>
                    <td>{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($invoice->total_amount - $invoice->paid_amount, 2) }}</td>
                </tr>
            </table>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>{{ __('invoice.thank_you') }}</p>
            <p>{{ __('invoice.authorized_signature') }}: __________________________</p>
        </div>
    </div>

</body>
</html>