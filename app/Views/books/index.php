<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?><?= esc(module_title('books')) ?><?= $this->endSection() ?>

<?php
$sortLink = static function (string $col, string $label) use ($filters) {
    $dir  = ($filters['sort'] === $col && $filters['dir'] !== 'desc') ? 'desc' : 'asc';
    $icon = $filters['sort'] === $col ? ($filters['dir'] === 'desc' ? ' <i class="bi bi-caret-down-fill"></i>' : ' <i class="bi bi-caret-up-fill"></i>') : '';

    return '<a class="text-reset text-decoration-none" href="' . current_url() . '?' . http_build_query(array_merge($filters, ['sort' => $col, 'dir' => $dir])) . '">' . $label . $icon . '</a>';
};
?>

<?= $this->section('content') ?>
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><div class="stat"><span class="icon"><i class="bi bi-book"></i></span><span><span class="value d-block"><?= $stats['titles'] ?></span><span class="label">Titles</span></span></div></div>
    <div class="col-6 col-lg-3"><div class="stat"><span class="icon"><i class="bi bi-stack"></i></span><span><span class="value d-block"><?= $stats['copies'] ?></span><span class="label">Copies owned</span></span></div></div>
    <div class="col-6 col-lg-3"><div class="stat"><span class="icon good"><i class="bi bi-check2-circle"></i></span><span><span class="value d-block"><?= $stats['available'] ?></span><span class="label">On the shelf</span></span></div></div>
    <div class="col-6 col-lg-3"><div class="stat"><span class="icon blue"><i class="bi bi-journal-arrow-up"></i></span><span><span class="value d-block"><?= $stats['on_loan'] ?></span><span class="label">On loan</span></span></div></div>
</div>

<div class="panel">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <h2 class="panel-title mb-0">Collection</h2>
        <div class="ms-auto d-flex gap-2">
            <?php if (is_head()): ?>
                <a class="btn btn-outline-nook btn-sm" href="<?= site_url('categories') ?>"><i class="bi bi-tags me-1"></i>Categories</a>
            <?php endif; ?>
            <a class="btn btn-nook btn-sm" href="<?= site_url('books/new') ?>"><i class="bi bi-plus-lg me-1"></i>Add book</a>
        </div>
    </div>

    <form class="filters" method="get">
        <input class="form-control form-control-sm" type="search" name="q" value="<?= esc($filters['q']) ?>" placeholder="Title, author, ISBN, publisher" aria-label="Search">
        <select class="form-select form-select-sm" name="category_id" aria-label="Category">
            <option value="">All categories</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $filters['category_id'] === (string) $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
            <?php endforeach; ?>
            <option value="none" <?= $filters['category_id'] === 'none' ? 'selected' : '' ?>>Uncategorized</option>
        </select>
        <select class="form-select form-select-sm" name="availability" aria-label="Availability">
            <option value="">Any availability</option>
            <option value="in_stock" <?= $filters['availability'] === 'in_stock' ? 'selected' : '' ?>>Available now</option>
            <option value="low_stock" <?= $filters['availability'] === 'low_stock' ? 'selected' : '' ?>>Few left</option>
            <option value="out_of_stock" <?= $filters['availability'] === 'out_of_stock' ? 'selected' : '' ?>>Unavailable</option>
        </select>
        <input type="hidden" name="sort" value="<?= esc($filters['sort']) ?>"><input type="hidden" name="dir" value="<?= esc($filters['dir']) ?>">
        <button class="btn btn-sm btn-nook" type="submit"><i class="bi bi-search"></i> Filter</button>
        <?php if ($filters['q'] || $filters['category_id'] || $filters['availability']): ?>
            <a class="btn btn-sm btn-link" href="<?= site_url('books') ?>">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (! $books): ?>
        <div class="empty"><i class="bi bi-search"></i>No books match your filters.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-nook">
                <thead><tr>
                    <th><?= $sortLink('title', 'Title') ?></th>
                    <th><?= $sortLink('author', 'Author') ?></th>
                    <th>Category</th>
                    <th>Shelf</th>
                    <th><?= $sortLink('available', 'Availability') ?></th>
                    <th class="text-end">Actions</th>
                </tr></thead>
                <tbody>
                <?php foreach ($books as $b): ?>
                    <tr>
                        <td><a class="fw-semibold text-reset" href="<?= site_url('books/' . $b['id']) ?>"><?= esc($b['title']) ?></a><div class="sub">ISBN <?= esc($b['isbn']) ?></div></td>
                        <td><?= esc($b['author']) ?></td>
                        <td><?= esc($b['category_name'] ?? '—') ?></td>
                        <td><?= esc($b['shelf_location'] ?? '—') ?></td>
                        <td><?= availability_badge((int) $b['available'], (int) $b['stock_count']) ?>
                            <?php if ((int) $b['queued'] > 0): ?><div class="sub"><?= (int) $b['queued'] ?> reservation(s) waiting</div><?php endif; ?></td>
                        <td class="actions">
                            <a class="btn btn-sm btn-outline-nook" href="<?= site_url('books/' . $b['id']) ?>" title="View"><i class="bi bi-eye"></i></a>
                            <a class="btn btn-sm btn-outline-nook" href="<?= site_url('books/' . $b['id'] . '/edit') ?>" title="Edit"><i class="bi bi-pencil"></i></a>
                            <?php if (is_head()): ?>
                                <form class="d-inline" method="post" action="<?= site_url('books/' . $b['id'] . '/delete') ?>" data-confirm="Delete &quot;<?= esc($b['title']) ?>&quot;? This cannot be undone.">
                                    <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= $pager->links('default', 'nook') ?>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
