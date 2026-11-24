<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?>My Account<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-4" style="max-width: 1000px">
    <div class="col-lg-6">
        <div class="panel">
            <h2 class="panel-title"><i class="bi bi-person"></i> Profile</h2>
            <dl class="row small">
                <dt class="col-5">Name</dt><dd class="col-7"><?= esc($user['name']) ?></dd>
                <dt class="col-5"><?= $user['role'] === 'student' ? 'Student number' : 'Staff ID' ?></dt><dd class="col-7"><?= esc($user['id_number']) ?></dd>
                <dt class="col-5">User level</dt><dd class="col-7"><?= esc(role_label($user['role'])) ?></dd>
                <?php if ($user['role'] === 'student'): ?>
                    <dt class="col-5">Course</dt><dd class="col-7"><?= esc($user['course'] ?? '—') ?> · <?= esc($user['year_level'] ?? '') ?></dd>
                <?php else: ?>
                    <dt class="col-5">Position</dt><dd class="col-7"><?= esc($user['position'] ?? '—') ?></dd>
                <?php endif; ?>
            </dl>
            <?php if (! is_head()): ?><p class="small text-muted">To change your name or ID number, please ask the Head Librarian.</p><?php endif; ?>
            <form method="post" action="<?= site_url('account/profile') ?>">
                <?= csrf_field() ?>
                <div class="mb-3"><label class="form-label" for="email">Email</label>
                    <input class="form-control" type="email" id="email" name="email" value="<?= esc(old('email', $user['email'], false)) ?>" required maxlength="150"></div>
                <div class="mb-3"><label class="form-label" for="phone">Phone</label>
                    <input class="form-control" id="phone" name="phone" value="<?= esc(old('phone', $user['phone'] ?? '', false)) ?>" maxlength="30"></div>
                <button class="btn btn-nook">Save details</button>
            </form>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel">
            <h2 class="panel-title"><i class="bi bi-shield-lock"></i> Change password</h2>
            <form method="post" action="<?= site_url('account/password') ?>">
                <?= csrf_field() ?>
                <div class="mb-3"><label class="form-label" for="current">Current password</label>
                    <input class="form-control" type="password" id="current" name="current" autocomplete="current-password" required></div>
                <div class="mb-3"><label class="form-label" for="password">New password</label>
                    <input class="form-control" type="password" id="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required>
                    <div class="form-text">At least 8 characters.</div></div>
                <div class="mb-3"><label class="form-label" for="confirm">Confirm new password</label>
                    <input class="form-control" type="password" id="confirm" name="confirm" minlength="8" maxlength="72" autocomplete="new-password" required></div>
                <button class="btn btn-nook">Change password</button>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
