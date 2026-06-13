{{-- In-app notification bell + dropdown (data loaded/synced via DigitexNotifications JS) --}}
<li class="nav-item dropdown notification_dropdown" id="inAppNotifRoot">
    <a class="nav-link bell ai-icon" href="#" role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside"
       title="{{ __('header.notifications') }}" id="inAppNotifBell" aria-expanded="false">
        <i class="fa fa-bell"></i>
        <div class="pulse-css in-app-pulse" id="inAppNotifPulse" style="{{ ($inAppUnreadCount ?? 0) > 0 ? '' : 'display:none;' }}"></div>
        <span class="badge bg-danger rounded-circle text-white in-app-unread-badge" id="inAppNotifBadge"
              style="position:absolute;top:0;right:0;font-size:10px;padding:3px 5px;{{ ($inAppUnreadCount ?? 0) > 0 ? '' : 'display:none;' }}">{{ $inAppUnreadCount ?? 0 }}</span>
    </a>
    <div class="dropdown-menu dropdown-menu-end p-0" style="min-width:340px;" id="inAppNotifDropdown">
        <div class="p-3 border-bottom bg-light rounded-top d-flex justify-content-between align-items-center">
            <h6 class="mb-0 text-black fw-bold">{{ __('header.notifications') }}</h6>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary text-white in-app-unread-label" id="inAppNotifLabel"
                      style="{{ ($inAppUnreadCount ?? 0) > 0 ? '' : 'display:none;' }}">
                    {{ $inAppUnreadCount ?? 0 }} {{ __('header.new') }}
                </span>
                <button type="button" id="markAllNotifications" class="btn btn-link btn-sm p-0 fs-11 text-primary"
                        style="{{ ($inAppUnreadCount ?? 0) > 0 ? '' : 'display:none;' }}">{{ __('header.mark_all_read') }}</button>
            </div>
        </div>
        <div class="widget-media dz-scroll p-3" style="height:auto;max-height:380px;overflow-y:auto;">
            <ul class="timeline" id="inAppNotificationsList">
                @include('layout.partials.in-app-notifications-items', ['items' => $inAppNotifications ?? collect()])
            </ul>
        </div>
    </div>
</li>
