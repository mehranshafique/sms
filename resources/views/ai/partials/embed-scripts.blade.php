<script>
(function () {
    if (window.DigitexAI) return;

    var cfg = {
        url: @json(route('ai.embed.run')),
        csrf: @json(csrf_token()),
        pageTitle: @json(trim($__env->yieldContent('title') ?: (View::getSections()['content'] ?? ''))),
        routeName: @json(Route::currentRouteName()),
        strings: {
            working: @json(__('ai.generating')),
            error: @json(__('ai.error_generic')),
            confirmApply: @json(__('ai.confirm_apply')),
            confirmSend: @json(__('ai.confirm_send')),
        }
    };

    function post(tool, params) {
        return fetch(cfg.url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': cfg.csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ tool: tool, params: params || {} })
        }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); });
    }

    function setLoading(btn, on) {
        if (!btn) return;
        btn.classList.toggle('is-loading', on);
        if (on) btn.dataset.origHtml = btn.innerHTML;
        else if (btn.dataset.origHtml) btn.innerHTML = btn.dataset.origHtml;
    }

    function fillTarget(selector, text) {
        if (!selector || !text) return;
        var el = document.querySelector(selector);
        if (!el) return;
        if (el.tagName === 'TEXTAREA' || el.tagName === 'INPUT') {
            el.value = text;
            el.dispatchEvent(new Event('input', { bubbles: true }));
        } else {
            el.textContent = text;
        }
    }

    function showPanel(btn, text) {
        var panelSel = btn.getAttribute('data-ai-panel');
        var panel = panelSel ? document.querySelector(panelSel) : btn.parentElement.querySelector('.ai-embed-panel');
        if (!panel) return;
        panel.textContent = text;
        panel.classList.add('is-visible');
    }

    window.DigitexAI = {
        run: function (tool, params) {
            var p = Object.assign({}, params || {});
            if (!p.page_title) p.page_title = document.title;
            if (!p.route_name) p.route_name = cfg.routeName;
            return post(tool, p).then(function (res) {
                if (!res.ok || !res.body.ok) {
                    throw new Error((res.body && res.body.message) || cfg.strings.error);
                }
                return res.body;
            });
        },
        confirmThen: function (message, fn) {
            if (typeof Swal !== 'undefined') {
                return Swal.fire({
                    title: message,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes',
                }).then(function (r) { if (r.isConfirmed) fn(); });
            }
            if (confirm(message)) fn();
        }
    };

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.ai-embed-btn');
        if (!btn) return;
        e.preventDefault();
        var tool = btn.getAttribute('data-ai-tool');
        var params = {};
        try { params = JSON.parse(btn.getAttribute('data-ai-params') || '{}'); } catch (err) {}
        try {
            var fields = JSON.parse(btn.getAttribute('data-ai-fields') || '{}');
            Object.keys(fields).forEach(function (k) {
                var el = document.querySelector(fields[k]);
                if (el && el.value !== undefined) params[k] = el.value;
            });
        } catch (err2) {}
        if (tool === 'draft_notice' && !params.topic) {
            var titleEl = document.querySelector('input[name=title]');
            if (titleEl && titleEl.value) params.topic = titleEl.value;
        }
        if (tool === 'translate' && !params.text) {
            var contentEl = document.querySelector('textarea[name=content]');
            if (contentEl) params.text = contentEl.value;
        }
        setLoading(btn, true);
        DigitexAI.run(tool, params).then(function (body) {
            var text = body.content || '';
            var target = btn.getAttribute('data-ai-target');
            if (target) {
                if (btn.getAttribute('data-ai-confirm') === '1' && typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: cfg.strings.confirmApply,
                        html: '<div style="text-align:left;white-space:pre-wrap;font-size:.9rem">' + (body.html || text) + '</div>',
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: 'Apply',
                    }).then(function (r) { if (r.isConfirmed) fillTarget(target, text); });
                } else {
                    fillTarget(target, text);
                }
            }
            showPanel(btn, text);
            btn.dispatchEvent(new CustomEvent('ai:done', { detail: body, bubbles: true }));
        }).catch(function (err) {
            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', text: err.message || cfg.strings.error });
            else alert(err.message || cfg.strings.error);
        }).finally(function () { setLoading(btn, false); });
    });

    // Floating widget
    var fab = document.getElementById('ai-fab-toggle');
    var panel = document.getElementById('ai-fab-panel');
    var closeBtn = document.getElementById('ai-fab-close');
    var input = document.getElementById('ai-fab-input');
    var sendBtn = document.getElementById('ai-fab-send');
    var thread = document.getElementById('ai-fab-thread');

    function appendMsg(text, role) {
        if (!thread) return;
        var div = document.createElement('div');
        div.className = 'ai-fab-msg ' + (role === 'user' ? 'user' : 'bot');
        div.textContent = text;
        thread.appendChild(div);
        thread.scrollTop = thread.scrollHeight;
    }

    function sendFab() {
        if (!input || !input.value.trim()) return;
        var msg = input.value.trim();
        input.value = '';
        appendMsg(msg, 'user');
        appendMsg('…', 'bot');
        var pending = thread.lastChild;
        DigitexAI.run('quick_chat', {
            message: msg,
            page_title: document.title,
            route_name: cfg.routeName
        }).then(function (body) {
            pending.textContent = body.content || '';
        }).catch(function (err) {
            pending.textContent = err.message || cfg.strings.error;
        });
    }

    if (fab && panel) {
        fab.addEventListener('click', function () {
            panel.classList.toggle('is-open');
            panel.setAttribute('aria-hidden', panel.classList.contains('is-open') ? 'false' : 'true');
        });
    }
    if (closeBtn && panel) {
        closeBtn.addEventListener('click', function () {
            panel.classList.remove('is-open');
            panel.setAttribute('aria-hidden', 'true');
        });
    }
    if (sendBtn) sendBtn.addEventListener('click', sendFab);
    if (input) input.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); sendFab(); } });
})();
</script>
