<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('results.page_title') }} - {{ $student->full_name }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; background: #f0f0f0; padding: 20px; }
        .result-card {
            background: #fff;
            max-width: 210mm; /* A4 Width */
            margin: 0 auto;
            padding: 40px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            position: relative;
        }
        .header { text-align: center; border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 30px; }
        .logo { max-height: 80px; margin-bottom: 10px; }
        .inst-name { font-size: 24px; font-weight: bold; text-transform: uppercase; color: #333; }
        .inst-address { font-size: 14px; color: #777; }
        
        .exam-title { text-align: center; margin: 20px 0; font-size: 18px; font-weight: bold; background: #f8f9fa; padding: 8px; border-radius: 5px; }
        
        .student-info { width: 100%; margin-bottom: 30px; }
        .student-info td { padding: 5px; font-size: 14px; }
        .label { font-weight: bold; color: #555; width: 120px; }

        .marks-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .marks-table th, .marks-table td { border: 1px solid #ddd; padding: 10px; text-align: center; font-size: 14px; }
        .marks-table th { background: #3a7afe; color: #fff; text-transform: uppercase; font-size: 12px; }
        .marks-table tr:nth-child(even) { background: #f9f9f9; }
        .text-left { text-align: left !important; }

        .summary-box { float: right; width: 40%; border: 1px solid #3a7afe; padding: 15px; border-radius: 8px; background: #f4f8ff; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
        .summary-row.total { font-weight: bold; font-size: 16px; border-top: 1px solid #ccc; padding-top: 8px; margin-top: 8px; }

        .footer { margin-top: 100px; display: flex; justify-content: space-between; padding-top: 20px; }
        .signature { text-align: center; border-top: 1px solid #333; width: 30%; padding-top: 5px; font-size: 14px; }

        @media print {
            body { background: #fff; padding: 0; }
            .result-card { box-shadow: none; padding: 20px; width: 100%; max-width: 100%; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #3a7afe; color: white; border: none; cursor: pointer; border-radius: 5px;">{{ __('results.print_btn') }}</button>
    </div>

    <div class="result-card">
        {{-- Header --}}
        <div class="header">
            @if($institution->logo)
                <img src="{{ asset('storage/'.$institution->logo) }}" alt="Logo" class="logo">
            @endif
            <div class="inst-name">{{ $institution->name }}</div>
            <div class="inst-address">{{ $institution->address }} | {{ $institution->email }}</div>
        </div>

        <div class="exam-title">
            {{ $exam->name }} - {{ $exam->academicSession->name }}
        </div>

        {{-- Student Info --}}
        <table class="student-info">
            <tr>
                <td class="label">{{ __('results.student_name') }}</td>
                <td>{{ $student->full_name }}</td>
                <td class="label">{{ __('results.roll_number') }}</td>
                <td>{{ $student->admission_number ?? __('results.na') }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('results.father_name') }}</td>
                <td>{{ $student->guardian_name }}</td>
                <td class="label">{{ __('results.class') }}</td>
                <td>{{ $classSection->name ?? __('results.na') }} {{ isset($classSection->gradeLevel) ? '('.$classSection->gradeLevel->name.')' : '' }}</td>
            </tr>
        </table>

        {{-- Results Table --}}
        <table class="marks-table">
            <thead>
                <tr>
                    <th class="text-left" width="5%">#</th>
                    <th class="text-left">{{ __('results.subject') }}</th>
                    @if($type === 'university')
                        <th>{{ __('results.credit_hours') }}</th>
                    @endif
                    <th>{{ __('results.total_marks') }}</th>
                    <th>{{ __('results.obtained') }}</th>
                    <th>{{ __('results.percentage') }}</th>
                    <th>{{ __('results.grade') }}</th>
                    @if($type === 'university')
                        <th>{{ __('results.gpa') }}</th>
                    @endif
                    <th>{{ __('results.remarks') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($subjects as $index => $sub)
                <tr>
                    <td class="text-left">{{ $index + 1 }}</td>
                    <td class="text-left">
                        <strong>{{ $sub['name'] }}</strong><br>
                        <span style="font-size: 11px; color: #777;">{{ $sub['code'] }}</span>
                    </td>
                    @if($type === 'university')
                        <td>{{ $sub['credit_hours'] }}</td>
                    @endif
                    <td>{{ $sub['total'] }}</td>
                    <td>{{ $sub['obtained'] }}</td>
                    <td>{{ $sub['percentage'] }}%</td>
                    <td style="font-weight: bold; color: {{ $sub['grade'] == 'F' ? 'red' : 'black' }}">{{ $sub['grade'] }}</td>
                    @if($type === 'university')
                        <td>{{ number_format($sub['gp'], 2) }}</td>
                    @endif
                    <td>{{ $sub['remarks'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Summary & Totals --}}
        <div style="overflow: hidden;">
            <div class="summary-box">
                @if($type === 'university')
                    <div class="summary-row"><span>{{ __('results.total_credits') }}</span> <strong>{{ $summary['total_credits'] }}</strong></div>
                    <div class="summary-row"><span>{{ __('results.gpa_current') }}</span> <strong>{{ number_format($summary['gpa'], 2) }}</strong></div>
                    <div class="summary-row total"><span>{{ __('results.cgpa') }}</span> <strong>{{ number_format($summary['cgpa'], 2) }}</strong></div>
                @else
                    <div class="summary-row"><span>{{ __('results.total_marks') }}:</span> <strong>{{ $summary['total_marks'] }}</strong></div>
                    <div class="summary-row"><span>{{ __('results.obtained_marks_summary') }}</span> <strong>{{ $summary['obtained_marks'] }}</strong></div>
                    <div class="summary-row total"><span>{{ __('results.percentage') }}:</span> <strong>{{ $summary['percentage'] }}%</strong></div>
                    <div class="summary-row"><span>{{ __('results.overall_grade') }}</span> 
                        @php 
                            $grade = '';
                            $p = $summary['percentage'];
                            if ($p >= 90) $grade = 'A+';
                            elseif ($p >= 80) $grade = 'A';
                            elseif ($p >= 70) $grade = 'B';
                            elseif ($p >= 60) $grade = 'C';
                            elseif ($p >= 50) $grade = 'D';
                            else $grade = 'F';
                        @endphp
                        <strong>{{ $grade }}</strong>
                    </div>
                @endif
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <div class="signature">{{ __('results.class_teacher') }}</div>
            <div class="signature">{{ __('results.controller_exam') }}</div>
            <div class="signature">{{ __('results.principal') }}</div>
        </div>

        <div style="text-align: center; margin-top: 30px; font-size: 12px; color: #999;">
            {{ __('results.computer_generated') }} <br>
            {{ __('results.generated_on') }} {{ now()->format('d M, Y h:i A') }}
        </div>
    </div>

</body>
</html>