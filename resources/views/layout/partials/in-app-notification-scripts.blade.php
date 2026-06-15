<script>
(function () {
    'use strict';

    if (window.DigitexNotifications) return;

    var cfg = {
        csrf: @json(csrf_token()),
        feedUrl: @json(route('notifications.feed')),
        markReadBase: @json(url('/notifications')),
        markAllUrl: @json(route('notifications.read_all')),
        newLabel: @json(__('header.new')),
        emptyText: @json(__('header.no_new_notifications')),
        strings: {
            markFailed: @json(__('header.notif_mark_failed')),
        },
        initialCount: {{ (int) ($inAppUnreadCount ?? 0) }},
    };

    var state = {
        unread: cfg.initialCount,
        syncing: false,
        mutating: false,
        syncAbort: null,
    };

    function els() {
        return {
            list: document.getElementById('inAppNotificationsList'),
            bell: document.getElementById('inAppNotifBell'),
            badge: document.getElementById('inAppNotifBadge'),
            label: document.getElementById('inAppNotifLabel'),
            pulse: document.getElementById('inAppNotifPulse'),
            markAll: document.getElementById('markAllNotifications'),
            root: document.getElementById('inAppNotifRoot'),
        };
    }

    function emptyHtml() {
        return '<li class="text-center text-muted py-4 in-app-notif-empty">' +
            '<i class="fa fa-bell-slash fs-24 mb-2 d-block opacity-50"></i>' +
            escapeHtml(cfg.emptyText) + '</li>';
    }

    function escapeHtml(s) {
        return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function setUnreadCount(count) {
        state.unread = Math.max(0, parseInt(count, 10) || 0);
        var $ = els();
        if ($.badge) {
            $.badge.textContent = state.unread;
            $.badge.style.display = state.unread > 0 ? '' : 'none';
        }
        if ($.label) {
            $.label.textContent = state.unread + ' ' + cfg.newLabel;
            $.label.style.display = state.unread > 0 ? '' : 'none';
        }
        if ($.pulse) {
            $.pulse.style.display = state.unread > 0 ? '' : 'none';
        }
        if ($.markAll) {
            $.markAll.style.display = state.unread > 0 ? '' : 'none';
        }
    }

    function updateSidebarBadges(badges) {
        if (!badges || typeof badges !== 'object') return;
        document.querySelectorAll('[data-sidebar-badge]').forEach(function (el) {
            var key = el.getAttribute('data-sidebar-badge');
            var count = parseInt(badges[key], 10) || 0;
            if (count > 0) {
                el.textContent = count > 99 ? '99+' : String(count);
                el.style.display = '';
            } else {
                el.style.display = 'none';
            }
        });
    }

    function renderItem(n) {
        var unread = n.is_unread ? '1' : '0';
        var link = n.link || '#';
        return '<li>' +
            '<a href="' + escapeHtml(link) + '" class="notification-link in-app-notif-link' + (n.is_unread ? '' : ' opacity-75') + '" data-id="' + n.id + '" data-unread="' + unread + '">' +
            '<div class="timeline-panel in-app-notif-panel rounded p-2 mb-2 border' + (n.is_unread ? ' border-primary border-opacity-25' : '') + '">' +
            '<div class="media me-3 d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:#f8f9fa;border-radius:50%;">' +
            '<i class="fa ' + escapeHtml(n.icon || 'fa-bell') + ' fs-20"></i></div>' +
            '<div class="media-body">' +
            '<h6 class="mb-1 text-dark fw-bold">' + escapeHtml(n.title) + '</h6>' +
            '<small class="d-block text-muted">' + escapeHtml(n.message) + '</small>' +
            '<small class="d-block text-muted mt-1">' + escapeHtml(n.time_ago || '') + '</small>' +
            '</div></div></a></li>';
    }

    function renderList(notifications) {
        var $ = els();
        if (!$.list) return;
        if (!notifications || !notifications.length) {
            $.list.innerHTML = emptyHtml();
            return;
        }
        $.list.innerHTML = notifications.map(renderItem).join('');
    }

    function markItemReadDom(link) {
        if (!link || link.dataset.unread !== '1') return false;
        link.dataset.unread = '0';
        link.classList.add('opacity-75');
        var panel = link.querySelector('.in-app-notif-panel');
        if (panel) panel.classList.remove('border-primary', 'border-opacity-25');
        return true;
    }

    function markItemUnreadDom(link) {
        if (!link) return;
        link.dataset.unread = '1';
        link.classList.remove('opacity-75');
        var panel = link.querySelector('.in-app-notif-panel');
        if (panel) panel.classList.add('border-primary', 'border-opacity-25');
    }

    function cancelSync() {
        if (state.syncAbort) {
            state.syncAbort.abort();
            state.syncAbort = null;
        }
        state.syncing = false;
    }

    function postForm(url) {
        var body = new URLSearchParams();
        body.append('_token', cfg.csrf);
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': cfg.csrf,
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: body.toString(),
        }).then(function (res) {
            return res.text().then(function (text) {
                var json = null;
                try {
                    json = text ? JSON.parse(text) : null;
                } catch (e) {
                    throw new Error(cfg.strings.markFailed);
                }
                if (!res.ok || !json || json.ok === false) {
                    throw new Error((json && json.message) || cfg.strings.markFailed);
                }
                return json;
            });
        });
    }

    function syncFeed(options) {
        options = options || {};
        if (!cfg.feedUrl) return Promise.resolve();
        if (state.mutating && !options.force) return Promise.resolve();
        if (state.syncing) return Promise.resolve();

        cancelSync();
        state.syncing = true;
        state.syncAbort = new AbortController();

        return fetch(cfg.feedUrl, {
            credentials: 'same-origin',
            signal: state.syncAbort.signal,
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (res) { return res.ok ? res.json() : Promise.reject(); })
            .then(function (data) {
                if (state.mutating) return data;
                if (typeof data.unread_count !== 'undefined') setUnreadCount(data.unread_count);
                if (data.notifications) renderList(data.notifications);
                if (data.sidebar_badges) updateSidebarBadges(data.sidebar_badges);
                return data;
            })
            .catch(function (err) {
                if (err && err.name === 'AbortError') return;
            })
            .finally(function () {
                state.syncing = false;
                state.syncAbort = null;
            });
    }

    function markOneRead(id, link) {
        cancelSync();
        state.mutating = true;
        var prev = state.unread;
        var changed = markItemReadDom(link);
        if (changed) setUnreadCount(prev - 1);

        return postForm(cfg.markReadBase + '/' + id + '/read')
            .then(function (data) {
                if (typeof data.unread_count !== 'undefined') setUnreadCount(data.unread_count);
                if (data.sidebar_badges) updateSidebarBadges(data.sidebar_badges);
                return data;
            })
            .catch(function () {
                if (changed) {
                    markItemUnreadDom(link);
                    setUnreadCount(prev);
                }
                throw new Error(cfg.strings.markFailed);
            })
            .finally(function () {
                state.mutating = false;
            });
    }

    function markAllRead() {
        cancelSync();
        state.mutating = true;
        var prev = state.unread;
        var $ = els();
        if ($.list) {
            $.list.querySelectorAll('.in-app-notif-link[data-unread="1"]').forEach(markItemReadDom);
        }
        setUnreadCount(0);
        updateSidebarBadges({});

        return postForm(cfg.markAllUrl)
            .then(function (data) {
                if (typeof data.unread_count !== 'undefined') setUnreadCount(data.unread_count);
                if (data.sidebar_badges) updateSidebarBadges(data.sidebar_badges);
                return data;
            })
            .catch(function () {
                setUnreadCount(prev);
                return syncFeed({ force: true });
            })
            .finally(function () {
                state.mutating = false;
            });
    }

    function bindEvents() {
        document.addEventListener('click', function (e) {
            var markAllBtn = e.target.closest('#markAllNotifications');
            if (markAllBtn) {
                e.preventDefault();
                e.stopPropagation();
                markAllRead();
                return;
            }

            var link = e.target.closest('.in-app-notif-link');
            if (!link) return;

            var id = link.dataset.id;
            var href = link.getAttribute('href') || '#';
            var isUnread = link.dataset.unread === '1';
            if (!id || !isUnread) return;

            e.preventDefault();
            e.stopPropagation();

            markOneRead(id, link)
                .finally(function () {
                    if (href && href !== '#') window.location.assign(href);
                });
        }, true);

        var root = els().root;
        if (root) {
            root.addEventListener('show.bs.dropdown', function () {
                cancelSync();
            });
        }

        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible' && !state.mutating) {
                syncFeed({ force: true });
            }
        });
    }

    function boot() {
        setUnreadCount(cfg.initialCount);
        bindEvents();
    }

    window.DigitexNotifications = {
        setUnreadCount: setUnreadCount,
        sync: syncFeed,
        markRead: markOneRead,
        markAllRead: markAllRead,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>
