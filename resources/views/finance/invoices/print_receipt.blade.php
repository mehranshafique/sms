<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('invoice.payment_receipt') }}</title>
    <style>
        @page { margin: 0px; }
        body {
            font-family: 'Helvetica', sans-serif;
            margin: 10px 5px;
            color: #000;
            font-size: {{ ($format ?? 'pos80') === 'pos58' ? '9px' : '11px' }};
            line-height: 1.2;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .line { border-bottom: 1px dashed #000; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 1px 0; }
        .amount-col { text-align: right; }
        .total-box { margin-top: 5px; padding: 5px 0; border-top: 1px solid #000; border-bottom: 1px solid #000; }
        .net-amount { font-size: {{ ($format ?? 'pos80') === 'pos58' ? '14px' : '16px' }}; font-weight: bold; }
        .footer { margin-top: 10px; text-align: center; font-size: 8px; }
        .qr { margin-top: 8px; text-align: center; }
    </style>
</head>
<body>
@php
    $currency = \App\Enums\CurrencySymbol::default();
    $dueAmount = $invoice->total_amount - $invoice->paid_amount;
    $lastPayment = $invoice->payments->last();
    $lastPaymentDate = $lastPayment?->payment_date?->format('d/m/Y') ?? $invoice->issue_date->format('d/m/Y');
    $displayReceiptNo = receipt_display_number($invoice);
    $enrollment = $invoice->student->enrollments->firstWhere('academic_session_id', $invoice->academic_session_id)
        ?? $invoice->student->enrollments->sortByDesc('created_at')->first();
    $amountForWords = $invoice->paid_amount > 0 ? (float) $invoice->paid_amount : (float) $dueAmount;
    $verifyUrl = $lastPayment?->receipt_verify_token
        ? route('receipt.verify', $lastPayment->receipt_verify_token)
        : null;
@endphp

<div class="text-center bold" style="font-size: {{ ($format ?? 'pos80') === 'pos58' ? '12px' : '14px' }};">
    {{ $invoice->institution->name ?? config('app.name') }}
</div>
<div class="text-center" style="margin-bottom: 6px;">{{ __('invoice.payment_receipt') }}</div>
<div class="line"></div>

<table>
    <tr><td>{{ __('invoice.receipt_no') }}</td><td class="amount-col bold">{{ $displayReceiptNo }}</td></tr>
    <tr><td colspan="2" style="font-size: 8px;">{{ __('invoice.invoice_ref') }}: {{ $invoice->invoice_number }}</td></tr>
    <tr><td>{{ __('invoice.payment_date') }}</td><td class="amount-col">{{ $lastPaymentDate }}</td></tr>
</table>

<div class="line"></div>

<table>
    <tr><td>{{ __('invoice.student_name') }}</td><td class="amount-col">{{ $invoice->student->formal_name }}</td></tr>
    <tr><td>{{ __('invoice.student_id') }}</td><td class="amount-col">{{ $invoice->student->admission_number }}</td></tr>
    <tr><td>{{ __('invoice.class') }}</td><td class="amount-col">{{ class_section_label($enrollment?->classSection) }}</td></tr>
    <tr><td>{{ __('invoice.school_year') }}</td><td class="amount-col">{{ $invoice->academicSession->name ?? 'N/A' }}</td></tr>
</table>

<div class="line"></div>

<table>
    @foreach($invoice->items as $item)
    <tr>
        <td>{{ localize_invoice_description($item->description) }}</td>
        <td class="amount-col">{{ number_format($item->amount, 2) }}</td>
    </tr>
    @endforeach
</table>

<div class="total-box text-center">
    <div>{{ __('invoice.amount_paid') }}</div>
    <div class="net-amount">{{ $currency }} {{ number_format($invoice->paid_amount, 2) }}</div>
    <div style="margin-top: 4px; font-size: 9px;">
        ({{ __('invoice.amount_in_words') }}: {{ amount_in_words($amountForWords) }})
    </div>
    @if($dueAmount > 0)
    <div style="margin-top: 4px;">{{ __('invoice.payment_due') }}: {{ $currency }} {{ number_format($dueAmount, 2) }}</div>
    @endif
</div>

@if($verifyUrl)
<div class="qr">
    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode($verifyUrl) }}" alt="QR" style="width: 80px; height: 80px;">
</div>
@endif

<div class="footer">
    {{ __('invoice.print_date') }}: {{ now()->format('d/m/Y H:i') }}<br>
    {{ __('invoice.thank_you') }}
</div>
</body>
</html>
