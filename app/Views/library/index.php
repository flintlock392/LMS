<?php
use App\Controllers\Library;
use App\Models\InventoryLogModel;

$tabs = [
    'all'         => 'All',
    'collections' => 'Collections',
    'borrowed'    => 'Borrowed',
    'overdue'     => 'Overdue',
    'returned'    => 'Returned',
    'lost'        => 'Lost',
    'books'       => 'Books',
];
// Build a link that keeps the search and date range when switching tabs.
$url = static function (array $extra = []) use ($tab, $q, $from, $to): string {
    $params = array_filter(array_merge(['tab' => $tab, 'q' => $q, 'from' => $from, 'to' => $to], $extra), static fn ($v) => $v !== '' && $v !== null);

    return site_url('library') . '?' . http_build_query($params);
};
$isLoanTab = ! in_array($tab, ['collections', 'books'], true);
?>
<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?>Library<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
    <?php if (is_head()): ?>
        <a class="btn btn-outline-nook" href="<?= site_url('activity') ?>"><i class="bi bi-clock-history me-1"></i>Activity Log</a>
        <button class="btn btn-outline-nook" type="button" data-bs-toggle="modal" data-bs-target="#stockModal"><i class="bi bi-arrow-repeat me-1"></i>Update Stock</button>
    <?php endif; ?>
    <button class="btn btn-outline-nook" type="button" data-bs-toggle="modal" data-bs-target="#issueModal"><i class="bi bi-box-arrow-up-right me-1"></i>Issue book</button>
    <a class="btn btn-nook" href="<?= site_url('books/new') ?>"><i class="bi bi-plus-lg me-1"></i>Add book</a>
</div>

<div class="panel">
    <nav class="tabs-nook library-tabs" aria-label="Library sections">
        <?php foreach ($tabs as $key => $label): ?>
            <a class="<?= $tab === $key ? 'active' : '' ?>" href="<?= $url(['tab' => $key, 'category_id' => null]) ?>"<?= $tab === $key ? ' aria-current="page"' : '' ?>>
                <?= $label ?><span class="count<?= $key === 'overdue' && $counts['overdue'] > 0 ? ' count-bad' : '' ?>"><?= $counts[$key] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Search + date range -->
    <form class="date-range mb-2" method="get" data-range-form>
        <input type="hidden" name="tab" value="<?= esc($tab) ?>">
        <div class="field">
            <label for="q">Search</label>
            <input class="form-control form-control-sm search-box" type="search" id="q" name="q" value="<?= esc($q) ?>" data-suggest="<?= $isLoanTab ? 'loans' : 'books' ?>"
                   placeholder="<?= $isLoanTab ? 'Book, ISBN or student' : 'Title, author or ISBN' ?>">
        </div>
        <?php if ($tab === 'collections' || $tab === 'books'): ?>
            <div class="field">
                <label for="category_id">Category</label>
                <select class="form-select form-select-sm" id="category_id" name="category_id">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $category === (string) $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div class="field">
            <label for="from">Start date</label>
            <input class="form-control form-control-sm" type="date" id="from" name="from" value="<?= esc($from) ?>" max="<?= date('Y-m-d') ?>" aria-describedby="range-help">
        </div>
        <span class="arrow" aria-hidden="true"><i class="bi bi-arrow-right"></i></span>
        <div class="field">
            <label for="to">End date</label>
            <input class="form-control form-control-sm" type="date" id="to" name="to" value="<?= esc($to) ?>" max="<?= date('Y-m-d') ?>" aria-describedby="range-help">
        </div>
        <button class="btn btn-sm btn-nook"><i class="bi bi-funnel me-1"></i>Apply</button>
        <?php if ($q !== '' || $from !== '' || $to !== '' || $category !== ''): ?>
            <a class="btn btn-sm btn-link" href="<?= site_url('library?tab=' . $tab) ?>">Clear</a>
        <?php endif; ?>
    </form>
    <p id="range-help" class="form-text mt-0 mb-3">
        <i class="bi bi-info-circle"></i>
        Pick a <strong>start date</strong> and an <strong>end date</strong> to show only items
        <strong><?= esc(Library::DATE_MEANING[$tab]) ?></strong> on or between those days (both days included).
        Leave the end date empty for "up to today", or the start date empty for "from the beginning".
        <?php if (is_head()): ?>With both dates set, every action taken in the system during that period is listed at the bottom.<?php endif; ?>
    </p>
    <?php if ($rangeError): ?>
        <div class="alert alert-warning py-2 small"><i class="bi bi-exclamation-triangle me-1"></i><?= esc($rangeError) ?></div>
    <?php endif; ?>
    <div class="filter-summary"><i class="bi bi-funnel"></i><span><?= esc($summary) ?></span></div>

    <?php if ($isLoanTab): ?>
        <!-- ===== Loan records: All / Borrowed / Overdue / Returned / Lost ===== -->
        <?php if (! $rows): ?>
            <div class="empty"><i class="bi bi-inbox"></i>No records to show.</div>
        <?php else: ?>
            <div class="table-responsive"><table class="table table-nook">
                <thead><tr><th>Book</th><th>Student</th><th>Borrowed</th><th>Due</th><th>Status</th><th class="text-end"></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $l): ?>
                    <tr>
                        <td><a class="text-reset" href="<?= site_url('books/' . $l['book_id']) ?>"><?= esc($l['title']) ?></a><div class="sub"><?= esc($l['isbn']) ?></div></td>
                        <td><a class="text-reset" href="<?= site_url('members/' . $l['user_id']) ?>"><?= esc($l['borrower_name']) ?></a><div class="sub"><?= esc($l['borrower_id_number']) ?></div></td>
                        <td><?= fmt_date($l['borrowed_at']) ?><div class="sub">by <?= esc($l['issued_by_name'] ?? '—') ?></div></td>
                        <td><?= fmt_date($l['due_date']) ?><?php if ($l['returned_at']): ?><div class="sub"><?= $l['status'] === 'lost' ? 'Reported' : 'Back' ?> <?= fmt_date($l['returned_at']) ?></div><?php endif; ?></td>
                        <td><?= status_badge($l['status'], $l['due_date']) ?>
                            <?php if ($l['days_late'] > 0): ?><div class="sub"><?= $l['days_late'] ?>d late · <?= money($l['est_fine']) ?></div><?php endif; ?>
                            <?php if ($l['condition'] === 'damaged'): ?><div class="sub text-danger">Damaged</div><?php endif; ?></td>
                        <td class="actions">
                            <?php if ($l['status'] === 'borrowed'): ?>
                                <button class="btn btn-sm btn-nook" type="button" data-bs-toggle="modal" data-bs-target="#returnModal"
                                        data-action="<?= site_url('circulation/' . $l['id'] . '/return') ?>"
                                        data-title="<?= esc($l['title']) ?>" data-student="<?= esc($l['borrower_name']) ?>"
                                        data-late="<?= $l['days_late'] > 0 ? $l['days_late'] . ' day(s) late — overdue fine ' . money($l['est_fine']) . ' will be charged.' : 'On time — no overdue fine.' ?>">Return</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
            <?= $pager->links('default', 'nook') ?>
        <?php endif; ?>

    <?php elseif ($tab === 'collections'): ?>
        <!-- ===== Collections: the catalog ===== -->
        <?php if (is_head()): ?>
            <div class="d-flex justify-content-end mb-2"><a class="btn btn-sm btn-outline-nook" href="<?= site_url('categories') ?>"><i class="bi bi-tags me-1"></i>Manage categories</a></div>
        <?php endif; ?>
        <?php if (! $rows): ?>
            <div class="empty"><i class="bi bi-search"></i>No titles match.</div>
        <?php else: ?>
            <div class="table-responsive"><table class="table table-nook">
                <thead><tr><th>Title</th><th>Author</th><th>Category</th><th>Shelf</th><th>Added</th><th>Availability</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $b): ?>
                    <tr>
                        <td><a class="fw-semibold text-reset" href="<?= site_url('books/' . $b['id']) ?>"><?= esc($b['title']) ?></a><div class="sub">ISBN <?= esc($b['isbn']) ?></div></td>
                        <td><?= esc($b['author']) ?></td>
                        <td><?= esc($b['category_name'] ?? '—') ?></td>
                        <td><?= esc($b['shelf_location'] ?? '—') ?></td>
                        <td class="sub"><?= fmt_date($b['created_at']) ?></td>
                        <td><?= availability_badge((int) $b['available'], (int) $b['stock_count']) ?>
                            </td>
                        <td class="actions">
                            <a class="btn btn-sm btn-outline-nook" href="<?= site_url('books/' . $b['id']) ?>" title="View"><i class="bi bi-eye"></i></a>
                            <a class="btn btn-sm btn-outline-nook" href="<?= site_url('books/' . $b['id'] . '/edit') ?>" title="Edit"><i class="bi bi-pencil"></i></a>
                            <?php if (is_head()): ?>
                                <form class="d-inline" method="post" action="<?= site_url('books/' . $b['id'] . '/delete') ?>" data-confirm="Delete &quot;<?= esc($b['title']) ?>&quot;? This cannot be undone.">
                                    <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
            <?= $pager->links('default', 'nook') ?>
        <?php endif; ?>

    <?php else: ?>
        <!-- ===== Books: stock levels (formerly Inventory) ===== -->
        <div class="row g-4">
            <div class="col-12">
                <div class="d-flex flex-wrap gap-3 small text-muted mb-2">
                    <span><strong class="text-body"><?= $stats['copies'] ?></strong> copies owned</span>
                    <span><strong class="text-body"><?= $stats['available'] ?></strong> on the shelf</span>
                    <span><strong class="text-body"><?= $stats['on_loan'] ?></strong> on loan</span>
                    <span><strong class="text-body"><?= $stats['unavailable'] ?></strong> title(s) unavailable</span>
                </div>
                <?php if (! is_head()): ?><p class="small text-muted">View only. Ask the Head Librarian to add or remove copies.</p><?php endif; ?>
                <?php if (! $rows): ?>
                    <div class="empty"><i class="bi bi-box-seam"></i>No titles match.</div>
                <?php else: ?>
                    <div class="table-responsive"><table class="table table-nook">
                        <thead><tr><th>Title</th><th>Shelf</th><th class="text-center">Owned</th><th class="text-center">On loan</th><th>On the shelf</th></tr></thead>
                        <tbody><?php foreach ($rows as $b): ?>
                            <tr>
                                <td><a class="text-reset" href="<?= site_url('books/' . $b['id']) ?>"><?= esc($b['title']) ?></a><div class="sub"><?= esc($b['isbn']) ?></div></td>
                                <td><?= esc($b['shelf_location'] ?? '—') ?></td>
                                <td class="text-center"><?= (int) $b['stock_count'] ?></td>
                                <td class="text-center"><?= (int) $b['on_loan'] ?></td>
                                <td><?= availability_badge((int) $b['available'], (int) $b['stock_count']) ?>
                                    </td>
                            </tr>
                        <?php endforeach; ?></tbody>
                    </table></div>
                    <?= $pager->links('default', 'nook') ?>
                <?php endif; ?>
            </div>
        </div>

        <section class="history-card mt-4" aria-labelledby="history-title">
            <h3 class="panel-title mb-1" id="history-title"><i class="bi bi-clock-history"></i> Stock history</h3>
            <p class="history-desc">
                Every change to the number of copies: when it happened, which title, how many copies were added or removed,
                the reason, and who made the change.
                <?= $from !== '' || $to !== ''
                    ? 'Only changes made ' . ($from !== '' && $to !== '' ? 'from ' . fmt_date($from) . ' to ' . fmt_date($to) : ($from !== '' ? 'on or after ' . fmt_date($from) : 'on or before ' . fmt_date($to))) . ' are listed.'
                    : 'Newest changes first.' ?>
            </p>
            <?php if (! $log): ?>
                <div class="empty"><i class="bi bi-inbox"></i>No stock changes in this period.</div>
            <?php else: ?>
                <div class="table-responsive"><table class="table table-nook history-table">
                    <colgroup><col style="width:16%"><col style="width:30%"><col style="width:15%"><col style="width:25%"><col style="width:14%"></colgroup>
                    <thead><tr><th>Date</th><th>Title</th><th>Change</th><th>Reason</th><th>Changed by</th></tr></thead>
                    <tbody><?php foreach ($log as $e): $up = $e['change'] > 0; ?>
                        <tr>
                            <td><?= fmt_date($e['created_at']) ?><div class="sub"><?= date('g:i A', strtotime($e['created_at'])) ?></div></td>
                            <td><a class="text-reset fw-medium" href="<?= site_url('books/' . $e['book_id']) ?>"><?= esc($e['title']) ?></a></td>
                            <td>
                                <span class="pill <?= $up ? 'pill-success' : 'pill-danger' ?>"><i class="bi bi-<?= $up ? 'plus' : 'dash' ?>"></i><?= abs((int) $e['change']) ?> cop<?= abs((int) $e['change']) === 1 ? 'y' : 'ies' ?></span>
                                <div class="sub mt-1">Total after: <?= (int) $e['stock_after'] ?></div>
                            </td>
                            <td>
                                <div class="fw-medium"><?= esc(InventoryLogModel::LABELS[$e['reason']] ?? $e['reason']) ?></div>
                                <div class="sub"><?= $e['notes'] ? esc($e['notes']) : '<span class="opacity-50">No description</span>' ?></div>
                            </td>
                            <td class="sub"><?= esc($e['user_name'] ?? 'System') ?></td>
                        </tr>
                    <?php endforeach; ?></tbody>
                </table></div>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                    <span class="small text-muted"><?= pager_info($logPager, 'history', 'changes') ?></span>
                    <div class="ms-auto"><?= $logPager->links('history', 'nook') ?></div>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<?php if ($activity !== null): ?>
    <div class="panel">
        <h2 class="panel-title"><i class="bi bi-clock-history"></i> Everything that happened from <?= fmt_date($from) ?> to <?= fmt_date($to) ?>
            <a class="ms-auto small" href="<?= site_url('activity?from=' . $from . '&to=' . $to) ?>">Open full Activity Log (<?= $activityTotal ?>)</a></h2>
        <?= view('activity/_list', ['rows' => $activity]) ?>
        <?php if ($activity): ?>
            <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                <span class="small text-muted"><?= pager_info($activityPager, 'activity', 'actions') ?></span>
                <div class="ms-auto"><?= $activityPager->links('activity', 'nook') ?></div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (is_head()): ?>
<!-- Update Stock → Adjust copies -->
<div class="modal fade" id="stockModal" tabindex="-1" aria-labelledby="stockTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px"><div class="modal-content adjust-modal">
        <form method="post" action="<?= site_url('inventory/adjust') ?>">
            <?= csrf_field() ?>
            <div class="modal-header border-0 pb-0">
                <h2 class="modal-title panel-title mb-0" id="stockTitle"><i class="bi bi-sliders"></i> Adjust copies</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label" for="book_id_s">Title</label>
                    <select class="form-select" id="book_id_s" name="book_id" required>
                        <option value="">Choose…</option>
                        <?php foreach ($allBooks as $b): ?><option value="<?= $b['id'] ?>" <?= (string) old('book_id') === (string) $b['id'] ? 'selected' : '' ?>><?= esc($b['title']) ?></option><?php endforeach; ?>
                    </select></div>
                <div class="mb-3">
                    <div class="btn-group w-100" role="group" aria-label="Add or remove copies">
                        <input type="radio" class="btn-check" name="direction" id="dir-add" value="add" <?= old('direction', 'add') === 'add' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-success" for="dir-add"><i class="bi bi-plus-lg"></i> Add copies</label>
                        <input type="radio" class="btn-check" name="direction" id="dir-remove" value="remove" <?= old('direction') === 'remove' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-danger" for="dir-remove"><i class="bi bi-dash-lg"></i> Remove copies</label>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-4"><label class="form-label" for="quantity">Qty</label>
                        <input class="form-control" type="number" id="quantity" name="quantity" min="1" max="1000" value="<?= esc(old('quantity', '1', false)) ?>" required></div>
                    <div class="col-8"><label class="form-label" for="reason">Reason</label>
                        <select class="form-select" id="reason" name="reason" required>
                            <optgroup label="Add" data-dir="add"><?php foreach (InventoryLogModel::ADD_REASONS as $k => $v): ?><option value="<?= $k ?>" <?= old('reason') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></optgroup>
                            <optgroup label="Remove" data-dir="remove"><?php foreach (InventoryLogModel::REMOVE_REASONS as $k => $v): ?><option value="<?= $k ?>" <?= old('reason') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></optgroup>
                            <option value="correction" <?= old('reason') === 'correction' ? 'selected' : '' ?>>Count correction</option>
                        </select></div>
                </div>
                <div class="mb-3"><label class="form-label" for="notes">Notes</label>
                    <input class="form-control" id="notes" name="notes" maxlength="255" placeholder="e.g. PO #1234, water damage" value="<?= esc(old('notes', '', false)) ?>"></div>
                <button class="btn btn-nook w-100">Save change</button>
                <p class="form-text mb-0 mt-2">Only copies on the shelf can be removed. Lost loans are removed automatically when returned as "Lost".</p>
            </div>
        </form>
    </div></div>
</div>
<?php endif; ?>

<!-- Issue a book -->
<div class="modal fade" id="issueModal" tabindex="-1" aria-labelledby="issueTitle" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post" action="<?= site_url('circulation/issue') ?>">
            <?= csrf_field() ?>
            <div class="modal-header"><h2 class="modal-title fs-5" id="issueTitle">Issue a book</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="user_id">Student</label>
                    <input class="form-control form-control-sm mb-1" type="search" placeholder="Type to filter by name or student no." data-filter="user_id" aria-label="Filter students">
                    <select class="form-select" id="user_id" name="user_id" size="5" required>
                        <?php foreach ($students as $s): ?><option value="<?= $s['id'] ?>"><?= esc($s['name']) ?> (<?= esc($s['id_number']) ?>)</option><?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label" for="book_id">Book</label>
                    <input class="form-control form-control-sm mb-1" type="search" placeholder="Type to filter by title or ISBN" data-filter="book_id" aria-label="Filter books">
                    <select class="form-select" id="book_id" name="book_id" size="6" required>
                        <?php foreach ($bookList as $b): ?>
                            <option value="<?= $b['id'] ?>" data-search="<?= esc(strtolower($b['title'] . ' ' . $b['isbn'] . ' ' . $b['author'])) ?>"><?= esc($b['title']) ?> — <?= (int) $b['available'] > 0 ? (int) $b['available'] . ' available' : 'none free' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <p class="form-text mb-0">Due back <?= fmt_date(date('Y-m-d', strtotime('+' . (int) setting('loan_days') . ' days'))) ?> (<?= (int) setting('loan_days') ?>-day loan). Max <?= (int) setting('max_loans') ?> books per student; students with overdue books<?= setting('block_unpaid_fines') === '1' ? ' or unpaid fines' : '' ?> can't borrow.</p>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button><button class="btn btn-nook"><i class="bi bi-check2 me-1"></i>Issue book</button></div>
        </form>
    </div></div>
</div>

<!-- Return a book -->
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
                <div class="small text-danger mb-3 d-none" id="lostNote">A lost book fee of <?= money(setting('lost_book_fee')) ?> will be charged and one copy removed from stock.</div>
                <label class="form-label" for="r-notes">Notes</label>
                <input class="form-control" id="r-notes" name="notes" maxlength="255" placeholder="Optional">
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button><button class="btn btn-nook">Confirm return</button></div>
        </form>
    </div></div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= view('partials/date_range_js') ?>
<script>
    // Type-to-filter for the student / book lists in the Issue dialog.
    document.querySelectorAll('[data-filter]').forEach(function (input) {
        const select = document.getElementById(input.dataset.filter);
        input.addEventListener('input', function () {
            const q = input.value.trim().toLowerCase();
            Array.from(select.options).forEach(o => { o.hidden = q !== '' && !(o.dataset.search || o.text.toLowerCase()).includes(q) && !o.text.toLowerCase().includes(q); });
        });
    });
    // Return dialog.
    const rm = document.getElementById('returnModal');
    rm.addEventListener('show.bs.modal', function (e) {
        const b = e.relatedTarget, f = document.getElementById('returnForm');
        f.reset();
        f.action = b.dataset.action;
        ['title', 'student', 'late'].forEach(k => f.querySelector('[data-field="' + k + '"]').textContent = b.dataset[k]);
        toggleCondition();
    });
    function toggleCondition() {
        const v = document.querySelector('#returnForm input[name="condition"]:checked').value;
        document.getElementById('damageFee').classList.toggle('d-none', v !== 'damaged');
        document.getElementById('lostNote').classList.toggle('d-none', v !== 'lost');
    }
    document.querySelectorAll('#returnForm input[name="condition"]').forEach(r => r.addEventListener('change', toggleCondition));
    // If a stock change was rejected (e.g. too many copies removed), reopen the dialog with the entries kept.
    <?php if (is_head() && old('quantity') !== null): ?>
    document.addEventListener('DOMContentLoaded', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('stockModal')).show());
    <?php endif; ?>
    // Update Stock: only show reasons that match Add / Remove.
    const reason = document.getElementById('reason');
    if (reason) {
        const sync = function () {
            const dir = document.querySelector('input[name="direction"]:checked').value;
            reason.querySelectorAll('optgroup').forEach(g => { g.hidden = g.dataset.dir !== dir; });
            const cur = reason.options[reason.selectedIndex];
            if (!cur || (cur.parentElement.dataset.dir && cur.parentElement.dataset.dir !== dir)) {
                reason.value = reason.querySelector('optgroup[data-dir="' + dir + '"] option').value;
            }
        };
        document.querySelectorAll('input[name="direction"]').forEach(r => r.addEventListener('change', sync));
        sync();
    }
</script>
<?= $this->endSection() ?>
