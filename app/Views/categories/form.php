<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= $category ? 'Edit category' : 'Add category' ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php $action = $category ? site_url('categories/' . $category['id'] . '/update') : site_url('categories/create'); ?>
<h1 class="h3 mb-3"><?= $category ? 'Edit category' : 'Add category' ?></h1>

<form class="card card-body" method="post" action="<?= $action ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label" for="name">Name *</label>
        <input class="form-control" id="name" name="name" value="<?= esc(old('name', $category['name'] ?? '', false)) ?>" required maxlength="100">
    </div>
    <div class="mb-3">
        <label class="form-label" for="description">Description</label>
        <textarea class="form-control" id="description" name="description" rows="3" maxlength="1000"><?= esc(old('description', $category['description'] ?? '', false)) ?></textarea>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary" type="submit"><?= $category ? 'Save changes' : 'Add category' ?></button>
        <a class="btn btn-outline-secondary" href="<?= site_url('categories') ?>">Cancel</a>
    </div>
</form>
<?= $this->endSection() ?>
