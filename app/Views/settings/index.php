<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?>System Settings<?= $this->endSection() ?>

<?php
$groups = [
    'General'      => ['library_name'],
    'Borrowing'    => ['loan_days', 'max_loans'],
    'Credits'      => ['credits_per_late_day', 'max_late_credits', 'damaged_credits', 'lost_credits'],
];
?>
<?= $this->section('content') ?>
<form method="post" action="<?= site_url('settings') ?>" style="max-width: 900px">
    <?= csrf_field() ?>
    <?php foreach ($groups as $group => $keys): ?>
        <div class="panel">
            <h2 class="panel-title"><?= $group ?></h2>
            <div class="row g-3">
                <?php foreach ($keys as $key): [$label, , $type, $help] = $defs[$key]; $val = old($key, $values[$key]); ?>
                    <?php if ($type === 'bool'): ?>
                        <div class="col-12">
                            <input type="hidden" name="<?= $key ?>" value="0">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="<?= $key ?>" name="<?= $key ?>" value="1" <?= $val === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="<?= $key ?>"><?= esc($label) ?></label>
                            </div>
                            <div class="form-text"><?= esc($help) ?></div>
                        </div>
                    <?php else: ?>
                        <div class="col-md-6">
                            <label class="form-label" for="<?= $key ?>"><?= esc($label) ?></label>
                            <div class="input-group">
                                <input class="form-control" id="<?= $key ?>" name="<?= $key ?>" value="<?= esc($val) ?>" required
                                    <?= $type === 'int' ? 'type="number" min="0" step="1"' : ($type === 'credits' ? 'type="number" min="0" max="20" step="1"' : 'type="text" maxlength="100"') ?>>
                                <?php if ($type === 'int' && str_contains($label, 'days')): ?><span class="input-group-text">days</span><?php endif; ?>
                                <?php if ($type === 'credits'): ?><span class="input-group-text">credits</span><?php endif; ?>
                            </div>
                            <div class="form-text"><?= esc($help) ?></div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php if ($group === 'Credits'): ?>
                <div class="small text-muted mt-3">
                    <strong>Standing levels</strong> (every student starts with 20 credits; at 0 they are blocked):
                    <ul class="mb-0 mt-1">
                        <?php foreach (\App\Libraries\CreditPolicy::TIERS as $i => $t): ?>
                            <li><?= \App\Libraries\CreditPolicy::rangeLabel($i) ?> credits · <strong><?= esc($t[2]) ?></strong>: <?= esc($t[6]) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <div class="mt-3"><button class="btn btn-nook"><i class="bi bi-save me-1"></i>Save settings</button></div>
</form>
<?= $this->endSection() ?>
