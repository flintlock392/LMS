<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?><?= esc($book['title']) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<a class="small d-inline-block mb-3" href="<?= site_url('library?tab=collections') ?>"><i class="bi bi-arrow-left"></i> Library</a>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="panel">
            <div class="d-flex align-items-start gap-2 mb-2">
                <h2 class="h5 mb-0 flex-grow-1"><?= esc($book['title']) ?></h2>
                <a class="btn btn-sm btn-outline-nook" href="<?= site_url('books/' . $book['id'] . '/edit') ?>"><i class="bi bi-pencil"></i> Edit</a>
            </div>
            <p class="text-muted mb-3">by <?= esc($book['author']) ?></p>
            <dl class="row small mb-0">
                <dt class="col-5">ISBN</dt><dd class="col-7"><?= esc($book['isbn']) ?></dd>
                <dt class="col-5">Category</dt><dd class="col-7"><?= esc($book['category_name'] ?? 'Uncategorized') ?></dd>
                <dt class="col-5">Publisher</dt><dd class="col-7"><?= esc($book['publisher'] ?? '—') ?></dd>
                <dt class="col-5">Year</dt><dd class="col-7"><?= esc($book['publication_year'] ?? '—') ?></dd>
                <dt class="col-5">Shelf</dt><dd class="col-7"><?= esc($book['shelf_location'] ?? '—') ?></dd>
            </dl>
            <?php if ($book['description']): ?><p class="small mt-3 mb-0"><?= nl2br(esc($book['description'])) ?></p><?php endif; ?>
        </div>
        <div class="panel">
            <h3 class="panel-title">Copies</h3>
            <div class="row text-center g-2">
                <div class="col-4"><div class="fs-4 fw-semibold"><?= (int) $book['stock_count'] ?></div><div class="small text-muted">Owned</div></div>
                <div class="col-4"><div class="fs-4 fw-semibold text-success"><?= max(0, (int) $book['available']) ?></div><div class="small text-muted">Shelf</div></div>
                <div class="col-4"><div class="fs-4 fw-semibold"><?= (int) $book['on_loan'] ?></div><div class="small text-muted">On loan</div></div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="panel">
            <h3 class="panel-title"><i class="bi bi-arrow-left-right"></i> Borrowing history</h3>
            <?php if (! $loans): ?>
                <div class="empty"><i class="bi bi-inbox"></i>This book hasn't been borrowed yet.</div>
            <?php else: ?>
                <div class="table-responsive"><table class="table table-nook">
                    <thead><tr><th>Student</th><th>Borrowed</th><th>Due</th><th>Status</th></tr></thead>
                    <tbody><?php foreach ($loans as $l): ?>
                        <tr><td><?= esc($l['borrower_name']) ?><div class="sub"><?= esc($l['borrower_id_number']) ?></div></td>
                            <td><?= fmt_date($l['borrowed_at']) ?></td><td><?= fmt_date($l['due_date']) ?></td>
                            <td><?= status_badge($l['status'], $l['due_date']) ?></td></tr>
                    <?php endforeach; ?></tbody>
                </table></div>
            <?php endif; ?>
        </div>
        <div class="panel">
            <h3 class="panel-title"><i class="bi bi-box-seam"></i> Inventory changes</h3>
            <?php if (! $log): ?>
                <div class="empty"><i class="bi bi-inbox"></i>No inventory changes recorded.</div>
            <?php else: ?>
                <div class="table-responsive"><table class="table table-nook">
                    <thead><tr><th>Date</th><th>Change</th><th>Reason</th><th>By</th></tr></thead>
                    <tbody><?php foreach ($log as $e): ?>
                        <tr><td class="sub"><?= fmt_date($e['created_at'], true) ?></td>
                            <td class="fw-semibold <?= $e['change'] > 0 ? 'text-success' : 'text-danger' ?>"><?= $e['change'] > 0 ? '+' : '' ?><?= (int) $e['change'] ?> → <?= (int) $e['stock_after'] ?></td>
                            <td><?= esc(\App\Models\InventoryLogModel::LABELS[$e['reason']] ?? $e['reason']) ?><?php if ($e['notes']): ?><div class="sub"><?= esc($e['notes']) ?></div><?php endif; ?></td>
                            <td class="sub"><?= esc($e['user_name'] ?? '—') ?></td></tr>
                    <?php endforeach; ?></tbody>
                </table></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
