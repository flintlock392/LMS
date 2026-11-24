<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?>My Reservations<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="panel">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <p class="small text-muted mb-0">You can have up to <?= (int) setting('max_reservations') ?> active reservations. Ready books are held for <?= (int) setting('hold_days') ?> day(s).</p>
        <a class="btn btn-sm btn-nook ms-auto" href="<?= site_url('catalog') ?>"><i class="bi bi-search me-1"></i>Find a book</a>
    </div>
    <?php if (! $rows): ?>
        <div class="empty"><i class="bi bi-bookmark"></i>No reservations yet.</div>
    <?php else: ?>
        <div class="table-responsive"><table class="table table-nook">
            <thead><tr><th>Book</th><th>Reserved</th><th>Status</th><th class="text-end"></th></tr></thead>
            <tbody><?php foreach ($rows as $r): ?>
                <tr>
                    <td><a class="text-reset" href="<?= site_url('catalog/' . $r['book_id']) ?>"><?= esc($r['title']) ?></a><div class="sub"><?= esc($r['author']) ?></div></td>
                    <td><?= fmt_date($r['reserved_at'], true) ?></td>
                    <td><?= status_badge($r['status']) ?>
                        <?php if ($r['status'] === 'pending'): ?><div class="sub">You're #<?= $r['position'] ?> in line</div><?php endif; ?>
                        <?php if ($r['status'] === 'ready'): ?><div class="sub">Pick up by <?= fmt_date($r['expires_at'], true) ?></div><?php endif; ?></td>
                    <td class="actions">
                        <?php if (in_array($r['status'], ['pending', 'ready'], true)): ?>
                            <form method="post" action="<?= site_url('my/reservations/' . $r['id'] . '/cancel') ?>" data-confirm="Cancel your reservation for &quot;<?= esc($r['title']) ?>&quot;?">
                                <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Cancel</button></form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?></tbody>
        </table></div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
