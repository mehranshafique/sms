<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('payroll.payslip') }}</title>
    <style>
        @page { margin: 0px; }
        body { 
            font-family: 'Helvetica', sans-serif; 
            margin: 10px 5px; 
            color: #000;
            font-size: {{ $format === 'pos58' ? '9px' : '11px' }}; 
            line-height: 1.2;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .line { border-bottom: 1px dashed #000; margin: 5px 0; }
        
        .header { margin-bottom: 10px; }
        .logo-text { font-size: {{ $format === 'pos58' ? '12px' : '14px' }}; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; }
        
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 1px 0; }
        
        .info-label { width: 40%; }
        .amount-col { text-align: right; }
        
        .total-box { margin-top: 5px; padding: 5px 0; border-top: 1px solid #000; border-bottom: 1px solid #000; }
        .net-amount { font-size: {{ $format === 'pos58' ? '14px' : '16px' }}; font-weight: bold; }
        
        .footer { margin-top: 15px; text-align: center; font-size: 8px; }
    </style>
</head>
<body>
    <div class="header text-center">
        <div class="logo-text">{{ $payroll->staff->institution->name ?? 'Institution' }}</div>
        <div>{{ __('payroll.payslip') }}</div>
        <div class="bold">{{ $payroll->month_year->format('F Y') }}</div>
    </div>

    <div class="line"></div>

    {{-- Staff Info --}}
    <table>
        <tr>
            <td class="info-label">{{ __('payroll.staff_id') }}:</td>
            <td class="text-right">{{ $payroll->staff->employee_id ?? $payroll->staff->id }}</td>
        </tr>
        <tr>
            <td class="info-label">{{ __('payroll.name') }}:</td>
            <td class="text-right bold">{{ $payroll->staff->user->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="info-label">{{ __('payroll.designation') }}:</td>
            <td class="text-right">{{ $payroll->staff->designation ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">{{ __('payroll.total_days') }}:</td>
            <td class="text-right">{{ $payroll->total_days }}</td>
        </tr>
    </table>

    <div class="line"></div>

    {{-- Earnings --}}
    <div class="bold" style="margin-bottom: 2px;">{{ __('payroll.earnings') }}</div>
    <table>
        <tr>
            <td>{{ __('payroll.base_salary') }}</td>
            <td class="amount-col">{{ number_format($payroll->basic_pay, 2) }}</td>
        </tr>
        @if($payroll->total_allowance > 0)
            <tr>
                <td>{{ __('payroll.allowances') }}</td>
                <td class="amount-col">{{ number_format($payroll->total_allowance, 2) }}</td>
            </tr>
        @endif
        {{-- Gross Salary Row --}}
        <tr class="bold">
            <td>Gross Salary</td>
            <td class="amount-col">{{ number_format($payroll->basic_pay + $payroll->total_allowance, 2) }}</td>
        </tr>
    </table>

    <div class="line"></div>

    {{-- Deductions --}}
    @if($payroll->total_deduction > 0)
        <div class="bold" style="margin-bottom: 2px;">{{ __('payroll.deductions') }}</div>
        <table>
            <tr>
                <td>{{ __('payroll.total_deduction') }}</td>
                <td class="amount-col">- {{ number_format($payroll->total_deduction, 2) }}</td>
            </tr>
        </table>
        <div class="line"></div>
    @endif

    {{-- Net Pay --}}
    <div class="text-center total-box">
        <div>{{ strtoupper(__('payroll.net_salary')) }}</div>
        <div class="net-amount">{{ config('app.currency_symbol', '$') }} {{ number_format($payroll->net_salary, 2) }}</div>
    </div>

    <div class="footer">
        <p>
            {{ __('payroll.status') }}: {{ ucfirst($payroll->status) }}<br>
            {{ __('results.computer_generated') }}<br>
            {{ now()->format('d/m/Y H:i') }}
        </p>
    </div>
</body>
</html>