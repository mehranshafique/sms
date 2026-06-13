@forelse($items as $notif)
    <li>
        <a href="{{ $notif->link ?? '#' }}"
           class="notification-link in-app-notif-link {{ $notif->isUnread() ? '' : 'opacity-75' }}"
           data-id="{{ $notif->id }}"
           data-unread="{{ $notif->isUnread() ? '1' : '0' }}">
            <div class="timeline-panel in-app-notif-panel rounded p-2 mb-2 border {{ $notif->isUnread() ? 'border-primary border-opacity-25' : '' }}">
                <div class="media me-3 d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:#f8f9fa;border-radius:50%;">
                    <i class="fa {{ $notif->icon }} fs-20"></i>
                </div>
                <div class="media-body">
                    <h6 class="mb-1 text-dark fw-bold">{{ $notif->title }}</h6>
                    <small class="d-block text-muted">{{ $notif->message }}</small>
                    <small class="d-block text-muted mt-1">{{ $notif->created_at->diffForHumans() }}</small>
                </div>
            </div>
        </a>
    </li>
@empty
    <li class="text-center text-muted py-4 in-app-notif-empty">
        <i class="fa fa-bell-slash fs-24 mb-2 d-block opacity-50"></i>
        {{ __('header.no_new_notifications') }}
    </li>
@endforelse
