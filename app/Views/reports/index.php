<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?><?= esc(module_title('reports')) ?><?= $this->endSection() ?>

<?php $qs = '?from=' . $from . '&to=' . $to; ?>
<?= $this->section('content') ?>
<div class="panel mb-4 no-print">
    <form class="d-flex flex-wrap gap-2 align-items-end" method="get">
        <div><label class="form-label small mb-1" for="from">From</label><input class="form-control form-control-sm" type="date" id="from" name="from" value="<?= esc($from) ?>"></div>
        <div><label class="form-label small mb-1" for="to">To</label><input class="form-control form-control-sm" type="date" id="to" name="to" value="<?= esc($to) ?>"></div>
        <button class="btn btn-sm btn-nook">Update</button>
        <div class="btn-group btn-group-sm ms-1" role="group" aria-label="Quick ranges">
            <a class="btn btn-outline-nook" href="?from=<?= date('Y-m-d') ?>&to=<?= date('Y-m-d') ?>">Today</a>
            <a class="btn btn-outline-nook" href="?from=<?= date('Y-m-d', strtotime('-6 days')) ?>&to=<?= date('Y-m-d') ?>">7 days</a>
            <a class="btn btn-outline-nook" href="?from=<?= date('Y-m-d', strtotime('-29 days')) ?>&to=<?= date('Y-m-d') ?>">30 days</a>
            <a class="btn btn-outline-nook" href="?from=<?= date('Y-01-01') ?>&to=<?= date('Y-m-d') ?>">This year</a>
        </div>
        <div class="ms-auto d-flex gap-2">
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-nook dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-download me-1"></i>Export CSV</button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= site_url('reports/export/loans' . $qs) ?>">Loans in this period</a></li>
                    <li><a class="dropdown-item" href="<?= site_url('reports/export/overdue') ?>">Overdue books (now)</a></li>
                    <li><a class="dropdown-item" href="<?= site_url('reports/export/fines' . $qs) ?>">Fines in this period</a></li>
                    <li><a class="dropdown-item" href="<?= site_url('reports/export/inventory') ?>">Inventory (now)</a></li>
                    <?php if (is_head()): ?><li><a class="dropdown-item" href="<?= site_url('reports/export/students') ?>">Student list</a></li><?php endif; ?>
                </ul>
            </div>
            <button class="btn btn-sm btn-outline-nook" type="button" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
        </div>
    </form>
</div>

<h2 class="h6 text-muted mb-3"><?= esc(setting('library_name')) ?> · <?= fmt_date($from) ?> – <?= fmt_date($to) ?></h2>
<div class="row g-3 mb-4">
    <?php foreach ([
        ['Books issued', $summary['issued'], 'journal-arrow-up', 'blue'],
        ['Books returned', $summary['returned'], 'journal-arrow-down', 'good'],
        ['Returned late', $summary['late_returns'], 'alarm', 'warn'],
        ['Reported lost', $summary['lost'], 'question-octagon', 'bad'],
        ['Students who borrowed', $summary['borrowers'], 'people', ''],
        ['Reservations placed', $summary['reservations'], 'bookmark-plus', ''],
        ['Fines charged', money($summary['fines_charged']), 'receipt', 'warn'],
        ['Fines collected', money($summary['fines_paid']), 'cash-stack', 'good'],
    ] as [$label, $value, $icon, $tone]): ?>
        <div class="col-6 col-lg-3"><div class="stat"><span class="icon <?= $tone ?>"><i class="bi bi-<?= $icon ?>"></i></span>
            <span><span class="value d-block"><?= $value ?></span><span class="label"><?= $label ?></span></span></div></div>
    <?php endforeach; ?>
</div>

<?php if (is_head()): ?>
    <?php $maxLoans = max(1, max(array_column($months, 'loans'))); $maxFines = max(1, max(array_column($months, 'fines'))); ?>
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="panel h-100">
                <h3 class="panel-title">Books borrowed per month</h3>
                <div class="vbars" role="img" aria-label="Books borrowed per month, last six months">
                    <?php foreach ($months as $m): ?>
                        <div class="col" title="<?= $m['label'] ?>: <?= $m['loans'] ?> books borrowed">
                            <span class="v"><?= $m['loans'] ?></span>
                            <span class="bar" style="height: <?= round($m['loans'] / $maxLoans * 100) ?>%"></span>
                            <span class="m"><?= $m['label'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="panel h-100">
                <h3 class="panel-title">Fines collected per month</h3>
                <div class="vbars" role="img" aria-label="Fines collected per month, last six months">
                    <?php foreach ($months as $m): ?>
                        <div class="col" title="<?= $m['label'] ?>: <?= money($m['fines']) ?> collected">
                            <span class="v"><?= $m['fines'] > 0 ? number_format($m['fines']) : '0' ?></span>
                            <span class="bar" style="height: <?= round($m['fines'] / $maxFines * 100) ?>%"></span>
                            <span class="m"><?= $m['label'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="stat"><span class="icon blue"><i class="bi bi-pie-chart"></i></span><span><span class="value d-block"><?= $utilization ?>%</span><span class="label">Of all copies are on loan right now</span></span></div></div>
        <div class="col-md-4"><div class="stat"><span class="icon"><i class="bi bi-moon-stars"></i></span><span><span class="value d-block"><?= $neverBorrowed ?></span><span class="label">Titles never borrowed</span></span></div></div>
        <div class="col-md-4"><div class="stat"><span class="icon"><i class="bi bi-people"></i></span><span><span class="value d-block"><?= $summary['borrowers'] ?> / <?= $students ?></span><span class="label">Active students who borrowed in this period</span></span></div></div>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-<?= is_head() ? '6' : '5' ?>">
        <div class="panel h-100">
            <h3 class="panel-title"><i class="bi bi-trophy"></i> Most borrowed books</h3>
            <?php if (! $topBooks): ?>
                <div class="empty"><i class="bi bi-inbox"></i>No books borrowed in this period.</div>
            <?php else: $max = max(array_column($topBooks, 'n')); ?>
                <div class="hbar">
                    <?php foreach ($topBooks as $b): ?>
                        <span class="name" title="<?= esc($b['title']) ?> by <?= esc($b['author']) ?>"><?= esc($b['title']) ?></span>
                        <span class="track" title="<?= esc($b['title']) ?>: borrowed <?= (int) $b['n'] ?> time(s)"><span class="fill d-block" style="width: <?= round($b['n'] / $max * 100) ?>%"></span></span>
                        <span class="num"><?= (int) $b['n'] ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (is_head()): ?>
        <div class="col-lg-6">
            <div class="panel h-100">
                <h3 class="panel-title"><i class="bi bi-tags"></i> Borrowing by category</h3>
                <?php if (! $categories): ?>
                    <div class="empty"><i class="bi bi-inbox"></i>No borrowing in this period.</div>
                <?php else: $max = max(array_column($categories, 'n')); ?>
                    <div class="hbar">
                        <?php foreach ($categories as $c): ?>
                            <span class="name"><?= esc($c['name']) ?></span>
                            <span class="track" title="<?= esc($c['name']) ?>: <?= (int) $c['n'] ?> loan(s)"><span class="fill d-block" style="width: <?= round($c['n'] / $max * 100) ?>%"></span></span>
                            <span class="num"><?= (int) $c['n'] ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="panel h-100">
                <h3 class="panel-title"><i class="bi bi-star"></i> Most active readers</h3>
                <?php if (! $topReaders): ?><div class="empty"><i class="bi bi-inbox"></i>No borrowing in this period.</div><?php else: ?>
                    <table class="table table-nook"><thead><tr><th>Student</th><th class="text-end">Books</th></tr></thead><tbody>
                    <?php foreach ($topReaders as $r): ?>
                        <tr><td><a class="text-reset" href="<?= site_url('members/' . $r['id']) ?>"><?= esc($r['name']) ?></a><div class="sub"><?= esc($r['id_number']) ?></div></td><td class="text-end"><?= (int) $r['n'] ?></td></tr>
                    <?php endforeach; ?></tbody></table>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="panel h-100">
                <h3 class="panel-title"><i class="bi bi-person-badge"></i> Staff activity</h3>
                <table class="table table-nook"><thead><tr><th>Staff</th><th class="text-end">Issued</th><th class="text-end">Received</th></tr></thead><tbody>
                <?php foreach ($staffWork as $s): ?>
                    <tr><td><?= esc($s['name']) ?><div class="sub"><?= esc(role_label($s['role'])) ?></div></td><td class="text-end"><?= (int) $s['issued'] ?></td><td class="text-end"><?= (int) $s['received'] ?></td></tr>
                <?php endforeach; ?></tbody></table>
            </div>
        </div>
    <?php endif; ?>

    <div class="col-lg-<?= is_head() ? '12' : '7' ?>">
        <div class="panel h-100">
            <h3 class="panel-title"><i class="bi bi-alarm text-danger"></i> Overdue right now
                <a class="ms-auto small no-print" href="<?= site_url('reports/export/overdue') ?>"><i class="bi bi-download"></i> CSV</a></h3>
            <?php if (! $overdue): ?>
                <div class="empty"><i class="bi bi-emoji-smile"></i>Nothing is overdue.</div>
            <?php else: ?>
                <div class="table-responsive"><table class="table table-nook">
                    <thead><tr><th>Student</th><th>Book</th><th>Due</th><th class="text-end">Days late</th></tr></thead>
                    <tbody><?php foreach ($overdue as $l): ?>
                        <tr><td><?= esc($l['borrower_name']) ?><div class="sub"><?= esc($l['borrower_id_number']) ?></div></td><td><?= esc($l['title']) ?></td>
                            <td><?= fmt_date($l['due_date']) ?></td><td class="text-end"><?= (int) floor((strtotime(date('Y-m-d')) - strtotime($l['due_date'])) / 86400) ?></td></tr>
                    <?php endforeach; ?></tbody>
                </table></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
