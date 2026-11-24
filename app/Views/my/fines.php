<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?>My Fines<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-3 mb-4">
    <div class="col-md-5"><div class="stat"><span class="icon <?= $unpaid > 0 ? 'bad' : 'good' ?>"><i class="bi bi-cash-coin"></i></span>
        <span><span class="value d-block"><?= money($unpaid) ?></span><span class="label">Amount you owe</span></span></div></div>
</div>
<?php if ($unpaid > 0): ?>
    <div class="alert alert-warning"><i class="bi bi-info-circle me-1"></i>Pay at the library desk.<?= setting('block_unpaid_fines') === '1' ? ' You can\'t borrow books until your fines are settled.' : '' ?></div>
<?php endif; ?>
<div class="panel">
    <?php if (! $rows): ?>
        <div class="empty"><i class="bi bi-emoji-smile"></i>You have no fines. Keep it up!</div>
    <?php else: ?>
        <div class="table-responsive"><table class="table table-nook">
            <thead><tr><th>Date</th><th>Reason</th><th>Book</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody><?php foreach ($rows as $f): ?>
                <tr><td><?= fmt_date($f['created_at']) ?></td>
                    <td><?= esc(\App\Models\FineModel::REASONS[$f['reason']] ?? $f['reason']) ?><div class="sub"><?= esc($f['notes'] ?? '') ?></div></td>
                    <td><?= esc($f['title'] ?? '—') ?></td>
                    <td class="fw-semibold"><?= money($f['amount']) ?></td>
                    <td><?= status_badge($f['status']) ?><?php if ($f['paid_at']): ?><div class="sub"><?= fmt_date($f['paid_at']) ?></div><?php endif; ?></td></tr>
            <?php endforeach; ?></tbody>
        </table></div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
