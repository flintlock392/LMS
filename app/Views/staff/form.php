<?php
$editing = $user !== null;
// Head Librarian accounts: position and user level are fixed (shown, but not editable).
$isHead = $editing && $user['role'] === \App\Models\UserModel::HEAD;
?>
<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?><?= $editing ? 'Edit staff account' : 'Add staff' ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<a class="small d-inline-block mb-3" href="<?= site_url('staff') ?>"><i class="bi bi-arrow-left"></i> User Management · Staff</a>
<div class="panel" style="max-width: 760px">
    <form method="post" action="<?= site_url($editing ? 'staff/' . $user['id'] . '/update' : 'staff/create') ?>">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="id_number">Staff ID *</label>
                <input class="form-control" id="id_number" name="id_number" value="<?= esc(old('id_number', $user['id_number'] ?? '', false)) ?>" required maxlength="30" placeholder="e.g. LS-0003">
                <div class="form-text">Used to log in.</div>
            </div>
            <div class="col-md-8">
                <label class="form-label" for="name">Full name *</label>
                <input class="form-control" id="name" name="name" value="<?= esc(old('name', $user['name'] ?? '', false)) ?>" required maxlength="100">
            </div>
            <div class="col-md-7">
                <label class="form-label" for="email">Email *</label>
                <input class="form-control" type="email" id="email" name="email" value="<?= esc(old('email', $user['email'] ?? '', false)) ?>" required maxlength="150">
            </div>
            <div class="col-md-5">
                <label class="form-label" for="phone">Phone</label>
                <input class="form-control" id="phone" name="phone" value="<?= esc(old('phone', $user['phone'] ?? '', false)) ?>" maxlength="30">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="position">Position / title</label>
                <?php if ($isHead): ?>
                    <div class="fixed-field" id="position" aria-describedby="fixed-help"><i class="bi bi-lock"></i><?= esc($user['position'] ?: role_label($user['role'])) ?></div>
                <?php else: ?>
                    <input class="form-control" id="position" name="position" value="<?= esc(old('position', $user['position'] ?? '', false)) ?>" maxlength="100" placeholder="e.g. Circulation Librarian">
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="role">User level *</label>
                <?php if ($isHead): ?>
                    <!-- A Head Librarian can't be changed to Librarian Staff, so the level is fixed. -->
                    <div class="fixed-field" id="role" aria-describedby="fixed-help"><i class="bi bi-lock"></i><?= esc(role_label($user['role'])) ?></div>
                    <input type="hidden" name="role" value="<?= \App\Models\UserModel::HEAD ?>">
                    <div class="form-text" id="fixed-help">Position and user level are fixed for Head Librarian accounts.</div>
                <?php else: ?>
                    <select class="form-select" id="role" name="role" required>
                        <?php $r = old('role', $user['role'] ?? 'librarian'); foreach (\App\Models\UserModel::STAFF_ROLES as $role): ?>
                            <option value="<?= $role ?>" <?= $r === $role ? 'selected' : '' ?>><?= esc(role_label($role)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Head Librarians can manage staff, settings, fines and inventory.</div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="password"><?= $editing ? 'Reset password' : 'Temporary password *' ?></label>
                <input class="form-control" type="text" id="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" <?= $editing ? 'placeholder="Leave blank to keep current"' : 'required' ?>>
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button class="btn btn-nook"><?= $editing ? 'Save changes' : 'Add staff member' ?></button>
            <a class="btn btn-link" href="<?= site_url('staff') ?>">Cancel</a>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
