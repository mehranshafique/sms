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

            aiLabel: @json(__('ai.widget_title')),

            applySchedule: @json(__('ai.apply_schedule')),

            applyTimetable: @json(__('ai.apply_timetable')),
            selectClassFirst: @json(__('ai.select_class_first')),
            overrideTimetable: @json(__('ai.override_timetable')),
            overrideTimetableConfirm: @json(__('ai.override_timetable_confirm')),
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



    function escapeHtml(s) {

        return String(s || '')

            .replace(/&/g, '&amp;')

            .replace(/</g, '&lt;')

            .replace(/>/g, '&gt;')

            .replace(/"/g, '&quot;');

    }



    function formatAiContent(text) {

        var raw = String(text || '').trim();

        if (!raw) return '';



        var escaped = escapeHtml(raw);

        escaped = escaped.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');



        var lines = escaped.split(/\r\n|\r|\n/);

        var parts = [];

        var inList = false;



        function closeList() {

            if (inList) { parts.push('</ul>'); inList = false; }

        }



        lines.forEach(function (line) {

            var trimmed = line.trim();

            if (!trimmed) {

                closeList();

                return;

            }

            var bullet = trimmed.match(/^[\*\-•]\s+(.+)$/);

            var numbered = trimmed.match(/^\d+[\.\)]\s+(.+)$/);

            if (bullet) {

                if (!inList) { parts.push('<ul class="ai-output-list">'); inList = true; }

                parts.push('<li>' + bullet[1] + '</li>');

            } else if (numbered) {

                if (!inList) { parts.push('<ul class="ai-output-list">'); inList = true; }

                parts.push('<li>' + numbered[1] + '</li>');

            } else {

                closeList();

                parts.push('<p class="ai-output-p">' + trimmed + '</p>');

            }

        });

        closeList();



        return '<div class="ai-output-view">' +

            '<div class="ai-output-view__head"><i class="la la-magic"></i> ' + escapeHtml(cfg.strings.aiLabel || 'Digitex AI') + '</div>' +

            '<div class="ai-output-view__body">' + parts.join('') + '</div></div>';

    }



    function fillTarget(selector, text) {

        if (!selector || !text) return;

        var el = document.querySelector(selector);

        if (!el) return;

        if (el.tagName === 'TEXTAREA' || el.tagName === 'INPUT') {

            el.value = text;

            el.dispatchEvent(new Event('input', { bubbles: true }));

        } else if (el.classList.contains('ai-embed-panel') || el.classList.contains('ai-fab-msg')) {

            el.innerHTML = formatAiContent(text);

            el.classList.add('is-visible');

        } else {

            el.innerHTML = formatAiContent(text);

        }

    }



    function showPanel(btn, text) {

        var panelSel = btn.getAttribute('data-ai-panel');

        var panel = panelSel ? document.querySelector(panelSel) : btn.parentElement.querySelector('.ai-embed-panel');

        if (!panel) return;

        panel.innerHTML = formatAiContent(text);

        panel.classList.add('is-visible');

    }



    function getSelectValue(el) {
        if (!el) return '';
        if (typeof jQuery !== 'undefined' && jQuery(el).is('select') && jQuery.fn.selectpicker) {
            var val = jQuery(el).selectpicker('val');
            if (Array.isArray(val)) return val[0] || '';
            return val || el.value || '';
        }
        return el.value || '';
    }

    function resolveFieldValue(selectors) {
        var parts = String(selectors || '').split(',');
        for (var i = 0; i < parts.length; i++) {
            var el = document.querySelector(parts[i].trim());
            if (!el) continue;
            var val = getSelectValue(el);
            if (val !== undefined && val !== null && val !== '') {
                return val;
            }
        }
        return null;
    }



    function collectLiveMarks() {

        var rows = document.querySelectorAll('#student_table_body tr.s-row');

        if (!rows.length) return [];

        var out = [];

        rows.forEach(function (row) {

            var input = row.querySelector('.mark-input');

            var absent = row.querySelector('.abs-check');

            var nameEl = row.querySelector('.s-name');

            if (!input) return;

            var m = input.name.match(/marks\[(\d+)\]/);

            if (!m) return;

            out.push({

                student_id: parseInt(m[1], 10),

                name: nameEl ? nameEl.textContent.trim() : '',

                marks: absent && absent.checked ? null : (input.value !== '' ? parseFloat(input.value) : null),

                is_absent: !!(absent && absent.checked)

            });

        });

        return out;

    }



    function applyExamSchedule(schedule) {

        if (!schedule) return;

        Object.keys(schedule).forEach(function (subjectId) {

            var item = schedule[subjectId];

            var dateInput = document.querySelector('input[name="schedules[' + subjectId + '][date]"]');

            var startInput = document.querySelector('input[name="schedules[' + subjectId + '][start_time]"]');

            var endInput = document.querySelector('input[name="schedules[' + subjectId + '][end_time]"]');

            var roomInput = document.querySelector('input[name="schedules[' + subjectId + '][room_number]"]');

            if (dateInput) {

                dateInput.value = item.date;

                if (typeof jQuery !== 'undefined' && jQuery(dateInput).bootstrapMaterialDatePicker) {

                    jQuery(dateInput).bootstrapMaterialDatePicker('setDate', item.date);

                }

            }

            if (startInput) startInput.value = item.start_time;

            if (endInput) endInput.value = item.end_time;

            if (roomInput && item.room_number) roomInput.value = item.room_number;

        });

    }



    window.DigitexAI = {

        formatContent: formatAiContent,

        applyExamSchedule: applyExamSchedule,

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

                var val = resolveFieldValue(fields[k]);

                if (val !== null) params[k] = val;

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

        if (tool === 'exam_at_risk') {

            var live = collectLiveMarks();

            if (live.length) params.live_marks = live;

            var subjSelect = document.getElementById('subject_select');

            if (subjSelect && subjSelect.value) {

                params.subject_name = subjSelect.options[subjSelect.selectedIndex].text;

            }

        }

        if (tool === 'generate_exam_datesheet') {
            var periodEl = document.getElementById('ai_period_days');
            if (periodEl && periodEl.value) params.period_days = parseInt(periodEl.value, 10);
        }
        if (tool === 'generate_timetable') {
            var classId = resolveFieldValue('#filter_class');
            if (!classId) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', text: cfg.strings.selectClassFirst || 'Select a class first.' });
                } else {
                    alert(cfg.strings.selectClassFirst || 'Select a class first.');
                }
                return;
            }
        }

        setLoading(btn, true);

        DigitexAI.run(tool, params).then(function (body) {

            var text = body.content || '';

            var target = btn.getAttribute('data-ai-target');

            if (target) {

                if (btn.getAttribute('data-ai-confirm') === '1' && typeof Swal !== 'undefined') {

                    Swal.fire({

                        title: cfg.strings.confirmApply,

                        html: '<div style="text-align:left;font-size:.9rem;max-height:50vh;overflow-y:auto">' + formatAiContent(text) + '</div>',

                        icon: 'info',

                        showCancelButton: true,

                        confirmButtonText: 'Apply',

                    }).then(function (r) { if (r.isConfirmed) fillTarget(target, text); });

                } else {

                    fillTarget(target, text);

                }

            }

            showPanel(btn, text);



            var meta = body.meta || {};

            var schedule = meta.schedule || body.meta && body.meta.schedule;

            if (tool === 'generate_exam_datesheet' && meta.schedule) {

                if (typeof Swal !== 'undefined') {

                    Swal.fire({

                        title: cfg.strings.applySchedule,

                        text: cfg.strings.confirmApply,

                        icon: 'question',

                        showCancelButton: true,

                        confirmButtonText: 'Apply',

                    }).then(function (r) {

                        if (r.isConfirmed) applyExamSchedule(meta.schedule);

                    });

                } else {

                    applyExamSchedule(meta.schedule);

                }

            }



            btn.dispatchEvent(new CustomEvent('ai:done', { detail: body, bubbles: true }));

        }).catch(function (err) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', text: err.message || cfg.strings.error })
                    .then(function () {
                        document.dispatchEvent(new CustomEvent('ai:error', { detail: { message: err.message }, bubbles: true }));
                    });
            } else {
                alert(err.message || cfg.strings.error);
                document.dispatchEvent(new CustomEvent('ai:error', { detail: { message: err.message }, bubbles: true }));
            }
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

            pending.innerHTML = formatAiContent(body.content || '');

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

