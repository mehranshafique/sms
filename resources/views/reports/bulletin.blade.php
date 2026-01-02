<!DOCTYPE html>
<html>
<head>
    <title>{{ __('reports.student_bulletin') }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page {
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Helvetica', sans-serif;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
            font-size: 12px;
        }
        .container {
            width: 100%;
            height: 100vh; /* Full viewport height */
            box-sizing: border-box;
            background-color: #fdf5e6;
            border: 2px solid #d2b48c;
            padding: 20px;
            position: relative;
        }
        
        /* Header Section */
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px dashed #d2b48c;
            padding-bottom: 10px;
        }
        .logo-img {
            max-height: 80px;
            margin-bottom: 10px;
        }
        .school-name {
            font-size: 24px;
            font-weight: bold;
            color: #8b4513;
            text-transform: uppercase;
            margin: 5px 0;
        }
        .student-name {
            font-size: 20px;
            font-weight: bold;
            margin: 10px 0;
            text-transform: uppercase;
            color: #333;
        }
        .class-info {
            font-size: 14px;
            margin: 5px 0;
            color: #555;
            font-weight: bold;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            color: #8b4513;
            margin-top: 10px;
            text-decoration: underline;
        }
        
        /* Real Barcode from Controller/API */
        .barcode-container {
            margin: 10px auto;
            text-align: center;
            width: 100%;
        }
        .barcode-img {
            height: 35px;
            width: auto;
            display: block;
            margin: 0 auto;
        }

        /* Tables */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .table th, .table td {
            border: 1px solid #d2b48c;
            padding: 6px;
            text-align: left;
        }
        .table th {
            background-color: #f5deb3;
            color: #5a3a22;
            text-transform: uppercase;
            font-weight: bold;
        }
        .table td:nth-child(2), .table td:nth-child(3) {
            text-align: center;
            font-weight: bold;
            width: 15%;
        }
        .table td:first-child {
            width: 70%;
        }
        
        /* Summary Section */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        .summary-table td {
            border: 1px solid #d2b48c;
            padding: 6px;
            font-weight: bold;
        }
        .summary-table td:nth-child(even) {
            text-align: center;
            background-color: #fff8dc;
            color: #333;
        }
        .summary-label {
            background-color: #faebd7;
            color: #8b4513;
            width: 40%;
        }

        /* Footer & Stamp Area */
        .footer {
            margin-top: 30px;
            position: relative;
            height: 150px; /* Space for signature and stamp */
        }
        .date-line {
            text-align: left;
            font-style: italic;
            margin-bottom: 20px;
        }
        
        /* Signature Box (Right) */
        .signature-box {
            position: absolute;
            right: 0;
            top: 40px;
            text-align: center;
            width: 250px;
            z-index: 2;
        }
        .signature-title {
            font-weight: bold;
            border-bottom: 1px solid #8b4513;
            padding-bottom: 5px;
            margin-bottom: 10px;
            display: inline-block;
        }

        /* QR Code (Left) */
        .qr-code-container {
            position: absolute;
            left: 0;
            bottom: 0;
            width: 80px;
            height: 80px;
            border: 1px solid #d2b48c;
            padding: 2px;
            background: #fff;
            z-index: 2;
        }
        .qr-code-img {
            width: 100%;
            height: 100%;
        }

        /* Circular Stamp (Centered) */
        .stamp-container {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 140px;
            height: 140px;
            border: 3px double #4682b4; /* Steel Blue */
            border-radius: 50%;
            color: #4682b4;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.8;
            background: rgba(255, 255, 255, 0.1);
            z-index: 1; /* Behind signature if they overlap */
        }
        .stamp-inner {
            width: 100%;
            height: 100%;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .stamp-text-top {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            position: absolute;
            top: 15px;
            width: 80%;
            text-align: center;
        }
        .stamp-logo {
            font-size: 24px;
            font-weight: bold;
            margin: 5px 0;
        }
        .stamp-text-bottom {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            position: absolute;
            bottom: 15px;
        }
    </style>
</head>
<body>

    <div class="container">
        {{-- HEADER --}}
        <div class="header">
            @if($student->institution->logo)
                <img src="{{ public_path('storage/'.$student->institution->logo) }}" class="logo-img">
            @endif
            <div class="school-name">{{ $student->institution->name }}</div>
            
            <div class="student-name">{{ $student->full_name }}</div>
            
            @php
                $className = $records->first()->classSection->name ?? 'N/A';
                if(isset($records->first()->classSection->gradeLevel->name)) {
                    $className .= ' - ' . $records->first()->classSection->gradeLevel->name;
                }
            @endphp
            <div class="class-info">{{ $className }}</div>
            
            {{-- Real Barcode (Code 128) --}}
            <div class="barcode-container">
                <img src="https://bwipjs-api.metafloor.com/?bcid=code128&text={{ $student->admission_number }}&scale=2&height=8&includetext" 
                     class="barcode-img" 
                     alt="Barcode">
            </div>
            
            <div class="report-title">{{ __('reports.bulletin_title') }} - {{ $exam->name }}</div>
        </div>

        {{-- MARKS TABLE --}}
        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('reports.subject') }}</th>
                    <th>{{ __('reports.marks_obtained') }}</th>
                    <th>{{ __('reports.max_marks') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $record)
                <tr>
                    <td>{{ $record->subject->name }}</td>
                    <td style="color: {{ $record->marks_obtained < ($record->subject->total_marks/2) ? 'red' : 'black' }}">
                        {{ $record->marks_obtained }}
                    </td>
                    <td>{{ $record->subject->total_marks ?? 100 }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- SUMMARY GRID --}}
        <table class="summary-table">
            <tr>
                <td class="summary-label">{{ __('reports.max_general') }}</td>
                <td>{{ $summary['total_marks'] }}</td>
            </tr>
            <tr>
                <td class="summary-label">{{ __('reports.total_obtained') }}</td>
                <td>{{ $summary['obtained_marks'] }}</td>
            </tr>
            <tr>
                <td class="summary-label">{{ __('reports.percentage') }}</td>
                <td>{{ $summary['percentage'] }}%</td>
            </tr>
            <tr>
                <td class="summary-label">{{ __('reports.conduct') }}</td>
                <td>{{ $summary['grade'] }}</td>
            </tr>
            <tr>
                <td class="summary-label">{{ __('reports.place_eff') }}</td>
                <td>- / {{ $attendance['total_students'] ?? '-' }}</td>
            </tr>
        </table>

        {{-- FOOTER & STAMP --}}
        <div class="footer">
            <div class="date-line">
                {{ __('reports.done_at', ['city' => $student->institution->city ?? 'City', 'date' => date('d/m/Y')]) }}
            </div>

            {{-- Real QR Code --}}
            <div class="qr-code-container">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode(route('students.show', $student->id)) }}" 
                     class="qr-code-img" 
                     alt="QR Code">
            </div>

            {{-- Circular Stamp (Centered) --}}
            <div class="stamp-container">
                <div class="stamp-inner">
                    <div class="stamp-text-top">{{ $student->institution->name }}</div>
                    <div class="stamp-logo">OFFICIAL</div>
                    <div class="stamp-text-bottom">{{ date('Y') }}</div>
                </div>
            </div>

            {{-- Principal Signature --}}
            <div class="signature-box">
                <div class="signature-title">{{ __('reports.principal') }}</div>
                <div style="font-weight: bold; margin-top: 30px;">
                    {{ $student->institution->principal_name ?? 'Principal Signature' }}
                </div>
            </div>
        </div>
    </div>

</body>
</html>