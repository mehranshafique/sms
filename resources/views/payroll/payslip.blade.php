<!DOCTYPE html>
<html>
<head>
    <title>{{ __('payroll.payslip') }} - {{ $payroll->staff->user->name ?? 'Staff' }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; font-size: 13px; line-height: 1.5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; border: 1px solid #ddd; padding: 30px; }
        
        .header { display: table; width: 100%; border-bottom: 2px solid #0056b3; padding-bottom: 20px; margin-bottom: 20px; }
        .logo-section { display: table-cell; vertical-align: middle; width: 60%; }
        .title-section { display: table-cell; vertical-align: middle; width: 40%; text-align: right; }
        
        .company-name { font-size: 24px; font-weight: bold; color: #0056b3; text-transform: uppercase; margin: 0; }
        .company-address { font-size: 11px; color: #666; margin-top: 5px; }
        .payslip-label { font-size: 20px; font-weight: bold; color: #333; text-transform: uppercase; }
        .payslip-month { font-size: 14px; color: #666; margin-top: 5px; }

        .info-grid { width: 100%; margin-bottom: 30px; background: #f9f9f9; padding: 15px; border-radius: 4px; }
        .info-grid td { padding: 5px 0; vertical-align: top; }
        .label { font-weight: bold; color: #555; width: 120px; }
        .val { color: #000; }

        .financial-section { display: table; width: 100%; margin-bottom: 20px; }
        .col-left { display: table-cell; width: 48%; padding-right: 2%; vertical-align: top; }
        .col-right { display: table-cell; width: 48%; padding-left: 2%; vertical-align: top; border-left: 1px solid #eee; }

        .table-fin { width: 100%; border-collapse: collapse; }
        .table-fin th { text-align: left; padding: 8px; border-bottom: 2px solid #ddd; font-size: 11px; text-transform: uppercase; color: #555; }
        .table-fin td { padding: 8px; border-bottom: 1px solid #eee; }
        .amount { text-align: right; font-weight: bold; }
        .total-row td { border-top: 2px solid #ddd; border-bottom: none; font-weight: bold; padding-top: 10px; font-size: 14px; }

        .net-pay-section { margin-top: 20px; text-align: right; background: #e3f2fd; padding: 15px; border-radius: 4px; border: 1px solid #bbdefb; }
        .net-label { font-size: 14px; font-weight: bold; color: #0d47a1; margin-right: 15px; }
        .net-value { font-size: 22px; font-weight: bold; color: #0d47a1; }

        .footer { margin-top: 50px; border-top: 1px dashed #ccc; padding-top: 10px; font-size: 10px; color: #888; text-align: center; }
        .signatures { margin-top: 60px; width: 100%; }
        .sig-box { width: 40%; border-top: 1px solid #333; text-align: center; font-size: 11px; padding-top: 5px; }
    </style>
</head>
<body>

<div class="container">
    {{-- Header --}}
    <div class="header">
        <div class="logo-section">
            <h1 class="company-name">{{ $payroll->staff->institution->name }}</h1>
            <div class="company-address">{{ $payroll->staff->institution->address ?? 'Institution Address' }}</div>
            <div class="company-address">{{ $payroll->staff->institution->email ?? '' }} | {{ $payroll->staff->institution->phone ?? '' }}</div>
        </div>
        <div class="title-section">
            <div class="payslip-label">{{ __('payroll.payslip') }}</div>
            <div class="payslip-month">{{ $payroll->month_year->format('F Y') }}</div>
            <div style="margin-top:5px;">
                <span style="background: {{ $payroll->status == 'paid' ? '#d4edda' : '#fff3cd' }}; color: {{ $payroll->status == 'paid' ? '#155724' : '#856404' }}; padding: 3px 8px; border-radius: 3px; font-size: 10px; font-weight: bold; text-transform: uppercase;">
                    {{ ucfirst($payroll->status) }}
                </span>
            </div>
        </div>
    </div>

    @php
        $structure = $payroll->staff->salaryStructure;
        $isHourly = ($structure && $structure->payment_basis === 'hourly');
        $basisLabel = $isHourly ? __('payroll.hourly') : __('payroll.monthly');
        $workUnitLabel = $isHourly ? __('payroll.hourly_short') : __('payroll.total_days');
        $rateLabel = $isHourly ? __('payroll.hourly_rate') : __('payroll.base_salary');
        $rateValue = $structure ? $structure->base_salary : 0;
        
        // --- FIX: Safely Decode Allowances & Deductions ---
        $allowances = $structure->allowances ?? [];
        if(is_string($allowances)) $allowances = json_decode($allowances, true);
        if(!is_array($allowances)) $allowances = [];

        $deductions = $structure->deductions ?? [];
        if(is_string($deductions)) $deductions = json_decode($deductions, true);
        if(!is_array($deductions)) $deductions = [];
    @endphp

    {{-- Employee Info --}}
    <table class="info-grid">
        <tr>
            <td class="label">{{ __('payroll.staff_id') }}:</td>
            {{-- FIX: Use employee_id from staff table --}}
            <td class="val">{{ $payroll->staff->employee_id ?? $payroll->staff->id }}</td>
            <td class="label">{{ __('payroll.designation') }}:</td>
            <td class="val">{{ $payroll->staff->designation ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('payroll.name') }}:</td>
            {{-- FIX: Use user name via relation --}}
            <td class="val" style="font-weight: bold;">{{ $payroll->staff->user->name ?? 'N/A' }}</td>
            <td class="label">{{ __('payroll.department') }}:</td>
            <td class="val">{{ $payroll->staff->department ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('payroll.join_date') }}:</td>
            <td class="val">{{ $payroll->staff->joining_date ? \Carbon\Carbon::parse($payroll->staff->joining_date)->format('d M, Y') : '-' }}</td>
            <td class="label">{{ __('payroll.payment_basis') }}:</td>
            <td class="val">{{ $basisLabel }}</td>
        </tr>
        <tr>
            <td class="label">{{ $workUnitLabel }}:</td>
            <td class="val">{{ $payroll->total_days }}</td>
            <td class="label">Attendance:</td>
            <td class="val">
                @if(!$isHourly)
                    P: {{ $payroll->present_days }} / A: {{ $payroll->absent_days }}
                @else
                    {{ $payroll->present_days }} days attended
                @endif
            </td>
        </tr>
    </table>

    {{-- Financial Details --}}
    <div class="financial-section">
        {{-- Earnings --}}
        <div class="col-left">
            <table class="table-fin">
                <thead>
                    <tr>
                        <th>{{ __('payroll.earnings') }}</th>
                        <th class="amount">{{ __('payroll.amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            {{ $rateLabel }} <br>
                            <small style="color:#888;">
                                @if($isHourly)
                                    ({{ $payroll->total_days }} hrs @ {{ number_format($rateValue, 2) }}/hr)
                                @else
                                    ({{ __('payroll.monthly') }})
                                @endif
                            </small>
                        </td>
                        <td class="amount">{{ number_format($payroll->basic_pay, 2) }}</td>
                    </tr>
                    
                    @foreach($allowances as $key => $val)
                    @php
                        // Handle array format [{'name'=>'Transport', 'amount'=>50}] vs associative ['Transport'=>50]
                        $label = is_array($val) ? ($val['name'] ?? 'Allowance') : $key;
                        $amount = is_array($val) ? ($val['amount'] ?? 0) : $val;
                    @endphp
                    <tr>
                        <td>{{ ucfirst(str_replace('_', ' ', $label)) }}</td>
                        <td class="amount">{{ number_format((float)$amount, 2) }}</td>
                    </tr>
                    @endforeach

                    <tr class="total-row">
                        <td>{{ __('payroll.total_earnings') }}</td>
                        <td class="amount">{{ number_format($payroll->basic_pay + $payroll->total_allowance, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Deductions --}}
        <div class="col-right">
            <table class="table-fin">
                <thead>
                    <tr>
                        <th>{{ __('payroll.deductions') }}</th>
                        <th class="amount">{{ __('payroll.amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @if($payroll->total_deduction > 0)
                        
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

                        @php
                            // Calculate total from structure to see if there is extra LOP
                            $structDedTotal = collect($deductions)->sum(function($item) {
                                return is_array($item) ? ($item['amount'] ?? 0) : $item;
                            });
                            $lop = $payroll->total_deduction - $structDedTotal;
                        @endphp
                        
                        @if($lop > 0.01)
                        <tr>
                            <td>{{ __('payroll.lop') }}</td>
                            <td class="amount">{{ number_format($lop, 2) }}</td>
                        </tr>
                        @endif

                        <tr class="total-row">
                            <td style="color: #dc3545;">{{ __('payroll.total_deduction') }}</td>
                            <td class="amount" style="color: #dc3545;">{{ number_format($payroll->total_deduction, 2) }}</td>
                        </tr>
                    @else
                        <tr>
                            <td colspan="2" style="text-align: center; color: #999; padding: 20px;">-</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- Net Pay --}}
    <div class="net-pay-section">
        <span class="net-label">{{ strtoupper(__('payroll.net_pay')) }}</span>
        <span class="net-value">{{ config('app.currency_symbol', '$') }} {{ number_format($payroll->net_salary, 2) }}</span>
        
        <div style="font-size: 11px; color: #666; margin-top: 5px;">
            @if(class_exists('NumberFormatter'))
                (Amount in words: {{ \NumberFormatter::create('en', \NumberFormatter::SPELLOUT)->format($payroll->net_salary) }} only)
            @endif
        </div>
    </div>

    {{-- Signatures --}}
    <table class="signatures">
        <tr>
            <td>
                <div class="sig-box" style="float:left;">
                    Employee Signature
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
        {{ __('results.computer_generated') }} | Generated on {{ now()->format('d M, Y h:i A') }} | {{ config('app.name') }}
    </div>
</div>

</body>
</html>