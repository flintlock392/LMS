<?= $this->extend($layout) ?>
<?= $this->section('title') ?><?= $layout === 'layouts/app' ? 'Browse Books' : 'Books' ?><?= $this->endSection() ?>

<?php
$spines = ['#6e7d4e', '#9a5530', '#4c6488', '#8e3b45', '#55342b', '#7a6a3a'];
$public = $layout === 'layouts/public';
?>
<?= $this->section('content') ?>
<?php if ($public): ?><div class="container pb-5 catalog-page"><h1 class="pub-heading mb-3">Our books</h1><?= view('partials/flash') ?><div class="pub-panel catalog-panel"><?php else: ?><div class="panel"><?php endif; ?>

    <form class="filters" method="get" data-filter-form>
        <input class="form-control form-control-sm" type="search" name="q" value="<?= esc($filters['q']) ?>" placeholder="Search title, author or ISBN" aria-label="Search books"
               data-suggest="books" data-suggest-url="<?= site_url('catalog/suggest') ?>">
        <select class="form-select form-select-sm" name="category_id" aria-label="Category">
            <option value="">All categories</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $filters['category_id'] === (string) $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select class="form-select form-select-sm" name="course" aria-label="Course" style="max-width: 300px">
            <option value="">All courses</option>
            <?php foreach (\App\Models\UserModel::COURSES as $c): ?>
                <option value="<?= esc($c) ?>" <?= $filters['course'] === $c ? 'selected' : '' ?>><?= esc($c) ?></option>
            <?php endforeach; ?>
            <option value="__general" <?= $filters['course'] === '__general' ? 'selected' : '' ?>>General reading (includes other courses)</option>
        </select>
        <div class="form-check ms-1">
            <input class="form-check-input" type="checkbox" id="avail" name="availability" value="in_stock" <?= $filters['availability'] ? 'checked' : '' ?>>
            <label class="form-check-label small" for="avail">Available now</label>
        </div>
        <button class="btn btn-sm btn-nook" data-apply><i class="bi bi-funnel me-1"></i>Apply</button>
        <?php if ($filters['q'] !== '' || $filters['category_id'] !== '' || $filters['course'] !== '' || $filters['availability'] !== ''): ?>
            <a class="btn-clear" href="<?= site_url('catalog') ?>"><i class="bi bi-x-circle"></i>Clear filters</a>
        <?php endif; ?>
    </form>

    <?php if (! $books): ?>
        <div class="empty"><i class="bi bi-search"></i>No books match your search.</div>
    <?php else: ?>
        <div class="catalog-scroll" tabindex="0" aria-label="Book list">
        <div class="row g-3">
            <?php foreach ($books as $b): ?>
                <div class="col-sm-6 col-xl-4">
                    <div class="book-card">
                        <div class="d-flex gap-3 mb-2">
                            <div class="spine" style="background: <?= $spines[$b['id'] % count($spines)] ?>" aria-hidden="true"><?= esc(mb_substr($b['title'], 0, 1)) ?></div>
                            <div class="min-w-0">
                                <h3><a class="text-reset text-decoration-none" href="<?= site_url('catalog/' . $b['id']) ?>"><?= esc($b['title']) ?></a></h3>
                                <div class="meta"><?= esc($b['author']) ?></div>
                                <div class="meta"><?= esc($b['category_name'] ?? 'Uncategorized') ?><?= $b['publication_year'] ? ' · ' . esc($b['publication_year']) : '' ?></div>
                            </div>
                        </div>
                        <div class="mt-auto d-flex flex-wrap justify-content-between align-items-center gap-2 pt-2">
                            <?= availability_badge((int) $b['available'], (int) $b['stock_count']) ?>
                            <?= view('catalog/_action', ['book' => $b, 'mine' => $mine]) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        </div>
        <?= $pager->links('default', 'nook') ?>
    <?php endif; ?>

<?php if ($public): ?></div></div><?php else: ?></div><?php endif; ?>
<?= $this->endSection() ?>
