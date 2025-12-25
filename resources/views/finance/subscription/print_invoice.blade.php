<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('subscription.invoice_title') }} #{{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 14px; color: #333; line-height: 1.4; }
        table { width: 100%; border-collapse: collapse; }
        
        /* Header Section */
        .header-table td { vertical-align: top; }
        .logo { width: 150px; }
        .company-details { text-align: right; }
        .company-details h2 { color: #007bff; margin: 0 0 5px 0; }
        
        /* Invoice Details */
        .details-box { margin-top: 30px; margin-bottom: 30px; border: 1px solid #eee; padding: 15px; }
        .details-table td { padding: 5px; }
        .heading { font-weight: bold; width: 120px; color: #555; }
        
        /* Items Table */
        .items-table { margin-bottom: 20px; border: 1px solid #ddd; }
        .items-table th { background-color: #f8f9fa; border-bottom: 2px solid #ddd; padding: 10px; text-align: left; }
        .items-table td { padding: 10px; border-bottom: 1px solid #eee; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        /* Totals */
        .totals-table { width: 100%; }
        .totals-table td { padding: 5px; }
        .total-row td { font-weight: bold; font-size: 16px; border-top: 2px solid #ddd; padding-top: 10px; }
        
        /* Status Badges */
        .status { padding: 5px 10px; color: white; border-radius: 4px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .status-paid { background-color: #28a745; }
        .status-unpaid { background-color: #dc3545; }
        .status-partial { background-color: #ffc107; color: black; }
        
        /* Footer */
        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #777; border-top: 1px solid #eee; padding-top: 20px; }
        
        /* Print Button - Hide in PDF or Print */
        @media print { .no-print { display: none; } }
        .no-print { text-align: center; margin-bottom: 20px; }
        .btn-print { background: #007bff; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 4px; text-decoration: none; }
    </style>
</head>
<body>
    @if(!$isPdf)
    <div class="no-print">
        <button onclick="window.print()" class="btn-print">{{ __('subscription.print') }}</button>
    </div>
    @endif

    <div class="invoice-box">
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td>
                    <img src="https://e-digitex.com/public/images/smsslogonew.png" alt="Logo" class="logo">
                </td>
                <td class="company-details">
                    <h2>{{ __('subscription.platform_invoice') }}</h2>
                    <div>Digitex System</div>
                    <div>support@digitex.com</div>
                </td>
            </tr>
        </table>

        <!-- Details -->
        <div class="details-box">
            <table>
                <tr>
                    <td style="width: 50%; vertical-align: top;">
                        <div style="font-weight: bold; margin-bottom: 5px; text-transform: uppercase; color: #777;">
                            {{ __('subscription.bill_to') }}
                        </div>
                        <strong>{{ $invoice->institution->name }}</strong><br>
                        {{ $invoice->institution->address }}<br>
                        {{ $invoice->institution->city }}<br>
                        {{ $invoice->institution->email }}
                    </td>
                    <td style="width: 50%; vertical-align: top; text-align: right;">
                        <table style="float: right;">
                            <tr>
                                <td class="heading">{{ __('subscription.invoice_number') }}:</td>
                                <td>{{ $invoice->invoice_number }}</td>
                            </tr>
                            <tr>
                                <td class="heading">{{ __('subscription.issue_date') }}:</td>
                                <td>{{ $invoice->invoice_date->format('d M, Y') }}</td>
                            </tr>
                            <tr>
                                <td class="heading">{{ __('subscription.due_date') }}:</td>
                                <td>{{ $invoice->due_date->format('d M, Y') }}</td>
                            </tr>
                            <tr>
                                <td class="heading">{{ __('subscription.status') }}:</td>
                                <td>
                                    <span class="status status-{{ $invoice->status }}">
                                        {{ ucfirst(__($invoice->status)) }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Items -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>{{ __('subscription.description') }}</th>
                    <th>{{ __('subscription.plan') }}</th>
                    <th>{{ __('subscription.duration') }}</th>
                    <th class="text-right">{{ __('subscription.amount') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ __('subscription.platform_invoice') }}</td>
                    <td>{{ $invoice->subscription->package->name ?? 'Custom' }}</td>
                    <td>
                        {{ $invoice->subscription->start_date->format('M Y') }} - 
                        {{ $invoice->subscription->end_date->format('M Y') }}
                    </td>
                    <td class="text-right">${{ number_format($invoice->total_amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Totals -->
        <table class="totals-table">
            <tr>
                <td style="width: 60%;"></td>
                <td style="width: 40%;">
                    <table style="width: 100%;">
                        <tr>
                            <td class="text-right">{{ __('subscription.subtotal') }}:</td>
                            <td class="text-right">${{ number_format($invoice->total_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="text-right">{{ __('subscription.paid') }}:</td>
                            <td class="text-right">${{ number_format($invoice->subscription->price_paid ?? 0, 2) }}</td>
                        </tr>
                        <tr class="total-row">
                            <td class="text-right">{{ __('subscription.total') }}:</td>
                            <td class="text-right">${{ number_format($invoice->total_amount, 2) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Footer -->
        <div class="footer">
            <p>{{ __('subscription.thank_you') }}</p>
            <div style="margin-top: 30px; border-top: 1px dashed #ccc; width: 200px; margin-left: auto; margin-right: auto; padding-top: 5px;">
                {{ __('subscription.authorized_signature') }}
            </div>
        </div>
    </div>
</body>
</html>