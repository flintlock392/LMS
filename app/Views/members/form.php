<?php $editing = $user !== null; ?>
<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?><?= $editing ? 'Edit student' : 'Register a student' ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<a class="small d-inline-block mb-3" href="<?= site_url($editing ? 'members/' . $user['id'] : 'members') ?>"><i class="bi bi-arrow-left"></i> Back</a>
<div class="panel" style="max-width: 760px">
    <form method="post" action="<?= site_url($editing ? 'members/' . $user['id'] . '/update' : 'members/create') ?>">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label" for="id_number">Student number *</label>
                <input class="form-control" id="id_number" name="id_number" value="<?= esc(old('id_number', $user['id_number'] ?? '', false)) ?>" required maxlength="30" placeholder="e.g. 2024-00123">
                <div class="form-text">Used to log in, and as the library card number.</div>
            </div>
            <div class="col-md-7">
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
            <div class="col-md-7">
                <?= view('partials/course_field', ['current' => (string) ($user['course'] ?? '')]) ?>
            </div>
            <div class="col-md-5">
                <label class="form-label" for="year_level">Year level *</label>
                <select class="form-select" id="year_level" name="year_level" required>
                    <?php $yl = old('year_level', $user['year_level'] ?? '1st Year'); // "Graduate" is not offered for new students; it stays available when editing someone already marked Graduate.
                    $years = ['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year'];
                    if ($editing && $yl === 'Graduate') { $years[] = 'Graduate'; }
                    foreach ($years as $y): ?>
                        <option <?= $yl === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="password"><?= $editing ? 'Reset password' : 'Temporary password *' ?></label>
                <!-- Hidden while typing; the eye button shows or hides it -->
                <div class="position-relative">
                    <input class="form-control pe-5" type="password" id="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" data-strength <?= $editing ? 'placeholder="Leave blank to keep current"' : 'required' ?>>
                    <button type="button" class="btn btn-sm position-absolute top-50 end-0 translate-middle-y me-1 border-0"
                            onclick="const p=document.getElementById('password');const show=p.type==='password';p.type=show?'text':'password';this.firstElementChild.className='bi bi-'+(show?'eye-slash':'eye');this.setAttribute('aria-label',show?'Hide password':'Show password')"
                            aria-label="Show password" aria-controls="password"><i class="bi bi-eye"></i></button>
                </div>
                <div class="form-text">The student can change it under My Account.</div>
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button class="btn btn-nook"><?= $editing ? 'Save changes' : 'Register student' ?></button>
            <a class="btn btn-link" href="<?= site_url('members') ?>">Cancel</a>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
