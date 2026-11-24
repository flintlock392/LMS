<?= $this->extend('layouts/public') ?>
<?= $this->section('title') ?>Forgot password<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-xl-5">
            <div class="login-card">
                <h1 style="font-size:2rem">Forgot password?</h1>
                <p class="lead-sm">We'll help you get back in.</p>
                <ol class="small mb-4">
                    <li class="mb-2">Visit the library circulation desk and bring your school ID.</li>
                    <li class="mb-2">The Head Librarian will verify your identity and set a temporary password.</li>
                    <li>Log in with the temporary password, then change it under <strong>My Account</strong>.</li>
                </ol>
                <p class="small text-muted mb-4">Library staff: ask the Head Librarian to reset your password from Staff Management.</p>
                <div class="text-center">
                    <a class="btn btn-nook" href="<?= site_url('login') ?>">Back to login</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
