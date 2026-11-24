<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('content') ?>
<p class="text-muted mb-4">Hi <?= esc(explode(' ', (string) session()->get('name'))[0]) ?>! Your library card number is <strong><?= esc(session()->get('idNumber')) ?></strong>.</p>

<div class="row g-3 mb-4">
    <?php $tier = \App\Libraries\CreditPolicy::tier($credits); $limit = \App\Libraries\CreditPolicy::maxBooks($credits, (int) setting('max_loans')); ?>
    <div class="col-sm-6 col-xl-3">
        <a class="stat" href="<?= site_url('my/loans') ?>"><span class="icon blue"><i class="bi bi-journal-bookmark"></i></span>
            <span><span class="value d-block"><?= count($loans) ?> / <?= $limit ?></span><span class="label">Books borrowed</span></span></a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a class="stat" href="<?= site_url('catalog') ?>"><span class="icon good"><i class="bi bi-bookmark-plus"></i></span>
            <span><span class="value d-block"><?= max(0, $limit - count($loans)) ?></span><span class="label">More books you can borrow</span></span></a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a class="stat" href="<?= site_url('my/credits') ?>"><span class="icon <?= $credits === 0 ? 'good' : ($tier['tone'] === 'warning' ? 'warn' : 'bad') ?>"><i class="bi bi-shield-check"></i></span>
            <span><span class="value d-block"><?= $credits ?> / <?= \App\Libraries\CreditPolicy::MAX ?></span><span class="label">Credits · <?= esc($tier['label']) ?></span></span></a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a class="stat" href="<?= site_url('my/loans') ?>"><span class="icon"><i class="bi bi-clock-history"></i></span>
            <span><span class="value d-block"><?= $history ?></span><span class="label">Books read so far</span></span></a>
    </div>
</div>

<?php if ($tier['key'] === 'blocked'): ?>
    <div class="alert alert-danger"><i class="bi bi-slash-circle me-1"></i>
        You can't borrow books right now (<?= $credits ?> credits). <a href="<?= site_url('my/credits') ?>">Appeal to the Head Librarian</a> to settle your status.</div>
<?php elseif ($tier['key'] !== 'good'): ?>
    <div class="alert alert-warning"><i class="bi bi-shield-exclamation me-1"></i>
        <strong><?= esc($tier['label']) ?></strong> (<?= $credits ?> credits): <?= esc($tier['explain']) ?> <a href="<?= site_url('my/credits') ?>">How to lower your credits</a></div>
<?php endif; ?>
<?php if ($estimated > 0): ?>
    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1"></i>
        You have overdue books. Returning them today adds about <strong><?= $estimated ?> credit<?= $estimated === 1 ? '' : 's' ?></strong>, and it grows each day.</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-xl-7">
        <div class="panel">
            <h2 class="panel-title"><i class="bi bi-journal-bookmark"></i> Books you have
                <a class="ms-auto small" href="<?= site_url('my/loans') ?>">History</a></h2>
            <?php if (! $loans): ?>
                <div class="empty"><i class="bi bi-book"></i>You have no books on loan. <a href="<?= site_url('catalog') ?>">Browse the collection</a></div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-nook">
                        <thead><tr><th>Book</th><th>Borrowed</th><th>Due</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($loans as $l): ?>
                            <tr>
                                <td><?= esc($l['title']) ?><div class="sub"><?= esc($l['author']) ?></div></td>
                                <td><?= fmt_date($l['borrowed_at']) ?></td>
                                <td><?= fmt_date($l['due_date']) ?></td>
                                <td><?= status_badge('borrowed', $l['due_date']) ?>
                                    <?php if ($l['days_late'] > 0): ?><div class="sub"><?= $l['days_late'] ?> day(s) late · ~+<?= $l['est_credits'] ?> credits</div><?php endif; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="panel">
            <h2 class="panel-title"><i class="bi bi-stars"></i> New in the collection</h2>
            <?php foreach ($newBooks as $b): ?>
                <a class="d-flex justify-content-between align-items-center py-2 border-bottom text-decoration-none text-reset" href="<?= site_url('catalog/' . $b['id']) ?>">
                    <span><?= esc($b['title']) ?><span class="d-block small text-muted"><?= esc($b['author']) ?></span></span>
                    <?= availability_badge((int) $b['available'], (int) $b['stock_count']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
