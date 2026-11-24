<?php use App\Models\ActivityLogModel; ?>
<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?>Activity Log<?= $this->endSection() ?>

<?php
$base  = ['from' => $filters['from'], 'to' => $filters['to'], 'user_id' => $filters['user_id'], 'q' => $filters['q']];
$link  = static fn (array $extra) => site_url('activity') . '?' . http_build_query(array_filter(array_merge($base, $extra), static fn ($v) => $v !== '' && $v !== null));
$total = array_sum($counts);
$quick = [
    'Today'        => [date('Y-m-d'), date('Y-m-d')],
    'Yesterday'    => [date('Y-m-d', strtotime('-1 day')), date('Y-m-d', strtotime('-1 day'))],
    'Last 7 days'  => [date('Y-m-d', strtotime('-6 days')), date('Y-m-d')],
    'Last 30 days' => [date('Y-m-d', strtotime('-29 days')), date('Y-m-d')],
    'This month'   => [date('Y-m-01'), date('Y-m-d')],
];
?>
<?= $this->section('content') ?>
<a class="small d-inline-block mb-3" href="<?= site_url('library') ?>"><i class="bi bi-arrow-left"></i> Library</a>

<div class="panel">
    <h2 class="panel-title"><i class="bi bi-clock-history"></i> Activity Log
        <a class="ms-auto btn btn-sm btn-outline-nook" href="<?= site_url('activity/export') . '?' . http_build_query($filters) ?>"><i class="bi bi-download me-1"></i>Export CSV</a></h2>

    <form class="date-range mb-2" method="get" data-range-form>
        <div class="field">
            <label for="from">Start date</label>
            <input class="form-control form-control-sm" type="date" id="from" name="from" value="<?= esc($filters['from']) ?>" max="<?= date('Y-m-d') ?>" required aria-describedby="activity-help">
        </div>
        <span class="arrow" aria-hidden="true"><i class="bi bi-arrow-right"></i></span>
        <div class="field">
            <label for="to">End date</label>
            <input class="form-control form-control-sm" type="date" id="to" name="to" value="<?= esc($filters['to']) ?>" max="<?= date('Y-m-d') ?>" required aria-describedby="activity-help">
        </div>
        <div class="field">
            <label for="user_id">Done by</label>
            <select class="form-select form-select-sm" id="user_id" name="user_id">
                <option value="">Anyone</option>
                <?php foreach ($people as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $filters['user_id'] === (string) $p['id'] ? 'selected' : '' ?>><?= esc($p['name']) ?> (<?= esc(role_label($p['role'])) ?>)</option>
                <?php endforeach; ?>
                <option value="system" <?= $filters['user_id'] === 'system' ? 'selected' : '' ?>>System (automatic)</option>
            </select>
        </div>
        <div class="field">
            <label for="q">Search</label>
            <input class="form-control form-control-sm" type="search" id="q" name="q" value="<?= esc($filters['q']) ?>" placeholder="Book, student, staff…">
        </div>
        <?php if ($filters['category'] !== ''): ?><input type="hidden" name="category" value="<?= esc($filters['category']) ?>"><?php endif; ?>
        <button class="btn btn-sm btn-nook"><i class="bi bi-funnel me-1"></i>Show activity</button>
    </form>
    <p id="activity-help" class="form-text mt-0 mb-2">
        <i class="bi bi-info-circle"></i>
        Choose a <strong>start date</strong> and an <strong>end date</strong>. The log lists every action recorded in the system from
        <strong>12:00 AM on the start date</strong> to <strong>11:59 PM on the end date</strong>: books issued and returned, credits, inventory changes, catalog edits, student and staff accounts, settings and sign-ins.
        Pick the same date in both boxes to see a single day.
    </p>
    <div class="d-flex flex-wrap gap-1 mb-3">
        <?php foreach ($quick as $label => [$f, $t]): ?>
            <a class="btn btn-sm <?= $filters['from'] === $f && $filters['to'] === $t ? 'btn-nook' : 'btn-outline-nook' ?>" href="<?= $link(['from' => $f, 'to' => $t, 'category' => $filters['category']]) ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-warning py-2 small"><i class="bi bi-exclamation-triangle me-1"></i><?= esc($error) ?></div>
    <?php endif; ?>

    <div class="filter-summary">
        <i class="bi bi-calendar-range"></i>
        <span>
            Showing <strong><?= $pager->getTotal() ?></strong> action<?= $pager->getTotal() === 1 ? '' : 's' ?>
            <?= $filters['from'] === $filters['to']
                ? 'on <strong>' . fmt_date($filters['from']) . '</strong>'
                : 'from <strong>' . fmt_date($filters['from']) . '</strong> to <strong>' . fmt_date($filters['to']) . '</strong> (' . $days . ' days, both included)' ?>
            <?= $filters['category'] !== '' ? ' in <strong>' . esc(ActivityLogModel::CATEGORIES[$filters['category']][0] ?? $filters['category']) . '</strong>' : '' ?>
            <?= $filters['q'] !== '' ? ' matching "' . esc($filters['q']) . '"' : '' ?>
        </span>
        <?php if ($filters['category'] !== '' || $filters['user_id'] !== '' || $filters['q'] !== ''): ?>
            <a href="<?= site_url('activity?from=' . $filters['from'] . '&to=' . $filters['to']) ?>">Clear filters</a>
        <?php endif; ?>
    </div>

    <div class="cat-chips" aria-label="Filter by area">
        <a class="<?= $filters['category'] === '' ? 'active' : '' ?>" href="<?= $link(['category' => '']) ?>">All areas<span class="n"><?= $total ?></span></a>
        <?php foreach (ActivityLogModel::CATEGORIES as $key => [$label, $icon]): if (empty($counts[$key])) continue; ?>
            <a class="<?= $filters['category'] === $key ? 'active' : '' ?>" href="<?= $link(['category' => $key]) ?>"><i class="bi bi-<?= $icon ?> me-1"></i><?= esc($label) ?><span class="n"><?= (int) $counts[$key] ?></span></a>
        <?php endforeach; ?>
    </div>

    <?= view('activity/_list', ['rows' => $rows]) ?>
    <?php if ($rows): ?>
        <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
            <span class="small text-muted"><?= pager_info($pager, 'default', 'actions') ?></span>
            <div class="ms-auto"><?= $pager->links('default', 'nook') ?></div>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= view('partials/date_range_js') ?>
<?= $this->endSection() ?>
