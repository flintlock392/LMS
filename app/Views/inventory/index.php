<?php use App\Models\InventoryLogModel; ?>
<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?><?= esc(module_title('inventory')) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-3 mb-4">
    <div class="col-6 col-lg"><div class="stat"><span class="icon"><i class="bi bi-stack"></i></span><span><span class="value d-block"><?= $stats['copies'] ?></span><span class="label">Copies owned</span></span></div></div>
    <div class="col-6 col-lg"><div class="stat"><span class="icon good"><i class="bi bi-check2-circle"></i></span><span><span class="value d-block"><?= $stats['available'] ?></span><span class="label">On the shelf</span></span></div></div>
    <div class="col-6 col-lg"><div class="stat"><span class="icon blue"><i class="bi bi-journal-arrow-up"></i></span><span><span class="value d-block"><?= $stats['on_loan'] ?></span><span class="label">On loan</span></span></div></div>
    <div class="col-6 col-lg"><div class="stat"><span class="icon"><i class="bi bi-bookmark"></i></span><span><span class="value d-block"><?= $stats['on_hold'] ?></span><span class="label">Held for pickup</span></span></div></div>
    <div class="col-6 col-lg"><div class="stat"><span class="icon bad"><i class="bi bi-exclamation-octagon"></i></span><span><span class="value d-block"><?= $stats['unavailable'] ?></span><span class="label">Titles unavailable</span></span></div></div>
</div>

<div class="row g-4">
    <div class="<?= is_head() ? 'col-xl-8' : 'col-12' ?>">
        <div class="panel">
            <h2 class="panel-title">Stock levels <span class="ms-auto small text-muted">Lowest availability first</span></h2>
            <form class="filters" method="get">
                <input class="form-control form-control-sm" type="search" name="q" value="<?= esc($filters['q']) ?>" placeholder="Title, author, ISBN" aria-label="Search">
                <select class="form-select form-select-sm" name="availability" aria-label="Availability">
                    <option value="">All titles</option>
                    <option value="out_of_stock" <?= $filters['availability'] === 'out_of_stock' ? 'selected' : '' ?>>Unavailable</option>
                    <option value="low_stock" <?= $filters['availability'] === 'low_stock' ? 'selected' : '' ?>>Few left (≤ <?= \App\Models\BookModel::LOW_STOCK ?>)</option>
                    <option value="in_stock" <?= $filters['availability'] === 'in_stock' ? 'selected' : '' ?>>Available</option>
                </select>
                <button class="btn btn-sm btn-nook"><i class="bi bi-search"></i></button>
            </form>
            <?php if (! $books): ?>
                <div class="empty"><i class="bi bi-box-seam"></i>No titles match.</div>
            <?php else: ?>
                <div class="table-responsive"><table class="table table-nook">
                    <thead><tr><th>Title</th><th>Shelf</th><th class="text-center">Owned</th><th class="text-center">On loan</th><th class="text-center">On hold</th><th class="text-center">Waiting</th><th>Shelf status</th></tr></thead>
                    <tbody><?php foreach ($books as $b): ?>
                        <tr>
                            <td><a class="text-reset" href="<?= site_url('books/' . $b['id']) ?>"><?= esc($b['title']) ?></a><div class="sub"><?= esc($b['isbn']) ?></div></td>
                            <td><?= esc($b['shelf_location'] ?? '—') ?></td>
                            <td class="text-center"><?= (int) $b['stock_count'] ?></td>
                            <td class="text-center"><?= (int) $b['on_loan'] ?></td>
                            <td class="text-center"><?= (int) $b['on_hold'] ?></td>
                            <td class="text-center"><?= (int) $b['queued'] ?: '—' ?></td>
                            <td><?= availability_badge((int) $b['available'], (int) $b['stock_count']) ?>
                                <?php if ((int) $b['queued'] > 0 && (int) $b['available'] <= 0): ?><div class="sub text-danger">Consider buying more</div><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?></tbody>
                </table></div>
                <?= $pager->links('default', 'nook') ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if (is_head()): ?>
        <div class="col-xl-4">
            <div class="panel">
                <h2 class="panel-title"><i class="bi bi-sliders"></i> Adjust copies</h2>
                <form method="post" action="<?= site_url('inventory/adjust') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3"><label class="form-label" for="book_id">Title</label>
                        <select class="form-select" id="book_id" name="book_id" required>
                            <option value="">Choose…</option>
                            <?php foreach ($all as $b): ?><option value="<?= $b['id'] ?>" <?= (string) old('book_id') === (string) $b['id'] ? 'selected' : '' ?>><?= esc($b['title']) ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="mb-3">
                        <div class="btn-group w-100" role="group" aria-label="Direction">
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
                                <optgroup label="Add" data-dir="add"><?php foreach (InventoryLogModel::ADD_REASONS as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?></optgroup>
                                <optgroup label="Remove" data-dir="remove"><?php foreach (InventoryLogModel::REMOVE_REASONS as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?></optgroup>
                                <option value="correction">Count correction</option>
                            </select></div>
                    </div>
                    <div class="mb-3"><label class="form-label" for="notes">Notes</label>
                        <input class="form-control" id="notes" name="notes" maxlength="255" placeholder="e.g. PO #1234, water damage" value="<?= esc(old('notes', '', false)) ?>"></div>
                    <button class="btn btn-nook w-100">Save change</button>
                    <p class="form-text mb-0 mt-2">Only copies on the shelf can be removed. Lost loans are removed automatically when returned as "Lost".</p>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="panel mt-4">
    <h2 class="panel-title"><i class="bi bi-clock-history"></i> Recent inventory changes</h2>
    <?php if (! $log): ?>
        <div class="empty"><i class="bi bi-inbox"></i>No changes recorded yet.</div>
    <?php else: ?>
        <div class="table-responsive"><table class="table table-nook">
            <thead><tr><th>Date</th><th>Title</th><th>Change</th><th>Reason</th><th>By</th></tr></thead>
            <tbody><?php foreach ($log as $e): ?>
                <tr><td class="sub"><?= fmt_date($e['created_at'], true) ?></td>
                    <td><?= esc($e['title']) ?></td>
                    <td class="fw-semibold <?= $e['change'] > 0 ? 'text-success' : 'text-danger' ?>"><?= $e['change'] > 0 ? '+' : '' ?><?= (int) $e['change'] ?> <span class="sub">→ <?= (int) $e['stock_after'] ?></span></td>
                    <td><?= esc(InventoryLogModel::LABELS[$e['reason']] ?? $e['reason']) ?><?php if ($e['notes']): ?><div class="sub"><?= esc($e['notes']) ?></div><?php endif; ?></td>
                    <td class="sub"><?= esc($e['user_name'] ?? '—') ?></td></tr>
            <?php endforeach; ?></tbody>
        </table></div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>

<?php if (is_head()): ?>
<?= $this->section('scripts') ?>
<script>
    // Only show the reasons that match "Add" or "Remove".
    function syncReasons() {
        const dir = document.querySelector('input[name="direction"]:checked').value;
        const sel = document.getElementById('reason');
        sel.querySelectorAll('optgroup').forEach(g => { g.hidden = g.dataset.dir !== dir; });
        const cur = sel.options[sel.selectedIndex];
        if (!cur || (cur.parentElement.dataset.dir && cur.parentElement.dataset.dir !== dir)) {
            sel.value = sel.querySelector('optgroup[data-dir="' + dir + '"] option').value;
        }
    }
    document.querySelectorAll('input[name="direction"]').forEach(r => r.addEventListener('change', syncReasons));
    syncReasons();
</script>
<?= $this->endSection() ?>
<?php endif; ?>
