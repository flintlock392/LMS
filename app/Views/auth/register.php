<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Register<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Create a member account</h1>
                <form method="post" action="<?= site_url('register') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="name">Name</label>
                        <input class="form-control" id="name" name="name" value="<?= esc(old('name', '', false)) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input class="form-control" type="email" id="email" name="email" value="<?= esc(old('email', '', false)) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Password <span class="text-muted small">(min. 8 characters)</span></label>
                        <input class="form-control" type="password" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="confirm">Confirm password</label>
                        <input class="form-control" type="password" id="confirm" name="confirm" required>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Register</button>
                </form>
                <p class="text-center text-muted small mt-3 mb-0">
                    Already registered? <a href="<?= site_url('login') ?>">Log in</a>
                </p>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
