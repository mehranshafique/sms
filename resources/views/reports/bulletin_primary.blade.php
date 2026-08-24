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

    <div class="single-card-page">
        <div class="student-column single-card-view cards-{{ $cards_per_page ?? 4 }}">
@else
    <div class="student-column">
@endif

    <div class="card-inner">
    @include('reports.partials.bulletin_banners')
    @php
        $labels = $column_labels ?? [];
        $subjectCount = count($data ?? []);
        $densityClass = $subjectCount > 16 ? 'density-high' : ($subjectCount > 12 ? 'density-medium' : 'density-low');
        $principalName = $authority['name'] ?? (__('reports.direction') ?? 'DIRECTION');
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
            <div class="class-name" style="font-size:10px;font-weight:normal;">
                {{ __('results.student_id') }} {{ $student->admission_number }}
                @if(!empty($student->roll_number) || !empty($enrollment->roll_number))
                    &nbsp;|&nbsp;
                    {{ __('results.roll_number') }} {{ $enrollment->roll_number ?? $student->roll_number }}
                @endif
            </div>
            <div class="barcode"></div>
            <div class="term-title-bar">{{ $term_title ?? (__('reports.bulletin_title') . ' ' . ($trimester ?? 1) . ' ' . __('reports.trimester')) }}</div>
        </div>

        <div class="divider-thick"></div>
        <div class="divider-thin"></div>

        <div class="subjects-table-wrap {{ $densityClass }}">
        <table>
            <thead>
                <tr>
                    <th class="left-align">{{ $labels['subject'] ?? __('reports.subject') }}</th>
                    <th>{{ $labels['p1'] ?? 'P1' }}</th>
                    <th>{{ $labels['p2'] ?? 'P2' }}</th>
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
                    @php
                            // Cast directly to (float) to completely remove .00 and decimal artifacts
                            $p1 = is_numeric($row['p1_score'] ?? null) ? (float)$row['p1_score'] : '-';
                            $p2 = is_numeric($row['p2_score'] ?? null) ? (float)$row['p2_score'] : '-';
                            $ex = is_numeric($row['exam_score'] ?? null) ? (float)$row['exam_score'] : '-';
                            
                            $p1_val = is_numeric($p1) ? $p1 : 0;
                            $p2_val = is_numeric($p2) ? $p2 : 0;
                            $ex_val = is_numeric($ex) ? $ex : 0;
                            
                            $tot = (is_numeric($p1) || is_numeric($p2) || is_numeric($ex))
                                ? (float) ($row['total_score'] ?? ($p1_val + $p2_val + $ex_val))
                                : '-';
                            
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
                @endforeach
            </tbody>
        </table>
        </div>

        @php
            $percentagePeriod = $sum_p_max_actual > 0 ? ($sum_p_obt / $sum_p_max_actual) * 100 : 0;
            $percentageTotal = $sum_tot_max > 0 ? ($sum_tot_obt / $sum_tot_max) * 100 : 0;
            
            $application = $application ?? '-';
            $conduct = $conduct ?? '-';
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
                    $placeEff = $ranks['place_eff'] ?? ((($ranks['section_rank'] ?? '-') . ' / ' . ($ranks['section_total'] ?? '-')));
                @endphp
                <span class="val">{{ $placeEff }}</span>
                <span class="val">{{ $placeEff }}</span>
            </div>
        </div>

        <div class="footer-wrapper">
            @php $qrData = urlencode("{$student->first_name} {$student->last_name} | ID: {$student->admission_number}"); @endphp
            <div class="qr-code" style="background-image: url('https://api.qrserver.com/v1/create-qr-code/?size=64x64&data={{ $qrData }}');"></div>

            @include('reports.partials.bulletin_seal')

            @include('reports.partials.bulletin_signature')
        </div>
    </div>
    </div>

@if(!isset($is_bulk) || !$is_bulk)
    </div>
</body>
</html>
@endif