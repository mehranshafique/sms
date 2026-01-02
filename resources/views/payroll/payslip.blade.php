<!DOCTYPE html>
<html>
<head>
    <title>{{ __('payroll.payslip') }} - {{ $payroll->staff->full_name }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: 'Helvetica', sans-serif; padding: 20px; font-size: 12px; }
        .container { width: 100%; max-width: 800px; margin: 0 auto; border: 1px solid #ccc; padding: 20px; }
        
        /* Header */
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .inst-name { font-size: 24px; font-weight: bold; text-transform: uppercase; }
        .inst-address { font-size: 14px; color: #555; }
        .payslip-title { font-size: 18px; font-weight: bold; text-transform: uppercase; margin-top: 10px; text-decoration: underline; }

        /* Info Block */
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 5px; }
        .label { font-weight: bold; color: #555; width: 150px; }

        /* Salary Table */
        .salary-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .salary-table th, .salary-table td { border: 1px solid #ccc; padding: 8px; }
        .salary-table th { background-color: #f5f5f5; text-align: left; }
        .amount-col { text-align: right; width: 150px; }

        /* Footer */
        .footer { margin-top: 50px; border-top: 1px dashed #ccc; padding-top: 10px; font-size: 10px; text-align: center; }
        .signatures { margin-top: 40px; display: flex; justify-content: space-between; }
        .sign-box { float: right; width: 200px; text-align: center; border-top: 1px solid #333; padding-top: 5px; }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <div class="inst-name">{{ $payroll->staff->institution->name }}</div>
            <div class="inst-address">{{ $payroll->staff->institution->address ?? '' }}</div>
            <div class="payslip-title">{{ __('payroll.payslip') }} - {{ $payroll->month_year->format('F Y') }}</div>
        </div>

        <table class="info-table">
            <tr>
                <td class="label">{{ __('payroll.staff_name') }}:</td>
                <td>{{ $payroll->staff->full_name }}</td>
                <td class="label">{{ __('payroll.status') }}:</td>
                <td>
                    <span style="color: {{ $payroll->status == 'paid' ? 'green' : 'red' }}; font-weight: bold;">
                        {{ ucfirst($payroll->status) }}
                    </span>
                </td>
            </tr>
            <tr>
                <td class="label">{{ __('payroll.designation') }}:</td>
                <td>{{ $payroll->staff->designation }}</td>
                <td class="label">{{ __('payroll.total_days') }}:</td>
                <td>{{ $payroll->total_days }} ({{ __('payroll.present') }}: {{ $payroll->present_days }})</td>
            </tr>
        </table>

        <table class="salary-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="amount-col">Amount ({{ config('app.currency_symbol', '$') }})</th>
                </tr>
            </thead>
            <tbody>
                {{-- Earnings --}}
                <tr>
                    <td><strong>{{ __('payroll.base_salary') }}</strong></td>
                    <td class="amount-col">{{ number_format($payroll->basic_pay, 2) }}</td>
                </tr>
                @if($payroll->total_allowance > 0)
                <tr>
                    <td>{{ __('payroll.allowances') }}</td>
                    <td class="amount-col">{{ number_format($payroll->total_allowance, 2) }}</td>
                </tr>
                @endif
                
                <tr style="background-color: #e8f5e9;">
                    <td><strong>Gross Salary</strong></td>
                    <td class="amount-col"><strong>{{ number_format($payroll->basic_pay + $payroll->total_allowance, 2) }}</strong></td>
                </tr>

                {{-- Deductions --}}
                @if($payroll->total_deduction > 0)
                <tr>
                    <td style="color: #d32f2f;">{{ __('payroll.deductions') }} (Absents/Tax)</td>
                    <td class="amount-col" style="color: #d32f2f;">- {{ number_format($payroll->total_deduction, 2) }}</td>
                </tr>
                @endif

                {{-- Net Pay --}}
                <tr style="background-color: #e3f2fd; border-top: 2px solid #333;">
                    <td style="font-size: 14px;"><strong>{{ __('payroll.net_salary') }}</strong></td>
                    <td class="amount-col" style="font-size: 14px;"><strong>{{ number_format($payroll->net_salary, 2) }}</strong></td>
                </tr>
            </tbody>
        </table>

        <div class="signatures">
            <div style="float: left; width: 200px; text-align: center; border-top: 1px solid #333; padding-top: 5px;">
                Employee Signature
            </div>
            <div class="sign-box">
                Authorized Signature
            </div>
        </div>

        <div class="footer">
            {{ __('results.computer_generated') }} | Generated on: {{ now()->format('d M, Y h:i A') }}
        </div>
    </div>

</body>
</html>