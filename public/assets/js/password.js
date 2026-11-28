/*
 * Password helpers (My Account, student / staff registration).
 *   data-password-toggle      adds an eye button that shows / hides the password
 *   data-strength             adds a strength meter and the policy checklist under the box
 *   data-match="#password"    says whether the two passwords match
 * Policy: at least 8 characters, a capital letter, a number and a special character.
 */
(function () {
    const RULES = [
        ['len', 'At least 8 characters', v => v.length >= 8],
        ['upper', 'A capital letter (A–Z)', v => /[A-Z]/.test(v)],
        ['num', 'A number (0–9)', v => /[0-9]/.test(v)],
        ['special', 'A special character (!@#$)', v => /[^A-Za-z0-9]/.test(v)],
    ];
    const LEVELS = [   // [label, css tone]
        ['Too weak', 'weak'], ['Weak', 'weak'], ['Fair', 'fair'], ['Good', 'good'], ['Strong', 'strong'], ['Very strong', 'strong'],
    ];

    // Eye button.
    document.querySelectorAll('input[data-password-toggle]').forEach(function (input) {
        const wrap = document.createElement('div');
        wrap.className = 'pw-wrap';
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);
        input.classList.add('pe-5');
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'pw-eye';
        btn.setAttribute('aria-controls', input.id);
        btn.setAttribute('aria-label', 'Show password');
        btn.innerHTML = '<i class="bi bi-eye"></i>';
        btn.addEventListener('click', function () {
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.firstElementChild.className = 'bi bi-' + (show ? 'eye-slash' : 'eye');
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            input.focus();
        });
        wrap.appendChild(btn);
    });

    // Strength meter + checklist.
    document.querySelectorAll('input[data-strength]').forEach(function (input) {
        const box = document.createElement('div');
        box.className = 'pw-strength';
        box.id = input.id + '-strength';
        box.innerHTML = '<div class="pw-meter" aria-hidden="true"><span></span><span></span><span></span><span></span></div>'
            + '<div class="pw-label" aria-live="polite"></div>'
            + '<ul class="pw-rules">' + RULES.map(r => '<li data-rule="' + r[0] + '"><i class="bi bi-circle"></i>' + r[1] + '</li>').join('') + '</ul>';
        (input.closest('.pw-wrap, .position-relative') || input).insertAdjacentElement('afterend', box);
        input.setAttribute('aria-describedby', box.id);
        const optional = !input.required;

        function check() {
            const v = input.value;
            const met = RULES.filter(r => r[2](v)).length;
            RULES.forEach(function (r) {
                const li = box.querySelector('[data-rule="' + r[0] + '"]'), ok = r[2](v);
                li.classList.toggle('ok', ok);
                li.firstElementChild.className = 'bi bi-' + (ok ? 'check-circle-fill' : 'circle');
            });
            // Score 0–5: the four rules, plus one for a long password with lower-case letters too.
            let score = met + (met === 4 && v.length >= 12 && /[a-z]/.test(v) ? 1 : 0);
            if (v === '') { score = 0; }
            const [label, tone] = LEVELS[score];
            box.dataset.tone = v === '' ? '' : tone;
            box.querySelectorAll('.pw-meter span').forEach((s, i) => s.classList.toggle('on', v !== '' && i < Math.min(4, Math.max(1, score))));
            box.querySelector('.pw-label').textContent = v === '' ? 'Password strength' : 'Strength: ' + label;
            // Block the form until the policy is met (a blank optional box is fine).
            input.setCustomValidity(met === 4 || (optional && v === '') ? '' : 'Use at least 8 characters with a capital letter, a number and a special character.');
        }
        input.addEventListener('input', check);
        check();
    });

    // "Confirm password" match hint.
    document.querySelectorAll('input[data-match]').forEach(function (input) {
        const first = document.querySelector(input.dataset.match);
        const hint = document.createElement('div');
        hint.className = 'pw-match small mt-1';
        hint.setAttribute('aria-live', 'polite');
        (input.closest('.pw-wrap') || input).insertAdjacentElement('afterend', hint);
        function check() {
            if (input.value === '') { hint.textContent = ''; input.setCustomValidity(''); return; }
            const same = input.value === first.value;
            hint.innerHTML = same ? '<i class="bi bi-check-circle-fill"></i> Passwords match' : '<i class="bi bi-x-circle-fill"></i> Passwords do not match';
            hint.className = 'pw-match small mt-1 ' + (same ? 'text-success' : 'text-danger');
            input.setCustomValidity(same ? '' : 'The passwords do not match.');
        }
        input.addEventListener('input', check);
        first.addEventListener('input', check);
    });
})();
