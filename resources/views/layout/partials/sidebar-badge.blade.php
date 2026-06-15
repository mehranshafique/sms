@php
    $count = (int) ($sidebarBadges[$key] ?? 0);
@endphp
@if($count > 0)
    <span class="badge badge-danger badge-xs style-1 sidebar-menu-badge" data-sidebar-badge="{{ $key }}">{{ $count > 99 ? '99+' : $count }}</span>
@endif
