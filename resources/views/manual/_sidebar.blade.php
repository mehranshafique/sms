<div class="manual-sidebar-wrap d-none d-lg-block">
    <div class="manual-sidebar-panel">
        <div class="manual-sidebar-header fw-semibold small text-uppercase text-muted">
            {{ __('manual.table_of_contents') }}
        </div>
        <div class="manual-sidebar-body" id="manual-sidebar-body">
            @if ($manualType === 'web')
                @if ($toc['introduction'] ?? null)
                    <a href="{{ route('manual.web.show', 'introduction') }}"
                       data-slug="introduction"
                       class="d-block px-3 py-2 small text-decoration-none text-dark {{ $currentSlug === 'introduction' ? 'active' : '' }}">
                        {{ __('manual.read_intro') }}
                    </a>
                @endif
                @foreach ($toc['parts'] as $part)
                    <div class="px-3 pt-2 pb-1 small text-muted fw-semibold">{{ __('manual.part_label', ['id' => $part['id']]) }}</div>
                    @foreach ($part['modules'] as $mod)
                        <a href="{{ route('manual.web.show', $mod['slug']) }}"
                           data-slug="{{ $mod['slug'] }}"
                           title="{{ $mod['title'] }}"
                           class="d-block px-3 py-2 small text-decoration-none text-dark {{ $currentSlug === $mod['slug'] ? 'active' : '' }}">
                            <strong>{{ $mod['id'] }}</strong> — {{ $mod['title'] }}
                        </a>
                    @endforeach
                @endforeach
            @else
                @if ($toc['introduction'] ?? null)
                    <a href="{{ route('manual.mobile.show', 'introduction') }}"
                       data-slug="introduction"
                       class="d-block px-3 py-2 small text-decoration-none text-dark {{ $currentSlug === 'introduction' ? 'active-mobile' : '' }}">
                        {{ __('manual.read_intro') }}
                    </a>
                @endif
                @foreach ($toc['parts'] as $part)
                    <a href="{{ route('manual.mobile.show', $part['slug']) }}"
                       data-slug="{{ $part['slug'] }}"
                       title="{{ $part['title'] }}"
                       class="d-block px-3 py-2 small text-decoration-none text-dark {{ $currentSlug === $part['slug'] ? 'active-mobile' : '' }}">
                        {{ __('manual.mobile_part_label', ['num' => $part['number']]) }} — {{ $part['title'] }}
                    </a>
                @endforeach
            @endif
        </div>
    </div>
</div>
