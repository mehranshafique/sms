@php
    $authority = $authority ?? null;
    $authorityTitle = $authority['title'] ?? (__('reports.principal') ?? 'Chef d\'établissement');
    $authorityName = trim((string) ($authority['name'] ?? ''));
    if ($authorityName === '') {
        $authorityName = $principalName ?? (__('reports.direction') ?? 'DIRECTION');
    }
    $authoritySignature = $authority['signature'] ?? null;
    $footerCity = $student->institution->cityRelation->name
        ?? (is_string($student->institution->city ?? null) && ! is_numeric($student->institution->city)
            ? $student->institution->city
            : null)
        ?? 'Kinshasa';
@endphp
<div class="signature-block">
    <div class="authority-date">{{ __('reports.made_in') ?? 'Fait à' }} {{ $footerCity }}, {{ __('reports.on_date') ?? 'le' }} {{ date('d/m/Y') }}</div>
    <div class="authority-title">{{ strtoupper($authorityTitle) }}</div>
    @if($authoritySignature)
        <img src="{{ asset('storage/' . $authoritySignature) }}" alt="signature" class="authority-signature-img">
    @else
        <div class="authority-signature-space"></div>
    @endif
    <div class="authority-line"></div>
    <div class="authority-name">{{ strtoupper($authorityName) }}</div>
</div>
