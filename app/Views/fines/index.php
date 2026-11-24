<?php use App\Models\FineModel; ?>
<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?><?= esc(module_title('fines')) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3"><div class="stat"><span class="icon bad"><i class="bi bi-hourglass-split"></i></span><span><span class="value d-block"><?= money($totals['unpaid']['total']) ?></span><span class="label">Outstanding (<?= $totals['unpaid']['n'] ?>)</span></span></div></div>
    <div class="col-sm-6 col-lg-3"><div class="stat"><span class="icon good"><i class="bi bi-cash-stack"></i></span><span><span class="value d-block"><?= money($totals['collected_today']) ?></span><span class="label">Collected today</span></span></div></div>
    <div class="col-sm-6 col-lg-3"><div class="stat"><span class="icon good"><i class="bi bi-check2-all"></i></span><span><span class="value d-block"><?= money($totals['paid']['total']) ?></span><span class="label">Collected all time</span></span></div></div>
    <div class="col-sm-6 col-lg-3"><div class="stat"><span class="icon"><i class="bi bi-slash-circle"></i></span><span><span class="value d-block"><?= money($totals['waived']['total']) ?></span><span class="label">Waived</span></span></div></div>
</div>

<div class="row g-4">
    <div class="<?= is_head() ? 'col-xl-8' : 'col-12' ?>">
        <div class="panel">
            <nav class="tabs-nook">
                <?php foreach (['unpaid' => 'Unpaid', 'paid' => 'Paid', 'waived' => 'Waived', 'all' => 'All'] as $key => $label): ?>
                    <a class="<?= $filters['status'] === $key ? 'active' : '' ?>" href="<?= site_url('fines?status=' . $key) ?>"><?= $label ?></a>
                <?php endforeach; ?>
            </nav>
            <form class="date-range mb-3" method="get">
                <input type="hidden" name="status" value="<?= esc($filters['status']) ?>">
                <div class="field">
                    <label for="by">Search by</label>
                    <select class="form-select form-select-sm" id="by" name="by">
                        <?php foreach (\App\Controllers\Fines::SEARCH_BY as $key => [$label, $placeholder]): ?>
                            <option value="<?= $key ?>" data-placeholder="<?= esc($placeholder) ?>" <?= $filters['by'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="fine-q">Search</label>
                    <input class="form-control form-control-sm" type="search" id="fine-q" name="q" value="<?= esc($filters['q']) ?>"
                           placeholder="<?= esc(\App\Controllers\Fines::SEARCH_BY[$filters['by']][1]) ?>"
                           data-suggest="fines" data-suggest-by="by" style="width:240px">
                </div>
                <button class="btn btn-sm btn-nook"><i class="bi bi-search me-1"></i>Search</button>
                <?php if ($filters['q'] !== ''): ?>
                    <a class="btn btn-sm btn-link" href="<?= site_url('fines?status=' . $filters['status']) ?>">Clear</a>
                <?php endif; ?>
            </form>
            <?php if ($filters['q'] !== ''): ?>
                <div class="filter-summary"><i class="bi bi-funnel"></i>
                    <span>Showing <?= $filters['status'] === 'all' ? 'all' : esc($filters['status']) ?> fines where
                        <strong><?= esc(strtolower(\App\Controllers\Fines::SEARCH_BY[$filters['by']][0] === 'Everything' ? 'student name, number or book' : \App\Controllers\Fines::SEARCH_BY[$filters['by']][0])) ?></strong>
                        contains "<?= esc($filters['q']) ?>" · <?= $pager->getTotal() ?> result(s)</span></div>
            <?php endif; ?>
            <?php if (! $rows): ?>
                <div class="empty"><i class="bi bi-emoji-smile"></i>No fines to show.</div>
            <?php else: ?>
                <div class="table-responsive"><table class="table table-nook">
                    <thead><tr><th>Student</th><th>Reason</th><th>Amount</th><th>Date</th><th>Status</th><th class="text-end"></th></tr></thead>
                    <tbody><?php foreach ($rows as $f): ?>
                        <tr>
                            <td><a class="text-reset" href="<?= site_url('members/' . $f['user_id']) ?>"><?= esc($f['borrower_name']) ?></a><div class="sub"><?= esc($f['borrower_id_number']) ?></div></td>
                            <td><?= esc(FineModel::REASONS[$f['reason']] ?? $f['reason']) ?><div class="sub"><?= esc(trim(($f['title'] ?? '') . ($f['title'] && $f['notes'] ? ' · ' : '') . ($f['notes'] ?? ''))) ?></div></td>
                            <td class="fw-semibold"><?= money($f['amount']) ?></td>
                            <td><?= fmt_date($f['created_at']) ?><?php if ($f['paid_at']): ?><div class="sub">Paid <?= fmt_date($f['paid_at']) ?></div><?php endif; ?></td>
                            <td><?= status_badge($f['status']) ?><?php if ($f['processed_by_name']): ?><div class="sub">by <?= esc($f['processed_by_name']) ?></div><?php endif; ?></td>
                            <td class="actions">
                                <?php if ($f['status'] === 'unpaid'): ?>
                                    <form class="d-inline" method="post" action="<?= site_url('fines/' . $f['id'] . '/pay') ?>" data-confirm="Record a payment of <?= money($f['amount']) ?> from <?= esc($f['borrower_name']) ?>?">
                                        <?= csrf_field() ?><button class="btn btn-sm btn-nook"><i class="bi bi-cash"></i> Mark paid</button></form>
                                    <?php if (is_head()): ?>
                                        <button class="btn btn-sm btn-outline-nook" type="button" data-bs-toggle="collapse" data-bs-target="#waive-<?= $f['id'] ?>">Waive</button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if (is_head()): ?>
                                    <form class="d-inline" method="post" action="<?= site_url('fines/' . $f['id'] . '/delete') ?>" data-confirm="Delete this fine record permanently?">
                                        <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button></form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if (is_head() && $f['status'] === 'unpaid'): ?>
                            <tr class="collapse" id="waive-<?= $f['id'] ?>"><td colspan="6">
                                <form class="d-flex gap-2" method="post" action="<?= site_url('fines/' . $f['id'] . '/waive') ?>">
                                    <?= csrf_field() ?>
                                    <input class="form-control form-control-sm" name="reason" maxlength="120" placeholder="Reason for waiving (e.g. medical leave)" required>
                                    <button class="btn btn-sm btn-nook">Confirm waive</button>
                                </form>
                            </td></tr>
                        <?php endif; ?>
                    <?php endforeach; ?></tbody>
                </table></div>
                <?= $pager->links('default', 'nook') ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if (is_head()): ?>
        <div class="col-xl-4">
            <div class="panel">
                <h2 class="panel-title"><i class="bi bi-plus-lg"></i> Add a fine</h2>
                <form method="post" action="<?= site_url('fines/create') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3"><label class="form-label" for="fine-student">Student</label>
                        <input class="form-control" type="search" id="fine-student" placeholder="Type a name or student number"
                               data-suggest="borrowers" data-suggest-pick="user_id" aria-describedby="fine-student-help">
                        <input type="hidden" id="user_id" name="user_id">
                        <div class="form-text" id="fine-student-help">Choose a student from the suggestions.</div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-7"><label class="form-label" for="reason">Reason</label>
                            <select class="form-select" id="reason" name="reason"><?php foreach (FineModel::REASONS as $k => $v): ?><option value="<?= $k ?>" <?= $k === 'other' ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
                        <div class="col-5"><label class="form-label" for="amount">Amount</label>
                            <input class="form-control" type="number" step="0.01" min="0.01" id="amount" name="amount" required></div>
                    </div>
                    <div class="mb-3"><label class="form-label" for="notes">Notes</label><input class="form-control" id="notes" name="notes" maxlength="255"></div>
                    <button class="btn btn-nook w-100">Add fine</button>
                </form>
                <p class="form-text mt-3 mb-0">Overdue, damage and lost-book fines are added automatically when a book is returned. Rates are set in <a href="<?= site_url('settings') ?>">System Settings</a>.</p>
            </div>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
