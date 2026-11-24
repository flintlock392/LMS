<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('content') ?>
<p class="text-muted mb-4">Good <?= (int) date('G') < 12 ? 'morning' : ((int) date('G') < 18 ? 'afternoon' : 'evening') ?>, <?= esc(session()->get('name')) ?>. Here's the library today, <?= date('l, F j') ?>.</p>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <a class="stat" href="<?= site_url('circulation') ?>">
            <span class="icon blue"><i class="bi bi-journal-arrow-up"></i></span>
            <span><span class="value d-block"><?= $loans['active'] ?></span><span class="label">Books on loan</span></span>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a class="stat" href="<?= site_url('circulation?status=overdue') ?>">
            <span class="icon bad"><i class="bi bi-alarm"></i></span>
            <span><span class="value d-block"><?= $loans['overdue'] ?></span><span class="label">Overdue</span></span>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a class="stat" href="<?= site_url('reservations?status=ready') ?>">
            <span class="icon good"><i class="bi bi-bookmark-check"></i></span>
            <span><span class="value d-block"><?= $resCounts['ready'] ?></span><span class="label">Ready for pickup · <?= $resCounts['pending'] ?> in queue</span></span>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a class="stat" href="<?= site_url('fines') ?>">
            <span class="icon warn"><i class="bi bi-cash-coin"></i></span>
            <span><span class="value d-block"><?= money($fineTotals['unpaid']['total']) ?></span><span class="label">Unpaid fines (<?= $fineTotals['unpaid']['n'] ?>)</span></span>
        </a>
    </div>

    <?php if (is_head()): ?>
        <div class="col-sm-6 col-xl-3">
            <a class="stat" href="<?= site_url('books') ?>">
                <span class="icon"><i class="bi bi-book"></i></span>
                <span><span class="value d-block"><?= number_format($bookStats['titles']) ?></span><span class="label">Titles · <?= number_format($bookStats['copies']) ?> copies</span></span>
            </a>
        </div>
        <div class="col-sm-6 col-xl-3">
            <a class="stat" href="<?= site_url('members') ?>">
                <span class="icon"><i class="bi bi-people"></i></span>
                <span><span class="value d-block"><?= number_format($students) ?></span><span class="label">Active students</span></span>
            </a>
        </div>
        <div class="col-sm-6 col-xl-3">
            <a class="stat" href="<?= site_url('staff') ?>">
                <span class="icon"><i class="bi bi-person-badge"></i></span>
                <span><span class="value d-block"><?= $staffCount ?></span><span class="label">Library staff</span></span>
            </a>
        </div>
        <div class="col-sm-6 col-xl-3">
            <a class="stat" href="<?= site_url('reports') ?>">
                <span class="icon good"><i class="bi bi-cash-stack"></i></span>
                <span><span class="value d-block"><?= money($fineTotals['paid']['total']) ?></span><span class="label">Fines collected (all time)</span></span>
            </a>
        </div>
    <?php else: ?>
        <div class="col-sm-6 col-xl-3">
            <div class="stat"><span class="icon"><i class="bi bi-box-arrow-up-right"></i></span>
                <span><span class="value d-block"><?= $loans['issued_today'] ?></span><span class="label">Issued today</span></span></div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat"><span class="icon good"><i class="bi bi-box-arrow-in-down-left"></i></span>
                <span><span class="value d-block"><?= $loans['returned_today'] ?></span><span class="label">Returned today</span></span></div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat"><span class="icon warn"><i class="bi bi-calendar-event"></i></span>
                <span><span class="value d-block"><?= $loans['due_today'] ?></span><span class="label">Due today</span></span></div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat"><span class="icon good"><i class="bi bi-cash-stack"></i></span>
                <span><span class="value d-block"><?= money($fineTotals['collected_today']) ?></span><span class="label">Fines collected today</span></span></div>
        </div>
    <?php endif; ?>
</div>

<div class="d-flex flex-wrap gap-2 mb-4">
    <a class="btn btn-nook" href="<?= site_url('circulation') ?>"><i class="bi bi-arrow-left-right me-1"></i> Issue / return a book</a>
    <a class="btn btn-outline-nook" href="<?= site_url('members/new') ?>"><i class="bi bi-person-plus me-1"></i> Register a student</a>
    <a class="btn btn-outline-nook" href="<?= site_url('books/new') ?>"><i class="bi bi-plus-lg me-1"></i> Add a book</a>
</div>

<div class="row g-4">
    <div class="col-xl-7">
        <div class="panel">
            <h2 class="panel-title"><i class="bi bi-alarm text-danger"></i> Overdue books
                <a class="ms-auto small" href="<?= site_url('circulation?status=overdue') ?>">View all</a></h2>
            <?php if (! $overdue): ?>
                <div class="empty"><i class="bi bi-emoji-smile"></i>Nothing is overdue.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-nook">
                        <thead><tr><th>Book</th><th>Student</th><th>Due</th><th>Days late</th></tr></thead>
                        <tbody>
                        <?php foreach ($overdue as $l): ?>
                            <tr>
                                <td><?= esc($l['title']) ?></td>
                                <td><?= esc($l['borrower_name']) ?><div class="sub"><?= esc($l['borrower_id_number']) ?></div></td>
                                <td><?= fmt_date($l['due_date']) ?></td>
                                <td><span class="pill pill-danger"><?= (int) floor((time() - strtotime($l['due_date'])) / 86400) ?> days</span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="panel">
            <h2 class="panel-title"><i class="bi bi-clock-history"></i> Recent circulation</h2>
            <?php if (! $recent): ?>
                <div class="empty"><i class="bi bi-inbox"></i>No activity yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-nook">
                        <thead><tr><th>Book</th><th>Student</th><th>Status</th><th>Updated</th></tr></thead>
                        <tbody>
                        <?php foreach ($recent as $l): ?>
                            <tr>
                                <td><?= esc($l['title']) ?></td>
                                <td><?= esc($l['borrower_name']) ?></td>
                                <td><?= status_badge($l['status'], $l['due_date']) ?></td>
                                <td class="sub"><?= fmt_date($l['updated_at'], true) ?></td>
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
            <h2 class="panel-title"><i class="bi bi-bookmark-check text-success"></i> Waiting for pickup
                <a class="ms-auto small" href="<?= site_url('reservations') ?>">Reservations</a></h2>
            <?php if (! $pickups): ?>
                <div class="empty"><i class="bi bi-inbox"></i>No books are being held.</div>
            <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($pickups as $r): ?>
                        <li class="d-flex justify-content-between align-items-start py-2 border-bottom">
                            <span><strong class="d-block"><?= esc($r['title']) ?></strong>
                                <span class="sub small text-muted"><?= esc($r['borrower_name']) ?> · <?= esc($r['borrower_id_number']) ?></span></span>
                            <span class="small text-muted text-end">Hold until<br><?= fmt_date($r['expires_at']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="panel">
            <h2 class="panel-title"><i class="bi bi-calendar-event text-warning"></i> Due in the next 2 days</h2>
            <?php if (! $dueSoon): ?>
                <div class="empty"><i class="bi bi-inbox"></i>No books are due soon.</div>
            <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($dueSoon as $l): ?>
                        <li class="d-flex justify-content-between py-2 border-bottom">
                            <span><?= esc($l['title']) ?><span class="d-block small text-muted"><?= esc($l['borrower_name']) ?></span></span>
                            <span class="small"><?= $l['due_date'] === date('Y-m-d') ? '<span class="pill pill-warning">Today</span>' : fmt_date($l['due_date']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="panel">
            <h2 class="panel-title"><i class="bi bi-box-seam"></i> Titles with no copies available
                <a class="ms-auto small" href="<?= site_url('inventory') ?>">Inventory</a></h2>
            <?php if (! $lowStock): ?>
                <div class="empty"><i class="bi bi-check2-circle"></i>Every title has a copy on the shelf.</div>
            <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($lowStock as $b): ?>
                        <li class="d-flex justify-content-between py-2 border-bottom">
                            <span><?= esc($b['title']) ?></span>
                            <span class="small text-muted"><?= (int) $b['on_loan'] ?> on loan<?= $b['queued'] ? ' · ' . (int) $b['queued'] . ' waiting' : '' ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
