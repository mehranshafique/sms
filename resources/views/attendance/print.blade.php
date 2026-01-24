<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ __('attendance.attendance_report') }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .details-box { margin-bottom: 20px; width: 100%; }
        .details-box td { padding: 5px; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #333; padding: 5px; text-align: center; }
        th { background-color: #f0f0f0; }
        .text-left { text-align: left; padding-left: 10px; }
        .status-p { color: green; font-weight: bold; }
        .status-a { color: red; font-weight: bold; }
        .status-l { color: orange; font-weight: bold; }
        .status-e { color: blue; font-weight: bold; }
        .status-h { color: purple; font-weight: bold; }
        .legend { font-size: 10px; margin-top: 10px; }
        .footer { font-size: 10px; text-align: center; margin-top: 30px; border-top: 1px dashed #ccc; padding-top: 10px; }
        
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
            @page { size: landscape; margin: 10mm; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: #fff; border: none; cursor: pointer; border-radius: 5px;">
            {{ __('attendance.print') }}
        </button>
    </div>

    <div class="header">
        <h2 style="margin: 0;">{{ $selectedClass->institution->name ?? 'Institution Name' }}</h2>
        <h3 style="margin: 5px 0;">{{ __('attendance.attendance_report') }}</h3>
        <div>{{ date("F Y", mktime(0, 0, 0, $month, 1, $year)) }}</div>
    </div>

    <table class="details-box" style="border: none;">
        <tr>
            <td width="15%"><strong>{{ __('attendance.class') }}:</strong></td>
            <td width="35%">{{ ($selectedClass->gradeLevel->name ?? '') . ' ' . $selectedClass->name }}</td>
            <td width="15%"><strong>{{ __('attendance.total_students') }}:</strong></td>
            <td width="35%">{{ count($students) }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th class="text-left" style="width: 200px;">{{ __('attendance.student') }}</th>
                @for($d=1; $d<=$daysInMonth; $d++)
                    <th style="font-size: 9px; width: 20px;">{{ $d }}</th>
                @endfor
                <th>%</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $enrollment)
                @php
                    $student = $enrollment->student;
                    $presents = 0;
                    $totalMarked = 0;
                @endphp
                <tr>
                    <td class="text-left">
                        <strong>{{ $student->full_name }}</strong><br>
                        <span style="font-size: 9px; color: #666;">{{ $student->admission_number }}</span>
                    </td>
                    @for($d=1; $d<=$daysInMonth; $d++)
                        @php
                            $status = $attendanceMap[$student->id][$d] ?? '-';
                            $class = '';
                            $code = '-';
                            
                            if ($status !== '-') {
                                $totalMarked++;
                                if ($status == 'present') { $code = 'P'; $class = 'status-p'; $presents++; }
                                elseif ($status == 'absent') { $code = 'A'; $class = 'status-a'; }
                                elseif ($status == 'late') { $code = 'L'; $class = 'status-l'; }
                                elseif ($status == 'excused') { $code = 'E'; $class = 'status-e'; }
                                elseif ($status == 'half_day') { $code = 'H'; $class = 'status-h'; }
                            }
                        @endphp
                        <td class="{{ $class }}">{{ $code }}</td>
                    @endfor
                    @php
                        $percentage = $totalMarked > 0 ? round(($presents / $totalMarked) * 100) : 0;
                    @endphp
                    <td>{{ $percentage }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="legend">
        <strong>{{ __('attendance.legend') }}:</strong> 
        {{ __('attendance.legend_p') }} | 
        {{ __('attendance.legend_a') }} | 
        {{ __('attendance.legend_l') }} | 
        {{ __('attendance.legend_e') }} | 
        {{ __('attendance.legend_h') }}
    </div>

    <div class="footer">
        {{ __('attendance.generated_on') }}: {{ now()->format('d M, Y h:i A') }}
    </div>
</body>
</html>