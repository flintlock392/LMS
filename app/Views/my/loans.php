<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?>My Loans<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php if ($requests): ?>
    <div class="panel">
        <h2 class="panel-title"><i class="bi bi-hourglass-split"></i> My requests</h2>
        <div class="table-responsive"><table class="table table-nook">
            <thead><tr><th>Book</th><th>Request</th><th>Sent</th><th>Status</th><th class="text-end"></th></tr></thead>
            <tbody><?php foreach ($requests as $r): ?>
                <tr>
                    <td><?= esc($r['title']) ?><div class="sub"><?= esc($r['author']) ?></div></td>
                    <td><?= $r['type'] === 'borrow' ? '<i class="bi bi-bookmark-plus me-1"></i>Borrow' : '<i class="bi bi-box-arrow-in-down-left me-1"></i>Return' ?></td>
                    <td class="sub"><?= fmt_date($r['created_at'], true) ?></td>
                    <td>
                        <?php if ($r['status'] === 'pending'): ?>
                            <span class="pill pill-warning">Waiting for staff</span>
                            <div class="sub"><?= $r['type'] === 'borrow' ? 'Collect it at the desk once it\'s issued.' : 'Bring the book to the desk.' ?></div>
                        <?php else: ?>
                            <span class="pill pill-danger">Declined</span>
                            <?php if ($r['reason']): ?><div class="sub">Reason: <?= esc($r['reason']) ?></div><?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td class="actions">
                        <?php if ($r['status'] === 'pending'): ?>
                            <form class="d-inline" method="post" action="<?= site_url('my/requests/' . $r['id'] . '/cancel') ?>" data-confirm="Cancel your <?= $r['type'] ?> request for &quot;<?= esc($r['title']) ?>&quot;?">
                                <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Cancel</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?></tbody>
        </table></div>
    </div>
<?php endif; ?>

<div class="panel">
    <h2 class="panel-title"><i class="bi bi-journal-bookmark"></i> Loans
        <a class="ms-auto btn btn-sm btn-nook" href="<?= site_url('catalog') ?>"><i class="bi bi-search me-1"></i>Browse books</a></h2>
    <p class="small text-muted">Books are lent for <?= (int) setting('loan_days') ?> days. Late returns add <?= (int) setting('credits_per_late_day') ?> credit per day (see <a href="<?= site_url('my/credits') ?>">My Credits</a>). Use <strong>Request return</strong>, then bring the book to the library desk.</p>
    <?php if (! $rows): ?>
        <div class="empty"><i class="bi bi-book"></i>You haven't borrowed any books yet. <a href="<?= site_url('catalog') ?>">Browse the collection</a></div>
    <?php else: ?>
        <div class="table-responsive"><table class="table table-nook">
            <thead><tr><th>Book</th><th>Borrowed</th><th>Due</th><th>Returned</th><th>Status</th><th class="text-end"></th></tr></thead>
            <tbody><?php foreach ($rows as $l): ?>
                <tr>
                    <td><a class="text-reset" href="<?= site_url('catalog/' . $l['book_id']) ?>"><?= esc($l['title']) ?></a><div class="sub"><?= esc($l['author']) ?></div></td>
                    <td><?= fmt_date($l['borrowed_at']) ?></td>
                    <td><?= fmt_date($l['due_date']) ?></td>
                    <td><?= fmt_date($l['returned_at']) ?></td>
                    <td><?= status_badge($l['status'], $l['due_date']) ?>
                        <?php if ($l['days_late'] > 0): ?><div class="sub text-danger"><?= $l['days_late'] ?> day(s) late · ~+<?= $l['est_credits'] ?> credits so far</div><?php endif; ?></td>
                    <td class="actions">
                        <?php if ($l['status'] === 'borrowed' && isset($pendingReturns[(int) $l['id']])): ?>
                            <span class="pill pill-warning">Return requested</span>
                        <?php elseif ($l['status'] === 'borrowed'): ?>
                            <form class="d-inline" method="post" action="<?= site_url('my/loans/' . $l['id'] . '/return-request') ?>"
                                  data-confirm="Request to return &quot;<?= esc($l['title']) ?>&quot;? Please bring the book to the library desk.">
                                <?= csrf_field() ?><button class="btn btn-sm btn-outline-nook"><i class="bi bi-box-arrow-in-down-left me-1"></i>Request return</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?></tbody>
        </table></div>
        <?= $pager->links('default', 'nook') ?>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
