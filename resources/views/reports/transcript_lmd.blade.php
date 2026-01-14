<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>LMD Transcript - {{ $student->full_name }}</title>
    <style>
        @page { margin: 15px; }
        body { font-family: 'Times New Roman', serif; font-size: 11px; }
        .header { text-align: center; text-transform: uppercase; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
        
        .title-block { text-align: center; font-weight: bold; margin: 15px 0; }
        .semester-label { font-size: 14px; background: #ddd; padding: 5px; display: inline-block; }

        .table-lmd { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table-lmd th, .table-lmd td { border: 1px solid #000; padding: 4px; text-align: center; }
        .table-lmd th { background: #f0f0f0; text-transform: uppercase; font-size: 9px; }
        .text-left { text-align: left !important; }

        .summary-block { margin-top: 20px; border-top: 1px dashed #000; padding-top: 10px; line-height: 1.8; }
        .mention-box { font-size: 16px; font-weight: bold; border: 2px solid #000; padding: 5px 15px; display: inline-block; margin-top: 10px; }
        
        .footer-note { font-size: 9px; margin-top: 40px; color: #555; }
    </style>
</head>
<body>

    <div class="header">
        <div style="font-size: 16px; font-weight: bold;">{{ $student->institution->name }}</div>
        <div>{{ $student->institution->address }}</div>
        <div style="margin-top: 5px;">{{ __('reports.academic_transcript') }} - LMD Model</div>
    </div>

    <div class="title-block">
        <div class="semester-label">{{ __('reports.semester') }} {{ $semester ?? 'GLOBAL' }}</div>
    </div>

    <table style="width: 100%; margin-bottom: 10px;">
        <tr>
            <td style="width: 60%;">
                <strong>{{ __('student.name') }}:</strong> {{ strtoupper($student->full_name) }}<br>
                <strong>{{ __('student.id') }}:</strong> {{ $student->admission_number }}
            </td>
            <td style="width: 40%; text-align: right;">
                <strong>{{ __('reports.session') }}:</strong> {{ $enrollment->academicSession->name }}<br>
                <strong>{{ __('reports.date') }}:</strong> {{ date('d/m/Y') }}
            </td>
        </tr>
    </table>

    <table class="table-lmd">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th class="text-left" style="width: 40%;">{{ __('reports.matieres') }}</th>
                <th>Vol. Horaire</th>
                <th>{{ __('reports.cotes') }}</th>
                <th>Max</th>
                <th>{{ __('reports.credit') }}</th>
                <th>{{ __('reports.credit_valide') }}</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $totalCotes = 0; $totalMax = 0; $totalCredits = 0; $totalValidatedCredits = 0;
            @endphp
            @foreach($records as $index => $record)
                @php
                    $isValide = ($record->marks_obtained / ($record->subject->total_marks ?: 20)) * 100 >= $threshold;
                    $totalCotes += $record->marks_obtained;
                    $totalMax += ($record->subject->total_marks ?: 20);
                    $totalCredits += $record->subject->credit_hours;
                    if($isValide) $totalValidatedCredits += $record->subject->credit_hours;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="text-left">{{ $record->subject->name }}</td>
                    <td>{{ $record->subject->credit_hours * 15 }} H</td> {{-- Example: Credit * 15h --}}
                    <td class="{{ !$isValide ? 'fail' : '' }}">{{ $record->marks_obtained }}</td>
                    <td>{{ $record->subject->total_marks ?: 20 }}</td>
                    <td>{{ $record->subject->credit_hours }}</td>
                    <td style="font-weight: bold;">{{ $isValide ? 'V' : 'NV' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background: #f9f9f9;">
                <td colspan="3" class="text-left">TOTAL</td>
                <td>{{ $totalCotes }}</td>
                <td>{{ $totalMax }}</td>
                <td>{{ $totalCredits }}</td>
                <td>{{ $totalValidatedCredits }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="summary-block">
        <div>TOTAL GENERAL: <strong>{{ $totalCotes }}/{{ $totalMax }}</strong></div>
        <div>TOTAL GENERAL CREDIT: <strong>{{ $totalValidatedCredits }}/{{ $totalCredits }}</strong></div>
        <div>POURCENTAGE: <strong>{{ number_format(($totalCotes / max(1, $totalMax)) * 100, 2) }}%</strong></div>
        
        @php
            $percentage = ($totalCotes / max(1, $totalMax)) * 100;
            $mention = 'NA';
            foreach($gradingScale as $scale) {
                if($percentage >= $scale['min']) {
                    $mention = $scale['grade'];
                    break;
                }
            }
        @endphp
        
        <div class="mention-box">MENTION: {{ $mention }}</div>
    </div>

    <div class="footer-note">
        * LH: Volume Horaire (Lectures Hours) | V: Validé | NV: Non Validé<br>
        {{ __('results.computer_generated') }}
    </div>

</body>
</html>