<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('exam_schedule.admit_card') }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; }
        .page-break { page-break-after: always; }
        .container { border: 2px solid #000; padding: 20px; margin-bottom: 20px; height: 95%; position: relative; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .logo { width: 80px; height: auto; position: absolute; top: 20px; left: 20px; }
        .school-name { font-size: 20px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
        .exam-title { font-size: 16px; font-weight: bold; margin-top: 5px; background: #eee; display: inline-block; padding: 5px 20px; border-radius: 15px; }
        
        .student-info { width: 100%; margin-bottom: 20px; }
        .student-info td { padding: 5px; vertical-align: top; }
        .photo-box { width: 100px; height: 120px; border: 1px solid #999; text-align: center; line-height: 120px; background: #f9f9f9; }
        .photo-img { width: 100px; height: 120px; object-fit: cover; }
        
        .schedule-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .schedule-table th, .schedule-table td { border: 1px solid #333; padding: 8px; text-align: center; }
        .schedule-table th { background-color: #f0f0f0; font-weight: bold; }
        .schedule-table td.subject { text-align: left; padding-left: 10px; }
        
        .footer { position: absolute; bottom: 30px; width: 100%; text-align: center; }
        .instructions { font-size: 10px; margin-top: 20px; border-top: 1px dashed #999; padding-top: 10px; text-align: left; }
        .signatures { margin-top: 40px; display: table; width: 100%; }
        .sig-box { display: table-cell; text-align: center; width: 33%; vertical-align: bottom; }
        .line { border-top: 1px solid #000; width: 80%; margin: 0 auto 5px auto; }
    </style>
</head>
<body>
    @foreach($students as $student)
        <div class="container">
            {{-- Header --}}
            <div class="header">
                @if($exam->institution && $exam->institution->logo)
                    <img src="{{ public_path('storage/'.$exam->institution->logo) }}" class="logo" alt="Logo">
                @endif
                <div class="school-name">{{ $exam->institution->name ?? 'School Name' }}</div>
                <div>{{ $exam->institution->address ?? '' }}</div>
                <div class="exam-title">{{ $exam->name }} - {{ __('exam_schedule.admit_card') }}</div>
            </div>

            {{-- Student Details --}}
            <table class="student-info">
                <tr>
                    <td width="75%">
                        <table width="100%">
                            <tr>
                                <td width="30%"><strong>{{ __('student.full_name') }}:</strong></td>
                                <td>{{ $student->full_name }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('student.admission_no') }}:</strong></td>
                                <td>{{ $student->admission_number }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('class_section.page_title') }}:</strong></td>
                                <td>{{ $classSection->gradeLevel->name }} - {{ $classSection->name }}</td>
                            </tr>
                            {{-- REMOVED ROLL NUMBER ROW AS REQUESTED --}}
                            <tr>
                                <td><strong>{{ __('student.parent_guardian') }}:</strong></td>
                                <td>{{ $student->parent->father_name ?? $student->parent->guardian_name ?? '-' }}</td>
                            </tr>
                        </table>
                    </td>
                    <td width="25%" align="right">
                        <div class="photo-box">
                            @if($student->student_photo)
                                <img src="{{ public_path('storage/'.$student->student_photo) }}" class="photo-img">
                            @else
                                {{ __('student.photo') }}
                            @endif
                        </div>
                    </td>
                </tr>
            </table>

            {{-- Date Sheet --}}
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>{{ __('exam_schedule.date') }}</th>
                        <th>{{ __('exam_schedule.subject') }}</th>
                        <th>{{ __('exam_schedule.time') }}</th>
                        <th>{{ __('exam_schedule.room') }}</th>
                        <th>{{ __('exam_schedule.invigilator_sign') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules->sortBy('exam_date') as $schedule)
                        <tr>
                            <td>{{ $schedule->exam_date->format('d M, Y (D)') }}</td>
                            <td class="subject">{{ $schedule->subject->name }}</td>
                            <td>{{ $schedule->start_time->format('h:i A') }} - {{ $schedule->end_time->format('h:i A') }}</td>
                            <td>{{ $schedule->room_number ?? '-' }}</td>
                            <td></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">{{ __('exam_schedule.no_schedules_found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Instructions --}}
            <div class="instructions">
                <strong>{{ __('exam_schedule.instructions') }}:</strong>
                <ul>
                    <li>{{ __('exam_schedule.instruction_1') }}</li>
                    <li>{{ __('exam_schedule.instruction_2') }}</li>
                    <li>{{ __('exam_schedule.instruction_3') }}</li>
                </ul>
            </div>

            {{-- Footer Signatures --}}
            <div class="footer">
                <div class="signatures">
                    <div class="sig-box">
                        <div class="line"></div>
                        {{ __('exam_schedule.student_sign') }}
                    </div>
                    <div class="sig-box">
                        <div class="line"></div>
                        {{ __('exam_schedule.controller_sign') }}
                    </div>
                    <div class="sig-box">
                        <div class="line"></div>
                        {{ __('exam_schedule.principal_sign') }}
                    </div>
                </div>
                <div style="margin-top: 10px; font-size: 9px; color: #777;">
                    {{ __('exam_schedule.generated_on') }}: {{ now()->format('d M, Y h:i A') }}
                </div>
            </div>
        </div>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
</html>