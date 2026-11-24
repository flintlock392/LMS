<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= esc($book['title']) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php $isStaff = in_array(session()->get('role'), ['admin', 'librarian'], true); ?>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h1 class="h3 mb-1"><?= esc($book['title']) ?></h1>
                <div class="text-muted">by <?= esc($book['author']) ?></div>
            </div>
            <?= stock_badge((int) $book['stock_count']) ?>
        </div>
        <dl class="row mt-4 mb-0">
            <dt class="col-sm-3">ISBN</dt>
            <dd class="col-sm-9"><code><?= esc($book['isbn']) ?></code></dd>
            <dt class="col-sm-3">Category</dt>
            <dd class="col-sm-9"><?= $book['category_name'] ? esc($book['category_name']) : '<span class="text-muted">Uncategorized</span>' ?></dd>
            <dt class="col-sm-3">Publisher</dt>
            <dd class="col-sm-9"><?= esc($book['publisher'] ?? '—') ?></dd>
            <dt class="col-sm-3">Year</dt>
            <dd class="col-sm-9"><?= esc($book['publication_year'] ?? '—') ?></dd>
            <dt class="col-sm-3">Copies in stock</dt>
            <dd class="col-sm-9"><?= (int) $book['stock_count'] ?></dd>
            <dt class="col-sm-3">Description</dt>
            <dd class="col-sm-9"><?= $book['description'] ? nl2br(esc($book['description'])) : '—' ?></dd>
        </dl>
    </div>
    <div class="card-footer d-flex gap-2">
        <a class="btn btn-outline-secondary" href="<?= site_url('books') ?>">← Back</a>
        <?php if ($isStaff): ?>
            <a class="btn btn-primary" href="<?= site_url('books/' . $book['id'] . '/edit') ?>">Edit</a>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
