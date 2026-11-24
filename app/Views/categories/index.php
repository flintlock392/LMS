<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?>Categories<?= $this->endSection() ?>

<?= $this->section('content') ?>
<a class="small d-inline-block mb-3" href="<?= site_url('books') ?>"><i class="bi bi-arrow-left"></i> Book Management</a>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="panel">
            <h2 class="panel-title"><i class="bi bi-plus-lg"></i> New category</h2>
            <form method="post" action="<?= site_url('categories/create') ?>">
                <?= csrf_field() ?>
                <div class="mb-3"><label class="form-label" for="name">Name</label>
                    <input class="form-control" id="name" name="name" maxlength="100" required></div>
                <div class="mb-3"><label class="form-label" for="description">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="2" maxlength="1000"></textarea></div>
                <button class="btn btn-nook">Add category</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="panel">
            <h2 class="panel-title">All categories</h2>
            <?php if (! $categories): ?>
                <div class="empty"><i class="bi bi-tags"></i>No categories yet.</div>
            <?php else: ?>
                <div class="table-responsive"><table class="table table-nook">
                    <thead><tr><th>Name</th><th>Titles</th><th>Copies</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($categories as $c): ?>
                        <tr>
                            <td><strong><?= esc($c['name']) ?></strong><div class="sub"><?= esc($c['description'] ?? '') ?></div></td>
                            <td><?= (int) $c['book_count'] ?></td>
                            <td><?= (int) $c['copies'] ?></td>
                            <td class="actions">
                                <button class="btn btn-sm btn-outline-nook" type="button" data-bs-toggle="collapse" data-bs-target="#edit-<?= $c['id'] ?>"><i class="bi bi-pencil"></i></button>
                                <form class="d-inline" method="post" action="<?= site_url('categories/' . $c['id'] . '/delete') ?>" data-confirm="Delete category &quot;<?= esc($c['name']) ?>&quot;? Its books will become uncategorized.">
                                    <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <tr class="collapse" id="edit-<?= $c['id'] ?>"><td colspan="4">
                            <form class="row g-2 align-items-end" method="post" action="<?= site_url('categories/' . $c['id'] . '/update') ?>">
                                <?= csrf_field() ?>
                                <div class="col-md-4"><label class="form-label small">Name</label><input class="form-control form-control-sm" name="name" value="<?= esc($c['name']) ?>" required maxlength="100"></div>
                                <div class="col-md-6"><label class="form-label small">Description</label><input class="form-control form-control-sm" name="description" value="<?= esc($c['description'] ?? '') ?>" maxlength="1000"></div>
                                <div class="col-md-2"><button class="btn btn-sm btn-nook w-100">Save</button></div>
                            </form>
                        </td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
