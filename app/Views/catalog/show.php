<?= $this->extend($layout) ?>
<?= $this->section('title') ?><?= esc($book['title']) ?><?= $this->endSection() ?>

<?php $public = $layout === 'layouts/public'; ?>
<?= $this->section('content') ?>
<?php if ($public): ?><div class="container pb-5"><?= view('partials/flash') ?><?php endif; ?>
<a class="small d-inline-block mb-3 <?= $public ? 'text-white' : '' ?>" href="<?= site_url('catalog') ?>"><i class="bi bi-arrow-left"></i> All books</a>
<div class="<?= $public ? 'pub-panel' : 'panel' ?>" style="max-width: 820px">
    <div class="d-flex gap-4 align-items-start mb-2">
    <?= book_cover($book, 'cover-lg', 'L') ?>
    <div class="flex-grow-1 min-w-0">
    <h1 class="h4 mb-1"><?= esc($book['title']) ?></h1>
    <p class="text-muted mb-2">by <?= esc($book['author']) ?><?= $book['edition'] ? ' · ' . esc($book['edition']) : '' ?></p>
    <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
        <?= availability_badge((int) $book['available'], (int) $book['stock_count']) ?>
        <div class="ms-auto"><?= view('catalog/_action', ['book' => $book, 'mine' => $mine]) ?></div>
    </div>
    <dl class="row small mb-0">
        <dt class="col-sm-3">Category</dt><dd class="col-sm-9"><?= esc($book['category_name'] ?? 'Uncategorized') ?></dd>
        <dt class="col-sm-3">ISBN</dt><dd class="col-sm-9"><?= esc($book['isbn']) ?></dd>
        <dt class="col-sm-3">Edition</dt><dd class="col-sm-9"><?= esc($book['edition'] ?? '—') ?></dd>
        <dt class="col-sm-3">Course</dt><dd class="col-sm-9"><?= esc($book['course'] ?: 'General reading (all courses)') ?></dd>
        <dt class="col-sm-3">Publisher</dt><dd class="col-sm-9"><?= esc($book['publisher'] ?? '—') ?><?= $book['publication_year'] ? ', ' . esc($book['publication_year']) : '' ?></dd>
        <dt class="col-sm-3">Shelf</dt><dd class="col-sm-9"><?= esc($book['shelf_location'] ?? '—') ?></dd>
    </dl>
    </div>
    </div>
    <?php if ($book['description']): ?><p class="mt-3 mb-0"><?= nl2br(esc($book['description'])) ?></p><?php endif; ?>
</div>
<?php if ($public): ?></div><?php endif; ?>
<?= $this->endSection() ?>
