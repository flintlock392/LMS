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
                <dt class="col-5">Unpaid fines</dt><dd class="col-7 <?= $unpaid > 0 ? 'text-danger fw-semibold' : '' ?>"><?= money($unpaid) ?></dd>
            </dl>
            <?php if (is_head()): ?>
                <hr>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-sm btn-outline-nook" href="<?= site_url('members/' . $user['id'] . '/edit') ?>"><i class="bi bi-pencil"></i> Edit / reset password</a>
                    <form method="post" action="<?= site_url('members/' . $user['id'] . '/status') ?>" data-confirm="<?= $user['status'] === 'active' ? 'Suspend this student? They will not be able to log in or borrow.' : 'Reactivate this student?' ?>">
                        <?= csrf_field() ?><button class="btn btn-sm <?= $user['status'] === 'active' ? 'btn-outline-warning' : 'btn-outline-success' ?>"><?= $user['status'] === 'active' ? 'Suspend' : 'Activate' ?></button>
                    </form>
                    <form method="post" action="<?= site_url('members/' . $user['id'] . '/delete') ?>" data-confirm="Delete this student account permanently?">
                        <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
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
                        <tr><td><?= esc($l['title']) ?></td><td><?= fmt_date($l['borrowed_at']) ?></td><td><?= fmt_date($l['due_date']) ?></td>
                            <td><?= fmt_date($l['returned_at']) ?></td><td><?= status_badge($l['status'], $l['due_date']) ?></td></tr>
                    <?php endforeach; ?></tbody>
                </table></div>
            <?php endif; ?>
        </div>
        <div class="panel">
            <h3 class="panel-title"><i class="bi bi-cash-coin"></i> Fines
                <a class="ms-auto small" href="<?= site_url('fines?status=all&q=' . urlencode($user['id_number'])) ?>">Open in <?= esc(module_title('fines')) ?></a></h3>
            <?php if (! $fines): ?>
                <div class="empty"><i class="bi bi-emoji-smile"></i>No fines.</div>
            <?php else: ?>
                <div class="table-responsive"><table class="table table-nook">
                    <thead><tr><th>Date</th><th>Reason</th><th>Amount</th><th>Status</th></tr></thead>
                    <tbody><?php foreach ($fines as $f): ?>
                        <tr><td><?= fmt_date($f['created_at']) ?></td>
                            <td><?= esc(\App\Models\FineModel::REASONS[$f['reason']] ?? $f['reason']) ?><div class="sub"><?= esc($f['title'] ?? $f['notes'] ?? '') ?></div></td>
                            <td><?= money($f['amount']) ?></td><td><?= status_badge($f['status']) ?></td></tr>
                    <?php endforeach; ?></tbody>
                </table></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
