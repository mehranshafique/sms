{{-- EPST / Ministry header block for bulletins (matches official layout) --}}
@php
    $inst = $student->institution ?? null;
    $cityName = null;
    if ($inst) {
        $cityName = $inst->cityRelation->name
            ?? (is_string($inst->city ?? null) && !is_numeric($inst->city) ? $inst->city : null);
    }
    $addressParts = collect([
        $inst->address ?? null,
        $cityName,
    ])->filter()->implode(', ');
@endphp
<div class="epst-header">
    <div class="epst-republic">{{ __('reports.epst_republic_line') }}</div>
    <div class="epst-ministry">{{ __('reports.epst_ministry_line') }}</div>
    <div class="epst-school">{{ strtoupper($inst->name ?? '') }}</div>
    <div class="epst-ornament" aria-hidden="true">
        <span class="epst-ornament-line"></span>
        <span class="epst-ornament-diamond"></span>
        <span class="epst-ornament-line"></span>
    </div>
    @if($addressParts !== '')
        <div class="epst-address">Adresse : {{ $addressParts }}</div>
    @endif
</div>
