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
    <div style="display: flex; justify-content: center; width: 100%; min-height: 100vh;">
        <div class="student-column single-card-view single-card-view-period">
@else
    <!-- Standard bulk mode column rendering -->
    <div class="student-column" style="float: left; width: 25%; height: 210mm;">
@endif

    @php
        $principalName = __('reports.direction') ?? 'DIRECTION';
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
            <div class="term-title">{{ __('reports.bulletin_title') ?? 'BULLETIN' }} {{ strtoupper($period) }}</div>
        </div>
        
        <div class="divider-thick"></div>
        <div class="divider-thin"></div>

        <table>
            <thead>
                <tr>
                    <th class="left-align">{{ __('reports.subject') ?? 'Branches' }}</th>
                    <th>{{ __('reports.cotes') ?? 'Cotes' }}</th>
                    <th>{{ __('reports.max_marks') ?? 'MAX' }}</th>
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
                    @if($row['has_marks'])
                        @php
                            $val = is_numeric($row['obtained']) ? (float)$row['obtained'] : '-';
                            $max = $row['max'] ?? 0;
                            
                            $val_calc = is_numeric($val) ? $val : 0;
                            $totalObtained += $val_calc;
                            $totalMax += $max;
                            $isFail = ($max > 0 && $val_calc < ($max / 2));
                        @endphp
                        <tr>
                            <td class="left-align" style="width: 60%;">{{ $row['subject']->name }}</td>
                            <td class="{{ $isFail ? 'fail-grade' : '' }}" style="width: 20%;">{{ $val }}</td>
                            <td style="width: 20%;">{{ $max > 0 ? $max : '-' }}</td>
                        </tr>
                    @endif
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
                @php
                    $secRank = $ranks['section_rank'] ?? '-';
                    $secTotal = $ranks['section_total'] ?? '-';
                @endphp
                <span class="val">{{ $secRank }}{{ is_numeric($secRank) ? 'e' : '' }} | {{ $secTotal }}</span>
            </div>
        </div>

        <div class="footer-wrapper">
            @php $qrData = urlencode("{$student->first_name} {$student->last_name} | ID: {$student->admission_number}"); @endphp
            <div class="qr-code" style="background-image: url('https://api.qrserver.com/v1/create-qr-code/?size=64x64&data={{ $qrData }}');"></div>

            <div class="stamp-overlay">
                @php $svgId = isset($loop_index) ? $loop_index . '-' . $student->id : $student->id; @endphp
                <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="50" cy="50" r="46" fill="none" stroke="var(--stamp-blue)" stroke-width="3"/>
                    <circle cx="50" cy="50" r="41" fill="none" stroke="var(--stamp-blue)" stroke-width="1.2"/>
                    <circle cx="50" cy="50" r="26" fill="none" stroke="var(--stamp-blue)" stroke-width="0.8" stroke-dasharray="2,2"/>
                    
                    <path id="txt-top-{{ $svgId }}" d="M 17 50 A 33 33 0 0 1 83 50" fill="none"/>
                    
                    <path id="txt-bot-{{ $svgId }}" d="M 83 50 A 33 33 0 0 1 17 50" fill="none"/>
                    
                    <text fill="var(--stamp-blue)" font-size="11.5" font-weight="bold" font-family="Arial" letter-spacing="1">
                        <textPath href="#txt-top-{{ $svgId }}" startOffset="50%" text-anchor="middle">{{ __('reports.bulletin_title') ?? 'BULLETIN' }}</textPath>
                    </text>
                    <text fill="var(--stamp-blue)" font-size="8.5" font-weight="bold" font-family="Arial" letter-spacing="1.5">
                        <textPath href="#txt-bot-{{ $svgId }}" startOffset="50%" text-anchor="middle">{{ strtoupper(\Illuminate\Support\Str::limit($student->institution->name ?? __('reports.direction') ?? 'DIRECTION', 18, '')) }}</textPath>
                    </text>
                    
                    <rect x="36" y="32" width="28" height="36" rx="2" fill="white" opacity="0.8"/>
                    <rect x="36" y="32" width="28" height="36" rx="2" fill="none" stroke="var(--stamp-blue)" stroke-width="1" transform="rotate(-8 50 50)"/>
                    
                    <text x="50" y="42" fill="var(--stamp-blue)" font-size="9" text-anchor="middle">★★★</text>
                    
                    @if(isset($student->institution->logo) && $student->institution->logo)
                        <image href="{{ asset('storage/' . $student->institution->logo) }}" x="36" y="36" height="28" width="28" preserveAspectRatio="xMidYMid meet"/>
                    @else
                        <circle cx="50" cy="52" r="5" fill="#c49a45"/>
                    @endif

                    <text x="50" y="65" fill="var(--stamp-blue)" font-size="9" text-anchor="middle">★★</text>
                </svg>
            </div>

            <div class="signature-block">
                <div>{{ __('reports.made_in') ?? 'Fait à' }} {{ $student->institution->city ?? 'Kinshasa' }}, {{ __('reports.on_date') ?? 'le' }} {{ date('d/m/Y') }}</div>
                <div style="margin: 3px 0;">{{ __('reports.principal') ?? 'Chef d\'établissement' }}</div>
                <div>{{ strtoupper($principalName) }}</div>
            </div>
        </div>
    </div> 

@if(!isset($is_bulk) || !$is_bulk)
    </div>
</body>
</html>
@endif