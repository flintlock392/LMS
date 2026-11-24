<?= $this->extend($layout) ?>
<?= $this->section('title') ?><?= esc($book['title']) ?><?= $this->endSection() ?>

<?php $public = $layout === 'layouts/public'; ?>
<?= $this->section('content') ?>
<?php if ($public): ?><div class="container pb-5"><?= view('partials/flash') ?><?php endif; ?>
<a class="small d-inline-block mb-3 <?= $public ? 'text-white' : '' ?>" href="<?= site_url('catalog') ?>"><i class="bi bi-arrow-left"></i> All books</a>
<div class="<?= $public ? 'pub-panel' : 'panel' ?>" style="max-width: 820px">
    <h1 class="h4 mb-1"><?= esc($book['title']) ?></h1>
    <p class="text-muted">by <?= esc($book['author']) ?></p>
    <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
        <?= availability_badge((int) $book['available'], (int) $book['stock_count']) ?>
        <?php if ((int) $book['queued'] > 0): ?><span class="small text-muted"><?= (int) $book['queued'] ?> student(s) waiting</span><?php endif; ?>
        <div class="ms-auto"><?= view('catalog/_action', ['book' => $book, 'mine' => $mine]) ?></div>
    </div>
    <dl class="row small mb-0">
        <dt class="col-sm-3">Category</dt><dd class="col-sm-9"><?= esc($book['category_name'] ?? 'Uncategorized') ?></dd>
        <dt class="col-sm-3">ISBN</dt><dd class="col-sm-9"><?= esc($book['isbn']) ?></dd>
        <dt class="col-sm-3">Publisher</dt><dd class="col-sm-9"><?= esc($book['publisher'] ?? '—') ?><?= $book['publication_year'] ? ', ' . esc($book['publication_year']) : '' ?></dd>
        <dt class="col-sm-3">Shelf</dt><dd class="col-sm-9"><?= esc($book['shelf_location'] ?? '—') ?></dd>
    </dl>
    <?php if ($book['description']): ?><p class="mt-3 mb-0"><?= nl2br(esc($book['description'])) ?></p><?php endif; ?>
</div>
<?php if ($public): ?></div><?php endif; ?>
<?= $this->endSection() ?>
