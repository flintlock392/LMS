<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?><?= esc($user['name']) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<a class="small d-inline-block mb-3" href="<?= site_url('members') ?>"><i class="bi bi-arrow-left"></i> <?= esc(module_title('members')) ?></a>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="panel">
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="avatar" style="width:52px;height:52px;font-size:1.1rem"><?= esc(strtoupper(mb_substr($user['name'], 0, 1))) ?></span>
                <div><h2 class="h5 mb-0"><?= esc($user['name']) ?></h2><?= status_badge($user['status']) ?></div>
            </div>
            <dl class="row small mb-0">
                <dt class="col-5">Student no.</dt><dd class="col-7"><?= esc($user['id_number']) ?></dd>
                <dt class="col-5">Email</dt><dd class="col-7 text-break"><?= esc($user['email']) ?></dd>
                <dt class="col-5">Phone</dt><dd class="col-7"><?= esc($user['phone'] ?? '—') ?></dd>
                <dt class="col-5">Course</dt><dd class="col-7"><?= esc($user['course'] ?? '—') ?></dd>
                <dt class="col-5">Year</dt><dd class="col-7"><?= esc($user['year_level'] ?? '—') ?></dd>
                <dt class="col-5">Registered</dt><dd class="col-7"><?= fmt_date($user['created_at']) ?></dd>
                <dt class="col-5">Last login</dt><dd class="col-7"><?= fmt_date($user['last_login_at'], true) ?></dd>
                <dt class="col-5">Credits</dt><dd class="col-7"><?= credit_badge($user['credits']) ?></dd>
            </dl>
            <?php if (is_head()): ?>
                <hr>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-sm btn-outline-nook" href="<?= site_url('members/' . $user['id'] . '/edit') ?>"><i class="bi bi-pencil"></i> Edit / reset password</a>
                    <form method="post" action="<?= site_url('members/' . $user['id'] . '/status') ?>" data-confirm="<?= $user['status'] === 'active' ? 'Suspend this student? They will not be able to log in or borrow.' : 'Reactivate this student?' ?>">
                        <?= csrf_field() ?><button class="btn btn-sm <?= $user['status'] === 'active' ? 'btn-outline-warning' : 'btn-outline-success' ?>"><?= $user['status'] === 'active' ? 'Suspend' : 'Activate' ?></button>
                    </form>
                    <form method="post" action="<?= site_url('members/' . $user['id'] . '/archive') ?>" data-confirm="Archive this student account? They won't be able to log in. You can restore it from the Archive page.">
                        <?= csrf_field() ?><button class="btn btn-sm btn-outline-nook"><i class="bi bi-archive me-1"></i>Archive</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="panel">
            <h3 class="panel-title"><i class="bi bi-journal-bookmark"></i> Borrowing history
                <a class="ms-auto small" href="<?= site_url('library?tab=all&q=' . urlencode($user['id_number'])) ?>">Open in Library</a></h3>
            <?php if (! $loans): ?>
                <div class="empty"><i class="bi bi-inbox"></i>No books borrowed yet.</div>
            <?php else: ?>
                <div class="table-responsive"><table class="table table-nook">
                    <thead><tr><th>Book</th><th>Borrowed</th><th>Due</th><th>Returned</th><th>Status</th></tr></thead>
                    <tbody><?php foreach ($loans as $l): ?>
                        <tr><td><div class="book-cell"><?= book_cover($l, 'cover-sm', 'S') ?><div><?= esc($l['title']) ?><span class="d-block small text-muted">ISBN <?= esc($l['isbn']) ?></span></div></div></td><td><?= fmt_date($l['borrowed_at']) ?></td><td><?= fmt_date($l['due_date']) ?></td>
                            <td><?= fmt_date($l['returned_at']) ?></td><td><?= status_badge($l['status'], $l['due_date']) ?></td></tr>
                    <?php endforeach; ?></tbody>
                </table></div>
            <?php endif; ?>
        </div>
        <div class="panel">
            <?php $t = \App\Libraries\CreditPolicy::tier((int) $user['credits']); ?>
            <h3 class="panel-title"><i class="bi bi-shield-exclamation"></i> Credits
                <a class="ms-auto small" href="<?= site_url('credits?tab=history&q=' . urlencode($user['id_number'])) ?>">Open in <?= esc(module_title('credits')) ?></a></h3>
            <p class="small mb-3"><?= credit_badge($user['credits']) ?> <span class="text-muted ms-1"><?= esc($t['explain']) ?></span></p>
            <?php if (! $creditLog): ?>
                <div class="empty"><i class="bi bi-emoji-smile"></i>No credit changes yet. This student still has all their credits.</div>
            <?php else: ?>
                <div class="table-responsive"><table class="table table-nook">
                    <thead><tr><th>Date</th><th>Change</th><th>Reason</th><th>Balance</th></tr></thead>
                    <tbody><?php foreach ($creditLog as $l): $up = $l['change'] > 0; ?>
                        <tr><td><?= fmt_date($l['created_at']) ?></td>
                            <td><span class="pill <?= $up ? 'pill-success' : 'pill-danger' ?>"><?= $up ? '+' : '−' ?><?= abs((int) $l['change']) ?></span></td>
                            <td><?= esc(\App\Models\CreditLogModel::LABELS[$l['reason']] ?? $l['reason']) ?><div class="sub"><?= esc($l['notes'] ?: ($l['title'] ?? '')) ?></div></td>
                            <td><?= (int) $l['balance_after'] ?></td></tr>
                    <?php endforeach; ?></tbody>
                </table></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
