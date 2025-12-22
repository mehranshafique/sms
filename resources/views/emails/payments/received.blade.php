<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Helvetica', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { width: 100%; max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #eee; border-radius: 8px; }
        .header { background-color: #f8f9fa; padding: 20px; text-align: center; border-bottom: 2px solid #007bff; border-radius: 8px 8px 0 0; }
        .header h2 { margin: 0; color: #007bff; }
        .content { padding: 20px; }
        .details-table { width: 100%; margin: 20px 0; border-collapse: collapse; }
        .details-table td { padding: 10px; border-bottom: 1px solid #eee; }
        .details-table td:first-child { font-weight: bold; width: 40%; color: #555; }
        .amount { color: #28a745; font-weight: bold; font-size: 18px; }
        .footer { text-align: center; font-size: 12px; color: #999; margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{ __('payment.payment_receipt') }}</h2>
            <p>{{ $payment->institution->name }}</p>
        </div>
        <div class="content">
            <p>{{ __('payment.email_greeting', ['name' => $payment->invoice->student->full_name]) }}</p>
            <p>{{ __('payment.email_intro', ['invoice' => $payment->invoice->invoice_number]) }}</p>
            
            <table class="details-table">
                <tr>
                    <td>{{ __('payment.transaction_id') }}:</td>
                    <td>{{ $payment->transaction_id }}</td>
                </tr>
                <tr>
                    <td>{{ __('payment.payment_date') }}:</td>
                    <td>{{ $payment->payment_date->format('d M, Y') }}</td>
                </tr>
                <tr>
                    <td>{{ __('payment.amount_paid') }}:</td>
                    <td class="amount">{{ number_format($payment->amount, 2) }}</td>
                </tr>
                <tr>
                    <td>{{ __('payment.method') }}:</td>
                    <td>{{ ucfirst($payment->method) }}</td>
                </tr>
            </table>

            <p><strong>{{ __('payment.remaining_balance') }}:</strong> {{ number_format($payment->invoice->total_amount - $payment->invoice->paid_amount, 2) }}</p>
            
            <p>{{ __('payment.thank_you') }}</p>
        </div>
        <div class="footer">
            <p>{{ $payment->institution->address }}</p>
            <p>&copy; {{ date('Y') }} {{ $payment->institution->name }}. {{ __('payment.all_rights_reserved') }}</p>
        </div>
    </div>
</body>
</html>