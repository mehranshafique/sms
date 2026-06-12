@extends('layout.layout')

@section('content')
@include('ai.partials.ai-styles')
@php
    $tools = [
        ['key' => 'draft_notice',   'icon' => 'la-bullhorn',        'color' => '#7c3aed'],
        ['key' => 'report_comment', 'icon' => 'la-comment-alt',     'color' => '#2563eb'],
        ['key' => 'translate',      'icon' => 'la-language',        'color' => '#0d9488'],
        ['key' => 'summarize',      'icon' => 'la-compress-alt',    'color' => '#d97706'],
        ['key' => 'improve',        'icon' => 'la-magic',           'color' => '#db2777'],
        ['key' => 'support_reply',  'icon' => 'la-headset',         'color' => '#475569'],
    ];
@endphp
<div class="content-body">
    <div class="container-fluid">

        {{-- Hero --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="ai-hero shadow-sm">
                    <div class="d-flex flex-wrap justify-content-between align-items-center p-4" style="position:relative; z-index:1;">
                        <div>
                            <span class="ai-hero__chip mb-2"><i class="la la-pen-fancy"></i> {{ __('ai.powered_by') }}</span>
                            <h3 class="text-white fw-bold mb-1">{{ __('ai.studio_title') }}</h3>
                            <p class="mb-0 text-white opacity-75">{{ __('ai.studio_subtitle') }}</p>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            @if($unlimited)
                                <span class="ai-quota-pill is-unlimited"><i class="la la-infinity"></i> {{ __('ai.unlimited') }}</span>
                            @else
                                <span class="ai-quota-pill {{ ($remaining !== null && $remaining <= 5) ? 'is-low' : '' }}">
                                    <i class="la la-bolt"></i> <span id="aiRemaining">{{ $remaining }}</span> {{ __('ai.left_this_month') }}
                                </span>
                            @endif
                            <i class="la la-robot ai-hero__icon d-none d-md-block"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(!$configured)
            <div class="alert alert-warning d-flex align-items-center gap-2">
                <i class="la la-exclamation-triangle fs-4"></i>
                <div>{{ __('ai.not_configured_notice') }}</div>
            </div>
        @endif

        <div class="row g-3 mb-4" id="aiToolGrid">
            @foreach($tools as $t)
                <div class="col-md-4 col-sm-6">
                    <div class="ai-tool-card" data-tool="{{ $t['key'] }}">
                        <div class="ai-tool-icon" style="background:linear-gradient(135deg,{{ $t['color'] }},{{ $t['color'] }}cc);">
                            <i class="la {{ $t['icon'] }}"></i>
                        </div>
                        <h6 class="fw-bold mb-1">{{ __('ai.tool_' . $t['key'] . '_title') }}</h6>
                        <p class="text-muted small mb-0">{{ __('ai.tool_' . $t['key'] . '_desc') }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Workspace --}}
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="ai-panel h-100">
                    <div class="ai-panel__head">
                        <h6 class="mb-0 fw-bold"><i class="la la-keyboard text-primary"></i> <span id="aiToolLabel">{{ __('ai.tool_draft_notice_title') }}</span></h6>
                    </div>
                    <div class="ai-panel__body">
                        <input type="hidden" id="aiTool" value="draft_notice">

                        <div class="mb-3 d-flex gap-2 flex-wrap">
                            <div id="aiToneWrap">
                                <label class="form-label small text-muted mb-1">{{ __('ai.tone') }}</label>
                                <select class="form-select form-select-sm" id="aiTone" style="width:auto;">
                                    <option value="professional">{{ __('ai.tone_professional') }}</option>
                                    <option value="friendly">{{ __('ai.tone_friendly') }}</option>
                                    <option value="formal">{{ __('ai.tone_formal') }}</option>
                                    <option value="urgent">{{ __('ai.tone_urgent') }}</option>
                                </select>
                            </div>
                            <div id="aiLangWrap" style="display:none;">
                                <label class="form-label small text-muted mb-1">{{ __('ai.target_language') }}</label>
                                <select class="form-select form-select-sm" id="aiLang" style="width:auto;">
                                    <option value="French">Français</option>
                                    <option value="English">English</option>
                                    <option value="Swahili">Kiswahili</option>
                                    <option value="Lingala">Lingala</option>
                                    <option value="Arabic">العربية</option>
                                </select>
                            </div>
                        </div>

                        <label class="form-label small text-muted mb-1" id="aiInputLabel">{{ __('ai.your_input') }}</label>
                        <textarea class="form-control" id="aiText" rows="8" placeholder="{{ __('ai.studio_input_placeholder') }}"></textarea>

                        <button class="btn btn-primary mt-3" id="aiGenerate">
                            <i class="la la-magic"></i> {{ __('ai.generate') }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="ai-panel h-100">
                    <div class="ai-panel__head">
                        <h6 class="mb-0 fw-bold"><i class="la la-file-alt text-success"></i> {{ __('ai.result') }}</h6>
                        <button class="btn btn-sm btn-outline-secondary" id="aiCopy" style="display:none;"><i class="la la-copy"></i> {{ __('ai.copy') }}</button>
                    </div>
                    <div class="ai-panel__body">
                        <div class="ai-output" id="aiOutput"><span class="text-muted">{{ __('ai.result_placeholder') }}</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
(function () {
    var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var genUrl = "{{ route('ai.studio.generate') }}";
    var labels = {
        @foreach($tools as $t)
        "{{ $t['key'] }}": "{{ __('ai.tool_' . $t['key'] . '_title') }}",
        @endforeach
    };

    var toolInput = document.getElementById('aiTool');
    var toolLabel = document.getElementById('aiToolLabel');
    var langWrap = document.getElementById('aiLangWrap');
    var toneWrap = document.getElementById('aiToneWrap');
    var output = document.getElementById('aiOutput');
    var copyBtn = document.getElementById('aiCopy');
    var genBtn = document.getElementById('aiGenerate');

    function selectTool(key, card){
        toolInput.value = key;
        toolLabel.textContent = labels[key] || '';
        document.querySelectorAll('.ai-tool-card').forEach(function(c){ c.classList.remove('active'); });
        if (card) card.classList.add('active');
        langWrap.style.display = (key === 'translate') ? '' : 'none';
        toneWrap.style.display = (key === 'draft_notice' || key === 'improve') ? '' : 'none';
    }

    document.querySelectorAll('.ai-tool-card').forEach(function(card){
        card.addEventListener('click', function(){ selectTool(card.getAttribute('data-tool'), card); });
    });
    // default
    selectTool('draft_notice', document.querySelector('.ai-tool-card'));

    genBtn.addEventListener('click', function(){
        var text = document.getElementById('aiText').value.trim();
        if (!text){ document.getElementById('aiText').focus(); return; }

        genBtn.disabled = true;
        genBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> {{ __('ai.generating') }}';
        output.innerHTML = '<span class="ai-typing"><span></span><span></span><span></span></span>';
        copyBtn.style.display = 'none';

        fetch(genUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                tool: toolInput.value,
                text: text,
                tone: document.getElementById('aiTone').value,
                language: document.getElementById('aiLang').value
            })
        })
        .then(function(r){ return r.json(); })
        .then(function(data){
            if (data.ok){
                output.innerHTML = data.html;
                output.dataset.raw = data.content;
                copyBtn.style.display = '';
                var rem = document.getElementById('aiRemaining');
                if (rem && data.remaining !== null && typeof data.remaining !== 'undefined') rem.textContent = data.remaining;
            } else {
                output.innerHTML = '<span class="text-danger">' + (data.message || '') + '</span>';
            }
        })
        .catch(function(){ output.innerHTML = '<span class="text-danger">{{ __('ai.error_generic') }}</span>'; })
        .finally(function(){
            genBtn.disabled = false;
            genBtn.innerHTML = '<i class="la la-magic"></i> {{ __('ai.generate') }}';
        });
    });

    copyBtn.addEventListener('click', function(){
        var raw = output.dataset.raw || output.innerText;
        navigator.clipboard.writeText(raw).then(function(){
            copyBtn.innerHTML = '<i class="la la-check"></i> {{ __('ai.copied') }}';
            setTimeout(function(){ copyBtn.innerHTML = '<i class="la la-copy"></i> {{ __('ai.copy') }}'; }, 1500);
        });
    });
})();
</script>
@endsection
