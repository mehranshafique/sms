<!DOCTYPE html>
<html>
<head>
    <title>{{ __('reports.transcript') }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; padding: 20px; color: #333; }
        .header { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px; }
        .logo { max-height: 80px; margin-bottom: 10px; }
        .inst-name { font-size: 24px; font-weight: bold; text-transform: uppercase; }
        .inst-address { font-size: 14px; }
        .report-title { font-size: 20px; font-weight: bold; text-decoration: underline; margin-top: 15px; text-transform: uppercase; }
        
        .student-info { width: 100%; margin-bottom: 20px; }
        .student-info td { padding: 5px 0; }
        .label { font-weight: bold; width: 150px; }

        .session-block { margin-bottom: 30px; page-break-inside: avoid; }
        .session-header { background: #f0f0f0; padding: 5px 10px; font-weight: bold; border: 1px solid #ccc; font-size: 14px; }
        
        .marks-table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .marks-table th, .marks-table td { border: 1px solid #ccc; padding: 6px; text-align: center; font-size: 12px; }
        .marks-table th { background: #e9ecef; }
        .text-left { text-align: left !important; }

        .footer { position: fixed; bottom: 0; left: 0; right: 0; border-top: 1px solid #000; padding-top: 10px; font-size: 12px; }
        .signature { float: right; border-top: 1px solid #000; padding-top: 5px; text-align: center; width: 200px; margin-top: 50px; }
    </style>
</head>
<body>

    <div class="header">
        <div class="inst-name">{{ $student->institution->name }}</div>
        <div class="inst-address">{{ $student->institution->address }}</div>
        <div class="report-title">{{ __('reports.transcript') }}</div>
    </div>

    <table class="student-info">
        <tr>
            <td class="label">{{ __('results.student_name') }}</td>
            <td>{{ $student->full_name }}</td>
            <td class="label">{{ __('results.roll_number') }}</td>
            <td>{{ $student->admission_number }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('results.father_name') }}</td>
            <td>{{ $student->guardian_name }}</td>
            <td class="label">{{ __('results.generated_on') }}</td>
            <td>{{ date('d M, Y') }}</td>
        </tr>
    </table>

    @forelse($history as $sessionId => $records)
        @php 
            $sessionName = $records->first()->exam->academicSession->name ?? 'Unknown Session';
        @endphp
        <div class="session-block">
            <div class="session-header">{{ $sessionName }}</div>
            <table class="marks-table">
                <thead>
                    <tr>
                        <th class="text-left">{{ __('results.subject') }}</th>
                        <th>{{ __('results.total_marks') }}</th>
                        <th>{{ __('results.obtained') }}</th>
                        <th>{{ __('results.grade') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $record)
                    <tr>
                        <td class="text-left">{{ $record->subject->name }}</td>
                        {{-- UPDATED: Use the dynamically calculated max marks from the controller logic --}}
                        <td>{{ $record->calculated_max_marks ?? $record->subject->total_marks ?? 100 }}</td>
                        <td>{{ $record->marks_obtained }}</td>
                        <td>
                            @php
                                $max = $record->calculated_max_marks ?? $record->subject->total_marks ?? 100;
                                $percent = ($max > 0) ? ($record->marks_obtained / $max * 100) : 0;
                                $grade = 'F';
                                if ($percent >= 90) $grade = 'A+';
                                elseif ($percent >= 80) $grade = 'A';
                                elseif ($percent >= 70) $grade = 'B';
                                elseif ($percent >= 60) $grade = 'C';
                                elseif ($percent >= 50) $grade = 'D';
                                elseif ($percent >= 40) $grade = 'E';
                            @endphp
                            {{ $grade }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <div style="text-align: center; padding: 50px; color: #777;">
            No academic history found for this student.
        </div>
    @endforelse

    <div class="footer">
        <div style="float: left;">This is a computer-generated document.</div>
        <div class="signature">Registrar / Principal</div>
    </div>

</body>
</html>