<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= $book ? 'Edit book' : 'Add book' ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
// Old input (after a validation error) wins over stored values.
$val = static fn (string $key, $default = '') => old($key, $book[$key] ?? $default, false);
$action = $book ? site_url('books/' . $book['id'] . '/update') : site_url('books/create');
?>
<h1 class="h3 mb-3"><?= $book ? 'Edit book' : 'Add book' ?></h1>

<form class="card card-body" method="post" action="<?= $action ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label" for="title">Title *</label>
            <input class="form-control" id="title" name="title" value="<?= esc($val('title')) ?>" required maxlength="255">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="isbn">ISBN (10 or 13 digits) *</label>
            <input class="form-control" id="isbn" name="isbn" value="<?= esc($val('isbn')) ?>" required maxlength="20" placeholder="978-0-13-235088-4">
            <div class="form-text">Hyphens are optional. The check digit is validated.</div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="author">Author *</label>
            <input class="form-control" id="author" name="author" value="<?= esc($val('author')) ?>" required maxlength="150">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="category_id">Category</label>
            <select class="form-select" id="category_id" name="category_id">
                <option value="">Uncategorized</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= (string) $val('category_id') === (string) $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="publisher">Publisher</label>
            <input class="form-control" id="publisher" name="publisher" value="<?= esc($val('publisher')) ?>" maxlength="150">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="publication_year">Publication year</label>
            <input class="form-control" type="number" id="publication_year" name="publication_year" value="<?= esc($val('publication_year')) ?>" min="1000" max="<?= (int) date('Y') + 1 ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="stock_count">Stock count *</label>
            <input class="form-control" type="number" id="stock_count" name="stock_count" value="<?= esc($val('stock_count', 1)) ?>" min="0" required>
        </div>
        <div class="col-12">
            <label class="form-label" for="description">Description</label>
            <textarea class="form-control" id="description" name="description" rows="4" maxlength="2000"><?= esc($val('description')) ?></textarea>
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary" type="submit"><?= $book ? 'Save changes' : 'Add book' ?></button>
        <a class="btn btn-outline-secondary" href="<?= site_url('books') ?>">Cancel</a>
    </div>
</form>
<?= $this->endSection() ?>
