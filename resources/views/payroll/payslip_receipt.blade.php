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

        $structure = $payroll->staff->salaryStructure;
        
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

    <div class="header text-center">
        @if(!empty($logoBase64))
            <img src="{{ $logoBase64 }}" style="max-height: 50px; max-width: 100%; margin-bottom: 5px;">
        @elseif(!empty($payroll->staff->institution->logo))
            <img src="{{ asset('storage/' . $payroll->staff->institution->logo) }}" style="max-height: 50px; max-width: 100%; margin-bottom: 5px;">
        @endif
        
        <div class="logo-text">{{ $payroll->staff->institution->name ?? config('app.name') }}</div>
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
        
        @foreach($allowances as $key => $val)
            @php
                $label = is_array($val) ? ($val['name'] ?? 'Allowance') : $key;
                $amount = is_array($val) ? ($val['amount'] ?? 0) : $val;
            @endphp
            <tr>
                <td>{{ ucfirst(str_replace('_', ' ', $label)) }}</td>
                <td class="amount-col">{{ number_format((float)$amount, 2) }}</td>
            </tr>
        @endforeach

        {{-- Gross Salary Row --}}
        <tr class="bold">
            <td style="padding-top: 3px;">{{ __('payroll.gross_salary') }}</td>
            <td class="amount-col" style="padding-top: 3px;">{{ number_format($payroll->basic_pay + $payroll->total_allowance, 2) }}</td>
        </tr>
    </table>

    <div class="line"></div>

    {{-- Deductions --}}
    @if($payroll->total_deduction > 0 || !empty($deductions))
        <div class="bold" style="margin-bottom: 2px;">{{ __('payroll.deductions') }}</div>
        <table>
            @foreach($deductions as $key => $val)
                @php
                    $label = is_array($val) ? ($val['name'] ?? 'Deduction') : $key;
                    $amount = is_array($val) ? ($val['amount'] ?? 0) : $val;
                @endphp
                <tr>
                    <td>{{ ucfirst(str_replace('_', ' ', $label)) }}</td>
                    <td class="amount-col">- {{ number_format((float)$amount, 2) }}</td>
                </tr>
            @endforeach
            
            @if($lop > 0.01)
                <tr>
                    <td>{{ __('payroll.lop') }}</td>
                    <td class="amount-col">- {{ number_format($lop, 2) }}</td>
                </tr>
            @endif

            <tr class="bold">
                <td style="padding-top: 3px;">{{ __('payroll.total_deduction') }}</td>
                <td class="amount-col" style="padding-top: 3px;">- {{ number_format($payroll->total_deduction, 2) }}</td>
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
            {{ __('payroll.status') }}: {{ __('payroll.'.$payroll->status) ?? ucfirst($payroll->status) }}<br>
            {{ __('results.computer_generated') ?? 'Computer Generated' }}<br>
            {{ now()->format('d/m/Y H:i') }}
        </p>
    </div>
</body>
</html>