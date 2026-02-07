<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Transcript - {{ $student->full_name }}</title>
    <style>
        @page { margin: 10mm; }
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; }
        .header { text-align: center; border-bottom: 2px solid #000; margin-bottom: 10px; padding-bottom: 5px; }
        .session-block { margin-bottom: 15px; page-break-inside: avoid; }
        .sem-title { background: #eee; padding: 5px; font-weight: bold; border: 1px solid #000; margin-top: 5px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        th, td { border: 1px solid #000; padding: 4px; text-align: center; }
        th { background: #f9f9f9; }
        .text-left { text-align: left !important; }
        .ue-row { background: #f0f0f0; font-weight: bold; }
        .sub-row td { border-top: 1px dotted #ccc; }
        
        .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 9px; text-align: center; }
        .status-v { color: green; font-weight: bold; }
        .status-nv { color: red; font-weight: bold; }
        .status-cmp { color: blue; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $student->institution->name }}</h2>
        <h3>Relevé de Notes (Transcript of Records)</h3>
        <p>Student: <strong>{{ strtoupper($student->full_name) }}</strong> | ID: {{ $student->admission_number }}</p>
    </div>

    @foreach($history as $sessionName => $semesters)
        <div class="session-block">
            <h4 style="margin: 0; padding: 5px; background: #333; color: #fff;">Year: {{ $sessionName }}</h4>
            
            @foreach($semesters as $semName => $data)
                <div class="sem-title">{{ $semName }} (Avg: {{ $data['average'] }}/20) - {{ $data['decision'] }}</div>
                
                <table>
                    <thead>
                        <tr>
                            <th class="text-left" width="40%">UE / Subjects</th>
                            <th width="10%">Credits</th>
                            <th width="10%">Coeff</th>
                            <th width="10%">Grade/20</th>
                            <th width="15%">Validation</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['units'] as $ue)
                            {{-- UE Header Row --}}
                            <tr class="ue-row">
                                <td class="text-left">{{ $ue['unit_code'] }} - {{ $ue['unit_name'] }}</td>
                                <td>{{ $ue['total_credits'] }}</td>
                                <td>-</td>
                                <td>{{ number_format($ue['average'], 2) }}</td>
                                <td>
                                    @if($ue['status'] == 'V') <span class="status-v">Validé</span>
                                    @elseif($ue['status'] == 'Cmp') <span class="status-cmp">Compensé</span>
                                    @else <span class="status-nv">Non Validé</span>
                                    @endif
                                </td>
                            </tr>
                            {{-- Subject Rows --}}
                            @foreach($ue['subjects'] as $sub)
                                <tr class="sub-row">
                                    <td class="text-left" style="padding-left: 20px;">- {{ $sub['name'] }}</td>
                                    <td>{{ $sub['credits'] }}</td>
                                    <td>{{ $sub['coefficient'] }}</td>
                                    <td>{{ number_format($sub['normalized'], 2) }}</td>
                                    <td>-</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background: #e0e0e0; font-weight: bold;">
                            <td class="text-left">TOTAL SEMESTER</td>
                            <td>{{ $data['credits_attempted'] }}</td>
                            <td>-</td>
                            <td>{{ $data['average'] }}</td>
                            <td>Earned: {{ $data['credits_earned'] }}</td>
                        </tr>
                    </tfoot>
                </table>
            @endforeach
        </div>
    @endforeach

    <div class="footer">
        Generated on {{ date('d M Y') }}. Grading Scale: 0-20. Pass mark: 10/20.
    </div>
</body>
</html>