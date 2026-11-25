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
    <link href="<?= asset('assets/css/app.css') ?>" rel="stylesheet">
    <script>
        // Apply the remembered sidebar state before the page paints (no flicker).
        try { if (localStorage.getItem('sidebarCollapsed') === '1') { document.documentElement.classList.add('sidebar-collapsed'); } } catch (e) {}
    </script>
</head>
<body class="app-body"<?= is_staff() ? ' data-suggest-url="' . site_url('suggest') . '"' : '' ?>>
<div class="app-shell">
    <aside class="sidebar" id="sidebar" aria-label="Main menu">
        <div class="sidebar-head">
            <a class="brand" href="<?= site_url('dashboard') ?>" title="<?= esc(setting('library_name')) ?>">
                <img src="<?= brand_image('logo') ?>" alt="">
                <span><strong><?= esc(setting('library_name')) ?></strong><small>Library System</small></span>
            </a>
            <button class="sidebar-toggle" type="button" id="sidebarToggle" aria-controls="sidebar" aria-label="Collapse menu" title="Show or hide the menu"><i class="bi bi-list"></i></button>
        </div>
        <span class="role-chip" title="<?= esc(role_label($role)) ?>"><i class="bi bi-<?= $roleIcon ?>"></i><span><?= esc(role_label($role)) ?></span></span>
        <nav>
            <?php foreach (nav_items($role) as $item): [$label, $icon, $target] = $item; ?>
                <?php $isActive = false;
                foreach (array_merge([$target], $item[3] ?? []) as $p) {
                    $isActive = $isActive || $path === $p || str_starts_with($path, $p . '/');
                } ?>
                <a class="nav-item-link<?= $isActive ? ' active' : '' ?>" href="<?= site_url($target) ?>" title="<?= esc($label) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
                    <i class="bi bi-<?= $icon ?>"></i><span><?= esc($label) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer d-flex flex-column gap-2">
            <a href="<?= site_url('account') ?>" title="My Account"><i class="bi bi-person-circle"></i><span>My Account</span></a>
            <form method="post" action="<?= site_url('logout') ?>" class="m-0" data-confirm="Do you want to log out of <?= esc(setting('library_name')) ?>?">
                <?= csrf_field() ?>
                <button type="submit" title="Log out"><i class="bi bi-box-arrow-right"></i><span>Log out</span></button>
            </form>
        </div>
    </aside>

    <div class="app-main">
        <header class="topbar">
            <!-- Phones only: the sidebar is hidden there, so this opens it -->
            <button class="sidebar-toggle sidebar-open-mobile" type="button" id="sidebarOpen" aria-controls="sidebar" aria-label="Open menu"><i class="bi bi-list"></i></button>
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
    // Burger: on wide screens it shrinks the sidebar to icons only (remembered on every page);
    // on small screens it slides the full menu in and out.
    (function () {
        const btn = document.getElementById('sidebarToggle');
        const sb = document.getElementById('sidebar');
        const root = document.documentElement;
        const wide = window.matchMedia('(min-width: 992px)');
        const label = () => btn.setAttribute('aria-label', wide.matches
            ? (root.classList.contains('sidebar-collapsed') ? 'Expand menu' : 'Collapse menu')
            : 'Close menu');
        document.getElementById('sidebarOpen').addEventListener('click', () => sb.classList.add('open'));
        btn.addEventListener('click', function () {
            if (wide.matches) {
                const collapsed = root.classList.toggle('sidebar-collapsed');
                try { localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0'); } catch (e) {}
            } else {
                sb.classList.toggle('open');
            }
            label();
        });
        wide.addEventListener('change', label);
        label();
    })();
    document.addEventListener('click', function (e) {
        const sb = document.getElementById('sidebar');
        if (sb.classList.contains('open') && !sb.contains(e.target) && !e.target.closest('.sidebar-toggle')) { sb.classList.remove('open'); }
    });
</script>
<script src="<?= asset('assets/js/suggest.js') ?>" defer></script>
<script src="<?= asset('assets/js/filters.js') ?>" defer></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
