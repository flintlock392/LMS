<?php $editing = $book !== null; ?>
<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?><?= $editing ? 'Edit book' : 'Add a book' ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<a class="small d-inline-block mb-3" href="<?= site_url($editing ? 'books/' . $book['id'] : 'books') ?>"><i class="bi bi-arrow-left"></i> Back</a>
<div class="panel" style="max-width: 860px">
    <form method="post" action="<?= site_url($editing ? 'books/' . $book['id'] . '/update' : 'books/create') ?>">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label" for="title">Title *</label>
                <input class="form-control" id="title" name="title" value="<?= esc(old('title', $book['title'] ?? '', false)) ?>" required maxlength="255">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="isbn">ISBN *</label>
                <input class="form-control" id="isbn" name="isbn" value="<?= esc(old('isbn', $book['isbn'] ?? '', false)) ?>" required maxlength="20" placeholder="978-…">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="author">Author *</label>
                <input class="form-control" id="author" name="author" value="<?= esc(old('author', $book['author'] ?? '', false)) ?>" required maxlength="150">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="category_id">Category</label>
                <select class="form-select" id="category_id" name="category_id">
                    <option value="">Uncategorized</option>
                    <?php $sel = (string) old('category_id', $book['category_id'] ?? ''); foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $sel === (string) $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label" for="publisher">Publisher</label>
                <input class="form-control" id="publisher" name="publisher" value="<?= esc(old('publisher', $book['publisher'] ?? '', false)) ?>" maxlength="150">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="publication_year">Year</label>
                <input class="form-control" type="number" id="publication_year" name="publication_year" value="<?= esc(old('publication_year', $book['publication_year'] ?? '', false)) ?>" min="1000" max="<?= date('Y') + 1 ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="shelf_location">Shelf location</label>
                <input class="form-control" id="shelf_location" name="shelf_location" value="<?= esc(old('shelf_location', $book['shelf_location'] ?? '', false)) ?>" maxlength="50" placeholder="e.g. A-101">
            </div>
            <?php if (! $editing): ?>
                <div class="col-md-4">
                    <label class="form-label" for="stock_count">Number of copies *</label>
                    <input class="form-control" type="number" id="stock_count" name="stock_count" value="<?= esc(old('stock_count', '1', false)) ?>" min="0" max="10000" required>
                </div>
            <?php else: ?>
                <div class="col-12"><div class="alert alert-light border small mb-0"><i class="bi bi-info-circle me-1"></i>
                    This title has <strong><?= (int) $book['stock_count'] ?></strong> copies. <?= is_head() ? 'Change the count in <a href="' . site_url('inventory') . '">Inventory Management</a>' : 'Only the Head Librarian can change the number of copies' ?> so every change is logged.</div></div>
            <?php endif; ?>
            <div class="col-12">
                <label class="form-label" for="description">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3" maxlength="2000"><?= esc(old('description', $book['description'] ?? '', false)) ?></textarea>
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button class="btn btn-nook" type="submit"><?= $editing ? 'Save changes' : 'Add book' ?></button>
            <a class="btn btn-link" href="<?= site_url('books') ?>">Cancel</a>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
