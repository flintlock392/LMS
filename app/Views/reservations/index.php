<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?>Reservations<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-4">
    <div class="col-xl-4">
        <div class="panel">
            <h2 class="panel-title"><i class="bi bi-bookmark-plus"></i> Reserve for a student</h2>
            <form method="post" action="<?= site_url('reservations/create') ?>" id="reserveForm">
                <?= csrf_field() ?>

                <!-- 1. Student: suggestive search by name or student number -->
                <div class="mb-3 suggest">
                    <label class="form-label" for="student-search">Student</label>
                    <div class="position-relative">
                        <i class="bi bi-search suggest-icon" aria-hidden="true"></i>
                        <input class="form-control ps-5" type="text" id="student-search" autocomplete="off"
                               placeholder="Type a name or student number"
                               role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="student-results" aria-describedby="student-help">
                        <ul class="suggest-list" id="student-results" role="listbox" hidden></ul>
                    </div>
                    <input type="hidden" name="user_id" id="user_id" required>
                    <div id="student-help" class="form-text">Suggestions appear as you type. Use ↑ ↓ and Enter to choose.</div>
                    <div class="student-chip mt-2" id="student-chip" hidden></div>
                </div>

                <!-- 2. Narrow the book list -->
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label" for="genre">Genre</label>
                        <select class="form-select form-select-sm" id="genre">
                            <option value="">All genres</option>
                            <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"><?= esc($c['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="course">Course</label>
                        <select class="form-select form-select-sm" id="course">
                            <option value="">All courses</option>
                            <option value="__general">General reading</option>
                            <?php foreach ($courses as $c): ?><option value="<?= esc($c) ?>"><?= esc($c) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- 3. Book -->
                <div class="mb-3">
                    <label class="form-label" for="book_id">Book <span class="text-muted fw-normal small" id="book-count"></span></label>
                    <select class="form-select" id="book_id" name="book_id" size="7" required>
                        <?php foreach ($books as $b): ?>
                            <option value="<?= $b['id'] ?>" data-genre="<?= esc((string) $b['category_id']) ?>" data-course="<?= esc((string) ($b['course'] ?? '')) ?>">
                                <?= esc($b['title']) ?> — <?= (int) $b['available'] > 0 ? (int) $b['available'] . ' available' : 'on loan' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text" id="book-empty" hidden>No books match this genre and course.</div>
                </div>
                <button class="btn btn-nook w-100">Place reservation</button>
            </form>
        </div>
        <div class="panel small text-muted">
            <h3 class="panel-title small mb-2"><i class="bi bi-info-circle"></i> How reservations work</h3>
            <ol class="ps-3 mb-0">
                <li>Students join a queue for a title (max <?= (int) setting('max_reservations') ?> each).</li>
                <li>When a copy is free, the first student in line becomes <em>Ready for pickup</em>.</li>
                <li>The copy is held for <?= (int) setting('hold_days') ?> day(s); after that it passes to the next student.</li>
            </ol>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="panel">
            <nav class="tabs-nook">
                <?php foreach (['open' => ['Open', $counts['pending'] + $counts['ready']], 'ready' => ['Ready for pickup', $counts['ready']], 'pending' => ['In queue', $counts['pending']], 'closed' => ['Closed', null], 'all' => ['All', null]] as $key => [$label, $n]): ?>
                    <a class="<?= $filters['status'] === $key ? 'active' : '' ?>" href="<?= site_url('reservations?status=' . $key) ?>"><?= $label ?><?php if ($n !== null): ?><span class="count"><?= $n ?></span><?php endif; ?></a>
                <?php endforeach; ?>
            </nav>
            <form class="filters" method="get">
                <input type="hidden" name="status" value="<?= esc($filters['status']) ?>">
                <input class="form-control form-control-sm" type="search" name="q" value="<?= esc($filters['q']) ?>" placeholder="Book or student" aria-label="Search">
                <button class="btn btn-sm btn-nook"><i class="bi bi-search"></i></button>
            </form>
            <?php if (! $rows): ?>
                <div class="empty"><i class="bi bi-bookmark"></i>No reservations to show.</div>
            <?php else: ?>
                <div class="table-responsive"><table class="table table-nook">
                    <thead><tr><th>Book</th><th>Student</th><th>Reserved</th><th>Status</th><th class="text-end"></th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= esc($r['title']) ?><div class="sub"><?= esc($r['author']) ?></div></td>
                            <td><?= esc($r['borrower_name']) ?><div class="sub"><?= esc($r['borrower_id_number']) ?></div></td>
                            <td><?= fmt_date($r['reserved_at'], true) ?></td>
                            <td><?= status_badge($r['status']) ?>
                                <?php if ($r['status'] === 'pending'): ?><div class="sub">#<?= $r['position'] ?> in line</div><?php endif; ?>
                                <?php if ($r['status'] === 'ready'): ?><div class="sub">Hold until <?= fmt_date($r['expires_at'], true) ?></div><?php endif; ?></td>
                            <td class="actions">
                                <?php if ($r['status'] === 'ready'): ?>
                                    <form class="d-inline" method="post" action="<?= site_url('reservations/' . $r['id'] . '/issue') ?>"><?= csrf_field() ?>
                                        <button class="btn btn-sm btn-nook">Issue</button></form>
                                <?php endif; ?>
                                <?php if (in_array($r['status'], ['pending', 'ready'], true)): ?>
                                    <form class="d-inline" method="post" action="<?= site_url('reservations/' . $r['id'] . '/cancel') ?>" data-confirm="Cancel this reservation?"><?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-danger">Cancel</button></form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
                <?= $pager->links('default', 'nook') ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    const input   = document.getElementById('student-search');
    const list    = document.getElementById('student-results');
    const hidden  = document.getElementById('user_id');
    const chip    = document.getElementById('student-chip');
    const form    = document.getElementById('reserveForm');
    const genre   = document.getElementById('genre');
    const course  = document.getElementById('course');
    const books   = document.getElementById('book_id');
    const endpoint = <?= json_encode(site_url('reservations/students')) ?>;
    let items = [], active = -1, timer = null, lastQuery = '';

    const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    // Bold the part of the text that matches what was typed.
    const mark = (text, q) => {
        const i = text.toLowerCase().indexOf(q.toLowerCase());
        return i < 0 ? esc(text) : esc(text.slice(0, i)) + '<mark>' + esc(text.slice(i, i + q.length)) + '</mark>' + esc(text.slice(i + q.length));
    };

    function close() { list.hidden = true; input.setAttribute('aria-expanded', 'false'); active = -1; input.removeAttribute('aria-activedescendant'); }

    function render(q) {
        if (!items.length) {
            list.innerHTML = '<li class="suggest-empty">No student matches “' + esc(q) + '”.</li>';
        } else {
            list.innerHTML = items.map((s, i) =>
                '<li role="option" id="opt-' + i + '" data-i="' + i + '" aria-disabled="' + (s.suspended ? 'true' : 'false') + '" class="' + (s.suspended ? 'disabled' : '') + '">' +
                    '<span class="avatar-sm">' + esc(s.name.charAt(0)) + '</span>' +
                    '<span class="flex-grow-1"><strong>' + mark(s.name, q) + '</strong>' +
                    '<span class="d-block small text-muted">' + mark(s.idNumber, q) + (s.course ? ' · ' + esc(s.course) : '') + '</span></span>' +
                    (s.suspended ? '<span class="pill pill-danger">Suspended</span>'
                        : s.atLimit ? '<span class="pill pill-warning">' + s.open + ' reserved (limit)</span>'
                        : (s.open ? '<span class="pill pill-secondary">' + s.open + ' reserved</span>' : '')) +
                '</li>').join('');
        }
        list.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    }

    function highlight(i) {
        const opts = list.querySelectorAll('[role="option"]');
        opts.forEach(o => o.classList.remove('active'));
        if (i >= 0 && opts[i]) {
            opts[i].classList.add('active');
            opts[i].scrollIntoView({ block: 'nearest' });
            input.setAttribute('aria-activedescendant', opts[i].id);
        }
        active = i;
    }

    function choose(i) {
        const s = items[i];
        if (!s || s.suspended) { return; }
        hidden.value = s.id;
        input.value = s.name + ' (' + s.idNumber + ')';
        chip.innerHTML = '<i class="bi bi-person-check me-1"></i><strong>' + esc(s.name) + '</strong> · ' + esc(s.idNumber) +
            (s.course ? '<br><span class="text-muted">' + esc(s.course) + (s.year ? ', ' + esc(s.year) : '') + '</span>' : '') +
            '<br><span class="text-muted">' + s.loans + ' book(s) on loan · ' + s.open + ' open reservation(s)</span>';
        chip.hidden = false;
        close();
        // Show books for the student's course first (they can switch back to "All courses").
        if (s.course && Array.from(course.options).some(o => o.value === s.course)) {
            course.value = s.course;
            filterBooks();
        }
    }

    function search() {
        const q = input.value.trim();
        if (q === lastQuery) { return; }
        lastQuery = q;
        if (q.length < 1) { items = []; close(); return; }
        fetch(endpoint + '?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => { if (input.value.trim() === q) { items = data; render(q); highlight(items.length ? 0 : -1); } })
            .catch(() => { list.innerHTML = '<li class="suggest-empty">Could not load students. Try again.</li>'; list.hidden = false; });
    }

    input.addEventListener('input', function () {
        hidden.value = '';          // typing again clears the previous choice
        chip.hidden = true;
        clearTimeout(timer);
        timer = setTimeout(search, 200);
    });
    input.addEventListener('keydown', function (e) {
        if (list.hidden) { return; }
        if (e.key === 'ArrowDown') { e.preventDefault(); highlight(Math.min(active + 1, items.length - 1)); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); highlight(Math.max(active - 1, 0)); }
        else if (e.key === 'Enter') { e.preventDefault(); if (active >= 0) { choose(active); } }
        else if (e.key === 'Escape') { close(); }
    });
    list.addEventListener('mousedown', function (e) {
        const li = e.target.closest('[role="option"]');
        if (li) { e.preventDefault(); choose(+li.dataset.i); }
    });
    input.addEventListener('blur', () => setTimeout(close, 150));
    input.addEventListener('focus', () => { if (items.length && !hidden.value && input.value.trim()) { render(input.value.trim()); } });

    form.addEventListener('submit', function (e) {
        if (!hidden.value) {
            e.preventDefault();
            input.classList.add('is-invalid');
            input.focus();
            document.getElementById('student-help').textContent = 'Choose a student from the suggestions first.';
        }
    });

    // Genre + course drop-downs narrow the book list.
    function filterBooks() {
        const g = genre.value, c = course.value;
        let shown = 0;
        Array.from(books.options).forEach(o => {
            const okGenre  = !g || o.dataset.genre === g;
            const okCourse = !c || (c === '__general' ? o.dataset.course === '' : o.dataset.course === c);
            o.hidden = !(okGenre && okCourse);
            if (o.hidden && o.selected) { o.selected = false; }
            if (!o.hidden) { shown++; }
        });
        document.getElementById('book-count').textContent = '(' + shown + ' of ' + books.options.length + ')';
        document.getElementById('book-empty').hidden = shown > 0;
    }
    genre.addEventListener('change', filterBooks);
    course.addEventListener('change', filterBooks);
    filterBooks();
})();
</script>
<?= $this->endSection() ?>
