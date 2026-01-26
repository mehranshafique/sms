<!DOCTYPE html>
<html>
<head>
    <title>{{ __('marks.award_list') }} - {{ $subject->name }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #333; }
        .container { width: 100%; margin: 0 auto; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .header h2 { margin: 0; text-transform: uppercase; font-size: 18px; }
        .header p { margin: 2px 0; font-size: 11px; }
        
        .info-table { width: 100%; margin-bottom: 20px; font-size: 13px; }
        .info-table td { padding: 5px; }
        .info-label { font-weight: bold; width: 120px; }

        .marks-table { width: 100%; border-collapse: collapse; }
        .marks-table th, .marks-table td { border: 1px solid #000; padding: 6px; text-align: center; }
        .marks-table th { background-color: #f0f0f0; font-weight: bold; }
        .marks-table td.name { text-align: left; padding-left: 8px; }
        
        .summary-box { margin-top: 20px; border: 1px solid #000; padding: 10px; width: 40%; float: right; }
        
        /* Fixed Signature Layout for DomPDF */
        .signature-table { width: 100%; margin-top: 50px; border: none; }
        .signature-table td { border: none; text-align: center; vertical-align: bottom; padding: 0 10px; }
        .sig-line { border-top: 1px solid #000; margin: 0 auto; width: 80%; display: block; margin-bottom: 5px; }
        
        .fail { color: red; font-weight: bold; }
        .pass { color: green; font-weight: bold; }
        
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; border-top: 1px dashed #ccc; padding-top: 5px; }
        @page { margin: 20px; }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <h2>{{ $exam->institution->name }}</h2>
            <p>{{ $exam->institution->address }}</p>
            <h3 style="margin-top: 10px; text-decoration: underline;">{{ __('marks.award_list') }}</h3>
            <p>{{ $exam->name }} ({{ $exam->academicSession->name }})</p>
        </div>

        {{-- Exam Info --}}
        <table class="info-table">
            <tr>
                <td class="info-label">{{ __('marks.grade') }}:</td>
                <td>{{ $classSection->gradeLevel->name }}</td>
                <td class="info-label">{{ __('marks.section') }}:</td>
                <td>{{ $classSection->name }}</td>
            </tr>
            <tr>
                <td class="info-label">{{ __('marks.subject') }}:</td>
                <td>{{ $subject->name }} ({{ $subject->code }})</td>
                <td class="info-label">{{ __('marks.teacher') }}:</td>
                <td>{{ $teacherName }}</td>
            </tr>
            <tr>
                <td class="info-label">{{ __('marks.total_marks') }}:</td>
                <td><strong>{{ $maxMarks }}</strong></td>
                <td class="info-label">{{ __('marks.pass_marks') }}:</td>
                <td><strong>{{ $passMarks }}</strong></td>
            </tr>
        </table>

        {{-- Marks Table --}}
        <table class="marks-table">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="15%">{{ __('marks.admission_no') }}</th>
                    <th class="name">{{ __('marks.student_name') }}</th>
                    <th width="15%">{{ __('marks.marks_obtained') }}</th>
                    <th width="10%">%</th>
                    <th width="10%">{{ __('marks.grade') }}</th>
                    <th width="10%">{{ __('marks.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($students as $index => $enrollment)
                    @php
                        $student = $enrollment->student;
                        $record = $marks[$student->id] ?? null;
                        $mark = $record ? $record->marks_obtained : 0;
                        $isAbsent = $record ? $record->is_absent : false;
                        
                        $percentage = ($maxMarks > 0) ? ($mark / $maxMarks) * 100 : 0;
                        
                        // Calculate Grade based on percentage
                        $gradeLetter = '-';
                        foreach($gradingScale as $scale) {
                            if ($percentage >= $scale['min']) {
                                $gradeLetter = $scale['grade'];
                                break;
                            }
                        }

                        // Determine Pass/Fail based on configured pass marks
                        $isPass = ($mark >= $passMarks);
                        $statusLabel = $isPass ? __('marks.pass') : __('marks.fail');
                        $statusClass = $isPass ? 'pass' : 'fail';
                        
                        if($isAbsent) {
                            $markDisplay = 'ABS';
                            $percentageDisplay = '-';
                            $statusLabel = __('marks.absent');
                            $statusClass = 'fail';
                            $gradeLetter = 'F';
                        } else {
                            $markDisplay = $mark;
                            $percentageDisplay = round($percentage, 1) . '%';
                        }
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $student->admission_number }}</td>
                        <td class="name">{{ $student->full_name }}</td>
                        <td>{{ $markDisplay }}</td>
                        <td>{{ $percentageDisplay }}</td>
                        <td>{{ $gradeLetter }}</td>
                        <td class="{{ $statusClass }}">{{ $statusLabel }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Summary --}}
        <div class="summary-box">
            <strong>{{ __('marks.summary') }}</strong><br>
            <table width="100%">
                <tr>
                    <td>{{ __('marks.total_students') }}:</td>
                    <td align="right">{{ $totalStudents }}</td>
                </tr>
                <tr>
                    <td>{{ __('marks.present') }}:</td>
                    <td align="right">{{ $presentCount }}</td>
                </tr>
                <tr>
                    <td>{{ __('marks.absent') }}:</td>
                    <td align="right">{{ $absentCount }}</td>
                </tr>
            </table>
        </div>
        
        <div style="clear: both;"></div>

        {{-- Signatures (Refactored to Table for DomPDF Stability) --}}
        <table class="signature-table">
            <tr>
                <td width="33%">
                    <div class="sig-line"></div>
                    {{ __('marks.teacher_sign') }}
                </td>
                <td width="33%">
                    <div class="sig-line"></div>
                    {{ __('exam_schedule.controller_sign') }}
                </td>
                <td width="33%">
                    <div class="sig-line"></div>
                    {{ __('exam_schedule.principal_sign') }}
                </td>
            </tr>
        </table>

        <div class="footer">
            Generated on {{ now()->format('d M, Y h:i A') }}
        </div>
    </div>
</body>
</html>