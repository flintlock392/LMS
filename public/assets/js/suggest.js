/**
 * Suggestive search for filter boxes.
 *
 *   <input type="search" name="q" data-suggest="students">
 *
 * As the user types, matching records are fetched from  <body data-suggest-url>?scope=…&q=…
 * and shown under the box. Choosing one (click, or ↑ ↓ + Enter) puts its value in the box
 * and submits the box's form, so the list below filters to that record.
 * Pressing Enter without choosing simply searches for what was typed.
 */
(function () {
    'use strict';

    const endpoint = document.body.dataset.suggestUrl;
    if (!endpoint) { return; }

    const ICONS = { student: 'mortarboard', staff: 'person-badge', book: 'book' };
    const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const mark = (text, q) => {
        const i = String(text).toLowerCase().indexOf(q.toLowerCase());
        return i < 0 ? esc(text) : esc(text.slice(0, i)) + '<mark>' + esc(text.slice(i, i + q.length)) + '</mark>' + esc(text.slice(i + q.length));
    };

    document.querySelectorAll('input[data-suggest]').forEach(function (input, n) {
        const scope = input.dataset.suggest;
        const form = input.form;

        // Wrap the box so the list can sit right under it.
        const wrap = document.createElement('div');
        wrap.className = 'suggest-wrap';
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);

        const list = document.createElement('ul');
        list.className = 'suggest-list';
        list.id = 'suggest-' + n;
        list.setAttribute('role', 'listbox');
        list.hidden = true;
        wrap.appendChild(list);

        input.setAttribute('autocomplete', 'off');
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-expanded', 'false');
        input.setAttribute('aria-controls', list.id);

        let items = [], active = -1, timer = null, controller = null;

        function close() {
            list.hidden = true;
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
            active = -1;
        }

        function highlight(i) {
            const opts = list.querySelectorAll('[role="option"]');
            opts.forEach(o => o.classList.remove('active'));
            active = i;
            if (i >= 0 && opts[i]) {
                opts[i].classList.add('active');
                opts[i].scrollIntoView({ block: 'nearest' });
                input.setAttribute('aria-activedescendant', opts[i].id);
            } else {
                input.removeAttribute('aria-activedescendant');
            }
        }

        function render(q) {
            list.innerHTML = items.length
                ? items.map((it, i) =>
                    '<li role="option" id="' + list.id + '-' + i + '" data-i="' + i + '">' +
                        '<span class="suggest-ico"><i class="bi bi-' + (ICONS[it.type] || 'search') + '"></i></span>' +
                        '<span class="flex-grow-1 min-w-0"><strong>' + mark(it.label, q) + '</strong>' +
                        '<span class="d-block small text-muted text-truncate">' + mark(it.sub, q) + '</span></span>' +
                        '<span class="suggest-type">' + esc(it.type) + '</span>' +
                    '</li>').join('')
                : '<li class="suggest-empty">No matches for “' + esc(q) + '”. Press Enter to search anyway.</li>';
            list.hidden = false;
            input.setAttribute('aria-expanded', 'true');
            active = -1;
        }

        function choose(i) {
            const it = items[i];
            if (!it) { return; }
            input.value = it.value;
            close();
            if (form) { form.submit(); }
        }

        function fetchSuggestions() {
            const q = input.value.trim();
            if (q.length < 1) { items = []; close(); return; }
            if (controller) { controller.abort(); }
            controller = new AbortController();
            fetch(endpoint + '?scope=' + encodeURIComponent(scope) + '&q=' + encodeURIComponent(q), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal,
            })
                .then(r => (r.ok ? r.json() : []))
                .then(data => { if (input.value.trim() === q && document.activeElement === input) { items = data; render(q); } })
                .catch(() => {});
        }

        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(fetchSuggestions, 180);
        });
        input.addEventListener('keydown', function (e) {
            if (list.hidden) { return; }
            if (e.key === 'ArrowDown') { e.preventDefault(); highlight(Math.min(active + 1, items.length - 1)); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); highlight(Math.max(active - 1, -1)); }
            else if (e.key === 'Enter' && active >= 0) { e.preventDefault(); choose(active); }
            else if (e.key === 'Escape') { close(); }
        });
        list.addEventListener('mousedown', function (e) {
            const li = e.target.closest('[role="option"]');
            if (li) { e.preventDefault(); choose(+li.dataset.i); }
        });
        input.addEventListener('blur', () => setTimeout(close, 150));
    });
})();
