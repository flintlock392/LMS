<?php
use App\Libraries\CreditPolicy;
use App\Models\CreditAppealModel;
use App\Models\CreditLogModel;

$tabs = [
    'students' => ['Students', 'people', null],
    'appeals'  => ['Appeals', 'chat-square-text', $pending],
    'history'  => ['History', 'clock-history', null],
];
?>
<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?><?= esc(module_title('credits')) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- How many students are at each standing level (click to filter) -->
<div class="row g-3 mb-3">
    <?php foreach (CreditPolicy::TIERS as $i => $t):
        $icon = ['good' => 'emoji-smile', 'warning' => 'exclamation-circle', 'probation' => 'exclamation-triangle', 'final' => 'exclamation-octagon', 'blocked' => 'slash-circle'][$t[1]];
        $tone = ['good' => 'good', 'warning' => 'warn', 'probation' => 'warn', 'final' => 'bad', 'blocked' => 'bad'][$t[1]]; ?>
        <div class="col-6 col-md-4 col-xl">
            <a class="stat" href="<?= site_url('credits?tab=students&tier=' . $t[1]) ?>" title="<?= esc($t[6]) ?>">
                <span class="icon <?= $tone ?>"><i class="bi bi-<?= $icon ?>"></i></span>
                <span><span class="value d-block"><?= $tiers[$t[1]] ?></span>
                    <span class="label"><?= esc($t[2]) ?> · <?= CreditPolicy::rangeLabel($i) ?></span></span>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <nav class="module-tabs" aria-label="Credit sections">
        <?php foreach ($tabs as $key => [$label, $icon, $n]): ?>
            <a class="<?= $tab === $key ? 'active' : '' ?>" href="<?= site_url('credits?tab=' . $key) ?>"<?= $tab === $key ? ' aria-current="page"' : '' ?>>
                <i class="bi bi-<?= $icon ?>"></i><?= $label ?><?php if ($n): ?> <span class="tab-n" style="background:#f6dede;color:#8f2f2f"><?= $n ?></span><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php if (is_head()): ?>
        <button class="btn btn-nook ms-auto" type="button" data-bs-toggle="modal" data-bs-target="#adjustModal"><i class="bi bi-plus-slash-minus me-1"></i>Deduct / restore credits</button>
    <?php endif; ?>
</div>

<div class="panel">
    <?php if (! is_head()): ?>
        <p class="small text-muted"><i class="bi bi-eye me-1"></i>View only. Every student starts with <?= CreditPolicy::MAX ?> credits. Credits are deducted automatically for late, damaged or lost books; only the Head Librarian can restore them.</p>
    <?php endif; ?>

    <?php if ($tab === 'students'): ?>
        <form class="filters" method="get" data-filter-form>
            <input type="hidden" name="tab" value="students">
            <input class="form-control form-control-sm" type="search" name="q" value="<?= esc($q) ?>" placeholder="Name, student number or course" aria-label="Search students" data-suggest="students">
            <select class="form-select form-select-sm" name="tier" aria-label="Standing" style="max-width:200px">
                <option value="">Any standing</option>
                <?php foreach (CreditPolicy::TIERS as $t): ?><option value="<?= $t[1] ?>" <?= $tier === $t[1] ? 'selected' : '' ?>><?= esc($t[2]) ?></option><?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-nook" data-apply><i class="bi bi-funnel me-1"></i>Apply</button>
            <?php if ($q !== '' || $tier !== ''): ?><a class="btn-clear" href="<?= site_url('credits') ?>"><i class="bi bi-x-circle"></i>Clear filters</a><?php endif; ?>
        </form>
        <?php if (! $rows): ?>
            <div class="empty"><i class="bi bi-people"></i>No students found.</div>
        <?php else: ?>
            <div class="table-responsive"><table class="table table-nook">
                <thead><tr><th>Student</th><th>Credits</th><th>Can borrow</th><th>On loan</th><th class="text-end"></th></tr></thead>
                <tbody><?php foreach ($rows as $u): $t = CreditPolicy::tier((int) $u['credits']); ?>
                    <tr>
                        <td><a class="fw-semibold text-reset" href="<?= site_url('members/' . $u['id']) ?>"><?= esc($u['name']) ?></a>
                            <div class="sub"><?= esc($u['id_number']) ?><?= $u['course'] ? ' · ' . esc($u['course']) : '' ?></div></td>
                        <td>
                            <?= credit_badge($u['credits']) ?>
                            <div class="credit-meter mt-1 tone-<?= $t['tone'] ?>" aria-hidden="true" title="<?= (int) $u['credits'] ?> of <?= CreditPolicy::MAX ?> credits"><span style="width: <?= (int) $u['credits'] / CreditPolicy::MAX * 100 ?>%"></span></div>
                        </td>
                        <td><?= $t['key'] === 'blocked' ? '<span class="text-danger fw-semibold">No books</span>' : CreditPolicy::maxBooks((int) $u['credits'], $maxLoans) . ' book(s)' ?>
                            <?php if ($t['loanFactor'] < 1 && $t['loanFactor'] > 0): ?><div class="sub">Shorter loans</div><?php endif; ?></td>
                        <td><?= (int) $u['loans'] ?></td>
                        <td class="actions">
                            <?php if (is_head()): ?>
                                <button class="btn btn-sm btn-outline-nook" type="button" data-bs-toggle="modal" data-bs-target="#adjustModal"
                                        data-user-id="<?= $u['id'] ?>" data-user-label="<?= esc($u['name'] . ' (' . $u['id_number'] . ')') ?>" data-credits="<?= (int) $u['credits'] ?>"><i class="bi bi-plus-slash-minus"></i> Adjust</button>
                            <?php endif; ?>
                            <a class="btn btn-sm btn-outline-nook" href="<?= site_url('credits?tab=history&q=' . urlencode($u['id_number'])) ?>" title="History"><i class="bi bi-clock-history"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?></tbody>
            </table></div>
            <?= $pager->links('default', 'nook') ?>
        <?php endif; ?>

    <?php elseif ($tab === 'appeals'): ?>
        <nav class="tabs-nook">
            <?php foreach (['pending' => 'Waiting', 'approved' => 'Approved', 'declined' => 'Declined', 'all' => 'All'] as $key => $label): ?>
                <a class="<?= $status === $key ? 'active' : '' ?>" href="<?= site_url('credits?tab=appeals&status=' . $key) ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </nav>
        <?php if (! $rows): ?>
            <div class="empty"><i class="bi bi-chat-square-text"></i><?= $status === 'pending' ? 'No appeals are waiting.' : 'No appeals to show.' ?></div>
        <?php else: ?>
            <?php foreach ($rows as $a): ?>
                <div class="appeal-card">
                    <div class="d-flex flex-wrap align-items-start gap-2">
                        <div class="flex-grow-1">
                            <a class="fw-semibold text-reset" href="<?= site_url('members/' . $a['user_id']) ?>"><?= esc($a['student_name']) ?></a>
                            <span class="sub"><?= esc($a['student_id_number']) ?></span>
                            <div class="sub"><?= esc(CreditAppealModel::TYPES[$a['type']] ?? $a['type']) ?><?= $a['hours'] ? ' · ' . rtrim(rtrim($a['hours'], '0'), '.') . ' hour(s) of help' : '' ?> · sent <?= fmt_date($a['created_at'], true) ?></div>
                        </div>
                        <div class="text-end"><?= credit_badge($a['credits']) ?><div class="mt-1"><?= status_badge($a['status']) ?></div></div>
                    </div>
                    <p class="appeal-message mt-2 mb-2"><?= nl2br(esc($a['message'])) ?></p>
                    <?php if ($a['status'] !== 'pending'): ?>
                        <div class="sub"><?= $a['status'] === 'approved' ? 'Restored ' . (int) $a['reduced_by'] . ' credit(s)' : 'Declined' ?> by <?= esc($a['handled_by_name'] ?? '—') ?> on <?= fmt_date($a['handled_at'], true) ?><?= $a['response'] ? ' · "' . esc($a['response']) . '"' : '' ?></div>
                    <?php elseif (is_head()): ?>
                        <div class="row g-2 align-items-end">
                            <form class="col-lg-7 d-flex flex-wrap gap-2 align-items-end" method="post" action="<?= site_url('credits/appeals/' . $a['id'] . '/approve') ?>"
                                  data-confirm="Approve <?= esc($a['student_name']) ?>'s appeal and restore the credits?">
                                <?= csrf_field() ?>
                                <?php $missing = CreditPolicy::MAX - (int) $a['credits']; ?>
                                <div><label class="form-label small mb-1" for="r<?= $a['id'] ?>">Restore</label>
                                    <input class="form-control form-control-sm" type="number" id="r<?= $a['id'] ?>" name="reduce_by" min="1" max="<?= max(1, $missing) ?>" value="<?= max(1, min(5, $missing)) ?>" style="width:90px" required></div>
                                <input class="form-control form-control-sm flex-grow-1" name="response" maxlength="200" placeholder="Note to the student (optional)" style="min-width:160px">
                                <button class="btn btn-sm btn-nook"><i class="bi bi-check2 me-1"></i>Approve</button>
                            </form>
                            <form class="col-lg-5 d-flex gap-2 align-items-end" method="post" action="<?= site_url('credits/appeals/' . $a['id'] . '/decline') ?>"
                                  data-confirm="Decline <?= esc($a['student_name']) ?>'s appeal?">
                                <?= csrf_field() ?>
                                <input class="form-control form-control-sm" name="response" maxlength="200" placeholder="Reason (optional)">
                                <button class="btn btn-sm btn-outline-danger text-nowrap">Decline</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="sub">Waiting for the Head Librarian.</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?= $pager->links('default', 'nook') ?>
        <?php endif; ?>

    <?php else: ?>
        <form class="filters" method="get" data-filter-form>
            <input type="hidden" name="tab" value="history">
            <input class="form-control form-control-sm" type="search" name="q" value="<?= esc($q) ?>" placeholder="Student name, number or book" aria-label="Search history" data-suggest="students">
            <select class="form-select form-select-sm" name="direction" aria-label="Change" style="max-width:180px">
                <option value="">Deducted and restored</option>
                <option value="deducted" <?= $direction === 'deducted' ? 'selected' : '' ?>>Deducted only</option>
                <option value="restored" <?= $direction === 'restored' ? 'selected' : '' ?>>Restored only</option>
            </select>
            <button class="btn btn-sm btn-nook" data-apply><i class="bi bi-funnel me-1"></i>Apply</button>
            <?php if ($q !== '' || $direction !== ''): ?><a class="btn-clear" href="<?= site_url('credits?tab=history') ?>"><i class="bi bi-x-circle"></i>Clear filters</a><?php endif; ?>
        </form>
        <?php if (! $rows): ?>
            <div class="empty"><i class="bi bi-clock-history"></i>No credit changes yet.</div>
        <?php else: ?>
            <div class="table-responsive"><table class="table table-nook">
                <thead><tr><th>Date</th><th>Student</th><th>Change</th><th>Reason</th><th>By</th></tr></thead>
                <tbody><?php foreach ($rows as $l): $up = $l['change'] > 0; ?>
                    <tr>
                        <td><?= fmt_date($l['created_at']) ?><div class="sub"><?= date('g:i A', strtotime($l['created_at'])) ?></div></td>
                        <td><a class="text-reset" href="<?= site_url('members/' . $l['user_id']) ?>"><?= esc($l['student_name']) ?></a><div class="sub"><?= esc($l['student_id_number']) ?></div></td>
                        <td><span class="pill <?= $up ? 'pill-success' : 'pill-danger' ?>"><?= $up ? '+' : '−' ?><?= abs((int) $l['change']) ?></span>
                            <div class="sub mt-1">Balance: <?= (int) $l['balance_after'] ?></div></td>
                        <td class="fw-medium"><?= esc(CreditLogModel::LABELS[$l['reason']] ?? $l['reason']) ?>
                            <div class="sub fw-normal"><?= esc($l['notes'] ?: ($l['title'] ?? '')) ?></div></td>
                        <td class="sub"><?= esc($l['staff_name'] ?? 'System') ?></td>
                    </tr>
                <?php endforeach; ?></tbody>
            </table></div>
            <?= $pager->links('default', 'nook') ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php if (is_head()): ?>
<!-- Deduct / restore credits -->
<div class="modal fade" id="adjustModal" tabindex="-1" aria-labelledby="adjustTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px"><div class="modal-content adjust-modal">
        <form method="post" action="<?= site_url('credits/adjust') ?>">
            <?= csrf_field() ?>
            <div class="modal-header border-0 pb-0">
                <h2 class="modal-title panel-title mb-0" id="adjustTitle"><i class="bi bi-plus-slash-minus"></i> Deduct / restore credits</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label" for="adjust-student">Student</label>
                    <input class="form-control" type="search" id="adjust-student" placeholder="Type a name or student number"
                           data-suggest="students" data-suggest-pick="adjust-user-id">
                    <input type="hidden" id="adjust-user-id" name="user_id">
                    <div class="form-text" id="cr-available" aria-live="polite">Choose a student to see how many credits they have.</div></div>
                <div class="mb-3">
                    <div class="btn-group w-100" role="group" aria-label="Deduct or restore">
                        <input type="radio" class="btn-check" name="direction" id="cr-deduct" value="deduct" checked>
                        <label class="btn btn-outline-danger" for="cr-deduct"><i class="bi bi-dash-lg"></i> Deduct credits</label>
                        <input type="radio" class="btn-check" name="direction" id="cr-restore" value="restore">
                        <label class="btn btn-outline-success" for="cr-restore"><i class="bi bi-plus-lg"></i> Restore credits</label>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-4"><label class="form-label" for="cr-amount">Credits</label>
                        <input class="form-control" type="number" id="cr-amount" name="amount" min="1" max="<?= CreditPolicy::MAX ?>" value="1" required aria-describedby="cr-limit">
                        <div class="form-text" id="cr-limit"></div></div>
                    <div class="col-8"><label class="form-label" for="cr-reason">Reason</label>
                        <select class="form-select" id="cr-reason" name="reason" required>
                            <optgroup label="Deduct" data-dir="deduct"><?php foreach (CreditLogModel::DEDUCT_REASONS as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?></optgroup>
                            <optgroup label="Restore" data-dir="restore"><?php foreach (CreditLogModel::RESTORE_REASONS as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?></optgroup>
                        </select></div>
                </div>
                <div class="mb-3"><label class="form-label" for="cr-notes">Notes</label>
                    <input class="form-control" id="cr-notes" name="notes" maxlength="255" placeholder="e.g. 3 hours shelving books on Nov 24"></div>
                <button class="btn btn-nook w-100">Save</button>
                <p class="form-text mb-0 mt-2">Credits stay between 0 and <?= CreditPolicy::MAX ?>. Every student starts with <?= CreditPolicy::MAX ?>; at 0 they are blocked from borrowing, and restoring credits settles the status.</p>
            </div>
        </form>
    </div></div>
</div>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php if (is_head()): ?>
<script>
    (function () {
        const modal = document.getElementById('adjustModal');
        const reason = document.getElementById('cr-reason');
        // Only show reasons that match "Deduct" or "Restore".
        function sync() {
            const dir = document.querySelector('input[name="direction"]:checked').value;
            reason.querySelectorAll('optgroup').forEach(g => { g.hidden = g.dataset.dir !== dir; });
            const cur = reason.options[reason.selectedIndex];
            if (!cur || cur.parentElement.dataset.dir !== dir) {
                reason.value = reason.querySelector('optgroup[data-dir="' + dir + '"] option').value;
            }
        }
        // The most that can be deducted is what the student has left;
        // the most that can be restored is what is missing to reach the full amount.
        const MAX = <?= CreditPolicy::MAX ?>;
        const amount = document.getElementById('cr-amount'), limit = document.getElementById('cr-limit');
        const avail = document.getElementById('cr-available'), save = modal.querySelector('form button.btn-nook');
        let credits = null;
        function cap() {
            const dir = document.querySelector('input[name="direction"]:checked').value;
            if (credits === null) {
                amount.max = MAX; limit.textContent = ''; save.disabled = false;
                avail.textContent = 'Choose a student to see how many credits they have.';
                return;
            }
            avail.innerHTML = 'Available: <strong>' + credits + ' of ' + MAX + '</strong> credits';
            const most = dir === 'deduct' ? credits : MAX - credits;
            amount.max = Math.max(1, most);
            if (most <= 0) {
                limit.textContent = dir === 'deduct' ? 'No credits left to deduct.' : 'Already has all ' + MAX + ' credits.';
                limit.classList.add('text-danger'); save.disabled = true;
                return;
            }
            limit.classList.remove('text-danger'); save.disabled = false;
            limit.textContent = 'Up to ' + most;
            if (+amount.value > most) { amount.value = most; }
            if (+amount.value < 1) { amount.value = 1; }
        }
        function setStudent(c) { credits = (c === null || c === undefined || c === '') ? null : parseInt(c, 10); cap(); }
        document.querySelectorAll('input[name="direction"]').forEach(r => r.addEventListener('change', function () { sync(); cap(); }));
        amount.addEventListener('input', function () { if (amount.max && +amount.value > +amount.max) { amount.value = amount.max; } });
        document.getElementById('adjust-user-id').addEventListener('suggest:pick', e => setStudent(e.detail ? e.detail.credits : null));
        sync();
        cap();
        // "Adjust" on a row pre-fills the student and their balance.
        modal.addEventListener('show.bs.modal', function (e) {
            const b = e.relatedTarget;
            const box = document.getElementById('adjust-student'), id = document.getElementById('adjust-user-id');
            if (b && b.dataset.userId) {
                id.value = b.dataset.userId; box.value = b.dataset.userLabel; box.classList.add('is-valid');
                setStudent(b.dataset.credits);
            } else if (!id.value) {
                setStudent(null);
            }
        });
    })();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
