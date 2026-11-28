/**
 * Suggestive search for filter boxes.
 *
 *   <input type="search" name="q" data-suggest="students">
 *
 * As the user types, matching records are fetched from  <body data-suggest-url>?scope=…&q=…
 * and shown under the box. Choosing one (click, or ↑ ↓ + Enter) puts its value in the box
 * and submits the box's form, so the list below filters to that record.
 * Pressing Enter without choosing simply searches for what was typed.
 *
 * Options:
 *   data-suggest-by="selectId"     send that <select>'s value as &by=… (e.g. "Search by: Student number"),
 *                                  and use its option's data-placeholder as the box's placeholder.
 *   data-suggest-pick="hiddenId"   pick mode: choosing a suggestion stores its id in that hidden input
 *                                  (instead of submitting); the form can't be sent until one is chosen.
 *   data-suggest-url="…"           ask this address instead of <body data-suggest-url> (e.g. the public catalog).
 */
(function () {
    'use strict';

    // Address for suggestions: the box's own data-suggest-url, or the page's <body data-suggest-url>.
    const defaultEndpoint = document.body.dataset.suggestUrl || '';

    const ICONS = { student: 'mortarboard', staff: 'person-badge', book: 'book' };
    const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const mark = (text, q) => {
        const i = String(text).toLowerCase().indexOf(q.toLowerCase());
        return i < 0 ? esc(text) : esc(text.slice(0, i)) + '<mark>' + esc(text.slice(i, i + q.length)) + '</mark>' + esc(text.slice(i + q.length));
    };

    document.querySelectorAll('input[data-suggest]').forEach(function (input, n) {
        const scope = input.dataset.suggest;
        const endpoint = input.dataset.suggestUrl || defaultEndpoint;
        if (!endpoint) { return; }
        const form = input.form;
        const bySelect = input.dataset.suggestBy ? document.getElementById(input.dataset.suggestBy) : null;
        const pickInput = input.dataset.suggestPick ? document.getElementById(input.dataset.suggestPick) : null;

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
            close();
            if (pickInput) {
                pickInput.value = it.id;
                input.value = it.pick || it.label;
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
                pickInput.dispatchEvent(new CustomEvent('suggest:pick', { detail: it }));   // e.g. Credit Management reads it.credits
                return;
            }
            input.value = it.value;
            if (form) { form.submit(); }
        }

        if (pickInput && form) {
            form.addEventListener('submit', function (e) {
                if (!pickInput.value) {
                    e.preventDefault();
                    input.classList.add('is-invalid');
                    input.focus();
                }
            });
        }

        if (bySelect) {
            const syncPlaceholder = function () {
                const opt = bySelect.options[bySelect.selectedIndex];
                if (opt && opt.dataset.placeholder) { input.placeholder = opt.dataset.placeholder; }
            };
            bySelect.addEventListener('change', function () {
                syncPlaceholder();
                items = [];
                close();
                input.focus();
                if (input.value.trim()) { fetchSuggestions(); }
            });
            syncPlaceholder();
        }

        function fetchSuggestions() {
            const q = input.value.trim();
            if (q.length < 1) { items = []; close(); return; }
            if (controller) { controller.abort(); }
            controller = new AbortController();
            const by = bySelect ? '&by=' + encodeURIComponent(bySelect.value) : '';
            fetch(endpoint + '?scope=' + encodeURIComponent(scope) + by + '&q=' + encodeURIComponent(q), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal,
            })
                .then(r => (r.ok ? r.json() : []))
                .then(data => { if (input.value.trim() === q && document.activeElement === input) { items = data; render(q); } })
                .catch(() => {});
        }

        input.addEventListener('input', function () {
            if (pickInput) {           // typing again clears the earlier choice
                if (pickInput.value) { pickInput.dispatchEvent(new CustomEvent('suggest:pick', { detail: null })); }
                pickInput.value = '';
                input.classList.remove('is-valid');
            }
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
