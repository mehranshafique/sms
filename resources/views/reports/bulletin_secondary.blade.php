@if(!isset($is_bulk) || !$is_bulk)
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('reports.bulletin_title') }}</title>
    @include('reports.partials.bulletin_css') 
</head>
<body>

    <div class="print-controls" id="printBtnBlock">
        <button onclick="this.style.display='none'; window.print(); setTimeout(() => this.style.display='flex', 2000);" class="print-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            Imprimer
        </button>
    </div>

    <!-- Container wrapper centers the beautiful Single Card view on the screen natively -->
    <div style="display: flex; justify-content: center; width: 100%; min-height: 100vh;">
        <div class="student-column single-card-view">
@else
    <!-- Standard bulk mode column rendering -->
    <div class="student-column" style="float: left; width: 33.33%; height: 210mm;">
@endif

    @php
        $principalName = 'DIRECTION';
        if (isset($student->institution_id)) {
            $adminUser = \App\Models\User::where('institute_id', $student->institution_id)
                            ->where(function($q) {
                                $q->where('user_type', 'school_admin')
                                  ->orWhereHas('roles', function($r) {
                                      $r->where('name', 'School Admin');
                                  });
                            })->first();
            if ($adminUser && !empty($adminUser->name)) {
                $principalName = $adminUser->name;
            }
        }

        $p1_label = ($semester == 1) ? "P1" : "P3";
        $p2_label = ($semester == 1) ? "P2" : "P4";
    @endphp

        <div class="header-content">
            <div class="logo-box">
                @if(isset($student->institution->logo) && $student->institution->logo)
                    <img src="{{ asset('storage/' . $student->institution->logo) }}" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                @else
                    @php $instNameParts = explode(' ', $student->institution->name ?? 'COLLEGE SAINT GABRIEL', 2); @endphp
                    <span style="text-align:center;">{{ strtoupper(substr($instNameParts[0], 0, 10)) }}<br>...</span>
                @endif
            </div>
            <div class="school-name">{{ strtoupper($student->institution->name ?? '') }}</div>
            <div class="student-name">{{ strtoupper($student->first_name . ' ' . $student->last_name) }}</div>
            <div class="class-name">{{ $enrollment->classSection->gradeLevel->name ?? '' }} - {{ $enrollment->classSection->name ?? '' }}</div>
            <div class="barcode"></div>
            <div class="term-title">{{ __('reports.bulletin_title') }} {{ $semester }}{{ $semester == 1 ? 'e' : 'e' }} {{ __('reports.semester') }}</div>
        </div>
        
        <div class="divider-thick"></div>
        <div class="divider-thin"></div>

        <table>
            <thead>
                <tr>
                    <th class="left-align" style="width: 40%;">{{ __('reports.subject') }}</th>
                    <th style="width: 15%;">{{ $p1_label }}</th>
                    <th style="width: 15%;">{{ $p2_label }}</th>
                    <th style="width: 15%;">EXAM</th>
                    <th style="width: 15%;">TOTAL</th>
                </tr>
            </thead>
        </table>
        
        <div class="divider-bottom"></div>

        <table>
            <tbody>
                @php
                    $totalObtained = 0;
                    $totalMax = 0;
                @endphp

                @foreach($data as $row)
                    @php
                        // Safely check for array keys using ?? null to prevent undefined key crashes
                        $p1 = is_numeric($row['p1_score'] ?? null) ? $row['p1_score'] : '-';
                        $p2 = is_numeric($row['p2_score'] ?? null) ? $row['p2_score'] : '-';
                        $ex = is_numeric($row['exam_score'] ?? null) ? $row['exam_score'] : '-';
                        
                        $p1_val = is_numeric($p1) ? $p1 : 0;
                        $p2_val = is_numeric($p2) ? $p2 : 0;
                        $ex_val = is_numeric($ex) ? $ex : 0;
                        
                        // Safely grab total_score if pre-calculated, otherwise sum it up natively
                        $tot = isset($row['total_score']) && is_numeric($row['total_score']) 
                               ? $row['total_score'] 
                               : ($p1_val + $p2_val + $ex_val);
                        
                        $p_max = $row['p_max'] ?? 20;
                        $ex_max = $row['exam_max'] ?? 40;
                        $tot_max = $row['total_max'] ?? ($row['subject']->total_marks ?? 100);
                        
                        $totalObtained += $tot;
                        $totalMax += $tot_max;
                        
                        $isP1Fail = (is_numeric($p1) && $p_max > 0 && $p1 < ($p_max / 2));
                        $isP2Fail = (is_numeric($p2) && $p_max > 0 && $p2 < ($p_max / 2));
                        $isExFail = (is_numeric($ex) && $ex_max > 0 && $ex < ($ex_max / 2));
                        $isTotFail = (is_numeric($tot) && $tot_max > 0 && $tot < ($tot_max / 2));
                    @endphp
                    <tr>
                        <td class="left-align" style="width: 40%;">{{ $row['subject']->name }}</td>
                        <td class="{{ $isP1Fail ? 'fail-grade' : '' }}" style="width: 15%;">
                            {{ $p1 }} <span style="font-size:7px; font-weight:normal; color:#555;">/{{ $p_max }}</span>
                        </td>
                        <td class="{{ $isP2Fail ? 'fail-grade' : '' }}" style="width: 15%;">
                            {{ $p2 }} <span style="font-size:7px; font-weight:normal; color:#555;">/{{ $p_max }}</span>
                        </td>
                        <td class="{{ $isExFail ? 'fail-grade' : '' }}" style="width: 15%;">
                            {{ $ex }} <span style="font-size:7px; font-weight:normal; color:#555;">/{{ $ex_max }}</span>
                        </td>
                        <td class="{{ $isTotFail ? 'fail-grade' : '' }}" style="width: 15%;">
                            {{ $tot }} <span style="font-size:7px; font-weight:normal; color:#555;">/{{ $tot_max }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @php
            $percentage = $totalMax > 0 ? ($totalObtained / $totalMax) * 100 : 0;
            $application = 'F';
            if ($percentage >= 80) $application = 'E';
            elseif ($percentage >= 70) $application = 'TB';
            elseif ($percentage >= 60) $application = 'B';
            elseif ($percentage >= 50) $application = 'AB';
            
            $conduct = !empty($student->conduct) ? $student->conduct : '-';
        @endphp

        <div class="summary-container">
            <div class="summary-row">
                <span class="label">{{ __('reports.maximum_general') }}</span>
                <span class="val">{{ $totalMax }}</span>
                <span class="val">{{ $totalMax }}</span>
            </div>
            <div class="summary-row">
                <span class="label">{{ __('reports.total_obtained') }}</span>
                <span class="val">{{ $ranks['total_score'] ?? $totalObtained }}</span>
                <span class="val">{{ $totalObtained }}</span>
            </div>
            <div class="summary-row">
                <span class="label">{{ __('reports.percentage') }}</span>
                <span class="val">{{ number_format($percentage, 2) }}%</span>
                <span class="val">{{ number_format($percentage, 2) }}%</span>
            </div>
            <div class="summary-row">
                <span class="label">{{ __('reports.conduct') }}</span>
                <span class="val">{{ $conduct }}</span>
                <span class="val">{{ $conduct }}</span>
            </div>
            <div class="summary-row">
                <span class="label">{{ __('reports.application') }}</span>
                <span class="val">{{ $application }}</span>
                <span class="val">{{ $application }}</span>
            </div>
            <div class="summary-row">
                <span class="label">{{ __('reports.place_eff') }}</span>
                @php
                    $grRank = $ranks['grade_rank'] ?? '-';
                    $grTotal = $ranks['grade_total'] ?? '-';
                    $secRank = $ranks['section_rank'] ?? '-';
                    $secTotal = $ranks['section_total'] ?? '-';
                @endphp
                <span class="val">{{ $grRank }}{{ is_numeric($grRank) ? 'e' : '' }} | {{ $grTotal }}</span>
                <span class="val">{{ $secRank }}{{ is_numeric($secRank) ? 'e' : '' }} | {{ $secTotal }}</span>
            </div>
        </div>

        <div class="footer-wrapper" style="position: relative; height: 70px; margin-top: 15px; clear: both; width: 100%;">
            @php $qrData = urlencode("{$student->first_name} {$student->last_name} | ID: {$student->admission_number}"); @endphp
            <div class="qr-code" style="position: absolute; left: 0; bottom: 0; width: 32px; height: 32px; background-color: white; padding: 2px; box-sizing: border-box; background-image: url('https://api.qrserver.com/v1/create-qr-code/?size=64x64&data={{ $qrData }}'); background-size: cover;"></div>

            <div class="stamp-overlay" style="position: absolute; left: 45%; margin-left: -40px; bottom: 0; width: 65px; height: 65px; opacity: 0.95; z-index: 5; pointer-events: none;">
                @php $svgId = isset($loop_index) ? $loop_index . '-' . $student->id : $student->id; @endphp
                <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="50" cy="50" r="48" fill="none" stroke="var(--stamp-blue)" stroke-width="2"/>
                    <circle cx="50" cy="50" r="45" fill="none" stroke="var(--stamp-blue)" stroke-width="1"/>
                    <circle cx="50" cy="50" r="28" fill="none" stroke="var(--stamp-blue)" stroke-width="1" stroke-dasharray="2,2"/>
                    
                    <path id="txt-top-{{ $svgId }}" d="M 18 50 A 32 32 0 0 1 82 50" fill="none"/>
                    
                    <!-- FIXED: Bottom Arc draws Right to Left to keep text Upright -->
                    <path id="txt-bot-{{ $svgId }}" d="M 82 50 A 32 32 0 0 1 18 50" fill="none"/>
                    
                    <text fill="var(--stamp-blue)" font-size="11" font-weight="bold" font-family="Arial" letter-spacing="2">
                        <textPath href="#txt-top-{{ $svgId }}" startOffset="50%" text-anchor="middle">{{ __('reports.bulletin_title') }}</textPath>
                    </text>
                    
                    <text fill="var(--stamp-blue)" font-size="8.5" font-weight="bold" font-family="Arial" letter-spacing="1">
                        <textPath href="#txt-bot-{{ $svgId }}" startOffset="50%" text-anchor="middle">{{ strtoupper(\Illuminate\Support\Str::limit($student->institution->name ?? 'DIRECTION', 18, '')) }}</textPath>
                    </text>

                    <circle cx="12" cy="50" r="2" fill="var(--stamp-blue)"/>
                    <circle cx="88" cy="50" r="2" fill="var(--stamp-blue)"/>
                    <text x="50" y="38" fill="var(--stamp-blue)" font-size="10" text-anchor="middle">★★★</text>
                    
                    <!-- FIXED: Prominent School Logo safely positioned in the center of the stamp -->
                    @if(isset($student->institution->logo) && $student->institution->logo)
                        <image href="{{ asset('storage/' . $student->institution->logo) }}" x="36" y="36" height="28" width="28" preserveAspectRatio="xMidYMid meet"/>
                    @else
                        <circle cx="50" cy="50" r="8" fill="#c49a45"/>
                    @endif
                    <text x="50" y="70" fill="var(--stamp-blue)" font-size="10" text-anchor="middle">★★</text>
                </svg>
            </div>

            <div class="signature-block" style="position: absolute; right: 0; bottom: 0; text-align: center; font-size: 8px; font-weight: bold; line-height: 1.5; color: #000;">
                <div>{{ __('reports.made_in') }} {{ $student->institution->city ?? 'Kinshasa' }}, {{ __('reports.on_date') }} {{ date('d/m/Y') }}</div>
                <div style="margin: 3px 0;">{{ __('reports.principal') }}</div>
                <div>{{ strtoupper($principalName) }}</div>
            </div>
        </div>
    </div> <!-- Close Column -->

@if(!isset($is_bulk) || !$is_bulk)
    </div> <!-- Close Responsive Centered Wrapper -->
</body>
</html>
@endif