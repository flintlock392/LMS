<?= $this->extend('layouts/public') ?>
<?= $this->section('title') ?>Create account<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-xl-6">
            <div class="login-card">
                <h1>Sign up</h1>
                <p class="lead-sm">Create your student library account</p>

                <?= view('partials/flash') ?>

                <form method="post" action="<?= site_url('signup') ?>">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label" for="id_number">Student number</label>
                            <input class="form-control" id="id_number" name="id_number" placeholder="e.g. 2024-00123"
                                   value="<?= esc(old('id_number', '', false)) ?>" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="name">Full name</label>
                            <input class="form-control" id="name" name="name" placeholder="Enter..."
                                   value="<?= esc(old('name', '', false)) ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="email">Email address</label>
                            <input class="form-control" type="email" id="email" name="email" placeholder="you@example.com"
                                   value="<?= esc(old('email', '', false)) ?>" required>
                        </div>
                        <div class="col-sm-7">
                            <?= view('partials/course_field', ['current' => '', 'selectClass' => 'form-control', 'showStar' => false]) ?>
                        </div>
                        <div class="col-sm-5">
                            <label class="form-label" for="year_level">Year level</label>
                            <select class="form-control" id="year_level" name="year_level" required>
                                <?php foreach (['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year', 'Graduate'] as $y): ?>
                                    <option <?= old('year_level') === $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="password">Password</label>
                            <input class="form-control" type="password" id="password" name="password" minlength="8"
                                   autocomplete="new-password" placeholder="At least 8 characters" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="confirm">Confirm password</label>
                            <input class="form-control" type="password" id="confirm" name="confirm" minlength="8"
                                   autocomplete="new-password" placeholder="Enter..." required>
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <button class="btn btn-nook" type="submit">Create account</button>
                    </div>
                    <p class="small-links">Already have an account? <a href="<?= site_url('login') ?>">Log in</a></p>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
