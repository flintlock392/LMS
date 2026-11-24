<?php
$role    = current_role();
$path    = trim(uri_string(), '/');
$name    = (string) session()->get('name');
$initials = strtoupper(implode('', array_map(static fn ($p) => mb_substr($p, 0, 1), array_slice(preg_split('/\s+/', trim($name)) ?: [], 0, 2))));
$roleIcon = ['head_librarian' => 'award', 'librarian' => 'person-badge', 'student' => 'mortarboard'][$role] ?? 'person';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->renderSection('title', true) ?> ·<?= esc(setting('library_name')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Abhaya+Libre:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="app-body"<?= is_staff() ? ' data-suggest-url="' . site_url('suggest') . '"' : '' ?>>
<div class="app-shell">
    <aside class="sidebar" id="sidebar" aria-label="Main menu">
        <a class="brand" href="<?= site_url('dashboard') ?>">
            <img src="<?= brand_image('logo') ?>" alt="">
            <span><strong><?= esc(setting('library_name')) ?></strong><small>Library System</small></span>
        </a>
        <span class="role-chip"><i class="bi bi-<?= $roleIcon ?>"></i><?= esc(role_label($role)) ?></span>
        <nav>
            <?php foreach (nav_items($role) as $item): [$label, $icon, $target] = $item; ?>
                <?php $isActive = false;
                foreach (array_merge([$target], $item[3] ?? []) as $p) {
                    $isActive = $isActive || $path === $p || str_starts_with($path, $p . '/');
                } ?>
                <a class="nav-item-link<?= $isActive ? ' active' : '' ?>" href="<?= site_url($target) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
                    <i class="bi bi-<?= $icon ?>"></i><span><?= esc($label) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer d-flex flex-column gap-2">
            <a href="<?= site_url('account') ?>"><i class="bi bi-person-circle me-2"></i>My Account</a>
            <form method="post" action="<?= site_url('logout') ?>" class="m-0" data-confirm="Do you want to log out of <?= esc(setting('library_name')) ?>?">
                <?= csrf_field() ?>
                <button type="submit"><i class="bi bi-box-arrow-right me-2"></i>Log out</button>
            </form>
        </div>
    </aside>

    <div class="app-main">
        <header class="topbar">
            <button class="btn btn-sm btn-outline-nook sidebar-toggle" type="button" aria-controls="sidebar" aria-label="Open menu"
                    onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="bi bi-list"></i></button>
            <h1><?= $this->renderSection('title') ?></h1>
            <div class="user">
                <div class="text-end d-none d-sm-block lh-sm">
                    <div class="fw-semibold"><?= esc($name) ?></div>
                    <div class="text-muted small"><?= esc((string) session()->get('idNumber')) ?></div>
                </div>
                <a class="avatar text-decoration-none" href="<?= site_url('account') ?>" title="My account"><?= esc($initials ?: '?') ?></a>
            </div>
        </header>

        <main class="app-content">
            <?= view('partials/flash') ?>
            <?= $this->renderSection('content') ?>
        </main>
    </div>
</div>

<!-- "Are you sure?" pop-up used by every form with data-confirm="message" -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmTitle" aria-describedby="confirmMessage" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px">
        <div class="modal-content confirm-modal">
            <div class="modal-body text-center p-4">
                <div class="confirm-icon mb-3" id="confirmIcon"><i class="bi bi-question-lg"></i></div>
                <h2 class="h5 mb-2" id="confirmTitle">Are you sure?</h2>
                <p class="mb-4 text-muted" id="confirmMessage"></p>
                <div class="confirm-actions">
                    <button type="button" class="btn btn-nook confirm-btn" id="confirmYes">Yes</button>
                    <button type="button" class="btn btn-outline-nook confirm-btn" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Ask before important actions: add data-confirm="message" to a form.
    // Shows the centered "Are you sure?" pop-up; the form is only sent after "Yes".
    (function () {
        const el = document.getElementById('confirmModal');
        const modal = new bootstrap.Modal(el);
        const yes = document.getElementById('confirmYes');
        const icon = document.getElementById('confirmIcon');
        let pending = null;

        document.addEventListener('submit', function (e) {
            const form = e.target;
            const msg = form.getAttribute('data-confirm');
            if (!msg) { return; }
            e.preventDefault();
            pending = form;
            document.getElementById('confirmMessage').textContent = msg;
            // Red "Yes" and a warning icon for actions that remove or block something.
            const risky = /^(delete|suspend)|permanently|cannot be undone/i.test(msg);
            yes.className = 'btn confirm-btn ' + (risky ? 'btn-danger' : 'btn-nook');
            icon.className = 'confirm-icon mb-3' + (risky ? ' risky' : '');
            icon.innerHTML = '<i class="bi bi-' + (risky ? 'exclamation-triangle' : 'question-lg') + '"></i>';
            modal.show();
        });
        el.addEventListener('shown.bs.modal', () => yes.focus());
        el.addEventListener('hidden.bs.modal', () => { pending = null; });
        yes.addEventListener('click', function () {
            const form = pending;
            pending = null;
            yes.disabled = true;
            if (form) { form.submit(); }   // submit() skips the submit event, so no second prompt
        });
    })();
    document.addEventListener('click', function (e) {
        const sb = document.getElementById('sidebar');
        if (sb.classList.contains('open') && !sb.contains(e.target) && !e.target.closest('.sidebar-toggle')) { sb.classList.remove('open'); }
    });
</script>
<script src="<?= base_url('assets/js/suggest.js') ?>" defer></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
