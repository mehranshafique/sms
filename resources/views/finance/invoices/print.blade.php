<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ trans()->has('invoice.invoice') ? __('invoice.invoice') : 'Facture' }} #{{ $invoice->invoice_number }}</title>
    
    @php
        $currency = \App\Enums\CurrencySymbol::default();
        $isPaid = $invoice->status === 'paid';
        
        // Document Title
        $docTitle = $invoice->payments->count() > 0 
            ? (trans()->has('invoice.payment_receipt') ? __('invoice.payment_receipt') : 'PAYMENT RECEIPT') 
            : (trans()->has('invoice.invoice') ? __('invoice.invoice') : 'INVOICE');
            
        $dueAmount = $invoice->total_amount - $invoice->paid_amount;
        $lastPaymentDate = $invoice->payments->count() > 0 ? $invoice->payments->last()->payment_date->format('d/m/Y') : $invoice->issue_date->format('d/m/Y');
        
        // Installment Calculation Logic
        $label = invoice_status_tranche_label($invoice);
        
        $enrollment = $invoice->student->enrollments
            ->firstWhere('academic_session_id', $invoice->academic_session_id)
            ?? $invoice->student->enrollments->sortByDesc('created_at')->first();

        // Exact Translation Fallbacks
        $txtReceiptNo = trans()->has('invoice.receipt_no') ? __('invoice.receipt_no') : 'RECEIPT NO.';
        $txtPaymentDate = trans()->has('invoice.payment_date') ? __('invoice.payment_date') : 'Payment date';
        $txtStatus = trans()->has('invoice.status_tranche') ? __('invoice.status_tranche') : 'Status / Tranche';
        $txtReceivedFor = trans()->has('invoice.received_for') ? __('invoice.received_for') : 'RECEIVED FOR';
        $txtStudentName = trans()->has('invoice.student_name') ? __('invoice.student_name') : 'Student\'s name';
        $txtStudentId = trans()->has('invoice.student_id') ? __('invoice.student_id') : 'Student ID';
        $txtClass = trans()->has('invoice.class') ? __('invoice.class') : 'Class';
        $txtYear = trans()->has('invoice.school_year') ? __('invoice.school_year') : 'School year';
        $txtNo = trans()->has('invoice.no') ? __('invoice.no') : 'No.';
        $txtDesignation = trans()->has('invoice.designation') ? __('invoice.designation') : 'DESIGNATION';
        $txtAmount = trans()->has('invoice.amount') ? __('invoice.amount') : 'AMOUNT';
        $txtSubtotal = trans()->has('invoice.subtotal') ? __('invoice.subtotal') : 'Subtotal:';
        $txtPaid = trans()->has('invoice.amount_paid') ? __('invoice.amount_paid') : 'Amount paid:';
        $txtDue = trans()->has('invoice.payment_due') ? __('invoice.payment_due') : 'Payment due:';
        $txtThanks = trans()->has('invoice.thank_you') ? __('invoice.thank_you') : 'Thank you for your trust.';
        $txtPrintDate = __('invoice.print_date');
        $txtAmountInWords = __('invoice.amount_in_words');
        $txtInvoiceRef = __('invoice.invoice_ref');

        $displayReceiptNo = receipt_display_number($invoice);
        $lastPayment = $invoice->payments->last();
        $amountForWords = $invoice->paid_amount > 0 ? (float) $invoice->paid_amount : (float) $dueAmount;
        $verifyUrl = $lastPayment?->receipt_verify_token
            ? route('receipt.verify', $lastPayment->receipt_verify_token)
            : null;
        $studentDisplayName = $invoice->student->full_name;
        
        $cityCountry = ($invoice->institution->city ?? 'Kinshasa');
        $encodedInvoiceNumber = urlencode($invoice->invoice_number);

        // Fetch barcode data server-side for DOMPDF to bypass remote image restrictions
        $barcodeBase64 = '';
        if (isset($isPdf) && $isPdf) {
            try {
                $context = stream_context_create(['http' => ['timeout' => 3]]);
                $barcodeData = @file_get_contents("https://bwipjs-api.metafloor.com/?bcid=code128&text={$encodedInvoiceNumber}&height=12", false, $context);
                if ($barcodeData) {
                    $barcodeBase64 = 'data:image/png;base64,' . base64_encode($barcodeData);
                }
            } catch (\Exception $e) {
                // Failsafe empty string
            }
        }
    @endphp

    @if(isset($isPdf) && $isPdf)
        <!-- ========================================== -->
        <!-- DOMPDF SAFE CSS ENGINE (For PDF Downloads) -->
        <!-- ========================================== -->
        <style>
            @page { margin: 40px; size: A4 portrait; }
            body { font-family: 'Helvetica', 'Arial', sans-serif; color: #111827; font-size: 13px; line-height: 1.4; margin: 0; padding: 0; }
            table { width: 100%; border-collapse: collapse; }
            .text-right { text-align: right; }
            .font-bold { font-weight: bold; }
            .uppercase { text-transform: uppercase; }
            .text-gray-500 { color: #4b5563; }
            .text-gray-900 { color: #000000; }
            
            /* Header */
            .logo { max-height: 70px; max-width: 150px; }
            .school-name { font-size: 22px; font-weight: 900; color: #083366; text-transform: uppercase; line-height: 1.1; margin-bottom: 8px; }
            .contact-info { font-size: 12px; color: #083366; line-height: 1.5; font-weight: 500; }
            
            /* Divider */
            .hr-line { border-top: 2px solid #083366; margin: 20px 0; }
            
            /* Section Headers */
            .section-header { background-color: #083366; color: #ffffff; padding: 4px 12px; border-radius: 3px; font-size: 12px; font-weight: bold; display: inline-block; text-transform: uppercase; margin-bottom: 5px; }
            .section-divider { border-top: 1px solid #d1d5db; margin-bottom: 15px; }
            
            /* Two Column Layout */
            .col-left { width: 55%; vertical-align: top; }
            .col-right { width: 45%; vertical-align: bottom; text-align: left; }
            
            .student-info-table td { padding: 4px 0; font-size: 13px; }
            .student-info-table td.label { width: 120px; color: #4b5563; }
            .student-info-table td.colon { width: 15px; color: #111827; font-weight: bold; }
            .student-info-table td.value { font-weight: bold; color: #111827; }
            
            .receipt-info-table { margin-left: auto; width: 100%; }
            .receipt-info-table td { padding: 4px 0; font-size: 13px; }
            .receipt-info-table td.label { width: 130px; font-weight: bold; color: #111827; }
            .receipt-info-table td.colon { width: 15px; color: #111827; font-weight: bold; }
            .receipt-info-table td.value { color: #111827; }
            
            /* Document Title Badge */
            .doc-title { font-size: 18px; font-weight: 900; color: #ffffff; background-color: #083366; text-transform: uppercase; padding: 8px 24px; border-radius: 4px; display: inline-block; letter-spacing: 1px; }
            .badge-status { background-color: #083366; color: #ffffff; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; }
            
            /* Items Table */
            .items-table { margin-top: 20px; margin-bottom: 20px; border: 1px solid #d1d5db; }
            .items-table th { background-color: #083366; color: #ffffff; padding: 8px 12px; text-align: left; font-size: 12px; text-transform: uppercase; font-weight: bold; border: 1px solid #d1d5db; }
            .items-table th.text-center { text-align: center; }
            .items-table th.text-right { text-align: right; }
            .items-table td { padding: 10px 12px; font-size: 13px; color: #111827; border: 1px solid #d1d5db; }
            .items-table td.text-center { text-align: center; }
            .items-table td.amount { font-weight: bold; text-align: right; }
            
            /* Summary Box */
            .summary-table { width: 280px; float: right; margin-top: 15px; border-collapse: collapse; }
            .summary-table td { padding: 6px 0; font-size: 13px; }
            .summary-table td.label { color: #4b5563; text-align: left; font-weight: 500; }
            .summary-table td.value { font-weight: bold; color: #111827; text-align: right; }
            .border-row td { border-top: 1px solid #d1d5db; }
            .due-box td { background-color: #e6f0fa; border-top: 1px solid #083366; border-bottom: 1px solid #083366; font-weight: bold; color: #083366; padding: 8px 10px; }
            
            /* Footer */
            .footer { clear: both; margin-top: 60px; font-size: 14px; color: #111827; text-align: center; border-top: 1px solid #d1d5db; padding-top: 15px; }
            .footer-thanks { font-style: italic; color: #4b5563; font-family: 'Times New Roman', serif; }
        </style>
    @else
        <!-- ========================================== -->
        <!-- TAILWIND ENGINE (For Web Browser Printing) -->
        <!-- ========================================== -->
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <style>
            @media print {
                body { background-color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; padding: 0 !important; }
                .no-print { display: none !important; }
                .print-shadow-none { box-shadow: none !important; max-width: 100% !important; margin: 0 !important; padding: 20px !important; border: none !important; }
            }
        </style>
    @endif
</head>
<body class="{{ isset($isPdf) && $isPdf ? '' : 'bg-gray-100 min-h-screen flex items-center justify-center py-10 px-4 font-sans text-gray-900' }}">

    @if(!isset($isPdf) || !$isPdf)
    <!-- Action Buttons (Hidden on Print) -->
    <div class="fixed top-4 right-4 flex gap-3 no-print z-10">
        <a href="{{ route('invoices.index') }}" class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded shadow-md font-semibold transition-all flex items-center gap-2">
            <i class="fa fa-arrow-left"></i> {{ __('invoice.back') ?? 'Back' }}
        </a>
        <a href="{{ route('invoices.download', $invoice->id) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow-md font-semibold transition-all flex items-center gap-2">
            <i class="fa fa-file-pdf"></i> PDF
        </a>
        <button onclick="window.print()" class="bg-[#083366] hover:bg-blue-900 text-white px-5 py-2 rounded shadow-md font-semibold transition-all flex items-center gap-2">
            <i class="fa fa-print"></i> {{ __('invoice.print') ?? 'Print' }}
        </button>
    </div>

    <!-- MAIN INVOICE CONTAINER -->
    <div class="bg-white w-full max-w-[850px] p-8 sm:p-12 relative overflow-hidden print-shadow-none mx-auto shadow-xl border border-gray-200">
        
        <!-- Header Section -->
        <header class="flex justify-between items-start mb-2">
            <!-- Left Side: Logo & Info -->
            <div class="flex items-start gap-5">
                @if($invoice->institution && $invoice->institution->logo)
                    <img src="{{ asset('storage/' . $invoice->institution->logo) }}" alt="Logo" class="h-[90px] object-contain flex-shrink-0">
                @endif
                <div class="flex flex-col mt-1">
                    <h1 class="text-2xl font-black text-[#083366] uppercase tracking-tight leading-tight mb-3">
                        {{ $invoice->institution->name ?? config('app.name') }}
                    </h1>
                    <div class="text-[13px] text-[#083366] font-medium leading-snug space-y-1.5">
                        <p class="flex items-center gap-2">
                            <i class="fa fa-map-marker-alt w-3 text-center"></i> {{ $invoice->institution->address ?? 'N/A' }}, {{ $cityCountry }}
                        </p>
                        @if($invoice->institution->phone)
                        <p class="flex items-center gap-2">
                            <i class="fa fa-phone w-3 text-center"></i> {{ $invoice->institution->phone }}
                        </p>
                        @endif
                        @if($invoice->institution->email)
                        <p class="flex items-center gap-2">
                            <i class="fa fa-envelope w-3 text-center"></i> {{ $invoice->institution->email }}
                        </p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Side: Barcode & Title -->
            <div class="flex flex-col items-end">
                <div class="mb-4">
                     <!-- Dynamic Barcode API -->
                     <img src="https://bwipjs-api.metafloor.com/?bcid=code128&text={{ $encodedInvoiceNumber }}&height=12" alt="Barcode" class="h-12 object-contain mix-blend-multiply">
                </div>
                <div class="bg-[#083366] text-white px-8 py-2.5 rounded-md font-bold text-xl uppercase tracking-wider text-center shadow-sm">
                    {{ $docTitle }}
                </div>
            </div>
        </header>

        <hr class="border-t-2 border-[#083366] my-6">

        <!-- Two Columns Layout -->
        <div class="flex justify-between items-end mb-8 gap-8">
            <!-- Left: Student Info -->
            <div class="w-[55%]">
                <div class="mb-3">
                    <span class="inline-block bg-[#083366] text-white px-4 py-1 rounded text-sm font-bold uppercase">
                        {{ $txtReceivedFor }}
                    </span>
                </div>
                <div class="w-full border-b border-gray-300 mb-4"></div>
                
                <table class="text-[14px] text-left w-full border-collapse">
                    <tr><td class="py-1 w-32 text-gray-700">{{ $txtStudentName }}</td><td class="py-1 font-bold text-gray-900 w-4">:</td><td class="py-1 font-bold text-gray-900">{{ $studentDisplayName }}</td></tr>
                    <tr><td class="py-1 text-gray-700">{{ $txtStudentId }}</td><td class="py-1 font-bold text-gray-900 w-4">:</td><td class="py-1 font-bold text-gray-900">{{ $invoice->student->admission_number }}</td></tr>
                    <tr><td class="py-1 text-gray-700">{{ $txtClass }}</td><td class="py-1 font-bold text-gray-900 w-4">:</td><td class="py-1 font-bold text-gray-900">{{ class_section_label($enrollment?->classSection) }}</td></tr>
                    <tr><td class="py-1 text-gray-700">{{ $txtYear }}</td><td class="py-1 font-bold text-gray-900 w-4">:</td><td class="py-1 font-bold text-gray-900">{{ $invoice->academicSession->name ?? 'N/A' }}</td></tr>
                </table>
            </div>
            
            <!-- Right: Receipt Details -->
            <div class="w-[45%] text-left">
                <table class="text-[14px] text-left border-collapse w-full">
                    <tr><td class="py-1.5 w-36 font-bold text-gray-900">{{ $txtReceiptNo }}</td><td class="py-1.5 font-bold text-gray-900 w-4">:</td><td class="py-1.5 font-bold text-gray-900"><span class="border border-gray-400 rounded-full px-3 py-0.5 bg-white">{{ $displayReceiptNo }}</span><div class="text-xs text-gray-500 font-normal mt-1">{{ $txtInvoiceRef }}: {{ $invoice->invoice_number }}</div></td></tr>
                    <tr><td class="py-1.5 font-bold text-gray-900">{{ $txtPaymentDate }}</td><td class="py-1.5 font-bold text-gray-900 w-4">:</td><td class="py-1.5 text-gray-900 px-2">{{ $lastPaymentDate }}</td></tr>
                    <tr><td class="py-1.5 font-bold text-gray-900">{{ $txtStatus }}</td><td class="py-1.5 font-bold text-gray-900 w-4">:</td><td class="py-1.5 font-bold text-gray-900"><span class="bg-[#083366] text-white px-3 py-1 rounded text-xs uppercase">{{ $label }}</span></td></tr>
                </table>
            </div>
        </div>

        <!-- Invoice Items Table -->
        <table class="w-full text-left border-collapse mb-8 border border-gray-300 text-[14px]">
            <thead>
                <tr class="bg-[#083366] text-white">
                    <th class="py-3 px-4 font-bold uppercase w-16 text-center border border-gray-300">{{ $txtNo }}</th>
                    <th class="py-3 px-4 font-bold uppercase text-center border border-gray-300">{{ $txtDesignation }}</th>
                    <th class="py-3 px-4 font-bold uppercase text-center w-40 border border-gray-300">{{ $txtAmount }} ({{ $currency }})</th>
                </tr>
            </thead>
            <tbody class="text-gray-800 bg-white">
                @foreach($invoice->items as $index => $item)
                <tr>
                    <td class="py-3 px-4 text-center border border-gray-300">{{ $index + 1 }}</td>
                    <td class="py-3 px-4 border border-gray-300 text-left">{{ localize_invoice_description($item->description) }}</td>
                    <td class="py-3 px-4 text-center border border-gray-300 font-bold">{{ number_format($item->amount, 2) }}</td>
                </tr>
                @endforeach
                
                <!-- Empty dashed rows for structure spacing -->
                @for($i = $invoice->items->count(); $i < 3; $i++)
                <tr class="h-10">
                    <td class="border border-gray-300 border-dashed border-t-0 border-b-0"></td>
                    <td class="border border-gray-300 border-dashed border-t-0 border-b-0"></td>
                    <td class="border border-gray-300 border-dashed border-t-0 border-b-0"></td>
                </tr>
                @endfor
                <tr><td colspan="3" class="border-t border-gray-300"></td></tr>
            </tbody>
        </table>

        <!-- Summary & Totals -->
        <div class="flex justify-between items-start mt-6 gap-8">
            <div class="w-[55%] text-[14px] text-gray-700 italic pt-2">
                <span class="font-semibold not-italic text-gray-800">{{ $txtAmountInWords }}:</span>
                {{ ucfirst(amount_in_words($amountForWords)) }}
            </div>
            <div class="w-full sm:w-[350px] text-[14px] text-gray-800 shrink-0">
                <div class="flex justify-between items-center py-2">
                    <span class="font-medium pr-4 text-gray-600">{{ $txtSubtotal }} :</span>
                    <span class="font-bold">{{ $currency }} {{ number_format($invoice->total_amount, 2) }}</span>
                </div>
                
                <div class="w-full border-t border-gray-300"></div>
                
                <div class="flex justify-between items-center py-2">
                    <span class="font-medium pr-4 text-gray-600">{{ $txtPaid }} :</span>
                    <span class="font-bold">{{ $currency }} {{ number_format($invoice->paid_amount, 2) }}</span>
                </div>
                
                <div class="flex justify-between items-center py-2.5 px-3 mt-1 bg-[#e6f0fa] border border-[#083366] font-bold text-[#083366]">
                    <span>{{ $txtDue }} :</span>
                    <span>{{ $currency }} {{ number_format($dueAmount, 2) }}</span>
                </div>
                
                <div class="w-full border-t border-gray-300 mt-2 mb-2"></div>
                
                <div class="flex justify-between items-center py-2 font-bold">
                    <span>{{ $txtPrintDate }} :</span>
                    <span>{{ now()->format('d/m/Y H:i') }}</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="mt-12 text-center relative pb-2 border-t border-gray-300 pt-5">
            @if($verifyUrl)
            <div class="mb-4">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ urlencode($verifyUrl) }}" alt="QR" class="inline-block" style="width: 90px; height: 90px;">
            </div>
            @endif
            <p class="font-serif italic text-gray-600 text-[15px]">
                {{ $txtThanks }}
            </p>
        </footer>
    </div>
    
    @else
        <!-- ========================================== -->
        <!-- DOMPDF HTML STRUCTURE (Mimics Exact PDF)   -->
        <!-- ========================================== -->
        <div class="container">
            <!-- Header Section -->
            <table width="100%" style="margin-bottom: 5px;">
                <tr>
                    <!-- Left Column: Logo & Institution Details -->
                    <td width="60%" valign="top">
                        <table width="100%">
                            <tr>
                                @if($invoice->institution && $invoice->institution->logo)
                                <td width="100" valign="top">
                                    <img src="{{ public_path('storage/' . $invoice->institution->logo) }}" class="logo">
                                </td>
                                @endif
                                <td valign="top" style="padding-left: {{ ($invoice->institution && $invoice->institution->logo) ? '15px' : '0' }};">
                                    <div class="school-name">{{ $invoice->institution->name ?? config('app.name') }}</div>
                                    <div class="contact-info">
                                        <div style="margin-bottom: 3px;">
                                            <img src="data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 384 512'%3E%3Cpath fill='%23083366' d='M215.7 499.2C267 435 384 279.4 384 192C384 86 298 0 192 0S0 86 0 192c0 87.4 117 243 168.3 307.2c12.3 15.3 35.1 15.3 47.4 0zM192 128a64 64 0 1 1 0 128 64 64 0 1 1 0-128z'/%3E%3C/svg%3E" width="10" height="13" style="vertical-align: middle; margin-right: 4px;">
                                            {{ $invoice->institution->address ?? 'N/A' }}, {{ $cityCountry }}
                                        </div>
                                        @if($invoice->institution->phone)
                                        <div style="margin-bottom: 3px;">
                                            <img src="data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Cpath fill='%23083366' d='M164.9 24.6c-7.7-18.6-28-28.5-47.4-23.2l-88 24C12.1 30.2 0 46 0 64C0 311.4 200.6 512 448 512c18 0 33.8-12.1 38.6-29.5l24-88c5.3-19.4-4.6-39.7-23.2-47.4l-96-40c-16.3-6.8-35.2-2.1-46.3 11.6L304.7 368C234.3 334.7 177.3 277.7 144 207.3L193.3 167c13.7-11.2 18.4-30 11.6-46.3l-40-96z'/%3E%3C/svg%3E" width="10" height="10" style="vertical-align: middle; margin-right: 4px;">
                                            {{ $invoice->institution->phone }}
                                        </div>
                                        @endif
                                        @if($invoice->institution->email)
                                        <div style="margin-bottom: 3px;">
                                            <img src="data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Cpath fill='%23083366' d='M48 64C21.5 64 0 85.5 0 112c0 15.1 7.1 29.3 19.2 38.4L236.8 313.6c11.4 8.5 27 8.5 38.4 0L492.8 150.4c12.1-9.1 19.2-23.3 19.2-38.4c0-26.5-21.5-48-48-48H48zM0 176V384c0 35.3 28.7 64 64 64H448c35.3 0 64-28.7 64-64V176L294.4 339.2c-22.8 17.1-54 17.1-76.8 0L0 176z'/%3E%3C/svg%3E" width="10" height="10" style="vertical-align: middle; margin-right: 4px;">
                                            {{ $invoice->institution->email }}
                                        </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                    
                    <!-- Right Column: Barcode & Doc Title -->
                    <td width="40%" valign="top" class="text-right">
                        <div style="margin-bottom: 12px;">
                            @if(!empty($barcodeBase64))
                                <img src="{{ $barcodeBase64 }}" alt="Barcode" style="height: 40px; max-width: 100%;">
                            @else
                                <div style="font-weight: bold; font-family: monospace; font-size: 14px; letter-spacing: 2px;">*{{ $invoice->invoice_number }}*</div>
                            @endif
                        </div>
                        <div class="doc-title">{{ $docTitle }}</div>
                    </td>
                </tr>
            </table>

            <div class="hr-line"></div>

            <!-- Two Columns Info Section -->
            <table width="100%" style="margin-bottom: 20px;">
                <tr>
                    <!-- Left: Student Info -->
                    <td class="col-left">
                        <div class="section-header">{{ $txtReceivedFor }}</div>
                        <div class="section-divider"></div>
                        
                        <table class="student-info-table">
                            <tr>
                                <td class="label">{{ $txtStudentName }}</td><td class="colon">:</td><td class="value">{{ $studentDisplayName }}</td>
                            </tr>
                            <tr>
                                <td class="label">{{ $txtStudentId }}</td><td class="colon">:</td><td class="value">{{ $invoice->student->admission_number }}</td>
                            </tr>
                            <tr>
                                <td class="label">{{ $txtClass }}</td><td class="colon">:</td><td class="value">{{ class_section_label($enrollment?->classSection) }}</td>
                            </tr>
                            <tr>
                                <td class="label">{{ $txtYear }}</td><td class="colon">:</td><td class="value">{{ $invoice->academicSession->name ?? 'N/A' }}</td>
                            </tr>
                        </table>
                    </td>
                    <td width="5%"></td>
                    
                    <!-- Right: Receipt Details -->
                    <td class="col-right">
                        <table class="receipt-info-table">
                            <tr>
                                <td class="label">{{ $txtReceiptNo }}</td><td class="colon">:</td>
                                <td><span style="border: 1px solid #9ca3af; border-radius: 12px; padding: 3px 12px; display: inline-block; font-weight: bold;">{{ $displayReceiptNo }}</span><div style="font-size: 10px; color: #6b7280; margin-top: 4px;">{{ $txtInvoiceRef }}: {{ $invoice->invoice_number }}</div></td>
                            </tr>
                            <tr>
                                <td class="label" style="padding-top: 10px;">{{ $txtPaymentDate }}</td><td class="colon" style="padding-top: 10px;">:</td>
                                <td class="value" style="padding-top: 10px;">{{ $lastPaymentDate }}</td>
                            </tr>
                            <tr>
                                <td class="label" style="padding-top: 10px;">{{ $txtStatus }}</td><td class="colon" style="padding-top: 10px;">:</td>
                                <td style="padding-top: 10px;"><span class="badge-status">{{ $label }}</span></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- Items -->
            <table class="items-table" cellspacing="0" cellpadding="0">
                <thead>
                    <tr>
                        <th width="10%" class="text-center">{{ $txtNo }}</th>
                        <th width="65%" class="text-center">{{ $txtDesignation }}</th>
                        <th width="25%" class="text-center">{{ $txtAmount }} ({{ $currency }})</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ localize_invoice_description($item->description) }}</td>
                        <td class="text-center amount">{{ number_format($item->amount, 2) }}</td>
                    </tr>
                    @endforeach
                    
                    <!-- Filler rows -->
                    @for($i = $invoice->items->count(); $i < 3; $i++)
                    <tr>
                        <td style="border-top: 1px dashed #d1d5db; border-bottom: 1px dashed #d1d5db; height: 30px;"></td>
                        <td style="border-top: 1px dashed #d1d5db; border-bottom: 1px dashed #d1d5db;"></td>
                        <td style="border-top: 1px dashed #d1d5db; border-bottom: 1px dashed #d1d5db;"></td>
                    </tr>
                    @endfor
                </tbody>
            </table>

            <!-- Summary -->
            <table width="100%" cellspacing="0" cellpadding="0" style="margin-top: 15px; clear: both;">
                <tr>
                    <td width="55%" valign="top" style="padding-right: 24px; font-size: 13px; font-style: italic; color: #374151; line-height: 1.5;">
                        <span style="font-style: normal; font-weight: bold; color: #111827;">{{ $txtAmountInWords }}:</span><br>
                        {{ ucfirst(amount_in_words($amountForWords)) }}
                    </td>
                    <td width="45%" valign="top">
                        <table class="summary-table" cellspacing="0" cellpadding="0" style="float: none; width: 100%; margin-top: 0;">
                <tr>
                    <td class="label">{{ $txtSubtotal }} :</td>
                    <td class="value">{{ $currency }} {{ number_format($invoice->total_amount, 2) }}</td>
                </tr>
                <tr class="border-row">
                    <td class="label" style="padding-top: 8px;">{{ $txtPaid }} :</td>
                    <td class="value" style="padding-top: 8px;">{{ $currency }} {{ number_format($invoice->paid_amount, 2) }}</td>
                </tr>
                <tr class="due-box">
                    <td class="label">{{ $txtDue }} :</td>
                    <td class="value">{{ $currency }} {{ number_format($dueAmount, 2) }}</td>
                </tr>
                <tr><td colspan="2" style="height: 10px;"></td></tr>
                <tr class="border-row">
                    <td class="label font-bold" style="padding-top: 10px;">{{ $txtPrintDate }} :</td>
                    <td class="value font-bold" style="padding-top: 10px;">{{ now()->format('d/m/Y H:i') }}</td>
                </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <div style="clear: both;"></div>

            <!-- Footer -->
            <div class="footer">
                @if($verifyUrl)
                <div style="text-align: center; margin-bottom: 10px;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode($verifyUrl) }}" alt="QR" style="width: 80px; height: 80px;">
                </div>
                @endif
                <div class="footer-thanks">{{ $txtThanks }}</div>
            </div>
        </div>
    @endif
</body>
</html>