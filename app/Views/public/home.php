<?= $this->extend('layouts/public') ?>
<?= $this->section('title') ?>Welcome<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="hero">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                <h1 class="hero-title">Welcome</h1>
                <p class="hero-sub">the gateway to<br>knowledge</p>
                <img class="hero-art" src="<?= brand_image('hero-books') ?>" alt="" width="1397" height="1126">
            </div>

            <div class="col-lg-5 offset-lg-1 align-self-center">
                <?php if (session()->get('isLoggedIn')): ?>
                    <div class="login-card text-center">
                        <div class="login-card-inner">
                            <h1>Hello!</h1>
                            <p class="lead-sm">You are signed in as <strong><?= esc(session()->get('name')) ?></strong> (<?= esc(role_label(session()->get('role'))) ?>).</p>
                            <a class="btn btn-nook" href="<?= site_url('dashboard') ?>">Go to dashboard</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="login-card">
                        <div class="login-card-inner">
                            <h1>Login</h1>
                            <p class="lead-sm">Fill your login details</p>

                            <?= view('partials/flash') ?>

                            <form method="post" action="<?= site_url('login') ?>" novalidate>
                                <?= csrf_field() ?>
                                <div class="mb-3">
                                    <label class="form-label" for="login">Student number</label>
                                    <!-- Library staff sign in here with their Staff ID. -->
                                    <input class="form-control" type="text" id="login" name="login" placeholder="Enter..."
                                           value="<?= esc(old('login', '', false)) ?>" autocomplete="username" required autofocus>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label" for="password">Password</label>
                                    <div class="position-relative">
                                        <input class="form-control pe-5" type="password" id="password" name="password" placeholder="Enter..."
                                               autocomplete="current-password" required>
                                        <button type="button" class="btn btn-sm position-absolute top-50 end-0 translate-middle-y me-1 border-0"
                                                onclick="const p=document.getElementById('password');p.type=p.type==='password'?'text':'password';this.firstElementChild.classList.toggle('bi-eye');this.firstElementChild.classList.toggle('bi-eye-slash')"
                                                aria-label="Show or hide password"><i class="bi bi-eye"></i></button>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <button class="btn btn-nook" type="submit">Login</button>
                                </div>
                                <p class="small-links">
                                    <a href="<?= site_url('forgot-password') ?>">Forgot password</a>
                                </p>
                                <p class="small-links mt-1 text-muted">No account yet? The library staff will register you at the desk.</p>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?= $this->endSection() ?>
