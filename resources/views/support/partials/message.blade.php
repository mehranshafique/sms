@php
    $isOwn = ($isSupport && $m->is_support) || (!$isSupport && !$m->is_support);
    $authorName = $m->user->name ?? __('support.system');
    $initials = strtoupper(mb_substr(trim($authorName), 0, 1));
    $isImage = $m->attachment_path && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $m->attachment_path);
@endphp
@if($m->is_system)
    <div class="sp-msg is-system" data-msg-id="{{ $m->id }}">
        <span class="sp-sysnote"><i class="la la-info-circle"></i> {{ $m->body }} · {{ $m->created_at->diffForHumans() }}</span>
    </div>
@else
    <div class="sp-msg {{ $isOwn ? 'is-own' : '' }}" data-msg-id="{{ $m->id }}">
        <span class="sp-msg__avatar">{{ $initials }}</span>
        <div class="sp-msg__bubble">
            <div class="sp-msg__name">
                {{ $authorName }}
                @if($m->is_support)<span class="badge-agent">{{ __('support.agent') }}</span>@endif
            </div>
            @if($m->body)
                <div class="sp-msg__body">{{ $m->body }}</div>
            @endif
            @if($m->attachment_path)
                @if($isImage)
                    <a href="{{ asset('storage/'.$m->attachment_path) }}" target="_blank" class="sp-msg__file">
                        <img src="{{ asset('storage/'.$m->attachment_path) }}" alt="attachment">
                    </a>
                @else
                    <a href="{{ asset('storage/'.$m->attachment_path) }}" target="_blank" class="sp-msg__file">
                        <i class="la la-paperclip"></i> {{ $m->attachment_name ?? __('support.attachment') }}
                    </a>
                @endif
            @endif
            <div class="sp-msg__time">{{ $m->created_at->format('M d, H:i') }}</div>
        </div>
    </div>
@endif
