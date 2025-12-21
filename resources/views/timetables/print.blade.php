<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable Print</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        body { font-family: 'Helvetica', sans-serif; color: #333; font-size: 12px; background: #fff; }
        .container { width: 100%; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; }
        .header h3 { margin: 5px 0; }
        .header p { margin: 2px 0; font-size: 14px; color: #555; }
        
        .routine-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .routine-table th, .routine-table td { border: 1px solid #999; padding: 8px; text-align: center; vertical-align: top; }
        .routine-table th { background-color: #f2f2f2; font-weight: bold; text-transform: uppercase; font-size: 13px; }
        .day-col { background-color: #f9f9f9; font-weight: bold; width: 120px; text-transform: uppercase; text-align: center; vertical-align: middle; }
        
        .slot-box { 
            display: inline-block; 
            width: 23%; /* Approx 4 per row */
            margin: 0.5%;
            vertical-align: top; 
            border: 1px solid #ccc; 
            padding: 6px; 
            border-radius: 4px; 
            background: #fff;
            text-align: left;
            page-break-inside: avoid;
        }
        .time { font-weight: bold; color: #000; display: block; margin-bottom: 2px; font-size: 11px; border-bottom: 1px dashed #ddd; padding-bottom: 2px; }
        .subject { font-weight: bold; font-size: 12px; display: block; margin: 2px 0; }
        .teacher { color: #444; font-size: 11px; display: block; }
        .room { font-style: italic; color: #666; font-size: 10px; margin-top: 2px; display: block;}

        .no-classes { color: #999; font-style: italic; padding: 10px; }

        /* Hide elements when printing */
        @media print {
            .no-print { display: none; }
            body { -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
{{-- FIX: Added onload to trigger print dialog --}}
<body onload="window.print()">
    <div class="container">
        <div class="header">
            <h1>{{ $timetable->institution->name ?? 'Institution Name' }}</h1>
            <p>{{ $timetable->institution->address ?? '' }}</p>
            <h3>{{ $headerTitle ?? 'Class Routine' }}</h3>
            <p>{{ $timetable->academicSession->name ?? 'Academic Session' }}</p>
        </div>

        <table class="routine-table">
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Schedule</th>
                </tr>
            </thead>
            <tbody>
                @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day)
                    <tr>
                        <td class="day-col">{{ ucfirst($day) }}</td>
                        <td style="text-align: left; padding: 10px;">
                            @if(isset($weeklySchedule[$day]) && $weeklySchedule[$day]->count() > 0)
                                @foreach($weeklySchedule[$day] as $slot)
                                    <div class="slot-box">
                                        <span class="time">{{ $slot->start_time->format('H:i') }} - {{ $slot->end_time->format('H:i') }}</span>
                                        <span class="subject">{{ $slot->subject->name }}</span>
                                        <span class="teacher">{{ $slot->teacher->user->name ?? 'N/A' }}</span>
                                        @if($slot->room_number)
                                            <span class="room">Rm: {{ $slot->room_number }}</span>
                                        @endif
                                        
                                        {{-- If viewing by teacher/room, show class name --}}
                                        @if(isset($headerTitle) && !str_contains($headerTitle, $slot->classSection->name))
                                             <span class="teacher" style="color: #007bff">Class: {{ $slot->classSection->name }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <div class="no-classes">- No Classes -</div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #777; border-top: 1px solid #eee; padding-top: 10px;">
            Generated on {{ date('d M, Y h:i A') }}
        </div>
    </div>
</body>
</html>