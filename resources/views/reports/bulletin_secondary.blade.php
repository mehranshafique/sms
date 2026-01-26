<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('reports.student_bulletin') }} - Semester {{ $semester }}</title>
    <style>
        @page { margin: 25px; }
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #222; }
        .header-main { width: 100%; margin-bottom: 20px; border-bottom: 3px double #000; padding-bottom: 10px; }
        .logo { width: 70px; height: 70px; float: left; margin-right: 15px; }
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
            <div style="font-size: 18px; font-weight: bold; color: #555;">{{ __('reports.semester_report') }}</div>
            <div>{{ __('reports.semester') }}: {{ $semester }}</div>
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

    @php
        // Determine dynamic headers based on semester
        $p1_label = ($semester == 1) ? "Period 1" : "Period 3";
        $p2_label = ($semester == 1) ? "Period 2" : "Period 4";
        $exam_label = "Exam S" . $semester;
    @endphp

    <table class="marks-table">
        <thead>
            <tr>
                <th class="text-left" style="width: 25%;">{{ __('reports.subject') }}</th>
                <th>{{ $p1_label }} <br><small>(Max)</small></th>
                <th>{{ $p2_label }} <br><small>(Max)</small></th>
                <th>{{ $exam_label }} <br><small>(Max)</small></th>
                <th>Total <br><small>(Max)</small></th>
                <th>{{ __('reports.grade') }}</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $grandTotalObtained = 0; 
                $grandTotalMax = 0; 
            @endphp

            @foreach($data as $row)
            @php 
                $isFail = false;
                $rowGrade = '-';
                
                if(is_numeric($row['total_score'])) {
                    $grandTotalObtained += $row['total_score'];
                    $grandTotalMax += $row['total_max'];
                    
                    // Fail if less than 50%
                    if ($row['total_score'] < ($row['total_max'] / 2)) {
                        $isFail = true;
                    }
                    
                    // Per-Subject Grading Logic from gradingScale
                    $subjPercent = ($row['total_score'] / $row['total_max']) * 100;
                    if(isset($gradingScale) && is_array($gradingScale) && count($gradingScale) > 0) {
                        foreach($gradingScale as $g) {
                            if($subjPercent >= $g['min']) {
                                $rowGrade = $g['grade'];
                                break;
                            }
                        }
                    } else {
                        // Fallback
                        $rowGrade = $isFail ? 'Fail' : 'Pass';
                    }
                }
            @endphp
            <tr>
                <td class="text-left">{{ $row['subject']->name }}</td>
                <td>{{ $row['p1_score'] }} <span class="text-muted text-xs">/{{ $row['p_max'] }}</span></td>
                <td>{{ $row['p2_score'] }} <span class="text-muted text-xs">/{{ $row['p_max'] }}</span></td>
                <td>{{ $row['exam_score'] }} <span class="text-muted text-xs">/{{ $row['exam_max'] }}</span></td>
                <td style="font-weight: bold; {{ $isFail ? 'color: red;' : '' }}">
                    {{ $row['total_score'] }} <span class="text-muted" style="font-weight: normal; font-size: 9px;">/{{ $row['total_max'] }}</span>
                </td>
                <td>{{ $rowGrade }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="clear"></div>
    
    @php
        $percentage = ($grandTotalMax > 0) ? ($grandTotalObtained / $grandTotalMax) * 100 : 0;
        
        $mention = 'Participated'; // Default
        
        // Dynamic Mention Calculation
        if(isset($gradingScale) && is_array($gradingScale) && count($gradingScale) > 0) {
            foreach($gradingScale as $g) {
                if($percentage >= $g['min']) {
                    // Use remark if available, else use grade
                    $mention = !empty($g['remark']) ? $g['remark'] : $g['grade'];
                    break;
                }
            }
        } else {
            // Hardcoded Fallback if no settings
            if ($percentage >= 80) $mention = 'Excellent';
            elseif ($percentage >= 70) $mention = 'Very Good';
            elseif ($percentage >= 60) $mention = 'Good';
            elseif ($percentage >= 50) $mention = 'Satisfactory';
            else $mention = 'Fail';
        }
    @endphp

    <div class="percentage-box">
        {{ __('reports.average') }}: {{ number_format($percentage, 2) }}%
    </div>
    
    <div class="mention">
        {{ __('reports.mention') }}: {{ $mention }}
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