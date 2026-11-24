<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?>System Settings<?= $this->endSection() ?>

<?php
$groups = [
    'General'      => ['library_name', 'currency'],
    'Borrowing'    => ['loan_days', 'max_loans', 'block_unpaid_fines'],
    'Fines'        => ['fine_per_day', 'max_overdue_fine', 'damaged_book_fee', 'lost_book_fee'],
    'Reservations' => ['max_reservations', 'hold_days'],
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
                                <?php if ($type === 'money'): ?><span class="input-group-text"><?= esc($values['currency']) ?></span><?php endif; ?>
                                <input class="form-control" id="<?= $key ?>" name="<?= $key ?>" value="<?= esc($val) ?>" required
                                    <?= $type === 'int' ? 'type="number" min="0" step="1"' : ($type === 'money' ? 'type="number" min="0" step="0.01"' : 'type="text" maxlength="100"') ?>>
                                <?php if ($type === 'int' && str_contains($label, 'days')): ?><span class="input-group-text">days</span><?php endif; ?>
                            </div>
                            <div class="form-text"><?= esc($help) ?></div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <div class="mt-3"><button class="btn btn-nook"><i class="bi bi-save me-1"></i>Save settings</button></div>
</form>
<?= $this->endSection() ?>
