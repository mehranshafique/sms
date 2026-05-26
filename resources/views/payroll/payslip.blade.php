<!DOCTYPE html>
<html>
<head>
    <title>{{ __('payroll.payslip') }} - {{ $payroll->staff->user->name ?? 'Staff' }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page { margin: 40px; size: A4 portrait; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #111827; font-size: 13px; line-height: 1.4; margin: 0; padding: 0; }
        table { width: 100%; border-collapse: collapse; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        
        /* Header */
        .logo { max-height: 60px; max-width: 150px; }
        .school-name { font-size: 22px; font-weight: 900; color: #083366; text-transform: uppercase; line-height: 1.1; margin-bottom: 8px; }
        .contact-info { font-size: 12px; color: #083366; line-height: 1.5; font-weight: 500; }
        
        /* Divider */
        .hr-line { border-top: 2px solid #083366; margin: 20px 0; }
        
        /* Section Headers */
        .section-header { background-color: #083366; color: #ffffff; padding: 4px 12px; border-radius: 3px; font-size: 12px; font-weight: bold; display: inline-block; text-transform: uppercase; margin-bottom: 5px; }
        .section-divider { border-top: 1px solid #d1d5db; margin-bottom: 15px; }
        
        /* Document Title Badge */
        .doc-title { font-size: 18px; font-weight: 900; color: #ffffff; background-color: #083366; text-transform: uppercase; padding: 8px 24px; border-radius: 4px; display: inline-block; letter-spacing: 1px; margin-bottom: 8px; }
        .badge-status { color: #ffffff; padding: 3px 10px; border-radius: 3px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .status-paid { background-color: #059669; } /* Green */
        .status-generated { background-color: #d97706; } /* Orange */

        /* Two Column Layout for Info */
        .col-left { width: 48%; vertical-align: top; }
        .col-right { width: 48%; vertical-align: top; }
        
        .info-table td { padding: 4px 0; font-size: 13px; }
        .info-table td.label { width: 130px; color: #4b5563; }
        .info-table td.colon { width: 15px; color: #111827; font-weight: bold; }
        .info-table td.value { font-weight: bold; color: #111827; }

        /* Items Table */
        .items-table { margin-top: 5px; border: 1px solid #d1d5db; width: 100%; }
        .items-table th { background-color: #083366; color: #ffffff; padding: 8px 12px; text-align: left; font-size: 12px; text-transform: uppercase; font-weight: bold; border: 1px solid #d1d5db; }
        .items-table th.text-right { text-align: right; }
        .items-table td { padding: 10px 12px; font-size: 13px; color: #111827; border: 1px solid #d1d5db; }
        .items-table td.amount { font-weight: bold; text-align: right; }
        .items-table .total-row td { background-color: #f3f4f6; font-weight: bold; border-top: 2px solid #d1d5db; }
        .items-table .total-row td.text-danger { color: #dc2626; }

        /* Net Pay Box */
        .net-pay-section { margin-top: 25px; text-align: right; background-color: #e6f0fa; padding: 15px 20px; border-top: 2px solid #083366; border-bottom: 2px solid #083366; }
        .net-label { font-size: 14px; font-weight: bold; color: #083366; margin-right: 15px; text-transform: uppercase; }
        .net-value { font-size: 22px; font-weight: bold; color: #083366; }
        .net-words { font-size: 11px; color: #4b5563; margin-top: 5px; }

        /* Signatures & Footer */
        .signatures { margin-top: 80px; width: 100%; }
        .sig-box { width: 35%; border-top: 1px solid #111827; text-align: center; font-size: 12px; padding-top: 5px; font-weight: bold; color: #4b5563; }
        .footer { margin-top: 50px; border-top: 1px dashed #d1d5db; padding-top: 15px; font-size: 11px; color: #6b7280; text-align: center; }
    </style>
</head>
<body>

@php
    // Safe Base64 Logo Loading for DOMPDF bypasses local symlink restrictions
    $logoBase64 = '';
    if (!empty($payroll->staff->institution->logo)) {
        $logo = $payroll->staff->institution->logo;
        $paths = [
            public_path('storage/' . $logo),
            storage_path('app/public/' . $logo),
            public_path($logo)
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $ext = pathinfo($path, PATHINFO_EXTENSION);
                if ($ext === 'jpg') $ext = 'jpeg';
                $data = @file_get_contents($path);
                if ($data !== false) {
                    $logoBase64 = 'data:image/' . $ext . ';base64,' . base64_encode($data);
                    break;
                }
            }
        }
    }
@endphp

<div class="container">
    {{-- Header --}}
    <table width="100%" style="margin-bottom: 5px;">
        <tr>
            <!-- Left Column: Logo & Institution Details -->
            <td width="60%" valign="top">
                <table width="100%">
                    <tr>
                        <td width="75" valign="top">
                            @if(!empty($logoBase64))
                                <img src="{{ $logoBase64 }}" class="logo">
                            @elseif(!empty($payroll->staff->institution->logo))
                                <img src="{{ asset('storage/' . $payroll->staff->institution->logo) }}" class="logo">
                            @else
                                <div style="width: 60px; height: 60px; background: #e5e7eb; text-align: center; line-height: 60px; font-size: 24px; font-weight: bold; color: #9ca3af; border-radius: 4px;">
                                    {{ substr($payroll->staff->institution->name ?? 'S', 0, 2) }}
                                </div>
                            @endif
                        </td>
                        <td valign="top" style="padding-left: 10px;">
                            <div class="school-name">{{ $payroll->staff->institution->name ?? config('app.name') }}</div>
                            <div class="contact-info">
                                <div style="margin-bottom: 3px;">
                                    <img src="data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 384 512'%3E%3Cpath fill='%23083366' d='M215.7 499.2C267 435 384 279.4 384 192C384 86 298 0 192 0S0 86 0 192c0 87.4 117 243 168.3 307.2c12.3 15.3 35.1 15.3 47.4 0zM192 128a64 64 0 1 1 0 128 64 64 0 1 1 0-128z'/%3E%3C/svg%3E" width="10" height="13" style="vertical-align: middle; margin-right: 4px;">
                                    {{ $payroll->staff->institution->address ?? 'N/A' }}
                                </div>
                                @if($payroll->staff->institution->phone)
                                <div style="margin-bottom: 3px;">
                                    <img src="data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Cpath fill='%23083366' d='M164.9 24.6c-7.7-18.6-28-28.5-47.4-23.2l-88 24C12.1 30.2 0 46 0 64C0 311.4 200.6 512 448 512c18 0 33.8-12.1 38.6-29.5l24-88c5.3-19.4-4.6-39.7-23.2-47.4l-96-40c-16.3-6.8-35.2-2.1-46.3 11.6L304.7 368C234.3 334.7 177.3 277.7 144 207.3L193.3 167c13.7-11.2 18.4-30 11.6-46.3l-40-96z'/%3E%3C/svg%3E" width="10" height="10" style="vertical-align: middle; margin-right: 4px;">
                                    {{ $payroll->staff->institution->phone }}
                                </div>
                                @endif
                                @if($payroll->staff->institution->email)
                                <div style="margin-bottom: 3px;">
                                    <img src="data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Cpath fill='%23083366' d='M48 64C21.5 64 0 85.5 0 112c0 15.1 7.1 29.3 19.2 38.4L236.8 313.6c11.4 8.5 27 8.5 38.4 0L492.8 150.4c12.1-9.1 19.2-23.3 19.2-38.4c0-26.5-21.5-48-48-48H48zM0 176V384c0 35.3 28.7 64 64 64H448c35.3 0 64-28.7 64-64V176L294.4 339.2c-22.8 17.1-54 17.1-76.8 0L0 176z'/%3E%3C/svg%3E" width="10" height="10" style="vertical-align: middle; margin-right: 4px;">
                                    {{ $payroll->staff->institution->email }}
                                </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            
            <!-- Right Column: Doc Title -->
            <td width="40%" valign="top" class="text-right">
                <div class="doc-title">{{ __('payroll.payslip') }}</div>
                <div style="font-weight: bold; font-size: 14px; margin-bottom: 5px; color: #111827;">
                    {{ $payroll->month_year->format('F Y') }}
                </div>
                <div>
                    <span class="badge-status {{ $payroll->status == 'paid' ? 'status-paid' : 'status-generated' }}">
                        {{ __('payroll.'.$payroll->status) ?? ucfirst($payroll->status) }}
                    </span>
                </div>
            </td>
        </tr>
    </table>

    <div class="hr-line"></div>

    @php
        $structure = $payroll->staff->salaryStructure;
        $isHourly = ($structure && $structure->payment_basis === 'hourly');
        $basisLabel = $isHourly ? __('payroll.hourly') : __('payroll.monthly');
        $workUnitLabel = $isHourly ? __('payroll.hourly_short') : __('payroll.total_days');
        $rateLabel = $isHourly ? __('payroll.hourly_rate') : __('payroll.base_salary');
        $rateValue = $structure ? $structure->base_salary : 0;
        
        $allowances = $structure->allowances ?? [];
        if(is_string($allowances)) $allowances = json_decode($allowances, true);
        if(!is_array($allowances)) $allowances = [];

        $deductions = $structure->deductions ?? [];
        if(is_string($deductions)) $deductions = json_decode($deductions, true);
        if(!is_array($deductions)) $deductions = [];

        $structDedTotal = collect($deductions)->sum(function($item) {
            return is_array($item) ? (float)($item['amount'] ?? 0) : (is_numeric($item) ? (float)$item : 0);
        });
        $lop = max(0, $payroll->total_deduction - $structDedTotal);
    @endphp

    {{-- Employee Info --}}
    <div class="section-header">{{ __('payroll.staff') }}</div>
    <div class="section-divider"></div>
    
    <table width="100%">
        <tr>
            <td class="col-left">
                <table class="info-table">
                    <tr><td class="label">{{ __('payroll.name') }}</td><td class="colon">:</td><td class="value">{{ $payroll->staff->user->name ?? 'N/A' }}</td></tr>
                    <tr><td class="label">{{ __('payroll.staff_id') }}</td><td class="colon">:</td><td class="value">{{ $payroll->staff->employee_id ?? $payroll->staff->id }}</td></tr>
                    <tr><td class="label">{{ __('payroll.designation') }}</td><td class="colon">:</td><td class="value">{{ $payroll->staff->designation ?? '-' }}</td></tr>
                    <tr><td class="label">{{ __('payroll.department') }}</td><td class="colon">:</td><td class="value">{{ $payroll->staff->department ?? '-' }}</td></tr>
                </table>
            </td>
            <td width="4%"></td>
            <td class="col-right">
                <table class="info-table">
                    <tr><td class="label">{{ __('payroll.join_date') }}</td><td class="colon">:</td><td class="value">{{ $payroll->staff->joining_date ? \Carbon\Carbon::parse($payroll->staff->joining_date)->format('d M, Y') : '-' }}</td></tr>
                    <tr><td class="label">{{ __('payroll.payment_basis') }}</td><td class="colon">:</td><td class="value">{{ $basisLabel }}</td></tr>
                    <tr><td class="label">{{ $workUnitLabel }}</td><td class="colon">:</td><td class="value">{{ $payroll->total_days }}</td></tr>
                    <tr><td class="label">{{ __('payroll.attendance') }}</td><td class="colon">:</td><td class="value">
                        @if(!$isHourly)
                            P: {{ $payroll->present_days }} / A: {{ $payroll->absent_days }}
                        @else
                            {{ $payroll->present_days }} {{ __('payroll.days_attended') }}
                        @endif
                    </td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Financial Details --}}
    <table width="100%" style="margin-top: 30px;">
        <tr>
            <!-- Earnings -->
            <td class="col-left">
                <table class="items-table" cellspacing="0" cellpadding="0">
                    <thead>
                        <tr>
                            <th width="70%">{{ __('payroll.earnings') }}</th>
                            <th width="30%" class="text-right">{{ __('payroll.amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                {{ $rateLabel }} <br>
                                <span style="font-size: 11px; color:#6b7280; font-weight: normal;">
                                    @if($isHourly)
                                        ({{ $payroll->total_days }} hrs @ {{ number_format($rateValue, 2) }}/hr)
                                    @else
                                        ({{ __('payroll.monthly') }})
                                    @endif
                                </span>
                            </td>
                            <td class="amount">{{ number_format($payroll->basic_pay, 2) }}</td>
                        </tr>
                        
                        @foreach($allowances as $key => $val)
                        @php
                            $label = is_array($val) ? ($val['name'] ?? 'Allowance') : $key;
                            $amount = is_array($val) ? ($val['amount'] ?? 0) : $val;
                        @endphp
                        <tr>
                            <td>{{ ucfirst(str_replace('_', ' ', $label)) }}</td>
                            <td class="amount">{{ number_format((float)$amount, 2) }}</td>
                        </tr>
                        @endforeach

                        <tr class="total-row">
                            <td>{{ __('payroll.gross_salary') }}</td>
                            <td class="amount">{{ number_format($payroll->basic_pay + $payroll->total_allowance, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>

            <td width="4%"></td>

            <!-- Deductions -->
            <td class="col-right">
                <table class="items-table" cellspacing="0" cellpadding="0">
                    <thead>
                        <tr>
                            <th width="70%">{{ __('payroll.deductions') }}</th>
                            <th width="30%" class="text-right">{{ __('payroll.amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($payroll->total_deduction > 0 || !empty($deductions))
                            
                            @foreach($deductions as $key => $val)
                            @php
                                $label = is_array($val) ? ($val['name'] ?? 'Deduction') : $key;
                                $amount = is_array($val) ? ($val['amount'] ?? 0) : $val;
                            @endphp
                            <tr>
                                <td>{{ ucfirst(str_replace('_', ' ', $label)) }}</td>
                                <td class="amount">{{ number_format((float)$amount, 2) }}</td>
                            </tr>
                            @endforeach

                            @if($lop > 0.01)
                            <tr>
                                <td>{{ __('payroll.lop') }}</td>
                                <td class="amount">{{ number_format($lop, 2) }}</td>
                            </tr>
                            @endif

                            <tr class="total-row">
                                <td class="text-danger">{{ __('payroll.total_deduction') }}</td>
                                <td class="amount text-danger">-{{ number_format($payroll->total_deduction, 2) }}</td>
                            </tr>
                        @else
                            <tr>
                                <td colspan="2" style="text-align: center; color: #9ca3af; padding: 25px;">-</td>
                            </tr>
                            <tr class="total-row">
                                <td>{{ __('payroll.total_deduction') }}</td>
                                <td class="amount">-0.00</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    {{-- Net Pay --}}
    <div class="net-pay-section">
        <span class="net-label">{{ __('payroll.net_pay') }}</span>
        <span class="net-value">{{ config('app.currency_symbol', '$') }} {{ number_format($payroll->net_salary, 2) }}</span>
        
        <div class="net-words">
            @if(class_exists('NumberFormatter'))
                ({{ __('payroll.amount_in_words') }}: {{ \NumberFormatter::create(app()->getLocale(), \NumberFormatter::SPELLOUT)->format($payroll->net_salary) }} {{ __('payroll.only') }})
            @endif
        </div>
    </div>

    {{-- Signatures --}}
    <table class="signatures">
        <tr>
            <td>
                <div class="sig-box" style="float:left;">
                    {{ __('payroll.employee_signature') }}
                </div>
            </td>
            <td>
                <div class="sig-box" style="float:right;">
                    {{ __('payroll.authorized_sign') }}
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        {{ __('results.computer_generated') ?? 'Computer Generated Document' }} | {{ __('payroll.generated_on') }} {{ now()->format('d M, Y h:i A') }} | {{ config('app.name') }}
    </div>
</div>

</body>
</html>