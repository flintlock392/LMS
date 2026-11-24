<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Log in<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Log in</h1>
                <form method="post" action="<?= site_url('login') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input class="form-control" type="email" id="email" name="email" value="<?= esc(old('email', '', false)) ?>" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Password</label>
                        <input class="form-control" type="password" id="password" name="password" required>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Log in</button>
                </form>
                <p class="text-center text-muted small mt-3 mb-0">
                    No account? <a href="<?= site_url('register') ?>">Register as a member</a>
                </p>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
