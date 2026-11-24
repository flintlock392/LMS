<?= $this->extend('layouts/public') ?>
<?= $this->section('title') ?>About us<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container pb-5">
    <h1 class="pub-heading mb-3">About <?= esc(setting('library_name')) ?></h1>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="pub-panel h-100">
                <p class="fs-5" style="font-family:var(--font-display)">A warm, quiet corner of campus where every student can find the book they need.</p>
                <p>Our library serves students with a growing collection across programming, literature, science and history.
                   Browse the catalog online to see which titles are on the shelf, then borrow them at the circulation desk.</p>
                <h2 class="h6 mt-4">Borrowing at a glance</h2>
                <ul class="mb-0">
                    <li>Borrow up to <strong><?= (int) setting('max_loans') ?></strong> books at a time for <strong><?= (int) setting('loan_days') ?> days</strong>.</li>
                    <li>Late, damaged or lost books add <strong>credits</strong> to your record. More credits mean fewer books you can borrow; at <?= \App\Libraries\CreditPolicy::MAX ?> credits borrowing stops until the Head Librarian settles your status.</li>
                    <li>Log in with your <strong>student number</strong>. It also serves as your library card number.</li>
                </ul>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="pub-panel h-100">
                <h2 class="h6">Our collection</h2>
                <div class="row text-center g-3 my-1">
                    <div class="col-6"><div class="fs-2 fw-semibold"><?= number_format($stats['titles']) ?></div><div class="small text-muted">titles</div></div>
                    <div class="col-6"><div class="fs-2 fw-semibold"><?= number_format($stats['copies']) ?></div><div class="small text-muted">copies</div></div>
                </div>
                <h2 class="h6 mt-3">Visit us</h2>
                <p class="mb-1"><i class="bi bi-clock me-2"></i>Mon–Sat, 8:00 AM – 6:00 PM</p>
                <p class="mb-0"><i class="bi bi-geo-alt me-2"></i>Ground floor, Main Building</p>
                <a class="btn btn-nook mt-4" href="<?= site_url('catalog') ?>">Browse books</a>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
