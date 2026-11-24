<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?>Borrowing &amp; Returning<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-4">
    <div class="col-xl-4">
        <div class="panel">
            <h2 class="panel-title"><i class="bi bi-box-arrow-up-right"></i> Issue a book</h2>
            <form method="post" action="<?= site_url('circulation/issue') ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="user_id">Student</label>
                    <input class="form-control form-control-sm mb-1" type="search" placeholder="Type to filter by name or student no." data-filter="user_id" aria-label="Filter students">
                    <select class="form-select" id="user_id" name="user_id" size="5" required>
                        <?php foreach ($students as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= (string) old('user_id') === (string) $s['id'] ? 'selected' : '' ?>><?= esc($s['name']) ?> (<?= esc($s['id_number']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="book_id">Book</label>
                    <input class="form-control form-control-sm mb-1" type="search" placeholder="Type to filter by title or ISBN" data-filter="book_id" aria-label="Filter books">
                    <select class="form-select" id="book_id" name="book_id" size="6" required>
                        <?php foreach ($books as $b): ?>
                            <option value="<?= $b['id'] ?>" data-search="<?= esc(strtolower($b['title'] . ' ' . $b['isbn'] . ' ' . $b['author'])) ?>" <?= (string) old('book_id') === (string) $b['id'] ? 'selected' : '' ?>>
                                <?= esc($b['title']) ?> — <?= (int) $b['available'] > 0 ? (int) $b['available'] . ' available' : 'none free' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Due date: <?= fmt_date(date('Y-m-d', strtotime('+' . (int) setting('loan_days') . ' days'))) ?> (<?= (int) setting('loan_days') ?>-day loan). Students holding a reservation can collect a "none free" book.</div>
                </div>
                <button class="btn btn-nook w-100" type="submit"><i class="bi bi-check2 me-1"></i>Issue book</button>
            </form>
        </div>
        <div class="panel small text-muted">
            <h3 class="panel-title small mb-2"><i class="bi bi-info-circle"></i> Borrowing rules</h3>
            <ul class="mb-0 ps-3">
                <li>Up to <?= (int) setting('max_loans') ?> books per student.</li>
                <li>Students with overdue books can't borrow more.</li>
                <?php if (setting('block_unpaid_fines') === '1'): ?><li>Students with unpaid fines can't borrow.</li><?php endif; ?>
                <li>Late fee: <?= money(setting('fine_per_day')) ?>/day<?= (float) setting('max_overdue_fine') > 0 ? ', max ' . money(setting('max_overdue_fine')) : '' ?>.</li>
            </ul>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="panel">
            <nav class="tabs-nook">
                <?php foreach (['active' => ['On loan', $counts['active']], 'overdue' => ['Overdue', $counts['overdue']], 'returned' => ['Returned', null], 'lost' => ['Lost', null], 'all' => ['All', null]] as $key => [$label, $n]): ?>
                    <a class="<?= $filters['status'] === $key ? 'active' : '' ?>" href="<?= site_url('circulation?status=' . $key) ?>"><?= $label ?><?php if ($n !== null): ?><span class="count"><?= $n ?></span><?php endif; ?></a>
                <?php endforeach; ?>
            </nav>
            <form class="filters" method="get">
                <input type="hidden" name="status" value="<?= esc($filters['status']) ?>">
                <input class="form-control form-control-sm" type="search" name="q" value="<?= esc($filters['q']) ?>" placeholder="Book, ISBN or student" aria-label="Search">
                <input class="form-control form-control-sm" type="date" name="from" value="<?= esc($filters['from']) ?>" aria-label="Borrowed from" style="max-width:150px">
                <input class="form-control form-control-sm" type="date" name="to" value="<?= esc($filters['to']) ?>" aria-label="Borrowed to" style="max-width:150px">
                <button class="btn btn-sm btn-nook"><i class="bi bi-search"></i></button>
            </form>

            <?php if (! $loans): ?>
                <div class="empty"><i class="bi bi-inbox"></i>No loans to show.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-nook">
                        <thead><tr><th>Book</th><th>Student</th><th>Borrowed</th><th>Due</th><th>Status</th><th class="text-end"></th></tr></thead>
                        <tbody>
                        <?php foreach ($loans as $l): ?>
                            <tr>
                                <td><?= esc($l['title']) ?><div class="sub"><?= esc($l['isbn']) ?></div></td>
                                <td><a class="text-reset" href="<?= site_url('members/' . $l['user_id']) ?>"><?= esc($l['borrower_name']) ?></a><div class="sub"><?= esc($l['borrower_id_number']) ?></div></td>
                                <td><?= fmt_date($l['borrowed_at']) ?><div class="sub">by <?= esc($l['issued_by_name'] ?? '—') ?></div></td>
                                <td><?= fmt_date($l['due_date']) ?><?php if ($l['returned_at']): ?><div class="sub">Back <?= fmt_date($l['returned_at']) ?></div><?php endif; ?></td>
                                <td><?= status_badge($l['status'], $l['due_date']) ?>
                                    <?php if ($l['days_late'] > 0): ?><div class="sub"><?= $l['days_late'] ?>d late · <?= money($l['est_fine']) ?></div><?php endif; ?>
                                    <?php if ($l['condition'] === 'damaged'): ?><div class="sub text-danger">Damaged</div><?php endif; ?></td>
                                <td class="actions">
                                    <?php if ($l['status'] === 'borrowed'): ?>
                                        <button class="btn btn-sm btn-nook" type="button" data-bs-toggle="modal" data-bs-target="#returnModal"
                                                data-action="<?= site_url('circulation/' . $l['id'] . '/return') ?>"
                                                data-title="<?= esc($l['title']) ?>" data-student="<?= esc($l['borrower_name']) ?>"
                                                data-late="<?= $l['days_late'] > 0 ? $l['days_late'] . ' day(s) late — overdue fine ' . money($l['est_fine']) . ' will be charged.' : 'On time — no overdue fine.' ?>">
                                            Return</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?= $pager->links('default', 'nook') ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="returnModal" tabindex="-1" aria-labelledby="returnTitle" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post" id="returnForm">
            <?= csrf_field() ?>
            <div class="modal-header"><h2 class="modal-title fs-5" id="returnTitle">Return book</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <p class="mb-1"><strong data-field="title"></strong></p>
                <p class="small text-muted mb-3">Borrowed by <span data-field="student"></span>. <span data-field="late"></span></p>
                <label class="form-label">Condition</label>
                <div class="mb-3">
                    <?php foreach (['good' => 'Good', 'damaged' => 'Damaged', 'lost' => 'Lost (not returned)'] as $v => $label): ?>
                        <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="condition" id="c-<?= $v ?>" value="<?= $v ?>" <?= $v === 'good' ? 'checked' : '' ?>><label class="form-check-label" for="c-<?= $v ?>"><?= $label ?></label></div>
                    <?php endforeach; ?>
                </div>
                <div class="mb-3 d-none" id="damageFee"><label class="form-label" for="damage_fee">Damage fee</label>
                    <input class="form-control" type="number" step="0.01" min="0" id="damage_fee" name="damage_fee" value="<?= esc(setting('damaged_book_fee')) ?>"></div>
                <div class="small text-danger mb-3 d-none" id="lostNote">A lost book fee of <?= money(setting('lost_book_fee')) ?> will be charged and one copy removed from inventory.</div>
                <label class="form-label" for="notes">Notes</label>
                <input class="form-control" id="notes" name="notes" maxlength="255" placeholder="Optional">
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button><button class="btn btn-nook">Confirm return</button></div>
        </form>
    </div></div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Type-to-filter for the long student / book lists.
    document.querySelectorAll('[data-filter]').forEach(function (input) {
        const select = document.getElementById(input.dataset.filter);
        input.addEventListener('input', function () {
            const q = input.value.trim().toLowerCase();
            Array.from(select.options).forEach(function (o) {
                o.hidden = q !== '' && !(o.dataset.search || o.text.toLowerCase()).includes(q) && !o.text.toLowerCase().includes(q);
            });
        });
    });
    // Fill the return dialog from the clicked row.
    document.getElementById('returnModal').addEventListener('show.bs.modal', function (e) {
        const b = e.relatedTarget, f = document.getElementById('returnForm');
        f.action = b.dataset.action;
        ['title', 'student', 'late'].forEach(k => f.querySelector('[data-field="' + k + '"]').textContent = b.dataset[k]);
        f.reset(); toggle();
    });
    function toggle() {
        const v = document.querySelector('#returnForm input[name="condition"]:checked').value;
        document.getElementById('damageFee').classList.toggle('d-none', v !== 'damaged');
        document.getElementById('lostNote').classList.toggle('d-none', v !== 'lost');
    }
    document.querySelectorAll('#returnForm input[name="condition"]').forEach(r => r.addEventListener('change', toggle));
</script>
<?= $this->endSection() ?>
