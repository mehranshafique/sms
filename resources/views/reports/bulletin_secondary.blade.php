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
    <div class="single-card-page">
        <div class="student-column single-card-view">
@else
    <div class="student-column">
@endif

    <div class="card-inner">
    @php
        $labels = $column_labels ?? [];
        $subjectCount = collect($data)->where('has_marks', true)->count();
        $densityClass = $subjectCount > 16 ? 'density-high' : ($subjectCount > 12 ? 'density-medium' : 'density-low');
        $principalName = __('reports.direction');
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

        $p1_label = $labels['p1'] ?? (($semester == 1) ? 'P1' : 'P3');
        $p2_label = $labels['p2'] ?? (($semester == 1) ? 'P2' : 'P4');
    @endphp

        <div class="header-content">
            @include('reports.partials.epst_header', ['student' => $student])
            <div class="logo-box">
                @if(isset($student->institution->logo) && $student->institution->logo)
                    <img src="{{ asset('storage/' . $student->institution->logo) }}" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                @else
                    @php $instNameParts = explode(' ', $student->institution->name ?? __('reports.direction'), 2); @endphp
                    <span style="text-align:center;">{{ strtoupper(substr($instNameParts[0], 0, 10)) }}<br>...</span>
                @endif
            </div>
            <div class="school-name">{{ strtoupper($student->institution->name ?? '') }}</div>
            <div class="student-name">{{ strtoupper($student->first_name . ' ' . $student->last_name) }}</div>
            <div class="class-name">{{ $enrollment->classSection->gradeLevel->name ?? '' }} - {{ $enrollment->classSection->name ?? '' }}</div>
            <div class="barcode"></div>
            <div class="term-title-bar">{{ $term_title ?? (__('reports.bulletin_title') . ' ' . ($semester ?? 1) . ' ' . __('reports.semester')) }}</div>
        </div>

        <div class="divider-thick"></div>
        <div class="divider-thin"></div>

        <div class="subjects-table-wrap {{ $densityClass }}">
        <table>
            <thead>
                <tr>
                    <th class="left-align">{{ $labels['subject'] ?? __('reports.subject') }}</th>
                    <th>{{ $p1_label }}</th>
                    <th>{{ $p2_label }}</th>
                    <th>{{ $labels['p_max'] ?? __('reports.max_marks') }}</th>
                    <th>{{ $labels['exam'] ?? __('reports.exam') }}</th>
                    <th>{{ $labels['exam_max'] ?? __('reports.max_marks') }}</th>
                    <th>{{ $labels['total'] ?? __('reports.total') }}</th>
                    <th>{{ $labels['total_max'] ?? __('reports.t_max') }}</th>
                </tr>
            </thead>
        </table>

        <div class="divider-bottom"></div>

        <table>
            <tbody>
                @php
                    $sum_p_obt = 0;
                    $sum_tot_obt = 0;
                    
                    $sum_p_max_actual = 0;
                    $sum_tot_max = 0;
                @endphp

                @foreach($data as $row)
                    @if($row['has_marks'])
                        @php
                            // Cast directly to (float) to completely remove .00 and decimal artifacts
                            $p1 = is_numeric($row['p1_score'] ?? null) ? (float)$row['p1_score'] : '-';
                            $p2 = is_numeric($row['p2_score'] ?? null) ? (float)$row['p2_score'] : '-';
                            $ex = is_numeric($row['exam_score'] ?? null) ? (float)$row['exam_score'] : '-';
                            
                            $p1_val = is_numeric($p1) ? $p1 : 0;
                            $p2_val = is_numeric($p2) ? $p2 : 0;
                            $ex_val = is_numeric($ex) ? $ex : 0;
                            
                            $tot = isset($row['total_score']) && is_numeric($row['total_score']) 
                                ? (float)$row['total_score'] 
                                : ($p1_val + $p2_val + $ex_val);
                            
                            $p1_max = $row['p1_max'] ?? 0;
                            $p2_max = $row['p2_max'] ?? 0;
                            $p_max_display = ($p1_max > 0 && $p2_max > 0 && $p1_max != $p2_max)
                                ? ($p1_max + $p2_max)
                                : max($p1_max, $p2_max);
                            $ex_max = $row['exam_max'] ?? 0;
                            $tot_max = $row['total_max'] ?? 0;

                            $sum_p_obt += ($p1_val + $p2_val);
                            $sum_tot_obt += $tot;
                            $sum_p_max_actual += ($p1_max + $p2_max);
                            $sum_tot_max += $tot_max;

                            $failThreshold = fn($score, $max) => is_numeric($score) && $max > 0 && ($score < ($max / 2) || ($max == 20 && $score < 10));
                            $isP1Fail = $failThreshold($p1, $p1_max);
                            $isP2Fail = $failThreshold($p2, $p2_max);
                            $isExFail = $failThreshold($ex, $ex_max);
                            $isTotFail = $failThreshold($tot, $tot_max);
                        @endphp
                        <tr>
                            <td class="left-align subject-name">{{ $row['subject']->name }}</td>
                            <td class="{{ $isP1Fail ? 'fail-grade' : '' }}">{{ $p1 }}</td>
                            <td class="{{ $isP2Fail ? 'fail-grade' : '' }}">{{ $p2 }}</td>
                            <td>{{ $p_max_display > 0 ? $p_max_display : '-' }}</td>
                            <td class="{{ $isExFail ? 'fail-grade' : '' }}">{{ $ex }}</td>
                            <td>{{ $ex_max > 0 ? $ex_max : '-' }}</td>
                            <td class="{{ $isTotFail ? 'fail-grade' : '' }}">{{ $tot }}</td>
                            <td>{{ $tot_max > 0 ? $tot_max : '-' }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
        </div>

        @php
            $percentagePeriod = $sum_p_max_actual > 0 ? ($sum_p_obt / $sum_p_max_actual) * 100 : 0;
            $percentageTotal = $sum_tot_max > 0 ? ($sum_tot_obt / $sum_tot_max) * 100 : 0;
            
            $application = 'F';
            if ($percentageTotal >= 80) $application = 'E';
            elseif ($percentageTotal >= 70) $application = 'TB';
            elseif ($percentageTotal >= 60) $application = 'B';
            elseif ($percentageTotal >= 50) $application = 'AB';
            
            $conduct = !empty($student->conduct) ? $student->conduct : '-';
        @endphp

        <div class="summary-container">
            <div class="summary-row">
                <span class="label">{{ __('reports.maximum_general') ?? 'MAXIMUM GENERAL' }}</span>
                <span class="val">{{ $sum_p_max_actual > 0 ? $sum_p_max_actual : 0 }}</span>
                <span class="val">{{ $sum_tot_max > 0 ? $sum_tot_max : 0 }}</span>
            </div>
            <div class="summary-row">
                <span class="label">{{ __('reports.total_obtained') ?? 'TOTAL OBTENU' }}</span>
                <span class="val">{{ $sum_p_obt }}</span>
                <span class="val">{{ $sum_tot_obt }}</span>
            </div>
            <div class="summary-row">
                <span class="label">{{ __('reports.percentage') ?? 'POURCENTAGE' }}</span>
                <span class="val">{{ number_format($percentagePeriod, 2) }}%</span>
                <span class="val">{{ number_format($percentageTotal, 2) }}%</span>
            </div>
            <div class="summary-row">
                <span class="label">{{ __('reports.mention') ?? 'MENTION' }}</span>
                <span class="val" colspan="2">{{ $mention ?? '—' }}</span>
            </div>
            <div class="summary-row">
                <span class="label">{{ __('reports.conduct') ?? 'CONDUITE' }}</span>
                <span class="val">{{ $conduct }}</span>
                <span class="val">{{ $conduct }}</span>
            </div>
            <div class="summary-row">
                <span class="label">{{ __('reports.application') ?? 'APPLICATION' }}</span>
                <span class="val">{{ $application }}</span>
                <span class="val">{{ $application }}</span>
            </div>
            <div class="summary-row">
                <span class="label">{{ __('reports.place_eff') ?? 'PLACE - EFF' }}</span>
                @php
                    // Display specific Section Rank and Total for exact class size matching
                    $secRank = $ranks['section_rank'] ?? '-';
                    $secTotal = $ranks['section_total'] ?? '-';
                @endphp
                <span class="val">{{ $secRank }}{{ is_numeric($secRank) ? 'e' : '' }} | {{ $secTotal }}</span>
                <span class="val">{{ $secRank }}{{ is_numeric($secRank) ? 'e' : '' }} | {{ $secTotal }}</span>
            </div>
        </div>

        <div class="footer-wrapper">
            @php $qrData = urlencode("{$student->first_name} {$student->last_name} | ID: {$student->admission_number}"); @endphp
            <div class="qr-code" style="background-image: url('https://api.qrserver.com/v1/create-qr-code/?size=64x64&data={{ $qrData }}');"></div>

            <div class="stamp-overlay">
                @php $svgId = isset($loop_index) ? $loop_index . '-' . $student->id : $student->id; @endphp
                <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="50" cy="50" r="48" fill="none" stroke="var(--stamp-blue)" stroke-width="2"/>
                    <circle cx="50" cy="50" r="45" fill="none" stroke="var(--stamp-blue)" stroke-width="1"/>
                    <circle cx="50" cy="50" r="28" fill="none" stroke="var(--stamp-blue)" stroke-width="1" stroke-dasharray="2,2"/>
                    
                    <path id="txt-top-{{ $svgId }}" d="M 18 50 A 32 32 0 0 1 82 50" fill="none"/>
                    
                    <path id="txt-bot-{{ $svgId }}" d="M 82 50 A 32 32 0 0 1 18 50" fill="none"/>
                    
                    <text fill="var(--stamp-blue)" font-size="11" font-weight="bold" font-family="Arial" letter-spacing="2">
                        <textPath href="#txt-top-{{ $svgId }}" startOffset="50%" text-anchor="middle">{{ __('reports.bulletin_title') }}</textPath>
                    </text>
                    
                    <text fill="var(--stamp-blue)" font-size="8.5" font-weight="bold" font-family="Arial" letter-spacing="1">
                        <textPath href="#txt-bot-{{ $svgId }}" startOffset="50%" text-anchor="middle">{{ strtoupper(\Illuminate\Support\Str::limit($student->institution->name ?? __('reports.direction'), 18, '')) }}</textPath>
                    </text>

                    <circle cx="12" cy="50" r="2" fill="var(--stamp-blue)"/>
                    <circle cx="88" cy="50" r="2" fill="var(--stamp-blue)"/>
                    <text x="50" y="38" fill="var(--stamp-blue)" font-size="10" text-anchor="middle">★★★</text>
                    
                    @if(isset($student->institution->logo) && $student->institution->logo)
                        <image href="{{ asset('storage/' . $student->institution->logo) }}" x="36" y="36" height="28" width="28" preserveAspectRatio="xMidYMid meet"/>
                    @else
                        <circle cx="50" cy="50" r="8" fill="#c49a45"/>
                    @endif
                    <text x="50" y="70" fill="var(--stamp-blue)" font-size="10" text-anchor="middle">★★</text>
                </svg>
            </div>

            <div class="signature-block">
                <div>{{ __('reports.made_in') }} {{ $student->institution->city ?? 'Kinshasa' }}, {{ __('reports.on_date') }} {{ date('d/m/Y') }}</div>
                <div style="margin: 2px 0;">{{ __('reports.principal') }}</div>
                <div>{{ strtoupper($principalName) }}</div>
            </div>
        </div>
    </div>
    </div>

@if(!isset($is_bulk) || !$is_bulk)
    </div>
</body>
</html>
@endif