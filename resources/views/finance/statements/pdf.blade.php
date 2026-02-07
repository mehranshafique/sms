<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Student Statement - {{ $student->admission_number }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { width: 100%; border-bottom: 2px solid #444; padding-bottom: 10px; margin-bottom: 20px; }
        .logo { float: left; width: 150px; } // Adjust if you have a logo variable
        .company-details { float: right; text-align: right; }
        .title { text-align: center; font-size: 18px; font-weight: bold; text-transform: uppercase; clear: both; padding-top: 20px; }
        
        .student-info { margin-bottom: 20px; width: 100%; }
        .student-info td { padding: 5px; }
        .label { font-weight: bold; width: 120px; }

        table.ledger { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.ledger th, table.ledger td { border: 1px solid #ddd; padding: 8px; }
        table.ledger th { background-color: #f2f2f2; text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .summary { float: right; width: 40%; }
        .summary table { width: 100%; border-collapse: collapse; }
        .summary td { padding: 5px; border-bottom: 1px solid #eee; }
        .summary .total { font-weight: bold; font-size: 14px; border-top: 2px solid #333; }
        
        .footer { position: fixed; bottom: 0; left: 0; right: 0; height: 30px; border-top: 1px solid #ddd; text-align: center; font-size: 10px; padding-top: 10px; }
    </style>
</head>
<body>

    <div class="header">
        <div class="company-details">
            <h2>{{ $student->institution->name ?? 'School Name' }}</h2>
            <p>{{ $student->institution->address ?? '' }}</p>
            <p>Date: {{ date('d M, Y') }}</p>
        </div>
        <div style="clear: both;"></div>
    </div>

    <div class="title">Student Financial Statement</div>

    <table class="student-info">
        <tr>
            <td class="label">Student Name:</td>
            <td>{{ $student->full_name }}</td>
            <td class="label">Admission No:</td>
            <td>{{ $student->admission_number }}</td>
        </tr>
        <tr>
            <td class="label">Class/Grade:</td>
            <td>{{ $student->gradeLevel->name ?? '-' }} {{ $student->currentEnrollment->classSection->name ?? '' }}</td>
            <td class="label">Parent:</td>
            <td>{{ $student->parent->father_name ?? $student->parent->guardian_name ?? 'N/A' }}</td>
        </tr>
    </table>

    <table class="ledger">
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th class="text-right">Debit (Invoice)</th>
                <th class="text-right">Credit (Payment)</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $runningBalance = 0; 
                $totalDebit = 0;
                $totalCredit = 0;
            @endphp
            
            {{-- Initial Balance Row (Optional, starts at 0 for full history) --}}
            
            @foreach($ledger as $row)
                @php
                    $isDebit = $row['amount'] < 0;
                    $absAmount = abs($row['amount']);
                    
                    // Logic: Debit increases debt (positive balance in school context usually means debt), 
                    // Credit reduces debt. 
                    // If $row['amount'] is negative (from controller logic for Invoice), it increases balance due.
                    // If positive (Payment), it decreases balance due.
                    
                    // Adjusting logic based on Controller: 
                    // Controller sent: Invoice = -amount, Payment = +amount.
                    // Usually: Invoice = Debit (+), Payment = Credit (-). 
                    // Let's invert for standard display if needed, or follow controller.
                    // Let's assume: Balance = Total Invoiced - Total Paid.
                    // Invoice adds to Balance. Payment subtracts.
                    
                    if ($isDebit) {
                        // Invoice
                        $debit = $absAmount;
                        $credit = 0;
                        $runningBalance += $debit;
                        $totalDebit += $debit;
                    } else {
                        // Payment
                        $debit = 0;
                        $credit = $absAmount;
                        $runningBalance -= $credit;
                        $totalCredit += $credit;
                    }
                @endphp
                <tr>
                    <td>{{ $row['date']->format('d M, Y') }}</td>
                    <td>{{ $row['desc'] }}</td>
                    <td class="text-right">{{ $debit > 0 ? number_format($debit, 2) : '-' }}</td>
                    <td class="text-right">{{ $credit > 0 ? number_format($credit, 2) : '-' }}</td>
                    <td class="text-right">{{ number_format($runningBalance, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <table>
            <tr>
                <td>Total Invoiced:</td>
                <td class="text-right">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($totalDebit, 2) }}</td>
            </tr>
            <tr>
                <td>Total Paid:</td>
                <td class="text-right">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($totalCredit, 2) }}</td>
            </tr>
            <tr class="total">
                <td>Outstanding Balance:</td>
                <td class="text-right">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($runningBalance, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Generated by E-Digitex School Management System. This is a computer-generated document.
    </div>

</body>
</html>