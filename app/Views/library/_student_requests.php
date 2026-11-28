<?php
/**
 * Library → Requested: "View requests" pop-up for a student with several waiting requests.
 * Tick the books, then approve & issue them all, or decline them all with one reason.
 *
 * @var array<int, list<array>> $studentRequests user_id => requests (oldest first)
 * @var array<int, array>       $studentInfo     user_id => name, id_number, course, credits, on_loan, limit
 */
$dueIfToday = fmt_date(date('Y-m-d', strtotime('+' . (int) setting('loan_days') . ' days')));
foreach ($studentRequests as $uid => $list):
    $u    = $studentInfo[$uid] ?? ['id' => $uid, 'name' => $list[0]['borrower_name'], 'id_number' => $list[0]['borrower_id_number'], 'course' => $list[0]['student_course'], 'credits' => 0, 'on_loan' => 0, 'limit' => 0];
    $room = max(0, (int) $u['limit'] - (int) $u['on_loan']);   // how many more books they may take
    $left = $room; ?>
<div class="modal fade" id="reqModal-<?= $uid ?>" tabindex="-1" aria-labelledby="reqTitle-<?= $uid ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content issue-modal">
        <form method="post" action="<?= site_url('requests/student/' . $uid . '/approve') ?>" class="req-form" data-room="<?= $room ?>">
            <?= csrf_field() ?>
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <?= user_avatar($u, 'avatar-md') ?>
                    <div>
                        <h2 class="modal-title fs-5 mb-0" id="reqTitle-<?= $uid ?>"><?= esc($u['name']) ?> · <?= count($list) ?> requests</h2>
                        <p class="small text-muted mb-0"><?= esc($u['id_number']) ?><?= $u['course'] ? ' · ' . esc($u['course']) : '' ?></p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="req-status mb-3">
                    <?= credit_badge((int) $u['credits']) ?>
                    <span><?= (int) $u['on_loan'] ?> of <?= (int) $u['limit'] ?> books on loan</span>
                    <span class="<?= $room === 0 ? 'text-danger fw-semibold' : '' ?>">Can take <strong><?= $room ?></strong> more</span>
                    <label class="form-check ms-auto mb-0 small"><input class="form-check-input" type="checkbox" data-req-all> Select all</label>
                </div>

                <div class="req-list">
                    <?php foreach ($list as $r):
                        $isBorrow = $r['type'] === 'borrow';
                        $free     = (int) $r['available'];
                        // Tick by default the borrow requests that can be issued now (on the shelf, within the loan limit).
                        $tick = $isBorrow && $free > 0 && $left > 0;
                        if ($tick) { $left--; } ?>
                        <div class="req-item">
                            <input class="form-check-input" type="checkbox" name="ids[]" value="<?= $r['id'] ?>" id="req-<?= $r['id'] ?>" data-type="<?= $r['type'] ?>" <?= $tick ? 'checked' : '' ?>>
                            <label for="req-<?= $r['id'] ?>" class="req-book">
                                <?= book_cover($r, 'cover-sm', 'S') ?>
                                <span class="min-w-0">
                                    <span class="d-block fw-semibold text-truncate"><?= esc($r['title']) ?></span>
                                    <span class="d-block sub"><?= $r['edition'] ? esc($r['edition']) . ' · ' : '' ?>ISBN <?= esc($r['isbn']) ?></span>
                                    <span class="d-block sub">Requested <?= fmt_date($r['created_at'], true) ?></span>
                                </span>
                            </label>
                            <div class="req-detail">
                                <span class="pill <?= $isBorrow ? 'pill-info' : 'pill-warning' ?>"><i class="bi bi-<?= $isBorrow ? 'bookmark-plus' : 'box-arrow-in-down-left' ?> me-1"></i><?= $isBorrow ? 'Borrow' : 'Return' ?></span>
                                <?php if ($isBorrow): ?>
                                    <?= $free > 0 ? '<span class="pill pill-success">' . $free . ' on the shelf</span>' : '<span class="pill pill-danger">None on the shelf</span>' ?>
                                <?php else: ?>
                                    <div class="sub mt-1">Due <?= fmt_date($r['due_date']) ?><?= $r['days_late'] > 0 ? ' · <span class="text-danger">' . $r['days_late'] . 'd late · −' . $r['est_credits'] . ' credits</span>' : ' · on time' ?></div>
                                    <button class="btn btn-sm btn-outline-nook mt-1" type="button" data-bs-toggle="modal" data-bs-target="#returnModal"
                                            data-action="<?= site_url('requests/' . $r['id'] . '/receive') ?>"
                                            data-title="<?= esc($r['title']) ?>" data-student="<?= esc($r['borrower_name']) ?>"
                                            data-late="<?= $r['days_late'] > 0 ? $r['days_late'] . ' day(s) late — ' . $r['est_credits'] . ' credit(s) deducted for lateness.' : 'On time — no credits deducted.' ?>">
                                        <i class="bi bi-box-arrow-in-down-left me-1"></i>Receive return</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="modal-footer req-footer">
                <p class="form-text w-100 mb-0">Approved books are issued now and due back <strong><?= $dueIfToday ?></strong>. Return requests need their condition, so use <em>Receive return</em> for those.</p>
                <input class="form-control form-control-sm req-reason" name="reason" maxlength="200" placeholder="Reason for declining (optional), e.g. book is being repaired" aria-label="Reason for declining">
                <button class="btn btn-outline-danger" formaction="<?= site_url('requests/student/' . $uid . '/decline') ?>" data-req-decline><i class="bi bi-x-lg me-1"></i>Decline <span data-n-all>0</span></button>
                <button class="btn btn-nook" data-req-approve><i class="bi bi-check2 me-1"></i>Approve &amp; issue <span data-n-borrow>0</span></button>
            </div>
        </form>
    </div></div>
</div>
<?php endforeach; ?>
