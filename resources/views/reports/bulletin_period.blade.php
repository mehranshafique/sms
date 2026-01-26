<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('reports.student_bulletin') }} - {{ strtoupper($period) }}</title>
    <style>
        @page { margin: 25px; }
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #222; }
        .header-main { width: 100%; margin-bottom: 20px; border-bottom: 3px double #000; padding-bottom: 10px; }
        .school-info { float: left; width: 400px; }
        .school-title { font-size: 22px; font-weight: bold; margin: 0; }
        
        .clear { clear: both; }
        
        .student-badge { background: #f4f4f4; padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px; }
        
        .marks-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        .marks-table th, .marks-table td { border: 1px solid #333; padding: 6px; text-align: center; }
        .marks-table th { background: #333; color: #fff; text-transform: uppercase; font-size: 9px; }
        .text-left { text-align: left !important; padding-left: 10px !important; }
        
        .percentage-box { float: right; border: 2px solid #000; padding: 10px 20px; font-size: 16px; font-weight: bold; background: #eee; }
        
        .mention { margin-top: 10px; font-style: italic; color: #555; }
        .fail { color: red; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header-main">
        <div class="school-info">
            <h1 class="school-title">{{ $student->institution->name }}</h1>
            <p>{{ $student->institution->address }} | {{ $student->institution->email }}</p>
        </div>
        <div style="float: right; text-align: right;">
            <div style="font-size: 18px; font-weight: bold; color: #555;">{{ __('reports.period_report') ?? 'Period Report' }}</div>
            <div>{{ strtoupper($period) }}</div>
        </div>
        <div class="clear"></div>
    </div>

    <div class="student-badge">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%;">
                    <strong>{{ __('student.name') }}:</strong> {{ $student->full_name }}<br>
                    <strong>{{ __('student.id') }}:</strong> {{ $student->admission_number }}
                </td>
                <td style="width: 50%; text-align: right;">
                    <strong>{{ __('student.class_grade') }}:</strong> {{ $enrollment->classSection->gradeLevel->name }} - {{ $enrollment->classSection->name }}<br>
                    <strong>{{ __('student.academic_year') }}:</strong> {{ $enrollment->academicSession->name }}
                </td>
            </tr>
            @if(isset($ranks))
            <tr>
                <td colspan="2" style="border-top: 1px dashed #ccc; padding-top: 5px; margin-top: 5px;">
                    <strong>Section Rank:</strong> {{ $ranks['section_rank'] }} / {{ $ranks['section_total'] }} &nbsp;&nbsp;|&nbsp;&nbsp;
                    <strong>Grade Rank:</strong> {{ $ranks['grade_rank'] }} / {{ $ranks['grade_total'] }}
                </td>
            </tr>
            @endif
        </table>
    </div>

    <table class="marks-table">
        <thead>
            <tr>
                <th class="text-left" style="width: 40%;">{{ __('reports.subject') }}</th>
                <th>{{ __('reports.marks_obtained') }}</th>
                <th>{{ __('reports.max_marks') }}</th>
                <th>{{ __('reports.percentage') }}</th>
                <th>{{ __('reports.grade') }}</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $totalObtained = 0; 
                $totalMax = 0; 
            @endphp

            @foreach($data as $row)
            @php 
                $obtained = is_numeric($row['obtained']) ? $row['obtained'] : 0;
                $max = $row['max'];
                
                if(is_numeric($row['obtained'])) {
                    $totalObtained += $obtained;
                    $totalMax += $max;
                }

                $percentage = $row['percentage'];
                $grade = '-';

                // Dynamic Grading
                if(isset($gradingScale) && is_array($gradingScale) && count($gradingScale) > 0) {
                    foreach($gradingScale as $g) {
                        if($percentage >= $g['min']) {
                            $grade = $g['grade'];
                            break;
                        }
                    }
                } else {
                    $grade = $percentage >= 50 ? 'Pass' : 'Fail';
                }
            @endphp
            <tr>
                <td class="text-left">{{ $row['subject']->name }}</td>
                <td>{{ $row['obtained'] }}</td>
                <td>{{ $max }}</td>
                <td>{{ number_format($percentage, 1) }}%</td>
                <td style="{{ $percentage < 50 ? 'color: red; font-weight: bold;' : '' }}">{{ $grade }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="clear"></div>
    
    @php
        $globalPercentage = ($totalMax > 0) ? ($totalObtained / $totalMax) * 100 : 0;
        $globalMention = 'Participated';

        if(isset($gradingScale) && is_array($gradingScale) && count($gradingScale) > 0) {
            foreach($gradingScale as $g) {
                if($globalPercentage >= $g['min']) {
                    $globalMention = !empty($g['remark']) ? $g['remark'] : $g['grade'];
                    break;
                }
            }
        }
    @endphp

    <div class="percentage-box">
        {{ __('reports.average') }}: {{ number_format($globalPercentage, 2) }}%
    </div>
    
    <div class="mention">
        {{ __('reports.mention') }}: {{ $globalMention }}
    </div>

    <div style="margin-top: 80px; width: 100%;">
        <div style="float: left; width: 45%; border-top: 1px solid #000; text-align: center; padding-top: 5px;">
            {{ __('reports.class_teacher') }}
        </div>
        <div style="float: right; width: 45%; border-top: 1px solid #000; text-align: center; padding-top: 5px;">
            {{ __('reports.principal') }}
        </div>
    </div>

</body>
</html>