{{-- EPST / Ministry header block for bulletins --}}
@php
    $inst = $student->institution ?? null;
    $epstCode = $inst->epst_school_code ?? $inst->code ?? '';
    $headName = $inst->head_person_name ?? __('reports.direction');
@endphp
<div class="epst-header" style="text-align:center; margin-bottom:8px; font-size:11px; line-height:1.4;">
    <div style="font-weight:bold; text-transform:uppercase;">{{ __('reports.epst_republic_line') }}</div>
    <div>{{ __('reports.epst_ministry_line') }}</div>
    @if($epstCode)
        <div>{{ __('reports.epst_school_code') }}: <strong>{{ $epstCode }}</strong></div>
    @endif
    <div style="font-size:13px; font-weight:bold; margin-top:6px;">{{ $inst->name ?? '' }}</div>
    <div style="font-size:10px;">{{ __('reports.epst_director') }}: {{ $headName }}</div>
</div>
