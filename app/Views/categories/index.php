<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Categories<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Categories</h1>
    <a class="btn btn-primary" href="<?= site_url('categories/new') ?>">+ Add category</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
            <tr><th>Name</th><th>Description</th><th>Titles</th><th>Copies</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($categories as $c): ?>
                <tr>
                    <td><a href="<?= site_url('books') ?>?category_id=<?= $c['id'] ?>"><?= esc($c['name']) ?></a></td>
                    <td class="text-muted"><?= esc($c['description'] ?? '') ?></td>
                    <td><?= (int) $c['book_count'] ?></td>
                    <td><?= (int) $c['copies'] ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="<?= site_url('categories/' . $c['id'] . '/edit') ?>">Edit</a>
                        <form class="d-inline" method="post" action="<?= site_url('categories/' . $c['id'] . '/delete') ?>"
                              onsubmit="return confirm('Delete this category? Its books will become uncategorized.');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (! $categories): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">No categories yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
