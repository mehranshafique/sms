<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('invoice.payment_receipt') }}</title>
    @php
        $isHtml = !empty($asHtml);
        $paperWidth = ($format ?? 'pos80') === 'pos58' ? '58mm' : '80mm';
        $baseFont = ($format ?? 'pos80') === 'pos58' ? '9px' : '11px';
        $titleFont = ($format ?? 'pos80') === 'pos58' ? '12px' : '14px';
        $paidFont = ($format ?? 'pos80') === 'pos58' ? '14px' : '16px';
    @endphp
    <style>
        @page { margin: 0; @if($isHtml) size: {{ $paperWidth }} auto; @endif }
        body {
            font-family: 'Helvetica', Arial, sans-serif;
            margin: {{ $isHtml ? '0' : '8px 5px' }};
            color: #000;
            font-size: {{ $baseFont }};
            line-height: 1.25;
            @if($isHtml)
            background: #e5e7eb;
            @endif
        }
        .receipt-wrap {
            @if($isHtml)
            width: {{ $paperWidth }};
            max-width: 100%;
            margin: 72px auto 24px;
            background: #fff;
            padding: 8px 5px;
            box-shadow: 0 4px 16px rgba(0,0,0,.15);
            @endif
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .line { border-bottom: 1px dashed #000; margin: 5px 0; }
        .line-solid { border-bottom: 1px solid #000; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 1px 0; }
        .amount-col { text-align: right; white-space: nowrap; }
        .summary-table td { padding: 3px 0; }
        .summary-table tr.due-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 4px 0;
        }
        .paid-highlight {
            font-size: {{ $paidFont }};
            font-weight: bold;
            text-align: center;
            margin: 6px 0 4px;
        }
        .status-badge {
            display: inline-block;
            font-size: 9px;
            font-weight: bold;
            padding: 2px 6px;
            border: 1px solid #000;
        }
        .footer { margin-top: 8px; text-align: center; font-size: 8px; }
        .qr { margin-top: 8px; text-align: center; }
        .meta-small { font-size: 8px; color: #333; }

        @if($isHtml)
        .no-print {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 50;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
            align-items: center;
            padding: 10px 12px;
            background: #0f172a;
            box-shadow: 0 2px 10px rgba(0,0,0,.25);
        }
        .no-print a, .no-print button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 0;
            border-radius: 10px;
            padding: 10px 14px;
            font-weight: 700;
            font-size: 14px;
            text-decoration: none;
            cursor: pointer;
        }
        .btn-print { background: #2563eb; color: #fff; }
        .btn-back { background: #334155; color: #fff; }
        .btn-pdf { background: #16a34a; color: #fff; }
        @media print {
            body { background: #fff !important; margin: 0 !important; }
            .no-print { display: none !important; }
            .receipt-wrap {
                margin: 0 !important;
                box-shadow: none !important;
                width: {{ $paperWidth }} !important;
                max-width: 100% !important;
            }
        }
        @endif
    </style>
    @if($isHtml)
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    @endif
</head>
<body>
@php
    $currency = \App\Enums\CurrencySymbol::default();
    $contextPayment = receipt_context_payment($invoice, $payment ?? null);
    $subtotal = (float) $invoice->total_amount;
    $paidTotal = (float) $invoice->paid_amount;
    $thisPaymentAmount = $contextPayment ? (float) $contextPayment->amount : $paidTotal;
    $dueAmount = max(0, $subtotal - $paidTotal);
    $paymentDate = $contextPayment?->payment_date?->format('d/m/Y') ?? $invoice->issue_date->format('d/m/Y');
    $displayReceiptNo = receipt_display_number($invoice, $contextPayment);
    $statusLabel = invoice_status_tranche_label($invoice);
    $enrollment = $invoice->student->enrollments->firstWhere('academic_session_id', $invoice->academic_session_id)
        ?? $invoice->student->enrollments->sortByDesc('created_at')->first();
    $amountForWords = $thisPaymentAmount > 0
        ? $thisPaymentAmount
        : ($paidTotal > 0 ? $paidTotal : $dueAmount);
    $highlightAmount = $amountForWords;
    $verifyUrl = $contextPayment?->receipt_verify_token
        ? route('receipt.verify', $contextPayment->receipt_verify_token)
        : null;
    $institution = $invoice->institution;
@endphp

@if($isHtml)
<div class="no-print">
    <a class="btn-back" href="{{ route('invoices.show', $invoice->id) }}">
        <i class="fa fa-arrow-left"></i> {{ __('invoice.back') }}
    </a>
    <button type="button" class="btn-print" onclick="window.print()">
        <i class="fa fa-print"></i> {{ __('invoice.print') }}
    </button>
    <a class="btn-pdf" href="{{ route('invoices.print_receipt', array_filter(['invoice' => $invoice->id, 'format' => $format ?? 'pos80', 'pdf' => 1, 'payment_id' => $payment->id ?? null])) }}" target="_blank">
        <i class="fa fa-file-pdf"></i> PDF
    </a>
</div>
@endif

<div class="receipt-wrap">
@if($institution?->logo)
<div class="text-center" style="margin: 0 0 6px;">
    <img src="{{ asset('storage/' . $institution->logo) }}" alt="Logo"
         style="display:block;margin:0 auto;max-height:56px;max-width:70%;width:auto;height:auto;object-fit:contain;">
</div>
@endif
<div class="text-center bold" style="font-size: {{ $titleFont }};">
    {{ $institution->name ?? config('app.name') }}
</div>
@if($institution?->address || $institution?->phone)
<div class="text-center meta-small">
    @if($institution->address){{ $institution->address }}@endif
    @if($institution->address && $institution->phone) · @endif
    @if($institution->phone){{ $institution->phone }}@endif
</div>
@endif
<div class="text-center bold" style="margin: 4px 0 6px;">{{ __('invoice.payment_receipt') }}</div>
<div class="line"></div>

<table>
    <tr>
        <td>{{ __('invoice.receipt_no') }}</td>
        <td class="amount-col bold">{{ $displayReceiptNo }}</td>
    </tr>
    <tr>
        <td colspan="2" class="meta-small">{{ __('invoice.invoice_ref') }}: {{ $invoice->invoice_number }}</td>
    </tr>
    <tr>
        <td>{{ __('invoice.payment_date') }}</td>
        <td class="amount-col">{{ $paymentDate }}</td>
    </tr>
    <tr>
        <td>{{ __('invoice.status_tranche') }}</td>
        <td class="amount-col"><span class="status-badge">{{ $statusLabel }}</span></td>
    </tr>
</table>

<div class="line"></div>

<table>
    <tr><td>{{ __('invoice.student_name') }}</td><td class="amount-col">{{ $invoice->student->full_name }}</td></tr>
    <tr><td>{{ __('invoice.student_id') }}</td><td class="amount-col">{{ $invoice->student->admission_number }}</td></tr>
    <tr><td>{{ __('invoice.class') }}</td><td class="amount-col">{{ class_section_label($enrollment?->classSection) }}</td></tr>
    <tr><td>{{ __('invoice.school_year') }}</td><td class="amount-col">{{ $invoice->academicSession->name ?? 'N/A' }}</td></tr>
</table>

<div class="line"></div>

<table>
    <tr class="bold">
        <td>{{ __('invoice.designation') }}</td>
        <td class="amount-col">{{ __('invoice.amount') }} ({{ $currency }})</td>
    </tr>
    @foreach($invoice->items as $index => $item)
    <tr>
        <td>{{ $index + 1 }}. {{ localize_invoice_description($item->description) }}</td>
        <td class="amount-col">{{ number_format($item->amount, 2) }}</td>
    </tr>
    @endforeach
</table>

<div class="line-solid"></div>

<table class="summary-table">
    <tr>
        <td>{{ __('invoice.subtotal') }}</td>
        <td class="amount-col">{{ $currency }} {{ number_format($subtotal, 2) }}</td>
    </tr>
    <tr>
        <td>{{ __('invoice.amount_paid') }}</td>
        <td class="amount-col bold">{{ $currency }} {{ number_format($paidTotal, 2) }}</td>
    </tr>
    @if($contextPayment && $thisPaymentAmount > 0 && abs($thisPaymentAmount - $paidTotal) > 0.009)
    <tr>
        <td>{{ __('invoice.this_payment') }}</td>
        <td class="amount-col bold">{{ $currency }} {{ number_format($thisPaymentAmount, 2) }}</td>
    </tr>
    @endif
    <tr class="due-row">
        <td>{{ __('invoice.payment_due') }}</td>
        <td class="amount-col">{{ $currency }} {{ number_format($dueAmount, 2) }}</td>
    </tr>
</table>

<div class="paid-highlight">{{ $currency }} {{ number_format($highlightAmount, 2) }}</div>
<div class="text-center meta-small" style="margin-bottom: 4px;">
    {{ __('invoice.amount_in_words') }}: {{ ucfirst(amount_in_words($amountForWords)) }}
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
</div>{{-- /.receipt-wrap --}}

@if($isHtml && !empty($autoPrint))
<script>
    window.addEventListener('load', function () {
        setTimeout(function () { window.print(); }, 400);
    });
</script>
@endif
</body>
</html>
