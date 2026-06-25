<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('invoice.verify_receipt') }}</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 24px; color: #111827; }
        .card { max-width: 520px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,.08); overflow: hidden; }
        .head { background: #083366; color: #fff; padding: 20px 24px; }
        .head h1 { margin: 0 0 4px; font-size: 20px; }
        .head p { margin: 0; opacity: .85; font-size: 14px; }
        .body { padding: 24px; }
        .row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
        .row:last-child { border-bottom: 0; }
        .label { color: #6b7280; }
        .value { font-weight: 700; text-align: right; }
        .ok { display: inline-block; margin-top: 16px; padding: 8px 12px; background: #ecfdf5; color: #047857; border-radius: 8px; font-size: 13px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="card">
        <div class="head">
            <h1>{{ $invoice->institution->name ?? config('app.name') }}</h1>
            <p>{{ __('invoice.verify_receipt') }}</p>
        </div>
        <div class="body">
            <div class="row"><span class="label">{{ __('invoice.student_name') }}</span><span class="value">{{ $student->full_name }}</span></div>
            <div class="row"><span class="label">{{ __('invoice.class') }}</span><span class="value">{{ class_section_label($enrollment?->classSection) }}</span></div>
            <div class="row"><span class="label">{{ __('invoice.receipt_no') }}</span><span class="value">{{ $payment->receipt_number ?? $invoice->invoice_number }}</span></div>
            <div class="row"><span class="label">{{ __('invoice.amount_paid') }}</span><span class="value">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($payment->amount, 2) }}</span></div>
            <div class="row"><span class="label">{{ __('invoice.payment_due') }}</span><span class="value">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($balanceDue, 2) }}</span></div>
            <div class="row"><span class="label">{{ __('invoice.school_year') }}</span><span class="value">{{ $invoice->academicSession->name ?? 'N/A' }}</span></div>
            <div class="row"><span class="label">{{ __('invoice.payment_date') }}</span><span class="value">{{ $payment->payment_date?->format('d/m/Y') }}</span></div>
            <div class="ok">{{ __('invoice.receipt_verified') }}</div>
        </div>
    </div>
</body>
</html>
