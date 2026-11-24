<?php
/**
 * Activity rows grouped by day (newest first).
 * @var list<array> $rows
 */
use App\Models\ActivityLogModel;

$links = [
    'book'        => 'books/',
    'user'        => 'members/',
    'borrowing'   => null,
];
?>
<?php if (! $rows): ?>
    <div class="empty"><i class="bi bi-calendar-x"></i>Nothing was recorded in this period.</div>
<?php else: ?>
    <?php $day = null; foreach ($rows as $r): $d = date('Y-m-d', strtotime($r['created_at'])); ?>
        <?php if ($d !== $day): $day = $d; ?>
            <div class="activity-day"><?= date('l, F j, Y', strtotime($d)) ?><?= $d === date('Y-m-d') ? ' · Today' : '' ?></div>
        <?php endif; ?>
        <?php [$catLabel, $icon] = ActivityLogModel::CATEGORIES[$r['category']] ?? [ucfirst($r['category']), 'dot']; ?>
        <div class="activity-item">
            <span class="time"><?= date('g:i A', strtotime($r['created_at'])) ?></span>
            <span class="ico" title="<?= esc($catLabel) ?>"><i class="bi bi-<?= $icon ?>"></i></span>
            <div>
                <div class="desc"><?= esc($r['description']) ?></div>
                <div class="who">
                    <?php if ($r['user_name']): ?>
                        <i class="bi bi-person"></i> <?= esc($r['user_name']) ?> · <?= esc(role_label($r['user_role'])) ?>
                    <?php else: ?>
                        <i class="bi bi-cpu"></i> System (automatic)
                    <?php endif; ?>
                    · <?= esc($catLabel) ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
