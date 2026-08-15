@if(!isset($is_bulk) || !$is_bulk)
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('reports.bulletin_title') ?? 'BULLETIN' }}</title>
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
    @endphp

        <div class="header-content">
            @include('reports.partials.epst_header', ['student' => $student])
            <div class="logo-box">
                @if(isset($student->institution->logo) && $student->institution->logo)
                    <img src="{{ asset('storage/' . $student->institution->logo) }}" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                @else
                    @php $instNameParts = explode(' ', $student->institution->name ?? __('reports.direction') ?? 'DIRECTION', 2); @endphp
                    <span style="text-align:center;">{{ strtoupper(substr($instNameParts[0], 0, 10)) }}<br>...</span>
                @endif
            </div>
            <div class="school-name">{{ strtoupper($student->institution->name ?? '') }}</div>
            <div class="student-name">{{ strtoupper($student->first_name . ' ' . $student->last_name) }}</div>
            <div class="class-name">{{ $enrollment->classSection->gradeLevel->name ?? '' }} - {{ $enrollment->classSection->name ?? '' }}</div>
            <div class="barcode"></div>
            <div class="term-title-bar">{{ $term_title ?? (__('reports.bulletin_period_title', ['period' => strtoupper($period ?? '')])) }}</div>
        </div>

        <div class="divider-thick"></div>
        <div class="divider-thin"></div>

        <div class="subjects-table-wrap {{ $densityClass }}">
        <table>
            <thead>
                <tr>
                    <th class="left-align">{{ $labels['subject'] ?? __('reports.subject') }}</th>
                    <th>{{ $labels['score'] ?? __('reports.cotes') }}</th>
                    <th>{{ $labels['max'] ?? __('reports.max_marks') }}</th>
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
                        $val = is_numeric($row['obtained']) ? (float)$row['obtained'] : '-';
                        $max = $row['max'] ?? 0;
                        
                        $val_calc = is_numeric($val) ? $val : 0;
                        $totalObtained += $val_calc;
                        $totalMax += $max;
                        $isFail = is_numeric($row['obtained']) && $max > 0 && ($val_calc < ($max / 2) || ($max == 20 && $val_calc < 10));
                    @endphp
                    <tr>
                        <td class="left-align subject-name">{{ $row['subject']->name }}</td>
                        <td class="{{ $isFail ? 'fail-grade' : '' }}">{{ $val }}</td>
                        <td>{{ $max > 0 ? $max : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>

        @php
            $percentage = $totalMax > 0 ? ($totalObtained / $totalMax) * 100 : 0;
            $application = $application ?? '-';
            $conduct = $conduct ?? '-';
        @endphp

        <div class="summary-container">
            <div class="summary-row">
                <span class="label">{{ __('reports.maximum_general') ?? 'MAXIMUM GENERAL' }}</span>
                <span class="val">{{ $totalMax }}</span>
            </div>
            <div class="summary-row">
                <span class="label">{{ __('reports.total_obtained') ?? 'TOTAL OBTENU' }}</span>
                <span class="val">{{ $totalObtained }}</span>
            </div>
            <div class="summary-row">
                <span class="label">{{ __('reports.percentage') ?? 'POURCENTAGE' }}</span>
                <span class="val">{{ number_format($percentage, 2) }}%</span>
            </div>
            <div class="summary-row">
                <span class="label">{{ __('reports.conduct') ?? 'CONDUITE' }}</span>
                <span class="val">{{ $conduct }}</span>
            </div>
            <div class="summary-row">
                <span class="label">{{ __('reports.application') ?? 'APPLICATION' }}</span>
                <span class="val">{{ $application }}</span>
            </div>
            <div class="summary-row">
                <span class="label">{{ __('reports.place_eff') ?? 'PLACE - EFF' }}</span>
                <span class="val">{{ $ranks['place_eff'] ?? ((is_numeric($ranks['section_rank'] ?? null) ? ($ranks['section_rank'] . ' / ' . ($ranks['section_total'] ?? '')) : '-')) }}</span>
            </div>
        </div>

        <div class="footer-wrapper">
            @php $qrData = urlencode("{$student->first_name} {$student->last_name} | ID: {$student->admission_number}"); @endphp
            <div class="qr-code" style="background-image: url('https://api.qrserver.com/v1/create-qr-code/?size=64x64&data={{ $qrData }}');"></div>

            @include('reports.partials.bulletin_seal')

            <div class="signature-block">
                <div>{{ __('reports.made_in') ?? 'Fait à' }} {{ $student->institution->city ?? 'Kinshasa' }}, {{ __('reports.on_date') ?? 'le' }} {{ date('d/m/Y') }}</div>
                <div style="margin: 3px 0;">{{ __('reports.principal') ?? 'Chef d\'établissement' }}</div>
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