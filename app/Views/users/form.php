<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= $user ? 'Edit user' : 'Add user' ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$action = $user ? site_url('users/' . $user['id'] . '/update') : site_url('users/create');
$role   = old('role', $user['role'] ?? 'member', false);
?>
<h1 class="h3 mb-3"><?= $user ? 'Edit user' : 'Add user' ?></h1>

<form class="card card-body" method="post" action="<?= $action ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="name">Name *</label>
            <input class="form-control" id="name" name="name" value="<?= esc(old('name', $user['name'] ?? '', false)) ?>" required maxlength="100">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="email">Email *</label>
            <input class="form-control" type="email" id="email" name="email" value="<?= esc(old('email', $user['email'] ?? '', false)) ?>" required maxlength="150">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="role">Role *</label>
            <select class="form-select" id="role" name="role">
                <?php foreach (\App\Models\UserModel::ROLES as $r): ?>
                    <option value="<?= $r ?>" <?= $role === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">Admin: everything · Librarian: books &amp; categories · Member: browse only.</div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="password">Password <?= $user ? '(leave blank to keep current)' : '*' ?></label>
            <input class="form-control" type="password" id="password" name="password" minlength="8" maxlength="72" <?= $user ? '' : 'required' ?> autocomplete="new-password">
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary" type="submit"><?= $user ? 'Save changes' : 'Create user' ?></button>
        <a class="btn btn-outline-secondary" href="<?= site_url('users') ?>">Cancel</a>
    </div>
</form>
<?= $this->endSection() ?>
