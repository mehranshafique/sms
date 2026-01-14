<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('reports.student_bulletin') }} - Trimester {{ $trimester }}</title>
    <style>
        @page { margin: 20px; }
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #333; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #444; padding-bottom: 10px; }
        .inst-name { font-size: 20px; font-weight: bold; text-transform: uppercase; margin: 0; }
        .inst-details { font-size: 10px; color: #666; }
        
        .report-title { font-size: 16px; font-weight: bold; text-decoration: underline; margin: 10px 0; text-transform: uppercase; }
        
        .info-table { width: 100%; margin-bottom: 15px; border-collapse: collapse; }
        .info-table td { padding: 4px; vertical-align: top; }
        .label { font-weight: bold; width: 100px; }

        .marks-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .marks-table th, .marks-table td { border: 1px solid #000; padding: 5px; text-align: center; }
        .marks-table th { background-color: #f2f2f2; font-weight: bold; text-transform: uppercase; font-size: 10px; }
        .text-left { text-align: left !important; }
        
        .fail { color: #d9534f; font-weight: bold; }
        .total-row { background-color: #f9f9f9; font-weight: bold; }
        
        .footer { margin-top: 30px; width: 100%; }
        .sig-box { width: 30%; text-align: center; display: inline-block; vertical-align: top; }
        .sig-space { height: 60px; }
        .stamp { border: 2px double #004085; border-radius: 50%; width: 100px; height: 100px; margin: 0 auto; color: #004085; padding-top: 35px; opacity: 0.6; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <div class="inst-name">{{ $student->institution->name }}</div>
        <div class="inst-details">{{ $student->institution->address }} | {{ $student->institution->phone }}</div>
        <div class="report-title">{{ __('reports.bulletin_title') }} - {{ __('reports.trimester') }} {{ $trimester }}</div>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">{{ __('student.full_name') }}:</td>
            <td style="font-size: 13px; font-weight: bold;">{{ $student->full_name }}</td>
            <td class="label">{{ __('student.admission_no') }}:</td>
            <td>{{ $student->admission_number }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('student.class_grade') }}:</td>
            <td>{{ $enrollment->classSection->gradeLevel->name }} - {{ $enrollment->classSection->name }}</td>
            <td class="label">{{ __('student.gender') }}:</td>
            <td>{{ ucfirst($student->gender) }}</td>
        </tr>
    </table>

    <table class="marks-table">
        <thead>
            <tr>
                <th class="text-left" style="width: 30%;">{{ __('reports.subject') }}</th>
                <th>P1</th>
                <th>P2</th>
                <th>Max (P)</th>
                <th>Exam</th>
                <th>Max (Ex)</th>
                <th>Total (TR)</th>
                <th>Max (TR)</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $grandTotalObt = 0;
                $grandTotalMax = 0;
            @endphp
            @foreach($data as $row)
                @php 
                    $subTotalObt = $row['p1_score'] + $row['p2_score'] + $row['exam_score'];
                    $subTotalMax = ($row['p_max'] * 2) + $row['exam_max'];
                    $grandTotalObt += $subTotalObt;
                    $grandTotalMax += $subTotalMax;
                @endphp
                <tr>
                    <td class="text-left">{{ $row['subject']->name }}</td>
                    <td>{{ $row['p1_score'] }}</td>
                    <td>{{ $row['p2_score'] }}</td>
                    <td style="background: #fafafa;">{{ $row['p_max'] }}</td>
                    <td>{{ $row['exam_score'] }}</td>
                    <td style="background: #fafafa;">{{ $row['exam_max'] }}</td>
                    <td class="{{ $subTotalObt < ($subTotalMax/2) ? 'fail' : '' }}">{{ $subTotalObt }}</td>
                    <td style="background: #eee;">{{ $subTotalMax }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td class="text-left">{{ __('reports.totals') }}</td>
                <td colspan="5"></td>
                <td>{{ $grandTotalObt }}</td>
                <td>{{ $grandTotalMax }}</td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-bottom: 20px;">
        <strong>{{ __('reports.percentage') }}:</strong> 
        <span style="font-size: 14px;">{{ number_format(($grandTotalObt / max(1, $grandTotalMax)) * 100, 2) }}%</span>
    </div>

    <div class="footer">
        <div class="sig-box">
            <div class="label">{{ __('reports.parent_signature') }}</div>
            <div class="sig-space"></div>
        </div>
        <div class="sig-box">
            <div class="stamp">OFFICIAL<br>STAMP</div>
        </div>
        <div class="sig-box">
            <div class="label">{{ __('reports.principal_signature') }}</div>
            <div class="sig-space"></div>
            <div style="font-weight: bold; border-top: 1px solid #000; padding-top: 5px;">{{ $student->institution->principal_name ?? 'Principal' }}</div>
        </div>
    </div>

</body>
</html>