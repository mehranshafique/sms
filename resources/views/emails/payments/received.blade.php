<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { width: 100%; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 20px; text-align: center; border-bottom: 3px solid #007bff; }
        .content { padding: 20px 0; }
        .footer { text-align: center; font-size: 12px; color: #999; margin-top: 20px; }
        .amount { color: #28a745; font-weight: bold; font-size: 18px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Payment Receipt</h2>
        </div>
        <div class="content">
            <p>Dear {{ $payment->invoice->student->full_name }},</p>
            <p>We have received your payment for <strong>Invoice #{{ $payment->invoice->invoice_number }}</strong>.</p>
            
            <table style="width: 100%; margin: 20px 0;">
                <tr>
                    <td><strong>Transaction ID:</strong></td>
                    <td>{{ $payment->transaction_id }}</td>
                </tr>
                <tr>
                    <td><strong>Date:</strong></td>
                    <td>{{ $payment->payment_date->format('d M, Y') }}</td>
                </tr>
                <tr>
                    <td><strong>Amount Paid:</strong></td>
                    <td class="amount">{{ number_format($payment->amount, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>Payment Method:</strong></td>
                    <td>{{ ucfirst($payment->method) }}</td>
                </tr>
            </table>

            <p><strong>Remaining Balance:</strong> {{ number_format($payment->invoice->total_amount - $payment->invoice->paid_amount, 2) }}</p>
            
            <p>Thank you!</p>
        </div>
        <div class="footer">
            <p>{{ $payment->institution->name }}</p>
            <p>{{ $payment->institution->address }}</p>
        </div>
    </div>
</body>
</html>