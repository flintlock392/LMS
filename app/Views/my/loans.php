<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?>My Loans<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="panel">
    <p class="small text-muted">Books are lent for <?= (int) setting('loan_days') ?> days. Late returns cost <?= money(setting('fine_per_day')) ?> per day. Return books at the circulation desk.</p>
    <?php if (! $rows): ?>
        <div class="empty"><i class="bi bi-book"></i>You haven't borrowed any books yet. <a href="<?= site_url('catalog') ?>">Browse the collection</a></div>
    <?php else: ?>
        <div class="table-responsive"><table class="table table-nook">
            <thead><tr><th>Book</th><th>Borrowed</th><th>Due</th><th>Returned</th><th>Status</th></tr></thead>
            <tbody><?php foreach ($rows as $l): ?>
                <tr>
                    <td><a class="text-reset" href="<?= site_url('catalog/' . $l['book_id']) ?>"><?= esc($l['title']) ?></a><div class="sub"><?= esc($l['author']) ?></div></td>
                    <td><?= fmt_date($l['borrowed_at']) ?></td>
                    <td><?= fmt_date($l['due_date']) ?></td>
                    <td><?= fmt_date($l['returned_at']) ?></td>
                    <td><?= status_badge($l['status'], $l['due_date']) ?>
                        <?php if ($l['days_late'] > 0): ?><div class="sub text-danger"><?= $l['days_late'] ?> day(s) late · ~<?= money($l['est_fine']) ?> so far</div><?php endif; ?></td>
                </tr>
            <?php endforeach; ?></tbody>
        </table></div>
        <?= $pager->links('default', 'nook') ?>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
