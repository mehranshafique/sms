{{--
    Reusable clean stat card.
    Props: $icon (la class), $tint (primary|success|warning|danger|info|dark),
           $label, $value, $hint (optional), $hintClass (optional), $url (optional)
--}}
@php
    $tint = $tint ?? 'primary';
    $hint = $hint ?? null;
    $hintClass = $hintClass ?? 'text-muted';
    $url = $url ?? null;
@endphp
<div class="dash-stat">
    <div class="d-flex align-items-center" style="gap: 14px;">
        <span class="dash-stat__icon tint-{{ $tint }}"><i class="{{ $icon }}"></i></span>
        <div class="flex-grow-1">
            <p class="dash-stat__label">{{ $label }}</p>
            <h4 class="dash-stat__value">{{ $value }}</h4>
            @if($hint)
                <small class="dash-stat__hint {{ $hintClass }}">{{ $hint }}</small>
            @endif
        </div>
        @if($url)
            <a href="{{ $url }}" class="text-tint-{{ $tint }}" title="{{ __('dashboard.view_details') }}">
                <i class="la la-arrow-right"></i>
            </a>
        @endif
    </div>
</div>
