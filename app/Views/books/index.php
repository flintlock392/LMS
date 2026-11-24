<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Books<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$isStaff = in_array(session()->get('role'), ['admin', 'librarian'], true);

// Build a sort link that keeps the active filters and toggles direction.
$sortLink = static function (string $column, string $label) use ($filters): string {
    $currentSort = $filters['sort'] ?: 'title';
    $nextDir     = ($currentSort === $column && strtolower($filters['dir']) !== 'desc') ? 'desc' : 'asc';
    $query       = array_merge($_GET, ['sort' => $column, 'dir' => $nextDir]);
    unset($query['page']);
    $arrow = $currentSort === $column ? (strtolower($filters['dir']) === 'desc' ? ' ▼' : ' ▲') : '';

    return '<a class="text-decoration-none text-reset" href="' . esc(site_url('books') . '?' . http_build_query($query)) . '">' . esc($label) . $arrow . '</a>';
};
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Books</h1>
    <?php if ($isStaff): ?>
        <a class="btn btn-primary" href="<?= site_url('books/new') ?>">+ Add book</a>
    <?php endif; ?>
</div>

<form class="card card-body mb-3" method="get" action="<?= site_url('books') ?>">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small mb-1" for="q">Search</label>
            <input class="form-control" id="q" name="q" placeholder="Title, author, ISBN, publisher" value="<?= esc($filters['q']) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1" for="category_id">Category</label>
            <select class="form-select" id="category_id" name="category_id">
                <option value="">All</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= (string) $filters['category_id'] === (string) $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                <?php endforeach; ?>
                <option value="none" <?= $filters['category_id'] === 'none' ? 'selected' : '' ?>>Uncategorized</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1" for="author">Author</label>
            <select class="form-select" id="author" name="author">
                <option value="">All</option>
                <?php foreach ($authors as $a): ?>
                    <option value="<?= esc($a, 'attr') ?>" <?= $filters['author'] === $a ? 'selected' : '' ?>><?= esc($a) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1" for="availability">Availability</label>
            <select class="form-select" id="availability" name="availability">
                <?php
                $opts = ['' => 'Any', 'in_stock' => 'In stock', 'low_stock' => 'Low stock', 'out_of_stock' => 'Out of stock'];
                foreach ($opts as $value => $label): ?>
                    <option value="<?= $value ?>" <?= $filters['availability'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-primary flex-fill" type="submit">Filter</button>
            <a class="btn btn-outline-secondary" href="<?= site_url('books') ?>">Reset</a>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th><?= $sortLink('title', 'Title') ?></th>
                <th><?= $sortLink('author', 'Author') ?></th>
                <th><?= $sortLink('isbn', 'ISBN') ?></th>
                <th>Category</th>
                <th><?= $sortLink('stock_count', 'Stock') ?></th>
                <?php if ($isStaff): ?><th class="text-end">Actions</th><?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($books as $b): ?>
                <tr>
                    <td><a href="<?= site_url('books/' . $b['id']) ?>"><?= esc($b['title']) ?></a></td>
                    <td><?= esc($b['author']) ?></td>
                    <td><code><?= esc($b['isbn']) ?></code></td>
                    <td><?= $b['category_name'] ? esc($b['category_name']) : '<span class="text-muted">Uncategorized</span>' ?></td>
                    <td>
                        <?= stock_badge((int) $b['stock_count']) ?>
                        <?php if ($isStaff): ?>
                            <form class="d-inline-flex gap-1 ms-2" method="post" action="<?= site_url('books/' . $b['id'] . '/stock') ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-secondary py-0" name="delta" value="-1" title="Remove one copy" <?= (int) $b['stock_count'] === 0 ? 'disabled' : '' ?>>−</button>
                                <button class="btn btn-sm btn-outline-secondary py-0" name="delta" value="1" title="Add one copy">+</button>
                            </form>
                        <?php endif; ?>
                    </td>
                    <?php if ($isStaff): ?>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="<?= site_url('books/' . $b['id'] . '/edit') ?>">Edit</a>
                            <form class="d-inline" method="post" action="<?= site_url('books/' . $b['id'] . '/delete') ?>"
                                  onsubmit="return confirm('Delete this book?');">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (! $books): ?>
                <tr><td colspan="<?= $isStaff ? 6 : 5 ?>" class="text-center text-muted py-4">No books match these filters.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer"><?= view('partials/pagination', ['pager' => $pager]) ?></div>
</div>
<?= $this->endSection() ?>
