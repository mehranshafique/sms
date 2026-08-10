@php
    $sealPosition = $settings['seal_position'] ?? 'center';
    $sealImage = $settings['seal_image'] ?? null;
    $svgId = isset($loop_index) ? $loop_index : rand(1000, 9999);
@endphp
@if($sealPosition !== 'none')
    <div class="stamp-overlay stamp-overlay--{{ $sealPosition }}">
        @if($sealImage)
            <img src="{{ asset('storage/' . $sealImage) }}" alt="{{ __('reports.school_seal') }}" class="stamp-image">
        @else
            <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="48" fill="none" stroke="var(--stamp-blue)" stroke-width="2"/>
                <circle cx="50" cy="50" r="45" fill="none" stroke="var(--stamp-blue)" stroke-width="1"/>
                <circle cx="50" cy="50" r="28" fill="none" stroke="var(--stamp-blue)" stroke-width="1" stroke-dasharray="2,2"/>

                <path id="txt-top-{{ $svgId }}" d="M 18 50 A 32 32 0 0 1 82 50" fill="none"/>
                <path id="txt-bot-{{ $svgId }}" d="M 12 50 A 38 38 0 0 0 88 50" fill="none"/>

                <text fill="var(--stamp-blue)" font-size="11" font-weight="bold" font-family="Arial" letter-spacing="2">
                    <textPath href="#txt-top-{{ $svgId }}" startOffset="50%" text-anchor="middle">{{ __('reports.bulletin_title') }}</textPath>
                </text>

                <text fill="var(--stamp-blue)" font-size="10" font-weight="bold" font-family="Arial" letter-spacing="1">
                    <textPath href="#txt-bot-{{ $svgId }}" startOffset="50%" text-anchor="middle">{{ strtoupper(\Illuminate\Support\Str::limit($student->institution->name ?? __('reports.direction'), 18, '')) }}</textPath>
                </text>

                <circle cx="12" cy="50" r="2" fill="var(--stamp-blue)"/>
                <circle cx="88" cy="50" r="2" fill="var(--stamp-blue)"/>
                <text x="50" y="38" fill="var(--stamp-blue)" font-size="10" text-anchor="middle">★★★</text>

                @if(isset($student->institution->logo) && $student->institution->logo)
                    <image href="{{ asset('storage/' . $student->institution->logo) }}" x="42" y="45" height="16" width="16" />
                @else
                    <circle cx="50" cy="53" r="6" fill="#c49a45"/>
                @endif
                <text x="50" y="72" fill="var(--stamp-blue)" font-size="10" text-anchor="middle">★★</text>
            </svg>
        @endif
    </div>
@endif
