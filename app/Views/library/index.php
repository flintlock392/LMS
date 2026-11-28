<?php
use App\Controllers\Library;
use App\Models\InventoryLogModel;

$tabs = [
    'all'         => 'All',
    'requested'   => 'Requested',
    'collections' => 'Collections',
    'borrowed'    => 'Borrowed',
    'overdue'     => 'Overdue',
    'returned'    => 'Returned',
    'lost'        => 'Lost',
];
// Build a link that keeps the search and date range when switching tabs.
$url = static function (array $extra = []) use ($tab, $q, $from, $to, $course, $category, $shelf): string {
    $params = array_filter(array_merge(['tab' => $tab, 'q' => $q, 'course' => $course, 'category_id' => $category, 'shelf' => $shelf, 'from' => $from, 'to' => $to], $extra), static fn ($v) => $v !== '' && $v !== null);

    return site_url('library') . '?' . http_build_query($params);
};
$isLoanTab = in_array($tab, ['all', 'borrowed', 'overdue', 'returned', 'lost'], true);
$isBookTab = $tab === 'collections';
?>
<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?>Library<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
    <?php if (is_head()): ?>
        <a class="btn btn-outline-nook" href="<?= site_url('activity') ?>"><i class="bi bi-clock-history me-1"></i>Activity Log</a>
        <a class="btn btn-outline-nook" href="<?= site_url('archive') ?>"><i class="bi bi-archive me-1"></i>Archive</a>
    <?php endif; ?>
    <!-- Head Librarian and Librarian Staff can both add or remove copies -->
    <button class="btn btn-outline-nook" type="button" data-bs-toggle="modal" data-bs-target="#stockModal"><i class="bi bi-arrow-repeat me-1"></i>Update Stock</button>
    <button class="btn btn-outline-nook" type="button" data-bs-toggle="modal" data-bs-target="#issueModal"><i class="bi bi-box-arrow-up-right me-1"></i>Issue book</button>
    <a class="btn btn-nook" href="<?= site_url('books/new') ?>"><i class="bi bi-plus-lg me-1"></i>Add book</a>
</div>

<div class="panel">
    <nav class="tabs-nook library-tabs" aria-label="Library sections">
        <?php foreach ($tabs as $key => $label): ?>
            <a class="<?= $tab === $key ? 'active' : '' ?>" href="<?= $url(['tab' => $key]) ?>"<?= $tab === $key ? ' aria-current="page"' : '' ?>>
                <?= $label ?><span class="count<?= in_array($key, ['overdue', 'requested'], true) && $counts[$key] > 0 ? ' count-bad' : '' ?>"><?= $counts[$key] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Search on its own row (top left), then the filters and date range on one line -->
    <form class="date-range mb-2" method="get" data-range-form data-filter-form>
        <input type="hidden" name="tab" value="<?= esc($tab) ?>">
        <div class="field field-search">
            <label for="q">Search</label>
            <div class="search-icon">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input class="form-control form-control-sm search-box" type="search" id="q" name="q" value="<?= esc($q) ?>" data-suggest="<?= $isBookTab ? 'books' : 'loans' ?>"
                       placeholder="<?= $isBookTab ? 'Search by title, author or ISBN' : 'Search by book, ISBN or student' ?>">
            </div>
        </div>
            <div class="field">
                <label for="category_id">Category</label>
                <select class="form-select form-select-sm category-filter" id="category_id" name="category_id">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $category === (string) $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="course">Course</label>
                <select class="form-select form-select-sm course-filter" id="course" name="course">
                    <option value="">All courses</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= esc($c) ?>" <?= $course === $c ? 'selected' : '' ?>><?= esc($c) ?></option>
                    <?php endforeach; ?>
                    <option value="__general" <?= $course === '__general' ? 'selected' : '' ?>>General reading (includes other courses)</option>
                </select>
            </div>
            <div class="field">
                <label for="shelf">Shelf</label>
                <select class="form-select form-select-sm shelf-filter" id="shelf" name="shelf">
                    <option value="">All shelves</option>
                    <?php foreach ($shelves as $section => $list): ?>
                        <optgroup label="Section <?= esc($section) ?>">
                            <?php if (count($list) > 1): ?>
                                <option value="section:<?= esc($section) ?>" <?= $shelf === 'section:' . $section ? 'selected' : '' ?>>Section <?= esc($section) ?> (all <?= count($list) ?> shelves)</option>
                            <?php endif; ?>
                            <?php foreach ($list as $s): ?>
                                <option value="<?= esc($s) ?>" <?= $shelf === $s ? 'selected' : '' ?>><?= esc($s) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>
        <div class="date-pair">
        <div class="field">
            <label for="from">Start date</label>
            <input class="form-control form-control-sm" type="date" id="from" name="from" value="<?= esc($from) ?>" max="<?= date('Y-m-d') ?>" aria-describedby="range-help">
        </div>
        <span class="arrow" aria-hidden="true"><i class="bi bi-arrow-right"></i></span>
        <div class="field">
            <label for="to">End date</label>
            <input class="form-control form-control-sm" type="date" id="to" name="to" value="<?= esc($to) ?>" max="<?= date('Y-m-d') ?>" aria-describedby="range-help">
        </div>
        </div>
        <button class="btn btn-sm btn-nook" data-apply><i class="bi bi-funnel me-1"></i>Apply</button>
        <?php if ($q !== '' || $from !== '' || $to !== '' || $category !== '' || $course !== '' || $shelf !== ''): ?>
            <a class="btn-clear" href="<?= site_url('library?tab=' . $tab) ?>"><i class="bi bi-x-circle"></i>Clear filters</a>
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

    <?php if ($tab === 'requested'): ?>
        <!-- ===== Requested: students' borrow / return requests waiting for staff ===== -->
        <?php if (! $rows): ?>
            <div class="empty"><i class="bi bi-check2-all"></i>No requests are waiting. Students' "Request to borrow" and "Request return" appear here.</div>
        <?php else: ?>
            <div class="table-responsive"><table class="table table-nook">
                <thead><tr><th>Request</th><th>Book</th><th>Student</th><th>Details</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php $shownGroup = [];
                foreach ($rows as $r): $isBorrow = $r['type'] === 'borrow';
                    $uid = (int) $r['user_id'];
                    if (isset($studentRequests[$uid])):
                        // A student with several requests: one row and a "View requests" pop-up.
                        if (isset($shownGroup[$uid])) { continue; }
                        $shownGroup[$uid] = true;
                        $list    = $studentRequests[$uid];
                        $borrows = count(array_filter($list, static fn ($x) => $x['type'] === 'borrow'));
                        $returns = count($list) - $borrows;
                        $info    = $studentInfo[$uid] ?? null; ?>
                    <tr class="request-group">
                        <td><span class="pill pill-info"><i class="bi bi-stack me-1"></i><?= count($list) ?> requests</span>
                            <div class="sub mt-1">Since <?= fmt_date($list[0]['created_at'], true) ?></div></td>
                        <td><div class="book-cell">
                                <span class="cover-stack"><?php foreach (array_slice($list, 0, 3) as $x): ?><?= book_cover($x, 'cover-sm', 'S') ?><?php endforeach; ?></span>
                                <div class="min-w-0"><div class="text-truncate-2"><?= esc(implode(', ', array_map(static fn ($x) => $x['title'], array_slice($list, 0, 2)))) ?><?= count($list) > 2 ? ' and ' . (count($list) - 2) . ' more' : '' ?></div>
                                    <div class="sub"><?= implode(' · ', array_filter([$borrows ? $borrows . ' to borrow' : '', $returns ? $returns . ' to return' : ''])) ?></div></div>
                            </div></td>
                        <td><a class="text-reset" href="<?= site_url('members/' . $uid) ?>"><?= esc($r['borrower_name']) ?></a>
                            <div class="sub"><?= esc($r['borrower_id_number']) ?><?= $r['student_course'] ? ' · ' . esc($r['student_course']) : '' ?></div></td>
                        <td><?php if ($info): ?>
                                <?= credit_badge((int) $info['credits']) ?>
                                <div class="sub"><?= $info['on_loan'] ?> of <?= $info['limit'] ?> on loan · can take <?= max(0, $info['limit'] - $info['on_loan']) ?> more</div>
                            <?php endif; ?></td>
                        <td class="actions">
                            <button class="btn btn-sm btn-nook" type="button" data-bs-toggle="modal" data-bs-target="#reqModal-<?= $uid ?>">
                                <i class="bi bi-list-check me-1"></i>View requests (<?= count($list) ?>)</button>
                        </td>
                    </tr>
                    <?php continue; endif; ?>
                    <tr>
                        <td><span class="pill <?= $isBorrow ? 'pill-info' : 'pill-warning' ?>"><i class="bi bi-<?= $isBorrow ? 'bookmark-plus' : 'box-arrow-in-down-left' ?> me-1"></i><?= $isBorrow ? 'Borrow' : 'Return' ?></span>
                            <div class="sub mt-1"><?= fmt_date($r['created_at'], true) ?></div></td>
                        <td><div class="book-cell"><?= book_cover($r, 'cover-sm', 'S') ?><div><a class="text-reset" href="<?= site_url('books/' . $r['book_id']) ?>"><?= esc($r['title']) ?></a><div class="sub"><?= esc($r['isbn']) ?></div></div></div></td>
                        <td><a class="text-reset" href="<?= site_url('members/' . $r['user_id']) ?>"><?= esc($r['borrower_name']) ?></a>
                            <div class="sub"><?= esc($r['borrower_id_number']) ?><?= $r['student_course'] ? ' · ' . esc($r['student_course']) : '' ?></div></td>
                        <td>
                            <?php if ($isBorrow): ?>
                                <?= (int) $r['available'] > 0
                                    ? '<span class="pill pill-success">' . (int) $r['available'] . ' on the shelf</span>'
                                    : '<span class="pill pill-danger">None on the shelf</span>' ?>
                                <div class="sub">Due back <?= fmt_date(date('Y-m-d', strtotime('+' . (int) setting('loan_days') . ' days'))) ?> if issued today</div>
                            <?php else: ?>
                                Due <?= fmt_date($r['due_date']) ?>
                                <?php if ($r['days_late'] > 0): ?><div class="sub text-danger"><?= $r['days_late'] ?>d late · −<?= $r['est_credits'] ?> credits</div><?php else: ?><div class="sub">On time</div><?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <?php if ($isBorrow): ?>
                                <form class="d-inline" method="post" action="<?= site_url('requests/' . $r['id'] . '/approve') ?>"
                                      data-confirm="Approve and issue &quot;<?= esc($r['title']) ?>&quot; to <?= esc($r['borrower_name']) ?>?">
                                    <?= csrf_field() ?><button class="btn btn-sm btn-nook"><i class="bi bi-check2 me-1"></i>Approve &amp; issue</button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-sm btn-nook" type="button" data-bs-toggle="modal" data-bs-target="#returnModal"
                                        data-action="<?= site_url('requests/' . $r['id'] . '/receive') ?>"
                                        data-title="<?= esc($r['title']) ?>" data-student="<?= esc($r['borrower_name']) ?>"
                                        data-late="<?= $r['days_late'] > 0 ? $r['days_late'] . ' day(s) late — ' . $r['est_credits'] . ' credit(s) deducted for lateness.' : 'On time — no credits deducted.' ?>">
                                    <i class="bi bi-box-arrow-in-down-left me-1"></i>Receive return</button>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="collapse" data-bs-target="#decline-<?= $r['id'] ?>">Decline</button>
                        </td>
                    </tr>
                    <tr class="collapse" id="decline-<?= $r['id'] ?>"><td colspan="5">
                        <form class="d-flex gap-2" method="post" action="<?= site_url('requests/' . $r['id'] . '/decline') ?>"
                              data-confirm="Decline <?= esc($r['borrower_name']) ?>'s <?= $r['type'] ?> request for &quot;<?= esc($r['title']) ?>&quot;?">
                            <?= csrf_field() ?>
                            <input class="form-control form-control-sm" name="reason" maxlength="200" placeholder="Reason the student will see (optional), e.g. book is being repaired">
                            <button class="btn btn-sm btn-danger text-nowrap">Decline request</button>
                        </form>
                    </td></tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
            <?= $pager->links('default', 'nook') ?>
            <?= view('library/_student_requests', ['studentRequests' => $studentRequests, 'studentInfo' => $studentInfo]) ?>
        <?php endif; ?>

    <?php elseif ($isLoanTab): ?>
        <!-- ===== Loan records: All / Borrowed / Overdue / Returned / Lost ===== -->
        <?php if (! $rows): ?>
            <div class="empty"><i class="bi bi-inbox"></i>No records to show.</div>
        <?php else: ?>
            <div class="table-responsive"><table class="table table-nook">
                <thead><tr><th>Book</th><th>Student</th><th>Borrowed</th><th>Due</th><th>Status</th><th class="text-end"></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $l): ?>
                    <tr>
                        <td><div class="book-cell"><?= book_cover($l, 'cover-sm', 'S') ?><div><a class="text-reset" href="<?= site_url('books/' . $l['book_id']) ?>"><?= esc($l['title']) ?></a><div class="sub"><?= esc($l['isbn']) ?></div></div></div></td>
                        <td><a class="text-reset" href="<?= site_url('members/' . $l['user_id']) ?>"><?= esc($l['borrower_name']) ?></a><div class="sub"><?= esc($l['borrower_id_number']) ?></div></td>
                        <td><?= fmt_date($l['borrowed_at']) ?><div class="sub">by <?= esc($l['issued_by_name'] ?? '—') ?></div></td>
                        <td><?= fmt_date($l['due_date']) ?><?php if ($l['returned_at']): ?><div class="sub"><?= $l['status'] === 'lost' ? 'Reported' : 'Back' ?> <?= fmt_date($l['returned_at']) ?></div><?php endif; ?></td>
                        <td><?= status_badge($l['status'], $l['due_date']) ?>
                            <?php if ($l['days_late'] > 0): ?><div class="sub"><?= $l['days_late'] ?>d late · −<?= $l['est_credits'] ?> credits</div><?php endif; ?>
                            <?php if ($l['condition'] === 'damaged'): ?><div class="sub text-danger">Damaged</div><?php endif; ?></td>
                        <td class="actions">
                            <?php if ($l['status'] === 'borrowed'): ?>
                                <button class="btn btn-sm btn-nook" type="button" data-bs-toggle="modal" data-bs-target="#returnModal"
                                        data-action="<?= site_url('circulation/' . $l['id'] . '/return') ?>"
                                        data-title="<?= esc($l['title']) ?>" data-student="<?= esc($l['borrower_name']) ?>"
                                        data-late="<?= $l['days_late'] > 0 ? $l['days_late'] . ' day(s) late — ' . $l['est_credits'] . ' credit(s) deducted for lateness.' : 'On time — no credits deducted.' ?>">Return</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
            <?= $pager->links('default', 'nook') ?>
        <?php endif; ?>

    <?php elseif ($tab === 'collections'): ?>
        <!-- ===== Collections: the catalog, unavailable and fewest copies left first ===== -->
        <div class="d-flex flex-wrap align-items-center gap-3 small text-muted mb-2">
            <span><strong class="text-body"><?= $stats['copies'] ?></strong> copies owned</span>
            <span><strong class="text-body"><?= $stats['available'] ?></strong> on the shelf</span>
            <span><strong class="text-body"><?= $stats['on_loan'] ?></strong> on loan</span>
            <span><strong class="text-danger"><?= $stats['unavailable'] ?></strong> unavailable</span>
            <span><strong class="text-warning-emphasis"><?= $stats['low'] ?></strong> with few copies left</span>
            <?php if (is_head()): ?>
                <a class="btn btn-sm btn-outline-nook ms-auto" href="<?= site_url('categories') ?>"><i class="bi bi-tags me-1"></i>Manage categories</a>
            <?php endif; ?>
        </div>
        <?php if (! $rows): ?>
            <div class="empty"><i class="bi bi-search"></i>No titles match.</div>
        <?php else: ?>
            <div class="table-responsive"><table class="table table-nook">
                <thead><tr><th>Title</th><th>Author</th><th>Category</th><th>Shelf</th><th>Added</th><th>Availability</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $b): ?>
                    <tr>
                        <td><div class="d-flex gap-2 align-items-center"><?= book_cover($b, 'cover-sm', 'S') ?><div><a class="fw-semibold text-reset" href="<?= site_url('books/' . $b['id']) ?>"><?= esc($b['title']) ?></a><div class="sub"><?= $b['edition'] ? esc($b['edition']) . ' · ' : '' ?>ISBN <?= esc($b['isbn']) ?></div></div></div></td>
                        <td><?= esc($b['author']) ?></td>
                        <td><?= esc($b['category_name'] ?? '—') ?></td>
                        <td><?= esc($b['shelf_location'] ?? '—') ?></td>
                        <td class="sub"><?= fmt_date($b['created_at']) ?></td>
                        <td><?= availability_badge((int) $b['available'], (int) $b['stock_count']) ?>
                            </td>
                        <td class="actions">
                            <a class="btn btn-sm btn-outline-nook" href="<?= site_url('books/' . $b['id']) ?>" title="View"><i class="bi bi-eye"></i></a>
                            <a class="btn btn-sm btn-outline-nook" href="<?= site_url('books/' . $b['id'] . '/edit') ?>" title="Edit"><i class="bi bi-pencil"></i></a>
                            <?php if (is_head() && (int) $b['on_loan'] > 0): $n = (int) $b['on_loan']; ?>
                                <!-- Can't archive while copies are out: say why instead of failing after the click -->
                                <span class="d-inline-block" tabindex="0" title="Can't archive yet: <?= $n ?> cop<?= $n === 1 ? 'y is' : 'ies are' ?> on loan. Archive it once <?= $n === 1 ? 'it is' : 'they are' ?> returned.">
                                    <button class="btn btn-sm btn-outline-nook" disabled aria-label="Archive (not available: copies on loan)"><i class="bi bi-archive"></i></button>
                                </span>
                            <?php elseif (is_head()): ?>
                                <form class="d-inline" method="post" action="<?= site_url('books/' . $b['id'] . '/archive') ?>" data-confirm="Archive &quot;<?= esc($b['title']) ?>&quot;? It will be hidden from the library. You can restore it from the Archive page.">
                                    <?= csrf_field() ?><button class="btn btn-sm btn-outline-nook" title="Archive" aria-label="Archive"><i class="bi bi-archive"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
            <?= $pager->links('default', 'nook') ?>
        <?php endif; ?>

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

<?php if (is_staff()): ?>
<!-- Update Stock → Adjust copies -->
<div class="modal fade" id="stockModal" tabindex="-1" aria-labelledby="stockTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 520px"><div class="modal-content adjust-modal">
        <form method="post" action="<?= site_url('inventory/adjust') ?>" id="stockForm">
            <?= csrf_field() ?>
            <div class="modal-header border-0 pb-0">
                <h2 class="modal-title panel-title mb-0" id="stockTitle"><i class="bi bi-sliders"></i> Adjust copies</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <fieldset class="mb-3">
                    <legend class="form-label fs-6 mb-2">Title</legend>
                    <div class="search-icon mb-2">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input class="form-control form-control-sm" type="search" placeholder="Title, author or ISBN" data-pick-filter="stock-books" aria-label="Search books">
                    </div>
                    <div class="pick-list pick-list-short" id="stock-books" role="radiogroup" aria-label="Books">
                        <?php foreach ($allBooks as $b): $free = (int) $b['available']; $owned = (int) $b['stock_count']; ?>
                            <label class="pick" data-search="<?= esc(strtolower($b['title'] . ' ' . $b['isbn'] . ' ' . $b['author'])) ?>">
                                <input class="visually-hidden" type="radio" name="book_id" value="<?= $b['id'] ?>" required <?= (string) old('book_id') === (string) $b['id'] ? 'checked' : '' ?>>
                                <?= book_cover($b, 'cover-sm', 'S') ?>
                                <span class="pick-body">
                                    <span class="pick-title"><?= esc($b['title']) ?></span>
                                    <span class="pick-sub">ISBN <?= esc($b['isbn']) ?></span>
                                    <span class="pick-meta"><?= $owned ?> cop<?= $owned === 1 ? 'y' : 'ies' ?> owned · <?= $free ?> on the shelf</span>
                                </span>
                                <i class="bi bi-check-circle-fill pick-check" aria-hidden="true"></i>
                            </label>
                        <?php endforeach; ?>
                        <p class="pick-empty" hidden>No book matches.</p>
                    </div>
                </fieldset>
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

<!-- Issue a book: pick a student, pick a book, check the summary, issue -->
<?php
$loanDays = (int) setting('loan_days');
$oldUser  = (string) old('user_id');
$oldBook  = (string) old('book_id');
?>
<div class="modal fade" id="issueModal" tabindex="-1" aria-labelledby="issueTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content issue-modal">
        <form method="post" action="<?= site_url('circulation/issue') ?>" id="issueForm">
            <?= csrf_field() ?>
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <span class="issue-icon" aria-hidden="true"><i class="bi bi-box-arrow-up-right"></i></span>
                    <div>
                        <h2 class="modal-title fs-5 mb-0" id="issueTitle">Issue a book</h2>
                        <p class="small text-muted mb-0">Choose a student and a book, then check the summary.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="row g-4">
                    <!-- 1. Student -->
                    <fieldset class="col-lg-6">
                        <legend class="issue-step"><span>1</span>Student</legend>
                        <div class="search-icon mb-2">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input class="form-control form-control-sm" type="search" placeholder="Name, student no. or course" data-pick-filter="issue-students" aria-label="Search students">
                        </div>
                        <div class="pick-list" id="issue-students" role="radiogroup" aria-label="Students">
                            <?php foreach ($students as $s):
                                $tier    = \App\Libraries\CreditPolicy::tier((int) $s['credits']);
                                $limit   = \App\Libraries\CreditPolicy::maxBooks((int) $s['credits'], (int) setting('max_loans'));
                                $blocked = $limit <= (int) $s['on_loan']; ?>
                                <label class="pick<?= $blocked ? ' is-limited' : '' ?>" data-search="<?= esc(strtolower($s['name'] . ' ' . $s['id_number'] . ' ' . ($s['course'] ?? ''))) ?>">
                                    <input class="visually-hidden" type="radio" name="user_id" value="<?= $s['id'] ?>" required <?= $oldUser === (string) $s['id'] ? 'checked' : '' ?>
                                           data-name="<?= esc($s['name']) ?>" data-sub="<?= esc($s['id_number']) ?>" data-avatar="<?= esc(user_avatar($s, 'avatar-md'), 'attr') ?>">
                                    <?= user_avatar($s, 'avatar-md') ?>
                                    <span class="pick-body">
                                        <span class="pick-title"><?= esc($s['name']) ?></span>
                                        <span class="pick-sub"><?= esc($s['id_number']) ?><?= $s['course'] ? ' · ' . esc(str_replace('Bachelor of Science in ', 'BS ', $s['course'])) : '' ?></span>
                                        <span class="pick-meta">
                                            <span class="pill pill-<?= $tier['tone'] ?>"><?= (int) $s['credits'] ?> credit<?= (int) $s['credits'] === 1 ? '' : 's' ?></span>
                                            <span class="<?= $blocked ? 'text-danger' : '' ?>"><?= (int) $s['on_loan'] ?> of <?= $limit ?> on loan<?= $blocked ? ' · at limit' : '' ?></span>
                                        </span>
                                    </span>
                                    <i class="bi bi-check-circle-fill pick-check" aria-hidden="true"></i>
                                </label>
                            <?php endforeach; ?>
                            <p class="pick-empty" hidden>No student matches.</p>
                        </div>
                    </fieldset>

                    <!-- 2. Book -->
                    <fieldset class="col-lg-6">
                        <legend class="issue-step"><span>2</span>Book</legend>
                        <div class="search-icon mb-2">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input class="form-control form-control-sm" type="search" placeholder="Title, author or ISBN" data-pick-filter="issue-books" aria-label="Search books">
                        </div>
                        <div class="pick-list" id="issue-books" role="radiogroup" aria-label="Books">
                            <?php foreach ($bookList as $b): $free = (int) $b['available']; ?>
                                <label class="pick<?= $free <= 0 ? ' is-disabled' : '' ?>" data-search="<?= esc(strtolower($b['title'] . ' ' . $b['isbn'] . ' ' . $b['author'])) ?>">
                                    <input class="visually-hidden" type="radio" name="book_id" value="<?= $b['id'] ?>" required <?= $free <= 0 ? 'disabled' : '' ?> <?= $oldBook === (string) $b['id'] ? 'checked' : '' ?>
                                           data-name="<?= esc($b['title']) ?>" data-sub="ISBN <?= esc($b['isbn']) ?>" data-avatar="<?= esc(book_cover($b, 'cover-sm', 'S'), 'attr') ?>">
                                    <?= book_cover($b, 'cover-sm', 'S') ?>
                                    <span class="pick-body">
                                        <span class="pick-title"><?= esc($b['title']) ?></span>
                                        <span class="pick-sub"><?= esc($b['author']) ?> · ISBN <?= esc($b['isbn']) ?></span>
                                        <span class="pick-meta"><?= availability_badge($free, (int) $b['stock_count']) ?></span>
                                    </span>
                                    <i class="bi bi-check-circle-fill pick-check" aria-hidden="true"></i>
                                </label>
                            <?php endforeach; ?>
                            <p class="pick-empty" hidden>No book matches.</p>
                        </div>
                    </fieldset>
                </div>
            </div>

            <!-- Summary -->
            <div class="modal-footer issue-summary">
                <div class="summary-slot" id="sumStudent"><span class="summary-empty"><i class="bi bi-person"></i>No student chosen</span></div>
                <i class="bi bi-arrow-right summary-arrow" aria-hidden="true"></i>
                <div class="summary-slot" id="sumBook"><span class="summary-empty"><i class="bi bi-book"></i>No book chosen</span></div>
                <div class="ms-auto text-end">
                    <div class="small text-muted">Due back</div>
                    <div class="fw-semibold"><?= fmt_date(date('Y-m-d', strtotime('+' . $loanDays . ' days'))) ?></div>
                </div>
                <div class="w-100 d-flex flex-wrap align-items-center gap-2">
                    <p class="form-text mb-0 issue-note">Usually a <?= $loanDays ?>-day loan. Students who have lost credits may get fewer books or a shorter loan; at 0 credits they can't borrow.</p>
                    <div class="issue-actions">
                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-nook" id="issueSubmit" disabled><i class="bi bi-check2 me-1"></i>Issue book</button>
                    </div>
                </div>
            </div>
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
                <div class="mb-3 d-none" id="damageFee"><label class="form-label" for="damage_credits">Credits to deduct for damage</label>
                    <input class="form-control" type="number" step="1" min="0" max="20" id="damage_credits" name="damage_credits" value="<?= (int) setting('damaged_credits') ?>"></div>
                <div class="small text-danger mb-3 d-none" id="lostNote"><?= (int) setting('lost_credits') ?> credits will be deducted from the student and one copy removed from stock.</div>
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
    // Issue a book: search inside each list, show the chosen student and book, enable "Issue book".
    // "View requests" pop-up: count the ticked books on the buttons; Select all.
    document.querySelectorAll('.req-form').forEach(function (form) {
        const room = parseInt(form.dataset.room, 10);
        const boxes = form.querySelectorAll('input[name="ids[]"]');
        const all = form.querySelector('[data-req-all]');
        const approve = form.querySelector('[data-req-approve]'), decline = form.querySelector('[data-req-decline]');
        const plural = n => n + ' book' + (n === 1 ? '' : 's');
        function update() {
            const ticked = [...boxes].filter(b => b.checked);
            const borrow = ticked.filter(b => b.dataset.type === 'borrow').length;
            approve.querySelector('[data-n-borrow]').textContent = plural(borrow);
            decline.querySelector('[data-n-all]').textContent = plural(ticked.length);
            approve.disabled = borrow === 0;
            decline.disabled = ticked.length === 0;
            approve.title = borrow > room ? 'Only ' + room + ' more allowed for this student; the extra ones will not be issued.' : '';
            all.checked = ticked.length === boxes.length;
            all.indeterminate = ticked.length > 0 && ticked.length < boxes.length;
        }
        all.addEventListener('change', () => { boxes.forEach(b => { b.checked = all.checked; }); update(); });
        boxes.forEach(b => b.addEventListener('change', update));
        // Enter in the reason box must not decline by accident.
        form.querySelector('.req-reason').addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); } });
        update();
    });
    // Adjust copies: clicking the chosen book again unselects it; show the chosen one when opened.
    (function () {
        const form = document.getElementById('stockForm');
        if (!form) { return; }
        let wasChecked = null;
        form.addEventListener('pointerdown', function (e) {
            const pick = e.target.closest('.pick'), r = pick && pick.querySelector('input[type="radio"]');
            wasChecked = r && r.checked ? r : null;
        });
        form.addEventListener('click', function (e) {
            if (e.target.type === 'radio' && e.target === wasChecked) { e.target.checked = false; wasChecked = null; }
        });
        document.getElementById('stockModal').addEventListener('shown.bs.modal', function () {
            const r = this.querySelector('input[name="book_id"]:checked');
            if (r) { r.closest('.pick').scrollIntoView({ block: 'nearest' }); }
        });
    })();
    (function () {
        // Search inside any pick list (Issue a book, Adjust copies).
        document.querySelectorAll('[data-pick-filter]').forEach(function (input) {
            const list = document.getElementById(input.dataset.pickFilter);
            input.addEventListener('input', function () {
                const q = input.value.trim().toLowerCase();
                let shown = 0;
                list.querySelectorAll('.pick').forEach(function (p) {
                    const hit = q === '' || p.dataset.search.includes(q);
                    p.hidden = !hit;
                    shown += hit ? 1 : 0;
                });
                list.querySelector('.pick-empty').hidden = shown > 0;
            });
        });
        const form = document.getElementById('issueForm');
        if (!form) { return; }
        const emptySlot = {
            sumStudent: document.getElementById('sumStudent').innerHTML,
            sumBook: document.getElementById('sumBook').innerHTML,
        };
        function slot(id, radio) {
            const el = document.getElementById(id);
            if (!radio) { el.innerHTML = emptySlot[id]; return; }
            el.innerHTML = radio.dataset.avatar + '<span class="summary-text"><strong></strong><small></small></span>';
            el.querySelector('strong').textContent = radio.dataset.name;
            el.querySelector('small').textContent = radio.dataset.sub;
        }
        function refresh() {
            const s = form.querySelector('input[name="user_id"]:checked');
            const b = form.querySelector('input[name="book_id"]:checked');
            slot('sumStudent', s);
            slot('sumBook', b);
            document.getElementById('issueSubmit').disabled = !(s && b);
        }
        form.addEventListener('change', refresh);
        // Clicking the chosen student or book again unselects it.
        let wasChecked = null;
        function remember(e) {
            const pick = e.target.closest('.pick');
            const r = pick && pick.querySelector('input[type="radio"]');
            wasChecked = r && r.checked ? r : null;
        }
        form.addEventListener('pointerdown', remember);
        form.addEventListener('keydown', function (e) { if (e.key === ' ') { remember(e); } });
        form.addEventListener('click', function (e) {
            if (e.target.type !== 'radio' || e.target !== wasChecked) { return; }
            e.target.checked = false;
            wasChecked = null;
            refresh();
        });
        refresh();
        document.getElementById('issueModal').addEventListener('shown.bs.modal', function () {
            const first = this.querySelector('[data-pick-filter]');
            if (first) { first.focus(); }
            this.querySelectorAll('input[type="radio"]:checked').forEach(r => r.closest('.pick').scrollIntoView({ block: 'nearest' }));
        });
    })();
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
    <?php if (old('user_id') !== null && old('quantity') === null): ?>
    document.addEventListener('DOMContentLoaded', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('issueModal')).show());
    <?php endif; ?>
    <?php if (old('quantity') !== null): ?>
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
