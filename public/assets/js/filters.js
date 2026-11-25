/*
 * Filter forms (Library, Browse Books, Credit Management, User Management...).
 *
 * Results only change when the user clicks Apply / Search. Picking a category,
 * course or other filter just highlights the Apply button as a reminder.
 */
(function () {
    function setup(form) {
        const apply = form.querySelector('[data-apply]') || form.querySelector('button:not([type="button"])');
        if (!apply) { return; }

        function markPending(e) {
            if (!e.target.name || e.target.type === 'hidden') { return; }
            apply.classList.add('needs-apply');
            apply.setAttribute('title', 'Click to show results with your filters');
        }

        form.addEventListener('change', markPending);
        form.addEventListener('input', function (e) {
            if (e.target.matches('input[type="search"], input[type="text"]')) { markPending(e); }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[data-filter-form]').forEach(setup);
    });
})();
