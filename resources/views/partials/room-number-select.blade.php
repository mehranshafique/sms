@php
    $roomFieldName = $name ?? 'room_number';
    $roomFieldId = $id ?? $roomFieldName;
    $roomSelected = old($roomFieldName, $selected ?? '');
    $roomOptions = $roomOptions ?? institution_room_options($institutionId ?? null);
    $roomCssClass = trim('form-control default-select ' . ($class ?? ''));
@endphp
<select name="{{ $roomFieldName }}" id="{{ $roomFieldId }}" class="{{ $roomCssClass }}" @if(!empty($required)) required @endif>
    <option value="">{{ $placeholder ?? __('class_section.select_room') }}</option>
    @foreach($roomOptions as $value => $label)
        <option value="{{ $value }}" @selected((string) $roomSelected === (string) $value)>{{ $label }}</option>
    @endforeach
    @if($roomSelected && !array_key_exists($roomSelected, $roomOptions))
        <option value="{{ $roomSelected }}" selected>{{ $roomSelected }}</option>
    @endif
</select>
