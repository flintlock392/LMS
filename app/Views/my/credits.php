<?php
use App\Libraries\CreditPolicy;
use App\Models\CreditAppealModel;
use App\Models\CreditLogModel;

$tier     = CreditPolicy::tier($credits);
$canHave  = CreditPolicy::maxBooks($credits, $maxLoans);
$days     = CreditPolicy::loanDays($credits, $loanDays);
$waiting  = array_filter($appeals, static fn ($a) => $a['status'] === 'pending');
?>
<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?>My Credits<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-4">
    <div class="col-xl-5">
        <div class="panel text-center">
            <div class="credit-ring tone-<?= $tier['tone'] ?>">
                <span class="n"><?= $credits ?></span><span class="of">of <?= CreditPolicy::MAX ?></span>
            </div>
            <h2 class="h5 mt-3 mb-1"><?= esc($tier['label']) ?></h2>
            <p class="text-muted small mb-3"><?= esc($tier['explain']) ?></p>
            <div class="d-flex justify-content-center gap-4 small">
                <div><div class="fw-semibold fs-5"><?= $tier['key'] === 'blocked' ? 0 : $canHave ?></div>books you may have</div>
                <div><div class="fw-semibold fs-5"><?= $loans ?></div>on loan now</div>
                <div><div class="fw-semibold fs-5"><?= $tier['key'] === 'blocked' ? '—' : $days ?></div>days per loan</div>
            </div>
            <?php if ($tier['worse'] !== null): ?>
                <p class="small text-muted mt-3 mb-0">Losing <?= $tier['toWorse'] ?> more credit<?= $tier['toWorse'] === 1 ? '' : 's' ?> moves you to "<?= esc($tier['worse']) ?>".</p>
            <?php endif; ?>
        </div>

        <div class="panel small">
            <h3 class="panel-title"><i class="bi bi-info-circle"></i> How credits work</h3>
            <p class="mb-2">You start with <strong><?= CreditPolicy::MAX ?> credits</strong>. Credits are deducted when a book is late
                (<?= (int) setting('credits_per_late_day') ?> per day, up to <?= (int) setting('max_late_credits') ?>), damaged (−<?= (int) setting('damaged_credits') ?>) or lost (−<?= (int) setting('lost_credits') ?>).</p>
            <ul class="mb-2 ps-3">
                <?php foreach (CreditPolicy::TIERS as $i => $t): ?>
                    <li class="<?= $t[1] === $tier['key'] ? 'fw-semibold' : '' ?>"><?= CreditPolicy::rangeLabel($i) ?>: <?= esc($t[2]) ?>. <?= esc($t[6]) ?></li>
                <?php endforeach; ?>
            </ul>
            <p class="mb-0"><strong>To get credits back</strong>, appeal to the Head Librarian or help out in the library, then report it below.</p>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="panel">
            <h3 class="panel-title"><i class="bi bi-chat-square-text"></i> Ask to get credits back</h3>
            <?php if ($credits >= CreditPolicy::MAX): ?>
                <p class="text-muted mb-0">You have all <?= CreditPolicy::MAX ?> credits. Keep returning books on time!</p>
            <?php elseif ($waiting): ?>
                <p class="mb-0"><span class="pill pill-warning">Waiting for review</span> Your appeal is with the Head Librarian. You'll see the answer below.</p>
            <?php else: ?>
                <form method="post" action="<?= site_url('my/credits/appeal') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <div class="btn-group w-100" role="group" aria-label="Type">
                            <input type="radio" class="btn-check" name="type" id="t-appeal" value="appeal" <?= old('type', 'appeal') === 'appeal' ? 'checked' : '' ?>
                                   onchange="document.getElementById('hours-wrap').hidden=true">
                            <label class="btn btn-outline-nook" for="t-appeal"><i class="bi bi-chat-left-text me-1"></i>Appeal</label>
                            <input type="radio" class="btn-check" name="type" id="t-service" value="library_service" <?= old('type') === 'library_service' ? 'checked' : '' ?>
                                   onchange="document.getElementById('hours-wrap').hidden=false">
                            <label class="btn btn-outline-nook" for="t-service"><i class="bi bi-person-workspace me-1"></i>I helped in the library</label>
                        </div>
                    </div>
                    <div class="mb-3" id="hours-wrap" <?= old('type') === 'library_service' ? '' : 'hidden' ?>>
                        <label class="form-label" for="hours">Hours of help</label>
                        <input class="form-control" type="number" step="0.5" min="0.5" max="100" id="hours" name="hours" value="<?= esc(old('hours', '', false)) ?>" style="max-width:140px">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="message">Message to the Head Librarian</label>
                        <textarea class="form-control" id="message" name="message" rows="4" maxlength="2000" required
                                  placeholder="Explain what happened, or what you helped with and when."><?= esc(old('message', '', false)) ?></textarea>
                    </div>
                    <button class="btn btn-nook">Send</button>
                </form>
            <?php endif; ?>
        </div>

        <?php if ($appeals): ?>
            <div class="panel">
                <h3 class="panel-title"><i class="bi bi-inboxes"></i> My appeals</h3>
                <?php foreach ($appeals as $a): ?>
                    <div class="appeal-card">
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <strong><?= esc(CreditAppealModel::TYPES[$a['type']] ?? $a['type']) ?></strong>
                            <span class="sub">sent <?= fmt_date($a['created_at'], true) ?></span>
                            <span class="ms-auto"><?= status_badge($a['status']) ?></span>
                        </div>
                        <p class="appeal-message mt-2 mb-2"><?= nl2br(esc($a['message'])) ?></p>
                        <?php if ($a['status'] === 'approved'): ?>
                            <div class="small text-success">Restored <?= (int) $a['reduced_by'] ?> credit(s).<?= $a['response'] ? ' "' . esc($a['response']) . '"' : '' ?></div>
                        <?php elseif ($a['status'] === 'declined'): ?>
                            <div class="small text-danger">Declined.<?= $a['response'] ? ' Reason: ' . esc($a['response']) : '' ?></div>
                        <?php else: ?>
                            <form method="post" action="<?= site_url('my/credits/appeals/' . $a['id'] . '/cancel') ?>" data-confirm="Cancel this appeal?">
                                <?= csrf_field() ?><button class="btn btn-sm btn-link p-0">Cancel appeal</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="panel">
            <h3 class="panel-title"><i class="bi bi-clock-history"></i> Credit history</h3>
            <?php if (! $history): ?>
                <div class="empty"><i class="bi bi-emoji-smile"></i>No changes yet. You still have all your credits.</div>
            <?php else: ?>
                <div class="table-responsive"><table class="table table-nook">
                    <thead><tr><th>Date</th><th>Change</th><th>Reason</th><th>Balance</th></tr></thead>
                    <tbody><?php foreach ($history as $l): $up = $l['change'] > 0; ?>
                        <tr><td><?= fmt_date($l['created_at']) ?></td>
                            <td><span class="pill <?= $up ? 'pill-success' : 'pill-danger' ?>"><?= $up ? '+' : '−' ?><?= abs((int) $l['change']) ?></span></td>
                            <td><?= esc(CreditLogModel::LABELS[$l['reason']] ?? $l['reason']) ?><div class="sub"><?= esc($l['notes'] ?: ($l['title'] ?? '')) ?></div></td>
                            <td><?= (int) $l['balance_after'] ?></td></tr>
                    <?php endforeach; ?></tbody>
                </table></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
