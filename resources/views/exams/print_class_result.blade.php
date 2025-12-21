<!DOCTYPE html>
<html>
<head>
    <title>{{ __('exam.result_sheet') }} - {{ $classSection->name }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: center; }
        th { background-color: #f0f0f0; }
        .text-left { text-align: left; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2, .header h3 { margin: 2px; }
        .fail { color: red; font-weight: bold; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()">{{ __('exam.print') }}</button>
        <a href="{{ url()->full() . '&download=true' }}">{{ __('exam.download_pdf') }}</a>
    </div>

    <div class="header">
        <h2>{{ $exam->institution->name }}</h2>
        <h3>{{ $exam->name }}</h3>
        <p>{{ __('exam.class') }}: {{ $classSection->name }} | {{ __('exam.session') }}: {{ $exam->academicSession->name }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-left">{{ __('exam.student') }}</th>
                @foreach($subjects as $subject)
                    <th>{{ $subject->code ?? substr($subject->name, 0, 3) }}</th>
                @endforeach
                <th>{{ __('exam.total') }}</th>
                <th>{{ __('exam.average') }}</th>
                <th>{{ __('exam.rank') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $studentId => $studentRecords)
                @php
                    $student = $studentRecords->first()->student;
                    $total = $studentRecords->sum('marks_obtained');
                    $count = $subjects->count();
                    $avg = $count > 0 ? $total / $count : 0;
                @endphp
                <tr>
                    <td class="text-left">{{ $student->full_name }}</td>
                    @foreach($subjects as $subject)
                        @php
                            $mark = $studentRecords->firstWhere('subject_id', $subject->id);
                        @endphp
                        <td>
                            @if($mark)
                                <span class="{{ $mark->marks_obtained < $subject->passing_marks ? 'fail' : '' }}">
                                    {{ $mark->is_absent ? __('exam.absent_short') : $mark->marks_obtained }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                    @endforeach
                    <td>{{ $total }}</td>
                    <td>{{ number_format($avg, 1) }}</td>
                    <td></td> 
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 40px; text-align: right; padding-right: 50px;">
        <p>__________________________</p>
        <p>{{ __('exam.authorized_signature') }}</p>
    </div>
    
    <div style="margin-top: 20px; font-size: 10px; color: #888; text-align: center;">
        {{ __('exam.generated_on') }}: {{ now()->format('d M, Y h:i A') }}
    </div>
</body>
</html>