<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?>Reservations<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-4">
    <div class="col-xl-4">
        <div class="panel">
            <h2 class="panel-title"><i class="bi bi-bookmark-plus"></i> Reserve for a student</h2>
            <form method="post" action="<?= site_url('reservations/create') ?>">
                <?= csrf_field() ?>
                <div class="mb-3"><label class="form-label" for="user_id">Student</label>
                    <select class="form-select" id="user_id" name="user_id" required>
                        <option value="">Choose…</option>
                        <?php foreach ($students as $s): ?><option value="<?= $s['id'] ?>"><?= esc($s['name']) ?> (<?= esc($s['id_number']) ?>)</option><?php endforeach; ?>
                    </select></div>
                <div class="mb-3"><label class="form-label" for="book_id">Book</label>
                    <select class="form-select" id="book_id" name="book_id" required>
                        <option value="">Choose…</option>
                        <?php foreach ($books as $b): ?><option value="<?= $b['id'] ?>"><?= esc($b['title']) ?><?= (int) $b['available'] > 0 ? ' (' . (int) $b['available'] . ' available)' : ' (on loan)' ?></option><?php endforeach; ?>
                    </select></div>
                <button class="btn btn-nook w-100">Place reservation</button>
            </form>
        </div>
        <div class="panel small text-muted">
            <h3 class="panel-title small mb-2"><i class="bi bi-info-circle"></i> How reservations work</h3>
            <ol class="ps-3 mb-0">
                <li>Students join a queue for a title (max <?= (int) setting('max_reservations') ?> each).</li>
                <li>When a copy is free, the first student in line becomes <em>Ready for pickup</em>.</li>
                <li>The copy is held for <?= (int) setting('hold_days') ?> day(s); after that it passes to the next student.</li>
            </ol>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="panel">
            <nav class="tabs-nook">
                <?php foreach (['open' => ['Open', $counts['pending'] + $counts['ready']], 'ready' => ['Ready for pickup', $counts['ready']], 'pending' => ['In queue', $counts['pending']], 'closed' => ['Closed', null], 'all' => ['All', null]] as $key => [$label, $n]): ?>
                    <a class="<?= $filters['status'] === $key ? 'active' : '' ?>" href="<?= site_url('reservations?status=' . $key) ?>"><?= $label ?><?php if ($n !== null): ?><span class="count"><?= $n ?></span><?php endif; ?></a>
                <?php endforeach; ?>
            </nav>
            <form class="filters" method="get">
                <input type="hidden" name="status" value="<?= esc($filters['status']) ?>">
                <input class="form-control form-control-sm" type="search" name="q" value="<?= esc($filters['q']) ?>" placeholder="Book or student" aria-label="Search">
                <button class="btn btn-sm btn-nook"><i class="bi bi-search"></i></button>
            </form>
            <?php if (! $rows): ?>
                <div class="empty"><i class="bi bi-bookmark"></i>No reservations to show.</div>
            <?php else: ?>
                <div class="table-responsive"><table class="table table-nook">
                    <thead><tr><th>Book</th><th>Student</th><th>Reserved</th><th>Status</th><th class="text-end"></th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= esc($r['title']) ?><div class="sub"><?= esc($r['author']) ?></div></td>
                            <td><?= esc($r['borrower_name']) ?><div class="sub"><?= esc($r['borrower_id_number']) ?></div></td>
                            <td><?= fmt_date($r['reserved_at'], true) ?></td>
                            <td><?= status_badge($r['status']) ?>
                                <?php if ($r['status'] === 'pending'): ?><div class="sub">#<?= $r['position'] ?> in line</div><?php endif; ?>
                                <?php if ($r['status'] === 'ready'): ?><div class="sub">Hold until <?= fmt_date($r['expires_at'], true) ?></div><?php endif; ?></td>
                            <td class="actions">
                                <?php if ($r['status'] === 'ready'): ?>
                                    <form class="d-inline" method="post" action="<?= site_url('reservations/' . $r['id'] . '/issue') ?>"><?= csrf_field() ?>
                                        <button class="btn btn-sm btn-nook">Issue</button></form>
                                <?php endif; ?>
                                <?php if (in_array($r['status'], ['pending', 'ready'], true)): ?>
                                    <form class="d-inline" method="post" action="<?= site_url('reservations/' . $r['id'] . '/cancel') ?>" data-confirm="Cancel this reservation?"><?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-danger">Cancel</button></form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
                <?= $pager->links('default', 'nook') ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
