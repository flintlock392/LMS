<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php $isStaff = in_array(session()->get('role'), ['admin', 'librarian'], true); ?>
<h1 class="h3 mb-4">Dashboard</h1>

<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Titles', $stats['titles'], 'primary'],
        ['Total copies', $stats['copies'], 'success'],
        ['Low stock', $stats['low_stock'], 'warning'],
        ['Out of stock', $stats['out_of_stock'], 'danger'],
        ['Categories', $stats['categories'], 'secondary'],
    ];
    foreach ($cards as [$label, $value, $color]): ?>
        <div class="col-6 col-md">
            <div class="card border-<?= $color ?> h-100">
                <div class="card-body">
                    <div class="text-muted small"><?= esc($label) ?></div>
                    <div class="fs-2 fw-semibold"><?= (int) $value ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">Low stock (<?= \App\Models\BookModel::LOW_STOCK ?> or fewer copies)</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($lowStock as $b): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="<?= site_url('books/' . $b['id']) ?>"><?= esc($b['title']) ?></a>
                        <?= stock_badge((int) $b['stock_count']) ?>
                    </li>
                <?php endforeach; ?>
                <?php if (! $lowStock): ?><li class="list-group-item text-muted">Nothing running low.</li><?php endif; ?>
            </ul>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">Out of stock</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($outOfStock as $b): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="<?= site_url('books/' . $b['id']) ?>"><?= esc($b['title']) ?></a>
                        <?= stock_badge(0) ?>
                    </li>
                <?php endforeach; ?>
                <?php if (! $outOfStock): ?><li class="list-group-item text-muted">Every title is available.</li><?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<div class="mt-4 d-flex gap-2">
    <a class="btn btn-primary" href="<?= site_url('books') ?>">Browse books</a>
    <?php if ($isStaff): ?>
        <a class="btn btn-outline-primary" href="<?= site_url('books/new') ?>">Add a book</a>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
