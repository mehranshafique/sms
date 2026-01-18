<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('marks.award_list') }} - {{ $subject->name }}</title>
    <style>
        @page { margin: 20px; }
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 15px; }
        .school-name { font-size: 18px; font-weight: bold; text-transform: uppercase; }
        .doc-title { font-size: 14px; font-weight: bold; margin-top: 5px; text-decoration: underline; text-transform: uppercase; }
        
        .meta-table { width: 100%; margin-bottom: 15px; }
        .meta-table td { padding: 3px; }
        .label { font-weight: bold; width: 120px; }
        
        .marks-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .marks-table th, .marks-table td { border: 1px solid #444; padding: 6px; text-align: center; }
        .marks-table th { background-color: #f0f0f0; text-transform: uppercase; font-size: 10px; }
        .text-left { text-align: left !important; padding-left: 10px !important; }
        
        .summary-box { border: 1px solid #333; padding: 10px; margin-top: 20px; width: 40%; display: inline-block; vertical-align: top; }
        .signatures { margin-top: 50px; width: 100%; }
        .sig-box { float: left; width: 33%; text-align: center; border-top: 1px solid #333; padding-top: 5px; margin-top: 40px; }
        
        .absent-text { color: red; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <div class="school-name">{{ $exam->institution->name }}</div>
        <div>{{ $exam->institution->address }}</div>
        <div class="doc-title">{{ __('marks.award_list') }}</div>
    </div>

    <table class="meta-table">
        <tr>
            <td class="label">{{ __('marks.exam') }}:</td>
            <td><strong>{{ $exam->name }}</strong> ({{ $exam->academicSession->name }})</td>
            <td class="label">{{ __('marks.date') }}:</td>
            <td>{{ now()->format('d M, Y') }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('marks.class') }}:</td>
            <td>{{ $classSection->gradeLevel->name }} - {{ $classSection->name }}</td>
            <td class="label">{{ __('marks.subject') }}:</td>
            <td><strong>{{ $subject->name }}</strong> (Max: {{ $subject->total_marks }})</td>
        </tr>
        <tr>
            <td class="label">{{ __('marks.teacher') }}:</td>
            <td colspan="3">{{ $teacherName }}</td>
        </tr>
    </table>

    <table class="marks-table">
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="15%">{{ __('marks.admission_no') }}</th>
                <th class="text-left">{{ __('marks.student_name') }}</th>
                <th width="15%">{{ __('marks.marks_obtained') }}</th>
                <th width="15%">{{ __('reports.grade') ?? 'Grade' }}</th>
                <th width="15%">{{ __('marks.status') ?? 'Status' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $index => $student)
                @php
                    $record = $marks[$student->student_id] ?? null;
                    
                    // Strict Check: Ensure 1 is treated as True
                    $isAbsent = $record && (bool)$record->is_absent;
                    
                    $mark = '-';
                    $grade = '-';
                    $status = '-';
                    
                    if ($record && !$isAbsent) {
                        $mark = $record->marks_obtained;
                        $max = $subject->total_marks ?: 100;
                        $pct = ($mark / $max) * 100;
                        
                        // Dynamic Grading from DB
                        $grade = 'F'; // Default
                        foreach($gradingScale as $scale) {
                            if($pct >= $scale['min']) {
                                $grade = $scale['grade'];
                                break;
                            }
                        }
                        
                        $status = $pct >= 50 ? 'Pass' : 'Fail';
                    } elseif ($isAbsent) {
                        $mark = 'ABS';
                        $status = __('marks.absent');
                        $grade = '-';
                    }
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $student->student->admission_number }}</td>
                    <td class="text-left">{{ $student->student->full_name }}</td>
                    <td style="font-weight: bold;" class="{{ $isAbsent ? 'absent-text' : '' }}">{{ $mark }}</td>
                    <td>{{ $grade }}</td>
                    <td class="{{ $isAbsent ? 'absent-text' : '' }}">{{ $status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary-box">
        <strong>{{ __('marks.summary') ?? 'Summary' }}</strong><br>
        {{ __('marks.total_students') }}: {{ $totalStudents }}<br>
        {{ __('marks.present') ?? 'Present' }}: {{ $presentCount }}<br>
        {{ __('marks.absent') ?? 'Absent' }}: {{ $absentCount }}
    </div>

    <div class="signatures">
        <div class="sig-box">{{ __('marks.teacher_sign') ?? 'Subject Teacher' }}</div>
        <div class="sig-box" style="margin: 40px 0 0 33%;">{{ __('reports.principal') }}</div>
    </div>

</body>
</html>