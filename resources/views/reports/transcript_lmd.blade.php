<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('reports.transcript') }} - {{ $student->full_name }}</title>
    <style>
        @page { margin: 10mm; }
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
        .logo { max-height: 60px; margin-bottom: 5px; }
        .inst-name { font-size: 16px; font-weight: bold; text-transform: uppercase; }
        .inst-address { font-size: 10px; }
        .report-title { font-size: 14px; font-weight: bold; text-decoration: underline; margin-top: 10px; text-transform: uppercase; }

        .student-info { width: 100%; margin-bottom: 15px; font-size: 11px; }
        .student-info td { padding: 3px 0; }
        .label { font-weight: bold; width: 120px; }

        /* Semester Block */
        .session-block { margin-bottom: 20px; page-break-inside: avoid; }
        .semester-header { 
            background: #e0e0e0; 
            padding: 5px; 
            font-weight: bold; 
            border: 1px solid #000; 
            font-size: 12px; 
            display: flex; 
            justify-content: space-between;
        }
        
        /* Table Styles */
        table.lmd-table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        table.lmd-table th, table.lmd-table td { border: 1px solid #000; padding: 4px; text-align: center; font-size: 10px; }
        table.lmd-table th { background: #f9f9f9; text-transform: uppercase; }
        
        .text-left { text-align: left !important; padding-left: 5px !important; }
        .ue-row { background: #f0f0f0; font-weight: bold; }
        .sub-row td { border-top: 1px dotted #ccc; }
        .ue-code { font-family: monospace; font-size: 9px; }

        /* Status Colors */
        .status-v { color: green; font-weight: bold; }
        .status-nv { color: red; font-weight: bold; }
        .status-cmp { color: blue; font-weight: bold; }

        /* Summary Box */
        .summary-box { 
            border: 2px solid #000; 
            padding: 10px; 
            margin-top: 10px; 
            font-weight: bold; 
            font-size: 11px;
            background: #fff;
        }
        
        .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 9px; text-align: center; border-top: 1px solid #ccc; padding-top: 5px; }
    </style>
</head>
<body>

    <div class="header">
        <div class="inst-name">{{ $student->institution->name }}</div>
        <div class="inst-address">{{ $student->institution->address }}</div>
        <div class="report-title">{{ __('reports.academic_transcript') }} (LMD)</div>
    </div>

    <table class="student-info">
        <tr>
            <td class="label">{{ __('student.name') }}:</td>
            <td>{{ strtoupper($student->full_name) }}</td>
            <td class="label">{{ __('student.admission_no') }}:</td>
            <td>{{ $student->admission_number }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('grade_level.grade_name') }}:</td>
            <td>{{ $student->gradeLevel->name ?? '-' }}</td>
            <td class="label">{{ __('reports.date') }}:</td>
            <td>{{ date('d M, Y') }}</td>
        </tr>
    </table>

    @if(empty($history))
        <div style="text-align:center; padding: 20px; border:1px dashed #ccc;">
            {{ __('reports.no_records_found') }}
        </div>
    @else
        @foreach($history as $sessionName => $semesters)
            <div style="font-size: 14px; font-weight: bold; margin-bottom: 5px; text-decoration: underline;">
                {{ __('academic_session.session') }}: {{ $sessionName }}
            </div>

            @foreach($semesters as $semName => $data)
                <div class="session-block">
                    <div class="semester-header">
                        <span>{{ __('reports.semester') }} {{ $data['semester'] }}</span>
                    </div>
                    
                    <table class="lmd-table">
                        <thead>
                            <tr>
                                <th class="text-left" width="45%">{{ __('lmd.ue_title') }} / {{ __('lmd.ec_title') }}</th>
                                <th width="10%">{{ __('lmd.credits') }}</th>
                                <th width="10%">Coeff</th>
                                <th width="10%">{{ __('reports.marks_obtained') }} / 20</th>
                                <th width="10%">{{ __('reports.grade') }}</th>
                                <th width="15%">{{ __('reports.decision') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['units'] as $ue)
                                {{-- UE Header Row --}}
                                <tr class="ue-row">
                                    <td class="text-left">
                                        <span class="ue-code">{{ $ue['unit_code'] }}</span> - {{ $ue['unit_name'] }}
                                    </td>
                                    <td>{{ $ue['total_credits'] }}</td>
                                    <td>-</td>
                                    <td>{{ number_format($ue['average'], 2) }}</td>
                                    <td>
                                        {{-- Simple Grade Logic for UE --}}
                                        @php
                                            $avg = $ue['average'];
                                            if($avg >= 16) echo 'A';
                                            elseif($avg >= 14) echo 'B';
                                            elseif($avg >= 12) echo 'C';
                                            elseif($avg >= 10) echo 'D';
                                            else echo 'E';
                                        @endphp
                                    </td>
                                    <td>
                                        @if($ue['status'] == 'V') <span class="status-v">{{ __('lmd.validated') }}</span>
                                        @elseif($ue['status'] == 'Cmp') <span class="status-cmp">{{ __('lmd.compensated') }}</span>
                                        @else <span class="status-nv">{{ __('lmd.failed') }}</span>
                                        @endif
                                    </td>
                                </tr>
                                
                                {{-- Subject (EC) Rows --}}
                                @foreach($ue['subjects'] as $sub)
                                    <tr class="sub-row">
                                        <td class="text-left" style="padding-left: 20px;">
                                            • {{ $sub['name'] }}
                                        </td>
                                        <td>{{ $sub['credits'] }}</td>
                                        <td>{{ $sub['coefficient'] }}</td>
                                        <td>{{ number_format($sub['normalized'], 2) }}</td>
                                        <td>-</td>
                                        <td>-</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>

                    <div class="summary-box">
                        <table width="100%">
                            <tr>
                                <td>{{ __('lmd.credits_attempted') }}: {{ $data['credits_attempted'] }}</td>
                                <td>{{ __('lmd.credits_earned') }}: {{ $data['credits_earned'] }}</td>
                                <td>{{ __('lmd.average') }}: {{ $data['average'] }}/20</td>
                                <td>{{ __('lmd.mention') }}: {{ $data['mention'] }}</td>
                                <td align="right">{{ __('reports.decision') }}: <strong>{{ $data['decision'] }}</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            @endforeach
        @endforeach
    @endif

    <div class="footer">
        {{ __('results.computer_generated') }} | Generated on {{ now()->format('d M, Y h:i A') }}
    </div>

</body>
</html>