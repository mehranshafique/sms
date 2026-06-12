{{--
  Reusable inline AI action button.
  @param string $tool   Embed tool key (e.g. draft_notice)
  @param array  $params JSON-serializable params for the tool
  @param string $label  Button label
  @param string $target CSS selector to fill with result text (optional)
  @param string $panel  CSS selector for output panel (optional, defaults to sibling .ai-embed-panel)
--}}
@php
    $btnId = 'ai-embed-' . md5($tool . ($target ?? '') . uniqid('', true));
@endphp
<button type="button"
    class="ai-embed-btn"
    id="{{ $btnId }}"
    data-ai-tool="{{ $tool }}"
    data-ai-params='@json($params ?? [])'
    @if(!empty($fields)) data-ai-fields='@json($fields)' @endif
    @if(!empty($target)) data-ai-target="{{ $target }}" @endif
    @if(!empty($panel)) data-ai-panel="{{ $panel }}" @endif
    @if(!empty($confirm)) data-ai-confirm="1" @endif
>
    <i class="la la-magic"></i> {{ $label ?? __('ai.generate') }}
</button>
